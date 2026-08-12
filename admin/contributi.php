<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

$stato = trim($_GET['stato'] ?? '');
$params = [];
$where = '';

if ($stato !== '') {
    $where = 'WHERE stato = :stato';
    $params['stato'] = $stato;
}

$stmt = $pdo->prepare("
    SELECT *
    FROM contributi
    {$where}
    ORDER BY FIELD(stato, 'nuovo','letto','valutato','pubblicato','archiviato'), created_at DESC
");
$stmt->execute($params);
$contributi = $stmt->fetchAll();

admin_page_open('Contributi', 'contributi');
?>
<style>
    .contributi-list {
        display: grid;
        gap: 14px;
    }

    .contributo-card {
        background: #fff;
        box-shadow: var(--admin-shadow);
        padding: 18px;
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        gap: 18px;
        align-items: center;
    }

    .contributo-card h3 {
        margin: 0 0 6px;
        overflow-wrap: anywhere;
    }

    .contributo-meta {
        color: #777;
        font-size: 13px;
        line-height: 1.55;
    }

    .contributo-actions {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .admin-pill {
        display: inline-block;
        padding: 5px 8px;
        background: #eee;
        font-size: 12px;
        font-weight: 700;
        margin-right: 5px;
    }

    .admin-pill.nuovo {
        background: #ffe0e0;
        color: #8a0000;
    }

    .admin-pill.letto,
    .admin-pill.valutato {
        background: #fff0c2;
        color: #684c00;
    }

    .admin-pill.pubblicato {
        background: #d1e7dd;
        color: #0f5132;
    }

    .admin-pill.archiviato {
        background: #e9ecef;
        color: #555;
    }

    @media (max-width: 760px) {
        .contributo-card {
            grid-template-columns: 1fr;
        }

        .contributo-actions {
            justify-content: flex-start;
        }
    }
</style>

<main class="wrap">
    <section class="hero-admin">
        <h1>Contributi</h1>
        <p>Materiali, fotografie, tracce e informazioni inviate dagli utenti.</p>
    </section>

    <div class="actions">
        <a class="btn secondary" href="contributi.php">Tutti</a>
        <a class="btn secondary" href="contributi.php?stato=nuovo">Nuovi</a>
        <a class="btn secondary" href="../contributi" target="_blank">Pagina pubblica</a>
        <a class="btn secondary" href="index.php">Dashboard</a>
    </div>

    <?php if (!$contributi): ?>
        <div class="notice">Nessun contributo presente.</div>
    <?php else: ?>
        <div class="contributi-list">
            <?php foreach ($contributi as $contributo): ?>
                <article class="contributo-card">
                    <div>
                        <h3>
                            <a href="contributo.php?id=<?= (int) $contributo['id'] ?>">
                                <?= e($contributo['titolo']) ?>
                            </a>
                        </h3>

                        <div class="contributo-meta">
                            <span class="admin-pill <?= e($contributo['stato']) ?>"><?= e($contributo['stato']) ?></span>
                            <?= e($contributo['codice']) ?> ·
                            <?= e($contributo['tipo']) ?> ·
                            <?= e($contributo['created_at']) ?>
                            <?php if (!empty($contributo['nome'])): ?>
                                · <?= e($contributo['nome']) ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="contributo-actions">
                        <a class="mini-btn" href="contributo.php?id=<?= (int) $contributo['id'] ?>">Apri</a>
                        <?php if (admin_can('admin.all')): ?>
                            <a class="mini-btn danger" href="contributo-delete.php?id=<?= (int) $contributo['id'] ?>&_csrf_token=<?= e(csrf_token()) ?>" onclick="return confirm('Eliminare definitivamente questo contributo?');">Elimina</a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php admin_page_close(); ?>
