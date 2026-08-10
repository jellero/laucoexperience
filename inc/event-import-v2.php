<?php
declare(strict_types=1);

require_once __DIR__ . '/http-client.php';

if (!function_exists('event_import_absolute_url')) {
    function event_import_absolute_url(string $baseUrl, string $href): ?string
    {
        $href = trim(html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($href === '' || str_starts_with($href, '#') || str_starts_with(strtolower($href), 'javascript:')) {
            return null;
        }
        try {
            return lauco_http_resolve_url($baseUrl, $href);
        } catch (Throwable $e) {
            return null;
        }
    }
}

if (!function_exists('event_import_link_is_allowed')) {
    /** @param array<string,mixed> $config */
    function event_import_link_is_allowed(string $url, array $config): bool
    {
        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        $pattern = (string) ($config['link_pattern'] ?? '~event~i');
        if ($path === '' || !preg_match($pattern, $path)) {
            return false;
        }
        foreach (($config['exclude_path_patterns'] ?? []) as $excludePattern) {
            if (is_string($excludePattern) && $excludePattern !== '' && preg_match($excludePattern, $path)) {
                return false;
            }
        }
        try {
            lauco_http_assert_url($url, $config['allowed_hosts']);
        } catch (Throwable $e) {
            return false;
        }
        return true;
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

        $preferred = [];
        $links = [];
        $preferredTextPattern = (string) ($config['preferred_link_text_pattern'] ?? '');

        foreach ($xpath->query('//a[@href]') ?: [] as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }
            $url = event_import_absolute_url((string) $config['listing_url'], $node->getAttribute('href'));
            if ($url === null || !event_import_link_is_allowed($url, $config)) {
                continue;
            }
            $text = trim(preg_replace('/\s+/u', ' ', $node->textContent) ?? '');
            if ($preferredTextPattern !== '' && preg_match($preferredTextPattern, $text)) {
                $preferred[$url] = true;
            } else {
                $links[$url] = true;
            }
        }

        foreach (($config['raw_link_patterns'] ?? []) as $rawPattern) {
            if (!is_string($rawPattern) || $rawPattern === '') {
                continue;
            }
            if (!preg_match_all($rawPattern, str_replace('\\/', '/', $html), $matches)) {
                continue;
            }
            foreach (($matches[1] ?? $matches[0] ?? []) as $rawLink) {
                $url = event_import_absolute_url((string) $config['listing_url'], html_entity_decode((string) $rawLink, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
                if ($url !== null && event_import_link_is_allowed($url, $config)) {
                    $links[$url] = true;
                }
            }
        }

        return array_keys($preferred + $links);
    }
}

if (!function_exists('event_import_generic_event_nodes_v2')) {
    /** @return list<array<string,mixed>> */
    function event_import_generic_event_nodes_v2(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $nodes = [];
        $hasTitle = isset($value['title']) || isset($value['name']);
        $dateKeys = ['startDate', 'start_date', 'startAt', 'start_at', 'date', 'event_date', 'data_evento', 'from'];
        $hasDate = false;
        foreach ($dateKeys as $dateKey) {
            if (isset($value[$dateKey]) && is_scalar($value[$dateKey]) && trim((string) $value[$dateKey]) !== '') {
                $hasDate = true;
                break;
            }
        }

        $type = strtolower(trim((string) ($value['type'] ?? $value['@type'] ?? '')));
        if ($hasTitle && ($hasDate || str_contains($type, 'event'))) {
            $nodes[] = $value;
        }

        foreach ($value as $child) {
            if (is_array($child)) {
                $nodes = array_merge($nodes, event_import_generic_event_nodes_v2($child));
            }
        }
        return $nodes;
    }
}

if (!function_exists('event_import_first_scalar_v2')) {
    /** @param list<string> $keys */
    function event_import_first_scalar_v2(array $node, array $keys): string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $node) && is_scalar($node[$key])) {
                $value = trim(strip_tags((string) $node[$key]));
                if ($value !== '') {
                    return $value;
                }
            }
        }
        return '';
    }
}

