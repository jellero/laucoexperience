<?php
require_once LAUCO_ROOT . '/inc/db.php';

function eventi_page_date(?string $date): string
{
    if (!$date) {
        return '';
    }

    $ts = strtotime($date);
    return $ts ? date('d.m.Y', $ts) : '';
}

function eventi_page_categories(?string $category): array
{
    if (!$category) {
        return ['Eventi'];
    }

    $items = array_filter(array_map('trim', explode(',', $category)));

    if (!$items) {
        return ['Eventi'];
    }

    if (!in_array('Eventi', $items, true)) {
        $items[] = 'Eventi';
    }

    return array_slice($items, 0, 3);
}

$today = (new DateTimeImmutable('today'))->format('Y-m-d');
$stmt = $pdo->prepare("
    SELECT *
    FROM eventi
    WHERE pubblicato = 1
      AND data_evento IS NOT NULL
      AND data_evento >= :today
    ORDER BY data_evento ASC, ordine ASC, created_at ASC
");
$stmt->execute(['today' => $today]);

$eventi = $stmt->fetchAll();
$hero = $eventi[0]['cover_image'] ?? 'assets/img/old.jpg';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>

    <style>
        .eventi-page-section {
            padding-top: 70px;
            padding-bottom: 80px;
        }

        .eventi-grid {
            margin-top: 40px;
        }

        .eventi-card-wrap {
            margin-bottom: 34px;
        }

        .eventi-card {
            position: relative;
            display: block;
            background: #fff;
            min-height: 460px;
            height: 460px;
            overflow: hidden;
            box-shadow: 0 10px 34px rgba(0,0,0,.08);
            transition: transform .25s ease, box-shadow .25s ease;
        }

        .eventi-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 42px rgba(0,0,0,.13);
        }

        .eventi-card-click {
            display: block;
            height: 100%;
            color: inherit;
            text-decoration: none;
            cursor: pointer;
        }

        .eventi-card-click:hover,
        .eventi-card-click:focus {
            color: inherit;
            text-decoration: none;
        }

        .eventi-card-image {
            height: 220px;
            background-size: cover;
            background-position: center;
            position: relative;
        }

        .eventi-card-image:after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(0,0,0,.05), rgba(0,0,0,.32));
        }

        .eventi-card-content {
            position: relative;
            height: 240px;
            padding: 26px 28px;
            overflow: hidden;
        }

        .eventi-card-content h3 {
            margin-top: 0;
            margin-bottom: 10px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 58px;
        }

        .eventi-card-date {
            display: block;
            margin-bottom: 12px;
            color: #999;
            font-size: 13px;
            letter-spacing: .04em;
        }

        .eventi-card-text {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
            min-height: 72px;
            margin-bottom: 14px;
        }

        .eventi-card-categories {
            position: absolute;
            left: 28px;
            right: 28px;
            bottom: 22px;
            max-height: 34px;
            overflow: hidden;
        }

        .eventi-card .category {
            margin-right: 5px;
            margin-bottom: 5px;
        }

        .eventi-empty {
            background: #fff;
            padding: 44px;
            box-shadow: 0 10px 34px rgba(0,0,0,.08);
        }

        .eventi-archive-link {
            clear: both;
            padding-top: 18px;
            text-align: center;
        }

        @media (max-width: 991px) {
            .eventi-card {
                height: auto;
                min-height: 0;
            }

            .eventi-card-content {
                height: auto;
                min-height: 260px;
            }

            .eventi-card-categories {
                position: static;
                margin-top: 16px;
            }
        }

        @media (max-width: 600px) {
            .eventi-page-section {
                padding-top: 45px;
                padding-bottom: 55px;
            }

            .eventi-card-image {
                height: 190px;
            }

            .eventi-card-content {
                padding: 22px;
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
                    <li style="background-image:url(<?= e($hero) ?>)">
                        <div class="container text text-center">
                            <h1 class="white margin-bottom-small">Eventi</h1>
                            <p class="heading white">
                                Vivi il territorio di Lauco attraverso appuntamenti, tradizioni e iniziative outdoor.
                            </p>
                        </div>
                        <div class="gradient dark"></div>
                    </li>
                    <ol class="breadcrumb">
                        <li><a href="/">Home</a></li>
                        <li class="active">Eventi</li>
                    </ol>
                </ul>
            </div>

            <div id="page-wrap" class="content-section fullpage-wrap">
                <div class="row margin-leftright-null grey-background eventi-page-section">
                    <div class="container">
                        <div class="col-md-12 padding-leftright-null text padding-bottom-null text-center">
                            <h2 class="margin-bottom-null title line center">EVENTI</h2>
                            <p class="heading center grey margin-bottom-null">
                                I prossimi appuntamenti, dal più vicino a quelli più avanti nel tempo.
                            </p>
                        </div>

                        <div class="col-md-12 eventi-grid">
                            <?php if (!$eventi): ?>
                                <div class="eventi-empty text-center">
                                    <p class="margin-bottom-null">Al momento non ci sono eventi futuri pubblicati.</p>
                                </div>
                            <?php endif; ?>

                            <?php foreach ($eventi as $evento): ?>
                                <?php
                                    $cover = $evento['cover_image'] ?: 'assets/img/cronoradima.jpg';
                                    $date = eventi_page_date($evento['data_evento'] ?? null);
                                    $categories = eventi_page_categories($evento['categoria'] ?? '');
                                    $url = '/evento?slug=' . urlencode($evento['slug']);
                                ?>

                                <div class="col-md-6 eventi-card-wrap">
                                    <article class="eventi-card">
                                        <a href="<?= e($url) ?>" class="eventi-card-click" aria-label="<?= e($evento['titolo']) ?>">
                                            <div class="eventi-card-image" style="background-image:url(<?= e($cover) ?>)"></div>

                                            <div class="eventi-card-content">
                                                <h3><?= e($evento['titolo']) ?></h3>

                                                <?php if ($date || !empty($evento['localita'])): ?>
                                                    <span class="eventi-card-date">
                                                        <?= e($date) ?>
                                                        <?= $date && !empty($evento['localita']) ? ' · ' : '' ?>
                                                        <?= e($evento['localita'] ?? '') ?>
                                                    </span>
                                                <?php endif; ?>

                                                <p class="eventi-card-text">
                                                    <?= e($evento['excerpt'] ?: $evento['contenuto']) ?>
                                                </p>

                                                <div class="eventi-card-categories">
                                                    <?php foreach ($categories as $category): ?>
                                                        <span class="category"><?= e($category) ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </a>
                                    </article>
                                </div>
                            <?php endforeach; ?>

                            <div class="eventi-archive-link">
                                <a href="/eventi/archivio" class="btn-alt small margin-null">Archivio eventi</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php require LAUCO_VIEW_PATH . '/partials/footer.php'; ?>
    </div>

    <?php require LAUCO_VIEW_PATH . '/partials/scripts.php'; ?>
</body>
</html>
