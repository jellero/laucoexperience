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
    if ($action === 'delete') {
        $trashFolder = backoffice_mail_trash_folder($client);
        $alreadyInTrash = $trashFolder instanceof Webklex\PHPIMAP\Folder
            && strcasecmp($folder->path, $trashFolder->path) === 0;

        if ($trashFolder instanceof Webklex\PHPIMAP\Folder && !$alreadyInTrash) {
            if (!$message->move($trashFolder->path, true)) {
                throw new RuntimeException('Non è stato possibile spostare il messaggio nel Cestino.');
            }
        } elseif (!$message->delete(true)) {
            throw new RuntimeException('Non è stato possibile eliminare il messaggio.');
        }
    } else {
        match ($action) {
            'read' => $message->setFlag('Seen'),
            'unread' => $message->unsetFlag('Seen'),
            'flag' => $message->setFlag('Flagged'),
            'unflag' => $message->unsetFlag('Flagged'),
            default => throw new InvalidArgumentException('Azione non valida.'),
        };
    }
} catch (Throwable $exception) {
    backoffice_mail_log_exception($exception, 'azione posta');
    header('Location: posta.php?' . http_build_query(['folder' => $folderPath, 'error' => 1]));
    exit;
}

if ($action === 'delete') {
    unset($_SESSION['mail_dashboard_summary']);
    header('Location: posta.php?' . http_build_query(['folder' => $folderPath, 'deleted' => 1]));
    exit;
}

if ($action === 'unread') {
    header('Location: posta.php?' . http_build_query(['folder' => $folderPath]));
    exit;
}

header('Location: posta-messaggio.php?' . http_build_query(['folder' => $folderPath, 'uid' => $uid]));
exit;
