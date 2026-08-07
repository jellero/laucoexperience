<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/http-client.php';

$laucoAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (is_file($laucoAutoload)) {
    require_once $laucoAutoload;
}

if (!function_exists('lauco_uuid_v4')) {
    function lauco_uuid_v4(): string
    {
        if (class_exists(\LaucoExperience\AI\OpenAIResponsesClient::class)) {
            return \LaucoExperience\AI\OpenAIResponsesClient::uuidV4();
        }
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20));
    }
}

if (!function_exists('lauco_openai_output_text')) {
    /** @param array<string,mixed> $response */
    function lauco_openai_output_text(array $response): string
    {
        if (class_exists(\LaucoExperience\AI\OpenAIResponsesClient::class)) {
            return \LaucoExperience\AI\OpenAIResponsesClient::outputText($response);
        }
        foreach (($response['output'] ?? []) as $item) {
            if (!is_array($item) || ($item['type'] ?? '') !== 'message') {
                continue;
            }
            foreach (($item['content'] ?? []) as $content) {
                if (!is_array($content)) {
                    continue;
                }
                if (($content['type'] ?? '') === 'refusal') {
                    throw new RuntimeException('Il modello ha rifiutato la richiesta.');
                }
                if (($content['type'] ?? '') === 'output_text' && isset($content['text'])) {
                    return (string) $content['text'];
                }
            }
        }
        throw new RuntimeException('OpenAI non ha restituito un output utilizzabile.');
    }
}

if (!function_exists('lauco_openai_structured')) {
    /**
     * @param array<string,mixed> $schema
     * @return array{data:array<string,mixed>,response_id:?string,request_id:?string,model:string}
     */
    function lauco_openai_structured(string $developerInstructions, string $userInput, string $schemaName, array $schema): array
    {
        $apiKey = lauco_env_required('OPENAI_API_KEY');
        $model = lauco_env_required('OPENAI_MODEL');

        if (class_exists(\LaucoExperience\AI\OpenAIResponsesClient::class)) {
            $client = new \LaucoExperience\AI\OpenAIResponsesClient(
                $apiKey,
                $model,
                lauco_env_int('OPENAI_TIMEOUT_SECONDS', 90),
                lauco_env_int('OPENAI_MAX_OUTPUT_TOKENS', 12000),
                static fn (string $url, array $payload, array $headers, int $timeout): array => lauco_http_post_json($url, $payload, $headers, $timeout),
                trim((string) lauco_env('OPENAI_REASONING_EFFORT', 'low')) ?: 'low'
            );
            $result = $client->structured(
                $developerInstructions,
                $userInput,
                $schemaName,
                $schema,
                'lauco-admin-' . (string) ($_SESSION['admin_id'] ?? 'anonymous')
            );
            unset($result['usage']);
            return $result;
        }

        $clientRequestId = lauco_uuid_v4();
        $payload = [
            'model' => $model,
            'store' => false,
            'max_output_tokens' => max(500, min(12000, lauco_env_int('OPENAI_MAX_OUTPUT_TOKENS', 3500))),
            'input' => [
                ['role' => 'developer', 'content' => [['type' => 'input_text', 'text' => $developerInstructions]]],
                ['role' => 'user', 'content' => [['type' => 'input_text', 'text' => $userInput]]],
            ],
            'text' => [
                'format' => [
                    'type' => 'json_schema',
                    'name' => $schemaName,
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
        ];

        $response = lauco_http_post_json(
            'https://api.openai.com/v1/responses',
            $payload,
            ['Authorization: Bearer ' . $apiKey, 'X-Client-Request-Id: ' . $clientRequestId],
            lauco_env_int('OPENAI_TIMEOUT_SECONDS', 90)
        );

        if ($response['status'] < 200 || $response['status'] >= 300) {
            throw new RuntimeException('OpenAI ha restituito HTTP ' . $response['status'] . '.');
        }

        $decoded = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($decoded)) {
            throw new RuntimeException('Risposta OpenAI non valida.');
        }

        $status = (string) ($decoded['status'] ?? 'completed');
        if ($status !== 'completed') {
            $reason = (string) ($decoded['incomplete_details']['reason'] ?? $decoded['error']['message'] ?? $status);
            throw new RuntimeException('Generazione non completata: ' . $reason);
        }

        $data = json_decode(lauco_openai_output_text($decoded), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('Output strutturato OpenAI non valido.');
        }

        return [
            'data' => $data,
            'response_id' => isset($decoded['id']) ? (string) $decoded['id'] : null,
            'request_id' => $response['headers']['x-request-id'] ?? null,
            'model' => isset($decoded['model']) ? (string) $decoded['model'] : $model,
        ];
    }
}
