<?php
/** Punto d’ingresso – versione con struttura fedele all’HTML originale */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>
</head>
<body>

    <!-- Loader -->
    <div id="myloader">
        <span class="loader"><div class="inner-loader"></div></span>
    </div>

    <!-- Main Wrap -->
    <div id="main-wrap" class="full-width">

        <!-- Header & Menu -->
        <?php require LAUCO_VIEW_PATH . '/partials/menu.php'; ?>

        <!-- Page Content -->
        <div id="page-content" class="header-static">

            <?php require LAUCO_VIEW_PATH . '/sections/slider.php'; ?>

            <!-- wrapper ripristinato per combaciare con l’HTML originale -->
            <div id="home-wrap" class="content-section fullpage-wrap">

                <?php
                    require LAUCO_VIEW_PATH . '/sections/about.php';
                    require LAUCO_VIEW_PATH . '/sections/territorio.php';
                    require LAUCO_VIEW_PATH . '/sections/services.php';
                    require LAUCO_VIEW_PATH . '/sections/gallery.php';
                    require LAUCO_VIEW_PATH . '/sections/trips.php';
                    require LAUCO_VIEW_PATH . '/sections/testimonials.php';
                    require LAUCO_VIEW_PATH . '/sections/news.php';
                    require LAUCO_VIEW_PATH . '/sections/forra.php';
                    require LAUCO_VIEW_PATH . '/sections/barbecue.php';
                    require LAUCO_VIEW_PATH . '/sections/contributi.php';
                    require LAUCO_VIEW_PATH . '/sections/sponsors.php';
                ?>

            </div><!-- /#home-wrap -->

        </div><!-- /#page-content -->

        <!-- Footer -->
        <?php require LAUCO_VIEW_PATH . '/partials/footer.php'; ?>

    </div><!-- /#main-wrap -->

    <!-- Scripts -->
    <?php require LAUCO_VIEW_PATH . '/partials/scripts.php'; ?>

    <script>
document.addEventListener('DOMContentLoaded', function () {
    function cambiaLocalita() {
        document.querySelectorAll('.location-value').forEach(function (elemento) {
            if (elemento.textContent.trim() === 'Enemonzo') {
                elemento.textContent = 'Lauco';
            }
        });
    }

    // Primo tentativo
    cambiaLocalita();

    // Controlla anche gli elementi aggiunti successivamente dal widget
    const observer = new MutationObserver(cambiaLocalita);

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
});
</script>
</body>
</html>
