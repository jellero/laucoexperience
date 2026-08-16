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
    try {
        if ((string) ($_POST['action'] ?? '') !== 'delete') {
            throw new RuntimeException('Operazione non valida.');
        }
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('SELECT nome, gpx_file FROM sentieri WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $trail = $stmt->fetch();
        if (!$trail) {
            throw new RuntimeException('Sentiero non trovato.');
        }
        $pdo->prepare('DELETE FROM sentieri WHERE id = :id')->execute(['id' => $id]);
        sentieri_delete_gpx((string) $trail['gpx_file']);
        $_SESSION['sentieri_flash'] = 'Sentiero eliminato.';
        header('Location: sentieri.php');
        exit;
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$moduleReady = true;
$trails = [];
try {
    $trails = $pdo->query(
        "SELECT * FROM sentieri ORDER BY pubblicato DESC, FIELD(stato,'non_percorribile','attenzione','in_verifica','verificato'), ordine, nome"
    )->fetchAll() ?: [];
} catch (Throwable) {
    $moduleReady = false;
}

admin_page_open('Sentieri', 'sentieri');
?>
<main class="wrap">
    <section class="hero-admin">
        <h1>Sentieri</h1>
        <p>Anagrafica autonoma degli itinerari: carica le tracce GPX e aggiorna lo stato pubblico di ogni sentiero.</p>
    </section>

    <?php if ($success !== ''): ?><div class="success"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <?php if (!$moduleReady): ?>
        <div class="error">La sezione non è ancora disponibile. Applica la migrazione <code>20260816_sentieri_autonomi.sql</code>.</div>
    <?php else: ?>
        <div class="actions">
            <a class="btn" href="sentiero-form.php">Nuovo sentiero</a>
            <a class="btn secondary" href="../stato-sentieri" target="_blank">Vedi pagina pubblica</a>
        </div>

        <table>
            <thead><tr><th>Sentiero</th><th>Stato</th><th>Verifica</th><th>GPX</th><th>Visibilità</th><th>Azioni</th></tr></thead>
            <tbody>
            <?php if ($trails === []): ?>
                <tr><td colspan="6">Nessun sentiero caricato.</td></tr>
            <?php endif; ?>
            <?php foreach ($trails as $trail): $stats = gpx_stats((string) $trail['gpx_file'], 'piedi'); ?>
                <tr>
                    <td>
                        <strong><?= e($trail['nome']) ?></strong><br>
                        <small><?= e($trail['codice'] ?: 'Senza codice') ?><?= $trail['localita'] ? ' · ' . e($trail['localita']) : '' ?></small>
                    </td>
                    <td><span class="trail-admin-badge <?= e($trail['stato']) ?>"><?= e(sentieri_status_label((string) $trail['stato'])) ?></span></td>
                    <td><?= $trail['ultima_verifica_at'] ? e(date('d/m/Y H:i', strtotime((string) $trail['ultima_verifica_at']))) : '<small>Non verificato</small>' ?></td>
                    <td>
                        <a href="../gpx/<?= rawurlencode(basename((string) $trail['gpx_file'])) ?>?download=1">Scarica GPX</a><br>
                        <small><?= e($stats['length_label']) ?> · +<?= (int) ($stats['ascent_m'] ?? 0) ?> m</small>
                    </td>
                    <td><span class="status <?= $trail['pubblicato'] ? 'ok' : 'draft' ?>"><?= $trail['pubblicato'] ? 'Pubblico' : 'Bozza' ?></span></td>
                    <td>
                        <a class="mini-btn" href="sentiero-form.php?id=<?= (int) $trail['id'] ?>">Modifica</a>
                        <form method="post" onsubmit="return confirm('Eliminare definitivamente questo sentiero e il suo GPX?')">
                            <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $trail['id'] ?>">
                            <button class="mini-btn danger" type="submit">Elimina</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
<style>
.trail-admin-badge{display:inline-block;padding:6px 8px;background:#eee;font-size:12px;font-weight:700}.trail-admin-badge.verificato{background:#d1e7dd;color:#0f5132}.trail-admin-badge.attenzione{background:#fff3cd;color:#664d03}.trail-admin-badge.non_percorribile{background:#f8d7da;color:#842029}
</style>
<?php admin_page_close(); ?>
