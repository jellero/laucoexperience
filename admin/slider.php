<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

$stmt = $pdo->query("
    SELECT s.*,
           e.titolo AS evento_titolo,
           p.titolo AS percorso_titolo
    FROM home_slider s
    LEFT JOIN eventi e ON e.id = s.evento_id
    LEFT JOIN percorsi p ON p.id = s.percorso_id
    ORDER BY s.pubblicato DESC, s.ordine ASC, s.created_at DESC
");

$slides = $stmt->fetchAll();

function slider_link_label(array $slide): string
{
    switch ($slide['link_type']) {
        case 'evento':
            return 'Evento: ' . ($slide['evento_titolo'] ?: '-');

        case 'percorso':
            return 'Percorso: ' . ($slide['percorso_titolo'] ?: '-');

        case 'free':
            return 'Libero: ' . ($slide['custom_url'] ?: '-');

        default:
            return 'Nessun link';
    }
}

$msg = trim($_GET['msg'] ?? '');
?>

<?php admin_page_open('Slider home', 'slider'); ?>

<main class="wrap">
    <h1>Slider home</h1>

    <?php if ($msg): ?>
        <div class="notice"><?= e($msg) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a class="btn" href="slider-form.php">Nuova slide</a>
        <a class="btn secondary" href="index.php">Dashboard</a>
        <a class="btn secondary" href="percorsi.php">Gestisci percorsi</a>
        <a class="btn secondary" href="eventi.php">Gestisci eventi</a>
        <a class="btn secondary" href="galleria.php">Gestisci galleria</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Immagine</th>
                <th>Titolo</th>
                <th>Sottotitolo</th>
                <th>Link</th>
                <th>Stato</th>
                <th>Ordine</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$slides): ?>
                <tr>
                    <td colspan="7">Nessuna slide presente.</td>
                </tr>
            <?php endif; ?>

            <?php foreach ($slides as $slide): ?>
                <tr>
                    <td>
                        <img class="thumb" src="../<?= e($slide['image_path']) ?>" alt="<?= e($slide['titolo']) ?>">
                        <small><?= e($slide['image_path']) ?></small>
                    </td>
                    <td><strong><?= e($slide['titolo']) ?></strong></td>
                    <td><?= e($slide['sottotitolo'] ?: '-') ?></td>
                    <td>
                        <?= e(slider_link_label($slide)) ?><br>
                        <small>Bottone: <?= e($slide['button_label']) ?></small>
                    </td>
                    <td class="status"><?= $slide['pubblicato'] ? 'Pubblicata' : 'Bozza' ?></td>
                    <td><?= (int) $slide['ordine'] ?></td>
                    <td>
                        <a class="btn" href="slider-form.php?id=<?= (int) $slide['id'] ?>">Modifica</a>
                        <a
                            class="btn danger"
                            href="slider-delete.php?id=<?= (int) $slide['id'] ?>&_csrf_token=<?= e(csrf_token()) ?>"
                            onclick="return confirm('Eliminare questa slide?');"
                        >Elimina</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>

<?php admin_page_close(); ?>
