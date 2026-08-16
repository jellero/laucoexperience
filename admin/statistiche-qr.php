<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/../inc/qr-stats.php';
require_once __DIR__ . '/../inc/download-stats.php';
require_once __DIR__ . '/../inc/page-stats.php';
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
$pageSummary = page_stats_summary($pdo);
$pageDaily = page_stats_daily($pdo, 30);
$pageTop = page_stats_top($pdo, 30, 30);
$pageLanguages = page_stats_languages($pdo, 30);

$deviceLabels = [
    'mobile' => 'Mobile',
    'tablet' => 'Tablet',
    'desktop' => 'Desktop',
    'unknown' => 'Non riconosciuto',
];

function stats_gpx_catalog(PDO $pdo): array
{
    try {
        $rows = $pdo->query(
            "SELECT id, titolo, gpx_file FROM percorsi WHERE gpx_file IS NOT NULL AND gpx_file <> ''"
        )->fetchAll() ?: [];
    } catch (Throwable $e) {
        return [];
    }

    $catalog = [];
    foreach ($rows as $row) {
        $filename = basename(str_replace('\\', '/', (string) ($row['gpx_file'] ?? '')));
        if ($filename === '') {
            continue;
        }

        $catalog[$filename] = [
            'id' => (int) ($row['id'] ?? 0),
            'titolo' => trim((string) ($row['titolo'] ?? '')),
        ];
    }

    return $catalog;
}

function stats_gpx_resource(array $catalog, string $resourceKey): array
{
    $filename = basename(str_replace('\\', '/', trim($resourceKey)));

    if ($filename !== '' && isset($catalog[$filename])) {
        $item = $catalog[$filename];
        return [
            'filename' => $filename,
            'label' => $item['titolo'] !== '' ? $item['titolo'] : $filename,
            'percorso_id' => (int) $item['id'],
            'linked' => true,
        ];
    }

    if (preg_match('/#_([^\.]+)\.gpx$/i', $filename, $matches)) {
        return [
            'filename' => $filename,
            'label' => 'Traccia mappa ' . trim($matches[1]),
            'percorso_id' => 0,
            'linked' => false,
        ];
    }

    return [
        'filename' => $filename !== '' ? $filename : $resourceKey,
        'label' => $filename !== '' ? $filename : $resourceKey,
        'percorso_id' => 0,
        'linked' => false,
    ];
}

$gpxCatalog = stats_gpx_catalog($pdo);

function stats_page_catalog(PDO $pdo): array
{
    $catalog = [
        '/' => 'Home',
        '/mappa' => 'Mappa dei sentieri',
        '/mappa-itinerari' => 'Mappa itinerari',
        '/stato-sentieri' => 'Stato dei sentieri',
        '/segnaletica' => 'Segnaletica',
        '/consigli' => 'Consigli escursionistici',
        '/itinerari-piedi' => 'Itinerari a piedi',
        '/itinerari-mtb' => 'Itinerari MTB',
        '/itinerari-speciali' => 'Itinerari speciali',
        '/luoghi' => 'Luoghi',
        '/frazioni' => 'Frazioni e borgate',
        '/storia' => 'Storia',
        '/natura' => 'Natura',
        '/come-arrivare' => 'Come arrivare',
        '/forra' => 'Forra del Vinadia',
        '/barbecue' => 'Aree barbecue',
        '/eventi' => 'Eventi',
        '/eventi/archivio' => 'Archivio eventi',
        '/contatti' => 'Contatti',
        '/gestione-sentieri' => 'Gestione sentieri',
        '/contribuisci' => 'Contribuisci',
        '/segnala-problema' => 'Segnala problema',
        '/privacy' => 'Privacy Policy',
        '/cookie' => 'Cookie Policy',
    ];
    $entities = [
        ['table' => 'percorsi', 'path' => '/percorso'],
        ['table' => 'luoghi', 'path' => '/luogo'],
        ['table' => 'eventi', 'path' => '/evento'],
    ];
    foreach ($entities as $entity) {
        try {
            $rows = $pdo->query(
                'SELECT slug, titolo FROM ' . $entity['table'] . " WHERE slug IS NOT NULL AND slug <> ''"
            )->fetchAll() ?: [];
            foreach ($rows as $row) {
                $slug = strtolower(trim((string) ($row['slug'] ?? '')));
                $title = trim((string) ($row['titolo'] ?? ''));
                if ($slug !== '' && $title !== '') {
                    $catalog[$entity['path'] . '?slug=' . $slug] = $title;
                }
            }
        } catch (Throwable) {
            continue;
        }
    }
    return $catalog;
}

