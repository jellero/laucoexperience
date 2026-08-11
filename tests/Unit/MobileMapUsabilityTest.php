<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class MobileMapUsabilityTest extends TestCase
{
    public function testRouteMapAlwaysEnablesGestureHandling(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2) . '/assets/js/percorso-map.js');

        self::assertIsString($script);
        self::assertStringContainsString('gestureHandling: true', $script);
        self::assertStringNotContainsString('L.GestureHandling', $script);
    }

    public function testElevationChartsAllowVerticalPanWithoutDisablingInteraction(): void
    {
        $mapCss = file_get_contents(dirname(__DIR__, 2) . '/assets/css/mappa.css');
        $routeCss = file_get_contents(dirname(__DIR__, 2) . '/assets/css/percorso.css');
        $routeView = file_get_contents(dirname(__DIR__, 2) . '/resources/views/pages/percorso.php');

        self::assertIsString($mapCss);
        self::assertIsString($routeCss);
        self::assertIsString($routeView);
        self::assertMatchesRegularExpression(
            '/#elevation \.elevation-control \*\s*\{\s*touch-action:\s*pan-y\s*!important;/',
            $mapCss
        );
        self::assertMatchesRegularExpression(
            '/#percorso-elevation \.elevation-control \*\s*\{\s*touch-action:\s*pan-y\s*!important;/',
            $routeCss
        );
        self::assertDoesNotMatchRegularExpression(
            '/#elevation \.elevation-control[^\{]*\{[^\}]*pointer-events:\s*none;/',
            $mapCss
        );
        self::assertDoesNotMatchRegularExpression(
            '/#percorso-elevation \.elevation-control[^\{]*\{[^\}]*pointer-events:\s*none;/',
            $routeCss
        );
        self::assertMatchesRegularExpression(
            '/#percorso-elevation \.tooltip\s*\{[^\}]*opacity:\s*1\s*!important;/',
            $routeCss
        );
        self::assertStringContainsString('/assets/css/percorso.css?v=', $routeView);
        self::assertStringContainsString('/assets/js/percorso-map.js?v=', $routeView);
    }
}
