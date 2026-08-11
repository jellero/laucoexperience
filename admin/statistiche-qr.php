<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/../inc/qr-stats.php';
require_once __DIR__ . '/_admin_layout.php';

$summary = qr_stats_summary($pdo);
$top = qr_stats_top($pdo, 30);
$daily = qr_stats_daily($pdo, 30);
$registry = qr_registry();
$detailAvailable = qr_scan_log_available($pdo);
$recent = $detailAvailable ? qr_stats_recent($pdo, 100) : [];
$retentionDays = qr_scan_log_retention_days();
$gpxDownloads = [];

try {
    $stmt = $pdo->query(
        "SELECT titolo, gpx_file FROM percorsi "
        . "WHERE pubblicato = 1 AND gpx_file IS NOT NULL AND TRIM(gpx_file) <> '' "
        . "ORDER BY ordine ASC, titolo ASC"
    );
    foreach ($stmt->fetchAll() ?: [] as $row) {
        $filename = basename(trim((string) ($row['gpx_file'] ?? '')));
        if ($filename === '' || !preg_match('/\.gpx$/i', $filename)) {
            continue;
        }
        $gpxDownloads[] = [
            'title' => trim((string) ($row['titolo'] ?? '')) ?: $filename,
            'filename' => $filename,
        ];
    }
} catch (Throwable $e) {
    $gpxDownloads = [];
}

$maxDaily = 1;
foreach ($daily as $row) {
    $maxDaily = max($maxDaily, (int) $row['scans']);
}

$deviceLabels = [
    'mobile' => 'Mobile',
    'tablet' => 'Tablet',
    'desktop' => 'Desktop',
    'unknown' => 'Non riconosciuto',
];

