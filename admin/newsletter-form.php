<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/../inc/newsletter.php';
require_once __DIR__ . '/_admin_layout.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : (int) ($_POST['id'] ?? 0);
$error = '';
$success = isset($_GET['saved']) ? 'Bozza salvata.' : '';
if (isset($_GET['sent'])) {
    $success = 'Newsletter inviata.';
}

$campaign = [
    'id' => 0,
    'subject' => '',
    'preheader' => '',
    'html_body' => '<h1>Lauco Experience</h1><p>Scrivi qui il contenuto della newsletter.</p>',
    'status' => 'draft',
    'sent_at' => null,
    'sent_count' => 0,
    'failed_count' => 0,
];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM newsletter_campaigns WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $loaded = $stmt->fetch();
    if (!$loaded) {
        http_response_code(404);
        exit('Newsletter non trovata.');
    }
    $campaign = array_merge($campaign, $loaded);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $action = (string) ($_POST['action'] ?? 'save');
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $preheader = trim((string) ($_POST['preheader'] ?? ''));
        $htmlBody = newsletter_sanitize_editor_html((string) ($_POST['html_body'] ?? ''));

        if ((string) ($campaign['status'] ?? 'draft') === 'sent') {
            throw new RuntimeException('Una newsletter già inviata è in sola lettura.');
        }
        if ($subject === '') {
            throw new RuntimeException('Inserisci l’oggetto della newsletter.');
        }
        if (mb_strlen($subject) > 190) {
            throw new RuntimeException('L’oggetto non può superare 190 caratteri.');
        }
        if (mb_strlen($preheader) > 255) {
            throw new RuntimeException('Il preheader non può superare 255 caratteri.');
        }
        if ($htmlBody === '') {
            throw new RuntimeException('Inserisci il contenuto HTML della newsletter.');
        }
        if (strlen($htmlBody) > 2_000_000) {
            throw new RuntimeException('Il contenuto HTML è troppo grande.');
        }

        if ($id > 0) {
            $stmt = $pdo->prepare(
                'UPDATE newsletter_campaigns SET subject = :subject, preheader = :preheader, '
                . "html_body = :html_body, status = 'draft' WHERE id = :id"
            );
            $stmt->execute([
                'subject' => $subject,
                'preheader' => $preheader !== '' ? $preheader : null,
                'html_body' => $htmlBody,
                'id' => $id,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO newsletter_campaigns (subject, preheader, html_body, status, created_by) '
                . "VALUES (:subject, :preheader, :html_body, 'draft', :created_by)"
            );
            $stmt->execute([
                'subject' => $subject,
                'preheader' => $preheader !== '' ? $preheader : null,
                'html_body' => $htmlBody,
                'created_by' => admin_id() ?: null,
            ]);
            $id = (int) $pdo->lastInsertId();
        }

        if ($action === 'send') {
            $stmt = $pdo->prepare('SELECT * FROM newsletter_campaigns WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $toSend = $stmt->fetch();
            if (!$toSend) {
                throw new RuntimeException('Newsletter non trovata dopo il salvataggio.');
            }

            $result = newsletter_send_campaign($pdo, $toSend);
            $status = $result['sent'] > 0 ? 'sent' : 'failed';
            $update = $pdo->prepare(
                'UPDATE newsletter_campaigns SET status = :status, sent_at = CURRENT_TIMESTAMP, '
                . 'sent_count = :sent_count, failed_count = :failed_count WHERE id = :id'
            );
            $update->execute([
                'status' => $status,
                'sent_count' => $result['sent'],
                'failed_count' => $result['failed'],
                'id' => $id,
            ]);

            if ($status !== 'sent') {
                throw new RuntimeException('Nessuna email è stata inviata. Controlla la configurazione mail del server.');
            }

            header('Location: newsletter-form.php?id=' . $id . '&sent=1');
            exit;
        }

        header('Location: newsletter-form.php?id=' . $id . '&saved=1');
        exit;
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
        $campaign = array_merge($campaign, [
            'id' => $id,
            'subject' => (string) ($_POST['subject'] ?? ''),
            'preheader' => (string) ($_POST['preheader'] ?? ''),
            'html_body' => (string) ($_POST['html_body'] ?? ''),
        ]);
    }
}

