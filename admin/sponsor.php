<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

$sponsors = $pdo->query('SELECT * FROM sponsors ORDER BY pubblicato DESC, ordine ASC, id ASC')->fetchAll();
$msg = trim((string) ($_GET['msg'] ?? ''));
?>

<?php admin_page_open('Sponsor', 'sponsor'); ?>

<main class="wrap">
    <div class="page-title">
        <h1>Sponsor</h1>
        <p>Gestisci i loghi mostrati nel carosello della homepage e i relativi collegamenti.</p>
    </div>

    <?php if ($msg !== ''): ?>
        <div class="success"><?= e($msg) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a class="btn" href="sponsor-form.php">Nuovo sponsor</a>
    </div>

    <table>
        <thead>
            <tr>
                <th>Logo</th>
                <th>Testo alternativo</th>
                <th>Link</th>
                <th>Stato</th>
                <th>Ordine</th>
                <th>Azioni</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($sponsors === []): ?>
                <tr><td colspan="6">Nessuno sponsor presente.</td></tr>
            <?php endif; ?>
            <?php foreach ($sponsors as $sponsor): ?>
                <tr>
                    <td>
                        <img class="thumb" style="object-fit:contain;padding:6px" src="../<?= e(ltrim((string) $sponsor['image_path'], '/')) ?>" alt="<?= e($sponsor['alt_text']) ?>">
                    </td>
                    <td><strong><?= e($sponsor['alt_text']) ?></strong></td>
                    <td>
                        <?php if (!empty($sponsor['url'])): ?>
                            <a href="<?= e($sponsor['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($sponsor['url']) ?></a>
                        <?php else: ?>
                            <span class="hint">Nessun link</span>
                        <?php endif; ?>
                    </td>
                    <td class="status <?= (int) $sponsor['pubblicato'] === 1 ? 'ok' : 'draft' ?>">
                        <?= (int) $sponsor['pubblicato'] === 1 ? 'Visibile' : 'Nascosto' ?>
                    </td>
                    <td><?= (int) $sponsor['ordine'] ?></td>
                    <td>
                        <a class="btn" href="sponsor-form.php?id=<?= (int) $sponsor['id'] ?>">Modifica</a>
                        <form method="post" action="sponsor-delete.php" class="inline" onsubmit="return confirm('Eliminare questo sponsor?');">
                            <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="id" value="<?= (int) $sponsor['id'] ?>">
                            <button class="btn danger" type="submit">Elimina</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>

<?php admin_page_close(); ?>
