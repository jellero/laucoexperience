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
                            'image_url',
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
                            'image_url' => ['type' => 'string'],
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

if (!function_exists('event_ai_web_image_url')) {
    function event_ai_web_image_url(string $url): ?string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($scheme !== 'https' || $host === '') {
            return null;
        }
        if ($host === 'localhost' || str_ends_with($host, '.local') || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        return $url;
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
            $imageUrl = event_ai_web_image_url((string) ($event['image_url'] ?? ''));
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
                'image_url' => $imageUrl,
                'raw' => [
                    'origin' => 'openai_web_search',
                    'source_url' => $canonicalSource,
                    'secondary_source_url' => $secondarySourceUrl !== ''
                        ? event_ai_web_canonical_url($secondarySourceUrl)
                        : null,
                    'image_url' => $imageUrl,
                    'evidence' => mb_substr($evidence, 0, 2000),
                ],
            ];

            if (!isset($normalized[$dedupeKey])) {
                $normalized[$dedupeKey] = $candidate;
                continue;
            }

            $current = $normalized[$dedupeKey];
            if (($current['image_url'] ?? null) === null && $imageUrl !== null) {
                $current['image_url'] = $imageUrl;
                $current['raw']['image_url'] = $imageUrl;
            }
            if (
                event_ai_web_source_priority($candidate['source_url'])
                < event_ai_web_source_priority((string) $current['source_url'])
            ) {
                if (($candidate['image_url'] ?? null) === null && ($current['image_url'] ?? null) !== null) {
                    $candidate['image_url'] = $current['image_url'];
                    $candidate['raw']['image_url'] = $current['image_url'];
                }
                $current = $candidate;
            }
            $normalized[$dedupeKey] = $current;
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

if (!function_exists('event_ai_web_search_passes')) {
    /** @param array<string,mixed> $config @return list<array{name:string,task:string,localities:list<string>,search_hints:list<string>}> */
    function event_ai_web_search_passes(array $config): array
    {
        $all = array_values(array_filter(
            array_map('strval', is_array($config['localities'] ?? null) ? $config['localities'] : ['Lauco']),
            static fn (string $value): bool => trim($value) !== ''
        ));
        $primary = array_values(array_filter(
            array_map('strval', is_array($config['primary_localities'] ?? null) ? $config['primary_localities'] : ['Lauco']),
            static fn (string $value): bool => trim($value) !== ''
        ));
        $nearby = array_values(array_filter(
            array_map('strval', is_array($config['nearby_localities'] ?? null) ? $config['nearby_localities'] : array_values(array_diff($all, $primary))),
            static fn (string $value): bool => trim($value) !== ''
        ));

        $passes = [[
            'name' => 'lauco',
            'task' => 'Cerca in modo esaustivo gli eventi futuri nel Comune di Lauco e nelle sue frazioni. Non fermarti al primo risultato: esegui più ricerche e controlla Comune, PromoTurismoFVG, Pro Loco/UNPLI e organizzatori.',
            'localities' => $primary ?: $all,
            'search_hints' => [
                'eventi Lauco',
                'Lauco eventi Comune',
                'site:comune.lauco.ud.it eventi Lauco',
                'site:turismofvg.it/eventi Lauco',
                'site:prolocoregionefvg.it Lauco eventi',
                'Lauco manifestazioni feste concerti escursioni',
            ],
        ]];

        if ($nearby !== []) {
            $passes[] = [
                'name' => 'carnia-vicina',
                'task' => 'Cerca eventi futuri nelle località della Carnia vicine a Lauco indicate. Copri più comuni e più fonti, privilegiando appuntamenti culturali, tradizionali, outdoor, famiglie, musica ed enogastronomia utili a chi soggiorna a Lauco.',
                'localities' => $nearby,
                'search_hints' => [
                    'eventi Carnia prossimi',
                    'site:turismofvg.it/eventi Carnia eventi',
                    'Villa Santina eventi',
                    'Tolmezzo eventi',
                    'Verzegnis eventi',
                    'Raveo eventi',
                    'Preone eventi',
                    'Ovaro eventi',
                    'Zuglio eventi',
                    'Arta Terme eventi',
                ],
            ];
        }

        return $passes;
    }
}

if (!function_exists('event_ai_web_openai_request')) {
    /**
     * @param array<string,mixed> $config
     * @param array{name:string,task:string,localities:list<string>,search_hints:list<string>} $pass
     * @return array{data:array<string,mixed>,web_sources:list<string>,response_id:?string,request_id:?string,model:string,pass:string}
     */
    function event_ai_web_openai_request(
        array $config,
        DateTimeImmutable $today,
        DateTimeImmutable $horizon,
        array $pass
    ): array {
        $apiKey = lauco_env_required('OPENAI_API_KEY');
        $model = lauco_env_required('OPENAI_MODEL');
        $clientRequestId = lauco_uuid_v4();
        $targetEvents = max(5, min(30, (int) ($config['target_events'] ?? lauco_env_int('EVENT_AI_WEB_TARGET_EVENTS', 15))));

        $developerInstructions = <<<'TEXT'
Sei il ricercatore editoriale degli eventi di Lauco Experience. Devi usare Web Search in questa richiesta: non affidarti alla memoria del modello.

Obiettivo: costruire un calendario utile e abbastanza ricco. Non fermarti al primo risultato valido. Esegui più ricerche Web Search con query diverse, apri le pagine evento quando necessario e prova a raggiungere il numero obiettivo di candidati, ma includi solo eventi realmente verificati.

Regole di verifica:
- ogni evento deve avere una data futura certa e svolgersi fisicamente in una delle località ammesse fornite nella richiesta;
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

Immagine:
- cerca per ogni evento una fotografia o locandina specifica dell'evento presente sulla fonte consultata o su una fonte ufficiale collegata;
- preferisci una fotografia orizzontale rappresentativa e di buona qualità; se non esiste, usa la locandina ufficiale;
- evita loghi, icone, avatar social, mappe, immagini generiche del sito e miniature palesemente piccole;
- preferisci l'immagine originale o ad alta risoluzione e, quando la fonte espone le dimensioni, una larghezza di almeno 1000 px;
- image_url deve essere un URL HTTPS diretto all'immagine; se non trovi un'immagine affidabile lascia una stringa vuota.

Restituisci solo il JSON richiesto dallo schema.
TEXT;

        $userInput = json_encode([
            'task' => (string) ($pass['task'] ?? 'Cerca eventi futuri verificabili.'),
            'date_from' => $today->format('Y-m-d'),
            'date_to' => $horizon->format('Y-m-d'),
            'target_events' => $targetEvents,
            'allowed_localities' => array_values($pass['localities'] ?? []),
            'priority_sources' => [
                'comune.lauco.ud.it',
                'turismofvg.it',
                'prolocoregionefvg.it',
                'siti ufficiali degli organizzatori',
            ],
            'search_hints' => array_values($pass['search_hints'] ?? []),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $payload = [
            'model' => $model,
            'store' => false,
            'max_output_tokens' => max(1600, min(20000, lauco_env_int('OPENAI_MAX_OUTPUT_TOKENS', 10000))),
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
            'max_tool_calls' => max(4, min(30, lauco_env_int('EVENT_AI_WEB_MAX_TOOL_CALLS', 20))),
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
            'pass' => (string) ($pass['name'] ?? 'default'),
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

        $eventsById = [];
        $errors = [];
        $successfulPasses = 0;

        foreach (event_ai_web_search_passes($config) as $pass) {
            try {
                $result = event_ai_web_openai_request($config, $today, $horizon, $pass);
                $successfulPasses++;
                $passConfig = $config;
                $passConfig['localities'] = $pass['localities'];
                $events = event_ai_web_normalize_events(
                    $result['data'],
                    $result['web_sources'],
                    $passConfig,
                    $today,
                    $horizon
                );

                foreach ($events as $event) {
                    $event['raw']['source_key'] = $sourceKey;
                    $event['raw']['search_pass'] = $result['pass'];
                    $event['raw']['openai_model'] = $result['model'];
                    $event['raw']['openai_response_id'] = $result['response_id'];
                    $event['raw']['openai_request_id'] = $result['request_id'];
                    $id = (string) $event['external_id'];

                    if (!isset($eventsById[$id])) {
                        $eventsById[$id] = $event;
                        continue;
                    }

                    $current = $eventsById[$id];
                    if (($current['image_url'] ?? null) === null && ($event['image_url'] ?? null) !== null) {
                        $current['image_url'] = $event['image_url'];
                        $current['raw']['image_url'] = $event['image_url'];
                    }
                    if (
                        event_ai_web_source_priority((string) $event['source_url'])
                        < event_ai_web_source_priority((string) $current['source_url'])
                    ) {
                        if (($event['image_url'] ?? null) === null && ($current['image_url'] ?? null) !== null) {
                            $event['image_url'] = $current['image_url'];
                            $event['raw']['image_url'] = $current['image_url'];
                        }
                        $current = $event;
                    }
                    $eventsById[$id] = $current;
                }
            } catch (Throwable $e) {
                $errors[] = (string) ($pass['name'] ?? 'ricerca') . ': ' . $e->getMessage();
            }
        }

        if ($successfulPasses === 0) {
            throw new RuntimeException('Ricerca AI eventi non riuscita: ' . implode(' | ', $errors));
        }

        $events = array_values($eventsById);
        usort(
            $events,
            static fn (array $a, array $b): int =>
                strcmp((string) $a['start_at_raw'], (string) $b['start_at_raw'])
                ?: strcmp((string) $a['title'], (string) $b['title'])
        );

        $maxEvents = max(1, min(100, lauco_env_int('EVENT_AI_WEB_MAX_EVENTS', 40)));
        return array_slice($events, 0, $maxEvents);
    }
}
