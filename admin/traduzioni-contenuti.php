<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/../inc/content-ai.php';
require_once __DIR__ . '/_admin_layout.php';

$entityLabels = [
    'percorso' => 'Percorsi',
    'evento' => 'Eventi',
    'luogo' => 'Luoghi',
    'galleria' => 'Galleria',
    'slider' => 'Slider home',
];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $entityType = (string) ($_POST['entity_type'] ?? '');
        $entityId = (int) ($_POST['entity_id'] ?? 0);
        $mode = (string) ($_POST['mode'] ?? 'full');
        if (!isset($entityLabels[$entityType]) || $entityId <= 0) {
            throw new RuntimeException('Contenuto non valido.');
        }
        $result = content_ai_generate_entity_bundle($pdo, $entityType, $entityId, $mode, admin_id());
        header('Location: percorso-ai-preview.php?batch=' . (int) $result['batch_id']);
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$groups = [];
foreach ($entityLabels as $entityType => $label) {
    $config = content_ai_entity_config($entityType);
    if (!$config) {
        continue;
    }
    $titleColumn = $config['title'];
    $rows = $pdo->query(
        'SELECT id, ' . $titleColumn . ' AS entity_title FROM ' . $config['table'] . ' ORDER BY ' . $titleColumn . ' ASC, id ASC'
    )->fetchAll() ?: [];
    $translationStmt = $pdo->prepare(
        "SELECT language FROM content_translations
         WHERE entity_type = :entity_type AND entity_id = :entity_id AND status = 'published'"
    );
    foreach ($rows as &$row) {
        $translationStmt->execute(['entity_type' => $entityType, 'entity_id' => (int) $row['id']]);
        $row['languages'] = array_values(array_filter(array_map('strval', $translationStmt->fetchAll(PDO::FETCH_COLUMN) ?: [])));
    }
    unset($row);
    $groups[$entityType] = ['label' => $label, 'rows' => $rows];
}

admin_page_open('Traduzioni contenuti', 'traduzioni');
?>

<style>
    .translation-group { margin-bottom:26px; }
    .language-state { display:flex; gap:6px; flex-wrap:wrap; }
    .language-pill { display:inline-block; padding:5px 8px; border:1px solid var(--admin-border); font-size:11px; background:#fff; }
    .language-pill.done { border-color:#b7dfc2; background:#eaf7ed; color:#0f6b2c; }
    .translation-form { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin:0; }
    .translation-form select { padding:9px; border:1px solid var(--admin-border); background:#fff; }
</style>

<main class="wrap">
    <div class="page-title">
        <h1>Traduzioni dei contenuti</h1>
        <p>Genera una sola preview coordinata in italiano, inglese, tedesco e sloveno. Nulla viene pubblicato prima dell’approvazione.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php foreach ($groups as $entityType => $group): ?>
        <section class="admin-card translation-group">
            <h2><?= e($group['label']) ?></h2>
            <table>
                <thead>
                <tr><th>Contenuto</th><th>Copertura pubblicata</th><th>Generazione assistita</th></tr>
                </thead>
                <tbody>
                <?php if ($group['rows'] === []): ?>
                    <tr><td colspan="3">Nessun contenuto presente.</td></tr>
                <?php endif; ?>
                <?php foreach ($group['rows'] as $row): ?>
                    <tr>
                        <td><strong><?= e((string) ($row['entity_title'] ?: '#' . $row['id'])) ?></strong></td>
                        <td>
                            <div class="language-state">
                                <span class="language-pill done">IT</span>
                                <?php foreach (['en', 'de', 'sl'] as $language): ?>
                                    <span class="language-pill<?= in_array($language, $row['languages'], true) ? ' done' : '' ?>"><?= e(strtoupper($language)) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td>
                            <form method="post" class="translation-form">
                                <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="entity_type" value="<?= e($entityType) ?>">
                                <input type="hidden" name="entity_id" value="<?= (int) $row['id'] ?>">
                                <select name="mode" aria-label="Modalità di generazione">
                                    <option value="full">Testo completo + SEO</option>
                                    <option value="translate">Traduzione fedele</option>
                                    <option value="editorial">Revisione editoriale</option>
                                    <option value="seo">Ottimizzazione SEO</option>
                                </select>
                                <button class="btn" type="submit">Genera IT / EN / DE / SL</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endforeach; ?>
</main>

<?php admin_page_close(); ?>
