<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use LaucoExperience\Localization\SiteCatalogRepository;
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

    public function testParticipationStringsExistInEverySupportedLanguage(): void
    {
        $root = dirname(__DIR__, 2);
        $repository = new SiteCatalogRepository(
            $root . '/resources/lang',
            sys_get_temp_dir() . '/lauco-participation-catalog-' . bin2hex(random_bytes(4))
        );
        $keys = [
            'participation.active.body',
            'participation.active.cta',
            'participation.active.title',
            'participation.home.intro',
            'participation.volunteer.breadcrumb',
            'participation.volunteer.hero',
        ];

        foreach (['it', 'en', 'de', 'sl'] as $locale) {
            $catalog = $repository->loadDefault($locale);
            foreach ($keys as $key) {
                self::assertArrayHasKey($key, $catalog, $locale . ': ' . $key);
                self::assertNotSame('', trim((string) $catalog[$key]), $locale . ': ' . $key);
            }
        }

        self::assertSame('Get actively involved', $repository->loadDefault('en')['participation.active.title']);
        self::assertSame('Aktiv mitmachen', $repository->loadDefault('de')['participation.active.title']);
        self::assertSame('Aktivno sodeluj', $repository->loadDefault('sl')['participation.active.title']);
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
