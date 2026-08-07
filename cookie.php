<?php
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'inc/header.php'; ?>

    <style>
        .institutional-page .lead-text {
            font-size: 18px;
            line-height: 1.75;
            color: #555;
            margin-bottom: 32px;
        }

        .institutional-page .info-card {
            background: #fff;
            padding: 28px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,.06);
            border: 1px solid rgba(0,0,0,.04);
            min-height: 220px;
        }

        .institutional-page .info-card h3,
        .institutional-page .info-card h4 {
            margin-top: 0;
            margin-bottom: 14px;
        }

        .institutional-page .info-card p,
        .institutional-page .info-card li {
            color: #666;
            line-height: 1.75;
        }

        .institutional-page .info-card ul,
        .institutional-page .info-card ol {
            padding-left: 19px;
            margin-bottom: 0;
        }

        .institutional-page .callout {
            background: #f7f7f7;
            padding: 28px;
            margin: 28px 0;
            border-left: 4px solid #222;
        }

        .institutional-page .callout p:last-child {
            margin-bottom: 0;
        }

        .institutional-page .simple-table {
            width: 100%;
            background: #fff;
            margin: 20px 0 30px;
            border-collapse: collapse;
            box-shadow: 0 10px 30px rgba(0,0,0,.06);
        }

        .institutional-page .simple-table th,
        .institutional-page .simple-table td {
            padding: 15px 18px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
            line-height: 1.55;
        }

        .institutional-page .simple-table th {
            width: 28%;
            color: #222;
            font-weight: 700;
            background: #fafafa;
        }

        .institutional-page .small-note {
            font-size: 13px;
            color: #777;
            line-height: 1.65;
        }

        @media (max-width: 767px) {
            .institutional-page .info-card {
                min-height: auto;
                padding: 22px;
            }

            .institutional-page .simple-table th,
            .institutional-page .simple-table td {
                display: block;
                width: 100%;
            }
        }
    </style>

</head>
<body>
    <div id="myloader">
        <span class="loader"><div class="inner-loader"></div></span>
    </div>

    <div id="main-wrap" class="full-width">
        <?php include 'inc/menu.php'; ?>

        <div id="page-content" class="header-static">
            <div id="flexslider" class="fullpage-wrap small">
                <ul class="slides">
                    <li style="background-image:url(assets/img/trip9.jpg)">
                        <div class="container text text-center">
                            <h1 class="white margin-bottom-small">Cookie</h1>
                            <p class="heading white">Informazioni sui cookie tecnici e sugli strumenti di tracciamento.</p>
                        </div>
                        <div class="gradient dark"></div>
                    </li>

                    <ol class="breadcrumb">
                        <li><a href="index.php">Home</a></li>
                        <li class="active">Cookie</li>
                    </ol>
                </ul>
            </div>

            <div id="page-wrap" class="content-section fullpage-wrap institutional-page">

                <div class="container text">
                    <div class="row margin-null">
                        <div class="col-md-12 padding-leftright-null">
                            <h2 class="margin-bottom-null title line left">Cookie policy</h2>
                            <p class="heading left grey margin-bottom">Informazioni sull’uso di cookie e strumenti tecnici del sito.</p>

                            <p class="lead-text">
                                Questa pagina spiega quali cookie e strumenti tecnici possono essere utilizzati dal sito Lauco Experience.
                                Il sito è impostato per usare solo strumenti necessari al funzionamento e alla sicurezza, salvo diversa integrazione futura.
                            </p>

                            <div class="callout">
                                <p><strong>Titolare:</strong> Comune di Lauco, Via Capoluogo 104, 33029 Lauco (UD).</p>
                                <p><strong>Contatti:</strong> <a href="mailto:protocollo@comune.lauco.ud.it">protocollo@comune.lauco.ud.it</a> · <a href="mailto:comune.lauco@certgov.fvg.it">comune.lauco@certgov.fvg.it</a></p>
                            </div>
                        </div>
                    </div>

                    <div class="row padding-onlytop-md">
                        <div class="col-md-12">
                            <table class="simple-table">
                                <tbody>
                                    <tr>
                                        <th>Cookie tecnici</th>
                                        <td>Possono essere utilizzati per il corretto funzionamento del sito, la gestione della sessione, la sicurezza e l’invio dei moduli.</td>
                                    </tr>
                                    <tr>
                                        <th>Cookie di preferenza</th>
                                        <td>Possono essere utilizzati per ricordare impostazioni tecniche strettamente collegate alla navigazione.</td>
                                    </tr>
                                    <tr>
                                        <th>Cookie analitici</th>
                                        <td>Al momento non vengono dichiarati strumenti analitici di profilazione. Se saranno attivati strumenti statistici, la policy dovrà essere aggiornata.</td>
                                    </tr>
                                    <tr>
                                        <th>Cookie di profilazione</th>
                                        <td>Il sito non è progettato per utilizzare cookie di profilazione pubblicitaria o tracciamento comportamentale.</td>
                                    </tr>
                                    <tr>
                                        <th>Contenuti esterni</th>
                                        <td>Eventuali mappe, video, font, widget o servizi di terze parti potrebbero impostare cookie propri. L’uso di tali servizi dovrà essere valutato e indicato in questa pagina.</td>
                                    </tr>
                                    <tr>
                                        <th>Gestione dal browser</th>
                                        <td>L’utente può cancellare o bloccare i cookie tramite le impostazioni del browser. La disabilitazione dei cookie tecnici può compromettere alcune funzionalità.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row padding-onlytop-md">
                        <div class="col-md-6">
                            <div class="info-card">
                                <h3>Cookie tecnici e consenso</h3>
                                <p>
                                    I cookie strettamente necessari al funzionamento del sito non richiedono il consenso preventivo dell’utente,
                                    ma devono essere descritti in modo chiaro nella presente informativa.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-card">
                                <h3>Aggiornamenti</h3>
                                <p>
                                    Se in futuro saranno aggiunti strumenti di analytics, mappe esterne, video incorporati o servizi di marketing,
                                    questa pagina dovrà essere aggiornata e, ove necessario, dovrà essere introdotto un sistema di consenso.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row padding-onlytop-md">
                        <div class="col-md-12">
                            <p class="small-note">
                                Ultimo aggiornamento: giugno 2026. La classificazione dei cookie deve essere verificata ogni volta che vengono aggiunti script,
                                plugin o servizi esterni al sito.
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <?php include 'inc/footer.php'; ?>
    </div>

    <?php include 'inc/scripts.php'; ?>
</body>
</html>
