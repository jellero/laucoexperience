<?php
declare(strict_types=1);

/** Optional responsive JPEG-to-WebP variants for the homepage only. */
function home_image_path(string $value): ?string
{
    if (!preg_match('~^/?(?:uploads/(?:slider|galleria)/[0-9]{14}-[a-f0-9]{12}\.jpe?g|assets/img/old\.jpg)$~iD', $value)) {
        return null;
    }
    return ltrim($value, '/');
}

function home_image_widths(string $path): array
{
    return str_starts_with($path, 'uploads/galleria/') ? [320, 640, 960] : [768, 1200, 1600, 1920];
}

function home_image_info(string $root, string $value): ?array
{
    $path = home_image_path($value);
    $base = realpath($root);
    if ($path === null || $base === false) {
        return null;
    }
    $file = $base . '/' . $path;
    // Do not follow symlinks or serve any file outside the allowlisted folders.
    if (realpath($file) !== $file || !is_file($file) || !is_readable($file)) {
        return null;
    }
    $bytes = @filesize($file);
    $mtime = @filemtime($file);
    if (!$bytes || $bytes > 16_000_000 || $mtime === false) {
        return null;
    }
    $info = @getimagesize($file);
    if ($info === false || $info[2] !== IMAGETYPE_JPEG || $info[0] < 1 || $info[1] < 1
        || $info[0] * $info[1] > 12_000_000) {
        return null;
    }
    return [
        'path' => $path, 'file' => $file, 'width' => $info[0], 'height' => $info[1],
        'bytes' => $bytes, 'version' => substr(hash('sha256', $bytes . ':' . $mtime . ':webp-v1'), 0, 16),
    ];
}

function home_image_webp_available(): bool
{
    return function_exists('imagecreatefromjpeg') && function_exists('imagewebp')
        && function_exists('imagecreatetruecolor') && function_exists('imagecopyresampled')
        && function_exists('imagetypes') && (imagetypes() & IMG_WEBP) !== 0;
}

function home_image_attributes(string $root, string $path, string $kind): string
{
    $info = home_image_info($root, $path);
    if ($info === null) {
        return '';
    }
    $attributes = ' width="' . $info['width'] . '" height="' . $info['height'] . '"';
    if (!home_image_webp_available()) {
        return $attributes;
    }
    $variants = [];
    foreach (home_image_widths($info['path']) as $width) {
        $actual = min($width, $info['width']);
        $url = '/public/home-image.php?' . http_build_query(
            ['src' => $info['path'], 'w' => $width, 'v' => $info['version']], '', '&', PHP_QUERY_RFC3986
        );
        if (!isset($variants[$actual])) {
            $variants[$actual] = $url . ' ' . $actual . 'w';
        }
    }
    $sizes = $kind === 'gallery'
        ? '(max-width: 479px) 50vw, (max-width: 766px) 25vw, (max-width: 990px) 20vw, (max-width: 1198px) 17vw, 13vw'
        : '(max-width: 767px) max(100vw, 120vh), 100vw';
    return $attributes . ' srcset="' . htmlspecialchars(implode(', ', $variants), ENT_QUOTES, 'UTF-8')
        . '" sizes="' . htmlspecialchars($sizes, ENT_QUOTES, 'UTF-8') . '"';
}

/** Never upscale or overwrite a source; return false on unsupported metadata or encoding. */
function home_image_encode(array $info, string $destination, int $width): bool
{
    if (!home_image_webp_available()) {
        return false;
    }
    // Preserve orientation: originals with non-normal EXIF orientation fall back unchanged.
    if (function_exists('exif_read_data')) {
        $exif = @exif_read_data($info['file']);
        if (is_array($exif) && (int) ($exif['Orientation'] ?? 1) !== 1) {
            return false;
        }
    } else {
        // Without EXIF support we cannot safely determine whether rotation is required.
        return false;
    }
    $source = @imagecreatefromjpeg($info['file']);
    if ($source === false) {
        return false;
    }
    $width = min($width, $info['width']);
    $height = max(1, (int) round($info['height'] * $width / $info['width']));
    $target = @imagecreatetruecolor($width, $height);
    if ($target === false) {
        return false;
    }
    if (!@imagecopyresampled($target, $source, 0, 0, 0, 0, $width, $height, $info['width'], $info['height'])) {
        return false;
    }
    $quality = str_starts_with($info['path'], 'uploads/galleria/') ? 78 : 82;
    return @imagewebp($target, $destination, $quality) && is_file($destination) && filesize($destination) > 0;
}
