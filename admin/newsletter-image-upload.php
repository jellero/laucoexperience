<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

function newsletter_upload_response(bool $success, string $message, array $extra = [], int $status = 200): never
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message] + $extra, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    newsletter_upload_response(false, 'Metodo non consentito.', [], 405);
}

$token = (string) ($_POST['_csrf_token'] ?? '');
if ($token === '' || !hash_equals(csrf_token(), $token)) {
    newsletter_upload_response(false, 'Sessione scaduta. Ricarica la pagina.', [], 419);
}

if (empty($_FILES['image']) || !is_array($_FILES['image'])) {
    newsletter_upload_response(false, 'Seleziona una immagine.', [], 422);
}

$file = $_FILES['image'];
$error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
if ($error !== UPLOAD_ERR_OK) {
    newsletter_upload_response(false, 'Errore durante il caricamento dell’immagine.', [], 422);
}

$size = (int) ($file['size'] ?? 0);
if ($size <= 0 || $size > 8 * 1024 * 1024) {
    newsletter_upload_response(false, 'L’immagine deve essere inferiore a 8 MB.', [], 422);
}

$tmp = (string) ($file['tmp_name'] ?? '');
if ($tmp === '' || !is_uploaded_file($tmp)) {
    newsletter_upload_response(false, 'File caricato non valido.', [], 422);
}

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = (string) $finfo->file($tmp);
$extensions = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];
if (!isset($extensions[$mime])) {
    newsletter_upload_response(false, 'Formato immagine non consentito.', [], 422);
}

$relativeDir = 'uploads/newsletter';
$root = dirname(__DIR__);
$absoluteDir = $root . '/' . $relativeDir;
if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0775, true) && !is_dir($absoluteDir)) {
    newsletter_upload_response(false, 'Impossibile creare la cartella immagini.', [], 500);
}

$filename = date('YmdHis') . '-' . bin2hex(random_bytes(8)) . '.' . $extensions[$mime];
$absolutePath = $absoluteDir . '/' . $filename;
if (!move_uploaded_file($tmp, $absolutePath)) {
    newsletter_upload_response(false, 'Impossibile salvare l’immagine.', [], 500);
}

$https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
    || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
$host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
if ($host === '') {
    newsletter_upload_response(false, 'Host applicazione non disponibile.', [], 500);
}

$basePath = trim((string) lauco_env('APP_BASE_PATH', ''), '/');
$publicPath = ($basePath !== '' ? '/' . $basePath : '') . '/' . $relativeDir . '/' . $filename;
$url = ($https ? 'https://' : 'http://') . $host . $publicPath;

newsletter_upload_response(true, 'Immagine caricata.', [
    'path' => $relativeDir . '/' . $filename,
    'url' => $url,
]);
