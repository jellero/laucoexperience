<?php
/** Pagina mappa – punto d’ingresso */
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'inc/header.php'; ?>

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

    <!-- Plugin gesture handling -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-gesture-handling/dist/leaflet-gesture-handling.min.css" />

    <!-- leaflet-elevation -->
    <link rel="stylesheet" href="https://unpkg.com/@raruto/leaflet-elevation@2.5.2/dist/leaflet-elevation.min.css">

    <!-- CSS della pagina mappa -->
    <link rel="stylesheet" href="assets/css/mappa.css">
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
        <div id="page-content" class="header-static footer-fixed">
            <div id="map-wrap" class="content-section fullpage-wrap">
                <?php include 'sections/map.php'; ?>
            </div>
        </div>

        <!-- Footer -->
        <?php include 'inc/footerf.php'; ?>

    </div>

    <!-- Scripts globali -->
    <?php include 'inc/scripts.php'; ?>

    <!-- Librerie mappa -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-gesture-handling/dist/leaflet-gesture-handling.min.js"></script>
    <script src="https://unpkg.com/@tmcw/togeojson@5.8.1/dist/togeojson.umd.js"></script>
    <script src="https://unpkg.com/@turf/turf@6.5.0/turf.min.js"></script>
    <script src="https://unpkg.com/d3@6.5.0/dist/d3.min.js"></script>
    <script src="https://unpkg.com/@raruto/leaflet-elevation@2.5.2/dist/leaflet-elevation.min.js"></script>

    <!-- Script pagina mappa -->
    <script src="assets/js/mappa.js"></script>
</body>
</html>