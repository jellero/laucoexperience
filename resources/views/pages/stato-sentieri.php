<?php
require_once LAUCO_ROOT . '/inc/db.php';
require_once LAUCO_ROOT . '/inc/sentieri.php';

$trailRows = [];
try {
    $trailRows = sentieri_directory_rows($pdo, true);
    $priority = ['non_percorribile' => 0, 'attenzione' => 1, 'in_verifica' => 2, 'verificato' => 3];
    usort($trailRows, static function (array $a, array $b) use ($priority): int {
        $statusOrder = ($priority[(string) $a['stato']] ?? 9) <=> ($priority[(string) $b['stato']] ?? 9);
        return $statusOrder !== 0 ? $statusOrder : strnatcasecmp((string) $a['filename'], (string) $b['filename']);
    });
} catch (Throwable) {
    $trailRows = [];
}
$trailStatusKeys = [
    'verificato' => 'trail_status.verified',
    'attenzione' => 'trail_status.attention',
    'non_percorribile' => 'trail_status.closed',
    'in_verifica' => 'trail_status.pending',
];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>
    <style>
        .trail-status-page{padding-bottom:80px}.trail-status-intro{max-width:850px;font-size:18px;line-height:1.7;color:#666}.trail-status-filters{display:flex;gap:8px;flex-wrap:wrap;margin:28px 0}.trail-status-filter{border:1px solid #222;background:#fff;color:#222;padding:10px 14px;cursor:pointer;font-weight:700}.trail-status-filter.is-active{background:#222;color:#fff}.trail-status-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px}.trail-status-card{background:#fff;border:1px solid #e8e8e8;box-shadow:0 10px 28px rgba(0,0,0,.06);padding:25px;display:grid;grid-template-columns:1fr auto;gap:18px}.trail-status-card h3{margin:4px 0 8px}.trail-status-card p{color:#666;line-height:1.6;margin:8px 0}.trail-status-badge{display:inline-block;padding:7px 9px;font-size:12px;font-weight:700;background:#eee;white-space:nowrap;height:max-content}.trail-status-badge.verificato{background:#d7f0df;color:#12602c}.trail-status-badge.attenzione{background:#fff1bf;color:#644b00}.trail-status-badge.non_percorribile{background:#f8d7da;color:#842029}.trail-status-badge.in_verifica{background:#e6e8eb;color:#4c5258}.trail-status-date{font-size:13px!important}.trail-status-meta{font-size:13px;color:#777}.trail-status-link{display:inline-block;margin-top:10px;font-size:12px;font-weight:700;text-transform:uppercase;border-bottom:2px solid #222;color:#222}.trail-status-empty{padding:28px;background:#fff;color:#666}@media(max-width:767px){.trail-status-grid{grid-template-columns:1fr}.trail-status-card{grid-template-columns:1fr}.trail-status-badge{justify-self:start}.trail-status-page{padding-bottom:55px}}
    </style>
</head>
<body>
<div id="myloader"><span class="loader"><div class="inner-loader"></div></span></div>
<div id="main-wrap" class="full-width">
    <?php require LAUCO_VIEW_PATH . '/partials/menu.php'; ?>
    <div id="page-content" class="header-static">
        <div id="flexslider" class="fullpage-wrap small">
            <ul class="slides">
                <li style="background-image:url(/assets/img/sentieri.webp)">
                    <div class="container text text-center"><h1 class="white margin-bottom-small"><?= e(site_text('trail_status.title', null, 'Stato dei sentieri')) ?></h1></div>
                    <div class="gradient dark"></div>
                </li>
                <ol class="breadcrumb"><li><a href="/"><?= e(site_text('breadcrumb.home', null, 'Home')) ?></a></li><li class="active"><?= e(site_text('trail_status.title', null, 'Stato dei sentieri')) ?></li></ol>
            </ul>
        </div>
        <main id="page-wrap" class="content-section fullpage-wrap trail-status-page">
            <div class="container text">
                <h2 class="margin-bottom-null title line left"><?= e(site_text('trail_status.title', null, 'Stato dei sentieri')) ?></h2>
                <p class="trail-status-intro"><?= e(site_text('trail_status.intro', null, 'Informazioni aggiornate sui singoli sentieri, indipendenti dalle schede degli itinerari.')) ?></p>
                <div class="trail-status-filters" role="group">
                    <button type="button" class="trail-status-filter is-active" data-status="all"><?= e(site_text('trail_status.all', null, 'Tutti')) ?></button>
                    <?php foreach ($trailStatusKeys as $status => $key): ?><button type="button" class="trail-status-filter" data-status="<?= e($status) ?>"><?= e(site_text($key, null, sentieri_status_label($status))) ?></button><?php endforeach; ?>
                </div>
                <?php if ($trailRows === []): ?>
                    <p class="trail-status-empty"><?= e(site_text('trail_status.empty', null, 'Lo stato dei sentieri è in fase di aggiornamento.')) ?></p>
                <?php else: ?>
                    <div class="trail-status-grid">
                        <?php foreach ($trailRows as $trail): $status=(string)($trail['stato'] ?: 'in_verifica'); $stats=gpx_stats((string)$trail['gpx_file'],'piedi'); ?>
                            <article class="trail-status-card" data-status="<?= e($status) ?>">
                                <div>
                                    <small><?= e(site_text('trail_status.trail', null, 'Sentiero')) ?><?= !empty($trail['localita']) ? ' · '.e($trail['localita']) : '' ?></small>
                                    <h3><?= e($trail['codice']) ?></h3>
                                    <?php if (!empty($trail['descrizione'])): ?><p><?= nl2br(e($trail['descrizione'])) ?></p><?php endif; ?>
                                    <?php if (!empty($trail['nota_pubblica'])): ?><p><strong><?= e(site_text('trail_status.notice', null, 'Avviso')) ?>:</strong> <?= nl2br(e($trail['nota_pubblica'])) ?></p><?php endif; ?>
                                    <p class="trail-status-date"><strong><?= e(site_text('trail_status.last_check', null, 'Ultima verifica')) ?>:</strong> <?= !empty($trail['ultima_verifica_at']) ? e(date('d/m/Y', strtotime((string)$trail['ultima_verifica_at']))) : e(site_text('trail_status.unknown', null, 'Non ancora verificato')) ?></p>
                                    <p class="trail-status-meta"><?= e($stats['length_label']) ?> · +<?= (int)($stats['ascent_m'] ?? 0) ?> m</p>
                                    <a class="trail-status-link" href="/gpx/<?= rawurlencode(basename((string)$trail['gpx_file'])) ?>?download=1"><?= e(site_text('trail_status.download_gpx', null, 'Scarica GPX')) ?></a>
                                </div>
                                <span class="trail-status-badge <?= e($status) ?>"><?= e(site_text($trailStatusKeys[$status] ?? 'trail_status.pending', null, sentieri_status_label($status))) ?></span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
    <?php require LAUCO_VIEW_PATH . '/partials/footerf.php'; ?>
</div>
<?php require LAUCO_VIEW_PATH . '/partials/scripts.php'; ?>
<script>(function(){var buttons=document.querySelectorAll('.trail-status-filter');var cards=document.querySelectorAll('.trail-status-card');buttons.forEach(function(button){button.addEventListener('click',function(){buttons.forEach(function(item){item.classList.remove('is-active');});button.classList.add('is-active');var selected=button.getAttribute('data-status');cards.forEach(function(card){card.hidden=selected!=='all'&&card.getAttribute('data-status')!==selected;});});});})();</script>
</body>
</html>
