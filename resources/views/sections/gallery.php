<?php
require_once LAUCO_ROOT . '/inc/db.php';

$stmt = $pdo->query("
    SELECT *
    FROM galleria
    WHERE pubblicato = 1
    ORDER BY ordine ASC, created_at DESC
");

$galleryImages = $stmt->fetchAll();
?>

<style>
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
                    <img src="<?= e($img['image_path']) ?>" alt="" loading="lazy" decoding="async" fetchpriority="low">
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
