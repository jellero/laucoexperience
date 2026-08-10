<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/route-ai-editorial.php';

final class RouteAiEditorialTest extends TestCase
{
    public function testRouteRequestGetsEditorialBriefAndHardRules(): void
    {
        $input = json_encode([
            'existing_route' => [
                'id' => 123,
                'title' => 'ALLEGNIDIS - I',
                'technical_data' => [
                    'distance' => '5,15 km',
                    'ascent' => '353 m',
                    'duration' => '1 h 53 min',
                    'difficulty' => 'E',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $result = \lauco_route_ai_enrich_request('lauco_route_all_locales', 'Base instructions.', $input);
        $payload = json_decode($result['user'], true, 512, JSON_THROW_ON_ERROR);

        self::assertStringContainsString('3-5 paragrafi', $result['developer']);
        self::assertStringContainsString('non la ripetizione del box tecnico', $result['developer']);
        self::assertStringContainsString('non sono disponibili ulteriori informazioni', $result['developer']);
        self::assertSame('3-5 paragrafi', $payload['existing_route']['editorial_brief']['description_structure']);
    }

    public function testNonRouteSchemasAreUntouched(): void
    {
        $result = \lauco_route_ai_enrich_request('lauco_content_all_locales', 'Developer', '{"ok":true}');

        self::assertSame('Developer', $result['developer']);
        self::assertSame('{"ok":true}', $result['user']);
    }
}
