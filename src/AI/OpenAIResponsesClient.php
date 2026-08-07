<?php
declare(strict_types=1);

namespace LaucoExperience\AI;

use JsonException;
use RuntimeException;

final class OpenAIResponsesClient
{
    /**
     * @param callable(string,array<string,mixed>,list<string>,int):array{status:int,body:string,headers:array<string,string>} $transport
     */
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
        private readonly int $timeoutSeconds,
        private readonly int $maxOutputTokens,
        private readonly mixed $transport,
        private readonly string $reasoningEffort = 'low',
    ) {
        if (trim($this->apiKey) === '' || trim($this->model) === '') {
            throw new RuntimeException('Configurazione OpenAI incompleta.');
        }
        if (!is_callable($this->transport)) {
            throw new RuntimeException('Trasporto HTTP OpenAI non valido.');
        }
    }

    /**
     * @param array<string,mixed> $schema
     * @return array{data:array<string,mixed>,response_id:?string,request_id:?string,model:string,usage:array<string,mixed>}
     */
    public function structured(
        string $developerInstructions,
        string $userInput,
        string $schemaName,
        array $schema,
        ?string $safetyIdentifier = null,
    ): array {
        $clientRequestId = self::uuidV4();
        $payload = [
            'model' => $this->model,
            'store' => false,
            'max_output_tokens' => max(500, min(30000, $this->maxOutputTokens)),
            'reasoning' => ['effort' => $this->reasoningEffort],
            'input' => [
                ['role' => 'developer', 'content' => [['type' => 'input_text', 'text' => $developerInstructions]]],
                ['role' => 'user', 'content' => [['type' => 'input_text', 'text' => $userInput]]],
            ],
            'text' => [
                'verbosity' => 'medium',
                'format' => [
                    'type' => 'json_schema',
                    'name' => substr(preg_replace('/[^a-zA-Z0-9_-]/', '_', $schemaName) ?? 'lauco_output', 0, 64),
                    'description' => 'Contenuti editoriali multilingua per Lauco Experience, destinati a revisione umana.',
                    'strict' => true,
                    'schema' => $schema,
                ],
            ],
        ];
        if ($safetyIdentifier !== null && $safetyIdentifier !== '') {
            $payload['safety_identifier'] = hash('sha256', $safetyIdentifier);
        }

        $response = ($this->transport)(
            'https://api.openai.com/v1/responses',
            $payload,
            ['Authorization: Bearer ' . $this->apiKey, 'X-Client-Request-Id: ' . $clientRequestId],
            max(10, $this->timeoutSeconds)
        );
        if ($response['status'] < 200 || $response['status'] >= 300) {
            throw new RuntimeException('OpenAI ha restituito HTTP ' . $response['status'] . '.');
        }

        try {
            $decoded = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('Risposta OpenAI non valida.', 0, $e);
        }
        if (!is_array($decoded)) {
            throw new RuntimeException('Risposta OpenAI non valida.');
        }
        $status = (string) ($decoded['status'] ?? 'completed');
        if ($status !== 'completed') {
            $reason = (string) ($decoded['incomplete_details']['reason'] ?? $decoded['error']['message'] ?? $status);
            throw new RuntimeException('Generazione non completata: ' . $reason);
        }

        try {
            $data = json_decode(self::outputText($decoded), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('Output strutturato OpenAI non valido.', 0, $e);
        }
        if (!is_array($data)) {
            throw new RuntimeException('Output strutturato OpenAI non valido.');
        }

        return [
            'data' => $data,
            'response_id' => isset($decoded['id']) ? (string) $decoded['id'] : null,
            'request_id' => $response['headers']['x-request-id'] ?? null,
            'model' => isset($decoded['model']) ? (string) $decoded['model'] : $this->model,
            'usage' => is_array($decoded['usage'] ?? null) ? $decoded['usage'] : [],
        ];
    }

    /** @param array<string,mixed> $response */
    public static function outputText(array $response): string
    {
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

    public static function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return sprintf('%s-%s-%s-%s-%s', substr($hex, 0, 8), substr($hex, 8, 4), substr($hex, 12, 4), substr($hex, 16, 4), substr($hex, 20));
    }
}
