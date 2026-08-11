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

    public function testElevationChartsReleasePointerEventsOnMobile(): void
    {
        $mapCss = file_get_contents(dirname(__DIR__, 2) . '/assets/css/mappa.css');
        $routeCss = file_get_contents(dirname(__DIR__, 2) . '/assets/css/percorso.css');

        self::assertIsString($mapCss);
        self::assertIsString($routeCss);
        self::assertMatchesRegularExpression(
            '/#elevation \.elevation-control,[\s\S]*?pointer-events:\s*none;/',
            $mapCss
        );
        self::assertMatchesRegularExpression(
            '/#percorso-elevation \.elevation-control[\s\S]*?pointer-events:\s*none;/',
            $routeCss
        );
    }
}
