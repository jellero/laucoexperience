<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$id) {
    http_response_code(404);
    exit('Contributo non valido.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $stato = trim($_POST['stato'] ?? 'nuovo');
    $noteAdmin = trim($_POST['note_admin'] ?? '');

    $allowed = ['nuovo', 'letto', 'valutato', 'pubblicato', 'archiviato'];

    if (!in_array($stato, $allowed, true)) {
        $stato = 'nuovo';
    }

    $up = $pdo->prepare("
        UPDATE contributi
        SET stato = :stato, note_admin = :note_admin
        WHERE id = :id
    ");
    $up->execute([
        'stato' => $stato,
        'note_admin' => $noteAdmin ?: null,
        'id' => $id,
    ]);

    header('Location: contributo.php?id=' . $id . '&msg=' . urlencode('Contributo aggiornato.'));
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM contributi WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$c = $stmt->fetch();

if (!$c) {
    http_response_code(404);
    exit('Contributo non trovato.');
}

$msg = trim($_GET['msg'] ?? '');

admin_page_open('Dettaglio contributo', 'contributi');
?>
<style>
    .detail-grid {
        display: grid;
        grid-template-columns: 1.2fr .8fr;
        gap: 22px;
        align-items: start;
    }

    .detail-box {
        background: #fff;
        box-shadow: var(--admin-shadow);
        padding: 24px;
    }

    .detail-row {
        padding: 12px 0;
        border-bottom: 1px solid #eee;
        line-height: 1.55;
    }

    .detail-row:last-child {
        border-bottom: 0;
    }

    .detail-row strong {
        display: block;
        margin-bottom: 4px;
    }

    .detail-box textarea,
    .detail-box select {
        width: 100%;
        box-sizing: border-box;
        padding: 11px;
        border: 1px solid #ddd;
    }

    .detail-box textarea {
        min-height: 150px;
        resize: vertical;
    }

    @media (max-width: 900px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<main class="wrap">
    <section class="hero-admin">
        <h1><?= e($c['titolo']) ?></h1>
        <p><?= e($c['codice']) ?> · <?= e($c['tipo']) ?> · <?= e($c['created_at']) ?></p>
    </section>

    <?php if ($msg): ?>
        <div class="notice"><?= e($msg) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a class="btn secondary" href="contributi.php">Torna ai contributi</a>
    </div>

    <div class="detail-grid">
        <section class="detail-box">
            <h2>Contributo</h2>

            <div class="detail-row"><strong>Stato</strong><?= e($c['stato']) ?></div>
            <div class="detail-row"><strong>Tipo</strong><?= e($c['tipo']) ?></div>
            <div class="detail-row"><strong>Titolo</strong><?= e($c['titolo']) ?></div>
            <div class="detail-row"><strong>Descrizione</strong><?= nl2br(e($c['descrizione'])) ?></div>

            <?php if (!empty($c['localita'])): ?>
                <div class="detail-row"><strong>Luogo / località</strong><?= e($c['localita']) ?></div>
            <?php endif; ?>

            <?php if (!empty($c['percorso_gpx'])): ?>
                <div class="detail-row">
                    <strong>Sentiero collegato</strong>
                    <a href="../<?= e(ltrim($c['percorso_gpx'], '/')) ?>" target="_blank"><?= e($c['percorso_gpx']) ?></a>
                </div>
            <?php endif; ?>

            <?php if (!empty($c['pagina_url'])): ?>
                <div class="detail-row"><strong>Pagina / link</strong><?= e($c['pagina_url']) ?></div>
            <?php endif; ?>

            <?php if (!empty($c['allegato_path'])): ?>
                <div class="detail-row">
                    <strong>Allegato</strong>
                    <a href="../<?= e($c['allegato_path']) ?>" target="_blank">Apri allegato</a>
                </div>
            <?php endif; ?>
        </section>

        <aside class="detail-box">
            <h2>Contatto</h2>

            <div class="detail-row"><strong>Nome</strong><?= e($c['nome'] ?: '-') ?></div>
            <div class="detail-row"><strong>Email</strong><?= e($c['email'] ?: '-') ?></div>
            <div class="detail-row"><strong>Telefono</strong><?= e($c['telefono'] ?: '-') ?></div>
            <div class="detail-row"><strong>IP</strong><?= e($c['ip_address'] ?: '-') ?></div>

            <form method="post">
                <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">

                <label for="stato"><strong>Stato</strong></label>
                <select id="stato" name="stato">
                    <?php foreach (['nuovo','letto','valutato','pubblicato','archiviato'] as $st): ?>
                        <option value="<?= e($st) ?>" <?= $c['stato'] === $st ? 'selected' : '' ?>><?= e($st) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="note_admin"><strong>Note admin</strong></label>
                <textarea id="note_admin" name="note_admin"><?= e($c['note_admin'] ?? '') ?></textarea>

                <div class="actions" style="margin-top:14px;">
                    <button type="submit" class="btn">Salva</button>
                </div>
            </form>
        </aside>
    </div>
</main>
<?php admin_page_close(); ?>
