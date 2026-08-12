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
$contributionId = (int) ($_GET['contribution_id'] ?? $_POST['contribution_id'] ?? 0);
$reportId = (int) ($_GET['report_id'] ?? $_POST['report_id'] ?? 0);
$to = mb_substr(trim((string) ($_GET['to'] ?? $_POST['to'] ?? '')), 0, 1000);
$cc = mb_substr(trim((string) ($_POST['cc'] ?? '')), 0, 1000);
$bcc = mb_substr(trim((string) ($_POST['bcc'] ?? '')), 0, 1000);
$subject = mb_substr(trim((string) ($_GET['subject'] ?? $_POST['subject'] ?? '')), 0, 255);
$htmlBody = trim((string) ($_POST['html_body'] ?? ''));
$originalMessageId = trim((string) ($_POST['original_message_id'] ?? ''));
$originalReferences = trim((string) ($_POST['original_references'] ?? ''));
$error = '';
$replyOnly = admin_role() === 'collaboratore';
$isReply = $replyUid > 0 || $contactId > 0 || $contributionId > 0 || $reportId > 0;

if ($replyOnly && !$isReply) {
    admin_access_denied('Il ruolo Collaboratore può rispondere alle comunicazioni ricevute, ma non iniziare una nuova email.');
}

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
            $quote = nl2br(e($originalText));
            $htmlBody = '<p><br></p><hr><p><strong>Messaggio originale</strong><br>'
                . 'Da: ' . e((string) $data['from_label']) . '<br>'
                . 'Data: ' . e(admin_mail_date($data['date'], true)) . '</p>'
                . '<blockquote>' . $quote . '</blockquote>';
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $contributionId > 0) {
    $stmt = $pdo->prepare('SELECT email, titolo FROM contributi WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $contributionId]);
    $contribution = $stmt->fetch();
    if (is_array($contribution) && filter_var($contribution['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
        $to = (string) $contribution['email'];
        $subject = 'Re: Contributo — ' . (string) $contribution['titolo'];
    } else {
        $contributionId = 0;
        $error = 'Il contributo non ha un indirizzo email valido.';
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $reportId > 0) {
    $stmt = $pdo->prepare('SELECT email, titolo FROM segnalazioni_problemi WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $reportId]);
    $report = $stmt->fetch();
    if (is_array($report) && filter_var($report['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
        $to = (string) $report['email'];
        $subject = 'Re: Segnalazione — ' . (string) $report['titolo'];
    } else {
        $reportId = 0;
        $error = 'La segnalazione non ha un indirizzo email valido.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        if ($replyOnly) {
            if ($contactId > 0) {
                $stmt = $pdo->prepare('SELECT email FROM contatti_messaggi WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => $contactId]);
                $to = (string) $stmt->fetchColumn();
            } elseif ($contributionId > 0) {
                $stmt = $pdo->prepare('SELECT email FROM contributi WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => $contributionId]);
                $to = (string) $stmt->fetchColumn();
            } elseif ($reportId > 0) {
                $stmt = $pdo->prepare('SELECT email FROM segnalazioni_problemi WHERE id = :id LIMIT 1');
                $stmt->execute(['id' => $reportId]);
                $to = (string) $stmt->fetchColumn();
            } elseif ($replyUid > 0) {
                $client = backoffice_mail_client();
                $folder = backoffice_mail_folder($folderPath, $client);
                $original = backoffice_mail_message($folder, $replyUid, false);
                $data = backoffice_mail_message_data($original);
                $recipient = $data['reply_to'][0] ?? $data['from'][0] ?? null;
                $to = is_array($recipient) ? (string) $recipient['full'] : '';
                $originalMessageId = (string) $data['message_id'];
                $references = backoffice_mail_attribute_first($original->getReferences());
                $originalReferences = trim(is_scalar($references) ? (string) $references : '');
            }
            $replyRecipients = backoffice_mail_parse_recipients($to);
            if (count($replyRecipients) !== 1) {
                throw new RuntimeException('Il destinatario della risposta non è valido.');
            }
            $to = $replyRecipients[0]->toString();
            $cc = '';
            $bcc = '';
        }

        $htmlBody = newsletter_sanitize_editor_html($htmlBody);
        $plainBody = backoffice_mail_html_to_text($htmlBody);
        if ($plainBody === '' && !preg_match('/<img\b/i', $htmlBody)) {
            throw new InvalidArgumentException('Scrivi il testo del messaggio.');
        }
        if ($plainBody === '') {
            $plainBody = '[Messaggio HTML con immagine]';
        }
        $attachments = backoffice_mail_uploads($_FILES['attachments'] ?? []);
        backoffice_mail_send(
            backoffice_mail_parse_recipients($to),
            backoffice_mail_parse_recipients($cc),
            backoffice_mail_parse_recipients($bcc),
            $subject,
            $plainBody,
            $htmlBody,
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
        if ($contributionId > 0) {
            $stmt = $pdo->prepare("UPDATE contributi SET stato = IF(stato IN ('nuovo','letto'), 'valutato', stato) WHERE id = :id");
            $stmt->execute(['id' => $contributionId]);
        }
        if ($reportId > 0) {
            $stmt = $pdo->prepare("UPDATE segnalazioni_problemi SET stato = IF(stato = 'nuova', 'in_lavorazione', stato) WHERE id = :id");
            $stmt->execute(['id' => $reportId]);
        }

        header('Location: posta.php?sent=1');
        exit;
    } catch (Throwable $exception) {
        $error = backoffice_mail_user_error($exception);
    }
}

$htmlBody = newsletter_sanitize_editor_html($htmlBody);

admin_page_open($isReply ? 'Rispondi' : 'Scrivi email', 'posta');
admin_mail_styles();
?>
<style>
    .mail-editor-toolbar { display:flex; gap:6px; flex-wrap:wrap; padding:10px; background:#f3f3f3; border:1px solid #ddd; border-bottom:0; }
    .mail-editor-toolbar button,.mail-editor-toolbar label { width:auto; margin:0; padding:8px 10px; border:1px solid #ccc; background:#fff; cursor:pointer; font-weight:700; font-size:13px; }
    .mail-editor-toolbar button:hover,.mail-editor-toolbar label:hover { background:#222; color:#fff; border-color:#222; }
    .mail-html-editor { min-height:320px; border:1px solid #ddd; padding:22px; outline:none; background:#fff; line-height:1.55; overflow-wrap:anywhere; }
    .mail-html-editor img { max-width:100%; height:auto; }
    .mail-html-source { display:none; min-height:320px!important; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:13px; }
    .mail-image-input { position:absolute; left:-9999px; width:1px!important; height:1px; }
    .mail-editor-preview { display:none; margin-top:16px; }
    .mail-editor-preview iframe { width:100%; height:520px; border:1px solid #ddd; background:#fff; }
    @media(max-width:760px) { .mail-html-editor { padding:16px; } }
</style>
<main class="wrap">
    <section class="hero-admin">
        <h1><?= $isReply ? 'Rispondi' : 'Scrivi email' ?></h1>
        <p>Il messaggio verrà inviato da info@laucoexperience.it.</p>
    </section>

    <?php if ($error !== ''): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a class="btn secondary" href="<?= e(admin_mail_folder_url($folderPath)) ?>">Annulla</a>
    </div>

    <form class="mail-compose" id="mailComposeForm" method="post" enctype="multipart/form-data">
        <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="folder" value="<?= e($folderPath) ?>">
        <input type="hidden" name="reply_uid" value="<?= $replyUid ?>">
        <input type="hidden" name="contact_id" value="<?= $contactId ?>">
        <input type="hidden" name="contribution_id" value="<?= $contributionId ?>">
        <input type="hidden" name="report_id" value="<?= $reportId ?>">
        <input type="hidden" name="original_message_id" value="<?= e($originalMessageId) ?>">
        <input type="hidden" name="original_references" value="<?= e($originalReferences) ?>">
        <textarea name="html_body" id="mailHtmlBody" hidden><?= e($htmlBody) ?></textarea>

        <div class="mail-compose-grid">
            <div class="full">
                <label for="mail-to">A</label>
                <input id="mail-to" type="text" name="to" value="<?= e($to) ?>" required autocomplete="off" placeholder="nome@esempio.it" <?= $replyOnly ? 'readonly' : '' ?>>
            </div>
            <?php if (!$replyOnly): ?>
            <div>
                <label for="mail-cc">CC</label>
                <input id="mail-cc" type="text" name="cc" value="<?= e($cc) ?>" autocomplete="off">
            </div>
            <div>
                <label for="mail-bcc">CCN</label>
                <input id="mail-bcc" type="text" name="bcc" value="<?= e($bcc) ?>" autocomplete="off">
            </div>
            <?php endif; ?>
            <div class="full">
                <label for="mail-subject">Oggetto</label>
                <input id="mail-subject" type="text" name="subject" maxlength="255" value="<?= e($subject) ?>" required>
            </div>
            <div class="full">
                <label>Messaggio</label>
                <div class="mail-editor-toolbar" id="mailEditorToolbar" aria-label="Strumenti editor HTML">
                    <button type="button" data-command="bold"><strong>B</strong></button>
                    <button type="button" data-command="italic"><em>I</em></button>
                    <button type="button" data-command="underline"><u>U</u></button>
                    <button type="button" data-block="h1">H1</button>
                    <button type="button" data-block="h2">H2</button>
                    <button type="button" data-block="p">Paragrafo</button>
                    <button type="button" data-command="insertUnorderedList">Elenco</button>
                    <button type="button" id="mailLinkButton">Link</button>
                    <label for="mailInlineImage">Immagine</label>
                    <input class="mail-image-input" type="file" id="mailInlineImage" accept="image/jpeg,image/png,image/webp,image/gif">
                    <button type="button" id="mailHtmlToggle">HTML</button>
                    <button type="button" id="mailPreviewButton">Anteprima</button>
                </div>
                <div id="mailHtmlEditor" class="mail-html-editor" contenteditable="true"><?= $htmlBody ?></div>
                <textarea id="mailSourceEditor" class="mail-html-source" aria-label="Codice HTML del messaggio"></textarea>
                <div id="mailEditorPreview" class="mail-editor-preview">
                    <iframe id="mailPreviewFrame" title="Anteprima email" sandbox=""></iframe>
                </div>
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
<script>
(function () {
    const form = document.getElementById('mailComposeForm');
    const editor = document.getElementById('mailHtmlEditor');
    const htmlBody = document.getElementById('mailHtmlBody');
    const source = document.getElementById('mailSourceEditor');
    const toolbar = document.getElementById('mailEditorToolbar');
    const imageInput = document.getElementById('mailInlineImage');
    const htmlToggle = document.getElementById('mailHtmlToggle');
    const previewButton = document.getElementById('mailPreviewButton');
    const preview = document.getElementById('mailEditorPreview');
    const previewFrame = document.getElementById('mailPreviewFrame');
    const linkButton = document.getElementById('mailLinkButton');
    const csrf = <?= json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    let sourceMode = false;
    let lastRange = null;

    function rememberSelection() {
        const selection = window.getSelection();
        if (selection && selection.rangeCount > 0 && editor.contains(selection.anchorNode)) {
            lastRange = selection.getRangeAt(0).cloneRange();
        }
    }

    function restoreSelection() {
        if (!lastRange) return;
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(lastRange);
    }

    function syncHidden() {
        htmlBody.value = sourceMode ? source.value : editor.innerHTML;
    }

    editor.addEventListener('keyup', rememberSelection);
    editor.addEventListener('mouseup', rememberSelection);
    editor.addEventListener('input', syncHidden);

    toolbar.querySelectorAll('[data-command]').forEach((button) => {
        button.addEventListener('click', () => {
            restoreSelection();
            document.execCommand(button.dataset.command, false, null);
            editor.focus();
            syncHidden();
        });
    });

    toolbar.querySelectorAll('[data-block]').forEach((button) => {
        button.addEventListener('click', () => {
            restoreSelection();
            document.execCommand('formatBlock', false, button.dataset.block);
            editor.focus();
            syncHidden();
        });
    });

    linkButton.addEventListener('click', () => {
        let url = window.prompt('URL del link (https://...)');
        if (!url) return;
        if (!/^https?:\/\//i.test(url) && !/^mailto:/i.test(url)) {
            url = 'https://' + url;
        }
        restoreSelection();
        document.execCommand('createLink', false, url);
        editor.querySelectorAll('a').forEach((link) => {
            link.setAttribute('target', '_blank');
            link.setAttribute('rel', 'noopener');
        });
        syncHidden();
    });

    imageInput.addEventListener('change', async () => {
        const file = imageInput.files && imageInput.files[0];
        if (!file) return;
        const data = new FormData();
        data.append('_csrf_token', csrf);
        data.append('image', file);

        try {
            const response = await fetch('newsletter-image-upload.php', {
                method: 'POST',
                body: data,
                credentials: 'same-origin'
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'Upload non riuscito.');
            }
            restoreSelection();
            document.execCommand('insertImage', false, payload.url);
            const images = editor.querySelectorAll('img');
            const inserted = images[images.length - 1];
            if (inserted) {
                inserted.setAttribute('style', 'max-width:100%;height:auto;display:block;');
                inserted.setAttribute('alt', file.name.replace(/\.[^.]+$/, ''));
            }
            syncHidden();
        } catch (error) {
            alert(error.message || 'Upload non riuscito.');
        } finally {
            imageInput.value = '';
        }
    });

    htmlToggle.addEventListener('click', () => {
        if (!sourceMode) {
            source.value = editor.innerHTML;
            editor.style.display = 'none';
            source.style.display = 'block';
            htmlToggle.textContent = 'Visuale';
            sourceMode = true;
        } else {
            editor.innerHTML = source.value;
            source.style.display = 'none';
            editor.style.display = 'block';
            htmlToggle.textContent = 'HTML';
            sourceMode = false;
        }
        syncHidden();
    });

    previewButton.addEventListener('click', () => {
        syncHidden();
        const subject = document.getElementById('mail-subject').value || 'Email';
        previewFrame.srcdoc = '<!doctype html><html><head><meta charset="utf-8"><title>'
            + subject.replace(/[&<>"]/g, '')
            + '</title></head><body style="margin:0;background:#f4f4f4;font-family:Arial,sans-serif;color:#222;">'
            + '<div style="max-width:680px;margin:0 auto;background:#fff;padding:32px 28px;line-height:1.55">'
            + htmlBody.value + '</div></body></html>';
        preview.style.display = preview.style.display === 'block' ? 'none' : 'block';
    });

    form.addEventListener('submit', syncHidden);
    syncHidden();
})();
</script>
<?php admin_page_close(); ?>
