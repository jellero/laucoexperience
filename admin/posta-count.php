<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/../inc/backoffice-mail.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store, max-age=0');

$cached = $_SESSION['mail_dashboard_summary'] ?? null;
if (
    is_array($cached)
    && (int) ($cached['checked_at'] ?? 0) >= time() - 60
) {
    echo json_encode([
        'success' => (bool) ($cached['success'] ?? false),
        'unread' => max(0, (int) ($cached['unread'] ?? 0)),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$summary = [
    'success' => false,
    'unread' => 0,
    'checked_at' => time(),
];

try {
    $client = backoffice_mail_client();
    $inbox = backoffice_mail_folder('INBOX', $client);
    $summary['unread'] = $inbox->query()->unseen()->count();
    $summary['success'] = true;
} catch (Throwable $exception) {
    backoffice_mail_log_exception($exception, 'conteggio dashboard posta');
}

$_SESSION['mail_dashboard_summary'] = $summary;
echo json_encode([
    'success' => $summary['success'],
    'unread' => $summary['unread'],
], JSON_UNESCAPED_UNICODE);
