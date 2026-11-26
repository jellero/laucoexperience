<?php
declare(strict_types=1);

// Independent media endpoint: no session, application bootstrapping or database access.
require_once dirname(__DIR__) . '/inc/home-image.php';
$root = dirname(__DIR__);
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');
header('Vary: Accept');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if (!in_array($method, ['GET', 'HEAD'], true)) {
    header('Allow: GET, HEAD');
    http_response_code(405);
    exit;
}
$src = $_GET['src'] ?? '';
$width = $_GET['w'] ?? '';
$version = $_GET['v'] ?? '';
if (!is_string($src) || !is_string($width) || !is_string($version)) {
    http_response_code(400);
    exit;
}
$info = home_image_info($root, $src);
if ($info === null || !in_array($width, array_map('strval', home_image_widths($info['path'])), true)) {
    http_response_code(404);
    exit;
}
$fallback = static function () use ($info): never {
    header('Location: /' . $info['path'], true, 302);
    exit;
};
if ($version !== $info['version'] || !home_image_webp_available()
    || !function_exists('exif_read_data')
    || !str_contains(strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? '')), 'image/webp')) {
    $fallback();
}
$directory = $root . '/uploads/.home-image-cache';
if ((!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory))
    || realpath($directory) !== realpath($root) . '/uploads/.home-image-cache') {
    $fallback();
}
$sourceHash = @hash_file('sha256', $info['file']);
if ($sourceHash === false) {
    $fallback();
}
$key = hash('sha256', $info['path'] . ':' . $sourceHash . ':' . $width . ':webp-v1');
$cache = $directory . '/' . $key . '.webp';
if (!is_file($cache)) {
    $lock = @fopen($directory . '/' . $key . '.lock', 'c');
    if ($lock === false || !flock($lock, LOCK_EX)) {
        if (is_resource($lock)) { fclose($lock); }
        $fallback();
    }
    try {
        if (!is_file($cache)) {
            $temporary = @tempnam($directory, 'tmp-');
            if ($temporary !== false) {
                try {
                    if (home_image_encode($info, $temporary, (int) $width)) {
                        @rename($temporary, $cache);
                    }
                } catch (Throwable $error) {
                    // Encoding is optional; never affect normal page or original image delivery.
                } finally {
                    if (is_file($temporary)) { @unlink($temporary); }
                }
            }
        }
    } finally {
        flock($lock, LOCK_UN);
        fclose($lock);
    }
}
clearstatcache(true, $cache);
$length = is_file($cache) ? @filesize($cache) : false;
if (!$length || $length >= $info['bytes'] || !is_readable($cache)) {
    $fallback();
}
header('Cache-Control: public, max-age=604800');
header('ETag: "' . $key . '"');
if (trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? '')) === '"' . $key . '"') {
    http_response_code(304);
    exit;
}
header('Content-Type: image/webp');
header('Content-Length: ' . $length);
if ($method !== 'HEAD') {
    readfile($cache);
}
