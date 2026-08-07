<?php
/** Punto d’ingresso itinerari a piedi */
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'inc/header.php'; ?>
    <link rel="stylesheet" href="assets/css/percorso.css">
</head>
<body>
    <div id="myloader">
        <span class="loader"><div class="inner-loader"></div></span>
    </div>

    <div id="main-wrap" class="full-width">
        <?php include 'inc/menu.php'; ?>
        <?php include 'sections/itinerari-piedi.php'; ?>
        <?php include 'inc/footerf.php'; ?>
    </div>

    <?php include 'inc/scripts.php'; ?>
</body>
</html>
