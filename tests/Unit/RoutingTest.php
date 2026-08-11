<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class RoutingTest extends TestCase
{
    public function testCleanAndLegacyPrivacyUrlsUseTheFrontController(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $factory = new ServerRequestFactory();

        $request = $factory->createServerRequest('GET', 'https://example.test/privacy');
        $response = $app->handle($request);
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<!DOCTYPE html>', (string) $response->getBody());
        self::assertSame('it', $response->getHeaderLine('Content-Language'));

        $request = $factory->createServerRequest('GET', 'https://example.test/privacy.php?lang=en');
        $response = $app->handle($request);
        self::assertSame(301, $response->getStatusCode());
        self::assertSame('/privacy?lang=en', $response->getHeaderLine('Location'));

        $request = $factory->createServerRequest('GET', 'https://example.test/privacy/?lang=de');
        $response = $app->handle($request);
        self::assertSame(301, $response->getStatusCode());
        self::assertSame('/privacy?lang=de', $response->getHeaderLine('Location'));
    }

    public function testForraPageIsRoutedByTheApplication(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'https://example.test/forra');
        $response = $app->handle($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Forra del Vinadia', (string) $response->getBody());
    }

    public function testMapQrEntryIsTrackedRouteAndPublicMapIsDirect(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $factory = new ServerRequestFactory();

        $response = $app->handle($factory->createServerRequest('GET', 'https://example.test/map'));
        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/mappa', $response->getHeaderLine('Location'));
        self::assertSame('', $response->getHeaderLine('Set-Cookie'));
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));

        $response = $app->handle($factory->createServerRequest('GET', 'https://example.test/mappa'));
        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('<!DOCTYPE html>', (string) $response->getBody());
        self::assertStringContainsString('/mappa/pdf', (string) $response->getBody());

        $response = $app->handle($factory->createServerRequest('GET', 'https://example.test/qr?c=map'));
        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/mappa', $response->getHeaderLine('Location'));

        $response = $app->handle($factory->createServerRequest('GET', 'https://example.test/qr?c=non-esiste'));
        self::assertSame(404, $response->getStatusCode());
        self::assertSame('', $response->getHeaderLine('Set-Cookie'));
    }

    public function testMapPdfEndpointTracksIntentAndOpensPrintableMap(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $response = $app->handle(
            (new ServerRequestFactory())->createServerRequest('GET', 'https://example.test/mappa/pdf')
        );

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/mappa?print=1', $response->getHeaderLine('Location'));
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));
    }

    public function testOnlyExplicitGpxDownloadGetsAttachmentResponse(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $factory = new ServerRequestFactory();
        $path = 'https://example.test/gpx/LAUCO_%23_1.gpx';

        $response = $app->handle($factory->createServerRequest('GET', $path));
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('', $response->getHeaderLine('Content-Disposition'));

        $response = $app->handle($factory->createServerRequest('GET', $path . '?download=1'));
        self::assertSame(200, $response->getStatusCode());
        self::assertStringStartsWith('attachment;', $response->getHeaderLine('Content-Disposition'));
    }

    public function testLegacyPostKeepsItsBodyAndReachesTheAction(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $request = (new ServerRequestFactory())->createServerRequest('POST', 'https://example.test/newsletter.php');
        $response = $app->handle($request);

        self::assertNotSame(301, $response->getStatusCode());
        self::assertStringStartsWith('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testHealthEndpointDescribesTheNewArchitecture(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'https://example.test/api/v1/health');
        $response = $app->handle($request);
        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('front-controller', $payload['architecture'] ?? null);
        self::assertSame('slim-4', $payload['framework'] ?? null);
    }

    public function testSitemapIsAvailableAsXml(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'https://example.test/sitemap.xml');
        $response = $app->handle($request);
        $body = (string) $response->getBody();

        self::assertSame(200, $response->getStatusCode());
        self::assertStringStartsWith('application/xml', $response->getHeaderLine('Content-Type'));
        self::assertStringContainsString('<urlset', $body);
        self::assertStringContainsString('<loc>https://', $body);
        self::assertStringContainsString('/mappa', $body);
        self::assertStringNotContainsString('<loc>https://laucoexperience.it/map</loc>', $body);
        self::assertStringContainsString('hreflang="en"', $body);
    }

    public function testUnknownPageUsesTheOrganized404View(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'https://example.test/non-esiste');
        $response = $app->handle($request);

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('<!DOCTYPE html>', (string) $response->getBody());
    }

    public function testExplicitErrorRouteReturns404(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'https://example.test/400');
        $response = $app->handle($request);

        self::assertSame(404, $response->getStatusCode());
        self::assertStringContainsString('<!DOCTYPE html>', (string) $response->getBody());
    }

    public function testNewsletterRejectsGetWithJsonResponse(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'https://example.test/newsletter');
        $response = $app->handle($request);

        self::assertSame(405, $response->getStatusCode());
        self::assertStringStartsWith('application/json', $response->getHeaderLine('Content-Type'));
    }
}
