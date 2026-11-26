<?php
require_once LAUCO_ROOT . '/inc/db.php';
require_once LAUCO_ROOT . '/inc/home-image.php';

$stmt = $pdo->query("
    SELECT *
    FROM galleria
    WHERE pubblicato = 1
    ORDER BY ordine ASC, created_at DESC
");

$galleryImages = $stmt->fetchAll();
?>

<style>
    /* Reserve the same row and item widths that Owl Carousel will use.
       Before its synchronous setup, avoid stacking full-width photographs. */
    .gallery-carousel:not(.owl-carousel) {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 25px;
        overflow: hidden;
    }
    .gallery-carousel:not(.owl-carousel) > .item:nth-child(n+3) { display: none; }
    @media (min-width: 480px) {
        .gallery-carousel:not(.owl-carousel) { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .gallery-carousel:not(.owl-carousel) > .item:nth-child(-n+4) { display: block; }
        .gallery-carousel:not(.owl-carousel) > .item:nth-child(n+5) { display: none; }
    }
    @media (min-width: 767px) {
        .gallery-carousel:not(.owl-carousel) { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        .gallery-carousel:not(.owl-carousel) > .item:nth-child(-n+5) { display: block; }
        .gallery-carousel:not(.owl-carousel) > .item:nth-child(n+6) { display: none; }
    }
    @media (min-width: 991px) {
        .gallery-carousel:not(.owl-carousel) { grid-template-columns: repeat(6, minmax(0, 1fr)); }
        .gallery-carousel:not(.owl-carousel) > .item:nth-child(-n+6) { display: block; }
        .gallery-carousel:not(.owl-carousel) > .item:nth-child(n+7) { display: none; }
    }
    @media (min-width: 1199px) {
        .gallery-carousel:not(.owl-carousel) { grid-template-columns: repeat(8, minmax(0, 1fr)); }
        .gallery-carousel:not(.owl-carousel) > .item:nth-child(-n+8) { display: block; }
        .gallery-carousel:not(.owl-carousel) > .item:nth-child(n+9) { display: none; }
    }

    .gallery-carousel .image {
        position: relative;
    }

    .gallery-carousel .image > img {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        object-position: center;
    }

    .gallery-carousel .gallery-link {
        position: absolute;
        inset: 0;
        display: block;
        z-index: 5;
        cursor: pointer;
    }
</style>

<!-- Carousel Gallery -->
<div class="row margin-leftright-null padding-sm">
    <div class="gallery-carousel">
        <?php foreach ($galleryImages as $img): ?>
            <div class="item">
                <div class="image">
                    <img src="<?= e($img['image_path']) ?>"<?= home_image_attributes(LAUCO_ROOT, (string) $img['image_path'], 'gallery') ?> alt="" loading="lazy" decoding="async" fetchpriority="low">
                    <a
                        class="gallery-link lightbox-image"
                        href="<?= e($img['image_path']) ?>"
                        title="<?= e($img['titolo'] ?: $img['alt']) ?>"
                        aria-label="<?= e($img['titolo'] ?: $img['alt'] ?: 'Immagine galleria') ?>"
                    ></a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<!-- END Carousel Gallery -->
