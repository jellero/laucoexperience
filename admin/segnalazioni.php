<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

function segnalazione_stato_label(string $stato): string
{
    $labels = [
        'nuova' => 'Nuova',
        'in_lavorazione' => 'In lavorazione',
        'risolta' => 'Risolta',
        'archiviata' => 'Archiviata',
    ];

    return $labels[$stato] ?? $stato;
}

function segnalazione_date($date): string
{
    $ts = strtotime((string) $date);
    return $ts ? date('d.m.Y H:i', $ts) : '-';
}

$stato = trim($_GET['stato'] ?? '');
$allowedStati = ['nuova', 'in_lavorazione', 'risolta', 'archiviata'];

$where = [];
$params = [];

if ($stato !== '' && in_array($stato, $allowedStati, true)) {
    $where[] = 's.stato = :stato';
    $params['stato'] = $stato;
}

$sql = "
    SELECT s.*,
           p.titolo AS percorso_titolo,
           e.titolo AS evento_titolo
    FROM segnalazioni_problemi s
    LEFT JOIN percorsi p ON p.id = s.percorso_id
    LEFT JOIN eventi e ON e.id = s.evento_id
";

if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY FIELD(s.stato, 'nuova','in_lavorazione','risolta','archiviata'), s.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$segnalazioni = $stmt->fetchAll();

$msg = trim($_GET['msg'] ?? '');
?>

<?php admin_page_open('Segnalazioni problemi', 'segnalazioni'); ?>

<style>
    .seg-filters {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }

    .seg-card {
        background: #fff;
        box-shadow: 0 6px 20px rgba(0,0,0,.06);
        margin-bottom: 14px;
        padding: 18px;
        display: grid;
        grid-template-columns: 1.2fr .8fr auto;
        gap: 18px;
        align-items: center;
    }

    .seg-title {
        margin: 0 0 6px;
    }

    .seg-meta {
        color: #777;
        font-size: 13px;
        line-height: 1.6;
    }

    .seg-badge {
        display: inline-block;
        padding: 5px 9px;
        background: #eee;
        font-size: 12px;
        font-weight: 700;
        margin-right: 4px;
    }

    .seg-badge.nuova { background: #ffe0e0; }
    .seg-badge.in_lavorazione { background: #fff0c2; }
    .seg-badge.risolta { background: #d9f2df; }
    .seg-badge.archiviata { background: #e7e7e7; }

    @media (max-width: 780px) {
        .seg-card {
            grid-template-columns: 1fr;
        }
    }
</style>

<main class="wrap">
    <h1>Segnalazioni problemi</h1>

    <?php if ($msg): ?>
        <div class="notice"><?= e($msg) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a class="btn secondary" href="index.php">Dashboard</a>
        <a class="btn secondary" href="../segnala-problema.php" target="_blank">Apri pagina pubblica</a>
    </div>

    <div class="seg-filters">
        <a class="btn <?= $stato === '' ? '' : 'secondary' ?>" href="segnalazioni.php">Tutte</a>
        <a class="btn <?= $stato === 'nuova' ? '' : 'secondary' ?>" href="segnalazioni.php?stato=nuova">Nuove</a>
        <a class="btn <?= $stato === 'in_lavorazione' ? '' : 'secondary' ?>" href="segnalazioni.php?stato=in_lavorazione">In lavorazione</a>
        <a class="btn <?= $stato === 'risolta' ? '' : 'secondary' ?>" href="segnalazioni.php?stato=risolta">Risolte</a>
        <a class="btn <?= $stato === 'archiviata' ? '' : 'secondary' ?>" href="segnalazioni.php?stato=archiviata">Archiviate</a>
    </div>

    <?php if (!$segnalazioni): ?>
        <div class="notice">Nessuna segnalazione presente.</div>
    <?php endif; ?>

    <?php foreach ($segnalazioni as $s): ?>
        <article class="seg-card">
            <div>
                <h3 class="seg-title"><?= e($s['titolo']) ?></h3>
                <div class="seg-meta">
                    <strong><?= e($s['codice']) ?></strong> · <?= e($s['categoria']) ?> · <?= e(segnalazione_date($s['created_at'])) ?><br>
                    <?php if (!empty($s['luogo'])): ?>
                        Luogo: <?= e($s['luogo']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($s['percorso_titolo'])): ?>
                        Percorso: <?= e($s['percorso_titolo']) ?><br>
                    <?php endif; ?>
                    <?php if (!empty($s['evento_titolo'])): ?>
                        Evento: <?= e($s['evento_titolo']) ?><br>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <span class="seg-badge <?= e($s['stato']) ?>"><?= e(segnalazione_stato_label($s['stato'])) ?></span>
                <span class="seg-badge"><?= e($s['priorita']) ?></span>
                <?php if (!empty($s['allegato_path'])): ?>
                    <span class="seg-badge">Allegato</span>
                <?php endif; ?>
            </div>

            <div>
                <a class="btn" href="segnalazione.php?id=<?= (int) $s['id'] ?>">Apri</a>
            </div>
        </article>
    <?php endforeach; ?>
</main>

<?php admin_page_close(); ?>
