<?php
declare(strict_types=1);

namespace LaucoExperience\AI;

use JsonException;
use LaucoExperience\Localization\LocaleResolver;
use LaucoExperience\Localization\SiteCatalogRepository;
use PDO;
use RuntimeException;

final class SiteCatalogTranslationService
{
    public function __construct(
        private readonly PDO $pdo,
        private readonly ?OpenAIResponsesClient $client,
        private readonly SiteCatalogRepository $catalogs,
    ) {
    }

    public function generate(int $adminId): int
    {
        if (!$this->client) {
            throw new RuntimeException('Configurare OPENAI_API_KEY e OPENAI_MODEL prima di generare la preview.');
        }
        $all = $this->catalogs->loadAll();
        $source = $all[LocaleResolver::DEFAULT_LOCALE];
        if ($source === []) {
            throw new RuntimeException('Il catalogo italiano è vuoto.');
        }

        $developer = 'Sei il traduttore editoriale del sito istituzionale Lauco Experience. '
            . 'Traduci ogni messaggio italiano in inglese, tedesco e sloveno in una sola risposta. '
            . 'Mantieni tono informativo, naturale e coerente con turismo outdoor e comunicazione comunale. '
            . 'Non modificare nomi propri, Lauco Experience, indirizzi, email, URL, numeri di emergenza, placeholder tra parentesi graffe o frammenti HTML. '
            . 'Non aggiungere informazioni e non omettere contenuto. Restituisci tutte le chiavi, anche quando il testo è già internazionale.';
        $generated = ['en' => [], 'de' => [], 'sl' => []];
        $lastResult = null;
        // Cataloghi completi possono superare comodamente la dimensione ideale
        // di una singola risposta strutturata. Le porzioni restano coordinate
        // nelle tre lingue e confluiscono in una sola preview atomica.
        foreach (array_chunk($source, 80, true) as $index => $chunk) {
            $user = json_encode([
                'task' => 'Translate this complete portion of the static website catalog for editorial preview.',
                'source_locale' => 'it',
                'target_locales' => ['en', 'de', 'sl'],
                'portion' => $index + 1,
                'messages' => $chunk,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $result = $this->client->structured(
                $developer,
                $user,
                'lauco_site_catalogs_' . ($index + 1),
                $this->schema(array_keys($chunk)),
                'lauco-admin-' . $adminId
            );
            foreach (['en', 'de', 'sl'] as $locale) {
                $catalog = $result['data'][$locale] ?? null;
                if (!is_array($catalog)) {
                    throw new RuntimeException('Catalogo AI mancante per ' . strtoupper($locale) . '.');
                }
                $generated[$locale] = array_replace(
                    $generated[$locale],
                    $this->normalizeGenerated($catalog, array_keys($chunk), $locale)
                );
            }
            $lastResult = $result;
        }
        if (!is_array($lastResult)) {
            throw new RuntimeException('Nessuna porzione del catalogo è stata generata.');
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO site_text_translation_drafts
             (source_revision, source_catalog, generated_catalogs, provider, model, response_id, request_id, status, created_by)
             VALUES (:revision, :source, :generated, 'openai', :model, :response_id, :request_id, 'review', :created_by)"
        );
        $stmt->execute([
            'revision' => $this->catalogs->revision($all),
            'source' => json_encode($source, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'generated' => json_encode($generated, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'model' => $lastResult['model'],
            'response_id' => $lastResult['response_id'],
            'request_id' => $lastResult['request_id'],
            'created_by' => $adminId ?: null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    /** @return array<string,mixed> */
    public function find(int $draftId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM site_text_translation_drafts WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $draftId]);
        $draft = $stmt->fetch();
        if (!is_array($draft)) {
            throw new RuntimeException('Preview testi sito non trovata.');
        }
        try {
            $draft['source'] = json_decode((string) $draft['source_catalog'], true, 512, JSON_THROW_ON_ERROR);
            $draft['generated'] = json_decode((string) $draft['generated_catalogs'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('La preview contiene JSON non valido.', 0, $e);
        }
        return $draft;
    }

    public function apply(int $draftId, int $adminId): void
    {
        $draft = $this->find($draftId);
        if ((string) $draft['status'] === 'applied') {
            return;
        }
        if ((string) $draft['status'] !== 'review') {
            throw new RuntimeException('La preview non può essere applicata nello stato corrente.');
        }
        $current = $this->catalogs->loadAll();
        if (!hash_equals((string) $draft['source_revision'], $this->catalogs->revision($current))) {
            throw new RuntimeException('I cataloghi sono cambiati dopo la generazione. Creare una nuova preview AI.');
        }

        $catalogs = [
            'it' => $draft['source'],
            'en' => $draft['generated']['en'] ?? [],
            'de' => $draft['generated']['de'] ?? [],
            'sl' => $draft['generated']['sl'] ?? [],
        ];
        $this->catalogs->saveAll($catalogs);
        $stmt = $this->pdo->prepare(
            "UPDATE site_text_translation_drafts
             SET status = 'applied', reviewed_by = :admin, reviewed_at = NOW(), applied_at = NOW()
             WHERE id = :id"
        );
        $stmt->execute(['admin' => $adminId ?: null, 'id' => $draftId]);
    }

    public function reject(int $draftId, int $adminId): void
    {
        $draft = $this->find($draftId);
        if ((string) $draft['status'] === 'applied') {
            throw new RuntimeException('Una preview applicata non può essere rifiutata.');
        }
        $stmt = $this->pdo->prepare(
            "UPDATE site_text_translation_drafts
             SET status = 'rejected', reviewed_by = :admin, reviewed_at = NOW()
             WHERE id = :id"
        );
        $stmt->execute(['admin' => $adminId ?: null, 'id' => $draftId]);
    }

    /** @param list<string> $keys @return array<string,mixed> */
    private function schema(array $keys): array
    {
        $messageProperties = [];
        foreach ($keys as $key) {
            $messageProperties[$key] = ['type' => 'string'];
        }
        $catalog = [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => $keys,
            'properties' => $messageProperties,
        ];
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['en', 'de', 'sl'],
            'properties' => [
                'en' => $catalog,
                'de' => $catalog,
                'sl' => $catalog,
            ],
        ];
    }

    /** @param array<mixed,mixed> $catalog @param list<string> $keys @return array<string,string> */
    private function normalizeGenerated(array $catalog, array $keys, string $locale): array
    {
        $normalized = [];
        foreach ($keys as $key) {
            $value = trim((string) ($catalog[$key] ?? ''));
            if ($value === '') {
                throw new RuntimeException('Traduzione vuota per ' . strtoupper($locale) . ': ' . $key);
            }
            $normalized[$key] = $value;
        }
        ksort($normalized, SORT_STRING);
        return $normalized;
    }
}
