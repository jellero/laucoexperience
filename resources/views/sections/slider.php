<?php
require_once LAUCO_ROOT . '/inc/db.php';

$slides = [];
$animationClass = 'flex-animation no-opacity';

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
<div id="flexslider-nav" class="fullpage-wrap small">
    <ul class="slides">
        <?php if (!$slides): ?>
            <li style="background-image:url(assets/img/old.jpg)">
                <div class="text container">
                    <h1 class="white flex-animation no-opacity">Lauco<br> Experience</h1>
                    <h2 class="white flex-animation no-opacity">Lauco</h2>
                </div>
                <div class="gradient dark"></div>
            </li>
        <?php endif; ?>

        <?php foreach ($slides as $index => $slide): ?>
            <?php
                $url = slider_url($slide);
                $hasLink = $url !== '#';
                $buttonLabel = $slide['button_label'] ?: 'info';
            ?>

            <li style="background-image:url(<?= e($slide['image_path']) ?>)">
                <div class="<?= $index === 1 ? 'container text' : 'text container' ?>">
                    <h1 class="white <?= e($animationClass) ?>"><?= slider_title_html($slide['titolo']) ?></h1>

                    <?php if (!empty($slide['sottotitolo'])): ?>
                        <h2 class="white <?= e($animationClass) ?>"><?= e($slide['sottotitolo']) ?></h2>
                    <?php endif; ?>

                    <?php if ($hasLink): ?>
                        <a href="<?= e($url) ?>" class="shadow btn-alt small activetwo margin-bottom-null <?= e($animationClass) ?>">
                            <?= e($buttonLabel) ?>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="gradient dark"></div>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="slider-navigation">
        <a href="#" class="flex-prev"><i class="icon ion-ios-arrow-thin-left"></i></a>
        <div class="slider-controls-container"></div>
        <a href="#" class="flex-next"><i class="icon ion-ios-arrow-thin-right"></i></a>
    </div>
</div>
<!-- END Slider -->
