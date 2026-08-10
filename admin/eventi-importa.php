<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/event-import-v2.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

$sources = event_import_sources();
$preview = [];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $sourceKey = (string) ($_POST['source_key'] ?? '');

    try {
        $preview = event_import_fetch($sourceKey);

        if (($_POST['action'] ?? '') === 'stage') {
            $runId = event_import_stage($pdo, $sourceKey, $preview, admin_id());
            $success = count($preview)
                . ' candidati salvati nella coda di revisione (importazione #'
                . $runId
                . ').';
        }
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$candidates = [];

try {
    $candidates = $pdo->query(
        "SELECT *
         FROM event_import_candidates
         ORDER BY FIELD(review_status,'pending','approved','rejected','published'), created_at DESC
         LIMIT 100"
    )->fetchAll() ?: [];
} catch (Throwable $e) {
    if ($error === '') {
        $error = 'Applicare la migrazione migrations/20260806_ai_event_import.sql prima di usare la coda eventi.';
    }
}

admin_page_open('Importazione eventi', 'eventi');
?>

<main class="wrap">
    <div class="page-title">
        <h1>Importazione eventi</h1>
        <p>Scarica eventi esclusivamente da fonti configurate. I risultati entrano nel gestionale come bozze e richiedono revisione manuale.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="success"><?= e($success) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a class="btn secondary" href="eventi.php">Torna agli eventi</a>
        <a class="btn" href="evento-form.php">Nuovo evento manuale</a>
    </div>

    <section class="box">
        <h2>Fonte eventi</h2>
        <p class="hint">PromoTurismoFVG e Comune di Lauco sono attivi. L’import segue solo URL consentiti, gestisce i redirect in modo controllato e non pubblica mai automaticamente: ogni candidato passa dalla revisione.</p>

        <form method="post">
            <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">

            <p>
                <label for="source_key">Fonte</label>
                <select id="source_key" name="source_key">
                    <?php foreach ($sources as $key => $source): ?>
                        <option value="<?= e($key) ?>"<?= empty($source['enabled']) ? ' disabled' : '' ?>>
                            <?= e($source['name']) ?><?= empty($source['enabled']) ? ' — non attiva' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <div class="actions">
                <button class="btn secondary" type="submit" name="action" value="preview">Anteprima</button>
                <button class="btn" type="submit" name="action" value="stage">Scarica nella coda</button>
            </div>
        </form>
    </section>

    <div class="actions"></div>

    <?php if ($preview): ?>
        <section class="box">
            <h2>Anteprima (<?= count($preview) ?>)</h2>

            <table>
                <thead>
                    <tr>
                        <th>Titolo</th>
                        <th>Data</th>
                        <th>Località</th>
                        <th>Fonte</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($preview as $event): ?>
                        <tr>
                            <td><?= e($event['title']) ?></td>
                            <td><?= e($event['start_at_raw'] ?: '-') ?></td>
                            <td><?= e($event['locality'] ?: $event['location_name']) ?></td>
                            <td>
                                <a href="<?= e($event['source_url']) ?>" target="_blank" rel="noopener">Pagina ufficiale</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>

        <div class="actions"></div>
    <?php endif; ?>

    <section class="box">
        <h2>Coda di revisione</h2>

        <?php if (!$candidates): ?>
            <p class="hint">Nessun candidato presente.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Titolo</th>
                        <th>Data</th>
                        <th>Località</th>
                        <th>Fonte</th>
                        <th>Stato</th>
                        <th>Azione</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($candidates as $candidate): ?>
                        <tr>
                            <td><strong><?= e($candidate['title']) ?></strong></td>
                            <td><?= e($candidate['start_at_raw'] ?: '-') ?></td>
                            <td><?= e($candidate['locality'] ?: $candidate['location_name']) ?></td>
                            <td><?= e($candidate['source_key']) ?></td>
                            <td class="status"><?= e($candidate['review_status']) ?></td>
                            <td>
                                <a class="btn" href="evento-import-review.php?id=<?= (int) $candidate['id'] ?>">Revisiona</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>

<?php admin_page_close(); ?>
