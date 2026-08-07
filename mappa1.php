<?php
/** Punto d’ingresso – versione con struttura fedele all’HTML originale */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'inc/header.php'; ?>
    

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

    <!-- leaflet-elevation -->
    <link rel="stylesheet" href="https://unpkg.com/@raruto/leaflet-elevation@2.5.2/dist/leaflet-elevation.min.css">
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

        <?php include 'sections/contatti.php'; ?>

        <!-- Footer -->
        <?php include 'inc/footer.php'; ?>

    </div><!-- /#main-wrap -->

    <!-- Scripts -->
    <?php include 'inc/scripts.php'; ?>
</body>
</html>