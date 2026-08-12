<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

function contatti_date($date): string
{
    $ts = strtotime((string) $date);
    return $ts ? date('d.m.Y H:i', $ts) : '-';
}

$stato = trim($_GET['stato'] ?? '');
$allowed = ['nuovo', 'letto', 'risposto', 'archiviato'];

$where = [];
$params = [];

if ($stato && in_array($stato, $allowed, true)) {
    $where[] = 'stato = :stato';
    $params['stato'] = $stato;
}

$sql = "SELECT * FROM contatti_messaggi";

if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY FIELD(stato, 'nuovo','letto','risposto','archiviato'), created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$messaggi = $stmt->fetchAll();

$msg = trim($_GET['msg'] ?? '');

admin_page_open('Messaggi contatti', 'messaggi');
?>
<style>
    .contact-admin-filters { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
    .contact-message-card { background:#fff; box-shadow:var(--admin-shadow); padding:18px; margin-bottom:14px; display:grid; grid-template-columns:minmax(0,1fr) auto; gap:18px; align-items:center; }
    .contact-message-card h3 { margin:0 0 6px; overflow-wrap:anywhere; }
    .contact-message-meta { color:#777; font-size:13px; line-height:1.6; overflow-wrap:anywhere; }
    .contact-pill { display:inline-block; padding:6px 8px; background:#eee; font-size:12px; font-weight:700; margin-bottom:8px; }
    .contact-pill.nuovo { background:#ffe0e0; color:#8a0000; }
    .contact-pill.letto { background:#fff0c2; color:#684c00; }
    .contact-pill.risposto { background:#d1e7dd; color:#0f5132; }
    .contact-pill.archiviato { background:#e9ecef; color:#555; }
    @media (max-width:760px) { .contact-message-card { grid-template-columns:1fr; } }
</style>

<main class="wrap">
    <section class="hero-admin">
        <h1>Messaggi contatti</h1>
        <p>Messaggi inviati dalla pagina contatti.</p>
    </section>

    <?php if ($msg): ?>
        <div class="notice"><?= e($msg) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a class="btn secondary" href="index.php">Dashboard</a>
        <a class="btn secondary" href="../contatti.php" target="_blank">Apri pagina contatti</a>
    </div>

    <div class="contact-admin-filters">
        <a class="btn <?= $stato === '' ? '' : 'secondary' ?>" href="contatti-messaggi.php">Tutti</a>
        <a class="btn <?= $stato === 'nuovo' ? '' : 'secondary' ?>" href="contatti-messaggi.php?stato=nuovo">Nuovi</a>
        <a class="btn <?= $stato === 'letto' ? '' : 'secondary' ?>" href="contatti-messaggi.php?stato=letto">Letti</a>
        <a class="btn <?= $stato === 'risposto' ? '' : 'secondary' ?>" href="contatti-messaggi.php?stato=risposto">Risposti</a>
        <a class="btn <?= $stato === 'archiviato' ? '' : 'secondary' ?>" href="contatti-messaggi.php?stato=archiviato">Archiviati</a>
    </div>

    <?php if (!$messaggi): ?>
        <div class="notice">Nessun messaggio presente.</div>
    <?php endif; ?>

    <?php foreach ($messaggi as $m): ?>
        <article class="contact-message-card">
            <div>
                <h3><?= e($m['oggetto']) ?></h3>
                <div class="contact-message-meta">
                    <strong><?= e($m['codice']) ?></strong> · <?= e(contatti_date($m['created_at'])) ?><br>
                    Da: <?= e($m['nome']) ?> · <?= e($m['email']) ?><br>
                    Email admin: <?= $m['mail_admin_sent'] ? 'inviata' : 'non inviata' ?> ·
                    risposta cliente: <?= $m['mail_customer_sent'] ? 'inviata' : 'non inviata' ?>
                </div>
            </div>

            <div>
                <span class="contact-pill <?= e($m['stato']) ?>"><?= e($m['stato']) ?></span><br>
                <a class="btn" href="contatto-messaggio.php?id=<?= (int) $m['id'] ?>">Apri</a>
            </div>
        </article>
    <?php endforeach; ?>
</main>
<?php admin_page_close(); ?>
