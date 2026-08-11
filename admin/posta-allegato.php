<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/../inc/backoffice-mail.php';

$folderPath = trim((string) ($_GET['folder'] ?? 'INBOX')) ?: 'INBOX';
$uid = (int) ($_GET['uid'] ?? 0);
$index = (int) ($_GET['index'] ?? -1);

try {
    if ($index < 0) {
        throw new RuntimeException('Allegato non valido.');
    }
    $client = backoffice_mail_client();
    $folder = backoffice_mail_folder($folderPath, $client);
    $message = backoffice_mail_message($folder, $uid, false);
    $attachments = array_values($message->getAttachments()->all());
    $attachment = $attachments[$index] ?? null;
    if (!$attachment) {
        throw new RuntimeException('Allegato non trovato.');
    }

    $content = (string) $attachment->getContent();
    $limit = (int) backoffice_mail_config()['attachment_max_bytes'];
    if (strlen($content) > $limit) {
        throw new RuntimeException('Allegato troppo grande.');
    }

    $filename = trim(str_replace(["\0", "\r", "\n", '"', '/', '\\'], '_', (string) ($attachment->getName() ?: 'allegato')));
    $filename = $filename !== '' ? mb_substr($filename, 0, 180) : 'allegato';
    $asciiName = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename) ?: 'allegato';
    $mime = preg_replace('/[^A-Za-z0-9.+\/-]/', '', (string) ($attachment->getMimeType() ?: 'application/octet-stream'));

    header('Content-Type: ' . ($mime ?: 'application/octet-stream'));
    header('Content-Length: ' . strlen($content));
    header('Content-Disposition: attachment; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, no-store, max-age=0');
    echo $content;
    exit;
} catch (Throwable $exception) {
    backoffice_mail_log_exception($exception, 'download allegato');
    http_response_code(404);
    exit('Allegato non disponibile.');
}
