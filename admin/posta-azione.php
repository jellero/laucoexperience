<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/../inc/backoffice-mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Metodo non consentito.');
}

verify_csrf();
$folderPath = trim((string) ($_POST['folder'] ?? 'INBOX')) ?: 'INBOX';
$uid = (int) ($_POST['uid'] ?? 0);
$action = trim((string) ($_POST['action'] ?? ''));

try {
    $client = backoffice_mail_client();
    $folder = backoffice_mail_folder($folderPath, $client);
    $message = $folder->query()->setFetchBody(false)->leaveUnread()->getMessageByUid($uid);
    match ($action) {
        'read' => $message->setFlag('Seen'),
        'unread' => $message->unsetFlag('Seen'),
        'flag' => $message->setFlag('Flagged'),
        'unflag' => $message->unsetFlag('Flagged'),
        default => throw new InvalidArgumentException('Azione non valida.'),
    };
} catch (Throwable $exception) {
    backoffice_mail_log_exception($exception, 'azione posta');
    header('Location: posta.php?' . http_build_query(['folder' => $folderPath, 'error' => 1]));
    exit;
}

if ($action === 'unread') {
    header('Location: posta.php?' . http_build_query(['folder' => $folderPath]));
    exit;
}

header('Location: posta-messaggio.php?' . http_build_query(['folder' => $folderPath, 'uid' => $uid]));
exit;
