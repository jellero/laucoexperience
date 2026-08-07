<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

function contatto_date_full($date): string
{
    $ts = strtotime((string) $date);
    return $ts ? date('d.m.Y H:i', $ts) : '-';
}

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if (!$id) {
    http_response_code(404);
    exit('Messaggio non trovato.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $stato = $_POST['stato'] ?? 'letto';

    if (!in_array($stato, ['nuovo', 'letto', 'risposto', 'archiviato'], true)) {
        $stato = 'letto';
    }

    $stmt = $pdo->prepare("
        UPDATE contatti_messaggi SET
            stato = :stato,
            note_admin = :note_admin
        WHERE id = :id
    ");

    $stmt->execute([
        'stato' => $stato,
        'note_admin' => trim($_POST['note_admin'] ?? ''),
        'id' => $id,
    ]);

    header('Location: contatto-messaggio.php?id=' . $id . '&msg=' . urlencode('Messaggio aggiornato.'));
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM contatti_messaggi WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$m = $stmt->fetch();

if (!$m) {
    http_response_code(404);
    exit('Messaggio non trovato.');
}

if ($m['stato'] === 'nuovo') {
    $update = $pdo->prepare("UPDATE contatti_messaggi SET stato = 'letto' WHERE id = :id");
    $update->execute(['id' => $id]);
    $m['stato'] = 'letto';
}

$msg = trim($_GET['msg'] ?? '');

admin_page_open('Messaggio contatto', '');
?>
<style>
    .message-detail-grid { display:grid; grid-template-columns:1.15fr .85fr; gap:22px; }
    .message-box { background:#fff; padding:24px; box-shadow:var(--admin-shadow); }
    .message-row { border-bottom:1px solid var(--admin-border); padding:11px 0; }
    .message-row strong { display:block; margin-bottom:5px; }
    .message-box textarea, .message-box select { width:100%; padding:11px; border:1px solid #ddd; box-sizing:border-box; }
    .message-box textarea { min-height:150px; resize:vertical; }
    @media (max-width:850px) { .message-detail-grid { grid-template-columns:1fr; } }
</style>

<main class="wrap">
    <section class="hero-admin">
        <h1><?= e($m['oggetto']) ?></h1>
        <p><?= e($m['codice']) ?></p>
    </section>

    <?php if ($msg): ?>
        <div class="notice"><?= e($msg) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a class="btn secondary" href="contatti-messaggi.php">Torna ai messaggi</a>
        <a class="btn" href="mailto:<?= e($m['email']) ?>?subject=Re:%20<?= urlencode($m['oggetto']) ?>">Rispondi via email</a>
        <a class="btn danger" href="contatto-messaggio-delete.php?id=<?= (int) $m['id'] ?>&_csrf_token=<?= e(csrf_token()) ?>" onclick="return confirm('Eliminare definitivamente questo messaggio?');">Elimina</a>
    </div>

    <div class="message-detail-grid">
        <section class="message-box">
            <h2>Messaggio</h2>
            <div class="message-row"><strong>Da</strong><?= e($m['nome']) ?> · <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a></div>
            <div class="message-row"><strong>Oggetto</strong><?= e($m['oggetto']) ?></div>
            <div class="message-row"><strong>Testo</strong><?= nl2br(e($m['messaggio'])) ?></div>
            <div class="message-row"><strong>Invio email</strong>Email admin: <?= $m['mail_admin_sent'] ? 'inviata' : 'non inviata' ?><br>Risposta cliente: <?= $m['mail_customer_sent'] ? 'inviata' : 'non inviata' ?></div>
        </section>

        <aside class="message-box">
            <h2>Gestione</h2>

            <form method="post">
                <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">

                <div class="message-row">
                    <strong>Stato</strong>
                    <select name="stato">
                        <option value="nuovo" <?= $m['stato'] === 'nuovo' ? 'selected' : '' ?>>Nuovo</option>
                        <option value="letto" <?= $m['stato'] === 'letto' ? 'selected' : '' ?>>Letto</option>
                        <option value="risposto" <?= $m['stato'] === 'risposto' ? 'selected' : '' ?>>Risposto</option>
                        <option value="archiviato" <?= $m['stato'] === 'archiviato' ? 'selected' : '' ?>>Archiviato</option>
                    </select>
                </div>

                <div class="message-row">
                    <strong>Note interne</strong>
                    <textarea name="note_admin"><?= e($m['note_admin'] ?? '') ?></textarea>
                </div>

                <button class="btn" type="submit">Salva</button>
            </form>

            <div class="message-row"><strong>Data</strong><?= e(contatto_date_full($m['created_at'])) ?></div>
            <div class="message-row"><strong>Dati tecnici</strong>IP: <?= e($m['ip_address'] ?: '-') ?><br>Browser: <?= e($m['user_agent'] ?: '-') ?></div>
        </aside>
    </div>
</main>
<?php admin_page_close(); ?>
