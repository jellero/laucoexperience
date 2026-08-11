<?php
declare(strict_types=1);

require_once LAUCO_ROOT . '/inc/fractions-content.php';

$locale = territory_locale();
$content = fractions_content($locale);
$hub = is_array($content['hub'] ?? null) ? $content['hub'] : [];
$ui = is_array($content['ui'] ?? null) ? $content['ui'] : [];
$items = fractions_items($locale);
$sources = is_array($content['sources'] ?? null) ? $content['sources'] : [];
$path = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/frazioni'), PHP_URL_PATH) ?: '/frazioni');
$slug = '';
if (preg_match('~^/frazioni/([a-z0-9-]+)$~i', rtrim($path, '/'), $matches)) {
    $slug = strtolower((string) $matches[1]);
}
$item = $slug !== '' ? fraction_content($slug, $locale) : null;

if ($slug !== '' && !is_array($item)) {
    http_response_code(404);
    require LAUCO_VIEW_PATH . '/pages/400.php';
    return;
}

$isDetail = is_array($item);
$title = $isDetail ? (string) ($item['name'] ?? '') : (string) ($hub['title'] ?? '');
$subtitle = $isDetail ? (string) ($item['summary'] ?? '') : (string) ($hub['subtitle'] ?? '');
$hero = $isDetail ? (string) ($item['hero'] ?? $hub['hero'] ?? 'assets/img/radime.jpg') : (string) ($hub['hero'] ?? 'assets/img/radime.jpg');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <base href="/">
    <?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>
    <style>
        .fractions-page .lead-text{font-size:18px;line-height:1.8;color:#555;margin-bottom:36px}
        .fractions-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:22px;margin-top:30px}
        .fraction-card{background:#fff;padding:28px;height:100%;box-shadow:0 10px 30px rgba(0,0,0,.06);border:1px solid rgba(0,0,0,.04)}
        .fraction-card h3{margin-top:0;margin-bottom:12px}
        .fraction-card p{color:#666;line-height:1.7;min-height:92px}
        .fraction-section{background:#fff;padding:30px;margin-bottom:24px;box-shadow:0 10px 30px rgba(0,0,0,.06);border:1px solid rgba(0,0,0,.04)}
        .fraction-section h2{margin-top:0;margin-bottom:15px;font-size:24px}
        .fraction-section p{color:#666;line-height:1.8}
        .fraction-section p:last-child{margin-bottom:0}
        .fraction-sources{background:#f7f7f7;padding:26px;margin-top:34px;border-left:4px solid #222}
        .fraction-sources h3{margin-top:0}
        .fraction-sources ul{margin:0;padding-left:18px}
        .fraction-sources li{margin-bottom:8px}
        .fraction-related{display:flex;gap:10px;flex-wrap:wrap;margin-top:30px}
        .fraction-updated{display:block;color:#888;font-size:13px;margin-top:-20px;margin-bottom:30px}
        @media(max-width:991px){.fractions-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:600px){.fractions-grid{grid-template-columns:1fr}.fraction-card p{min-height:0}.fraction-section{padding:22px}}
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
                            <h1 class="white margin-bottom-small"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
                            <p class="heading white"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="gradient dark"></div>
                    </li>
                    <ol class="breadcrumb">
                        <li><a href="<?= htmlspecialchars(content_language_url($locale, '/'), ENT_QUOTES, 'UTF-8') ?>">Home</a></li>
                        <?php if ($isDetail): ?>
                            <li><a href="<?= htmlspecialchars(content_language_url($locale, '/frazioni'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($hub['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a></li>
                            <li class="active"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php else: ?>
                            <li class="active"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endif; ?>
                    </ol>
                </ul>
            </div>

            <div id="page-wrap" class="content-section fullpage-wrap fractions-page">
                <div class="container text">
                    <?php if (!$isDetail): ?>
                        <div class="row margin-null">
                            <div class="col-md-12 padding-leftright-null">
                                <h2 class="margin-bottom-null title line left"><?= htmlspecialchars((string) ($hub['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="heading left grey margin-bottom"><?= htmlspecialchars((string) ($hub['subtitle'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="lead-text"><?= htmlspecialchars((string) ($hub['lead'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                <?php if (!empty($hub['updated'])): ?>
                                    <span class="fraction-updated"><?= htmlspecialchars((string) ($ui['updated'] ?? ''), ENT_QUOTES, 'UTF-8') ?>: <?= htmlspecialchars((string) $hub['updated'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="fractions-grid">
                            <?php foreach ($items as $fraction): ?>
                                <article class="fraction-card">
                                    <h3><?= htmlspecialchars((string) ($fraction['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                                    <p><?= htmlspecialchars((string) ($fraction['summary'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                    <a class="btn-alt small" href="<?= htmlspecialchars(content_language_url($locale, '/frazioni/' . (string) ($fraction['slug'] ?? '')), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($ui['discover'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="row margin-null">
                            <div class="col-md-12 padding-leftright-null">
                                <h2 class="margin-bottom-null title line left"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h2>
                                <p class="heading left grey margin-bottom"><?= htmlspecialchars($subtitle, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>

                        <?php foreach (($item['sections'] ?? []) as $section): ?>
                            <section class="fraction-section">
                                <h2><?= htmlspecialchars((string) ($section['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
                                <?php foreach (($section['paragraphs'] ?? []) as $paragraph): ?>
                                    <p><?= htmlspecialchars((string) $paragraph, ENT_QUOTES, 'UTF-8') ?></p>
                                <?php endforeach; ?>
                            </section>
                        <?php endforeach; ?>

                        <div class="fraction-related">
                            <a class="btn-alt small" href="<?= htmlspecialchars(content_language_url($locale, '/frazioni'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($ui['back'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a>
                            <a class="btn-alt small" href="<?= htmlspecialchars(content_language_url($locale, '/storia'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(territory_content('common')['history'] ?? 'Storia', ENT_QUOTES, 'UTF-8') ?></a>
                            <a class="btn-alt small" href="<?= htmlspecialchars(content_language_url($locale, '/natura'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(territory_content('common')['nature'] ?? 'Natura', ENT_QUOTES, 'UTF-8') ?></a>
                            <a class="btn-alt small" href="<?= htmlspecialchars(content_language_url($locale, '/luoghi'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(territory_content('common')['places'] ?? 'Luoghi', ENT_QUOTES, 'UTF-8') ?></a>
                        </div>
                    <?php endif; ?>

                    <?php if ($sources): ?>
                        <aside class="fraction-sources">
                            <h3><?= htmlspecialchars((string) ($ui['sources'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                            <ul>
                                <?php foreach ($sources as $source): ?>
                                    <li><a href="<?= htmlspecialchars((string) ($source['url'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars((string) ($source['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </aside>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php require LAUCO_VIEW_PATH . '/partials/footer.php'; ?>
    </div>
    <?php require LAUCO_VIEW_PATH . '/partials/scripts.php'; ?>
</body>
</html>
