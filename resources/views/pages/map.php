<?php
/** Pagina mappa – punto d’ingresso */
$printMode = isset($_GET['print']) && (string) $_GET['print'] === '1';
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>

    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />

    <!-- Plugin gesture handling -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-gesture-handling/dist/leaflet-gesture-handling.min.css" />

    <!-- leaflet-elevation -->
    <link rel="stylesheet" href="https://unpkg.com/@raruto/leaflet-elevation@2.5.2/dist/leaflet-elevation.min.css">

    <!-- CSS della pagina mappa -->
    <link rel="stylesheet" href="assets/css/mappa.css">
    <?php if ($printMode): ?>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        html, body, #main-wrap, #page-content, #map-wrap { width: 100% !important; height: 100% !important; margin: 0 !important; padding: 0 !important; }
        #header, footer, #footer, #footerf, #myloader { display: none !important; }
        #page-content { padding-top: 0 !important; }
        #map-wrap { min-height: 180mm !important; }
        @media print {
            #header, footer, #footer, #footerf, #myloader { display: none !important; }
            #page-content, #map-wrap { min-height: 180mm !important; height: 180mm !important; }
            .leaflet-control-zoom, .leaflet-control-attribution { display: none !important; }
        }
    </style>
    <?php endif; ?>
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
        <div id="page-content" class="header-static footer-fixed">
            <div id="map-wrap" class="content-section fullpage-wrap">
                <?php require LAUCO_VIEW_PATH . '/sections/map.php'; ?>
            </div>
        </div>

        <!-- Footer -->
        <?php require LAUCO_VIEW_PATH . '/partials/footerf.php'; ?>

    </div>

    <!-- Scripts globali -->
    <?php require LAUCO_VIEW_PATH . '/partials/scripts.php'; ?>

    <!-- Librerie mappa -->
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-gesture-handling/dist/leaflet-gesture-handling.min.js"></script>
    <script src="https://unpkg.com/@tmcw/togeojson@5.8.1/dist/togeojson.umd.js"></script>
    <script src="https://unpkg.com/@turf/turf@6.5.0/turf.min.js"></script>
    <script src="https://unpkg.com/d3@6.5.0/dist/d3.min.js"></script>
    <script src="https://unpkg.com/@raruto/leaflet-elevation@2.5.2/dist/leaflet-elevation.min.js"></script>

    <!-- Script pagina mappa -->
    <script src="assets/js/mappa.js"></script>
    <?php if ($printMode): ?>
    <script>
        window.addEventListener('load', function () {
            window.setTimeout(function () {
                window.dispatchEvent(new Event('resize'));
                window.setTimeout(function () { window.print(); }, 500);
            }, 2200);
        });
    </script>
    <?php endif; ?>
</body>
</html>
