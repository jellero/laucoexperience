<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

function segnalazione_date_full($date): string
{
    $ts = strtotime((string) $date);
    return $ts ? date('d.m.Y H:i', $ts) : '-';
}

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$id) {
    http_response_code(404);
    exit('Segnalazione non trovata.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $stato = $_POST['stato'] ?? 'nuova';
    $priorita = $_POST['priorita'] ?? 'media';
    $noteAdmin = trim($_POST['note_admin'] ?? '');

    if (!in_array($stato, ['nuova', 'in_lavorazione', 'risolta', 'archiviata'], true)) {
        $stato = 'nuova';
    }

    if (!in_array($priorita, ['bassa', 'media', 'alta'], true)) {
        $priorita = 'media';
    }

    $stmt = $pdo->prepare("
        UPDATE segnalazioni_problemi SET
            stato = :stato,
            priorita = :priorita,
            note_admin = :note_admin
        WHERE id = :id
    ");

    $stmt->execute([
        'stato' => $stato,
        'priorita' => $priorita,
        'note_admin' => $noteAdmin,
        'id' => $id,
    ]);

    header('Location: segnalazione.php?id=' . $id . '&msg=' . urlencode('Segnalazione aggiornata.'));
    exit;
}

$stmt = $pdo->prepare("
    SELECT s.*,
           p.titolo AS percorso_titolo,
           p.slug AS percorso_slug,
           e.titolo AS evento_titolo,
           e.slug AS evento_slug
    FROM segnalazioni_problemi s
    LEFT JOIN percorsi p ON p.id = s.percorso_id
    LEFT JOIN eventi e ON e.id = s.evento_id
    WHERE s.id = :id
    LIMIT 1
");
$stmt->execute(['id' => $id]);
$s = $stmt->fetch();

if (!$s) {
    http_response_code(404);
    exit('Segnalazione non trovata.');
}

$msg = trim($_GET['msg'] ?? '');
?>

<?php admin_page_open('Dettaglio segnalazione', 'segnalazioni'); ?>

<style>
    .detail-grid {
        display: grid;
        grid-template-columns: 1.15fr .85fr;
        gap: 22px;
    }

    .detail-box {
        background: #fff;
        padding: 24px;
        box-shadow: 0 6px 20px rgba(0,0,0,.06);
    }

    .detail-box h2 {
        margin-top: 0;
    }

    .detail-row {
        border-bottom: 1px solid #eee;
        padding: 10px 0;
    }

    .detail-row strong {
        display: block;
        margin-bottom: 4px;
    }

    .detail-box textarea,
    .detail-box select {
        width: 100%;
        padding: 11px;
        border: 1px solid #ddd;
        box-sizing: border-box;
    }

    .detail-box textarea {
        min-height: 160px;
        resize: vertical;
    }

    @media (max-width: 850px) {
        .detail-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<main class="wrap">
    <h1><?= e($s['titolo']) ?></h1>

    <?php if ($msg): ?>
        <div class="notice"><?= e($msg) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a class="btn secondary" href="segnalazioni.php">Torna alle segnalazioni</a>
        <?php if (!empty($s['email'])): ?>
            <a class="btn" href="posta-scrivi.php?<?= e(http_build_query(['report_id' => (int) $s['id']])) ?>">Rispondi via email</a>
        <?php endif; ?>
        <?php if (admin_can('admin.all')): ?>
        <a
            class="btn danger"
            href="segnalazione-delete.php?id=<?= (int) $s['id'] ?>&_csrf_token=<?= e(csrf_token()) ?>"
            onclick="return confirm('Eliminare definitivamente questa segnalazione?');"
        >Elimina</a>
        <?php endif; ?>
    </div>

    <div class="detail-grid">
        <section class="detail-box">
            <h2>Dettagli</h2>

            <div class="detail-row">
                <strong>Codice</strong>
                <?= e($s['codice']) ?>
            </div>

            <div class="detail-row">
                <strong>Categoria</strong>
                <?= e($s['categoria']) ?>
            </div>

            <div class="detail-row">
                <strong>Descrizione</strong>
                <?= nl2br(e($s['descrizione'])) ?>
            </div>

            <?php if (!empty($s['luogo'])): ?>
                <div class="detail-row">
                    <strong>Luogo</strong>
                    <?= e($s['luogo']) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($s['pagina_url'])): ?>
                <div class="detail-row">
                    <strong>Pagina / link</strong>
                    <?= e($s['pagina_url']) ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($s['percorso_titolo'])): ?>
                <div class="detail-row">
                    <strong>Percorso</strong>
                    <a href="../percorso.php?slug=<?= urlencode($s['percorso_slug']) ?>" target="_blank"><?= e($s['percorso_titolo']) ?></a>
                </div>
            <?php endif; ?>

            <?php if (!empty($s['evento_titolo'])): ?>
                <div class="detail-row">
                    <strong>Evento</strong>
                    <a href="../evento.php?slug=<?= urlencode($s['evento_slug']) ?>" target="_blank"><?= e($s['evento_titolo']) ?></a>
                </div>
            <?php endif; ?>

            <?php if (!empty($s['allegato_path'])): ?>
                <div class="detail-row">
                    <strong>Allegato</strong>
                    <a href="../<?= e($s['allegato_path']) ?>" target="_blank">Apri allegato</a>
                </div>
            <?php endif; ?>
        </section>

        <aside class="detail-box">
            <h2>Gestione</h2>

            <form method="post">
                <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $s['id'] ?>">

                <div class="detail-row">
                    <strong>Stato</strong>
                    <select name="stato">
                        <option value="nuova" <?= $s['stato'] === 'nuova' ? 'selected' : '' ?>>Nuova</option>
                        <option value="in_lavorazione" <?= $s['stato'] === 'in_lavorazione' ? 'selected' : '' ?>>In lavorazione</option>
                        <option value="risolta" <?= $s['stato'] === 'risolta' ? 'selected' : '' ?>>Risolta</option>
                        <option value="archiviata" <?= $s['stato'] === 'archiviata' ? 'selected' : '' ?>>Archiviata</option>
                    </select>
                </div>

                <div class="detail-row">
                    <strong>Priorità</strong>
                    <select name="priorita">
                        <option value="bassa" <?= $s['priorita'] === 'bassa' ? 'selected' : '' ?>>Bassa</option>
                        <option value="media" <?= $s['priorita'] === 'media' ? 'selected' : '' ?>>Media</option>
                        <option value="alta" <?= $s['priorita'] === 'alta' ? 'selected' : '' ?>>Alta</option>
                    </select>
                </div>

                <div class="detail-row">
                    <strong>Note interne</strong>
                    <textarea name="note_admin"><?= e($s['note_admin'] ?? '') ?></textarea>
                </div>

                <button class="btn" type="submit">Salva gestione</button>
            </form>

            <div class="detail-row">
                <strong>Data invio</strong>
                <?= e(segnalazione_date_full($s['created_at'])) ?>
            </div>

            <div class="detail-row">
                <strong>Contatto</strong>
                <?= e($s['nome'] ?: '-') ?><br>
                <?= e($s['email'] ?: '-') ?><br>
                <?= e($s['telefono'] ?: '-') ?>
            </div>

            <div class="detail-row">
                <strong>Dati tecnici</strong>
                IP: <?= e($s['ip_address'] ?: '-') ?><br>
                Browser: <?= e($s['user_agent'] ?: '-') ?>
            </div>
        </aside>
    </div>
</main>

<?php admin_page_close(); ?>
