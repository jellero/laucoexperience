<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

final class SeoMetadataTest extends TestCase
{
    #[RunInSeparateProcess]
    public function testHomepageTargetsLaucoAndDevelopmentHostIsNotIndexed(): void
    {
        $root = dirname(__DIR__, 2);
        require_once $root . '/inc/seo.php';

        $previousUri = $_SERVER['REQUEST_URI'] ?? null;
        $previousHost = $_SERVER['HTTP_HOST'] ?? null;
        $previousGet = $_GET;

        try {
            $_SERVER['REQUEST_URI'] = '/';
            $_SERVER['HTTP_HOST'] = 'laucoexperience.it';
            $_GET = ['lang' => 'it'];

            $meta = seo_metadata();
            self::assertSame(
                'Lauco: cosa vedere, sentieri e itinerari | Lauco Experience',
                $meta['title']
            );
            self::assertSame('https://laucoexperience.it/', $meta['canonical']);
            self::assertStringContainsString('cosa vedere', $meta['description']);
            self::assertStringStartsWith('index,follow', $meta['robots']);
            self::assertContains('TouristDestination', array_column($meta['json_ld']['@graph'], '@type'));

            $_SERVER['HTTP_HOST'] = 'dev.laucoexperience.it';
            $staging = seo_metadata();
            self::assertSame('noindex,follow', $staging['robots']);
        } finally {
            $_GET = $previousGet;
            if ($previousUri === null) {
                unset($_SERVER['REQUEST_URI']);
            } else {
                $_SERVER['REQUEST_URI'] = $previousUri;
            }
            if ($previousHost === null) {
                unset($_SERVER['HTTP_HOST']);
            } else {
                $_SERVER['HTTP_HOST'] = $previousHost;
            }
        }
    }
}
