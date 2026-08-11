<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/../inc/backoffice-mail.php';
require_once __DIR__ . '/_admin_layout.php';
require_once __DIR__ . '/_mail_ui.php';

$requestedFolder = trim((string) ($_GET['folder'] ?? 'INBOX')) ?: 'INBOX';
$search = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 160);
$page = max(1, (int) ($_GET['page'] ?? 1));
$folders = [];
$messages = [];
$folder = null;
$paginator = null;
$error = '';

try {
    $client = backoffice_mail_client();
    $folders = backoffice_mail_folders($client);
    $folder = backoffice_mail_folder($requestedFolder, $client);
    $query = $folder->query()
        ->setFetchBody(false)
        ->setFetchOrderDesc()
        ->leaveUnread();
    $search !== '' ? $query->whereText($search) : $query->all();
    $paginator = $query->paginate(25, $page, 'page');
    foreach ($paginator as $message) {
        $messages[] = backoffice_mail_message_data($message);
    }
} catch (Throwable $exception) {
    $error = backoffice_mail_user_error($exception);
}

$selectedFolder = $folder?->path ?? $requestedFolder;
$sent = isset($_GET['sent']);
if ($error === '' && isset($_GET['error'])) {
    $error = 'Non è stato possibile aggiornare il messaggio.';
}

admin_page_open('Posta', 'posta');
admin_mail_styles();
?>
<main class="wrap">
    <section class="hero-admin">
        <h1>Posta</h1>
        <p>Leggi e invia le email di info@laucoexperience.it senza uscire dal backoffice.</p>
    </section>

    <?php if ($sent): ?>
        <div class="success">Email inviata correttamente.</div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="mail-shell">
        <?php admin_mail_sidebar($folders, $selectedFolder); ?>

        <section class="mail-panel">
            <div class="mail-toolbar">
                <form method="get" action="posta.php">
                    <input type="hidden" name="folder" value="<?= e($selectedFolder) ?>">
                    <input type="search" name="q" value="<?= e($search) ?>" placeholder="Cerca nella cartella" aria-label="Cerca email">
                    <button class="btn" type="submit">Cerca</button>
                </form>
                <a class="btn secondary" href="<?= e(admin_mail_folder_url($selectedFolder, $search !== '' ? ['q' => $search] : [])) ?>">Aggiorna</a>
            </div>

            <?php if ($error === '' && $messages === []): ?>
                <div class="mail-empty"><?= $search !== '' ? 'Nessuna email corrisponde alla ricerca.' : 'La cartella è vuota.' ?></div>
            <?php endif; ?>

            <?php if ($messages !== []): ?>
                <div class="mail-list">
                    <?php foreach ($messages as $message): ?>
                        <a class="mail-row<?= $message['unread'] ? ' unread' : '' ?>" href="posta-messaggio.php?<?= e(http_build_query(['folder' => $selectedFolder, 'uid' => $message['uid']])) ?>">
                            <span class="mail-sender"><?= e($message['from_label']) ?></span>
                            <span class="mail-subject">
                                <?= e($message['subject']) ?>
                                <span class="mail-icons"><?= $message['flagged'] ? '★' : '' ?><?= $message['has_attachments'] ? ' 📎' : '' ?></span>
                            </span>
                            <time class="mail-date"><?= e(admin_mail_date($message['date'])) ?></time>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($paginator && $paginator->lastPage() > 1): ?>
                <nav class="mail-pagination" aria-label="Paginazione email">
                    <span>Pagina <?= (int) $paginator->currentPage() ?> di <?= (int) $paginator->lastPage() ?></span>
                    <div class="actions" style="margin:0">
                        <?php if ($paginator->currentPage() > 1): ?>
                            <a class="btn secondary" href="<?= e(admin_mail_folder_url($selectedFolder, array_filter(['q' => $search, 'page' => $paginator->currentPage() - 1]))) ?>">Precedente</a>
                        <?php endif; ?>
                        <?php if ($paginator->currentPage() < $paginator->lastPage()): ?>
                            <a class="btn secondary" href="<?= e(admin_mail_folder_url($selectedFolder, array_filter(['q' => $search, 'page' => $paginator->currentPage() + 1]))) ?>">Successiva</a>
                        <?php endif; ?>
                    </div>
                </nav>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php admin_page_close(); ?>
