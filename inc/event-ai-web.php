<?php
declare(strict_types=1);

require_once __DIR__ . '/openai-client.php';
require_once __DIR__ . '/event-import.php';

if (!function_exists('event_ai_web_schema')) {
    /** @return array<string,mixed> */
    function event_ai_web_schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['events'],
            'properties' => [
                'events' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => [
                            'title',
                            'description',
                            'start_at_raw',
                            'end_at_raw',
                            'location_name',
                            'locality',
                            'organizer',
                            'source_url',
                            'secondary_source_url',
                            'evidence',
                        ],
                        'properties' => [
                            'title' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'start_at_raw' => ['type' => 'string'],
                            'end_at_raw' => ['type' => 'string'],
                            'location_name' => ['type' => 'string'],
                            'locality' => ['type' => 'string'],
                            'organizer' => ['type' => 'string'],
                            'source_url' => ['type' => 'string'],
                            'secondary_source_url' => ['type' => 'string'],
                            'evidence' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];
    }
}

if (!function_exists('event_ai_web_canonical_url')) {
    function event_ai_web_canonical_url(string $url): ?string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($scheme !== 'https' || $host === '') {
            return null;
        }

        $path = (string) ($parts['path'] ?? '/');
        $path = $path === '' ? '/' : $path;
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        $query = [];
        if (isset($parts['query']) && $parts['query'] !== '') {
            parse_str((string) $parts['query'], $query);
            foreach (array_keys($query) as $key) {
                $normalized = strtolower((string) $key);
                if (
                    str_starts_with($normalized, 'utm_')
                    || in_array($normalized, ['gclid', 'fbclid', 'msclkid', 'mc_cid', 'mc_eid'], true)
                ) {
                    unset($query[$key]);
                }
            }
            ksort($query, SORT_STRING);
        }

        $port = isset($parts['port']) ? ':' . (int) $parts['port'] : '';
        $canonical = 'https://' . $host . $port . $path;
        if ($query !== []) {
            $canonical .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }
        return $canonical;
    }
}

