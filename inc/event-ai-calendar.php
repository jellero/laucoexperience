<?php
declare(strict_types=1);

require_once __DIR__ . '/event-ai-web.php';

if (!function_exists('event_ai_calendar_documents')) {
    /** @param array<string,mixed> $config @return list<string> */
    function event_ai_calendar_documents(array $config): array
    {
        $documents = [];
        foreach (($config['calendar_documents'] ?? []) as $url) {
            if (!is_string($url)) {
                continue;
            }
            $canonical = event_ai_web_canonical_url($url);
            if ($canonical !== null) {
                $documents[$canonical] = $canonical;
            }
        }
        return array_values($documents);
    }
}

if (!function_exists('event_ai_calendar_request')) {
    /**
     * @param array<string,mixed> $config
     * @return array{data:array<string,mixed>,web_sources:list<string>,response_id:?string,request_id:?string,model:string,document_url:string}
     */
    function event_ai_calendar_request(
        array $config,
        string $documentUrl,
        DateTimeImmutable $today,
        DateTimeImmutable $horizon
    ): array {
        $apiKey = lauco_env_required('OPENAI_API_KEY');
        $model = lauco_env_required('OPENAI_MODEL');
        $clientRequestId = lauco_uuid_v4();
        $targetEvents = max(5, min(60, (int) ($config['target_events'] ?? 30)));
        $localities = array_values(array_filter(
            array_map('strval', is_array($config['localities'] ?? null) ? $config['localities'] : ['Lauco']),
            static fn (string $value): bool => trim($value) !== ''
        ));

        $developerInstructions = <<<'TEXT'
Sei il ricercatore editoriale degli eventi di Lauco Experience.

La locandina PDF allegata è una fonte ufficiale del Comune di Lauco e deve essere trattata come calendario principale. Prima leggi l'intero PDF, pagina per pagina, ed estrai TUTTI gli appuntamenti futuri rispetto alla data iniziale indicata. Non fermarti ai primi eventi e non limitarti agli eventi già presenti nelle pagine HTML del Comune.

Dopo avere estratto il calendario, usa obbligatoriamente Web Search per verificare e arricchire ogni appuntamento quando esistono pagine dedicate del Comune, PromoTurismoFVG, Pro Loco/UNPLI, organizzatori o altre fonti locali affidabili.

Regole:
- includi solo eventi con data futura certa e che si svolgono nel Comune di Lauco o nelle località ammesse;
- non scartare un evento presente nel PDF ufficiale solo perché non trovi una pagina web dedicata: in quel caso usa l'URL del PDF come source_url;
- se trovi una pagina evento specifica verificata, usala come source_url e usa il PDF ufficiale come secondary_source_url;
- se trovi più fonti, privilegia Comune di Lauco, PromoTurismoFVG, Pro Loco/UNPLI e organizzatore ufficiale;
- non inventare orari, programma, prezzi, contatti, servizi o organizzatori non presenti nelle fonti;
- usa start_at_raw ed end_at_raw in formato ISO 8601; se è nota solo la data usa YYYY-MM-DD;
- la descrizione deve essere in italiano, 2-4 frasi, fattuale e sintetica;
- evidence deve indicare se titolo/data/località derivano dal calendario PDF e quali dati sono stati confermati sul web;
- non usare markdown o HTML nei campi.

Immagini:
- per ogni evento cerca sul web una fotografia o locandina specifica dell'appuntamento;
- preferisci una fotografia orizzontale rappresentativa e ad alta risoluzione; se non esiste, usa la locandina ufficiale;
- preferisci immagini di almeno 1000 px di larghezza quando la dimensione è verificabile;
- evita loghi, icone, avatar social, mappe, immagini generiche del sito e miniature palesemente piccole;
- image_url deve essere un URL HTTPS diretto a un'immagine; non usare il PDF come image_url;
- se non trovi un'immagine affidabile lascia image_url vuoto.

Restituisci solo il JSON richiesto dallo schema.
TEXT;

        $userInput = json_encode([
            'task' => 'Leggi l’intero calendario ufficiale allegato, estrai tutti gli eventi futuri di Lauco e usa Web Search per verificare dettagli e trovare una bella immagine per ogni evento.',
            'date_from' => $today->format('Y-m-d'),
            'date_to' => $horizon->format('Y-m-d'),
            'target_events' => $targetEvents,
            'calendar_document_url' => $documentUrl,
            'allowed_localities' => $localities,
            'priority_sources' => [
                'comune.lauco.ud.it',
                'turismofvg.it',
                'prolocoregionefvg.it',
                'siti ufficiali degli organizzatori',
            ],
            'search_hints' => [
                'site:comune.lauco.ud.it Lauco 2026 evento',
                'site:turismofvg.it/eventi Lauco 2026',
                'site:prolocoregionefvg.it Lauco 2026',
                'Lauco 2026 eventi locandina',
            ],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $filename = basename((string) (parse_url($documentUrl, PHP_URL_PATH) ?? 'calendario-lauco.pdf'));
        if ($filename === '' || !str_ends_with(strtolower($filename), '.pdf')) {
            $filename = 'calendario-eventi-lauco.pdf';
        }

        $payload = [
            'model' => $model,
            'store' => false,
            'max_output_tokens' => max(2500, min(24000, lauco_env_int('OPENAI_MAX_OUTPUT_TOKENS', 16000))),
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
            'max_tool_calls' => max(8, min(40, lauco_env_int('EVENT_AI_WEB_MAX_TOOL_CALLS', 20))),
            'include' => ['web_search_call.action.sources'],
            'input' => [
                [
                    'role' => 'developer',
                    'content' => [
                        ['type' => 'input_text', 'text' => $developerInstructions],
                    ],
                ],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'input_text', 'text' => $userInput],
                        [
                            'type' => 'input_file',
                            'file_url' => $documentUrl,
                            'filename' => $filename,
                        ],
                    ],
                ],
            ],
            'text' => [
                'verbosity' => 'medium',
                'format' => [
                    'type' => 'json_schema',
                    'name' => 'lauco_official_calendar_events',
                    'description' => 'Eventi futuri estratti dal calendario ufficiale del Comune di Lauco e verificati sul web.',
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
            max(60, lauco_env_int('OPENAI_TIMEOUT_SECONDS', 90))
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
                'OpenAI calendario eventi ha restituito HTTP '
                . $response['status']
                . ($detail !== '' ? ': ' . $detail : '.')
            );
        }

        $decoded = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new RuntimeException('Risposta OpenAI calendario eventi non valida.');
        }

        $status = (string) ($decoded['status'] ?? 'completed');
        if ($status !== 'completed') {
            $reason = (string) ($decoded['incomplete_details']['reason'] ?? $decoded['error']['message'] ?? $status);
            throw new RuntimeException('Lettura calendario eventi non completata: ' . $reason);
        }

        $data = json_decode(lauco_openai_output_text($decoded), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('Output strutturato del calendario eventi non valido.');
        }

        $sources = event_ai_web_collect_sources($decoded);
        $canonicalDocument = event_ai_web_canonical_url($documentUrl);
        if ($canonicalDocument !== null) {
            $sources[] = $canonicalDocument;
            $sources = array_values(array_unique($sources));
        }

        return [
            'data' => $data,
            'web_sources' => $sources,
            'response_id' => isset($decoded['id']) ? (string) $decoded['id'] : null,
            'request_id' => $response['headers']['x-request-id'] ?? null,
            'model' => isset($decoded['model']) ? (string) $decoded['model'] : $model,
            'document_url' => $documentUrl,
        ];
    }
}

