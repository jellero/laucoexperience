<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/../inc/sentieri.php';
require_once __DIR__ . '/_admin_layout.php';

$error = '';
$success = (string) ($_SESSION['sentieri_flash'] ?? '');
unset($_SESSION['sentieri_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $uploadedPath = null;
    try {
        if ($action === 'upload') {
            $uploadedPath = sentieri_store_gpx($_FILES['gpx_file'] ?? []);
            sentieri_sync_gpx_directory($pdo, admin_id());
            $_SESSION['sentieri_flash'] = 'File GPX caricato nella cartella /gpx e aggiunto all’elenco.';
        } elseif ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $status = (string) ($_POST['stato'] ?? 'in_verifica');
            if ($id < 1 || !array_key_exists($status, sentieri_statuses())) {
                throw new RuntimeException('Dati del sentiero non validi.');
            }
            $checkedAt = sentieri_normalize_datetime((string) ($_POST['ultima_verifica_at'] ?? ''));
            $note = trim((string) ($_POST['nota_pubblica'] ?? '')) ?: null;
            $stmt = $pdo->prepare("SELECT * FROM sentieri WHERE id=:id AND gpx_file LIKE 'gpx/%' LIMIT 1");
            $stmt->execute(['id' => $id]);
            $old = $stmt->fetch();
            if (!$old || !is_file(dirname(__DIR__) . '/' . (string) $old['gpx_file'])) {
                throw new RuntimeException('Il file GPX non è più presente nella cartella. Aggiorna l’elenco.');
            }
            $code = sentieri_code_from_filename(basename((string) $old['gpx_file']));

            $pdo->beginTransaction();
            $pdo->prepare('UPDATE sentieri SET nome=:name_code,codice=:trail_code,stato=:stato,nota_pubblica=:nota,ultima_verifica_at=:checked,pubblicato=:pubblicato,updated_by=:admin WHERE id=:id')->execute([
                'name_code' => $code,
                'trail_code' => $code,
                'stato' => $status,
                'nota' => $note,
                'checked' => $checkedAt,
                'pubblicato' => isset($_POST['pubblicato']) ? 1 : 0,
                'admin' => admin_id(),
                'id' => $id,
            ]);
            $changed = $checkedAt !== null && (
                (string) $old['stato'] !== $status
                || (string) ($old['ultima_verifica_at'] ?? '') !== $checkedAt
                || (string) ($old['nota_pubblica'] ?? '') !== (string) ($note ?? '')
            );
            if ($changed) {
                $pdo->prepare('INSERT INTO sentieri_verifiche (sentiero_id,stato,nota,verificato_at,created_by) VALUES (:sentiero,:stato,:nota,:checked,:admin)')->execute([
                    'sentiero' => $id,
                    'stato' => $status,
                    'nota' => $note,
                    'checked' => $checkedAt,
                    'admin' => admin_id(),
                ]);
            }
            $pdo->commit();
            $_SESSION['sentieri_flash'] = 'Stato aggiornato per il sentiero ' . $code . '.';
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $pdo->prepare("SELECT nome,gpx_file FROM sentieri WHERE id=:id AND gpx_file LIKE 'gpx/%' LIMIT 1");
            $stmt->execute(['id' => $id]);
            $trail = $stmt->fetch();
            if (!$trail) {
                throw new RuntimeException('Sentiero non trovato.');
            }
            sentieri_delete_gpx((string) $trail['gpx_file']);
            $pdo->prepare('DELETE FROM sentieri WHERE id=:id')->execute(['id' => $id]);
            $_SESSION['sentieri_flash'] = 'File GPX e stato eliminati.';
        } else {
            throw new RuntimeException('Operazione non valida.');
        }
        header('Location: sentieri.php');
        exit;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        if ($uploadedPath !== null) {
            try {
                sentieri_delete_gpx($uploadedPath);
            } catch (Throwable) {
            }
        }
        $error = $exception->getMessage();
    }
}

$moduleReady = true;
$trails = [];
try {
    sentieri_sync_gpx_directory($pdo, admin_id());
    $trails = sentieri_directory_rows($pdo);
} catch (Throwable) {
    $moduleReady = false;
}

