<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_admin_permission('admin.all');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$token = (string) ($_GET['_csrf_token'] ?? $_POST['_csrf_token'] ?? '');

verify_csrf($token);

if (!$id) {
    header('Location: segnalazioni.php?msg=' . urlencode('Segnalazione non valida.'));
    exit;
}

$uploadRoot = dirname(__DIR__);

function delete_segnalazione_upload(?string $relativePath): void
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

$stmt = $pdo->prepare('SELECT * FROM segnalazioni_problemi WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$s = $stmt->fetch();

if ($s) {
    delete_segnalazione_upload($s['allegato_path'] ?? null);

    $del = $pdo->prepare('DELETE FROM segnalazioni_problemi WHERE id = :id');
    $del->execute(['id' => $id]);
}

header('Location: segnalazioni.php?msg=' . urlencode('Segnalazione eliminata.'));
exit;