admin_page_open('Statistiche QR', 'qr-stats');
?>
<main class="wrap">
    <section class="page-title">
        <h1>Statistiche QR mappa</h1>
        <p>Qui vengono conteggiati soltanto gli accessi che arrivano dal QR fisico su <code>/map</code>. Il menu e i normali link del sito aprono direttamente <code>/mappa</code> e non incrementano queste statistiche.</p>
    </section>

    <?php if (!$summary['available']): ?>
        <div class="error">
            La tabella delle statistiche QR non è ancora disponibile. Applica la migrazione <code>20260808_qr_analytics.sql</code> prima di pubblicare il QR tracciato.
        </div>
    <?php endif; ?>

    <?php if (!$detailAvailable): ?>
        <div class="error">
            Il dettaglio con data/ora e dispositivo sarà disponibile dopo l'applicazione delle nuove migrazioni QR del 11 agosto 2026.
        </div>
    <?php endif; ?>

    <section class="dashboard-grid">
        <div class="dashboard-card">
            <small>Oggi</small>
            <span class="number"><?= (int) $summary['today'] ?></span>
            <p>scansioni del QR mappa</p>
        </div>
        <div class="dashboard-card">
            <small>Ultimi 30 giorni</small>
            <span class="number"><?= (int) $summary['last30'] ?></span>
            <p>scansioni del QR mappa</p>
        </div>
        <div class="dashboard-card">
            <small>Totale storico</small>
            <span class="number"><?= (int) $summary['total'] ?></span>
            <p>scansioni del QR attivo</p>
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
            <p class="hint">Il QR usa <code>/map</code>, registra la scansione e reindirizza alla mappa pubblica.</p>
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
            <p class="hint">Sono incluse solo le scansioni del QR <code>map</code>; gli accessi diretti a <code>/mappa</code> restano fuori dal conteggio.</p>
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
        <h2>Ultime scansioni</h2>
        <p class="hint">Dettaglio delle ultime 100 scansioni del QR mappa. Data/ora, user agent e classificazione del dispositivo vengono conservati per <?= (int) $retentionDays ?> giorni. L'indirizzo IP non viene conservato.</p>
        <?php if (!$detailAvailable): ?>
            <p>Dettaglio non ancora disponibile: applicare le nuove migrazioni QR.</p>
        <?php elseif ($recent === []): ?>
            <p>Nessuna scansione dettagliata registrata.</p>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table>
                    <thead><tr><th>Data e ora</th><th>Dispositivo</th><th>User agent</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent as $scan): ?>
                        <tr>
                            <td style="white-space:nowrap"><?= e($scan['scanned_at']) ?></td>
                            <td><?= e($deviceLabels[$scan['device_type']] ?? ucfirst($scan['device_type'])) ?></td>
                            <td style="max-width:620px;word-break:break-word"><small><?= e($scan['user_agent'] !== '' ? $scan['user_agent'] : '-') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-card" style="margin-top:22px">
        <h2>Download mappa e GPX</h2>
        <p class="hint">La mappa PDF usa la vista di stampa della mappa pubblica; dal dialogo del browser scegli “Salva come PDF”. Le tracce GPX sono quelle dei percorsi pubblicati.</p>
        <p>
            <a class="btn" href="../mappa?print=1" target="_blank" rel="noopener noreferrer">Mappa PDF / stampa</a>
            <a class="btn secondary" href="../mappa" target="_blank" rel="noopener noreferrer">Apri mappa</a>
        </p>
        <?php if ($gpxDownloads === []): ?>
            <p>Nessuna traccia GPX pubblicata disponibile.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Percorso</th><th>File</th><th>Download</th></tr></thead>
                <tbody>
                <?php foreach ($gpxDownloads as $gpx): ?>
                    <tr>
                        <td><?= e($gpx['title']) ?></td>
                        <td><code><?= e($gpx['filename']) ?></code></td>
                        <td><a class="btn secondary" href="../gpx/<?= e(rawurlencode($gpx['filename'])) ?>">Scarica GPX</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section class="admin-card" style="margin-top:22px">
        <h2>URL del QR</h2>
        <p class="hint">Il QR fisico deve continuare a contenere <code>/map</code>. Il menu del sito usa invece <code>/mappa</code>.</p>
        <table>
            <thead><tr><th>Etichetta</th><th>Codice</th><th>URL nel QR</th><th>Destinazione finale</th></tr></thead>
            <tbody>
            <?php foreach ($registry as $code => $definition): ?>
                <?php $entry = (string) ($definition['entry'] ?? ('/qr?c=' . rawurlencode($code))); ?>
                <tr>
                    <td><?= e((string) ($definition['label'] ?? $code)) ?></td>
                    <td><code><?= e($code) ?></code></td>
                    <td><code><?= e($entry) ?></code></td>
                    <td><a href="<?= e((string) ($definition['destination'] ?? '/')) ?>" target="_blank" rel="noopener noreferrer"><?= e((string) ($definition['destination'] ?? '/')) ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="admin-card" style="margin-top:22px">
        <h2>Come funziona</h2>
        <p><strong>QR fisico:</strong> <code>/map</code> → registra data/ora e dati tecnici del dispositivo, senza conservare l'IP → redirect a <code>/mappa</code>.</p>
        <p><strong>Menu e link:</strong> <code>/mappa</code> → apertura diretta della mappa → nessuna registrazione nelle statistiche QR.</p>
    </section>

    <section class="admin-card" style="margin-top:22px">
        <h2>Dati e conservazione</h2>
        <p>Il log QR registra <strong>data e ora, user agent e classe dispositivo</strong>. Non conserva l'indirizzo IP, non usa cookie analitici e non salva la geolocalizzazione. I dettagli vengono conservati per <?= (int) $retentionDays ?> giorni; i conteggi aggregati giornalieri restano disponibili per le statistiche storiche.</p>
        <p><a class="btn secondary" href="../privacy" target="_blank">Privacy</a> <a class="btn secondary" href="../cookie" target="_blank">Cookie</a></p>
    </section>
</main>
<?php admin_page_close(); ?>
