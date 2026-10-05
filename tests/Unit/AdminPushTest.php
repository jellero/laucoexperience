<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AdminPushTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/inc/env.php';
        require_once dirname(__DIR__, 2) . '/inc/admin-push-preferences.php';
    }

    public function testPushEndpointAllowlistCoversAppleAndroidAndFirefox(): void
    {
        self::assertTrue(admin_push_endpoint_allowed('https://web.push.apple.com/QP/example'));
        self::assertTrue(admin_push_endpoint_allowed('https://fcm.googleapis.com/fcm/send/example'));
        self::assertTrue(admin_push_endpoint_allowed('https://updates.push.services.mozilla.com/wpush/v2/example'));
        self::assertTrue(admin_push_endpoint_allowed('https://wns2-example.notify.windows.com/w/?token=example'));

        self::assertFalse(admin_push_endpoint_allowed('http://fcm.googleapis.com/fcm/send/example'));
        self::assertFalse(admin_push_endpoint_allowed('https://example.com/push'));
        self::assertFalse(admin_push_endpoint_allowed('https://push.apple.com.evil.example/push'));
    }

    public function testGeneratedVapidKeysAreP256Coordinates(): void
    {
        if (!extension_loaded('openssl')) {
            self::markTestSkipped('OpenSSL non disponibile.');
        }

        $keys = admin_push_generate_vapid_keypair();
        $public = admin_push_base64url_decode($keys['public']);
        $private = admin_push_base64url_decode($keys['private']);

        self::assertIsString($public);
        self::assertIsString($private);
        self::assertSame(65, strlen($public));
        self::assertSame("\x04", $public[0]);
        self::assertSame(32, strlen($private));
    }

    public function testVapidAuthorizationContainsEs256JwtAndPublicKey(): void
    {
        if (!extension_loaded('openssl')) {
            self::markTestSkipped('OpenSSL non disponibile.');
        }

        $keys = admin_push_generate_vapid_keypair();
        $authorization = admin_push_vapid_authorization(
            'https://web.push.apple.com/QP/example',
            $keys['public'],
            $keys['private']
        );

        self::assertStringStartsWith('vapid t=', $authorization);
        self::assertStringContainsString(', k=' . $keys['public'], $authorization);

        preg_match('/^vapid t=([^,]+), k=/', $authorization, $matches);
        self::assertArrayHasKey(1, $matches);
        $jwtParts = explode('.', $matches[1]);
        self::assertCount(3, $jwtParts);

        $header = json_decode((string) admin_push_base64url_decode($jwtParts[0]), true, 512, JSON_THROW_ON_ERROR);
        $payload = json_decode((string) admin_push_base64url_decode($jwtParts[1]), true, 512, JSON_THROW_ON_ERROR);
        $signature = admin_push_base64url_decode($jwtParts[2]);

        self::assertSame('ES256', $header['alg']);
        self::assertSame('https://web.push.apple.com', $payload['aud']);
        self::assertArrayHasKey('exp', $payload);
        self::assertArrayHasKey('sub', $payload);
        self::assertIsString($signature);
        self::assertSame(64, strlen($signature));
    }

    public function testPushCategoriesRespectBackofficePermissions(): void
    {
        self::assertSame(
            ['mail', 'contacts', 'reports', 'contributions', 'volunteers', 'whatsapp', 'newsletter'],
            array_keys(admin_push_allowed_categories('admin'))
        );
        self::assertSame(
            ['mail', 'contacts', 'reports', 'contributions'],
            array_keys(admin_push_allowed_categories('collaboratore'))
        );
        self::assertSame(
            ['whatsapp'],
            array_keys(admin_push_allowed_categories('whatsapp'))
        );
        self::assertSame(
            ['mail', 'contacts', 'reports', 'contributions', 'whatsapp'],
            array_keys(admin_push_allowed_categories('collaboratore,whatsapp'))
        );
    }
}
