<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class VolunteeringTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/inc/volontariato.php';
    }

    public function testItalianMobileNumbersAreNormalizedForCloudApi(): void
    {
        self::assertSame('+393331234567', volontariato_normalize_phone('333 123 4567'));
        self::assertSame('+393331234567', volontariato_normalize_phone('0039 333 123 4567'));
        self::assertSame('+436601234567', volontariato_normalize_phone('+43 660 1234567'));
        self::assertNull(volontariato_normalize_phone('123'));
    }

    public function testInterestsOnlyDecodeKnownValues(): void
    {
        self::assertSame(['sentieri', 'foto'], volontariato_decode_interessi('["sentieri","invalid","foto"]'));
        self::assertSame([], volontariato_decode_interessi('not-json'));
    }

    public function testPublicRoutesIncludeSignupWebhookAndTrailStatus(): void
    {
        $routes = require dirname(__DIR__, 2) . '/config/routes.php';
        $names = array_column($routes, 'name');
        self::assertContains('volunteer.signup', $names);
        self::assertContains('whatsapp.webhook', $names);
        self::assertContains('trail.status', $names);
    }
}
