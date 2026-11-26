<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/inc/home-image.php';

final class HomeImageTest extends TestCase
{
    public function testOnlyExpectedPublicJpegPathsAreAccepted(): void
    {
        self::assertSame('uploads/galleria/20260602115819-bee92ec64a65.jpg', home_image_path('/uploads/galleria/20260602115819-bee92ec64a65.jpg'));
        foreach (['../.env', '/uploads/contributi/20260602115819-bee92ec64a65.jpg',
            '//uploads/galleria/20260602115819-bee92ec64a65.jpg',
            'https://example.com/uploads/galleria/20260602115819-bee92ec64a65.jpg',
            'uploads/galleria/../../.env', 'uploads/galleria/x.php'] as $invalid) {
            self::assertNull(home_image_path($invalid));
        }
    }

    public function testWebpEncodingPreservesAspectRatioAndOriginal(): void
    {
        if (!home_image_webp_available() || !function_exists('exif_read_data')) {
            self::markTestSkipped('Optional GD WebP and EXIF support is unavailable.');
        }
        $root = sys_get_temp_dir() . '/lauco-image-' . bin2hex(random_bytes(6));
        $path = 'uploads/galleria/20260602115819-bee92ec64a65.jpg';
        mkdir($root . '/uploads/galleria', 0770, true);
        $file = $root . '/' . $path;
        $variant = $root . '/variant.webp';
        try {
            $image = imagecreatetruecolor(800, 400);
            imagefilledrectangle($image, 0, 0, 799, 399, imagecolorallocate($image, 100, 150, 80));
            imagejpeg($image, $file, 90);
            $original = hash_file('sha256', $file);
            $info = home_image_info($root, $path);
            self::assertNotNull($info);
            self::assertStringContainsString('320w', home_image_attributes($root, $path, 'gallery'));
            self::assertStringContainsString('800w', home_image_attributes($root, $path, 'gallery'));
            self::assertTrue(home_image_encode($info, $variant, 320));
            $dimensions = getimagesize($variant);
            self::assertSame([320, 160, IMAGETYPE_WEBP], array_slice($dimensions, 0, 3));
            self::assertTrue(home_image_encode($info, $variant, 960));
            self::assertSame(800, getimagesize($variant)[0]);
            self::assertSame($original, hash_file('sha256', $file));
        } finally {
            if (is_file($variant)) { unlink($variant); }
            if (is_file($file)) { unlink($file); }
            rmdir($root . '/uploads/galleria');
            rmdir($root . '/uploads');
            rmdir($root);
        }
    }
}
