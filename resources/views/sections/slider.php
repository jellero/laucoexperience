<?php
require_once LAUCO_ROOT . '/inc/db.php';
require_once LAUCO_ROOT . '/inc/home-image.php';

$slides = [];
$sliderControlLabels = match (content_language_from_request()) {
    'en' => ['Previous slide', 'Next slide'],
    'de' => ['Vorherige Folie', 'Nächste Folie'],
    'sl' => ['Prejšnji diapozitiv', 'Naslednji diapozitiv'],
    default => ['Slide precedente', 'Slide successiva'],
};

try {
    $stmt = $pdo->query("
        SELECT s.*,
               e.slug AS evento_slug,
               p.slug AS percorso_slug
        FROM home_slider s
        LEFT JOIN eventi e ON e.id = s.evento_id
        LEFT JOIN percorsi p ON p.id = s.percorso_id
        WHERE s.pubblicato = 1
        ORDER BY s.ordine ASC, s.created_at DESC
    ");

    $slides = $stmt->fetchAll() ?: [];
} catch (Throwable $e) {
    error_log('[Lauco slider] ' . $e->getMessage());
    $slides = [];
}

function slider_url(array $slide): string
{
    switch ($slide['link_type']) {
        case 'evento':
            return !empty($slide['evento_slug']) ? '/evento?slug=' . urlencode($slide['evento_slug']) : '#';

        case 'percorso':
            return !empty($slide['percorso_slug']) ? '/percorso?slug=' . urlencode($slide['percorso_slug']) : '#';

        case 'free':
            return !empty($slide['custom_url']) ? $slide['custom_url'] : '#';

        default:
            return '#';
    }
}

function slider_title_html(string $title): string
{
    $safe = e($title);
    return str_replace('&lt;br&gt;', '<br>', $safe);
}
?>

<!-- Slider -->
<style>
    #page-content #flexslider-nav > .slides,
    #page-content #flexslider-nav > .flex-viewport,
    #page-content #flexslider-nav > .flex-viewport > .slides {
        height: 100%;
    }
    #page-content #flexslider-nav > .slides > li:not(:first-child) { display: none; }
    /* FlexSlider delays the inline slide widths and floats by 100 ms.
       Match its horizontal track during that initialization window. */
    #page-content #flexslider-nav > .flex-viewport > .slides > li {
        display: block;
        float: left;
        width: calc(100% / var(--lauco-slide-track-factor));
    }
    #page-content #flexslider-nav .slides > li > .slider-hero-image {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
        display: block;
        pointer-events: none;
    }
</style>
<div id="flexslider-nav" class="fullpage-wrap small" style="--lauco-slide-track-factor: <?= 2 * (max(1, count($slides)) + 2) ?>">
    <ul class="slides">
        <?php if (!$slides): ?>
            <li>
                <img class="slider-hero-image" src="assets/img/old.jpg"<?= home_image_attributes(LAUCO_ROOT, 'assets/img/old.jpg', 'hero') ?> alt="" fetchpriority="high" loading="eager" decoding="async">
                <div class="text container">
                    <h1 class="white flex-animation">Lauco<br> Experience</h1>
                    <h2 class="white flex-animation">Lauco</h2>
                </div>
                <div class="gradient dark"></div>
            </li>
        <?php endif; ?>

        <?php foreach ($slides as $index => $slide): ?>
            <?php
                $url = slider_url($slide);
                $hasLink = $url !== '#';
                $buttonLabel = $slide['button_label'] ?: 'info';
                $animationClass = $index === 0
                    ? 'flex-animation'
                    : 'flex-animation no-opacity';
            ?>

            <li>
                <img class="slider-hero-image" src="<?= e($slide['image_path']) ?>"<?= home_image_attributes(LAUCO_ROOT, (string) $slide['image_path'], 'hero') ?> alt="" loading="<?= $index === 0 ? 'eager' : 'lazy' ?>" fetchpriority="<?= $index === 0 ? 'high' : 'low' ?>" decoding="async">
                <div class="<?= $index === 1 ? 'container text' : 'text container' ?>">
                    <h1 class="white <?= e($animationClass) ?>"><?= slider_title_html($slide['titolo']) ?></h1>

                    <?php if (!empty($slide['sottotitolo'])): ?>
                        <h2 class="white <?= e($animationClass) ?>"><?= e($slide['sottotitolo']) ?></h2>
                    <?php endif; ?>

                    <?php if ($hasLink): ?>
                        <a href="<?= e($url) ?>" class="shadow btn-alt small activetwo margin-bottom-null <?= e($animationClass) ?>" aria-label="<?= e($buttonLabel . ': ' . trim(strip_tags(str_ireplace('<br>', ' ', $slide['titolo'])))) ?>">
                            <?= e($buttonLabel) ?>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="gradient dark"></div>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="slider-navigation">
        <a href="#" class="flex-prev" aria-label="<?= e($sliderControlLabels[0]) ?>"><i class="icon ion-ios-arrow-thin-left" aria-hidden="true"></i></a>
        <div class="slider-controls-container"></div>
        <a href="#" class="flex-next" aria-label="<?= e($sliderControlLabels[1]) ?>"><i class="icon ion-ios-arrow-thin-right" aria-hidden="true"></i></a>
    </div>
</div>
<!-- END Slider -->
