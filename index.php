<?php
/** Punto d’ingresso – versione con struttura fedele all’HTML originale */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'inc/header.php'; ?>
</head>
<body>

    <!-- Loader -->
    <div id="myloader">
        <span class="loader"><div class="inner-loader"></div></span>
    </div>

    <!-- Main Wrap -->
    <div id="main-wrap" class="full-width">

        <!-- Header & Menu -->
        <?php include 'inc/menu.php'; ?>

        <!-- Page Content -->
        <div id="page-content" class="header-static">

            <?php include 'sections/slider.php'; ?>

            <!-- wrapper ripristinato per combaciare con l’HTML originale -->
            <div id="home-wrap" class="content-section fullpage-wrap">

                <?php
                    include 'sections/about.php';
                    include 'sections/services.php';
                    include 'sections/gallery.php';
                    include 'sections/trips.php';
                    include 'sections/testimonials.php';
                    include 'sections/news.php';
                    include 'sections/contributi.php';
                    include 'sections/sponsors.php';
                ?>

            </div><!-- /#home-wrap -->

        </div><!-- /#page-content -->

        <!-- Footer -->
        <?php include 'inc/footer.php'; ?>

    </div><!-- /#main-wrap -->

    <!-- Scripts -->
    <?php include 'inc/scripts.php'; ?>

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