<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class ParticipationRoutingTest extends TestCase
{
    public function testVolunteerPageContainsTheExistingSignupForm(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'https://example.test/volontariato');
        $response = $app->handle($request);
        $body = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Partecipa attivamente', $body);
        self::assertStringContainsString('id="volunteerSignupForm"', $body);
        self::assertStringContainsString('action="/volontariato/iscrizione"', $body);
    }

    public function testHomeParticipationOffersThreeIndependentActionsWithoutEmbeddingTheForm(): void
    {
        $section = file_get_contents(dirname(__DIR__, 2) . '/resources/views/sections/contributi.php');

        self::assertIsString($section);
        self::assertStringContainsString('href="/segnala-problema"', $section);
        self::assertStringContainsString('href="/contribuisci"', $section);
        self::assertStringContainsString('href="/volontariato"', $section);
        self::assertStringNotContainsString('<span class="code">1</span>', $section);
        self::assertStringNotContainsString('<span class="code">2</span>', $section);
        self::assertStringNotContainsString('<span class="code">3</span>', $section);
        self::assertStringNotContainsString('modalità progressive', $section);
        self::assertStringNotContainsString('dal gesto più semplice', $section);
        self::assertStringNotContainsString('sections/volontariato.php', $section);
        self::assertStringNotContainsString('volunteerSignupForm', $section);
    }

    public function testVolunteerPageIsIncludedInThePublicSitemap(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'https://example.test/sitemap.xml');
        $response = $app->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString(
            '<loc>https://laucoexperience.it/volontariato</loc>',
            (string) $response->getBody()
        );
    }
}
