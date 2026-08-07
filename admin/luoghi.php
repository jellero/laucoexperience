<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

$stmt = $pdo->query("
    SELECT *
    FROM luoghi
    ORDER BY ordine ASC, titolo ASC
");
$luoghi = $stmt->fetchAll();

$msg = trim($_GET['msg'] ?? '');

admin_page_open('Luoghi', 'luoghi');
?>
<style>
    .luoghi-admin-list {
        display: grid;
        gap: 14px;
    }

    .luogo-admin-card {
        background: #fff;
        box-shadow: var(--admin-shadow);
        padding: 18px;
        display: grid;
        grid-template-columns: 120px minmax(0, 1fr) auto;
        gap: 18px;
        align-items: center;
    }

    .luogo-admin-thumb {
        height: 86px;
        background-size: cover;
        background-position: center;
        background-color: #eee;
    }

    .luogo-admin-card h3 {
        margin: 0 0 6px;
        overflow-wrap: anywhere;
    }

    .luogo-admin-meta {
        color: #777;
        font-size: 13px;
        line-height: 1.55;
    }

    .luogo-admin-actions {
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

    .admin-pill.ok {
        background: #d1e7dd;
        color: #0f5132;
    }

    .admin-pill.draft {
        background: #f1f1f1;
        color: #666;
    }

    .admin-pill.featured {
        background: #fff0c2;
        color: #684c00;
    }

    @media (max-width: 760px) {
        .luogo-admin-card {
            grid-template-columns: 1fr;
        }

        .luogo-admin-actions {
            justify-content: flex-start;
        }
    }
</style>

<main class="wrap">
    <section class="hero-admin">
        <h1>Luoghi</h1>
        <p>Gestione dei luoghi da scoprire: storia, panorami, natura e punti di interesse.</p>
    </section>

    <?php if ($msg): ?>
        <div class="notice"><?= e($msg) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a class="btn" href="luogo-form.php">Nuovo luogo</a>
        <a class="btn secondary" href="../luoghi" target="_blank">Vedi pagina pubblica</a>
        <a class="btn secondary" href="index.php">Dashboard</a>
    </div>

    <?php if (!$luoghi): ?>
        <div class="notice">Nessun luogo presente.</div>
    <?php else: ?>
        <div class="luoghi-admin-list">
            <?php foreach ($luoghi as $luogo): ?>
                <article class="luogo-admin-card">
                    <div class="luogo-admin-thumb" style="background-image:url(<?= e('../' . ($luogo['cover_image'] ?: 'assets/img/trip4.jpg')) ?>)"></div>

                    <div>
                        <h3><?= e($luogo['titolo']) ?></h3>
                        <div class="luogo-admin-meta">
                            <?= e($luogo['categoria'] ?: 'Senza categoria') ?>
                            <?php if (!empty($luogo['localita'])): ?>
                                · <?= e($luogo['localita']) ?>
                            <?php endif; ?>
                            · ordine <?= (int) $luogo['ordine'] ?>
                            <br>
                            <span class="admin-pill <?= !empty($luogo['pubblicato']) ? 'ok' : 'draft' ?>">
                                <?= !empty($luogo['pubblicato']) ? 'Pubblicato' : 'Bozza' ?>
                            </span>
                            <?php if (!empty($luogo['in_evidenza'])): ?>
                                <span class="admin-pill featured">In evidenza</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="luogo-admin-actions">
                        <a class="mini-btn" href="luogo-form.php?id=<?= (int) $luogo['id'] ?>">Modifica</a>
                        <a class="mini-btn secondary" href="../luogo?slug=<?= urlencode($luogo['slug']) ?>" target="_blank">Vedi</a>
                        <a class="mini-btn danger" href="luogo-delete.php?id=<?= (int) $luogo['id'] ?>&_csrf_token=<?= e(csrf_token()) ?>" onclick="return confirm('Eliminare definitivamente questo luogo?');">Elimina</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php admin_page_close(); ?>
