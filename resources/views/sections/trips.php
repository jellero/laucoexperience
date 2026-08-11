<?php
/**
 * Homepage - Percorsi consigliati.
 *
 * Mostra solo percorsi:
 * - pubblicati;
 * - marcati come consigliati nel backoffice.
 *
 * Richiede:
 * - inc/db.php
 * - inc/gpx-stats.php
 * - tabella percorsi con colonna consigliato
 */

require_once LAUCO_ROOT . '/inc/db.php';

if (file_exists(LAUCO_ROOT . '/inc/gpx-stats.php')) {
    require_once LAUCO_ROOT . '/inc/gpx-stats.php';
}

if (!function_exists('home_gpx_stats_fallback')) {
    function home_gpx_stats_fallback(array $percorso): array
    {
        if (function_exists('gpx_stats')) {
            return gpx_stats($percorso['gpx_file'] ?? null, $percorso['tipo'] ?? 'piedi');
        }

        return [
            'length_label' => !empty($percorso['distanza_km']) ? fmt_it($percorso['distanza_km'], ' km') : '-',
            'ascent_label' => !empty($percorso['dislivello_m']) ? fmt_it($percorso['dislivello_m'], ' m', 0) : '-',
            'duration_label' => !empty($percorso['durata']) ? (string) $percorso['durata'] : '-',
            'difficulty' => !empty($percorso['difficolta']) ? (string) $percorso['difficolta'] : '-',
        ];
    }
}

$stmt = $pdo->query("
    SELECT *
    FROM percorsi
    WHERE pubblicato = 1
      AND consigliato = 1
    ORDER BY ordine ASC, created_at DESC
    LIMIT 6
");

$percorsiConsigliati = $stmt->fetchAll();
?>

<div id="showcase-treks" class="text padding-bottom-null grey-background center">
    <div class="container">
        <div class="col-md-12 padding-leftright-null">

            <div class="col-md-12 text padding-bottom-null text-center">
                <h2 class="margin-bottom-null title line center">Percorsi consigliati</h2>
                <p class="heading center grey margin-bottom-null">
                    Una selezione di itinerari consigliati a Lauco, con traccia GPX, mappa e dati tecnici automatici.
                </p>
            </div>

            <div class="col-md-12 text" id="treks">
                <?php if (!$percorsiConsigliati): ?>
                    <p class="text-center margin-bottom-null">
                        Al momento non ci sono percorsi consigliati pubblicati.
                    </p>
                <?php endif; ?>

                <?php foreach ($percorsiConsigliati as $percorso): ?>
                    <?php
                        $stats = home_gpx_stats_fallback($percorso);
                        $cover = $percorso['cover_image'] ?: 'assets/img/trip5.jpg';
                        $tipoLabel = $percorso['tipo'] === 'mtb' ? 'MTB' : 'Trekking';
                    ?>

                    <div class="item col-md-6">
                        <div class="showcase-trek">
                            <span class="read">
                                <?= e($stats['length_label']) ?>
                            </span>

                            <img class="itinerary-card-image" src="<?= e($cover) ?>" alt="<?= e($percorso['titolo']) ?>">

                            <div class="content text-center">
                                <div class="row margin-leftright-null">

                                    <div class="meta">
                                        <div class="col-md-4 padding-leftright-null">
                                            <h3><?= e($stats['ascent_label']) ?></h3>
                                            <h4>Dislivello</h4>
                                        </div>

                                        <div class="col-md-4 padding-leftright-null">
                                            <h3><?= e($stats['duration_label']) ?></h3>
                                            <h4>Durata</h4>
                                        </div>

                                        <div class="col-md-4 padding-leftright-null">
                                            <h3><?= e($stats['difficulty']) ?></h3>
                                            <h4>Difficoltà</h4>
                                        </div>
                                    </div>

                                    <div class="category">
                                        <h3><?= e($tipoLabel) ?></h3>
                                    </div>

                                    <div class="info">
                                        <div class="col-md-12 padding-leftright-null">
                                            <h6 class="heading"><?= e($percorso['titolo']) ?></h6>
                                            <p class="margin-null">
                                                <?= e($percorso['localita'] ?: $percorso['sottotitolo']) ?>
                                            </p>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <a href="/percorso?slug=<?= urlencode($percorso['slug']) ?>" class="link" aria-label="<?= e($percorso['titolo']) ?>"></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($percorsiConsigliati): ?>
                <div class="col-md-12 text text-center padding-top-null">
                    <a href="/itinerari-piedi" class="btn-alt small margin-null">Itinerari a piedi</a>
                    <a href="/itinerari-mtb" class="btn-alt small margin-null active">Itinerari MTB</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>
