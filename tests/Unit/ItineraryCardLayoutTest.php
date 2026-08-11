<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ItineraryCardLayoutTest extends TestCase
{
    public function testItineraryImagesUseAConsistentCroppedHeight(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . '/assets/css/percorso.css');
        $listView = file_get_contents(dirname(__DIR__, 2) . '/resources/views/sections/itinerari-list.php');
        $routeView = file_get_contents(dirname(__DIR__, 2) . '/resources/views/pages/percorso.php');

        self::assertIsString($css);
        self::assertIsString($listView);
        self::assertIsString($routeView);
        self::assertMatchesRegularExpression(
            '/\.itinerary-card-image\s*\{[^\}]*height:\s*320px;[^\}]*object-fit:\s*cover;/',
            $css
        );
        self::assertStringContainsString('class="itinerary-card-image"', $listView);
        self::assertStringContainsString('class="itinerary-card-image"', $routeView);
    }
}