if (!function_exists('event_import_node_to_event_v2')) {
    /** @return array<string,mixed>|null */
    function event_import_node_to_event_v2(array $node, string $sourceUrl): ?array
    {
        $title = event_import_first_scalar_v2($node, ['name', 'title']);
        if ($title === '') {
            return null;
        }

        $location = is_array($node['location'] ?? null) ? $node['location'] : [];
        $address = is_array($location['address'] ?? null) ? $location['address'] : [];
        $organizer = is_array($node['organizer'] ?? null) ? $node['organizer'] : [];
        $place = is_array($node['place'] ?? null) ? $node['place'] : [];

        $description = event_import_first_scalar_v2($node, ['description', 'abstract', 'excerpt', 'body', 'content', 'text']);
        $start = event_import_first_scalar_v2($node, ['startDate', 'start_date', 'startAt', 'start_at', 'date', 'event_date', 'data_evento', 'from']);
        $end = event_import_first_scalar_v2($node, ['endDate', 'end_date', 'endAt', 'end_at', 'to']);

        $locationName = event_import_first_scalar_v2($location, ['name', 'title']);
        if ($locationName === '') {
            $locationName = event_import_first_scalar_v2($place, ['name', 'title']);
        }
        if ($locationName === '' && is_scalar($node['location'] ?? null)) {
            $locationName = trim(strip_tags((string) $node['location']));
        }

        $locality = event_import_first_scalar_v2($address, ['addressLocality', 'locality', 'city', 'municipality']);
        if ($locality === '') {
            $locality = event_import_first_scalar_v2($location, ['addressLocality', 'locality', 'city', 'municipality']);
        }
        if ($locality === '') {
            $locality = event_import_first_scalar_v2($node, ['locality', 'city', 'municipality', 'comune']);
        }

        $organizerName = event_import_first_scalar_v2($organizer, ['name', 'title']);
        if ($organizerName === '' && is_scalar($node['organizer'] ?? null)) {
            $organizerName = trim(strip_tags((string) $node['organizer']));
        }

        $identifier = $node['identifier'] ?? $node['id'] ?? $node['@id'] ?? null;
        if (is_array($identifier)) {
            $identifier = $identifier['value'] ?? $identifier['@id'] ?? $identifier['id'] ?? null;
        }

        $image = $node['image'] ?? $node['image_url'] ?? $node['cover_image'] ?? $node['cover'] ?? null;
        if (is_array($image)) {
            $image = $image['url'] ?? $image['src'] ?? $image[0] ?? null;
        }

        $externalId = trim((string) ($identifier ?: hash('sha256', $sourceUrl . '|' . $title . '|' . $start)));

        return [
            'external_id' => $externalId,
            'title' => $title,
            'description' => $description,
            'start_at_raw' => $start,
            'end_at_raw' => $end,
            'location_name' => $locationName,
            'locality' => $locality,
            'organizer' => $organizerName,
            'source_url' => $sourceUrl,
            'image_url' => event_import_https_url($image),
            'raw' => $node,
        ];
    }
}

if (!function_exists('event_import_html_fallback_event_v2')) {
    /** @return array<string,mixed>|null */
    function event_import_html_fallback_event_v2(DOMXPath $xpath, string $sourceUrl): ?array
    {
        $title = '';
        $h1 = $xpath->query('//h1[1]')?->item(0);
        if ($h1 instanceof DOMNode) {
            $title = trim(preg_replace('/\s+/u', ' ', $h1->textContent) ?? '');
        }
        if ($title === '') {
            return null;
        }

        $times = [];
        foreach ($xpath->query('//time[@datetime]/@datetime') ?: [] as $timeAttr) {
            if ($timeAttr instanceof DOMAttr && trim($timeAttr->value) !== '') {
                $times[] = trim($timeAttr->value);
            }
        }
        if (!$times) {
            return null;
        }

        $description = '';
        $descriptionMeta = $xpath->query('//meta[@property="og:description" or @name="description"]/@content')?->item(0);
        if ($descriptionMeta instanceof DOMAttr) {
            $description = trim(strip_tags($descriptionMeta->value));
        }

        $image = null;
        $imageMeta = $xpath->query('//meta[@property="og:image"]/@content')?->item(0);
        if ($imageMeta instanceof DOMAttr) {
            $image = event_import_https_url($imageMeta->value);
        }

        return [
            'external_id' => hash('sha256', $sourceUrl . '|' . $title . '|' . $times[0]),
            'title' => $title,
            'description' => $description,
            'start_at_raw' => $times[0],
            'end_at_raw' => $times[1] ?? '',
            'location_name' => '',
            'locality' => '',
            'organizer' => '',
            'source_url' => $sourceUrl,
            'image_url' => $image,
            'raw' => ['fallback' => 'html-meta-time'],
        ];
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
                $event = event_import_node_to_event_v2($node, $sourceUrl);
                if ($event !== null) {
                    $events[$event['external_id']] = $event;
                }
            }
        }

        if (!$events) {
            foreach ($xpath->query('//script[not(@src)]') ?: [] as $script) {
                $json = trim((string) $script->textContent);
                if ($json === '' || (!str_starts_with($json, '{') && !str_starts_with($json, '['))) {
                    continue;
                }
                try {
                    $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                } catch (Throwable $e) {
                    continue;
                }
                foreach (event_import_generic_event_nodes_v2($decoded) as $node) {
                    $event = event_import_node_to_event_v2($node, $sourceUrl);
                    if ($event !== null) {
                        $events[$event['external_id']] = $event;
                    }
                }
            }
        }

        if (!$events) {
            $fallback = event_import_html_fallback_event_v2($xpath, $sourceUrl);
            if ($fallback !== null) {
                $events[$fallback['external_id']] = $fallback;
            }
        }

        return array_values($events);
    }
}

