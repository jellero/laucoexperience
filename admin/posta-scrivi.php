<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/../inc/backoffice-mail.php';
require_once __DIR__ . '/_admin_layout.php';
require_once __DIR__ . '/_mail_ui.php';

$folderPath = trim((string) ($_GET['folder'] ?? $_POST['folder'] ?? 'INBOX')) ?: 'INBOX';
$replyUid = (int) ($_GET['reply_uid'] ?? $_POST['reply_uid'] ?? 0);
$contactId = (int) ($_GET['contact_id'] ?? $_POST['contact_id'] ?? 0);
$to = mb_substr(trim((string) ($_GET['to'] ?? $_POST['to'] ?? '')), 0, 1000);
$cc = mb_substr(trim((string) ($_POST['cc'] ?? '')), 0, 1000);
$bcc = mb_substr(trim((string) ($_POST['bcc'] ?? '')), 0, 1000);
$subject = mb_substr(trim((string) ($_GET['subject'] ?? $_POST['subject'] ?? '')), 0, 255);
$body = trim((string) ($_POST['body'] ?? ''));
$originalMessageId = trim((string) ($_POST['original_message_id'] ?? ''));
$originalReferences = trim((string) ($_POST['original_references'] ?? ''));
$error = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $replyUid > 0) {
    try {
        $client = backoffice_mail_client();
        $folder = backoffice_mail_folder($folderPath, $client);
        $original = backoffice_mail_message($folder, $replyUid, false);
        $data = backoffice_mail_message_data($original);
        $recipient = $data['reply_to'][0] ?? $data['from'][0] ?? null;
        $to = is_array($recipient) ? (string) $recipient['full'] : '';
        $subject = preg_match('/^\s*re\s*:/i', (string) $data['subject'])
            ? (string) $data['subject']
            : 'Re: ' . $data['subject'];
        $originalMessageId = (string) $data['message_id'];
        $references = backoffice_mail_attribute_first($original->getReferences());
        $originalReferences = trim(is_scalar($references) ? (string) $references : '');
        $originalText = trim((string) ($original->getTextBody() ?? ''));
        if ($originalText !== '') {
            $quote = preg_replace('/^/m', '> ', $originalText);
            $body = "\n\n--- Messaggio originale ---\nDa: " . $data['from_label'] . "\nData: " . admin_mail_date($data['date'], true) . "\n\n" . $quote;
        }
    } catch (Throwable $exception) {
        $error = backoffice_mail_user_error($exception);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $contactId > 0) {
    $stmt = $pdo->prepare('SELECT email, oggetto FROM contatti_messaggi WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $contactId]);
    $contact = $stmt->fetch();
    if (is_array($contact)) {
        $to = (string) $contact['email'];
        $subject = preg_match('/^\s*re\s*:/i', (string) $contact['oggetto'])
            ? (string) $contact['oggetto']
            : 'Re: ' . $contact['oggetto'];
    } else {
        $contactId = 0;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        if ($body === '') {
            throw new InvalidArgumentException('Scrivi il testo del messaggio.');
        }
        $attachments = backoffice_mail_uploads($_FILES['attachments'] ?? []);
        backoffice_mail_send(
            backoffice_mail_parse_recipients($to),
            backoffice_mail_parse_recipients($cc),
            backoffice_mail_parse_recipients($bcc),
            $subject,
            $body,
            $attachments,
            $originalMessageId,
            $originalReferences
        );

        if ($replyUid > 0) {
            try {
                $client = backoffice_mail_client();
                $folder = backoffice_mail_folder($folderPath, $client);
                $folder->query()->setFetchBody(false)->leaveUnread()->getMessageByUid($replyUid)->setFlag('Answered');
            } catch (Throwable $exception) {
                backoffice_mail_log_exception($exception, 'contrassegno risposta');
            }
        }

        if ($contactId > 0) {
            $stmt = $pdo->prepare("UPDATE contatti_messaggi SET stato = 'risposto' WHERE id = :id");
            $stmt->execute(['id' => $contactId]);
        }

        header('Location: posta.php?sent=1');
        exit;
    } catch (Throwable $exception) {
        $error = backoffice_mail_user_error($exception);
    }
}

admin_page_open($replyUid > 0 || $contactId > 0 ? 'Rispondi' : 'Scrivi email', 'posta');
admin_mail_styles();
?>
<main class="wrap">
    <section class="hero-admin">
        <h1><?= $replyUid > 0 || $contactId > 0 ? 'Rispondi' : 'Scrivi email' ?></h1>
        <p>Il messaggio verrà inviato da info@laucoexperience.it.</p>
    </section>

    <?php if ($error !== ''): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a class="btn secondary" href="<?= e(admin_mail_folder_url($folderPath)) ?>">Annulla</a>
    </div>

    <form class="mail-compose" method="post" enctype="multipart/form-data">
        <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="folder" value="<?= e($folderPath) ?>">
        <input type="hidden" name="reply_uid" value="<?= $replyUid ?>">
        <input type="hidden" name="contact_id" value="<?= $contactId ?>">
        <input type="hidden" name="original_message_id" value="<?= e($originalMessageId) ?>">
        <input type="hidden" name="original_references" value="<?= e($originalReferences) ?>">

        <div class="mail-compose-grid">
            <div class="full">
                <label for="mail-to">A</label>
                <input id="mail-to" type="text" name="to" value="<?= e($to) ?>" required autocomplete="off" placeholder="nome@esempio.it">
            </div>
            <div>
                <label for="mail-cc">CC</label>
                <input id="mail-cc" type="text" name="cc" value="<?= e($cc) ?>" autocomplete="off">
            </div>
            <div>
                <label for="mail-bcc">CCN</label>
                <input id="mail-bcc" type="text" name="bcc" value="<?= e($bcc) ?>" autocomplete="off">
            </div>
            <div class="full">
                <label for="mail-subject">Oggetto</label>
                <input id="mail-subject" type="text" name="subject" maxlength="255" value="<?= e($subject) ?>" required>
            </div>
            <div class="full">
                <label for="mail-body">Messaggio</label>
                <textarea id="mail-body" name="body" required><?= e($body) ?></textarea>
            </div>
            <div class="full">
                <label for="mail-attachments">Allegati</label>
                <input id="mail-attachments" type="file" name="attachments[]" multiple>
                <small>Dimensione complessiva massima: <?= e(admin_mail_size((int) backoffice_mail_config()['attachment_max_bytes'])) ?>.</small>
            </div>
        </div>

        <div class="actions" style="margin:22px 0 0">
            <button class="btn" type="submit">Invia email</button>
        </div>
    </form>
</main>
<?php admin_page_close(); ?>