if (!function_exists('event_ai_web_collect_sources')) {
    /** @param array<string,mixed> $response @return list<string> */
    function event_ai_web_collect_sources(array $response): array
    {
        $sources = [];

        foreach (($response['output'] ?? []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            if (($item['type'] ?? '') === 'web_search_call') {
                foreach (($item['action']['sources'] ?? []) as $source) {
                    if (!is_array($source) || !is_string($source['url'] ?? null)) {
                        continue;
                    }
                    $canonical = event_ai_web_canonical_url((string) $source['url']);
                    if ($canonical !== null) {
                        $sources[$canonical] = true;
                    }
                }
            }

            if (($item['type'] ?? '') !== 'message') {
                continue;
            }
            foreach (($item['content'] ?? []) as $content) {
                if (!is_array($content)) {
                    continue;
                }
                foreach (($content['annotations'] ?? []) as $annotation) {
                    if (!is_array($annotation) || ($annotation['type'] ?? '') !== 'url_citation') {
                        continue;
                    }
                    $canonical = event_ai_web_canonical_url((string) ($annotation['url'] ?? ''));
                    if ($canonical !== null) {
                        $sources[$canonical] = true;
                    }
                }
            }
        }

        return array_keys($sources);
    }
}

if (!function_exists('event_ai_web_source_supported')) {
    /** @param list<string> $webSources */
    function event_ai_web_source_supported(string $sourceUrl, array $webSources): bool
    {
        $canonical = event_ai_web_canonical_url($sourceUrl);
        if ($canonical === null) {
            return false;
        }

        foreach ($webSources as $webSource) {
            if ($canonical === event_ai_web_canonical_url((string) $webSource)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('event_ai_web_locality_allowed')) {
    /** @param list<string> $localities */
    function event_ai_web_locality_allowed(string $locality, string $locationName, array $localities): bool
    {
        $haystack = mb_strtolower(trim($locality . ' ' . $locationName));
        if ($haystack === '') {
            return false;
        }

        foreach ($localities as $allowed) {
            $allowed = mb_strtolower(trim((string) $allowed));
            if ($allowed !== '' && str_contains($haystack, $allowed)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('event_ai_web_source_priority')) {
    function event_ai_web_source_priority(string $url): int
    {
        $host = strtolower((string) (parse_url($url, PHP_URL_HOST) ?? ''));
        if ($host === 'comune.lauco.ud.it' || $host === 'www.comune.lauco.ud.it') {
            return 1;
        }
        if ($host === 'turismofvg.it' || $host === 'www.turismofvg.it') {
            return 2;
        }
        if ($host === 'prolocoregionefvg.it' || $host === 'www.prolocoregionefvg.it') {
            return 3;
        }
        return 10;
    }
}

if (!function_exists('event_ai_web_normalize_events')) {
    /**
     * @param array<string,mixed> $payload
     * @param list<string> $webSources
     * @param array<string,mixed> $config
     * @return list<array<string,mixed>>
     */
    function event_ai_web_normalize_events(
        array $payload,
        array $webSources,
        array $config,
        ?DateTimeImmutable $today = null,
        ?DateTimeImmutable $horizon = null
    ): array {
        $timezoneName = trim((string) lauco_env('APP_TIMEZONE', 'Europe/Rome')) ?: 'Europe/Rome';
        try {
            $timezone = new DateTimeZone($timezoneName);
        } catch (Throwable $e) {
            $timezone = new DateTimeZone('Europe/Rome');
        }

        $today ??= new DateTimeImmutable('today', $timezone);
        $horizon ??= $today->modify('+' . max(1, lauco_env_int('EVENT_AI_WEB_HORIZON_DAYS', 365)) . ' days');

        $localities = is_array($config['localities'] ?? null) ? $config['localities'] : ['Lauco'];
        $normalized = [];

        foreach (($payload['events'] ?? []) as $event) {
            if (!is_array($event)) {
                continue;
            }

            $title = trim(strip_tags((string) ($event['title'] ?? '')));
            $description = trim(strip_tags((string) ($event['description'] ?? '')));
            $startRaw = trim((string) ($event['start_at_raw'] ?? ''));
            $endRaw = trim((string) ($event['end_at_raw'] ?? ''));
            $locationName = trim(strip_tags((string) ($event['location_name'] ?? '')));
            $locality = trim(strip_tags((string) ($event['locality'] ?? '')));
            $organizer = trim(strip_tags((string) ($event['organizer'] ?? '')));
            $sourceUrl = trim((string) ($event['source_url'] ?? ''));
            $secondarySourceUrl = trim((string) ($event['secondary_source_url'] ?? ''));
            $evidence = trim(strip_tags((string) ($event['evidence'] ?? '')));

            if ($title === '' || $description === '' || !preg_match('/^\d{4}-\d{2}-\d{2}/', $startRaw)) {
                continue;
            }
            if (!event_ai_web_locality_allowed($locality, $locationName, $localities)) {
                continue;
            }
            if (!event_ai_web_source_supported($sourceUrl, $webSources)) {
                continue;
            }

            try {
                $start = new DateTimeImmutable(substr($startRaw, 0, 10), $timezone);
            } catch (Throwable $e) {
                continue;
            }
            if ($start < $today || $start > $horizon) {
                continue;
            }

            if ($secondarySourceUrl !== '' && !event_ai_web_source_supported($secondarySourceUrl, $webSources)) {
                $secondarySourceUrl = '';
            }

            $canonicalSource = event_ai_web_canonical_url($sourceUrl);
            if ($canonicalSource === null) {
                continue;
            }

            $date = $start->format('Y-m-d');
            $dedupeKey = hash(
                'sha256',
                mb_strtolower($title) . '|' . $date . '|' . mb_strtolower($locality ?: $locationName)
            );

            $candidate = [
                'external_id' => 'aiweb:' . $dedupeKey,
                'title' => mb_substr($title, 0, 255),
                'description' => mb_substr($description, 0, 12000),
                'start_at_raw' => mb_substr($startRaw, 0, 100),
                'end_at_raw' => mb_substr($endRaw, 0, 100),
                'location_name' => mb_substr($locationName, 0, 255),
                'locality' => mb_substr($locality, 0, 255),
                'organizer' => mb_substr($organizer, 0, 255),
                'source_url' => $canonicalSource,
                'image_url' => null,
                'raw' => [
                    'origin' => 'openai_web_search',
                    'source_url' => $canonicalSource,
                    'secondary_source_url' => $secondarySourceUrl !== ''
                        ? event_ai_web_canonical_url($secondarySourceUrl)
                        : null,
                    'evidence' => mb_substr($evidence, 0, 2000),
                ],
            ];

            if (
                !isset($normalized[$dedupeKey])
                || event_ai_web_source_priority($candidate['source_url'])
                    < event_ai_web_source_priority($normalized[$dedupeKey]['source_url'])
            ) {
                $normalized[$dedupeKey] = $candidate;
            }
        }

        $events = array_values($normalized);
        usort(
            $events,
            static fn (array $a, array $b): int =>
                strcmp((string) $a['start_at_raw'], (string) $b['start_at_raw'])
                ?: strcmp((string) $a['title'], (string) $b['title'])
        );

        return $events;
    }
}

if (!function_exists('event_ai_web_openai_request')) {
    /**
     * @param array<string,mixed> $config
     * @return array{data:array<string,mixed>,web_sources:list<string>,response_id:?string,request_id:?string,model:string}
     */
    function event_ai_web_openai_request(array $config, DateTimeImmutable $today, DateTimeImmutable $horizon): array
    {
        $apiKey = lauco_env_required('OPENAI_API_KEY');
        $model = lauco_env_required('OPENAI_MODEL');
        $clientRequestId = lauco_uuid_v4();

        $localities = array_values(array_filter(
            array_map('strval', is_array($config['localities'] ?? null) ? $config['localities'] : ['Lauco']),
            static fn (string $value): bool => trim($value) !== ''
        ));

        $developerInstructions = <<<'TEXT'
Sei il ricercatore editoriale degli eventi di Lauco Experience. Devi usare Web Search in questa richiesta: non affidarti alla memoria del modello.

Trova esclusivamente eventi pubblici con data futura certa che si svolgono fisicamente nel Comune di Lauco o nelle località ammesse fornite. Non includere eventi di altri comuni solo perché organizzati da un soggetto di Lauco.

Regole di verifica:
- ogni evento deve essere sostenuto da almeno una pagina web realmente consultata;
- source_url deve essere la pagina che documenta direttamente titolo, data e luogo dell'evento, non una homepage generica se esiste una scheda specifica;
- preferisci, nell'ordine, Comune di Lauco, PromoTurismoFVG, UNPLI/Pro Loco, organizzatore ufficiale e altre fonti locali affidabili;
- quando possibile verifica l'evento su una seconda fonte e inseriscila in secondary_source_url;
- se data o luogo sono contraddittori, incerti, rinviati o non verificabili, ometti l'evento;
- non inventare orari, programma, prezzi, contatti, servizi, organizzatori o descrizioni;
- usa start_at_raw ed end_at_raw in formato ISO 8601; se è nota solo la data usa YYYY-MM-DD;
- la descrizione deve essere in italiano, 2-4 frasi, puramente fattuale e ricavata dalle fonti;
- evidence deve sintetizzare in una frase quali dati della fonte confermano l'evento;
- non includere eventi già trascorsi rispetto alla data iniziale fornita;
- non usare markdown o HTML nei campi.

Restituisci solo il JSON richiesto dallo schema.
TEXT;

        $userInput = json_encode([
            'task' => 'Cerca sul web gli eventi futuri verificabili del Comune di Lauco e prepara candidati per revisione editoriale.',
            'date_from' => $today->format('Y-m-d'),
            'date_to' => $horizon->format('Y-m-d'),
            'allowed_localities' => $localities,
            'priority_sources' => [
                'comune.lauco.ud.it',
                'turismofvg.it',
                'prolocoregionefvg.it',
                'siti ufficiali degli organizzatori',
            ],
            'search_hints' => [
                'eventi Lauco',
                'Lauco eventi Comune',
                'site:turismofvg.it Lauco eventi',
                'site:comune.lauco.ud.it Lauco eventi',
                'feste manifestazioni Lauco Carnia',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $payload = [
            'model' => $model,
            'store' => false,
            'max_output_tokens' => max(1200, min(16000, lauco_env_int('OPENAI_MAX_OUTPUT_TOKENS', 8000))),
            'reasoning' => [
                'effort' => trim((string) lauco_env('OPENAI_REASONING_EFFORT', 'low')) ?: 'low',
            ],
            'tools' => [
                [
                    'type' => 'web_search',
                    'search_context_size' => 'high',
                ],
            ],
            'tool_choice' => 'required',
            'max_tool_calls' => max(2, min(20, lauco_env_int('EVENT_AI_WEB_MAX_TOOL_CALLS', 12))),
            'include' => ['web_search_call.action.sources'],
            'input' => [
                ['role' => 'developer', 'content' => [['type' => 'input_text', 'text' => $developerInstructions]]],
                ['role' => 'user', 'content' => [['type' => 'input_text', 'text' => $userInput]]],
            ],
            'text' => [
                'verbosity' => 'medium',
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'lauco_event_web_search',
                    'description' => 'Eventi futuri verificati sul web per la coda di revisione di Lauco Experience.',
                    'strict' => true,
                    'schema' => event_ai_web_schema(),
                ],
            ],
        ];

        $response = lauco_http_post_json(
            'https://api.openai.com/v1/responses',
            $payload,
            [
                'Authorization: Bearer ' . $apiKey,
                'X-Client-Request-Id: ' . $clientRequestId,
            ],
            max(30, lauco_env_int('OPENAI_TIMEOUT_SECONDS', 90))
        );

        if ($response['status'] < 200 || $response['status'] >= 300) {
            $detail = '';
            try {
                $error = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
                if (is_array($error)) {
                    $detail = trim((string) ($error['error']['message'] ?? ''));
                }
            } catch (Throwable $e) {
                $detail = '';
            }
            throw new RuntimeException(
                'OpenAI Web Search ha restituito HTTP '
                . $response['status']
                . ($detail !== '' ? ': ' . $detail : '.')
            );
        }

        $decoded = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new RuntimeException('Risposta OpenAI Web Search non valida.');
        }

        $status = (string) ($decoded['status'] ?? 'completed');
        if ($status !== 'completed') {
            $reason = (string) ($decoded['incomplete_details']['reason'] ?? $decoded['error']['message'] ?? $status);
            throw new RuntimeException('Ricerca eventi non completata: ' . $reason);
        }

        $data = json_decode(lauco_openai_output_text($decoded), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('Output strutturato della ricerca eventi non valido.');
        }

        $webSources = event_ai_web_collect_sources($decoded);
        if ($webSources === []) {
            throw new RuntimeException('OpenAI Web Search non ha restituito fonti web verificabili.');
        }

        return [
            'data' => $data,
            'web_sources' => $webSources,
            'response_id' => isset($decoded['id']) ? (string) $decoded['id'] : null,
            'request_id' => $response['headers']['x-request-id'] ?? null,
            'model' => isset($decoded['model']) ? (string) $decoded['model'] : $model,
        ];
    }
}

if (!function_exists('event_ai_web_fetch')) {
    /** @param array<string,mixed> $config @return list<array<string,mixed>> */
    function event_ai_web_fetch(string $sourceKey, array $config): array
    {
        if (empty($config['enabled']) || ($config['kind'] ?? '') !== 'ai_web') {
            throw new RuntimeException('Fonte AI Web Search non disponibile.');
        }

        $timezoneName = trim((string) lauco_env('APP_TIMEZONE', 'Europe/Rome')) ?: 'Europe/Rome';
        try {
            $timezone = new DateTimeZone($timezoneName);
        } catch (Throwable $e) {
            $timezone = new DateTimeZone('Europe/Rome');
        }

        $today = new DateTimeImmutable('today', $timezone);
        $horizonDays = max(1, min(730, lauco_env_int('EVENT_AI_WEB_HORIZON_DAYS', 365)));
        $horizon = $today->modify('+' . $horizonDays . ' days');

        $result = event_ai_web_openai_request($config, $today, $horizon);
        $events = event_ai_web_normalize_events(
            $result['data'],
            $result['web_sources'],
            $config,
            $today,
            $horizon
        );

        $maxEvents = max(1, min(100, lauco_env_int('EVENT_AI_WEB_MAX_EVENTS', 30)));
        $events = array_slice($events, 0, $maxEvents);

        foreach ($events as &$event) {
            $event['raw']['source_key'] = $sourceKey;
            $event['raw']['openai_model'] = $result['model'];
            $event['raw']['openai_response_id'] = $result['response_id'];
            $event['raw']['openai_request_id'] = $result['request_id'];
        }
        unset($event);

        return $events;
    }
}
