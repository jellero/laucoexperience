<?php
declare(strict_types=1);

if (!function_exists('sponsor_upload_image')) {
    function sponsor_upload_image(string $field): ?string
    {
        $file = $_FILES[$field] ?? null;
        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Errore durante il caricamento del logo.');
        }
        if ((int) ($file['size'] ?? 0) <= 0 || (int) $file['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException('Il logo deve pesare al massimo 5 MB.');
        }

        $temporaryPath = (string) ($file['tmp_name'] ?? '');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($temporaryPath);
        $extensions = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        if (!is_string($mime) || !isset($extensions[$mime]) || @getimagesize($temporaryPath) === false) {
            throw new RuntimeException('Formato non valido. Usa JPG, PNG, WEBP o GIF.');
        }

        $relativeDir = 'uploads/sponsor';
        $absoluteDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'sponsor';
        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
            throw new RuntimeException('Impossibile creare la cartella dei loghi.');
        }

        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
        $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $filename;
        if (!move_uploaded_file($temporaryPath, $absolutePath)) {
            throw new RuntimeException('Impossibile salvare il logo.');
        }

        return $relativeDir . '/' . $filename;
    }
}

if (!function_exists('sponsor_delete_uploaded_image')) {
    function sponsor_delete_uploaded_image(?string $relativePath): void
    {
        if (!$relativePath || !str_starts_with(str_replace('\\', '/', $relativePath), 'uploads/sponsor/')) {
            return;
        }

        $root = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'sponsor');
        $absolute = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath));
        if ($root === false || $absolute === false || !str_starts_with($absolute, $root . DIRECTORY_SEPARATOR)) {
            return;
        }
        if (is_file($absolute)) {
            unlink($absolute);
        }
    }
}

if (!function_exists('sponsor_normalize_url')) {
    function sponsor_normalize_url(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        if (filter_var($value, FILTER_VALIDATE_URL) === false) {
            throw new RuntimeException('Inserisci un URL completo e valido, per esempio https://www.sito.it.');
        }
        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            throw new RuntimeException('Il link deve iniziare con http:// oppure https://.');
        }
        return $value;
    }
}
