<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/page-stats.php';

final class PageStatsTest extends TestCase
{
    public function testPageKeyKeepsOnlyPublicDetailSlug(): void
    {
        self::assertSame('/percorso?slug=anello-di-lauco', page_stats_key('/percorso', [
            'slug' => 'Anello-di-Lauco',
            'lang' => 'en',
            'utm_source' => 'newsletter',
        ]));
        self::assertSame('/mappa', page_stats_key('/mappa.php', ['print' => '1']));
        self::assertSame('/', page_stats_key('/index.php'));
    }

    public function testTrackingRejectsBotsAndPrivatePages(): void
    {
        self::assertTrue(page_stats_should_track('GET', 200, 'mappa-itinerari.php', 'Mozilla/5.0'));
        self::assertFalse(page_stats_should_track('HEAD', 200, 'mappa-itinerari.php', 'Mozilla/5.0'));
        self::assertFalse(page_stats_should_track('GET', 404, '400.php', 'Mozilla/5.0'));
        self::assertFalse(page_stats_should_track('GET', 200, 'login.php', 'Mozilla/5.0'));
        self::assertFalse(page_stats_should_track('GET', 200, 'mappa-itinerari.php', 'Googlebot/2.1'));
    }
}
