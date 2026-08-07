<?php
declare(strict_types=1);

require_once __DIR__ . '/openai-client.php';
require_once __DIR__ . '/translations.php';
require_once __DIR__ . '/gpx-stats.php';

if (!function_exists('content_ai_clean')) {
    function content_ai_clean(mixed $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', strip_tags((string) $value)) ?? '');
    }
}

if (!function_exists('content_ai_route_stats')) {
    /** @param array<string,mixed> $route @return array<string,mixed> */
    function content_ai_route_stats(array $route): array
    {
        $gpx = gpx_stats($route['gpx_file'] ?? null, $route['tipo'] ?? 'piedi');
        $manualDistance = trim((string) ($route['distanza_km'] ?? ''));
        $manualAscent = trim((string) ($route['dislivello_m'] ?? ''));
        $manualDuration = trim((string) ($route['tempo'] ?? $route['durata'] ?? ''));
        $manualDifficulty = trim((string) ($route['difficolta'] ?? ''));

        return [
            'distance' => $manualDistance !== '' ? fmt_it($manualDistance, ' km', 2) : ($gpx['length_label'] ?? '-'),
            'ascent' => $manualAscent !== '' ? fmt_it($manualAscent, ' m', 0) : ($gpx['ascent_label'] ?? '-'),
            'duration' => $manualDuration !== '' ? $manualDuration : ($gpx['duration_label'] ?? '-'),
            'difficulty' => $manualDifficulty !== '' ? $manualDifficulty : ($gpx['difficulty'] ?? '-'),
            'gpx_updated' => $gpx['updated_label'] ?? '-',
            'distance_source' => $manualDistance !== '' ? 'database' : 'gpx',
            'ascent_source' => $manualAscent !== '' ? 'database' : 'gpx',
            'duration_source' => $manualDuration !== '' ? 'database' : 'gpx',
            'difficulty_source' => $manualDifficulty !== '' ? 'database' : 'gpx',
        ];
    }
}

if (!function_exists('content_ai_route_context')) {
    /** @param array<string,mixed> $route @param array<string,mixed> $stats */
    function content_ai_route_context(PDO $pdo, array $route, array $stats): array
    {
        $galleryCount = 0;
        try {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM percorso_gallery WHERE percorso_id = :id');
            $stmt->execute(['id' => (int) $route['id']]);
            $galleryCount = (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            $galleryCount = 0;
        }

        return [
            'id' => (int) $route['id'],
            'title' => content_ai_clean($route['titolo'] ?? ''),
            'subtitle' => content_ai_clean($route['sottotitolo'] ?? ''),
            'excerpt' => content_ai_clean($route['excerpt'] ?? ''),
            'description' => trim(strip_tags((string) ($route['descrizione'] ?? ''))),
            'type' => ($route['tipo'] ?? '') === 'mtb' ? 'MTB' : 'a piedi',
            'location' => content_ai_clean($route['localita'] ?? ''),
            'recommended' => !empty($route['consigliato']),
            'special' => !empty($route['speciale']),
            'technical_data' => $stats,
            'gallery_images' => $galleryCount,
            'has_gpx' => trim((string) ($route['gpx_file'] ?? '')) !== '',
        ];
    }
}

if (!function_exists('content_ai_schema')) {
    /** @return array<string,mixed> */
    function content_ai_schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['language', 'title', 'subtitle', 'excerpt', 'description', 'seo_title', 'seo_description', 'card_text', 'warnings'],
            'properties' => [
                'language' => ['type' => 'string'],
                'title' => ['type' => 'string'],
                'subtitle' => ['type' => 'string'],
                'excerpt' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'seo_title' => ['type' => 'string'],
                'seo_description' => ['type' => 'string'],
                'card_text' => ['type' => 'string'],
                'warnings' => ['type' => 'array', 'items' => ['type' => 'string']],
            ],
        ];
    }
}