function stats_page_label(array $catalog, string $pageKey): string
{
    if (isset($catalog[$pageKey])) {
        return (string) $catalog[$pageKey];
    }
    $path = (string) (parse_url($pageKey, PHP_URL_PATH) ?: $pageKey);
    $slug = basename($path);
    return $path === '/' ? 'Home' : ucfirst(str_replace('-', ' ', $slug));
}

$pageCatalog = stats_page_catalog($pdo);

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
        <p>Scansioni QR, download dei contenuti cartografici e accessi aggregati alle pagine. I caricamenti tecnici dei GPX necessari a visualizzare le tracce sulla mappa non vengono conteggiati come download.</p>
    </section>

    <?php if (!$qrSummary['available']): ?>
        <div class="error">Statistiche QR non disponibili: verificare la migrazione <code>20260808_qr_analytics.sql</code>.</div>
    <?php endif; ?>
    <?php if (!$gpxSummary['available'] || !$pdfSummary['available']): ?>
        <div class="error">Statistiche download non ancora disponibili: applicare la migrazione <code>20260811_download_analytics.sql</code>.</div>
    <?php endif; ?>
    <?php if (!$pageSummary['available']): ?>
        <div class="error">Statistiche pagine non ancora disponibili: applicare la migrazione <code>20260816_page_view_analytics.sql</code>.</div>
    <?php endif; ?>

    <section class="dashboard-grid">
        <div class="dashboard-card">
            <small>QR oggi</small>
            <span class="number"><?= (int) $qrSummary['today'] ?></span>
            <p>Scansioni QR</p>
        </div>
        <div class="dashboard-card">
            <small>QR · 30 giorni</small>
            <span class="number"><?= (int) $qrSummary['last30'] ?></span>
            <p>scansioni QR</p>
        </div>
        <div class="dashboard-card">
            <small>GPX oggi</small>
            <span class="number"><?= (int) $gpxSummary['today'] ?></span>
            <p>Download GPX</p>
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
        <div class="dashboard-card">
            <small>Pagine oggi</small>
            <span class="number"><?= (int) $pageSummary['today'] ?></span>
            <p>visualizzazioni</p>
        </div>
        <div class="dashboard-card">
            <small>Pagine · 30 giorni</small>
            <span class="number"><?= (int) $pageSummary['last30'] ?></span>
            <p>visualizzazioni</p>
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
                <tr><td>Visualizzazioni pagine</td><td><strong><?= (int) $pageSummary['total'] ?></strong></td></tr>
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

    <section class="dashboard-columns" style="margin-top:22px">
        <div class="admin-card">
            <h2>Mappa PDF · andamento 30 giorni</h2>
            <?php stats_bar_rows($pdfDaily, 'download_date', 'downloads'); ?>
        </div>
        <div class="admin-card">
            <h2>Pagine · andamento 30 giorni</h2>
            <?php stats_bar_rows($pageDaily, 'view_date', 'views'); ?>
        </div>
    </section>

    <section class="dashboard-columns" style="margin-top:22px">
        <div class="admin-card">
            <h2>Pagine più visitate</h2>
            <p class="hint">Conteggio delle visualizzazioni, non dei visitatori unici.</p>
            <?php if ($pageTop === []): ?>
                <p>Nessun accesso registrato.</p>
            <?php else: ?>
                <div style="overflow-x:auto">
                    <table>
                        <thead><tr><th>Pagina</th><th>30 giorni</th><th>Totale</th></tr></thead>
                        <tbody>
                        <?php foreach ($pageTop as $row): ?>
                            <tr>
                                <td><a href="<?= e($row['page_key']) ?>" target="_blank" rel="noopener"><strong><?= e(stats_page_label($pageCatalog, (string) $row['page_key'])) ?></strong></a><br><small><code><?= e($row['page_key']) ?></code></small></td>
                                <td><?= (int) $row['period_views'] ?></td>
                                <td><?= (int) $row['total_views'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <div class="admin-card">
            <h2>Lingue delle pagine</h2>
            <?php if ($pageLanguages === []): ?>
                <p>Nessun accesso registrato.</p>
            <?php else: ?>
                <table>
                    <thead><tr><th>Lingua</th><th>30 giorni</th><th>Totale</th></tr></thead>
                    <tbody>
                    <?php foreach ($pageLanguages as $row): ?>
                        <tr>
                            <td><?= e(['it' => 'Italiano', 'en' => 'English', 'de' => 'Deutsch', 'sl' => 'Slovenščina'][$row['language']] ?? strtoupper($row['language'])) ?></td>
                            <td><?= (int) $row['period_views'] ?></td>
                            <td><?= (int) $row['total_views'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </section>

    <section class="admin-card" style="margin-top:22px">
        <h2>Itinerari GPX più scaricati</h2>
        <p class="hint">La classifica conta soltanto i click di download. Quando il GPX appartiene a un itinerario del backoffice viene mostrato il nome dell'itinerario; il nome tecnico del file resta visibile solo come riferimento.</p>
        <?php if ($gpxTop === []): ?>
            <p>Nessun download GPX registrato.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Itinerario</th><th>File GPX</th><th>30 giorni</th><th>Totale</th></tr></thead>
                <tbody>
                <?php foreach ($gpxTop as $row): ?>
                    <?php $gpxResource = stats_gpx_resource($gpxCatalog, (string) $row['resource_key']); ?>
                    <tr>
                        <td>
                            <?php if ($gpxResource['linked'] && $gpxResource['percorso_id'] > 0): ?>
                                <a href="percorso-form.php?id=<?= (int) $gpxResource['percorso_id'] ?>"><strong><?= e($gpxResource['label']) ?></strong></a>
                            <?php else: ?>
                                <strong><?= e($gpxResource['label']) ?></strong>
                            <?php endif; ?>
                        </td>
                        <td><code><?= e($gpxResource['filename']) ?></code></td>
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
        <p class="hint">Data/ora e tipo di dispositivo. Per i GPX collegati al backoffice viene mostrato l'itinerario invece del solo nome tecnico del file. L'indirizzo IP non viene raccolto.</p>
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
                            <td>
                                <?php if ($row['download_type'] === 'gpx'): ?>
                                    <?php $gpxResource = stats_gpx_resource($gpxCatalog, (string) $row['resource_key']); ?>
                                    <?php if ($gpxResource['linked'] && $gpxResource['percorso_id'] > 0): ?>
                                        <a href="percorso-form.php?id=<?= (int) $gpxResource['percorso_id'] ?>"><strong><?= e($gpxResource['label']) ?></strong></a><br>
                                        <small><code><?= e($gpxResource['filename']) ?></code></small>
                                    <?php else: ?>
                                        <strong><?= e($gpxResource['label']) ?></strong><br>
                                        <small><code><?= e($gpxResource['filename']) ?></code></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <strong>Mappa Lauco Experience</strong>
                                <?php endif; ?>
                            </td>
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
        <p>Per QR e download vengono registrati <strong>data/ora, user agent e classe dispositivo</strong>. Per gli accessi alle pagine vengono salvati soltanto <strong>giorno, pagina, lingua e conteggio aggregato</strong>. Non viene conservato l'indirizzo IP e non vengono usati cookie analitici per questi conteggi. I log dettagliati di QR e download vengono eliminati dopo 90 giorni; i conteggi giornalieri aggregati restano disponibili per lo storico.</p>
    </section>
</main>
<?php admin_page_close(); ?>
