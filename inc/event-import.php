<?php
declare(strict_types=1);

require_once __DIR__ . '/http-client.php';

if (!function_exists('event_import_sources')) {
    /** @return array<string,array<string,mixed>> */
    function event_import_sources(): array
    {
        return require dirname(__DIR__) . '/config/event_sources.php';
    }
}

if (!function_exists('event_import_absolute_url')) {
    function event_import_absolute_url(string $baseUrl, string $href): ?string
    {
        $href = trim(html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($href === '' || str_starts_with($href, '#') || str_starts_with(strtolower($href), 'javascript:')) {
            return null;
        }
        if (preg_match('~^https://~i', $href)) {
            return $href;
        }
        $base = parse_url($baseUrl);
        if (!isset($base['scheme'], $base['host'])) {
            return null;
        }
        if (str_starts_with($href, '//')) {
            return 'https:' . $href;
        }
        $port = isset($base['port']) ? ':' . $base['port'] : '';
        $origin = $base['scheme'] . '://' . $base['host'] . $port;
        if (str_starts_with($href, '/')) {
            return $origin . $href;
        }
        $path = (string) ($base['path'] ?? '/');
        $directory = rtrim(str_replace('\\', '/', dirname($path)), '/');
        return $origin . ($directory !== '' ? $directory : '') . '/' . $href;
    }
}

if (!function_exists('event_import_listing_links')) {
    /** @param array<string,mixed> $config @return list<string> */
    function event_import_listing_links(string $html, array $config): array
    {
        if (!class_exists('DOMDocument')) {
            throw new RuntimeException('L’estensione PHP DOM è necessaria per importare gli eventi.');
        }
        $document = new DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML($html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        $xpath = new DOMXPath($document);
        $links = [];
        $pattern = (string) ($config['link_pattern'] ?? '~event~i');
        foreach ($xpath->query('//a[@href]') ?: [] as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            $url = event_import_absolute_url((string) $config['listing_url'], $node->getAttribute('href'));
            if ($url === null || !preg_match($pattern, (string) (parse_url($url, PHP_URL_PATH) ?? ''))) {
                continue;
            }
            try {
                lauco_http_assert_url($url, $config['allowed_hosts']);
            } catch (Throwable $e) {
                continue;
            }
            $links[$url] = true;
        }
        return array_keys($links);
    }
}

if (!function_exists('event_import_jsonld_nodes')) {
    /** @return list<array<string,mixed>> */
    function event_import_jsonld_nodes(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }
        $nodes = [];
        $type = $value['@type'] ?? null;
        $types = is_array($type) ? $type : [$type];
        if (in_array('Event', $types, true)) {
            $nodes[] = $value;
        }
        foreach ($value as $child) {
            if (is_array($child)) {
                $nodes = array_merge($nodes, event_import_jsonld_nodes($child));
            }
        }
        return $nodes;
    }
}

if (!function_exists('event_import_https_url')) {
    function event_import_https_url(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        $parts = parse_url($value);
        return strtolower((string) ($parts['scheme'] ?? '')) === 'https'
            && trim((string) ($parts['host'] ?? '')) !== ''
            ? $value
            : null;
    }
}

if (!function_exists('event_import_page_events')) {
    /** @return list<array<string,mixed>> */
    function event_import_page_events(string $html, string $sourceUrl): array
    {
        if (!class_exists('DOMDocument')) {
            throw new RuntimeException('L’estensione PHP DOM è necessaria per importare gli eventi.');
        }
        $document = new DOMDocument();
        libxml_use_internal_errors(true);
        $document->loadHTML($html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR);
        libxml_clear_errors();
        $xpath = new DOMXPath($document);
        $events = [];
        foreach ($xpath->query('//script[contains(translate(@type,"ABCDEFGHIJKLMNOPQRSTUVWXYZ","abcdefghijklmnopqrstuvwxyz"),"ld+json")]') ?: [] as $script) {
            $json = trim((string) $script->textContent);
            if ($json === '') {
                continue;
            }
            try {
                $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            } catch (Throwable $e) {
                continue;
            }
            foreach (event_import_jsonld_nodes($decoded) as $node) {
                $location = is_array($node['location'] ?? null) ? $node['location'] : [];
                $address = is_array($location['address'] ?? null) ? $location['address'] : [];
                $organizer = is_array($node['organizer'] ?? null) ? $node['organizer'] : [];
                $image = $node['image'] ?? null;
                if (is_array($image)) {
                    $image = is_string($image[0] ?? null) ? $image[0] : (($image[0]['url'] ?? null));
                }
                $identifier = $node['identifier'] ?? null;
                if (is_array($identifier)) {
                    $identifier = $identifier['value'] ?? $identifier['@id'] ?? null;
                }
                $title = trim(strip_tags((string) ($node['name'] ?? '')));
                if ($title === '') {
                    continue;
                }
                $events[] = [
                    'external_id' => trim((string) ($identifier ?: ($node['@id'] ?? hash('sha256', $sourceUrl . '|' . $title . '|' . ($node['startDate'] ?? ''))))),
                    'title' => $title,
                    'description' => trim(strip_tags((string) ($node['description'] ?? ''))),
                    'start_at_raw' => trim((string) ($node['startDate'] ?? '')),
                    'end_at_raw' => trim((string) ($node['endDate'] ?? '')),
                    'location_name' => trim(strip_tags((string) ($location['name'] ?? ''))),
                    'locality' => trim(strip_tags((string) ($address['addressLocality'] ?? $location['address'] ?? ''))),
                    'organizer' => trim(strip_tags((string) ($organizer['name'] ?? ''))),
                    'source_url' => $sourceUrl,
                    'image_url' => event_import_https_url($image),
                    'raw' => $node,
                ];
            }
        }
        return $events;
    }
}

if (!function_exists('event_import_locality_allowed')) {
    /** @param list<string> $allowlist */
    function event_import_locality_allowed(array $event, array $allowlist): bool
    {
        $haystack = mb_strtolower(implode(' ', [$event['title'] ?? '', $event['description'] ?? '', $event['location_name'] ?? '', $event['locality'] ?? '']));
        foreach ($allowlist as $locality) {
            if (str_contains($haystack, mb_strtolower($locality))) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('event_import_fetch')) {
    /** @return list<array<string,mixed>> */
    function event_import_fetch(string $sourceKey): array
    {
        $sources = event_import_sources();
        $config = $sources[$sourceKey] ?? null;
        if (!is_array($config) || empty($config['enabled'])) {
            throw new RuntimeException('Fonte non disponibile.');
        }
        $listing = lauco_http_get_allowlisted((string) $config['listing_url'], $config['allowed_hosts'], 30);
        $links = array_slice(event_import_listing_links($listing, $config), 0, max(1, lauco_env_int('EVENT_IMPORT_MAX_ITEMS', 40)));
        $events = [];
        foreach ($links as $link) {
            try {
                $page = lauco_http_get_allowlisted($link, $config['allowed_hosts'], 30);
                foreach (event_import_page_events($page, $link) as $event) {
                    if (event_import_locality_allowed($event, $config['localities'])) {
                        $events[$sourceKey . '|' . $event['external_id']] = $event;
                    }
                }
            } catch (Throwable $e) {
                continue;
            }
        }
        return array_values($events);
    }
}

if (!function_exists('event_import_stage')) {
    /** @param list<array<string,mixed>> $events */
    function event_import_stage(PDO $pdo, string $sourceKey, array $events, int $adminId): int
    {
        $stmt = $pdo->prepare("INSERT INTO event_import_runs (source_key, status, candidate_count, started_by, started_at, finished_at) VALUES (:source, 'completed', :count, :admin, NOW(), NOW())");
        $stmt->execute(['source' => $sourceKey, 'count' => count($events), 'admin' => $adminId ?: null]);
        $runId = (int) $pdo->lastInsertId();
        $insert = $pdo->prepare(
            "INSERT INTO event_import_candidates
             (run_id, source_key, external_id, title, description, start_at_raw, end_at_raw, location_name, locality, organizer, source_url, image_url, raw_payload, review_status)
             VALUES (:run_id, :source_key, :external_id, :title, :description, :start_at_raw, :end_at_raw, :location_name, :locality, :organizer, :source_url, :image_url, :raw_payload, 'pending')
             ON DUPLICATE KEY UPDATE run_id = VALUES(run_id), title = VALUES(title), description = VALUES(description), start_at_raw = VALUES(start_at_raw), end_at_raw = VALUES(end_at_raw), location_name = VALUES(location_name), locality = VALUES(locality), organizer = VALUES(organizer), source_url = VALUES(source_url), image_url = VALUES(image_url), raw_payload = VALUES(raw_payload), review_status = IF(review_status IN ('approved','rejected','published'), review_status, 'pending'), updated_at = NOW()"
        );
        foreach ($events as $event) {
            $insert->execute([
                'run_id' => $runId,
                'source_key' => $sourceKey,
                'external_id' => (string) $event['external_id'],
                'title' => (string) $event['title'],
                'description' => (string) $event['description'],
                'start_at_raw' => (string) $event['start_at_raw'],
                'end_at_raw' => (string) $event['end_at_raw'],
                'location_name' => (string) $event['location_name'],
                'locality' => (string) $event['locality'],
                'organizer' => (string) $event['organizer'],
                'source_url' => (string) $event['source_url'],
                'image_url' => $event['image_url'],
                'raw_payload' => json_encode($event['raw'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            ]);
        }
        return $runId;
    }
}

if (!function_exists('event_import_candidate')) {
    /** @return array<string,mixed> */
    function event_import_candidate(PDO $pdo, int $id): array
    {
        $stmt = $pdo->prepare('SELECT * FROM event_import_candidates WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $candidate = $stmt->fetch();
        if (!is_array($candidate)) {
            throw new RuntimeException('Candidato evento non trovato.');
        }
        return $candidate;
    }
}

if (!function_exists('event_import_date')) {
    function event_import_date(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }
        try {
            return (new DateTimeImmutable($raw))->format('Y-m-d');
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('event_import_unique_slug')) {
    function event_import_unique_slug(PDO $pdo, string $title): string
    {
        $base = slugify($title) ?: 'evento';
        $slug = $base;
        $counter = 2;
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM eventi WHERE slug = :slug');
        while (true) {
            $stmt->execute(['slug' => $slug]);
            if ((int) $stmt->fetchColumn() === 0) {
                return $slug;
            }
            $slug = $base . '-' . $counter++;
        }
    }
}

if (!function_exists('event_import_review')) {
    function event_import_review(PDO $pdo, int $candidateId, string $action, int $adminId): ?int
    {
        $candidate = event_import_candidate($pdo, $candidateId);
        $status = (string) ($candidate['review_status'] ?? 'pending');
        if ($action === 'reject') {
            if (!empty($candidate['published_event_id']) || in_array($status, ['approved', 'published'], true)) {
                throw new RuntimeException('Un candidato già trasformato in evento non può essere rifiutato.');
            }
            if ($status === 'rejected') {
                return null;
            }
            $stmt = $pdo->prepare("UPDATE event_import_candidates SET review_status = 'rejected', reviewed_by = :admin, reviewed_at = NOW() WHERE id = :id");
            $stmt->execute(['admin' => $adminId ?: null, 'id' => $candidateId]);
            return null;
        }
        if ($action !== 'approve') {
            throw new RuntimeException('Azione non valida.');
        }
        if ($status === 'rejected') {
            throw new RuntimeException('Un candidato rifiutato non può essere approvato.');
        }
        if (!empty($candidate['published_event_id'])) {
            return (int) $candidate['published_event_id'];
        }
        if ($status !== 'pending') {
            throw new RuntimeException('Lo stato corrente non consente la creazione dell’evento.');
        }
        $pdo->beginTransaction();
        try {
            $slug = event_import_unique_slug($pdo, (string) $candidate['title']);
            $description = trim((string) $candidate['description']);
            $excerpt = mb_substr($description, 0, 350);
            $stmt = $pdo->prepare(
                "INSERT INTO eventi (titolo, slug, data_evento, localita, categoria, excerpt, contenuto, cover_image, ordine, pubblicato)
                 VALUES (:title, :slug, :date, :locality, 'Eventi', :excerpt, :content, :cover, 0, 0)"
            );
            $stmt->execute([
                'title' => (string) $candidate['title'],
                'slug' => $slug,
                'date' => event_import_date($candidate['start_at_raw']),
                'locality' => trim((string) ($candidate['locality'] ?: $candidate['location_name'])),
                'excerpt' => $excerpt,
                'content' => $description,
                'cover' => trim((string) ($candidate['image_url'] ?? '')) ?: null,
            ]);
            $eventId = (int) $pdo->lastInsertId();
            $stmt = $pdo->prepare('INSERT INTO eventi_fonti (evento_id, source_key, source_url) VALUES (:event_id, :source_key, :source_url)');
            $stmt->execute(['event_id' => $eventId, 'source_key' => (string) $candidate['source_key'], 'source_url' => (string) $candidate['source_url']]);
            $stmt = $pdo->prepare("UPDATE event_import_candidates SET review_status = 'approved', published_event_id = :event_id, reviewed_by = :admin, reviewed_at = NOW() WHERE id = :id");
            $stmt->execute(['event_id' => $eventId, 'admin' => $adminId ?: null, 'id' => $candidateId]);
            $pdo->commit();
            return $eventId;
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
