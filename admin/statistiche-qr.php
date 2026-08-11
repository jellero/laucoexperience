<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/../inc/qr-stats.php';
require_once __DIR__ . '/../inc/download-stats.php';
require_once __DIR__ . '/_admin_layout.php';

$qrSummary = qr_stats_summary($pdo);
$qrDaily = qr_stats_daily($pdo, 30);
$qrDetailAvailable = qr_scan_log_available($pdo);
$qrRecent = $qrDetailAvailable ? qr_stats_recent($pdo, 50) : [];

$gpxSummary = download_stats_summary($pdo, 'gpx');
$pdfSummary = download_stats_summary($pdo, 'map_pdf');
$gpxDaily = download_stats_daily($pdo, 'gpx', 30);
$pdfDaily = download_stats_daily($pdo, 'map_pdf', 30);
$gpxTop = download_stats_top($pdo, 'gpx', 30, 20);
$downloadDetailAvailable = download_log_available($pdo);
$downloadRecent = $downloadDetailAvailable ? download_stats_recent($pdo, 100) : [];

$deviceLabels = [
    'mobile' => 'Mobile',
    'tablet' => 'Tablet',
    'desktop' => 'Desktop',
    'unknown' => 'Non riconosciuto',
];

function stats_bar_rows(array $rows, string $dateKey, string $valueKey): void
{
    if ($rows === []) {
        echo '<p>Nessun dato registrato.</p>';
        return;
    }

    $max = 1;
    foreach ($rows as $row) {
        $max = max($max, (int) ($row[$valueKey] ?? 0));
    }

    echo '<div style="display:grid;gap:8px;margin-top:18px">';
    foreach ($rows as $row) {
        $value = (int) ($row[$valueKey] ?? 0);
        $width = max(2, (int) round(($value / $max) * 100));
        echo '<div style="display:grid;grid-template-columns:92px 1fr 48px;gap:10px;align-items:center">';
        echo '<small>' . e((string) ($row[$dateKey] ?? '')) . '</small>';
        echo '<div style="height:10px;background:#eee"><div style="height:10px;background:#222;width:' . $width . '%"></div></div>';
        echo '<strong>' . $value . '</strong>';
        echo '</div>';
    }
    echo '</div>';
}

