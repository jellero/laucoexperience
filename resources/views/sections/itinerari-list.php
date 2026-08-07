<?php
require_once LAUCO_ROOT . '/inc/db.php';
require_once LAUCO_ROOT . '/inc/gpx-stats.php';

$tipoPercorso = $tipoPercorso ?? 'piedi';
$soloSpeciali = !empty($soloSpeciali);

if ($soloSpeciali) {
    $titoloPagina = $titoloPagina ?? 'Itinerari speciali';
    $sottotitoloPagina = $sottotitoloPagina ?? 'Percorsi selezionati e consigliati sul territorio';
} else {
    $titoloPagina = $titoloPagina ?? ($tipoPercorso === 'mtb' ? 'Itinerari MTB' : 'Itinerari a piedi');
    $sottotitoloPagina = $sottotitoloPagina ?? 'Percorsi disponibili';
}

if (!function_exists('percorso_value_is_set')) {
    function percorso_value_is_set($value): bool
    {
        return $value !== null && trim((string) $value) !== '';
    }
}

if (!function_exists('percorso_display_stats')) {
    function percorso_display_stats(array $percorso): array
    {
        $stats = gpx_stats($percorso['gpx_file'] ?? null, $percorso['tipo'] ?? 'piedi');

        $length = percorso_value_is_set($percorso['distanza_km'] ?? null)
            ? fmt_it($percorso['distanza_km'], ' km', 2)
            : ($stats['length_label'] ?? '-');

        $ascent = percorso_value_is_set($percorso['dislivello_m'] ?? null)
            ? fmt_it($percorso['dislivello_m'], ' m', 0)
            : ($stats['ascent_label'] ?? '-');

        $time = percorso_value_is_set($percorso['tempo'] ?? null)
            ? trim((string) $percorso['tempo'])
            : (
                percorso_value_is_set($percorso['durata'] ?? null)
                    ? trim((string) $percorso['durata'])
                    : ($stats['duration_label'] ?? '-')
            );

        $difficulty = percorso_value_is_set($percorso['difficolta'] ?? null)
            ? trim((string) $percorso['difficolta'])
            : ($stats['difficulty'] ?? '-');

        $updated = $stats['updated_label'] ?? '-';

        if ($updated === '-' && percorso_value_is_set($percorso['updated_at'] ?? null)) {
            $ts = strtotime((string) $percorso['updated_at']);
            $updated = $ts ? date('d/m/Y', $ts) : '-';
        }

        if ($updated === '-' && percorso_value_is_set($percorso['created_at'] ?? null)) {
            $ts = strtotime((string) $percorso['created_at']);
            $updated = $ts ? date('d/m/Y', $ts) : '-';
        }

        return [
            'length_label' => $length,
            'ascent_label' => $ascent,
            'duration_label' => $time,
            'difficulty' => $difficulty,
            'updated_label' => $updated,
        ];
    }
}

$where = [
    'pubblicato = 1',
];

$params = [];

if ($soloSpeciali) {
    $where[] = 'speciale = 1';
} else {
    $where[] = 'tipo = :tipo';
    $where[] = 'COALESCE(speciale, 0) = 0';
    $params['tipo'] = $tipoPercorso;
}

$sql = "
    SELECT *
    FROM percorsi
    WHERE " . implode(' AND ', $where) . "
    ORDER BY speciale DESC, consigliato DESC, ordine ASC, titolo ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$percorsi = $stmt->fetchAll();

$hero = $percorsi[0]['cover_image'] ?? 'assets/img/trip11.jpg';
?>
<div id="page-content" class="header-static footer-fixed">
    <div id="flexslider" class="fullpage-wrap small">
        <ul class="slides">
            <li style="background-image:url(<?= e($hero) ?>)">
                <div class="container text text-center">
                    <h1 class="white margin-bottom-small"><?= e($titoloPagina) ?></h1>
                    <p class="heading white"><?= e($sottotitoloPagina) ?></p>
                </div>
                <div class="gradient dark"></div>
            </li>
        </ul>
    </div>

    <div id="showcase-treks" class="content-section fullpage-wrap">
        <div class="container">
            <div class="col-md-12 text padding-bottom-null text-center">
                <h2 class="margin-bottom-null title line center"><?= e($titoloPagina) ?></h2>
                <p class="heading center grey margin-bottom-null">
                    <?= $soloSpeciali ? 'Percorsi speciali selezionati dal territorio di Lauco.' : 'Seleziona un percorso per vedere scheda, GPX, mappa e altimetria.' ?>
                </p>
            </div>

            <div class="col-md-12 text" id="treks">
                <?php if (!$percorsi): ?>
                    <p class="text-center">Nessun percorso pubblicato.</p>
                <?php endif; ?>

                <?php foreach ($percorsi as $p): ?>
                    <?php $displayStats = percorso_display_stats($p); ?>

                    <div class="item col-md-6">
                        <div class="showcase-trek">
                            <span class="read">
                                <?= e($displayStats['length_label']) ?>
                            </span>

                            <?php if (!empty($p['speciale'])): ?>
                                <span class="recommended-badge">Speciale</span>
                            <?php elseif (!empty($p['consigliato'])): ?>
                                <span class="recommended-badge">Consigliato</span>
                            <?php endif; ?>

                            <img src="<?= e($p['cover_image'] ?: 'assets/img/trip5.jpg') ?>" alt="<?= e($p['titolo']) ?>">

                            <div class="content text-center">
                                <div class="row margin-leftright-null">
                                    <div class="meta">
                                        <div class="col-md-4 padding-leftright-null">
                                            <h3><?= e($displayStats['ascent_label']) ?></h3>
                                            <h4>Dislivello</h4>
                                        </div>

                                        <div class="col-md-4 padding-leftright-null">
                                            <h3><?= e($displayStats['duration_label']) ?></h3>
                                            <h4>Durata</h4>
                                        </div>

                                        <div class="col-md-4 padding-leftright-null">
                                            <h3><?= e($displayStats['difficulty']) ?></h3>
                                            <h4>Difficoltà</h4>
                                        </div>
                                    </div>

                                    <div class="category">
                                        <h3><?= $p['tipo'] === 'mtb' ? 'MTB' : 'Trekking' ?></h3>
                                    </div>

                                    <div class="info">
                                        <div class="col-md-12 padding-leftright-null">
                                            <h6 class="heading"><?= e($p['titolo']) ?></h6>
                                            <p class="margin-null"><?= e($p['localita'] ?: $p['sottotitolo']) ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <a href="/percorso?slug=<?= urlencode($p['slug']) ?>" class="link" aria-label="<?= e($p['titolo']) ?>"></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
