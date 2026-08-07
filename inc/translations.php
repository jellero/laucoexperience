<?php
declare(strict_types=1);

if (!function_exists('content_supported_languages')) {
    /** @return array<string,string> */
    function content_supported_languages(): array
    {
        return ['it' => 'Italiano', 'en' => 'English', 'de' => 'Deutsch', 'sl' => 'Slovenščina'];
    }
}

if (!function_exists('content_language_from_request')) {
    function content_language_from_request(): string
    {
        static $resolved;
        if (is_string($resolved)) {
            return $resolved;
        }

        $supported = content_supported_languages();
        $requested = isset($_GET['lang']) ? strtolower(trim((string) $_GET['lang'])) : '';
        if ($requested !== '' && array_key_exists($requested, $supported)) {
            $resolved = $requested;

            if (PHP_SAPI !== 'cli' && !headers_sent()) {
                $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                    || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
                setcookie('lauco_lang', $resolved, [
                    'expires' => time() + 60 * 60 * 24 * 365,
                    'path' => '/',
                    'secure' => $https,
                    'httponly' => false,
                    'samesite' => 'Lax',
                ]);
            }

            return $resolved;
        }

        $cookie = strtolower(trim((string) ($_COOKIE['lauco_lang'] ?? '')));
        $resolved = array_key_exists($cookie, $supported) ? $cookie : 'it';
        return $resolved;
    }
}

if (!function_exists('content_translation_find')) {
    /** @return array<string,mixed>|null */
    function content_translation_find(PDO $pdo, string $entityType, int $entityId, string $language): ?array
    {
        if ($language === 'it') {
            return null;
        }
        try {
            $stmt = $pdo->prepare(
                "SELECT * FROM content_translations
                 WHERE entity_type = :entity_type AND entity_id = :entity_id
                   AND language = :language AND status = 'published'
                 LIMIT 1"
            );
            $stmt->execute(['entity_type' => $entityType, 'entity_id' => $entityId, 'language' => $language]);
            $row = $stmt->fetch();
            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('content_apply_translation')) {
    /** @param array<string,mixed> $entity @return array<string,mixed> */
    function content_apply_translation(PDO $pdo, string $entityType, array $entity, string $language): array
    {
        $translation = content_translation_find($pdo, $entityType, (int) ($entity['id'] ?? 0), $language);
        if (!$translation) {
            $entity['_content_language'] = 'it';
            return $entity;
        }

        $map = match ($entityType) {
            'evento' => ['title' => 'titolo', 'excerpt' => 'excerpt', 'body' => 'contenuto'],
            'luogo' => ['title' => 'titolo', 'subtitle' => 'sottotitolo', 'excerpt' => 'excerpt', 'body' => 'descrizione'],
            'galleria' => ['title' => 'titolo', 'subtitle' => 'alt'],
            'slider' => ['title' => 'titolo', 'subtitle' => 'sottotitolo', 'excerpt' => 'button_label'],
            default => ['title' => 'titolo', 'subtitle' => 'sottotitolo', 'excerpt' => 'excerpt', 'body' => 'descrizione'],
        };

        foreach ($map as $source => $target) {
            if (isset($translation[$source]) && trim((string) $translation[$source]) !== '') {
                $entity[$target] = $translation[$source];
            }
        }
        $entity['_content_language'] = $language;
        return $entity;
    }
}

if (!function_exists('content_lang_query')) {
    function content_lang_query(string $language): string
    {
        return $language !== 'it' ? '&lang=' . rawurlencode($language) : '';
    }
}

if (!function_exists('content_language_url')) {
    function content_language_url(string $language, ?string $url = null): string
    {
        if (!array_key_exists($language, content_supported_languages())) {
            $language = 'it';
        }
        $url ??= (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $parts = parse_url($url);
        $path = (string) ($parts['path'] ?? '/');
        $query = [];
        parse_str((string) ($parts['query'] ?? ''), $query);
        $query['lang'] = $language;
        $encoded = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        return $path . ($encoded !== '' ? '?' . $encoded : '');
    }
}

if (!function_exists('site_catalog_repository')) {
    function site_catalog_repository(): ?\LaucoExperience\Localization\SiteCatalogRepository
    {
        static $repository;
        static $attempted = false;
        if ($attempted) {
            return $repository;
        }
        $attempted = true;
        $root = dirname(__DIR__);
        $autoload = $root . '/vendor/autoload.php';
        if (!class_exists(\LaucoExperience\Localization\SiteCatalogRepository::class) && is_file($autoload)) {
            require_once $autoload;
        }
        if (!class_exists(\LaucoExperience\Localization\SiteCatalogRepository::class)) {
            return null;
        }
        $repository = new \LaucoExperience\Localization\SiteCatalogRepository(
            $root . '/resources/lang',
            $root . '/storage/translations'
        );
        return $repository;
    }
}

if (!function_exists('site_text')) {
    function site_text(string $key, ?string $locale = null, ?string $fallback = null): string
    {
        $repository = site_catalog_repository();
        if (!$repository) {
            return $fallback ?? $key;
        }
        $locale ??= content_language_from_request();
        try {
            $catalog = $repository->load($locale);
            return trim((string) ($catalog[$key] ?? '')) !== '' ? (string) $catalog[$key] : ($fallback ?? $key);
        } catch (Throwable $e) {
            return $fallback ?? $key;
        }
    }
}
