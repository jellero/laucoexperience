<?php
/** Punto d’ingresso itinerari MTB */
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>
    <link rel="stylesheet" href="assets/css/percorso.css">
</head>
<body>
    <div id="myloader">
        <span class="loader"><div class="inner-loader"></div></span>
    </div>

    <div id="main-wrap" class="full-width">
        <?php require LAUCO_VIEW_PATH . '/partials/menu.php'; ?>
        <?php require LAUCO_VIEW_PATH . '/sections/itinerari-mtb.php'; ?>
        <?php require LAUCO_VIEW_PATH . '/partials/footerf.php'; ?>
    </div>

    <?php require LAUCO_VIEW_PATH . '/partials/scripts.php'; ?>
</body>
</html>
