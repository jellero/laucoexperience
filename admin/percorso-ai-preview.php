<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/../inc/content-ai.php';
require_once __DIR__ . '/_admin_layout.php';

$batchId = (int) ($_GET['batch'] ?? $_POST['batch'] ?? 0);
if ($batchId <= 0) {
    http_response_code(404);
    exit('Preview non trovata.');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        content_ai_review_batch($pdo, $batchId, (string) ($_POST['action'] ?? ''), admin_id());
        header('Location: percorso-ai-preview.php?batch=' . $batchId);
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

try {
    $batch = content_ai_find_batch($pdo, $batchId);
} catch (Throwable $e) {
    http_response_code(404);
    exit(e($e->getMessage()));
}

$languageNames = content_supported_languages();
$canReview = !in_array((string) $batch['status'], ['rejected', 'applied'], true);
$entityType = (string) $batch['entity_type'];
$backUrl = $entityType === 'percorso'
    ? 'percorso-ai-demo.php?id=' . (int) $batch['entity_id']
    : 'traduzioni-contenuti.php';
$publicUrl = match ($entityType) {
    'percorso' => '../percorso.php?slug=' . urlencode((string) $batch['entity_slug']),
    'evento' => '../evento.php?slug=' . urlencode((string) $batch['entity_slug']),
    'luogo' => '../luogo.php?slug=' . urlencode((string) $batch['entity_slug']),
    default => '../index.php',
};
admin_page_open('Preview AI multilingua', $entityType === 'percorso' ? 'percorsi' : 'traduzioni');
?>

<style>
    .locale-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:20px; }
    .locale-card { background:#fff; box-shadow:var(--admin-shadow); padding:24px; min-width:0; }
    .locale-card h2 { margin:0 0 6px; }
    .locale-meta { color:var(--admin-muted); margin-bottom:18px; }
    .locale-field { border-top:1px solid var(--admin-border); padding:14px 0 0; margin-top:14px; }
    .locale-field strong { display:block; margin-bottom:6px; }
    .locale-body { white-space:pre-wrap; line-height:1.6; }
    .locale-warning { background:#fff3cd; color:#664d03; padding:10px 12px; margin-top:10px; }
    @media (max-width:900px) { .locale-grid { grid-template-columns:1fr; } }
</style>

<main class="wrap">
    <div class="page-title">
        <h1>Preview AI in quattro lingue</h1>
        <p><?= e($batch['entity_title'] ?: ucfirst($entityType)) ?> · confronto coordinato IT / EN / DE / SL prima della pubblicazione.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a class="btn secondary" href="<?= e($backUrl) ?>">Torna alla generazione</a>
        <a class="btn secondary" target="_blank" href="<?= e($publicUrl) ?>">Vedi pubblico</a>
    </div>

    <section class="admin-card" style="margin-bottom:22px;">
        <strong>Stato batch:</strong> <?= e((string) $batch['status']) ?>
        <span style="margin-left:16px;"><strong>Modello:</strong> <?= e((string) ($batch['model'] ?: '-')) ?></span>
        <?php if ($canReview): ?>
            <form method="post" class="actions" style="margin-top:18px;margin-bottom:0;">
                <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="batch" value="<?= $batchId ?>">
                <button class="btn secondary" type="submit" name="action" value="approve">Approva tutte</button>
                <button class="btn" type="submit" name="action" value="apply" onclick="return confirm('Applicare e pubblicare tutte le quattro versioni?')">Applica tutte</button>
                <button class="btn danger" type="submit" name="action" value="reject" onclick="return confirm('Rifiutare l’intera preview?')">Rifiuta tutte</button>
            </form>
        <?php endif; ?>
    </section>

    <section class="locale-grid">
        <?php foreach ($languageNames as $language => $languageName): ?>
            <?php $draft = $batch['drafts'][$language] ?? null; ?>
            <article class="locale-card" lang="<?= e($language) ?>">
                <h2><?= e(strtoupper($language)) ?> · <?= e($languageName) ?></h2>
                <?php if (!$draft): ?>
                    <p class="error">Bozza mancante per questa lingua.</p>
                    <?php continue; ?>
                <?php endif; ?>
                <?php $generated = $draft['generated']; ?>
                <div class="locale-meta">Stato: <?= e((string) $draft['status']) ?></div>
                <div class="locale-field"><strong>Titolo</strong><?= e((string) ($generated['title'] ?? '')) ?></div>
                <div class="locale-field"><strong>Sottotitolo</strong><?= e((string) ($generated['subtitle'] ?? '')) ?></div>
                <div class="locale-field"><strong>Excerpt</strong><?= e((string) ($generated['excerpt'] ?? '')) ?></div>
                <div class="locale-field"><strong>Descrizione</strong><div class="locale-body"><?= e((string) ($generated['description'] ?? '')) ?></div></div>
                <div class="locale-field"><strong>SEO title</strong><?= e((string) ($generated['seo_title'] ?? '')) ?></div>
                <div class="locale-field"><strong>SEO description</strong><?= e((string) ($generated['seo_description'] ?? '')) ?></div>
                <?php foreach (($generated['warnings'] ?? []) as $warning): ?>
                    <div class="locale-warning"><?= e((string) $warning) ?></div>
                <?php endforeach; ?>
                <?php if ($entityType === 'percorso'): ?>
                    <div class="actions" style="margin-top:18px;margin-bottom:0;">
                        <a class="btn secondary" href="percorso-ai-review.php?id=<?= (int) $draft['id'] ?>">Revisiona singolarmente</a>
                    </div>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </section>
</main>

<?php admin_page_close(); ?>