if (!function_exists('event_ai_calendar_fetch')) {
    /** @param array<string,mixed> $config @return list<array<string,mixed>> */
    function event_ai_calendar_fetch(string $sourceKey, array $config): array
    {
        if (empty($config['enabled']) || ($config['kind'] ?? '') !== 'ai_calendar_web') {
            throw new RuntimeException('Fonte calendario AI non disponibile.');
        }

        $documents = event_ai_calendar_documents($config);
        if ($documents === []) {
            throw new RuntimeException('Nessun calendario PDF configurato.');
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

        foreach ($documents as $documentUrl) {
            try {
                $result = event_ai_calendar_request($config, $documentUrl, $today, $horizon);
                $events = event_ai_web_normalize_events(
                    $result['data'],
                    $result['web_sources'],
                    $config,
                    $today,
                    $horizon
                );

                foreach ($events as $event) {
                    $event['raw']['origin'] = 'openai_calendar_pdf_web_search';
                    $event['raw']['source_key'] = $sourceKey;
                    $event['raw']['calendar_document'] = $result['document_url'];
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
                $errors[] = $e->getMessage();
            }
        }

        if ($eventsById === []) {
            throw new RuntimeException('Nessun evento estratto dal calendario ufficiale: ' . implode(' | ', $errors));
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
