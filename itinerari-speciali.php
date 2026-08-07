<?php
$soloSpeciali = true;
$titoloPagina = 'Itinerari speciali';
$sottotitoloPagina = 'Percorsi selezionati per scoprire il territorio di Lauco';
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

        <?php include 'sections/itinerari-list.php'; ?>

        <?php include 'inc/footer.php'; ?>
    </div>

    <?php include 'inc/scripts.php'; ?>
</body>
</html>
