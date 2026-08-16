<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/http-client.php';

if (!function_exists('facebook_publishing_config')) {
    /** @return array{enabled:bool,version:string,page_id:string,token:string,app_secret:string,timeout:int} */
    function facebook_publishing_config(): array
    {
        $version = trim((string) lauco_env('FACEBOOK_GRAPH_VERSION', 'v26.0'));
        if (!preg_match('/^v\d+\.\d+$/', $version)) {
            $version = 'v26.0';
        }

        return [
            'enabled' => lauco_env_bool('FACEBOOK_ENABLED'),
            'version' => $version,
            'page_id' => trim((string) lauco_env('FACEBOOK_PAGE_ID', '')),
            'token' => trim((string) lauco_env('FACEBOOK_PAGE_ACCESS_TOKEN', '')),
            'app_secret' => trim((string) lauco_env('FACEBOOK_APP_SECRET', '')),
            'timeout' => max(5, min(90, lauco_env_int('FACEBOOK_TIMEOUT_SECONDS', 20))),
        ];
    }
}

if (!function_exists('facebook_publishing_missing_config')) {
    /** @return list<string> */
    function facebook_publishing_missing_config(): array
    {
        $config = facebook_publishing_config();
        $missing = [];
        if (!$config['enabled']) {
            $missing[] = 'FACEBOOK_ENABLED=true';
        }
        if ($config['page_id'] === '') {
            $missing[] = 'FACEBOOK_PAGE_ID';
        }
        if ($config['token'] === '') {
            $missing[] = 'FACEBOOK_PAGE_ACCESS_TOKEN';
        }
        return $missing;
    }
}

if (!function_exists('facebook_publishing_ready')) {
    function facebook_publishing_ready(): bool
    {
        return facebook_publishing_missing_config() === [];
    }
}

if (!function_exists('facebook_publishing_clean_text')) {
    function facebook_publishing_clean_text(string $value, int $limit = 5000): string
    {
        $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $value = preg_replace("/\r\n?|\n/u", "\n", $value) ?? $value;
        $value = preg_replace('/[ \t]+/u', ' ', $value) ?? $value;
        $value = preg_replace('/\n{3,}/u', "\n\n", $value) ?? $value;
        $value = trim($value);
        if (mb_strlen($value) > $limit) {
            $value = rtrim(mb_substr($value, 0, $limit - 1)) . '…';
        }
        return $value;
    }
}

if (!function_exists('facebook_publishing_entity_url')) {
    function facebook_publishing_entity_url(string $entityType, string $slug): string
    {
        $baseUrl = trim((string) lauco_env('APP_URL', 'https://laucoexperience.it'));
        if (!preg_match('~^https://~i', $baseUrl)) {
            $baseUrl = 'https://laucoexperience.it';
        }
        $path = $entityType === 'evento' ? '/evento' : '/percorso';
        return rtrim($baseUrl, '/') . $path . '?' . http_build_query(['slug' => $slug], '', '&', PHP_QUERY_RFC3986);
    }
}

if (!function_exists('facebook_publishing_default_message')) {
    /** @param array<string,mixed> $entity */
    function facebook_publishing_default_message(string $entityType, array $entity): string
    {
        $title = facebook_publishing_clean_text((string) ($entity['titolo'] ?? ''), 300);
        if ($title === '') {
            return '';
        }

        $lines = [];
        if ($entityType === 'evento') {
            $lines[] = $title;
            $details = [];
            $date = trim((string) ($entity['data_evento'] ?? ''));
            if ($date !== '') {
                $timestamp = strtotime($date);
                if ($timestamp !== false) {
                    $details[] = date('d/m/Y', $timestamp);
                }
            }
            $location = facebook_publishing_clean_text((string) ($entity['localita'] ?? ''), 190);
            if ($location !== '') {
                $details[] = $location;
            }
            if ($details !== []) {
                $lines[] = implode(' · ', $details);
            }
        } else {
            $type = (($entity['tipo'] ?? '') === 'mtb') ? 'Itinerario MTB' : 'Itinerario a piedi';
            $lines[] = $type . ': ' . $title;
            $location = facebook_publishing_clean_text((string) ($entity['localita'] ?? ''), 190);
            if ($location !== '') {
                $lines[] = $location;
            }
        }

        $excerpt = facebook_publishing_clean_text((string) ($entity['excerpt'] ?? ''), 700);
        if ($excerpt !== '') {
            $lines[] = '';
            $lines[] = $excerpt;
        }
        $lines[] = '';
        $lines[] = 'Scopri tutti i dettagli su Lauco Experience.';

        return facebook_publishing_clean_text(implode("\n", $lines));
    }
}

