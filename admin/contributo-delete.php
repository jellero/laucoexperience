<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_admin_permission('admin.all');

$id = (int) ($_GET['id'] ?? 0);
$token = (string) ($_GET['_csrf_token'] ?? '');

verify_csrf($token);

if (!$id) {
    header('Location: contributi.php');
    exit;
}

$stmt = $pdo->prepare('DELETE FROM contributi WHERE id = :id');
$stmt->execute(['id' => $id]);

header('Location: contributi.php');
exit;
