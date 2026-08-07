<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/openai-client.php';

if (!function_exists('site_text_translation_service')) {
    function site_text_translation_service(PDO $pdo): \LaucoExperience\AI\SiteCatalogTranslationService
    {
        $repository = site_catalog_repository();
        if (!$repository) {
            throw new RuntimeException('Il framework Composer non è disponibile.');
        }
        $apiKey = trim((string) lauco_env('OPENAI_API_KEY', ''));
        $model = trim((string) lauco_env('OPENAI_MODEL', ''));
        $client = $apiKey !== '' && $model !== ''
            ? new \LaucoExperience\AI\OpenAIResponsesClient(
                $apiKey,
                $model,
                lauco_env_int('OPENAI_TIMEOUT_SECONDS', 90),
                lauco_env_int('OPENAI_MAX_OUTPUT_TOKENS', 16000),
                static fn (string $url, array $payload, array $headers, int $timeout): array => lauco_http_post_json($url, $payload, $headers, $timeout),
                trim((string) lauco_env('OPENAI_REASONING_EFFORT', 'low')) ?: 'low'
            )
            : null;
        return new \LaucoExperience\AI\SiteCatalogTranslationService($pdo, $client, $repository);
    }
}
