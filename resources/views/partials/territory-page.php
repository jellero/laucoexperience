<?php
declare(strict_types=1);

require_once LAUCO_ROOT . '/inc/territory-content.php';

$pageKey = isset($territoryPageKey) && is_string($territoryPageKey) ? $territoryPageKey : '';
$page = territory_content($pageKey);
$common = territory_content('common');

if ($page === []) {
    http_response_code(404);
    return;
}

$hero = (string) ($page['hero'] ?? 'assets/img/trip4.jpg');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(territory_locale(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>
    <style>
        .territory-page .lead-text{font-size:18px;line-height:1.8;color:#555;margin-bottom:38px}
        .territory-page .territory-section{background:#fff;padding:30px;margin-bottom:24px;box-shadow:0 10px 30px rgba(0,0,0,.06);border:1px solid rgba(0,0,0,.04)}
        .territory-page .territory-section h3{margin-top:0;margin-bottom:15px}
        .territory-page .territory-section p,.territory-page .territory-section li{color:#666;line-height:1.8}
        .territory-page .territory-section p:last-child{margin-bottom:0}
        .territory-page .territory-section ul{padding-left:20px;margin-bottom:0}
        .territory-page .source-box{background:#f7f7f7;padding:26px;margin-top:36px;border-left:4px solid #222}
        .territory-page .source-box h3{margin-top:0}
        .territory-page .source-box ul{margin:0;padding-left:18px}
        .territory-page .source-box li{margin-bottom:8px}
        .territory-page .related-links{display:flex;gap:10px;flex-wrap:wrap;margin-top:30px}
        @media(max-width:767px){.territory-page .territory-section{padding:22px}}
    </style>
</head>
<body>
    <div id="myloader"><span class="loader"><div class="inner-loader"></div></span></div>
    <div id="main-wrap" class="full-width">
        <?php require LAUCO_VIEW_PATH . '/partials/menu.php'; ?>
        <div id="page-content" class="header-static">
            <div id="flexslider" class="fullpage-wrap small">
                <ul class="slides">
                    <li style="background-image:url(<?= htmlspecialchars($hero, ENT_QUOTES, 'UTF-8') ?>)">
                        <div class="container text text-center">
                            <h1 class="white margin-bottom-small"><?= htmlspecialchars((string) $page['title'], ENT_QUOTES, 'UTF-8') ?></h1>
                            <p class="heading white"><?= htmlspecialchars((string) $page['subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="gradient dark"></div>
                    </li>
                    <ol class="breadcrumb">
                        <li><a href="/"><?= htmlspecialchars((string) ($common['home'] ?? 'Home'), ENT_QUOTES, 'UTF-8') ?></a></li>
                        <li class="active"><?= htmlspecialchars((string) $page['title'], ENT_QUOTES, 'UTF-8') ?></li>
                    </ol>
                </ul>
            </div>

            <div id="page-wrap" class="content-section fullpage-wrap territory-page">
                <div class="container text">
                    <div class="row margin-null">
                        <div class="col-md-12 padding-leftright-null">
                            <h2 class="margin-bottom-null title line left"><?= htmlspecialchars((string) $page['title'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <p class="heading left grey margin-bottom"><?= htmlspecialchars((string) $page['subtitle'], ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="lead-text"><?= htmlspecialchars((string) $page['lead'], ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <?php foreach (($page['sections'] ?? []) as $section): ?>
                                <section class="territory-section">
                                    <h3><?= htmlspecialchars((string) ($section['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                                    <?php foreach (($section['paragraphs'] ?? []) as $paragraph): ?>
                                        <p><?= htmlspecialchars((string) $paragraph, ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php endforeach; ?>
                                    <?php if (!empty($section['bullets'])): ?>
                                        <ul>
                                            <?php foreach ($section['bullets'] as $item): ?>
                                                <li><?= htmlspecialchars((string) $item, ENT_QUOTES, 'UTF-8') ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endif; ?>
                                </section>
                            <?php endforeach; ?>

                            <?php if (!empty($page['sources'])): ?>
                                <aside class="source-box">
                                    <h3><?= htmlspecialchars((string) ($page['sources_title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                                    <ul>
                                        <?php foreach ($page['sources'] as $source): ?>
                                            <li>
                                                <a href="<?= htmlspecialchars((string) $source['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">
                                                    <?= htmlspecialchars((string) $source['label'], ENT_QUOTES, 'UTF-8') ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </aside>
                            <?php endif; ?>

                            <div class="related-links">
                                <a class="btn-alt small" href="<?= htmlspecialchars(content_language_url(territory_locale(), '/luoghi'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($common['places'] ?? 'Luoghi'), ENT_QUOTES, 'UTF-8') ?></a>
                                <?php if ($pageKey !== 'history'): ?><a class="btn-alt small" href="<?= htmlspecialchars(content_language_url(territory_locale(), '/storia'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($common['history'] ?? 'Storia'), ENT_QUOTES, 'UTF-8') ?></a><?php endif; ?>
                                <?php if ($pageKey !== 'nature'): ?><a class="btn-alt small" href="<?= htmlspecialchars(content_language_url(territory_locale(), '/natura'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($common['nature'] ?? 'Natura'), ENT_QUOTES, 'UTF-8') ?></a><?php endif; ?>
                                <?php if ($pageKey !== 'arrive'): ?><a class="btn-alt small" href="<?= htmlspecialchars(content_language_url(territory_locale(), '/come-arrivare'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($common['arrive'] ?? 'Come arrivare'), ENT_QUOTES, 'UTF-8') ?></a><?php endif; ?>
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
