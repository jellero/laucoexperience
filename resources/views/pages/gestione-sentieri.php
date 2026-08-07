<?php
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>

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
        <?php require LAUCO_VIEW_PATH . '/partials/menu.php'; ?>

        <div id="page-content" class="header-static">
            <div id="flexslider" class="fullpage-wrap small">
                <ul class="slides">
                    <li style="background-image:url(assets/img/sentieri.webp)">
                        <div class="container text text-center">
                            <h1 class="white margin-bottom-small">Gestione sentieri</h1>
                            <p class="heading white">Come vengono organizzate, aggiornate e verificate le informazioni sui percorsi.</p>
                        </div>
                        <div class="gradient dark"></div>
                    </li>

                    <ol class="breadcrumb">
                        <li><a href="/">Home</a></li>
                        <li class="active">Gestione sentieri</li>
                    </ol>
                </ul>
            </div>

            <div id="page-wrap" class="content-section fullpage-wrap institutional-page">

                <div class="container text">
                    <div class="row margin-null">
                        <div class="col-md-12 padding-leftright-null">
                            <h2 class="margin-bottom-null title line left">Gestione dei sentieri</h2>
                            <p class="heading left grey margin-bottom">Criteri, responsabilità e modalità di aggiornamento delle informazioni pubblicate.</p>

                            <p class="lead-text">
                                La sezione sentieri di Lauco Experience raccoglie percorsi, tracce, immagini e informazioni utili alla fruizione del territorio.
                                La responsabilità istituzionale del progetto è del Comune di Lauco, che coordina la pubblicazione dei contenuti e la loro revisione progressiva.
                            </p>

                            <div class="callout">
                                <p><strong>Responsabile del progetto:</strong> Comune di Lauco, Via Capoluogo 104, 33029 Lauco (UD).</p>
                                <p><strong>Contatti:</strong> <a href="mailto:info@laucoexperience.it">info@laucoexperience.it</a></p>
                            </div>
                        </div>
                    </div>

                    <div class="row padding-onlytop-md">
                        <div class="col-md-4">
                            <div class="info-card">
                                <h3>Contenuti pubblicati</h3>
                                <p>
                                    Le schede dei percorsi possono includere descrizione, località, difficoltà, durata indicativa,
                                    dislivello, immagini, mappa e traccia GPX scaricabile.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="info-card">
                                <h3>Aggiornamento</h3>
                                <p>
                                    I contenuti vengono aggiornati in base alle verifiche disponibili, alle segnalazioni ricevute e alle modifiche
                                    del territorio, della segnaletica o della percorribilità.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="info-card">
                                <h3>Sicurezza</h3>
                                <p>
                                    Le informazioni hanno carattere orientativo. Prima di partire è sempre necessario valutare meteo,
                                    stagione, capacità personali, attrezzatura e condizioni reali del tracciato.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row padding-onlytop-md">
                        <div class="col-md-6">
                            <div class="info-card">
                                <h3>Cosa viene controllato</h3>
                                <ul>
                                    <li>coerenza della descrizione del percorso;</li>
                                    <li>presenza e correttezza dei dati tecnici disponibili;</li>
                                    <li>leggibilità della traccia e della mappa;</li>
                                    <li>qualità e pertinenza delle immagini;</li>
                                    <li>eventuali segnalazioni su criticità o variazioni.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-card">
                                <h3>Segnalazioni</h3>
                                <p>
                                    Chi nota errori, tratti non percorribili, problemi di segnaletica, ostacoli, frane, alberi caduti o informazioni non aggiornate
                                    può comunicarlo tramite la pagina contatti o tramite gli strumenti di segnalazione disponibili sul sito.
                                </p>
                                <p><a class="btn-alt small" href="contatti">Contattaci</a></p>
                            </div>
                        </div>
                    </div>

                    <div class="row padding-onlytop-md">
                        <div class="col-md-12">
                            <h2 class="small">Avvertenze operative</h2>
                            <table class="simple-table">
                                <tbody>
                                    <tr>
                                        <th>Percorribilità</th>
                                        <td>La presenza di una scheda sul sito non garantisce che il percorso sia sempre percorribile in ogni stagione o condizione.</td>
                                    </tr>
                                    <tr>
                                        <th>Tracce GPX</th>
                                        <td>Le tracce sono strumenti di supporto e possono presentare approssimazioni dovute al dispositivo, al segnale o alle variazioni del territorio.</td>
                                    </tr>
                                    <tr>
                                        <th>Difficoltà</th>
                                        <td>La difficoltà è indicativa e deve essere valutata anche in base a preparazione, meteo, terreno e condizioni fisiche.</td>
                                    </tr>
                                    <tr>
                                        <th>Responsabilità personale</th>
                                        <td>Ogni escursionista resta responsabile delle proprie scelte, della propria attrezzatura e della valutazione del rischio.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <?php require LAUCO_VIEW_PATH . '/partials/footer.php'; ?>
    </div>

    <?php require LAUCO_VIEW_PATH . '/partials/scripts.php'; ?>
</body>
</html>