admin_page_open('Statistiche', 'qr-stats');
?>
<main class="wrap">
    <section class="page-title">
        <h1>Statistiche</h1>
        <p>Scansioni del QR della mappa e scaricamenti dei contenuti cartografici. I caricamenti tecnici dei GPX necessari a visualizzare le tracce sulla mappa non vengono conteggiati come download.</p>
    </section>

    <?php if (!$qrSummary['available']): ?>
        <div class="error">Statistiche QR non disponibili: verificare la migrazione <code>20260808_qr_analytics.sql</code>.</div>
    <?php endif; ?>
    <?php if (!$gpxSummary['available'] || !$pdfSummary['available']): ?>
        <div class="error">Statistiche download non ancora disponibili: applicare la migrazione <code>20260811_download_analytics.sql</code>.</div>
    <?php endif; ?>

    <section class="dashboard-grid">
        <div class="dashboard-card">
            <small>QR oggi</small>
            <span class="number"><?= (int) $qrSummary['today'] ?></span>
            <p>scansioni da <code>/map</code></p>
        </div>
        <div class="dashboard-card">
            <small>QR · 30 giorni</small>
            <span class="number"><?= (int) $qrSummary['last30'] ?></span>
            <p>scansioni QR</p>
        </div>
        <div class="dashboard-card">
            <small>GPX oggi</small>
            <span class="number"><?= (int) $gpxSummary['today'] ?></span>
            <p>download espliciti</p>
        </div>
        <div class="dashboard-card">
            <small>GPX · 30 giorni</small>
            <span class="number"><?= (int) $gpxSummary['last30'] ?></span>
            <p>download GPX</p>
        </div>
        <div class="dashboard-card">
            <small>Mappa PDF oggi</small>
            <span class="number"><?= (int) $pdfSummary['today'] ?></span>
            <p>richieste PDF</p>
        </div>
        <div class="dashboard-card">
            <small>Mappa PDF · 30 giorni</small>
            <span class="number"><?= (int) $pdfSummary['last30'] ?></span>
            <p>richieste PDF</p>
        </div>
    </section>

    <section class="admin-card" style="margin-top:22px">
        <h2>Totali storici</h2>
        <table>
            <thead><tr><th>Voce</th><th>Totale</th></tr></thead>
            <tbody>
                <tr><td>Scansioni QR mappa</td><td><strong><?= (int) $qrSummary['total'] ?></strong></td></tr>
                <tr><td>Download GPX</td><td><strong><?= (int) $gpxSummary['total'] ?></strong></td></tr>
                <tr><td>Mappa PDF</td><td><strong><?= (int) $pdfSummary['total'] ?></strong></td></tr>
            </tbody>
        </table>
    </section>

    <section class="dashboard-columns" style="margin-top:22px">
        <div class="admin-card">
            <h2>QR · andamento 30 giorni</h2>
            <?php stats_bar_rows($qrDaily, 'scan_date', 'scans'); ?>
        </div>
        <div class="admin-card">
            <h2>GPX · andamento 30 giorni</h2>
            <?php stats_bar_rows($gpxDaily, 'download_date', 'downloads'); ?>
        </div>
    </section>

    <section class="admin-card" style="margin-top:22px">
        <h2>Mappa PDF · andamento 30 giorni</h2>
        <?php stats_bar_rows($pdfDaily, 'download_date', 'downloads'); ?>
    </section>

    <section class="admin-card" style="margin-top:22px">
        <h2>GPX più scaricati</h2>
        <p class="hint">La classifica conta soltanto i click di download; le richieste usate dalla mappa per visualizzare una traccia sono escluse.</p>
        <?php if ($gpxTop === []): ?>
            <p>Nessun download GPX registrato.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>File GPX</th><th>30 giorni</th><th>Totale</th></tr></thead>
                <tbody>
                <?php foreach ($gpxTop as $row): ?>
                    <tr>
                        <td><code><?= e($row['resource_key']) ?></code></td>
                        <td><?= (int) $row['period_downloads'] ?></td>
                        <td><?= (int) $row['total_downloads'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section class="admin-card" style="margin-top:22px">
        <h2>Ultimi scaricamenti</h2>
        <p class="hint">Data/ora e tipo di dispositivo. L'indirizzo IP non viene raccolto.</p>
        <?php if (!$downloadDetailAvailable): ?>
            <p>Dettaglio non ancora disponibile.</p>
        <?php elseif ($downloadRecent === []): ?>
            <p>Nessuno scaricamento registrato.</p>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table>
                    <thead><tr><th>Data e ora</th><th>Tipo</th><th>Risorsa</th><th>Dispositivo</th></tr></thead>
                    <tbody>
                    <?php foreach ($downloadRecent as $row): ?>
                        <tr>
                            <td style="white-space:nowrap"><?= e($row['downloaded_at']) ?></td>
                            <td><?= $row['download_type'] === 'map_pdf' ? 'Mappa PDF' : 'GPX' ?></td>
                            <td><code><?= e($row['resource_key']) ?></code></td>
                            <td><?= e($deviceLabels[$row['device_type']] ?? ucfirst($row['device_type'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-card" style="margin-top:22px">
        <h2>Ultime scansioni QR</h2>
        <p class="hint">Il QR fisico usa <code>/map</code>; gli accessi normali a <code>/mappa</code> restano esclusi. L'indirizzo IP non viene raccolto.</p>
        <?php if (!$qrDetailAvailable): ?>
            <p>Dettaglio non ancora disponibile.</p>
        <?php elseif ($qrRecent === []): ?>
            <p>Nessuna scansione registrata.</p>
        <?php else: ?>
            <div style="overflow-x:auto">
                <table>
                    <thead><tr><th>Data e ora</th><th>Dispositivo</th></tr></thead>
                    <tbody>
                    <?php foreach ($qrRecent as $scan): ?>
                        <tr>
                            <td style="white-space:nowrap"><?= e($scan['scanned_at']) ?></td>
                            <td><?= e($deviceLabels[$scan['device_type']] ?? ucfirst($scan['device_type'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-card" style="margin-top:22px">
        <h2>Dati registrati</h2>
        <p>Per QR e download vengono registrati <strong>data/ora, user agent e classe dispositivo</strong>. Non viene conservato l'indirizzo IP e non vengono usati cookie analitici per questi conteggi. I log dettagliati vengono eliminati dopo 90 giorni; i conteggi giornalieri aggregati restano disponibili per lo storico.</p>
    </section>
</main>
<?php admin_page_close(); ?>
