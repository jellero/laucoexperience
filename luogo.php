<?php
require_once __DIR__ . '/inc/db.php';

$slug = trim($_GET['slug'] ?? '');

if ($slug === '') {
    http_response_code(404);
    exit('Luogo non trovato.');
}

$stmt = $pdo->prepare("
    SELECT *
    FROM luoghi
    WHERE slug = :slug AND pubblicato = 1
    LIMIT 1
");
$stmt->execute(['slug' => $slug]);
$luogo = $stmt->fetch();

if (!$luogo) {
    http_response_code(404);
    exit('Luogo non trovato.');
}

$galleryStmt = $pdo->prepare("
    SELECT *
    FROM luogo_gallery
    WHERE luogo_id = :luogo_id
    ORDER BY ordine ASC, id ASC
");
$galleryStmt->execute(['luogo_id' => $luogo['id']]);
$gallery = $galleryStmt->fetchAll();

$hero = $luogo['cover_image'] ?: 'assets/img/trip4.jpg';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'inc/header.php'; ?>

    <style>
        .place-detail .lead-text {
            font-size: 18px;
            line-height: 1.75;
            color: #555;
            margin-bottom: 30px;
        }

        .place-detail .place-content {
            color: #666;
            line-height: 1.85;
            font-size: 16px;
        }

        .place-detail .place-content p {
            margin-bottom: 18px;
        }

        .place-side-card {
            background: #fff;
            padding: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,.06);
            border: 1px solid rgba(0,0,0,.04);
            margin-bottom: 22px;
        }

        .place-side-card h3 {
            margin-top: 0;
            margin-bottom: 16px;
        }

        .place-info-row {
            border-bottom: 1px solid #eee;
            padding: 11px 0;
            color: #666;
            line-height: 1.55;
        }

        .place-info-row:last-child {
            border-bottom: 0;
        }

        .place-info-row strong {
            display: block;
            color: #222;
            margin-bottom: 4px;
        }

        .place-gallery {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 15px;
            margin-top: 25px;
        }

        .place-gallery a {
            display: block;
            min-height: 190px;
            background-size: cover;
            background-position: center;
        }

        @media (max-width: 767px) {
            .place-gallery {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div id="myloader">
        <span class="loader"><div class="inner-loader"></div></span>
    </div>

    <div id="main-wrap" class="full-width">
        <?php include 'inc/menu.php'; ?>

        <div id="page-content" class="header-static">
            <div id="flexslider" class="fullpage-wrap small">
                <ul class="slides">
                    <li style="background-image:url(<?= e($hero) ?>)">
                        <div class="container text text-center">
                            <h1 class="white margin-bottom-small"><?= e($luogo['titolo']) ?></h1>
                            <p class="heading white"><?= e($luogo['sottotitolo'] ?? '') ?></p>
                        </div>
                        <div class="gradient dark"></div>
                    </li>

                    <ol class="breadcrumb">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="luoghi">Luoghi</a></li>
                        <li class="active"><?= e($luogo['titolo']) ?></li>
                    </ol>
                </ul>
            </div>

            <div id="page-wrap" class="content-section fullpage-wrap place-detail">
                <div class="container text">
                    <div class="row">
                        <div class="col-md-8">
                            <h2 class="margin-bottom-null title line left"><?= e($luogo['titolo']) ?></h2>

                            <?php if (!empty($luogo['sottotitolo'])): ?>
                                <p class="heading left grey margin-bottom"><?= e($luogo['sottotitolo']) ?></p>
                            <?php endif; ?>

                            <?php if (!empty($luogo['excerpt'])): ?>
                                <p class="lead-text"><?= e($luogo['excerpt']) ?></p>
                            <?php endif; ?>

                            <div class="place-content">
                                <?= nl2br(e($luogo['descrizione'] ?? '')) ?>
                            </div>

                            <?php if ($gallery): ?>
                                <h2 class="small padding-onlytop-md">Galleria</h2>

                                <div id="gallery" class="place-gallery">
                                    <?php foreach ($gallery as $img): ?>
                                        <a class="lightbox" href="<?= e($img['image_path']) ?>" style="background-image:url(<?= e($img['image_path']) ?>)" title="<?= e($img['caption'] ?? '') ?>"></a>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-4">
                            <aside class="place-side-card">
                                <h3>Informazioni</h3>

                                <?php if (!empty($luogo['categoria'])): ?>
                                    <div class="place-info-row"><strong>Categoria</strong><?= e($luogo['categoria']) ?></div>
                                <?php endif; ?>

                                <?php if (!empty($luogo['localita'])): ?>
                                    <div class="place-info-row"><strong>Località</strong><?= e($luogo['localita']) ?></div>
                                <?php endif; ?>

                                <?php if (!empty($luogo['periodo_consigliato'])): ?>
                                    <div class="place-info-row"><strong>Periodo consigliato</strong><?= e($luogo['periodo_consigliato']) ?></div>
                                <?php endif; ?>

                                <?php if (!empty($luogo['accessibilita'])): ?>
                                    <div class="place-info-row"><strong>Accessibilità / note di visita</strong><?= nl2br(e($luogo['accessibilita'])) ?></div>
                                <?php endif; ?>

                                <?php if ($luogo['lat'] !== null && $luogo['lng'] !== null): ?>
                                    <div class="place-info-row"><strong>Coordinate</strong><?= e($luogo['lat']) ?>, <?= e($luogo['lng']) ?></div>
                                <?php endif; ?>
                            </aside>

                            <?php if (!empty($luogo['note'])): ?>
                                <aside class="place-side-card">
                                    <h3>Note</h3>
                                    <p><?= nl2br(e($luogo['note'])) ?></p>
                                </aside>
                            <?php endif; ?>

                            <p><a class="btn-alt small" href="luoghi">Torna ai luoghi</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'inc/footer.php'; ?>
    </div>

    <?php include 'inc/scripts.php'; ?>
</body>
</html>
