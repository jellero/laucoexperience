<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/../inc/backoffice-mail.php';
require_once __DIR__ . '/_admin_layout.php';
require_once __DIR__ . '/_mail_ui.php';

$folderPath = trim((string) ($_GET['folder'] ?? 'INBOX')) ?: 'INBOX';
$uid = (int) ($_GET['uid'] ?? 0);
$folders = [];
$folder = null;
$message = null;
$data = null;
$bodyHtml = '';
$attachments = [];
$trashFolder = null;
$isTrashFolder = false;
$error = '';

try {
    $client = backoffice_mail_client();
    $folders = backoffice_mail_folders($client);
    $folder = backoffice_mail_folder($folderPath, $client);
    $trashFolder = backoffice_mail_trash_folder($client);
    $isTrashFolder = $trashFolder instanceof Webklex\PHPIMAP\Folder
        && strcasecmp($folder->path, $trashFolder->path) === 0;
    $message = backoffice_mail_message($folder, $uid, true);
    $data = backoffice_mail_message_data($message);
    $html = (string) ($message->getHTMLBody() ?? '');
    $text = (string) ($message->getTextBody() ?? '');
    $bodyHtml = $html !== ''
        ? backoffice_mail_sanitize_html($html)
        : nl2br(e($text !== '' ? $text : '(Messaggio senza contenuto)'));
    $attachments = array_values($message->getAttachments()->all());
} catch (Throwable $exception) {
    $error = backoffice_mail_user_error($exception);
}

$selectedFolder = $folder?->path ?? $folderPath;
$title = is_array($data) ? (string) $data['subject'] : 'Messaggio';

admin_page_open($title, 'posta');
admin_mail_styles();
?>
<main class="wrap">
    <div class="actions">
        <a class="btn secondary" href="<?= e(admin_mail_folder_url($selectedFolder)) ?>">Torna alla posta</a>
        <?php if ($data): ?>
            <a class="btn" href="posta-scrivi.php?<?= e(http_build_query(['folder' => $selectedFolder, 'reply_uid' => $data['uid']])) ?>">Rispondi</a>
            <form method="post" action="posta-azione.php" class="inline">
                <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="folder" value="<?= e($selectedFolder) ?>">
                <input type="hidden" name="uid" value="<?= (int) $data['uid'] ?>">
                <input type="hidden" name="action" value="<?= $data['flagged'] ? 'unflag' : 'flag' ?>">
                <button class="btn secondary" type="submit"><?= $data['flagged'] ? 'Rimuovi stella' : 'Aggiungi stella' ?></button>
            </form>
            <form method="post" action="posta-azione.php" class="inline">
                <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="folder" value="<?= e($selectedFolder) ?>">
                <input type="hidden" name="uid" value="<?= (int) $data['uid'] ?>">
                <input type="hidden" name="action" value="unread">
                <button class="btn secondary" type="submit">Segna da leggere</button>
            </form>
            <form method="post" action="posta-azione.php" class="inline" onsubmit="return confirm(<?= e(json_encode($isTrashFolder ? 'Eliminare definitivamente questa email? L\'operazione non è reversibile.' : 'Spostare questa email nel Cestino?', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>)">
                <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="folder" value="<?= e($selectedFolder) ?>">
                <input type="hidden" name="uid" value="<?= (int) $data['uid'] ?>">
                <input type="hidden" name="action" value="delete">
                <button class="btn danger" type="submit"><?= $isTrashFolder ? 'Elimina definitivamente' : 'Elimina' ?></button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($error !== ''): ?>
        <div class="error"><?= e($error) ?></div>
    <?php elseif ($data): ?>
        <article class="mail-message">
            <header class="mail-message-head">
                <h1><?= e($data['subject']) ?></h1>
                <div class="mail-meta">
                    <strong>Da</strong><span><?= e($data['from_label']) ?></span>
                    <strong>A</strong><span><?= e(implode(', ', array_column($data['to'], 'full'))) ?></span>
                    <strong>Data</strong><time><?= e(admin_mail_date($data['date'], true)) ?></time>
                </div>
            </header>

            <div class="mail-body"><?= $bodyHtml ?></div>

            <?php if ($attachments !== []): ?>
                <div class="mail-attachments">
                    <?php foreach ($attachments as $index => $attachment): ?>
                        <a class="mail-attachment" href="posta-allegato.php?<?= e(http_build_query(['folder' => $selectedFolder, 'uid' => $data['uid'], 'index' => $index])) ?>">
                            📎 <?= e((string) ($attachment->getName() ?: 'allegato')) ?>
                            <small><?= e(admin_mail_size((int) $attachment->getSize())) ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
    <?php endif; ?>
</main>
<?php admin_page_close(); ?>
