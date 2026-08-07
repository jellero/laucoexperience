<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use LaucoExperience\AI\OpenAIResponsesClient;
use PHPUnit\Framework\TestCase;

final class OpenAIResponsesClientTest extends TestCase
{
    public function testItUsesResponsesStructuredOutputsWithoutStorage(): void
    {
        $captured = [];
        $transport = static function (string $url, array $payload, array $headers, int $timeout) use (&$captured): array {
            $captured = compact('url', 'payload', 'headers', 'timeout');
            return [
                'status' => 200,
                'headers' => ['x-request-id' => 'req_test'],
                'body' => json_encode([
                    'id' => 'resp_test',
                    'model' => 'gpt-5.6-terra',
                    'status' => 'completed',
                    'output' => [[
                        'type' => 'message',
                        'content' => [['type' => 'output_text', 'text' => '{"ok":true}']],
                    ]],
                    'usage' => ['input_tokens' => 10, 'output_tokens' => 4],
                ], JSON_THROW_ON_ERROR),
            ];
        };
        $client = new OpenAIResponsesClient('test-key', 'gpt-5.6-terra', 30, 4000, $transport, 'low');

        $result = $client->structured('Instructions', 'Input', 'test_schema', [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => ['ok'],
            'properties' => ['ok' => ['type' => 'boolean']],
        ], 'admin-1');

        self::assertTrue($result['data']['ok']);
        self::assertSame('https://api.openai.com/v1/responses', $captured['url']);
        self::assertFalse($captured['payload']['store']);
        self::assertSame('json_schema', $captured['payload']['text']['format']['type']);
        self::assertTrue($captured['payload']['text']['format']['strict']);
        self::assertSame('low', $captured['payload']['reasoning']['effort']);
        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $captured['payload']['safety_identifier']);
    }
}