if (!function_exists('facebook_publishing_latest')) {
    /** @return array<string,mixed>|null */
    function facebook_publishing_latest(PDO $pdo, string $entityType, int $entityId): ?array
    {
        if ($entityId <= 0) {
            return null;
        }
        try {
            $stmt = $pdo->prepare('SELECT * FROM facebook_publications WHERE entity_type = :type AND entity_id = :id ORDER BY COALESCE(updated_at, created_at) DESC, id DESC LIMIT 1');
            $stmt->execute(['type' => $entityType, 'id' => $entityId]);
            $row = $stmt->fetch();
            return is_array($row) ? $row : null;
        } catch (Throwable) {
            return null;
        }
    }
}

if (!function_exists('facebook_publishing_post_url')) {
    function facebook_publishing_post_url(?string $postId): ?string
    {
        $postId = trim((string) $postId);
        if ($postId === '') {
            return null;
        }
        if (str_contains($postId, '_')) {
            [$pageId, $storyId] = array_pad(explode('_', $postId, 2), 2, '');
            if ($pageId !== '' && $storyId !== '') {
                return 'https://www.facebook.com/' . rawurlencode($pageId) . '/posts/' . rawurlencode($storyId);
            }
        }
        return 'https://www.facebook.com/' . rawurlencode($postId);
    }
}

if (!function_exists('facebook_publishing_graph_error')) {
    /** @param array<string,mixed>|null $decoded */
    function facebook_publishing_graph_error(?array $decoded, int $status): string
    {
        $error = is_array($decoded['error'] ?? null) ? $decoded['error'] : [];
        $message = facebook_publishing_clean_text((string) ($error['message'] ?? ''), 900);
        $code = isset($error['code']) ? (int) $error['code'] : 0;
        if ($message !== '') {
            return $message . ($code > 0 ? ' (Meta #' . $code . ')' : '');
        }
        return 'Facebook ha restituito HTTP ' . $status . '.';
    }
}

