<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/../inc/site-texts.php';
require_once __DIR__ . '/_admin_layout.php';

$repository = site_catalog_repository();
if (!$repository) {
    http_response_code(503);
    exit('Eseguire composer install prima di usare i cataloghi del sito.');
}

$error = '';
$notice = '';
$catalogs = $repository->loadAll();
$revision = $repository->revision($catalogs);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $action = (string) ($_POST['action'] ?? '');
        if ($action === 'save') {
            if (!hash_equals($revision, (string) ($_POST['revision'] ?? ''))) {
                throw new RuntimeException('I testi sono cambiati in un’altra sessione. Ricaricare la pagina prima di salvare.');
            }
            $posted = json_decode((string) ($_POST['catalog_payload'] ?? ''), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($posted)) {
                throw new RuntimeException('Cataloghi inviati in un formato non valido.');
            }
            $updated = [];
            foreach (array_keys(content_supported_languages()) as $locale) {
                $values = is_array($posted[$locale] ?? null) ? $posted[$locale] : [];
                $updated[$locale] = [];
                foreach (array_keys($catalogs['it']) as $key) {
                    $updated[$locale][$key] = trim((string) ($values[$key] ?? ''));
                }
            }
            $repository->saveAll($updated);
            $catalogs = $repository->loadAll();
            $revision = $repository->revision($catalogs);
            $notice = 'Cataloghi salvati. Le nuove versioni sono già disponibili sul sito.';
        } elseif ($action === 'ai') {
            $service = site_text_translation_service($pdo);
            $draftId = $service->generate(admin_id());
            header('Location: testi-sito-preview.php?id=' . $draftId);
            exit;
        } else {
            throw new RuntimeException('Azione non valida.');
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$drafts = [];
try {
    $drafts = $pdo->query('SELECT id, source_revision, model, status, created_at FROM site_text_translation_drafts ORDER BY created_at DESC, id DESC LIMIT 20')->fetchAll() ?: [];
} catch (Throwable $e) {
    $drafts = [];
}
$openAiConfigured = trim((string) lauco_env('OPENAI_API_KEY', '')) !== ''
    && trim((string) lauco_env('OPENAI_MODEL', '')) !== '';

admin_page_open('Testi sito', 'testi-sito');
?>

<style>
    .catalog-table { min-width:1180px; }
    .catalog-wrap { overflow:auto; }
    .catalog-key { width:180px; font-family:Consolas,monospace; font-size:12px; }
    .catalog-input { width:100%; min-width:230px; min-height:76px; resize:vertical; padding:9px; border:1px solid var(--admin-border); font:13px/1.4 Arial,sans-serif; }
    .catalog-source { background:#fafafa; }
    .sticky-actions { position:sticky; bottom:0; background:#fff; box-shadow:0 -8px 24px rgba(0,0,0,.08); padding:14px; z-index:3; }
    .catalog-filter { width:min(560px,100%); padding:11px 12px; border:1px solid var(--admin-border); margin-bottom:14px; }
</style>

<main class="wrap">
    <div class="page-title">
        <h1>Testi statici del sito</h1>
        <p>Tutti i testi non gestiti da itinerari, eventi, luoghi, galleria o slider sono raccolti nei cataloghi JSON IT / EN / DE / SL.</p>
    </div>

    <?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($notice !== ''): ?><div class="success"><?= e($notice) ?></div><?php endif; ?>

    <section class="admin-card" style="margin-bottom:22px;">
        <h2>Traduzione assistita</h2>
        <p>OpenAI usa il catalogo italiano come fonte e prepara contemporaneamente inglese, tedesco e sloveno. Il risultato passa sempre da una preview e non viene applicato automaticamente.</p>
        <form method="post">
            <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
            <button class="btn" type="submit" name="action" value="ai"<?= !$openAiConfigured ? ' disabled' : '' ?>>Genera preview AI completa</button>
            <?php if (!$openAiConfigured): ?><span class="hint">Configurare OPENAI_API_KEY e OPENAI_MODEL in .env.</span><?php endif; ?>
        </form>
    </section>

    <form method="post" id="catalog-form">
        <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="revision" value="<?= e($revision) ?>">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="catalog_payload" id="catalog-payload" value="">
        <label for="catalog-filter"><strong>Filtra testi o chiavi</strong></label><br>
        <input class="catalog-filter" id="catalog-filter" type="search" placeholder="Es. sicurezza, menu, contatti…">
        <div class="catalog-wrap">
            <table class="catalog-table">
                <thead><tr><th>Chiave</th><th>Italiano</th><th>English</th><th>Deutsch</th><th>Slovenščina</th></tr></thead>
                <tbody>
                <?php foreach (array_keys($catalogs['it']) as $key): ?>
                    <tr data-catalog-row data-search="<?= e(strtolower($key . ' ' . implode(' ', array_map(static fn (string $locale): string => (string) ($catalogs[$locale][$key] ?? ''), ['it','en','de','sl'])))) ?>">
                        <td class="catalog-key"><?= e($key) ?></td>
                        <?php foreach (['it','en','de','sl'] as $locale): ?>
                            <td><textarea class="catalog-input<?= $locale === 'it' ? ' catalog-source' : '' ?>" data-locale="<?= e($locale) ?>" data-key="<?= e($key) ?>" lang="<?= e($locale) ?>" required><?= e($catalogs[$locale][$key] ?? '') ?></textarea></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="sticky-actions"><button class="btn" type="submit">Salva tutti i cataloghi</button></div>
    </form>

    <section class="admin-card" style="margin-top:24px;">
        <h2>Preview precedenti</h2>
        <?php if (!$drafts): ?>
            <p class="hint">Nessuna preview disponibile. Applicare prima le migrazioni aggiornate.</p>
        <?php else: ?>
            <table><thead><tr><th>Data</th><th>Modello</th><th>Stato</th><th></th></tr></thead><tbody>
            <?php foreach ($drafts as $draft): ?>
                <tr><td><?= e($draft['created_at']) ?></td><td><?= e($draft['model'] ?: '-') ?></td><td class="status"><?= e($draft['status']) ?></td><td><a class="btn" href="testi-sito-preview.php?id=<?= (int) $draft['id'] ?>">Apri</a></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        <?php endif; ?>
    </section>
</main>

<script>
(() => {
    const form = document.getElementById('catalog-form');
    const payload = document.getElementById('catalog-payload');
    const filter = document.getElementById('catalog-filter');
    form.addEventListener('submit', () => {
        const catalogs = {it: {}, en: {}, de: {}, sl: {}};
        form.querySelectorAll('textarea[data-locale][data-key]').forEach((field) => {
            catalogs[field.dataset.locale][field.dataset.key] = field.value.trim();
        });
        payload.value = JSON.stringify(catalogs);
    });
    filter.addEventListener('input', () => {
        const needle = filter.value.trim().toLocaleLowerCase('it');
        document.querySelectorAll('[data-catalog-row]').forEach((row) => {
            row.hidden = needle !== '' && !row.dataset.search.includes(needle);
        });
    });
})();
</script>

<?php admin_page_close(); ?>
