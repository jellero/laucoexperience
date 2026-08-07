<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();

function redirect_percorsi(array $params = []): void
{
    $url = 'percorsi.php';

    if ($params) {
        $url .= '?' . http_build_query($params);
    }

    header('Location: ' . $url);
    exit;
}

function admin_table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table');
    $stmt->execute(['table' => $table]);

    return (int) $stmt->fetchColumn() > 0;
}

function absolute_upload_path(?string $relativePath): ?string
{
    if (!$relativePath) {
        return null;
    }

    $root = realpath(dirname(__DIR__) . '/uploads');
    $absolute = realpath(dirname(__DIR__) . '/' . $relativePath);

    if ($root && $absolute && strpos($absolute, $root) === 0 && is_file($absolute)) {
        return $absolute;
    }

    return null;
}

function delete_files_after_commit(array $paths): void
{
    foreach ($paths as $path) {
        if ($path && is_file($path)) {
            @unlink($path);
        }
    }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
} elseif ($method === 'GET') {
    $token = (string) ($_GET['_csrf_token'] ?? '');

    if (!hash_equals($_SESSION['_csrf_token'] ?? '', $token)) {
        redirect_percorsi(['deleted' => '0', 'error' => 'token di sicurezza non valido']);
    }

    $id = (int) ($_GET['id'] ?? 0);
} else {
    redirect_percorsi(['deleted' => '0', 'error' => 'metodo non consentito']);
}

if ($id <= 0) {
    redirect_percorsi(['deleted' => '0', 'error' => 'id percorso non valido']);
}

$filesToDelete = [];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT * FROM percorsi WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $percorso = $stmt->fetch();

    if (!$percorso) {
        $pdo->rollBack();
        redirect_percorsi(['deleted' => '0', 'error' => 'percorso non trovato']);
    }

    $filesToDelete[] = absolute_upload_path($percorso['cover_image'] ?? null);
    $filesToDelete[] = absolute_upload_path($percorso['gpx_file'] ?? null);

    $stmt = $pdo->prepare('SELECT * FROM percorso_gallery WHERE percorso_id = :id');
    $stmt->execute(['id' => $id]);
    $gallery = $stmt->fetchAll();

    foreach ($gallery as $img) {
        $filesToDelete[] = absolute_upload_path($img['image_path'] ?? null);
    }

    if (admin_table_exists($pdo, 'home_slider')) {
        $stmt = $pdo->prepare("UPDATE home_slider SET link_type = 'none', percorso_id = NULL WHERE percorso_id = :id");
        $stmt->execute(['id' => $id]);
    }

    $stmt = $pdo->prepare('DELETE FROM percorso_gallery WHERE percorso_id = :id');
    $stmt->execute(['id' => $id]);

    $stmt = $pdo->prepare('DELETE FROM percorsi WHERE id = :id');
    $stmt->execute(['id' => $id]);

    $pdo->commit();

    delete_files_after_commit($filesToDelete);
    redirect_percorsi(['deleted' => '1']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    redirect_percorsi([
        'deleted' => '0',
        'error' => $e->getMessage(),
    ]);
}
