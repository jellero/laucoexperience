<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

$stmt = $pdo->query("
    SELECT *
    FROM galleria
    ORDER BY pubblicato DESC, ordine ASC, created_at DESC
");

$immagini = $stmt->fetchAll();

$msg = trim($_GET['msg'] ?? '');
?>

<?php admin_page_open('Galleria', 'galleria'); ?>

<main class="wrap">
    <h1>Galleria</h1>

    <?php if ($msg): ?>
        <div class="notice"><?= e($msg) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a class="btn" href="galleria-form.php">Aggiungi immagini</a>
        <a class="btn secondary" href="index.php">Dashboard</a>
        <a class="btn secondary" href="percorsi.php">Gestisci percorsi</a>
        <a class="btn secondary" href="eventi.php">Gestisci eventi</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Immagine</th>
                <th>Titolo</th>
                <th>Categoria</th>
                <th>Stato</th>
                <th>Ordine</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$immagini): ?>
                <tr>
                    <td colspan="6">Nessuna immagine presente.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($immagini as $img): ?>
                <tr>
                    <td>
                        <img class="thumb" src="../<?= e($img['image_path']) ?>" alt="<?= e($img['alt'] ?: $img['titolo']) ?>">
                        <small><?= e($img['image_path']) ?></small>
                    </td>
                    <td>
                        <strong><?= e($img['titolo'] ?: '-') ?></strong><br>
                        <small>Alt: <?= e($img['alt'] ?: '-') ?></small>
                    </td>
                    <td><?= e($img['categoria'] ?: '-') ?></td>
                    <td class="status"><?= $img['pubblicato'] ? 'Pubblicata' : 'Bozza' ?></td>
                    <td><?= (int) $img['ordine'] ?></td>
                    <td>
                        <a class="btn" href="galleria-form.php?id=<?= (int) $img['id'] ?>">Modifica</a>
                        <a
                            class="btn danger"
                            href="galleria-delete.php?id=<?= (int) $img['id'] ?>&_csrf_token=<?= e(csrf_token()) ?>"
                            onclick="return confirm('Eliminare questa immagine?');"
                        >Elimina</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>

<?php admin_page_close(); ?>
