<?php
require_once LAUCO_ROOT . '/inc/db.php';

$stmt = $pdo->query("
    SELECT *
    FROM luoghi
    WHERE pubblicato = 1
    ORDER BY ordine ASC, titolo ASC
");
$luoghi = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>

    <style>
        .places-page .lead-text {
            font-size: 18px;
            line-height: 1.75;
            color: #555;
            margin-bottom: 34px;
        }

        .places-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 28px;
        }

        .place-card {
            background: #fff;
            box-shadow: 0 10px 30px rgba(0,0,0,.06);
            border: 1px solid rgba(0,0,0,.04);
            overflow: hidden;
            height: 100%;
        }

        .place-card-image {
            display: block;
            min-height: 230px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .place-card-image:after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,0), rgba(0,0,0,.38));
        }

        .place-card-body {
            padding: 24px;
        }

        .place-card-body h3 {
            margin-top: 0;
            margin-bottom: 8px;
        }

        .place-card-body p {
            color: #666;
            line-height: 1.65;
        }

        .place-meta {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-bottom: 13px;
        }

        .place-tag {
            display: inline-block;
            padding: 6px 8px;
            background: #f0f0f0;
            color: #333;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        .places-empty {
            background: #fff;
            padding: 28px;
            box-shadow: 0 10px 30px rgba(0,0,0,.06);
            color: #666;
        }

        @media (max-width: 991px) {
            .places-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 650px) {
            .places-grid {
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
        <?php require LAUCO_VIEW_PATH . '/partials/menu.php'; ?>

        <div id="page-content" class="header-static">
            <div id="flexslider" class="fullpage-wrap small">
                <ul class="slides">
                    <li style="background-image:url(assets/img/radime.jpg)">
                        <div class="container text text-center">
                            <h1 class="white margin-bottom-small">Luoghi da scoprire</h1>
                            <p class="heading white">Storia, natura, panorami e punti di interesse del territorio di Lauco.</p>
                        </div>
                        <div class="gradient dark"></div>
                    </li>

                    <ol class="breadcrumb">
                        <li><a href="/">Home</a></li>
                        <li class="active">Luoghi</li>
                    </ol>
                </ul>
            </div>

            <div id="page-wrap" class="content-section fullpage-wrap places-page">
                <div class="container text">
                    <div class="row margin-null">
                        <div class="col-md-12 padding-leftright-null">
                            <h2 class="margin-bottom-null title line left">Luoghi da scoprire</h2>
                            <p class="heading left grey margin-bottom">Piccoli patrimoni, punti panoramici e memorie del territorio.</p>

                            <p class="lead-text">
                                Questa sezione raccoglie luoghi, punti di interesse e testimonianze del territorio di Lauco:
                                elementi storici, scorci panoramici, architetture minori e luoghi naturali da conoscere con rispetto.
                            </p>
                        </div>
                    </div>

                    <?php if (!$luoghi): ?>
                        <div class="places-empty">
                            Nessun luogo pubblicato al momento.
                        </div>
                    <?php else: ?>
                        <div class="places-grid">
                            <?php foreach ($luoghi as $luogo): ?>
                                <?php
                                    $image = $luogo['cover_image'] ?: 'assets/img/trip4.jpg';
                                    $url = 'luogo?slug=' . urlencode($luogo['slug']);
                                ?>
                                <article class="place-card">
                                    <a class="place-card-image" href="<?= e($url) ?>" style="background-image:url(<?= e($image) ?>)"></a>

                                    <div class="place-card-body">
                                        <div class="place-meta">
                                            <?php if (!empty($luogo['categoria'])): ?>
                                                <span class="place-tag"><?= e($luogo['categoria']) ?></span>
                                            <?php endif; ?>

                                            <?php if (!empty($luogo['localita'])): ?>
                                                <span class="place-tag"><?= e($luogo['localita']) ?></span>
                                            <?php endif; ?>
                                        </div>

                                        <h3><a href="<?= e($url) ?>"><?= e($luogo['titolo']) ?></a></h3>

                                        <?php if (!empty($luogo['sottotitolo'])): ?>
                                            <p><strong><?= e($luogo['sottotitolo']) ?></strong></p>
                                        <?php endif; ?>

                                        <?php if (!empty($luogo['excerpt'])): ?>
                                            <p><?= e($luogo['excerpt']) ?></p>
                                        <?php endif; ?>

                                        <p><a class="btn-alt small" href="<?= e($url) ?>">Scopri</a></p>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php require LAUCO_VIEW_PATH . '/partials/footer.php'; ?>
    </div>

    <?php require LAUCO_VIEW_PATH . '/partials/scripts.php'; ?>
</body>
</html>
