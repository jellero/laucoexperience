<?php
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'inc/header.php'; ?>

    <style>
        .advice-page .lead-text {
            font-size: 18px;
            line-height: 1.75;
            color: #555;
            margin-bottom: 34px;
        }

        .advice-page .advice-card {
            background: #fff;
            padding: 28px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,.06);
            border: 1px solid rgba(0,0,0,.04);
            min-height: 250px;
        }

        .advice-page .advice-card.compact {
            min-height: 190px;
        }

        .advice-page .advice-card h3,
        .advice-page .advice-card h4 {
            margin-top: 0;
            margin-bottom: 14px;
        }

        .advice-page .advice-card p,
        .advice-page .advice-card li {
            color: #666;
            line-height: 1.75;
        }

        .advice-page .advice-card ul,
        .advice-page .advice-card ol {
            padding-left: 19px;
            margin-bottom: 0;
        }

        .advice-page .advice-icon {
            font-size: 42px;
            display: block;
            margin-bottom: 18px;
            color: #222;
        }

        .advice-page .callout {
            background: #f7f7f7;
            padding: 28px;
            margin: 28px 0;
            border-left: 4px solid #222;
        }

        .advice-page .callout.warning {
            border-left-color: #9b1c1c;
            background: #fbf1f1;
        }

        .advice-page .callout p:last-child {
            margin-bottom: 0;
        }

        .advice-page .simple-table {
            width: 100%;
            background: #fff;
            margin: 20px 0 30px;
            border-collapse: collapse;
            box-shadow: 0 10px 30px rgba(0,0,0,.06);
        }

        .advice-page .simple-table th,
        .advice-page .simple-table td {
            padding: 15px 18px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
            line-height: 1.55;
        }

        .advice-page .simple-table th {
            width: 26%;
            color: #222;
            font-weight: 700;
            background: #fafafa;
        }

        .advice-page .mini-label {
            display: inline-block;
            padding: 6px 9px;
            margin: 0 6px 8px 0;
            background: #efefef;
            color: #333;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: .03em;
            text-transform: uppercase;
        }

        .advice-page .route-check {
            background: #fff;
            padding: 26px;
            margin-top: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,.06);
        }

        .advice-page .route-check li {
            margin-bottom: 9px;
            color: #666;
            line-height: 1.7;
        }

        .advice-page .small-note {
            font-size: 13px;
            color: #777;
            line-height: 1.65;
        }

        @media (max-width: 767px) {
            .advice-page .advice-card {
                min-height: auto;
                padding: 22px;
            }

            .advice-page .simple-table th,
            .advice-page .simple-table td {
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
                    <li style="background-image:url(assets/img/taressa.jpg)">
                        <div class="container text text-center">
                            <h1 class="white margin-bottom-small">Consigli escursionistici</h1>
                            <p class="heading white">Preparazione, prudenza e buone pratiche per vivere i sentieri di Lauco in sicurezza.</p>
                        </div>
                        <div class="gradient dark"></div>
                    </li>

                    <ol class="breadcrumb">
                        <li><a href="index.php">Home</a></li>
                        <li class="active">Consigli</li>
                    </ol>
                </ul>
            </div>

            <div id="page-wrap" class="content-section fullpage-wrap advice-page">
                <div class="container text">
                    <div class="row margin-null">
                        <div class="col-md-12 padding-leftright-null">
                            <h2 class="margin-bottom-null title line left">Prima di partire</h2>
                            <p class="heading left grey margin-bottom">Un’escursione piacevole inizia da una scelta corretta del percorso.</p>

                            <p class="lead-text">
                                I sentieri del territorio di Lauco attraversano ambienti naturali, boschi, pendii, prati, mulattiere e tratti che possono cambiare rapidamente
                                in base a stagione, meteo e manutenzione. Le informazioni pubblicate sul sito sono uno strumento di supporto, ma non sostituiscono
                                la valutazione personale, la preparazione e la prudenza sul campo.
                            </p>

                            <div class="callout warning">
                                <p>
                                    <strong>Importante:</strong> non affrontare un percorso solo perché è presente online. Valuta sempre meteo, durata, dislivello,
                                    difficoltà, attrezzatura, allenamento, condizioni del terreno e orario di rientro. In caso di dubbio, scegli un itinerario più semplice.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row padding-onlytop-md">
                        <div class="col-md-4">
                            <div class="advice-card">
                                <i class="icon ion-ios-compass-outline advice-icon"></i>
                                <h3>Scegli il percorso giusto</h3>
                                <p>
                                    Parti da itinerari adatti alla tua esperienza. Distanza, dislivello e durata non vanno valutati separatamente:
                                    un percorso breve ma ripido o esposto può essere più impegnativo di uno più lungo ma regolare.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="advice-card">
                                <i class="icon ion-ios-cloud-outline advice-icon"></i>
                                <h3>Controlla il meteo</h3>
                                <p>
                                    Verifica le previsioni prima della partenza e osserva l’evoluzione del tempo durante l’escursione.
                                    Pioggia, temporali, vento, neve, ghiaccio o nebbia possono rendere difficile anche un sentiero semplice.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="advice-card">
                                <i class="icon ion-ios-clock-outline advice-icon"></i>
                                <h3>Calcola i tempi</h3>
                                <p>
                                    Parti con margine, evita gli orari troppo tardi e considera pause, passo del gruppo, rientro e ore di luce.
                                    Non impostare l’escursione sul ritmo della persona più veloce, ma su quello della più lenta.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row padding-onlytop-md">
                        <div class="col-md-12">
                            <h2 class="small">Checklist essenziale</h2>
                            <div class="route-check">
                                <div class="row">
                                    <div class="col-md-6">
                                        <ul>
                                            <li>scarpe adeguate al terreno, meglio con suola scolpita;</li>
                                            <li>acqua sufficiente e qualcosa da mangiare;</li>
                                            <li>giacca antivento o antipioggia, anche con tempo buono;</li>
                                            <li>telefono carico, meglio con power bank;</li>
                                            <li>mappa, traccia GPX o indicazioni del percorso già salvate offline;</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <ul>
                                            <li>abbigliamento a strati, adatto a cambi di temperatura;</li>
                                            <li>piccolo kit di primo soccorso personale;</li>
                                            <li>lampada frontale se l’escursione può prolungarsi;</li>
                                            <li>protezione solare, cappello e occhiali nelle giornate esposte;</li>
                                            <li>informare qualcuno su itinerario e orario indicativo di rientro.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row padding-onlytop-md">
                        <div class="col-md-6">
                            <div class="advice-card">
                                <h3>Segnaletica e orientamento</h3>
                                <p>
                                    La segnaletica aiuta, ma può essere danneggiata, coperta dalla vegetazione o temporaneamente assente.
                                    Non proseguire alla cieca se perdi la traccia: torna all’ultimo punto certo e rivaluta il percorso.
                                </p>
                                <ul>
                                    <li>controlla spesso posizione e direzione;</li>
                                    <li>non affidarti solo al telefono se la batteria è bassa;</li>
                                    <li>scarica mappe e tracce prima di partire;</li>
                                    <li>rispetta proprietà private, divieti e indicazioni locali.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="advice-card">
                                <h3>Uso delle tracce GPX</h3>
                                <p>
                                    Le tracce GPX sono uno strumento utile, ma non sono una garanzia assoluta. Possono contenere approssimazioni,
                                    deviazioni temporanee o dati registrati in condizioni diverse da quelle attuali.
                                </p>
                                <ul>
                                    <li>usa il GPX come supporto, non come unica fonte;</li>
                                    <li>verifica che la traccia corrisponda al percorso scelto;</li>
                                    <li>non seguire una traccia se porta su terreno pericoloso;</li>
                                    <li>in caso di dubbio, torna indietro.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="row padding-onlytop-md">
                        <div class="col-md-4">
                            <div class="advice-card compact">
                                <h3>Acqua e alimentazione</h3>
                                <p>
                                    Porta acqua in quantità adeguata, soprattutto in estate o sui percorsi esposti. Non fare affidamento sulla presenza di fontane
                                    o sorgenti se non sono indicate e verificate.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="advice-card compact">
                                <h3>Gruppi e bambini</h3>
                                <p>
                                    Con bambini o gruppi eterogenei scegli itinerari brevi, con dislivello contenuto e punti di rientro chiari.
                                    Prevedi pause frequenti e tempi più larghi.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="advice-card compact">
                                <h3>Cani al seguito</h3>
                                <p>
                                    Tieni il cane sotto controllo, porta acqua anche per lui e rispetta fauna, altri escursionisti, proprietà private e aree sensibili.
                                    Usa il guinzaglio dove necessario.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row padding-onlytop-md">
                        <div class="col-md-12">
                            <h2 class="small">Comportamento sui sentieri</h2>
                            <table class="simple-table">
                                <tbody>
                                    <tr>
                                        <th>Resta sul tracciato</th>
                                        <td>Evita scorciatoie e tagli: danneggiano il terreno, aumentano l’erosione e possono creare situazioni pericolose.</td>
                                    </tr>
                                    <tr>
                                        <th>Rispetta l’ambiente</th>
                                        <td>Non lasciare rifiuti, non raccogliere specie protette, non disturbare la fauna e limita il rumore.</td>
                                    </tr>
                                    <tr>
                                        <th>Dai precedenza</th>
                                        <td>Su tratti stretti o ripidi fermati in posizione sicura e agevola il passaggio di chi è più esposto o in difficoltà.</td>
                                    </tr>
                                    <tr>
                                        <th>Attenzione al fondo</th>
                                        <td>Radici, foglie, sassi bagnati, fango, neve o ghiaccio possono cambiare completamente la difficoltà del percorso.</td>
                                    </tr>
                                    <tr>
                                        <th>Non sottovalutare il rientro</th>
                                        <td>La stanchezza aumenta il rischio di scivolate, errori di orientamento e decisioni affrettate.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="row padding-onlytop-md">
                        <div class="col-md-6">
                            <div class="advice-card">
                                <h3>In caso di difficoltà</h3>
                                <p>
                                    Fermati, mantieni la calma e valuta la situazione. Se hai perso il sentiero, evita di scendere a caso per boschi o canaloni.
                                    Torna all’ultimo punto sicuro, controlla mappa e posizione e, se necessario, chiedi aiuto.
                                </p>
                                <p>
                                    In caso di emergenza chiama il <strong>112</strong>, indica cosa è successo, dove ti trovi, quante persone sono coinvolte
                                    e quali sono le condizioni del gruppo.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="advice-card">
                                <h3>Quando rinunciare</h3>
                                <p>
                                    Rinunciare non è un fallimento. È la scelta corretta quando meteo, terreno, stanchezza, orario, attrezzatura o sicurezza
                                    non sono compatibili con il percorso.
                                </p>
                                <ul>
                                    <li>temporali in avvicinamento;</li>
                                    <li>sentiero non riconoscibile;</li>
                                    <li>neve o ghiaccio non previsti;</li>
                                    <li>mancanza di acqua o luce;</li>
                                    <li>persona del gruppo in difficoltà.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="row padding-onlytop-md">
                        <div class="col-md-12">
                            <h2 class="small">Consigli per stagione</h2>
                            <table class="simple-table">
                                <tbody>
                                    <tr>
                                        <th>Primavera</th>
                                        <td>Possibili tratti fangosi, residui di neve, alberi caduti o vegetazione alta. Verifica sempre percorribilità e meteo.</td>
                                    </tr>
                                    <tr>
                                        <th>Estate</th>
                                        <td>Parti presto, porta acqua, protezione solare e attenzione ai temporali pomeridiani.</td>
                                    </tr>
                                    <tr>
                                        <th>Autunno</th>
                                        <td>Fogliame, umidità e giornate più corte richiedono maggiore attenzione a fondo e orari.</td>
                                    </tr>
                                    <tr>
                                        <th>Inverno</th>
                                        <td>Neve, ghiaccio e freddo possono trasformare l’escursione in attività tecnica. Non improvvisare senza esperienza e attrezzatura.</td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="callout">
                                <p>
                                    Se noti errori nelle schede, problemi di segnaletica, tratti non percorribili o informazioni non aggiornate,
                                    invia una segnalazione al Comune di Lauco tramite la pagina contatti.
                                </p>
                                <p>
                                    <a class="btn-alt small" href="segnala-problema">Segnala un aggiornamento</a>
                                    <a class="btn-alt small active" href="itinerari-piedi">Scopri gli itinerari</a>
                                </p>
                            </div>

                            <p class="small-note">
                                Le indicazioni presenti in questa pagina hanno finalità informative e non sostituiscono formazione, esperienza,
                                prudenza e valutazione diretta delle condizioni del percorso.
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
