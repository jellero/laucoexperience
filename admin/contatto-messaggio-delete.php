<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_admin_permission('admin.all');

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$token = (string) ($_GET['_csrf_token'] ?? $_POST['_csrf_token'] ?? '');

verify_csrf($token);

if (!$id) {
    header('Location: contatti-messaggi.php?msg=' . urlencode('Messaggio non valido.'));
    exit;
}

$stmt = $pdo->prepare('DELETE FROM contatti_messaggi WHERE id = :id');
$stmt->execute(['id' => $id]);

header('Location: contatti-messaggi.php?msg=' . urlencode('Messaggio eliminato.'));
exit;
