<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/share-stats.php';

final class ShareStatsTest extends TestCase
{
    public function testOnlyKnownShareChannelsAreAccepted(): void
    {
        self::assertSame('open', share_stats_channel('open'));
        self::assertSame('facebook', share_stats_channel(' Facebook '));
        self::assertSame('copy_link', share_stats_channel('copy_link'));
        self::assertNull(share_stats_channel('unknown'));
    }

    public function testPublicRouteIncludesShareTrackingEndpoint(): void
    {
        $routes = require dirname(__DIR__, 2) . '/config/routes.php';
        $route = null;
        foreach ($routes as $candidate) {
            if (($candidate['name'] ?? '') === 'share.track') {
                $route = $candidate;
                break;
            }
        }

        self::assertIsArray($route);
        self::assertSame(['POST'], $route['methods']);
        self::assertSame(['/api/share'], $route['paths']);
    }
}
