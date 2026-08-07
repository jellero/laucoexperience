<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/../inc/site-texts.php';
require_once __DIR__ . '/_admin_layout.php';

$draftId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
if ($draftId <= 0) {
    http_response_code(404);
    exit('Preview non trovata.');
}

$error = '';
try {
    $service = site_text_translation_service($pdo);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'apply') {
            $service->apply($draftId, admin_id());
        } elseif ($action === 'reject') {
            $service->reject($draftId, admin_id());
        } else {
            throw new RuntimeException('Azione non valida.');
        }
        header('Location: testi-sito-preview.php?id=' . $draftId);
        exit;
    }
    $draft = $service->find($draftId);
} catch (Throwable $e) {
    $error = $e->getMessage();
    $draft = $draft ?? null;
}

admin_page_open('Preview traduzioni sito', 'testi-sito');
?>

<style>
    .preview-wrap { overflow:auto; }
    .preview-table { min-width:1100px; }
    .preview-table td { width:25%; white-space:pre-wrap; }
    .preview-key { font-family:Consolas,monospace; font-size:12px; }
</style>

<main class="wrap">
    <div class="page-title">
        <h1>Preview traduzioni statiche</h1>
        <p>Confronto completo prima di scrivere i cataloghi JSON runtime.</p>
    </div>
    <?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($draft): ?>
        <div class="actions"><a class="btn secondary" href="testi-sito.php">Torna ai testi</a></div>
        <section class="admin-card" style="margin-bottom:22px;">
            <strong>Stato:</strong> <?= e((string) $draft['status']) ?> · <strong>Modello:</strong> <?= e((string) ($draft['model'] ?: '-')) ?>
            <?php if ((string) $draft['status'] === 'review'): ?>
                <form method="post" class="actions" style="margin-top:18px;margin-bottom:0;">
                    <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= $draftId ?>">
                    <button class="btn" type="submit" name="action" value="apply" onclick="return confirm('Applicare i cataloghi EN, DE e SL?')">Applica cataloghi</button>
                    <button class="btn danger" type="submit" name="action" value="reject">Rifiuta preview</button>
                </form>
            <?php endif; ?>
        </section>
        <div class="preview-wrap">
            <table class="preview-table">
                <thead><tr><th>Chiave / IT</th><th>English</th><th>Deutsch</th><th>Slovenščina</th></tr></thead>
                <tbody>
                <?php foreach ($draft['source'] as $key => $italian): ?>
                    <tr>
                        <td><div class="preview-key"><?= e((string) $key) ?></div><?= e((string) $italian) ?></td>
                        <td lang="en"><?= e((string) ($draft['generated']['en'][$key] ?? '')) ?></td>
                        <td lang="de"><?= e((string) ($draft['generated']['de'][$key] ?? '')) ?></td>
                        <td lang="sl"><?= e((string) ($draft['generated']['sl'][$key] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php admin_page_close(); ?>
