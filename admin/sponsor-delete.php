<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_sponsor_helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Metodo non consentito.');
}

verify_csrf();
$id = max(0, (int) ($_POST['id'] ?? 0));
if ($id === 0) {
    header('Location: sponsor.php?msg=' . urlencode('Sponsor non valido.'));
    exit;
}

$stmt = $pdo->prepare('SELECT image_path FROM sponsors WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$sponsor = $stmt->fetch();
if (!$sponsor) {
    header('Location: sponsor.php?msg=' . urlencode('Sponsor non trovato.'));
    exit;
}

$stmt = $pdo->prepare('DELETE FROM sponsors WHERE id = :id');
$stmt->execute(['id' => $id]);
sponsor_delete_uploaded_image((string) $sponsor['image_path']);

header('Location: sponsor.php?msg=' . urlencode('Sponsor eliminato.'));
exit;