if (!function_exists('facebook_publish_entity')) {
    /**
     * @param array<string,mixed> $entity
     * @return array{status:string,message:string,publication:?array}
     */
    function facebook_publish_entity(PDO $pdo, string $entityType, int $entityId, array $entity, string $customMessage, int $adminId): array
    {
        if (!in_array($entityType, ['evento', 'percorso'], true) || $entityId <= 0) {
            return ['status' => 'failed', 'message' => 'Contenuto Facebook non valido.', 'publication' => null];
        }
        if (empty($entity['pubblicato'])) {
            return ['status' => 'unpublished', 'message' => 'Pubblica prima il contenuto sul sito.', 'publication' => null];
        }
        if (!facebook_publishing_ready()) {
            return ['status' => 'unconfigured', 'message' => 'Configurazione Facebook incompleta.', 'publication' => null];
        }

        $slug = trim((string) ($entity['slug'] ?? ''));
        if ($slug === '') {
            return ['status' => 'failed', 'message' => 'Slug pubblico mancante.', 'publication' => null];
        }
        $message = facebook_publishing_clean_text($customMessage);
        if ($message === '') {
            $message = facebook_publishing_default_message($entityType, $entity);
        }
        if ($message === '') {
            return ['status' => 'failed', 'message' => 'Il testo Facebook è vuoto.', 'publication' => null];
        }

        $linkUrl = facebook_publishing_entity_url($entityType, $slug);
        $contentHash = hash('sha256', $entityType . "\n" . $entityId . "\n" . $message . "\n" . $linkUrl);
        $publicationId = 0;

        try {
            $stmt = $pdo->prepare('SELECT * FROM facebook_publications WHERE entity_type = :type AND entity_id = :id AND content_hash = :hash LIMIT 1');
            $stmt->execute(['type' => $entityType, 'id' => $entityId, 'hash' => $contentHash]);
            $existing = $stmt->fetch();
            if (is_array($existing) && ($existing['status'] ?? '') === 'published') {
                return ['status' => 'duplicate', 'message' => 'Questo contenuto è già stato pubblicato su Facebook.', 'publication' => $existing];
            }

            if (is_array($existing)) {
                $publicationId = (int) $existing['id'];
                $stmt = $pdo->prepare("UPDATE facebook_publications SET message = :message, link_url = :link, status = 'pending', attempts = attempts + 1, error_message = NULL, response_json = NULL, created_by = :user WHERE id = :id");
                $stmt->execute(['message' => $message, 'link' => $linkUrl, 'user' => $adminId ?: null, 'id' => $publicationId]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO facebook_publications (entity_type, entity_id, content_hash, message, link_url, status, created_by) VALUES (:type, :entity_id, :hash, :message, :link, 'pending', :user)");
                $stmt->execute([
                    'type' => $entityType,
                    'entity_id' => $entityId,
                    'hash' => $contentHash,
                    'message' => $message,
                    'link' => $linkUrl,
                    'user' => $adminId ?: null,
                ]);
                $publicationId = (int) $pdo->lastInsertId();
            }

            $config = facebook_publishing_config();
            $payload = ['message' => $message, 'link' => $linkUrl];
            if ($config['app_secret'] !== '') {
                $payload['appsecret_proof'] = hash_hmac('sha256', $config['token'], $config['app_secret']);
            }
            $endpoint = 'https://graph.facebook.com/' . rawurlencode($config['version']) . '/' . rawurlencode($config['page_id']) . '/feed';
            $response = lauco_http_request('POST', $endpoint, [
                'Accept: application/json',
                'Authorization: Bearer ' . $config['token'],
                'Content-Type: application/x-www-form-urlencoded',
            ], http_build_query($payload, '', '&', PHP_QUERY_RFC3986), $config['timeout']);

            $decoded = json_decode($response['body'], true);
            $decoded = is_array($decoded) ? $decoded : null;
            if ($response['status'] < 200 || $response['status'] >= 300 || !is_string($decoded['id'] ?? null) || trim((string) $decoded['id']) === '') {
                throw new RuntimeException(facebook_publishing_graph_error($decoded, $response['status']));
            }

            $postId = trim((string) $decoded['id']);
            $responseJson = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $stmt = $pdo->prepare("UPDATE facebook_publications SET facebook_post_id = :post_id, status = 'published', error_message = NULL, response_json = :response, published_at = NOW() WHERE id = :id");
            $stmt->execute(['post_id' => $postId, 'response' => $responseJson ?: null, 'id' => $publicationId]);

            $publication = facebook_publishing_latest($pdo, $entityType, $entityId);
            return ['status' => 'published', 'message' => 'Pubblicato correttamente su Facebook.', 'publication' => $publication];
        } catch (Throwable $exception) {
            $error = facebook_publishing_clean_text($exception->getMessage(), 1200);
            if ($publicationId > 0) {
                try {
                    $stmt = $pdo->prepare("UPDATE facebook_publications SET status = 'failed', error_message = :error WHERE id = :id");
                    $stmt->execute(['error' => $error, 'id' => $publicationId]);
                } catch (Throwable) {
                    // Il salvataggio del contenuto principale non deve mai essere annullato da un errore Facebook.
                }
            }
            return ['status' => 'failed', 'message' => $error ?: 'Invio Facebook non riuscito.', 'publication' => facebook_publishing_latest($pdo, $entityType, $entityId)];
        }
    }
}
