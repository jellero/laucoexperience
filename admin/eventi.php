<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

function data_evento_it($date)
{
    if (!$date) {
        return '-';
    }

    $ts = strtotime($date);
    return $ts ? date('d.m.Y', $ts) : '-';
}

$eventi = $pdo->query("
    SELECT *
    FROM eventi
    ORDER BY pubblicato DESC, ordine ASC, data_evento DESC, created_at DESC
")->fetchAll();

$msg = trim($_GET['msg'] ?? '');
?>

<?php admin_page_open('Eventi', 'eventi'); ?>

<main class="wrap">
    <h1>Eventi</h1>

    <?php if ($msg): ?>
        <div class="notice"><?= e($msg) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a class="btn" href="evento-form.php">Nuovo evento</a>
        <a class="btn" href="eventi-importa.php">Importa da fonti</a>
        <a class="btn secondary" href="index.php">Dashboard</a>
        <a class="btn secondary" href="percorsi.php">Gestisci percorsi</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Titolo</th>
                <th>Data</th>
                <th>Categoria</th>
                <th>Località</th>
                <th>Stato</th>
                <th>Ordine</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$eventi): ?>
                <tr>
                    <td colspan="7">Nessun evento presente.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($eventi as $evento): ?>
                <tr>
                    <td>
                        <strong><?= e($evento['titolo']) ?></strong><br>
                        <small><?= e($evento['slug']) ?></small>
                    </td>
                    <td><?= e(data_evento_it($evento['data_evento'] ?? null)) ?></td>
                    <td><?= e($evento['categoria'] ?: '-') ?></td>
                    <td><?= e($evento['localita'] ?: '-') ?></td>
                    <td><?= $evento['pubblicato'] ? 'Pubblicato' : 'Bozza' ?></td>
                    <td><?= (int) $evento['ordine'] ?></td>
                    <td>
                        <a class="btn secondary" href="../evento.php?slug=<?= urlencode($evento['slug']) ?>" target="_blank">Vedi</a>
                        <a class="btn" href="evento-form.php?id=<?= (int) $evento['id'] ?>">Modifica</a>
                        <a
                            class="btn danger"
                            href="evento-delete.php?id=<?= (int) $evento['id'] ?>&_csrf_token=<?= e(csrf_token()) ?>"
                            onclick="return confirm('Eliminare questo evento?');"
                        >Elimina</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>

<?php admin_page_close(); ?>
