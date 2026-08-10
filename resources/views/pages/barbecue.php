<?php
require_once LAUCO_ROOT . '/inc/barbecue-images.php';

$barbecueVinaioImage = lauco_barbecue_image_data_uri('vinaio');
$barbecuePortealImage = lauco_barbecue_image_data_uri('porteal');
$barbecueHeroImage = $barbecuePortealImage !== ''
    ? $barbecuePortealImage
    : ($barbecueVinaioImage !== '' ? $barbecueVinaioImage : 'assets/img/sentieri.webp');
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>
    <style>
        .barbecue-page .lead-text {font-size:18px;line-height:1.75;color:#555;margin-bottom:32px}
        .barbecue-page .barbecue-photo {background:#fff;margin-bottom:28px;box-shadow:0 10px 30px rgba(0,0,0,.06)}
        .barbecue-page .barbecue-photo img {display:block;width:100%;height:auto}
        .barbecue-page .barbecue-photo figcaption {padding:16px 20px;color:#666;line-height:1.6}
        .barbecue-page .info-card {background:#fff;padding:28px;margin-bottom:25px;box-shadow:0 10px 30px rgba(0,0,0,.06);border:1px solid rgba(0,0,0,.04);min-height:230px}
        .barbecue-page .info-card h3 {margin-top:0;margin-bottom:14px}
        .barbecue-page .info-card p {color:#666;line-height:1.75}
        .barbecue-page .callout {background:#f7f7f7;padding:28px;margin:28px 0;border-left:4px solid #222}
        .barbecue-page .callout.upcoming {border-left-color:#7d6418;background:#fbf8ed}
        .barbecue-page .callout p:last-child {margin-bottom:0}
        .barbecue-page .small-note {font-size:13px;color:#777;line-height:1.65}
        @media (max-width:767px) {.barbecue-page .info-card{min-height:auto;padding:22px}}
    </style>
</head>
<body>
    <div id="myloader"><span class="loader"><div class="inner-loader"></div></span></div>

    <div id="main-wrap" class="full-width">
        <?php require LAUCO_VIEW_PATH . '/partials/menu.php'; ?>

        <div id="page-content" class="header-static">
            <div id="flexslider" class="fullpage-wrap small">
                <ul class="slides">
                    <li style="background-image:url('<?= htmlspecialchars($barbecueHeroImage, ENT_QUOTES, 'UTF-8') ?>');background-position:center center;">
                        <div class="container text text-center">
                            <h1 class="white margin-bottom-small">Barbecue ad uso comune</h1>
                            <p class="heading white">Porteal, Vinaio e prossimamente Val di Lauco.</p>
                        </div>
                        <div class="gradient dark"></div>
                    </li>
                    <ol class="breadcrumb">
                        <li><a href="/">Home</a></li>
                        <li class="active">Aree barbecue</li>
                    </ol>
                </ul>
            </div>

            <div id="page-wrap" class="content-section fullpage-wrap barbecue-page">
                <div class="container text">
                    <div class="row margin-null">
                        <div class="col-md-12 padding-leftright-null">
                            <h2 class="margin-bottom-null title line left">Spazi da condividere</h2>
                            <p class="heading left grey margin-bottom">Due installazioni già disponibili e una terza in arrivo.</p>
                            <p class="lead-text">A Porteal e a Vinaio sono stati installati due barbecue ad uso comune, pensati come punti di sosta e convivialità per residenti e visitatori. Una nuova installazione è prevista prossimamente anche in Val di Lauco.</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <figure class="barbecue-photo">
                                <?php if ($barbecueVinaioImage !== ''): ?>
                                    <img src="<?= htmlspecialchars($barbecueVinaioImage, ENT_QUOTES, 'UTF-8') ?>" width="1000" height="750" alt="Barbecue ad uso comune a Vinaio vicino al torrente e al ponte">
                                <?php endif; ?>
                                <figcaption><strong>Vinaio.</strong> Barbecue ad uso comune nell’area vicina al torrente e al ponte.</figcaption>
                            </figure>
                        </div>
                        <div class="col-md-6">
                            <figure class="barbecue-photo">
                                <?php if ($barbecuePortealImage !== ''): ?>
                                    <img src="<?= htmlspecialchars($barbecuePortealImage, ENT_QUOTES, 'UTF-8') ?>" width="1000" height="750" alt="Barbecue ad uso comune a Porteal con vista sul paesaggio montano">
                                <?php endif; ?>
                                <figcaption><strong>Porteal.</strong> Barbecue ad uso comune in posizione panoramica.</figcaption>
                            </figure>
                        </div>
                    </div>

                    <div class="row padding-onlytop-md">
                        <div class="col-md-6">
                            <div class="info-card">
                                <h3>Un servizio per tutti</h3>
                                <p>Le strutture sono ad uso comune: un punto semplice per fermarsi, stare insieme e vivere gli spazi all’aperto con attenzione verso il luogo e verso chi lo utilizzerà dopo.</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-card">
                                <h3>Un omaggio al territorio</h3>
                                <p>Le installazioni sono un omaggio del <strong>Giro degli Auguri</strong>, con il supporto di <strong>Valerio</strong>: un contributo concreto alla fruibilità e alla convivialità sul territorio.</p>
                            </div>
                        </div>
                    </div>

                    <div class="callout upcoming">
                        <h3>Prossimamente: Val di Lauco</h3>
                        <p>La rete si allargherà con una nuova installazione prevista in Val di Lauco. La pagina verrà aggiornata quando il nuovo barbecue sarà disponibile.</p>
                    </div>

                    <div class="callout">
                        <h3>Uso responsabile</h3>
                        <p>Usa le strutture con attenzione, lascia l’area pulita e in ordine e, prima di accendere il fuoco, verifica eventuali limitazioni temporanee e le disposizioni vigenti.</p>
                    </div>

                    <p class="small-note">Le condizioni e l’utilizzabilità delle aree possono cambiare. Sul posto fanno sempre fede eventuali indicazioni e limitazioni temporanee.</p>
                </div>
            </div>
        </div>

        <?php require LAUCO_VIEW_PATH . '/partials/footer.php'; ?>
    </div>

    <?php require LAUCO_VIEW_PATH . '/partials/scripts.php'; ?>
</body>
</html>
