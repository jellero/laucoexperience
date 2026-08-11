<?php
require_once LAUCO_ROOT . '/inc/db.php';
require_once LAUCO_ROOT . '/inc/gpx-stats.php';

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


if (!function_exists('percorso_download_filename')) {
    function percorso_download_filename(string $title): string
    {
        $name = strtolower(trim($title));
        $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
        $name = preg_replace('/[^a-z0-9]+/', '-', $name);
        $name = trim($name, '-');

        if ($name === '') {
            $name = 'percorso';
        }

        return $name . '.gpx';
    }
}

$slug = trim($_GET['slug'] ?? '');

if ($slug === '') {
    http_response_code(404);
    return;
}

$stmt = $pdo->prepare('SELECT * FROM percorsi WHERE slug = :slug AND pubblicato = 1 LIMIT 1');
$stmt->execute(['slug' => $slug]);
$percorso = $stmt->fetch();

if (!$percorso) {
    http_response_code(404);
    return;
}

$displayStats = percorso_display_stats($percorso);

$stmt = $pdo->prepare('SELECT * FROM percorso_gallery WHERE percorso_id = :id ORDER BY sort_order ASC, id ASC');
$stmt->execute(['id' => $percorso['id']]);
$gallery = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT *
    FROM percorsi
    WHERE tipo = :tipo
      AND pubblicato = 1
      AND id <> :id
    ORDER BY consigliato DESC, ordine ASC, titolo ASC
    LIMIT 2
");
$stmt->execute([
    'tipo' => $percorso['tipo'],
    'id' => $percorso['id'],
]);
$simili = $stmt->fetchAll();

$cover = $percorso['cover_image'] ?: 'assets/img/trip11.jpg';
$tipoLabel = $percorso['tipo'] === 'mtb' ? 'MTB' : 'A piedi';
$percorsoCssVersion = (string) (filemtime(LAUCO_ROOT . '/assets/css/percorso.css') ?: 1);
$percorsoJsVersion = (string) (filemtime(LAUCO_ROOT . '/assets/js/percorso-map.js') ?: 1);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>

    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet-gesture-handling/dist/leaflet-gesture-handling.min.css">
    <link rel="stylesheet" href="https://unpkg.com/@raruto/leaflet-elevation@2.5.2/dist/leaflet-elevation.min.css">
    <link rel="stylesheet" href="/assets/css/percorso.css?v=<?= e($percorsoCssVersion) ?>">

    <style>
        .zcl{
            z-index:999;
        }
        .trek-map-section {
            position: relative;
            z-index: 1;
            clear: both;
            overflow: visible;
            padding-bottom: 70px;
        }

        .trek-map-section #percorso-map {
            min-height: 560px;
        }

        .trek-map-section #percorso-elevation {
            position: relative;
            z-index: 2;
            min-height: 260px;
            margin-bottom: 45px;
            background: #fff;
            clear: both;
        }

        .percorsi-simili-section {
            position: relative;
            z-index: 1;
            clear: both;
            margin-top: 270px;
        }

        @media (max-width: 767px) {
            .trek-map-section {
                padding-bottom: 45px;
            }

            .trek-map-section #percorso-map {
                min-height: 420px;
            }

            .trek-map-section #percorso-elevation {
                min-height: 220px;
                margin-bottom: 30px;
            }

            .percorsi-simili-section {
                padding-top: 45px;
            }
        }
    </style>
