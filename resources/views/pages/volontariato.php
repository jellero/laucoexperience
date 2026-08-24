<?php
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>
</head>
<body>
    <div id="myloader">
        <span class="loader"><div class="inner-loader"></div></span>
    </div>

    <div id="main-wrap" class="full-width">
        <?php require LAUCO_VIEW_PATH . '/partials/menu.php'; ?>

        <div id="page-content" class="header-static">
            <div id="flexslider" class="fullpage-wrap small">
                <ul class="slides">
                    <li style="background-image:url(assets/img/sentieri.webp)">
                        <div class="container text text-center">
                            <h1 class="white margin-bottom-small">Partecipa attivamente</h1>
                            <p class="heading white">Volontariato e cura concreta del territorio di Lauco.</p>
                        </div>
                        <div class="gradient dark"></div>
                    </li>

                    <ol class="breadcrumb">
                        <li><a href="/">Home</a></li>
                        <li class="active">Volontariato</li>
                    </ol>
                </ul>
            </div>

            <?php require LAUCO_VIEW_PATH . '/sections/volontariato.php'; ?>
        </div>

        <?php require LAUCO_VIEW_PATH . '/partials/footer.php'; ?>
    </div>

    <?php require LAUCO_VIEW_PATH . '/partials/scripts.php'; ?>
</body>
</html>
