<?php
/** Punto d’ingresso – versione con struttura fedele all’HTML originale */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preload" as="style" href="https://fonts.googleapis.com/css?family=Montserrat:300,300i,400,400i|Open+Sans:300,300i,400,400i|Trirong:300,300i,400,400i&amp;display=swap">
    <?php $laucoHomePerformance = true; require LAUCO_VIEW_PATH . '/partials/header.php'; ?>
    <style>
        #myloader { display: none !important; }
        /* Keep the original beige palette; use dark text on light surfaces. */
        #home-wrap a.btn-alt.small:not(.white) {
            color: #5e4c28;
            border-color: #8b794f;
        }
        #home-wrap a.btn-alt.small:not(.white).active,
        #home-wrap a.btn-alt.small:not(.white):hover,
        #home-wrap a.btn-alt.small:not(.white):focus-visible {
            color: #2f2911 !important;
        }
        #home-wrap p.heading.grey,
        #home-wrap .territory-home-card p,
        #forra-home .forra-home-inner > p {
            color: #555 !important;
        }
        #page-content #showcase-treks .item .showcase-trek span.read {
            color: #2f2911;
        }
        #newsletter-form form .form-input input[type="submit"] {
            color: #2f2911;
        }
        #newsletter-form form .form-input input[type="submit"]:hover,
        #newsletter-form form .form-input input[type="submit"]:focus-visible {
            color: #fff;
            background-color: #2f2911;
        }
        #lauco-consent .lauco-consent__copy a {
            color: #5e4c28;
        }
        #home-wrap a:focus-visible, #newsletter-submit:focus-visible,
        #lauco-consent a:focus-visible {
            outline: 2px solid currentColor;
            outline-offset: 3px;
        }
    </style>
</head>
<body>

    <?php require_once LAUCO_VIEW_PATH . '/partials/consent.php'; ?>

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