</head>
<body>
    <div id="myloader">
        <span class="loader"><div class="inner-loader"></div></span>
    </div>

    <div id="main-wrap" class="full-width">
        <?php require LAUCO_VIEW_PATH . '/partials/menu.php'; ?>

        <div id="page-content" class="header-static footer-fixed">
            <div id="flexslider" class="fullpage-wrap small">
                <ul class="slides">
                    <li style="background-image:url(<?= e($cover) ?>)">
                        <div class="container text text-center">
                            <h1 class="white margin-bottom-small"><?= e($percorso['titolo']) ?></h1>
                            <?php if (!empty($percorso['sottotitolo'])): ?>
                                <p class="heading white"><?= e($percorso['sottotitolo']) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="gradient dark"></div>
                    </li>

                    <ol class="breadcrumb">
                        <li>
                            <a href="<?= $percorso['tipo'] === 'mtb' ? '/itinerari-mtb' : '/itinerari-piedi' ?>">
                                <?= $percorso['tipo'] === 'mtb' ? 'Itinerari MTB' : 'Itinerari a piedi' ?>
                            </a>
                        </li>
                        <li class="active"><?= e($percorso['titolo']) ?></li>
                    </ol>
                </ul>
            </div>

            <div id="post-wrap" class="content-section fullpage-wrap">
                <div class="row margin-leftright-null">
                    <div class="container">
                        <div class="col-lg-7 padding-leftright-null">
                            <div class="text padding-lg-bottom-null">
                                <h2 class="margin-bottom-null title line left"><?= e($percorso['titolo']) ?></h2>

                                <?php if (!empty($percorso['localita'])): ?>
                                    <p class="heading left grey margin-bottom-null"><?= e($percorso['localita']) ?></p>
                                <?php endif; ?>

                                <?php if (!empty($percorso['excerpt'])): ?>
                                    <div class="padding-onlytop-md">
                                        <p><?= nl2br(e($percorso['excerpt'])) ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-lg-5 padding-leftright-null">
                            <div class="text padding-md-top-null height-auto-lg">
                                <div class="grey-background box-shadow text responsive-padding-sm">
                                    <h3 class="margin-bottom-small">Dati percorso</h3>
                                    <p><strong>Tipo:</strong> <?= e($tipoLabel) ?></p>

                                    <?php if (!empty($percorso['consigliato'])): ?>
                                        <p><strong>Consigliato:</strong> Sì</p>
                                    <?php endif; ?>

                                    <p><strong>Data aggiornamento:</strong> <?= e($displayStats['updated_label']) ?></p>
                                    <p><strong>Difficoltà:</strong> <?= e($displayStats['difficulty']) ?></p>
                                    <p><strong>Tempo percorrenza:</strong> <?= e($displayStats['duration_label']) ?></p>
                                    <p><strong>Lunghezza:</strong> <?= e($displayStats['length_label']) ?></p>
                                    <p><strong>Dislivello:</strong> <?= e($displayStats['ascent_label']) ?></p>

                                                                            <?php if (!empty($percorso['gpx_file'])): ?>
                                            <?php $gpxDownloadName = percorso_download_filename($percorso['titolo']); ?>
                                            <div class="gpx-download-wrap">
                                                <a
                                                    class="btn-alt small active margin-null gpx-download-btn zcl"
                                                    href="<?= e($percorso['gpx_file']) ?>"
                                                    download="<?= e($gpxDownloadName) ?>"
                                                    target="_blank"
                                                    rel="noopener"
                                                >Scarica GPX</a>
                                            </div>
                                        <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row margin-leftright-null grey-background">
                    <div class="bg-img overlay simple-parallax responsive" style="background-image:url(<?= e($cover) ?>)">
                        <div class="container">
                            <div class="text trek-data text-center">
                                <div class="col-sm-4 col-md-2">
                                    <i class="pd-icon-hour service big margin-bottom-null white"></i>
                                    <em class="color">Data aggiornamento</em>
                                    <h3 class="white"><?= e($displayStats['updated_label']) ?></h3>
                                </div>

                                <div class="col-sm-4 col-md-2">
                                    <i class="pd-icon-camp-bag service big margin-bottom-null white"></i>
                                    <em class="color">Difficoltà</em>
                                    <h3 class="white"><?= e($displayStats['difficulty']) ?></h3>
                                </div>

                                <div class="col-sm-4 col-md-2">
                                    <i class="pd-icon-watch service big margin-bottom-null white"></i>
                                    <em class="color">Tempo percorrenza</em>
                                    <h3 class="white"><?= e($displayStats['duration_label']) ?></h3>
                                </div>

                                <div class="col-sm-4 col-md-2">
                                    <i class="pd-icon-distance service big margin-bottom-null white"></i>
                                    <em class="color">Lunghezza</em>
                                    <h3 class="white"><?= e($displayStats['length_label']) ?></h3>
                                </div>

                                <div class="col-sm-4 col-md-2">
                                    <i class="ion-ios-analytics-outline service big margin-bottom-null white"></i>
                                    <em class="color">Dislivello</em>
                                    <h3 class="white"><?= e($displayStats['ascent_label']) ?></h3>
                                </div>

                                <div class="col-sm-4 col-md-2">
                                    <i class="pd-icon-male service big margin-bottom-null white"></i>
                                    <em class="color">Tipo</em>
                                    <h3 class="white"><?= e($tipoLabel) ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($percorso['descrizione'])): ?>
                    <div class="row margin-leftright-null">
                        <div class="container">
                            <div class="col-md-12 text padding-bottom-null text-center">
                                <h2 class="margin-bottom-null title line center">Descrizione</h2>
                            </div>
                            <div class="col-md-12 text">
                                <p><?= nl2br(e($percorso['descrizione'])) ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($gallery): ?>
                    <div class="row margin-leftright-null">
                        <div class="container text padding-md-topbottom-null">
                            <div class="col-md-12 text padding-bottom-null text-center">
                                <h2 class="margin-bottom-null title line center">Gallery</h2>
                            </div>

                            <section class="grid-images padding-top-null percorso-gallery-grid">
                                <?php foreach (array_chunk($gallery, 2) as $row): ?>
                                    <div class="row padding-onlytop-sm">
                                        <?php foreach ($row as $img): ?>
                                            <div class="col-md-6">
                                                <div class="image simple-shadow" style="background-image:url(<?= e($img['image_path']) ?>)">
                                                    <a class="lightbox-image" href="<?= e($img['image_path']) ?>" title="<?= e($img['alt'] ?: $percorso['titolo']) ?>"></a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>
                            </section>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($percorso['gpx_file'])): ?>
                    <div class="row margin-leftright-null trek-map trek-map-section">
                        <div class="container-fluid padding-leftright-null">
                            <div id="percorso-map" data-gpx="<?= e($percorso['gpx_file']) ?>"></div>
                            <div id="percorso-elevation"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($simili): ?>
                    <div id="showcase-treks" class="row margin-leftright-null grey-background percorsi-simili-section">
                        <div class="container">
                            <div class="col-md-12 text padding-bottom-null text-center">
                                <h2 class="margin-bottom-null title line center">Percorsi simili</h2>
                            </div>

                            <div class="col-md-12 text" id="treks">
                                <?php foreach ($simili as $p): ?>
                                    <?php $similarStats = percorso_display_stats($p); ?>

                                    <div class="item col-md-6">
                                        <div class="showcase-trek">
                                            <span class="read"><?= e($similarStats['length_label']) ?></span>

                                            <?php if (!empty($p['consigliato'])): ?>
                                                <span class="recommended-badge">Consigliato</span>
                                            <?php endif; ?>

                                            <img class="itinerary-card-image" src="<?= e($p['cover_image'] ?: 'assets/img/trip5.jpg') ?>" alt="<?= e($p['titolo']) ?>">

                                            <div class="content text-center">
                                                <div class="row margin-leftright-null">
                                                    <div class="meta">
                                                        <div class="col-md-4 padding-leftright-null">
                                                            <h3><?= e($similarStats['ascent_label']) ?></h3>
                                                            <h4>Dislivello</h4>
                                                        </div>

                                                        <div class="col-md-4 padding-leftright-null">
                                                            <h3><?= e($similarStats['duration_label']) ?></h3>
                                                            <h4>Durata</h4>
                                                        </div>

                                                        <div class="col-md-4 padding-leftright-null">
                                                            <h3><?= e($similarStats['difficulty']) ?></h3>
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
                <?php endif; ?>
            </div>
        </div>

        <?php require LAUCO_VIEW_PATH . '/partials/footerf.php'; ?>
    </div>

    <?php require LAUCO_VIEW_PATH . '/partials/scripts.php'; ?>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-gesture-handling/dist/leaflet-gesture-handling.min.js"></script>
    <script src="https://unpkg.com/@tmcw/togeojson@5.8.1/dist/togeojson.umd.js"></script>
    <script src="https://unpkg.com/@turf/turf@6.5.0/turf.min.js"></script>
    <script src="https://unpkg.com/d3@6.5.0/dist/d3.min.js"></script>
    <script src="https://unpkg.com/@raruto/leaflet-elevation@2.5.2/dist/leaflet-elevation.min.js"></script>
    <script src="/assets/js/percorso-map.js?v=<?= e($percorsoJsVersion) ?>"></script>
</body>
</html>
