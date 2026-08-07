<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/gpx-stats.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

$tipo = $_GET['tipo'] ?? '';
$allowedTypes = ['piedi', 'mtb'];

if (in_array($tipo, $allowedTypes, true)) {
    $stmt = $pdo->prepare('SELECT * FROM percorsi WHERE tipo = :tipo ORDER BY titolo ASC, id ASC');
    $stmt->execute(['tipo' => $tipo]);
} else {
    $stmt = $pdo->query('SELECT * FROM percorsi ORDER BY titolo ASC, id ASC');
}

$percorsi = $stmt->fetchAll();
$deleteStatus = $_GET['deleted'] ?? '';
$deleteError = trim($_GET['error'] ?? '');
?>

<?php admin_page_open('Percorsi', 'percorsi'); ?>

<main class="wrap">
    <div class="page-title">
        <h1>Percorsi</h1>
        <p>Gestione degli itinerari ordinati alfabeticamente per titolo.</p>
    </div>

    <?php if ($deleteStatus === '1'): ?>
        <div class="success">Percorso eliminato correttamente.</div>
    <?php elseif ($deleteStatus === '0'): ?>
        <div class="error">
            Eliminazione non riuscita<?= $deleteError !== '' ? ': ' . e($deleteError) : '.' ?>
        </div>
    <?php endif; ?>

    <div class="actions">
        <a class="btn" href="percorso-form.php">Nuovo percorso</a>
        <a class="btn secondary" href="percorsi.php">Tutti</a>
        <a class="btn secondary" href="percorsi.php?tipo=piedi">Solo piedi</a>
        <a class="btn secondary" href="percorsi.php?tipo=mtb">Solo MTB</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Titolo</th>
                <th>Tipo</th>
                <th>Dati percorso</th>
                <th>Stato</th>
                <th>Consigliato</th>
                <th>Speciale</th>
                <th>Ordine</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
        <?php if (!$percorsi): ?>
            <tr>
                <td colspan="8">Nessun percorso presente.</td>
            </tr>
        <?php endif; ?>

        <?php foreach ($percorsi as $p): ?>
            <?php
                $stats = gpx_stats($p['gpx_file'] ?? null, $p['tipo']);
                $displayDifficulty = trim((string) ($p['difficolta'] ?? '')) ?: ($stats['difficulty'] ?? '-');
                $displayTime = trim((string) ($p['tempo'] ?? '')) ?: ($stats['duration_label'] ?? '-');
                $deleteUrl = 'percorso-delete.php?id=' . urlencode((string) $p['id']) . '&_csrf_token=' . urlencode(csrf_token());
            ?>
            <tr>
                <td>
                    <strong><?= e($p['titolo']) ?></strong><br>
                    <small><?= e($p['slug']) ?></small>
                </td>
                <td><?= e(strtoupper($p['tipo'])) ?></td>
                <td>
                    Lunghezza: <?= e($stats['length_label']) ?><br>
                    Dislivello: <?= e($stats['ascent_label']) ?><br>
                    Tempo: <?= e($displayTime) ?><br>
                    Difficoltà: <?= e($displayDifficulty) ?><br>
                    <small class="muted">Aggiornamento GPX: <?= e($stats['updated_label']) ?></small>
                </td>
                <td class="status"><?= $p['pubblicato'] ? 'Pubblicato' : 'Bozza' ?></td>
                <td><?= !empty($p['consigliato']) ? 'Sì' : 'No' ?></td>
                <td><?= !empty($p['speciale']) ? 'Sì' : 'No' ?></td>
                <td><?= (int) $p['ordine'] ?></td>
                <td>
                    <a class="btn secondary" href="../percorso.php?slug=<?= urlencode($p['slug']) ?>" target="_blank">Vedi</a>
                    <a class="btn" href="percorso-form.php?id=<?= (int) $p['id'] ?>">Modifica</a>
                    <a class="btn secondary" href="percorso-ai-demo.php?id=<?= (int) $p['id'] ?>">AI / Lingue</a>
                    <a class="btn danger" href="<?= e($deleteUrl) ?>" onclick="return confirm('Eliminare definitivamente questo percorso?');">Elimina</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</main>
<?php admin_page_close(); ?>
