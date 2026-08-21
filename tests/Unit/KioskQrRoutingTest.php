<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;

final class KioskQrRoutingTest extends TestCase
{
    public function testKioskQrRedirectsToHomeWithoutCookies(): void
    {
        $app = require dirname(__DIR__, 2) . '/bootstrap/app.php';
        $request = (new ServerRequestFactory())->createServerRequest('GET', 'https://example.test/chiosco');
        $response = $app->handle($request);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/', $response->getHeaderLine('Location'));
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));
        self::assertSame('', $response->getHeaderLine('Set-Cookie'));
    }
}
