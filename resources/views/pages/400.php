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

        <?php require LAUCO_VIEW_PATH . '/sections/400.php'; ?>

        <!-- Footer -->
        <?php require LAUCO_VIEW_PATH . '/partials/footer.php'; ?>

    </div><!-- /#main-wrap -->

    <!-- Scripts -->
    <?php require LAUCO_VIEW_PATH . '/partials/scripts.php'; ?>
</body>
</html>