$isLocked = (string) ($campaign['status'] ?? 'draft') === 'sent';
$activeSubscribers = 0;
try {
    $activeSubscribers = (int) $pdo->query(
        "SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'active'"
    )->fetchColumn();
} catch (Throwable) {
}

admin_page_open($id > 0 ? 'Newsletter' : 'Nuova newsletter', 'newsletter');
?>

<style>
.newsletter-editor-box{background:#fff;box-shadow:var(--admin-shadow);padding:26px}
.newsletter-toolbar{display:flex;gap:6px;flex-wrap:wrap;padding:10px;background:#f3f3f3;border:1px solid #ddd;border-bottom:0}
.newsletter-toolbar button,.newsletter-toolbar label{width:auto;margin:0;padding:8px 10px;border:1px solid #ccc;background:#fff;cursor:pointer;font-weight:700;font-size:13px}
.newsletter-toolbar button:hover,.newsletter-toolbar label:hover{background:#222;color:#fff;border-color:#222}
.newsletter-editor{min-height:440px;border:1px solid #ddd;padding:28px;outline:none;background:#fff;line-height:1.55;overflow-wrap:anywhere}
.newsletter-editor img{max-width:100%;height:auto}
.newsletter-source{display:none;min-height:440px;font-family:ui-monospace,SFMono-Regular,Menlo,monospace;font-size:13px}
.newsletter-preview{display:none;margin-top:20px}
.newsletter-preview iframe{width:100%;height:620px;border:1px solid #ddd;background:#fff}
.newsletter-meta{display:grid;grid-template-columns:1fr 1fr;gap:18px;margin-bottom:20px}
.newsletter-meta .full{grid-column:1/-1}
.newsletter-status{background:#f7f7f7;border:1px solid #e4e4e4;padding:14px 16px;margin-bottom:20px;line-height:1.5}
.newsletter-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:22px}
.newsletter-image-input{position:absolute;left:-9999px;width:1px!important;height:1px}
@media(max-width:760px){.newsletter-meta{grid-template-columns:1fr}.newsletter-meta .full{grid-column:auto}.newsletter-editor{padding:18px}}
</style>

<main class="wrap">
    <section class="hero-admin">
        <h1><?= $id > 0 ? 'Newsletter' : 'Nuova newsletter' ?></h1>
        <p>Componi email HTML, inserisci immagini e invia agli iscritti attivi.</p>
    </section>

    <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?= e($success) ?></div><?php endif; ?>

    <div class="actions">
        <a class="btn secondary" href="newsletter.php">Torna alla newsletter</a>
    </div>

    <form method="post" id="newsletterForm">
        <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int) ($campaign['id'] ?? 0) ?>">
        <input type="hidden" name="action" id="formAction" value="save">
        <textarea name="html_body" id="htmlBody" hidden><?= e((string) $campaign['html_body']) ?></textarea>

        <section class="newsletter-editor-box">
            <div class="newsletter-status">
                <strong>Stato:</strong> <?= e((string) $campaign['status']) ?> ·
                <strong>Iscritti attivi:</strong> <?= $activeSubscribers ?>
                <?php if (!empty($campaign['sent_at'])): ?>
                    · <strong>Inviata:</strong> <?= e(date('d/m/Y H:i', strtotime((string) $campaign['sent_at']))) ?>
                    · <?= (int) $campaign['sent_count'] ?> consegne tentate con successo
                    · <?= (int) $campaign['failed_count'] ?> errori
                <?php endif; ?>
            </div>

            <div class="newsletter-meta">
                <div class="full">
                    <label for="subject">Oggetto</label>
                    <input type="text" id="subject" name="subject" maxlength="190" required
                           value="<?= e((string) $campaign['subject']) ?>" <?= $isLocked ? 'readonly' : '' ?>>
                </div>
                <div class="full">
                    <label for="preheader">Preheader</label>
                    <input type="text" id="preheader" name="preheader" maxlength="255"
                           placeholder="Testo di anteprima mostrato da molti client email"
                           value="<?= e((string) $campaign['preheader']) ?>" <?= $isLocked ? 'readonly' : '' ?>>
                </div>
            </div>

            <?php if (!$isLocked): ?>
                <div class="newsletter-toolbar" id="toolbar" aria-label="Strumenti editor">
                    <button type="button" data-command="bold"><strong>B</strong></button>
                    <button type="button" data-command="italic"><em>I</em></button>
                    <button type="button" data-command="underline"><u>U</u></button>
                    <button type="button" data-block="h1">H1</button>
                    <button type="button" data-block="h2">H2</button>
                    <button type="button" data-block="p">Paragrafo</button>
                    <button type="button" data-command="insertUnorderedList">Elenco</button>
                    <button type="button" id="linkButton">Link</button>
                    <label for="newsletterImage">Immagine</label>
                    <input class="newsletter-image-input" type="file" id="newsletterImage" accept="image/jpeg,image/png,image/webp,image/gif">
                    <button type="button" id="htmlToggle">HTML</button>
                    <button type="button" id="previewButton">Anteprima</button>
                </div>
            <?php endif; ?>

            <div id="editor" class="newsletter-editor" contenteditable="<?= $isLocked ? 'false' : 'true' ?>"><?= (string) $campaign['html_body'] ?></div>
            <textarea id="sourceEditor" class="newsletter-source" <?= $isLocked ? 'readonly' : '' ?>></textarea>

            <div id="preview" class="newsletter-preview">
                <iframe id="previewFrame" title="Anteprima newsletter" sandbox=""></iframe>
            </div>

            <?php if (!$isLocked): ?>
                <div class="newsletter-actions">
                    <button class="btn" type="submit" data-submit-action="save">Salva bozza</button>
                    <button class="btn" type="submit" data-submit-action="send"
                            <?= $activeSubscribers <= 0 ? 'disabled title="Nessun iscritto attivo"' : '' ?>>
                        Invia a <?= $activeSubscribers ?> iscritti
                    </button>
                </div>
                <p class="hint">L’invio viene eseguito singolarmente per non esporre gli indirizzi email degli iscritti.</p>
            <?php endif; ?>
        </section>
    </form>
</main>

<script>
(function () {
    const form = document.getElementById('newsletterForm');
    const editor = document.getElementById('editor');
    const htmlBody = document.getElementById('htmlBody');
    const source = document.getElementById('sourceEditor');
    const action = document.getElementById('formAction');
    const toolbar = document.getElementById('toolbar');
    const imageInput = document.getElementById('newsletterImage');
    const htmlToggle = document.getElementById('htmlToggle');
    const previewButton = document.getElementById('previewButton');
    const preview = document.getElementById('preview');
    const previewFrame = document.getElementById('previewFrame');
    const linkButton = document.getElementById('linkButton');
    const csrf = <?= json_encode(csrf_token(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    const locked = <?= $isLocked ? 'true' : 'false' ?>;
    let sourceMode = false;
    let lastRange = null;

    if (!editor || locked) {
        return;
    }

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
        const url = window.prompt('URL del link (https://...)');
        if (!url) return;
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
        const subject = document.getElementById('subject').value || 'Newsletter';
        const preheader = document.getElementById('preheader').value || '';
        const body = htmlBody.value;
        const documentHtml = '<!doctype html><html><head><meta charset="utf-8"><title>'
            + subject.replace(/[&<>"]/g, '')
            + '</title></head><body style="margin:0;background:#f4f4f4;font-family:Arial,sans-serif;color:#222;">'
            + (preheader ? '<div style="display:none;max-height:0;overflow:hidden;">' + preheader.replace(/[&<>"]/g, '') + '</div>' : '')
            + '<div style="max-width:680px;margin:0 auto;background:#fff;padding:32px 28px;">'
            + body + '</div></body></html>';
        previewFrame.srcdoc = documentHtml;
        preview.style.display = preview.style.display === 'block' ? 'none' : 'block';
    });

    form.querySelectorAll('[data-submit-action]').forEach((button) => {
        button.addEventListener('click', (event) => {
            action.value = button.dataset.submitAction;
            if (action.value === 'send') {
                const count = <?= $activeSubscribers ?>;
                if (!window.confirm('Inviare ora questa newsletter a ' + count + ' iscritti attivi?')) {
                    event.preventDefault();
                }
            }
        });
    });

    form.addEventListener('submit', () => {
        syncHidden();
    });

    syncHidden();
})();
</script>

<?php admin_page_close(); ?>