if (!function_exists('event_import_locality_allowed')) {
    /** @param list<string> $allowlist */
    function event_import_locality_allowed(array $event, array $allowlist): bool
    {
        if (!$allowlist) {
            return true;
        }
        $haystack = mb_strtolower(implode(' ', [
            $event['title'] ?? '',
            $event['description'] ?? '',
            $event['location_name'] ?? '',
            $event['locality'] ?? '',
            $event['organizer'] ?? '',
        ]));
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

        $listingUrls = [(string) $config['listing_url']];
        foreach (($config['fallback_listing_urls'] ?? []) as $fallbackUrl) {
            if (is_string($fallbackUrl) && trim($fallbackUrl) !== '') {
                $listingUrls[] = trim($fallbackUrl);
            }
        }
        $listingUrls = array_values(array_unique($listingUrls));

        $maxItems = max(1, lauco_env_int('EVENT_IMPORT_MAX_ITEMS', 80));
        $events = [];
        $links = [];
        $listingErrors = [];

        foreach ($listingUrls as $listingUrl) {
            $listingConfig = $config;
            $listingConfig['listing_url'] = $listingUrl;
            try {
                $listing = lauco_http_get_allowlisted($listingUrl, $config['allowed_hosts'], 30);
            } catch (Throwable $e) {
                $listingErrors[] = $listingUrl . ': ' . $e->getMessage();
                continue;
            }

            foreach (event_import_page_events($listing, $listingUrl) as $event) {
                if (event_import_locality_allowed($event, $config['localities'])) {
                    $events[$sourceKey . '|' . $event['external_id']] = $event;
                }
            }

            foreach (event_import_listing_links($listing, $listingConfig) as $link) {
                $links[$link] = true;
                if (count($links) >= $maxItems) {
                    break 2;
                }
            }
        }

        if (!$events && !$links && count($listingErrors) === count($listingUrls)) {
            throw new RuntimeException('Impossibile raggiungere la fonte: ' . implode(' | ', $listingErrors));
        }

        $detailFailures = [];
        foreach (array_slice(array_keys($links), 0, $maxItems) as $link) {
            try {
                $page = lauco_http_get_allowlisted($link, $config['allowed_hosts'], 30);
                $pageEvents = event_import_page_events($page, $link);
                if (!$pageEvents) {
                    $detailFailures[] = $link . ': nessun dato evento riconosciuto';
                    continue;
                }
                foreach ($pageEvents as $event) {
                    if (event_import_locality_allowed($event, $config['localities'])) {
                        $events[$sourceKey . '|' . $event['external_id']] = $event;
                    }
                }
            } catch (Throwable $e) {
                $detailFailures[] = $link . ': ' . $e->getMessage();
            }
        }

        if (!$events && $links && count($detailFailures) >= count($links)) {
            throw new RuntimeException(
                'La fonte è raggiungibile ma le pagine evento non sono leggibili. Primo errore: '
                . ($detailFailures[0] ?? 'formato non riconosciuto')
            );
        }

        return array_values($events);
    }
}

require_once __DIR__ . '/event-import.php';
