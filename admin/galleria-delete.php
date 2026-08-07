<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();

function request_token(): string
{
    return (string) ($_POST['_csrf_token'] ?? $_GET['_csrf_token'] ?? $_POST['token'] ?? $_GET['token'] ?? '');
}

function request_gallery_id(): int
{
    return (int) ($_POST['id'] ?? $_GET['id'] ?? $_POST['galleria_id'] ?? $_GET['galleria_id'] ?? 0);
}

if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'GET'], true)) {
    http_response_code(405);
    exit('Metodo non consentito.');
}

$id = request_gallery_id();
$token = request_token();

if (!$token || !hash_equals($_SESSION['_csrf_token'] ?? '', $token)) {
    http_response_code(403);
    exit('Token di sicurezza non valido.');
}

if (!$id) {
    header('Location: galleria.php?msg=' . urlencode('Immagine non valida.'));
    exit;
}

$uploadRoot = dirname(__DIR__);

function delete_gallery_upload(?string $relativePath): void
{
    global $uploadRoot;

    if (!$relativePath) {
        return;
    }

    $absolute = realpath($uploadRoot . '/' . $relativePath);
    $root = realpath($uploadRoot . '/uploads');

    if ($absolute && $root && strpos($absolute, $root) === 0 && is_file($absolute)) {
        unlink($absolute);
    }
}

$stmt = $pdo->prepare('SELECT * FROM galleria WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$img = $stmt->fetch();

if (!$img) {
    header('Location: galleria.php?msg=' . urlencode('Immagine non trovata.'));
    exit;
}

delete_gallery_upload($img['image_path'] ?? null);

$del = $pdo->prepare('DELETE FROM galleria WHERE id = :id');
$del->execute(['id' => $id]);

header('Location: galleria.php?msg=' . urlencode('Immagine eliminata.'));
exit;
