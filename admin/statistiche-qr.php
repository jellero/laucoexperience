<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/../inc/qr-stats.php';
require_once __DIR__ . '/_admin_layout.php';

$summary = qr_stats_summary($pdo);
$top = qr_stats_top($pdo, 30);
$daily = qr_stats_daily($pdo, 30);
$registry = qr_registry();
$maxDaily = 1;
foreach ($daily as $row) {
    $maxDaily = max($maxDaily, (int) $row['scans']);
}

admin_page_open('Statistiche QR', 'qr-stats');
?>
<main class="wrap">
    <section class="page-title">
        <h1>Statistiche QR mappa</h1>
        <p>Qui vengono conteggiati soltanto gli accessi alla mappa provenienti dal QR fisico. I click dal menu e i link diretti a <code>/map</code> non vengono registrati da questo sistema.</p>
    </section>

    <?php if (!$summary['available']): ?>
        <div class="error">
            La tabella delle statistiche QR non è ancora disponibile. Applica la migrazione <code>20260808_qr_analytics.sql</code> prima di pubblicare il QR tracciato.
        </div>
    <?php endif; ?>

    <section class="dashboard-grid">
        <div class="dashboard-card">
            <small>Oggi</small>
            <span class="number"><?= (int) $summary['today'] ?></span>
            <p>accessi da QR</p>
        </div>
        <div class="dashboard-card">
            <small>Ultimi 30 giorni</small>
            <span class="number"><?= (int) $summary['last30'] ?></span>
            <p>accessi da QR</p>
        </div>
        <div class="dashboard-card">
            <small>Totale storico</small>
            <span class="number"><?= (int) $summary['total'] ?></span>
            <p>accessi da QR attivo</p>
        </div>
        <div class="dashboard-card">
            <small>QR attivi</small>
            <span class="number"><?= count($registry) ?></span>
            <p>QR ufficiale della mappa</p>
        </div>
    </section>

    <section class="dashboard-columns">
        <div class="admin-card">
            <h2>QR ufficiale</h2>
            <p class="hint">Il QR usa un URL tracciato e viene poi reindirizzato alla stessa mappa aperta dal menu.</p>
            <table>
                <thead><tr><th>QR</th><th>30 giorni</th><th>Totale</th></tr></thead>
                <tbody>
                <?php if ($top === []): ?>
                    <tr><td colspan="3">Nessuna scansione registrata.</td></tr>
                <?php else: ?>
                    <?php foreach ($top as $row):
                        $definition = $registry[$row['qr_code']] ?? null;
                        $label = is_array($definition) ? (string) ($definition['label'] ?? $row['qr_code']) : $row['qr_code'];
                    ?>
                        <tr>
                            <td><strong><?= e($label) ?></strong><br><small><?= e($row['qr_code']) ?></small></td>
                            <td><?= (int) $row['period_scans'] ?></td>
                            <td><?= (int) $row['total_scans'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="admin-card">
            <h2>Andamento 30 giorni</h2>
            <p class="hint">Sono incluse solo le scansioni del QR attivo; gli accessi diretti alla mappa restano fuori da questo conteggio.</p>
            <?php if ($daily === []): ?>
                <p>Nessuna scansione registrata.</p>
            <?php else: ?>
                <div style="display:grid;gap:8px;margin-top:20px">
                <?php foreach ($daily as $row):
                    $width = max(2, (int) round(((int) $row['scans'] / $maxDaily) * 100));
                ?>
                    <div style="display:grid;grid-template-columns:92px 1fr 48px;gap:10px;align-items:center">
                        <small><?= e($row['scan_date']) ?></small>
                        <div style="height:10px;background:#eee"><div style="height:10px;background:#222;width:<?= $width ?>%"></div></div>
                        <strong><?= (int) $row['scans'] ?></strong>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="admin-card" style="margin-top:22px">
        <h2>URL del QR</h2>
        <p class="hint">Nel QR fisico usa esclusivamente l’URL tracciato. Menu e normali collegamenti del sito devono invece continuare a puntare direttamente a <code>/map</code>.</p>
        <table>
            <thead><tr><th>Etichetta</th><th>Codice</th><th>URL da codificare nel QR</th><th>Destinazione finale</th></tr></thead>
            <tbody>
            <?php foreach ($registry as $code => $definition): ?>
                <tr>
                    <td><?= e((string) ($definition['label'] ?? $code)) ?></td>
                    <td><code><?= e($code) ?></code></td>
                    <td><code>/qr?c=<?= e(rawurlencode($code)) ?></code></td>
                    <td><a href="<?= e((string) ($definition['destination'] ?? '/')) ?>" target="_blank" rel="noopener noreferrer"><?= e((string) ($definition['destination'] ?? '/')) ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="admin-card" style="margin-top:22px">
        <h2>Come funziona</h2>
        <p><strong>QR fisico:</strong> <code>/qr?c=map</code> → registra una scansione anonima → redirect a <code>/map</code>.</p>
        <p><strong>Menu e link:</strong> <code>/map</code> → apertura diretta della mappa → nessuna registrazione nelle statistiche QR.</p>
    </section>

    <section class="admin-card" style="margin-top:22px">
        <h2>Privacy by design</h2>
        <p>La misurazione QR non salva indirizzi IP, user agent, geolocalizzazione, identificativi del dispositivo o cookie analitici. I dati sono aggregati direttamente per <strong>giorno + codice QR</strong>.</p>
        <p><a class="btn secondary" href="../privacy" target="_blank">Privacy</a> <a class="btn secondary" href="../cookie" target="_blank">Cookie</a></p>
    </section>
</main>
<?php admin_page_close(); ?>
