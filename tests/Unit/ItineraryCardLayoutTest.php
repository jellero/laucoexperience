<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class ItineraryCardLayoutTest extends TestCase
{
    public function testItineraryImagesUseAConsistentCroppedHeight(): void
    {
        $css = file_get_contents(dirname(__DIR__, 2) . '/assets/css/style.css');
        $scss = file_get_contents(dirname(__DIR__, 2) . '/assets/sass/style.scss');
        $homeView = file_get_contents(dirname(__DIR__, 2) . '/resources/views/sections/trips.php');
        $listView = file_get_contents(dirname(__DIR__, 2) . '/resources/views/sections/itinerari-list.php');
        $routeView = file_get_contents(dirname(__DIR__, 2) . '/resources/views/pages/percorso.php');
        $headerView = file_get_contents(dirname(__DIR__, 2) . '/resources/views/partials/header.php');

        self::assertIsString($css);
        self::assertIsString($scss);
        self::assertIsString($homeView);
        self::assertIsString($listView);
        self::assertIsString($routeView);
        self::assertIsString($headerView);
        self::assertMatchesRegularExpression(
            '/img\.itinerary-card-image\s*\{[^\}]*height:\s*320px;[^\}]*object-fit:\s*cover;/',
            $css
        );
        self::assertStringContainsString('&.itinerary-card-image', $scss);
        self::assertStringContainsString('class="itinerary-card-image"', $homeView);
        self::assertStringContainsString('class="itinerary-card-image"', $listView);
        self::assertStringContainsString('class="itinerary-card-image"', $routeView);
        self::assertStringContainsString('/assets/css/style.css?v=', $headerView);
    }
}
