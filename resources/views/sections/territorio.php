<?php
declare(strict_types=1);

require_once LAUCO_ROOT . '/inc/territory-content.php';
$territoryHome = territory_content('home');
?>
<section class="row no-margin territory-home">
    <style>
        .territory-home{padding:70px 0;background:#f7f7f7}
        .territory-home .territory-intro{text-align:center;max-width:850px;margin:0 auto 35px}
        .territory-home .territory-intro p{color:#666;line-height:1.75}
        .territory-home-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}
        .territory-home-card{background:#fff;padding:26px;height:100%;box-shadow:0 10px 30px rgba(0,0,0,.05)}
        .territory-home-card i{font-size:38px;display:block;margin-bottom:15px}
        .territory-home-card h3{margin-top:0;font-size:19px}
        .territory-home-card p{color:#666;line-height:1.65;min-height:80px}
        @media(max-width:991px){.territory-home-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
        @media(max-width:600px){.territory-home-grid{grid-template-columns:1fr}.territory-home-card p{min-height:0}}
    </style>
    <div class="container">
        <div class="territory-intro">
            <p class="heading grey margin-bottom-extrasmall"><?= htmlspecialchars((string) ($territoryHome['eyebrow'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
            <h2 class="margin-bottom-small title center"><?= htmlspecialchars((string) ($territoryHome['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
            <p><?= htmlspecialchars((string) ($territoryHome['lead'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <div class="territory-home-grid">
            <?php foreach (($territoryHome['cards'] ?? []) as $card): ?>
                <article class="territory-home-card">
                    <i class="icon <?= htmlspecialchars((string) ($card['icon'] ?? 'ion-ios-location-outline'), ENT_QUOTES, 'UTF-8') ?>"></i>
                    <h3><?= htmlspecialchars((string) ($card['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                    <p><?= htmlspecialchars((string) ($card['text'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                    <a class="btn-alt small" href="<?= htmlspecialchars(content_language_url(territory_locale(), (string) ($card['url'] ?? '/')), ENT_QUOTES, 'UTF-8') ?>">
                        <?= htmlspecialchars((string) ($territoryHome['discover'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
