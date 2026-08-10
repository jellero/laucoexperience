<?php
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>
    <style>
        .forra-page .lead-text {font-size:18px;line-height:1.75;color:#555;margin-bottom:32px}
        .forra-page .info-card {background:#fff;padding:28px;margin-bottom:25px;box-shadow:0 10px 30px rgba(0,0,0,.06);border:1px solid rgba(0,0,0,.04);min-height:245px}
        .forra-page .info-card h3 {margin-top:0;margin-bottom:14px}
        .forra-page .info-card p,.forra-page .info-card li {color:#666;line-height:1.75}
        .forra-page .info-card ul {padding-left:19px;margin-bottom:0}
        .forra-page .callout {background:#f7f7f7;padding:28px;margin:28px 0;border-left:4px solid #222}
        .forra-page .callout.warning {border-left-color:#9b1c1c;background:#fbf1f1}
        .forra-page .callout p:last-child {margin-bottom:0}
        .forra-page .action-row {display:flex;gap:12px;flex-wrap:wrap;margin-top:24px}
        .forra-page .action-row a {margin:0}
        .forra-page .small-note {font-size:13px;color:#777;line-height:1.65}
        @media (max-width:767px){.forra-page .info-card{min-height:auto;padding:22px}}
    </style>
</head>
<body>
    <div id="myloader"><span class="loader"><div class="inner-loader"></div></span></div>

    <div id="main-wrap" class="full-width">
        <?php require LAUCO_VIEW_PATH . '/partials/menu.php'; ?>

        <div id="page-content" class="header-static">
            <div id="flexslider" class="fullpage-wrap small">
                <ul class="slides">
                    <li style="background-image:url(assets/img/sentieri.webp)">
                        <div class="container text text-center">
                            <h1 class="white margin-bottom-small">Forra del Vinadia</h1>
                            <p class="heading white">Natura, accessi e informazioni per preparare la visita.</p>
                        </div>
                        <div class="gradient dark"></div>
                    </li>
                    <ol class="breadcrumb">
                        <li><a href="/">Home</a></li>
                        <li class="active">Forra del Vinadia</li>
                    </ol>
                </ul>
            </div>

            <div id="page-wrap" class="content-section fullpage-wrap forra-page">
                <div class="container text">
                    <div class="row margin-null">
                        <div class="col-md-12 padding-leftright-null">
                            <h2 class="margin-bottom-null title line left">La Forra dentro Lauco Experience</h2>
                            <p class="heading left grey margin-bottom">Un punto di accesso locale che collega la Forra al territorio di Lauco.</p>
                            <p class="lead-text">
                                La Forra del Vinadia è uno dei luoghi naturalistici più riconoscibili del territorio. Questa pagina la integra in Lauco Experience senza sostituire il sito dedicato: raccoglie i riferimenti essenziali per orientarsi e collega la visita a Vinaio, ai sentieri e agli altri punti di interesse.
                            </p>

                            <div class="callout warning" id="sicurezza">
                                <p><strong>Sicurezza prima di tutto.</strong> L’interno della gola richiede esperienza e attrezzatura adeguata. Prima della visita verifica meteo, condizioni dell’acqua e indicazioni aggiornate; per l’interno della Forra è raccomandato l’accompagnamento di una guida.</p>
                            </div>
                        </div>
                    </div>

                    <div class="row padding-onlytop-md" id="accesso">
                        <div class="col-md-4">
                            <div class="info-card">
                                <h3>Accesso e Vinaio</h3>
                                <p>Vinaio è il riferimento territoriale per l’accesso alla Forra. Prima di partire consulta le indicazioni aggiornate del sito dedicato e la segnaletica presente sul posto.</p>
                                <p>La sosta nell’area di Vinaio può essere collegata anche ai percorsi e ai punti di interesse vicini.</p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-card">
                                <h3>Condizioni da verificare</h3>
                                <ul>
                                    <li>previsioni meteo e precipitazioni recenti;</li>
                                    <li>condizioni dell’acqua e rischio di piena;</li>
                                    <li>stato degli accessi e della segnaletica;</li>
                                    <li>attrezzatura e preparazione adeguate al percorso scelto.</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-card">
                                <h3>Forra e territorio</h3>
                                <p>La visita può essere letta insieme a Vinaio, alla rete sentieristica, ai punti panoramici e all’area di sosta sopra il ponte, inserendo la Forra in un’esperienza più ampia dell’altopiano di Lauco.</p>
                            </div>
                        </div>
                    </div>

                    <div class="row padding-onlytop-md">
                        <div class="col-md-12">
                            <div class="callout">
                                <h3>Informazioni aggiornate</h3>
                                <p>Per percorso, mappa, sicurezza, ambiente, geologia, flora e fauna consulta il sito dedicato alla Forra del Vinadia. Le condizioni sul terreno possono cambiare: fai sempre riferimento alle informazioni più recenti disponibili.</p>
                                <div class="action-row">
                                    <a class="btn-alt small active" href="https://www.forravinadia.it/" target="_blank" rel="noopener noreferrer">Sito Forra del Vinadia</a>
                                    <a class="btn-alt small" href="/map">Mappa Lauco Experience</a>
                                    <a class="btn-alt small" href="/itinerari-piedi">Sentieri a piedi</a>
                                    <a class="btn-alt small" href="/segnala-problema">Segnala un problema</a>
                                </div>
                            </div>
                            <p class="small-note">Lauco Experience è uno strumento di orientamento e non sostituisce la valutazione delle condizioni reali, la preparazione personale o le indicazioni delle autorità e dei soggetti che presidiano il territorio.</p>
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