admin_page_open('Sentieri', 'sentieri');
?>
<main class="wrap">
    <section class="hero-admin">
        <h1>Sentieri dalla cartella GPX</h1>
        <p>La lista legge direttamente tutti i file presenti in <code>/gpx</code>. Aggiungi o rimuovi un file e aggiorna la pagina per riallineare automaticamente l’elenco.</p>
    </section>

    <?php if ($success !== ''): ?><div class="success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <?php if (!$moduleReady): ?>
        <div class="error">La sezione non è ancora disponibile. Verifica la cartella <code>/gpx</code> e la migrazione <code>20260816_sentieri_autonomi.sql</code>.</div>
    <?php else: ?>
        <section class="box trail-upload" id="carica">
            <div>
                <h2>Carica un nuovo sentiero</h2>
                <p class="hint">Il file viene salvato direttamente nella cartella <code>/gpx</code> e compare subito nell’elenco.</p>
            </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="action" value="upload">
                <input type="file" name="gpx_file" accept=".gpx,application/gpx+xml" required>
                <button class="btn" type="submit">Carica GPX</button>
            </form>
        </section>

        <div class="actions trail-list-actions">
            <a class="btn" href="sentieri.php">Aggiorna elenco</a>
            <a class="btn secondary" href="../stato-sentieri" target="_blank">Vedi pagina pubblica</a>
            <span><strong><?= count($trails) ?></strong> file GPX trovati</span>
        </div>

        <table class="trail-table">
            <thead><tr><th>File GPX</th><th>Codice</th><th>Stato</th><th>Nota pubblica</th><th>Ultima verifica</th><th>Pubblico</th><th>Azioni</th></tr></thead>
            <tbody>
            <?php if ($trails === []): ?><tr><td colspan="7">La cartella <code>/gpx</code> non contiene file GPX.</td></tr><?php endif; ?>
            <?php foreach ($trails as $trail): $stats = gpx_stats((string) $trail['gpx_file'], 'piedi'); $formId = 'trail-' . (int) $trail['id']; ?>
                <tr>
                    <td>
                        <strong><?= e($trail['filename']) ?></strong><br>
                        <small><?= e($stats['length_label']) ?> · +<?= (int) ($stats['ascent_m'] ?? 0) ?> m · <?= e(date('d/m/Y H:i', (int) $trail['file_modified_at'])) ?></small><br>
                        <a href="../gpx/<?= rawurlencode((string) $trail['filename']) ?>?download=1">Scarica</a>
                    </td>
                    <td><strong class="trail-code"><?= e($trail['codice']) ?></strong></td>
                    <td><select form="<?= e($formId) ?>" name="stato"><?php foreach (sentieri_statuses() as $value => $label): ?><option value="<?= e($value) ?>" <?= (string) $trail['stato'] === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></td>
                    <td><textarea form="<?= e($formId) ?>" name="nota_pubblica" rows="3"><?= e($trail['nota_pubblica']) ?></textarea></td>
                    <td><input form="<?= e($formId) ?>" type="datetime-local" name="ultima_verifica_at" value="<?= !empty($trail['ultima_verifica_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $trail['ultima_verifica_at']))) : '' ?>"></td>
                    <td><label><input form="<?= e($formId) ?>" type="checkbox" name="pubblicato" value="1" <?= !empty($trail['pubblicato']) ? 'checked' : '' ?>> Sì</label></td>
                    <td>
                        <form id="<?= e($formId) ?>" method="post">
                            <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="save">
                            <input type="hidden" name="id" value="<?= (int) $trail['id'] ?>">
                            <button class="mini-btn" type="submit">Salva stato</button>
                        </form>
                        <form method="post" onsubmit="return confirm('Eliminare definitivamente questo file dalla cartella /gpx?')">
                            <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $trail['id'] ?>">
                            <button class="mini-btn danger" type="submit">Elimina file</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
<style>
.trail-upload{display:grid;grid-template-columns:1fr minmax(320px,520px);gap:24px;align-items:center;margin-bottom:24px}.trail-upload h2{margin:0 0 8px}.trail-upload p{margin:0}.trail-upload form{display:grid;grid-template-columns:1fr auto;gap:10px;align-items:center}.trail-list-actions{align-items:center}.trail-list-actions span{margin-left:auto}.trail-table input,.trail-table select,.trail-table textarea{min-width:150px}.trail-table textarea{min-height:72px}.trail-table form{display:block;margin-bottom:7px}@media(max-width:850px){.trail-upload{grid-template-columns:1fr}.trail-upload form{grid-template-columns:1fr}.trail-list-actions span{margin-left:0}}
</style>
<?php admin_page_close(); ?>
