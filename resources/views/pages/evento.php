<?php
require_once LAUCO_ROOT . '/inc/db.php';

function public_event_date($date) {
    if (!$date) return '';
    $ts = strtotime($date);
    return $ts ? date('d.m.Y', $ts) : '';
}

function public_event_categories($category) {
    $items = array_filter(array_map('trim', explode(',', (string)$category)));
    return $items ?: ['Eventi'];
}

$slug = trim($_GET['slug'] ?? '');
if ($slug === '') { http_response_code(404); return; }

$s = $pdo->prepare('SELECT * FROM eventi WHERE slug = :slug AND pubblicato = 1 LIMIT 1');
$s->execute(['slug'=>$slug]);
$evento = $s->fetch();
if (!$evento) { http_response_code(404); return; }

$s = $pdo->prepare('SELECT * FROM evento_gallery WHERE evento_id = :id ORDER BY sort_order ASC, id ASC');
$s->execute(['id'=>$evento['id']]);
$gallery = $s->fetchAll();

$cover = $evento['cover_image'] ?: 'assets/img/trip5.jpg';
$date = public_event_date($evento['data_evento'] ?? null);
$categories = public_event_categories($evento['categoria'] ?? '');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>
</head>
<body>
<div id="myloader"><span class="loader"><div class="inner-loader"></div></span></div>

<div id="main-wrap" class="full-width">
    <?php require LAUCO_VIEW_PATH . '/partials/menu.php'; ?>

    <div id="page-content" class="header-static">
        <div id="flexslider" class="fullpage-wrap small">
            <ul class="slides">
                <li style="background-image:url(<?= e($cover) ?>)">
                    <div class="container text text-center">
                        <h1 class="white margin-bottom-small"><?= e($evento['titolo']) ?></h1>
                        <?php if (!empty($evento['excerpt'])): ?><p class="heading white"><?= e($evento['excerpt']) ?></p><?php endif; ?>
                    </div>
                    <div class="gradient dark"></div>
                </li>
                <ol class="breadcrumb">
                    <li><a href="/">Home</a></li>
                    <li><a href="/eventi">Eventi</a></li>
                    <li class="active"><?= e($evento['titolo']) ?></li>
                </ol>
            </ul>
        </div>

        <div id="post-wrap" class="content-section fullpage-wrap">
            <div class="row margin-leftright-null">
                <div class="container text">
                    <div class="row content-post no-margin">
                        <div class="col-md-12 padding-leftright-null text-center">
                            <h2 class="margin-bottom-null title simple left"><?= e($evento['titolo']) ?></h2>
                            <?php foreach ($categories as $i => $cat): ?><span class="category<?= $i === count($categories)-1 ? ' last' : '' ?>"><?= e($cat) ?></span><?php endforeach; ?>
                            <?php if ($date): ?><span class="date"><?= e($date) ?></span><?php endif; ?>
                            <?php if (!empty($evento['localita'])): ?><span class="date"><?= e($evento['localita']) ?></span><?php endif; ?>
                        </div>

                        <div class="col-md-12 padding-onlytop-md padding-leftright-null">
                            <?php if (!empty($evento['excerpt'])): ?><p><b><?= nl2br(e($evento['excerpt'])) ?></b></p><?php endif; ?>
                            <?php if (!empty($evento['contenuto'])): ?><p class="margin-null"><?= nl2br(e($evento['contenuto'])) ?></p><?php endif; ?>

                            <?php if ($gallery): ?>
                                <section class="grid-images padding-sm padding-md-bottom-null">
                                    <?php foreach (array_chunk($gallery, 2) as $row): ?>
                                        <div class="row padding-onlytop-sm">
                                            <?php foreach ($row as $img): ?>
                                                <div class="col-md-<?= count($row) === 1 ? '12' : '6' ?>">
                                                    <div class="image simple-shadow" style="background-image:url(<?= e($img['image_path']) ?>)">
                                                        <a class="lightbox-image" href="<?= e($img['image_path']) ?>" title="<?= e($img['alt'] ?: $evento['titolo']) ?>"></a>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </section>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row no-margin">
                        <div class="col-md-12 padding-leftright-null text-center padding-onlytop-md">
                            <a href="/eventi" class="btn-alt small margin-null">Torna agli eventi</a>
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