if (!function_exists('content_ai_normalize_payload')) {
    /** @param array<string,mixed> $payload @return array<string,mixed> */
    function content_ai_normalize_payload(array $payload, string $language): array
    {
        $text = static fn (string $key, int $max): string => mb_substr(trim((string) ($payload[$key] ?? '')), 0, $max);
        $warnings = [];
        foreach (is_array($payload['warnings'] ?? null) ? $payload['warnings'] : [] as $warning) {
            $warning = mb_substr(trim((string) $warning), 0, 500);
            if ($warning !== '') {
                $warnings[] = $warning;
            }
            if (count($warnings) >= 20) {
                break;
            }
        }

        return [
            'language' => $language,
            'title' => $text('title', 255),
            'subtitle' => $text('subtitle', 255),
            'excerpt' => $text('excerpt', 2000),
            'description' => $text('description', 50000),
            'seo_title' => $text('seo_title', 255),
            'seo_description' => $text('seo_description', 320),
            'card_text' => $text('card_text', 500),
            'warnings' => $warnings,
        ];
    }
}

if (!function_exists('content_ai_generate_route')) {
    /** @return array<string,mixed> */
    function content_ai_generate_route(PDO $pdo, int $routeId, string $language, string $mode, int $adminId): array
    {
        if (!array_key_exists($language, content_supported_languages())) {
            throw new RuntimeException('Lingua non supportata.');
        }
        if (!in_array($mode, ['full', 'translate', 'editorial', 'seo'], true)) {
            throw new RuntimeException('Modalità non valida.');
        }

        $stmt = $pdo->prepare('SELECT * FROM percorsi WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $routeId]);
        $route = $stmt->fetch();
        if (!is_array($route)) {
            throw new RuntimeException('Percorso non trovato.');
        }

        $stats = content_ai_route_stats($route);
        $context = content_ai_route_context($pdo, $route, $stats);
        $languageName = content_supported_languages()[$language];
        $developer = "Sei l’assistente editoriale del sito Lauco Experience. Produci contenuti turistici outdoor accurati in {$languageName}. "
            . "Usa esclusivamente i dati forniti. Non inventare accessi, parcheggi, fontane, rifugi, servizi, orari, pericoli, quote o punti panoramici. "
            . "I valori marcati come database prevalgono sui valori GPX. Se i dati non bastano, resta generico e inserisci un avviso. "
            . "Mantieni i nomi propri e la toponomastica. Non usare markdown né HTML. Modalità richiesta: {$mode}.";
        $user = json_encode(['target_language' => $language, 'mode' => $mode, 'existing_route' => $context], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $result = lauco_openai_structured($developer, $user, 'lauco_route_content', content_ai_schema());
        $result['data'] = content_ai_normalize_payload($result['data'], $language);

        $snapshot = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $generated = json_encode($result['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $stmt = $pdo->prepare(
            "INSERT INTO ai_content_drafts
             (entity_type, entity_id, target_language, mode, source_snapshot, generated_payload,
              provider, model, response_id, request_id, status, created_by)
             VALUES ('percorso', :entity_id, :language, :mode, :source_snapshot, :generated_payload,
              'openai', :model, :response_id, :request_id, 'review', :created_by)"
        );
        $stmt->execute([
            'entity_id' => $routeId,
            'language' => $language,
            'mode' => $mode,
            'source_snapshot' => $snapshot,
            'generated_payload' => $generated,
            'model' => $result['model'],
            'response_id' => $result['response_id'],
            'request_id' => $result['request_id'],
            'created_by' => $adminId ?: null,
        ]);
        $draftId = (int) $pdo->lastInsertId();
        content_ai_audit($pdo, $adminId, 'ai_draft_created', 'percorso', $routeId, ['draft_id' => $draftId, 'language' => $language, 'mode' => $mode]);

        return ['id' => $draftId, 'route' => $route, 'stats' => $stats, 'data' => $result['data']];
    }
}

if (!function_exists('content_ai_bundle_schema')) {
    /** @return array<string,mixed> */
    function content_ai_bundle_schema(): array
    {
        $properties = [];
        $required = [];
        foreach (array_keys(content_supported_languages()) as $language) {
            $payload = content_ai_schema();
            $payload['properties']['language'] = ['type' => 'string', 'enum' => [$language]];
            $properties[$language] = $payload;
            $required[] = $language;
        }
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => $required,
            'properties' => $properties,
        ];
    }
}

if (!function_exists('content_ai_generate_route_bundle')) {
    /**
     * Genera con una sola richiesta una preview coerente in IT, EN, DE e SL.
     * Ogni lingua resta una bozza indipendente e nessun testo viene pubblicato automaticamente.
     *
     * @return array{batch_id:int,draft_ids:array<string,int>,route:array<string,mixed>,stats:array<string,mixed>,locales:array<string,array<string,mixed>>}
     */
    function content_ai_generate_route_bundle(PDO $pdo, int $routeId, string $mode, int $adminId): array
    {
        if (!in_array($mode, ['full', 'translate', 'editorial', 'seo'], true)) {
            throw new RuntimeException('Modalità non valida.');
        }

        $stmt = $pdo->prepare('SELECT * FROM percorsi WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $routeId]);
        $route = $stmt->fetch();
        if (!is_array($route)) {
            throw new RuntimeException('Percorso non trovato.');
        }

        $stats = content_ai_route_stats($route);
        $context = content_ai_route_context($pdo, $route, $stats);
        $languages = content_supported_languages();
        $developer = 'Sei l’assistente editoriale del sito istituzionale Lauco Experience. '
            . 'Genera in un’unica risposta quattro versioni coordinate dello stesso contenuto: italiano, inglese, tedesco e sloveno. '
            . 'L’italiano è la versione editoriale di riferimento; le altre versioni devono essere traduzioni naturali e fedeli, non riassunti. '
            . 'Mantieni nomi propri, toponimi e dati tecnici. Usa esclusivamente i dati forniti e non inventare accessi, servizi, parcheggi, fontane, rifugi, orari, quote, pericoli o punti panoramici. '
            . 'I valori marcati come database prevalgono sul GPX. Se le informazioni non bastano, resta generico e inserisci un warning. '
            . 'Non usare markdown né HTML. Ogni campo deve essere completo nella lingua indicata. Modalità: ' . $mode . '.';
        $user = json_encode([
            'task' => 'Create a synchronized multilingual preview for editorial review.',
            'locales' => $languages,
            'mode' => $mode,
            'existing_route' => $context,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $result = lauco_openai_structured($developer, $user, 'lauco_route_all_locales', content_ai_bundle_schema());

        $localized = [];
        foreach (array_keys($languages) as $language) {
            $payload = $result['data'][$language] ?? null;
            if (!is_array($payload)) {
                throw new RuntimeException('La risposta AI non contiene la lingua ' . strtoupper($language) . '.');
            }
            $localized[$language] = content_ai_normalize_payload($payload, $language);
        }

        $snapshot = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $draftIds = [];
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO ai_generation_batches
                 (entity_type, entity_id, mode, source_snapshot, provider, model, response_id, request_id, status, created_by)
                 VALUES ('percorso', :entity_id, :mode, :source_snapshot, 'openai', :model, :response_id, :request_id, 'review', :created_by)"
            );
            $stmt->execute([
                'entity_id' => $routeId,
                'mode' => $mode,
                'source_snapshot' => $snapshot,
                'model' => $result['model'],
                'response_id' => $result['response_id'],
                'request_id' => $result['request_id'],
                'created_by' => $adminId ?: null,
            ]);
            $batchId = (int) $pdo->lastInsertId();

            $draftInsert = $pdo->prepare(
                "INSERT INTO ai_content_drafts
                 (entity_type, entity_id, target_language, mode, source_snapshot, generated_payload,
                  provider, model, response_id, request_id, status, created_by)
                 VALUES ('percorso', :entity_id, :language, :mode, :source_snapshot, :generated_payload,
                  'openai', :model, :response_id, :request_id, 'review', :created_by)"
            );
            $linkInsert = $pdo->prepare(
                'INSERT INTO ai_generation_batch_drafts (batch_id, draft_id, language) VALUES (:batch_id, :draft_id, :language)'
            );
            foreach ($localized as $language => $payload) {
                $draftInsert->execute([
                    'entity_id' => $routeId,
                    'language' => $language,
                    'mode' => $mode,
                    'source_snapshot' => $snapshot,
                    'generated_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                    'model' => $result['model'],
                    'response_id' => $result['response_id'],
                    'request_id' => $result['request_id'],
                    'created_by' => $adminId ?: null,
                ]);
                $draftId = (int) $pdo->lastInsertId();
                $draftIds[$language] = $draftId;
                $linkInsert->execute(['batch_id' => $batchId, 'draft_id' => $draftId, 'language' => $language]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }

        content_ai_audit($pdo, $adminId, 'ai_multilingual_batch_created', 'percorso', $routeId, [
            'batch_id' => $batchId,
            'languages' => array_keys($localized),
            'mode' => $mode,
        ]);
        return [
            'batch_id' => $batchId,
            'draft_ids' => $draftIds,
            'route' => $route,
            'stats' => $stats,
            'locales' => $localized,
        ];
    }
}

if (!function_exists('content_ai_entity_config')) {
    /** @return array{table:string,title:string,subtitle:?string,excerpt:?string,body:?string,slug:?string}|null */
    function content_ai_entity_config(string $entityType): ?array
    {
        return match ($entityType) {
            'percorso' => ['table' => 'percorsi', 'title' => 'titolo', 'subtitle' => 'sottotitolo', 'excerpt' => 'excerpt', 'body' => 'descrizione', 'slug' => 'slug'],
            'evento' => ['table' => 'eventi', 'title' => 'titolo', 'subtitle' => null, 'excerpt' => 'excerpt', 'body' => 'contenuto', 'slug' => 'slug'],
            'luogo' => ['table' => 'luoghi', 'title' => 'titolo', 'subtitle' => 'sottotitolo', 'excerpt' => 'excerpt', 'body' => 'descrizione', 'slug' => 'slug'],
            'galleria' => ['table' => 'galleria', 'title' => 'titolo', 'subtitle' => 'alt', 'excerpt' => null, 'body' => null, 'slug' => null],
            'slider' => ['table' => 'home_slider', 'title' => 'titolo', 'subtitle' => 'sottotitolo', 'excerpt' => 'button_label', 'body' => null, 'slug' => null],
            default => null,
        };
    }
}

if (!function_exists('content_ai_generate_entity_bundle')) {
    /** @return array{batch_id:int,draft_ids:array<string,int>,entity:array<string,mixed>,locales:array<string,array<string,mixed>>} */
    function content_ai_generate_entity_bundle(PDO $pdo, string $entityType, int $entityId, string $mode, int $adminId): array
    {
        if ($entityType === 'percorso') {
            $route = content_ai_generate_route_bundle($pdo, $entityId, $mode, $adminId);
            return [
                'batch_id' => $route['batch_id'],
                'draft_ids' => $route['draft_ids'],
                'entity' => $route['route'],
                'locales' => $route['locales'],
            ];
        }
        if (!in_array($mode, ['full', 'translate', 'editorial', 'seo'], true)) {
            throw new RuntimeException('Modalità non valida.');
        }
        $config = content_ai_entity_config($entityType);
        if (!$config) {
            throw new RuntimeException('Tipo di contenuto non supportato.');
        }
        $stmt = $pdo->prepare('SELECT * FROM ' . $config['table'] . ' WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $entityId]);
        $entity = $stmt->fetch();
        if (!is_array($entity)) {
            throw new RuntimeException('Contenuto non trovato.');
        }

        $context = [];
        foreach ($entity as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $context[(string) $key] = is_string($value) ? trim(strip_tags($value)) : $value;
            }
        }
        $developer = 'Sei l’assistente editoriale del sito istituzionale Lauco Experience. '
            . 'Genera in un’unica risposta versioni coordinate in italiano, inglese, tedesco e sloveno. '
            . 'L’italiano è la versione editoriale di riferimento; le altre sono traduzioni naturali e complete. '
            . 'Usa soltanto i dati forniti, conserva nomi propri, toponimi, date e informazioni verificabili. Non inventare dettagli. '
            . 'Non usare markdown né HTML. Compila tutti i campi; usa una stringa vuota solo quando il tipo di contenuto non richiede quel campo. '
            . 'Tipo di contenuto: ' . $entityType . '. Modalità: ' . $mode . '.';
        $result = lauco_openai_structured(
            $developer,
            json_encode(['entity_type' => $entityType, 'mode' => $mode, 'source' => $context], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'lauco_content_all_locales',
            content_ai_bundle_schema()
        );
        $localized = [];
        foreach (array_keys(content_supported_languages()) as $language) {
            $payload = $result['data'][$language] ?? null;
            if (!is_array($payload)) {
                throw new RuntimeException('La risposta AI non contiene la lingua ' . strtoupper($language) . '.');
            }
            $localized[$language] = content_ai_normalize_payload($payload, $language);
        }

        $snapshot = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        $draftIds = [];
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO ai_generation_batches
                 (entity_type, entity_id, mode, source_snapshot, provider, model, response_id, request_id, status, created_by)
                 VALUES (:entity_type, :entity_id, :mode, :source_snapshot, 'openai', :model, :response_id, :request_id, 'review', :created_by)"
            );
            $stmt->execute([
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'mode' => $mode,
                'source_snapshot' => $snapshot,
                'model' => $result['model'],
                'response_id' => $result['response_id'],
                'request_id' => $result['request_id'],
                'created_by' => $adminId ?: null,
            ]);
            $batchId = (int) $pdo->lastInsertId();
            $draftInsert = $pdo->prepare(
                "INSERT INTO ai_content_drafts
                 (entity_type, entity_id, target_language, mode, source_snapshot, generated_payload,
                  provider, model, response_id, request_id, status, created_by)
                 VALUES (:entity_type, :entity_id, :language, :mode, :source_snapshot, :generated_payload,
                  'openai', :model, :response_id, :request_id, 'review', :created_by)"
            );
            $linkInsert = $pdo->prepare('INSERT INTO ai_generation_batch_drafts (batch_id, draft_id, language) VALUES (:batch_id, :draft_id, :language)');
            foreach ($localized as $language => $payload) {
                $draftInsert->execute([
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'language' => $language,
                    'mode' => $mode,
                    'source_snapshot' => $snapshot,
                    'generated_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                    'model' => $result['model'],
                    'response_id' => $result['response_id'],
                    'request_id' => $result['request_id'],
                    'created_by' => $adminId ?: null,
                ]);
                $draftId = (int) $pdo->lastInsertId();
                $draftIds[$language] = $draftId;
                $linkInsert->execute(['batch_id' => $batchId, 'draft_id' => $draftId, 'language' => $language]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        content_ai_audit($pdo, $adminId, 'ai_multilingual_batch_created', $entityType, $entityId, [
            'batch_id' => $batchId,
            'languages' => array_keys($localized),
            'mode' => $mode,
        ]);
        return ['batch_id' => $batchId, 'draft_ids' => $draftIds, 'entity' => $entity, 'locales' => $localized];
    }
}

if (!function_exists('content_ai_find_batch')) {
    /** @return array<string,mixed> */
    function content_ai_find_batch(PDO $pdo, int $batchId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM ai_generation_batches WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $batchId]);
        $batch = $stmt->fetch();
        if (!is_array($batch)) {
            throw new RuntimeException('Preview multilingua non trovata.');
        }
        $config = content_ai_entity_config((string) $batch['entity_type']);
        $batch['entity_title'] = '';
        $batch['entity_slug'] = '';
        if ($config) {
            $columns = $config['title'] . ' AS entity_title';
            if ($config['slug']) {
                $columns .= ', ' . $config['slug'] . ' AS entity_slug';
            }
            $entityStmt = $pdo->prepare('SELECT ' . $columns . ' FROM ' . $config['table'] . ' WHERE id = :id LIMIT 1');
            $entityStmt->execute(['id' => (int) $batch['entity_id']]);
            $entity = $entityStmt->fetch();
            if (is_array($entity)) {
                $batch['entity_title'] = (string) ($entity['entity_title'] ?? '');
                $batch['entity_slug'] = (string) ($entity['entity_slug'] ?? '');
            }
        }
        $stmt = $pdo->prepare(
            'SELECT l.language, d.* FROM ai_generation_batch_drafts l
             INNER JOIN ai_content_drafts d ON d.id = l.draft_id
             WHERE l.batch_id = :id ORDER BY FIELD(l.language, \'it\', \'en\', \'de\', \'sl\')'
        );
        $stmt->execute(['id' => $batchId]);
        $batch['drafts'] = [];
        foreach ($stmt->fetchAll() ?: [] as $draft) {
            $draft['generated'] = json_decode((string) $draft['generated_payload'], true, 512, JSON_THROW_ON_ERROR);
            $batch['drafts'][(string) $draft['language']] = $draft;
        }
        return $batch;
    }
}

if (!function_exists('content_ai_review_batch')) {
    function content_ai_review_batch(PDO $pdo, int $batchId, string $action, int $adminId): void
    {
        if (!in_array($action, ['approve', 'reject', 'apply'], true)) {
            throw new RuntimeException('Azione batch non valida.');
        }
        $batch = content_ai_find_batch($pdo, $batchId);
        if (in_array((string) $batch['status'], ['rejected', 'applied'], true)) {
            if (($action === 'reject' && $batch['status'] === 'rejected') || ($action === 'apply' && $batch['status'] === 'applied')) {
                return;
            }
            throw new RuntimeException('Lo stato corrente della preview non consente questa azione.');
        }

        $completed = 0;
        try {
            foreach ($batch['drafts'] as $draft) {
                content_ai_review($pdo, (int) $draft['id'], $action, $adminId);
                $completed++;
            }
        } catch (Throwable $e) {
            if ($completed > 0) {
                $pdo->prepare("UPDATE ai_generation_batches SET status = 'partial', reviewed_by = :admin, reviewed_at = NOW() WHERE id = :id")
                    ->execute(['admin' => $adminId ?: null, 'id' => $batchId]);
            }
            throw $e;
        }

        $status = $action === 'apply' ? 'applied' : ($action === 'approve' ? 'approved' : 'rejected');
        $sql = "UPDATE ai_generation_batches SET status = :status, reviewed_by = :admin, reviewed_at = NOW()";
        if ($action === 'apply') {
            $sql .= ', applied_at = NOW()';
        }
        $sql .= ' WHERE id = :id';
        $pdo->prepare($sql)->execute(['status' => $status, 'admin' => $adminId ?: null, 'id' => $batchId]);
        content_ai_audit($pdo, $adminId, 'ai_multilingual_batch_' . $status, (string) $batch['entity_type'], (int) $batch['entity_id'], [
            'batch_id' => $batchId,
            'languages' => array_keys($batch['drafts']),
        ]);
    }
}

if (!function_exists('content_ai_find_draft')) {
    /** @return array<string,mixed> */
    function content_ai_find_draft(PDO $pdo, int $draftId): array
    {
        $stmt = $pdo->prepare('SELECT * FROM ai_content_drafts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $draftId]);
        $draft = $stmt->fetch();
        if (!is_array($draft)) {
            throw new RuntimeException('Bozza AI non trovata.');
        }
        $draft['entity_title'] = '';
        $draft['entity_slug'] = '';
        $config = content_ai_entity_config((string) $draft['entity_type']);
        if ($config) {
            $columns = $config['title'] . ' AS entity_title';
            if ($config['slug']) {
                $columns .= ', ' . $config['slug'] . ' AS entity_slug';
            }
            $entityStmt = $pdo->prepare('SELECT ' . $columns . ' FROM ' . $config['table'] . ' WHERE id = :id LIMIT 1');
            $entityStmt->execute(['id' => (int) $draft['entity_id']]);
            $entity = $entityStmt->fetch();
            if (is_array($entity)) {
                $draft['entity_title'] = (string) ($entity['entity_title'] ?? '');
                $draft['entity_slug'] = (string) ($entity['entity_slug'] ?? '');
            }
        }
        $draft['generated'] = json_decode((string) $draft['generated_payload'], true, 512, JSON_THROW_ON_ERROR);
        $draft['snapshot'] = json_decode((string) $draft['source_snapshot'], true, 512, JSON_THROW_ON_ERROR);
        return $draft;
    }
}

if (!function_exists('content_ai_route_drafts')) {
    /** @return list<array<string,mixed>> */
    function content_ai_route_drafts(PDO $pdo, int $routeId): array
    {
        try {
            $stmt = $pdo->prepare('SELECT * FROM ai_content_drafts WHERE entity_type = \'percorso\' AND entity_id = :id ORDER BY created_at DESC, id DESC LIMIT 20');
            $stmt->execute(['id' => $routeId]);
            return $stmt->fetchAll() ?: [];
        } catch (Throwable $e) {
            return [];
        }
    }
}

if (!function_exists('content_ai_review')) {
    function content_ai_review(PDO $pdo, int $draftId, string $action, int $adminId): void
    {
        $draft = content_ai_find_draft($pdo, $draftId);
        if (!in_array($action, ['approve', 'reject', 'apply'], true)) {
            throw new RuntimeException('Azione non valida.');
        }
        $config = content_ai_entity_config((string) ($draft['entity_type'] ?? ''));
        if (!$config) {
            throw new RuntimeException('Tipo di bozza non supportato.');
        }

        $status = (string) ($draft['status'] ?? 'review');
        if ($action === 'reject') {
            if ($status === 'applied') {
                throw new RuntimeException('Una bozza già applicata non può essere rifiutata.');
            }
            if ($status === 'rejected') {
                return;
            }
            $stmt = $pdo->prepare("UPDATE ai_content_drafts SET status = 'rejected', reviewed_by = :admin, reviewed_at = NOW() WHERE id = :id");
            $stmt->execute(['admin' => $adminId ?: null, 'id' => $draftId]);
            content_ai_audit($pdo, $adminId, 'ai_draft_rejected', (string) $draft['entity_type'], (int) $draft['entity_id'], ['draft_id' => $draftId]);
            return;
        }
        if ($action === 'approve') {
            if ($status === 'rejected' || $status === 'applied') {
                throw new RuntimeException('Lo stato corrente non consente l’approvazione.');
            }
            if ($status === 'approved') {
                return;
            }
            $stmt = $pdo->prepare("UPDATE ai_content_drafts SET status = 'approved', reviewed_by = :admin, reviewed_at = NOW() WHERE id = :id");
            $stmt->execute(['admin' => $adminId ?: null, 'id' => $draftId]);
            content_ai_audit($pdo, $adminId, 'ai_draft_approved', (string) $draft['entity_type'], (int) $draft['entity_id'], ['draft_id' => $draftId]);
            return;
        }

        if ($status === 'rejected') {
            throw new RuntimeException('Una bozza rifiutata non può essere applicata.');
        }
        if ($status === 'applied') {
            throw new RuntimeException('La bozza è già stata applicata.');
        }

        $payload = content_ai_normalize_payload($draft['generated'], (string) $draft['target_language']);
        $language = (string) $draft['target_language'];
        $pdo->beginTransaction();
        try {
            if ($language === 'it') {
                $fieldMap = [
                    'title' => $config['title'],
                    'subtitle' => $config['subtitle'],
                    'excerpt' => $config['excerpt'],
                    'description' => $config['body'],
                ];
                $assignments = [];
                $params = ['id' => (int) $draft['entity_id']];
                foreach ($fieldMap as $payloadField => $column) {
                    if ($column === null) {
                        continue;
                    }
                    $assignments[] = $column . ' = :' . $payloadField;
                    $params[$payloadField] = trim((string) ($payload[$payloadField] ?? ''));
                }
                if ($assignments === []) {
                    throw new RuntimeException('Il contenuto non ha campi editoriali aggiornabili.');
                }
                $stmt = $pdo->prepare('UPDATE ' . $config['table'] . ' SET ' . implode(', ', $assignments) . ' WHERE id = :id');
                $stmt->execute($params);
            } else {
                $stmt = $pdo->prepare(
                    "INSERT INTO content_translations
                     (entity_type, entity_id, language, title, subtitle, excerpt, body, seo_title, seo_description, status, source_draft_id, published_at)
                     VALUES (:entity_type, :entity_id, :language, :title, :subtitle, :excerpt, :body, :seo_title, :seo_description, 'published', :draft_id, NOW())
                     ON DUPLICATE KEY UPDATE title = VALUES(title), subtitle = VALUES(subtitle), excerpt = VALUES(excerpt), body = VALUES(body),
                     seo_title = VALUES(seo_title), seo_description = VALUES(seo_description), status = 'published', source_draft_id = VALUES(source_draft_id), published_at = NOW(), updated_at = NOW()"
                );
                $stmt->execute([
                    'entity_type' => (string) $draft['entity_type'],
                    'entity_id' => (int) $draft['entity_id'],
                    'language' => $language,
                    'title' => trim((string) ($payload['title'] ?? '')),
                    'subtitle' => trim((string) ($payload['subtitle'] ?? '')),
                    'excerpt' => trim((string) ($payload['excerpt'] ?? '')),
                    'body' => trim((string) ($payload['description'] ?? '')),
                    'seo_title' => trim((string) ($payload['seo_title'] ?? '')),
                    'seo_description' => trim((string) ($payload['seo_description'] ?? '')),
                    'draft_id' => $draftId,
                ]);
            }
            $stmt = $pdo->prepare("UPDATE ai_content_drafts SET status = 'applied', reviewed_by = :admin, reviewed_at = NOW(), applied_at = NOW() WHERE id = :id");
            $stmt->execute(['admin' => $adminId ?: null, 'id' => $draftId]);
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        content_ai_audit($pdo, $adminId, 'ai_draft_applied', (string) $draft['entity_type'], (int) $draft['entity_id'], ['draft_id' => $draftId, 'language' => $language]);
    }
}

if (!function_exists('content_ai_audit')) {
    /** @param array<string,mixed> $details */
    function content_ai_audit(PDO $pdo, int $adminId, string $action, string $entityType, int $entityId, array $details = []): void
    {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO audit_log (admin_id, action_name, entity_type, entity_id, details_json, ip_address)
                 VALUES (:admin_id, :action_name, :entity_type, :entity_id, :details_json, :ip_address)'
            );
            $stmt->execute([
                'admin_id' => $adminId ?: null,
                'action_name' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'details_json' => json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'ip_address' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 45),
            ]);
        } catch (Throwable $e) {
            // L'audit non deve interrompere il flusso editoriale.
        }
    }
}
