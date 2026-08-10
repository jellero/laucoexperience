<?php
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>
    <style>
        .institutional-page .lead-text{font-size:18px;line-height:1.75;color:#555;margin-bottom:32px}
        .institutional-page .info-card{background:#fff;padding:28px;margin-bottom:25px;box-shadow:0 10px 30px rgba(0,0,0,.06);border:1px solid rgba(0,0,0,.04);min-height:220px}
        .institutional-page .info-card h3,.institutional-page .info-card h4{margin-top:0;margin-bottom:14px}
        .institutional-page .info-card p,.institutional-page .info-card li{color:#666;line-height:1.75}
        .institutional-page .callout{background:#f7f7f7;padding:28px;margin:28px 0;border-left:4px solid #222}
        .institutional-page .callout p:last-child{margin-bottom:0}
        .institutional-page .simple-table{width:100%;background:#fff;margin:20px 0 30px;border-collapse:collapse;box-shadow:0 10px 30px rgba(0,0,0,.06)}
        .institutional-page .simple-table th,.institutional-page .simple-table td{padding:15px 18px;border-bottom:1px solid #eee;vertical-align:top;line-height:1.55}
        .institutional-page .simple-table th{width:28%;color:#222;font-weight:700;background:#fafafa}
        .institutional-page .small-note{font-size:13px;color:#777;line-height:1.65}
        @media(max-width:767px){.institutional-page .info-card{min-height:auto;padding:22px}.institutional-page .simple-table th,.institutional-page .simple-table td{display:block;width:100%}}
    </style>
</head>
<body>
    <div id="myloader"><span class="loader"><div class="inner-loader"></div></span></div>
    <div id="main-wrap" class="full-width">
        <?php require LAUCO_VIEW_PATH . '/partials/menu.php'; ?>
        <div id="page-content" class="header-static">
            <div id="flexslider" class="fullpage-wrap small">
                <ul class="slides">
                    <li style="background-image:url(assets/img/trip8.jpg)">
                        <div class="container text text-center">
                            <h1 class="white margin-bottom-small">Privacy</h1>
                            <p class="heading white">Informativa sul trattamento dei dati personali del sito Lauco Experience.</p>
                        </div>
                        <div class="gradient dark"></div>
                    </li>
                    <ol class="breadcrumb"><li><a href="/">Home</a></li><li class="active">Privacy</li></ol>
                </ul>
            </div>

            <div id="page-wrap" class="content-section fullpage-wrap institutional-page">
                <div class="container text">
                    <div class="row margin-null">
                        <div class="col-md-12 padding-leftright-null">
                            <h2 class="margin-bottom-null title line left">Informativa privacy</h2>
                            <p class="heading left grey margin-bottom">Informazioni essenziali sul trattamento dei dati personali.</p>
                            <p class="lead-text">Questa informativa descrive in modo sintetico come vengono trattati i dati raccolti attraverso Lauco Experience, compresi i moduli del sito e la misurazione aggregata dei QR territoriali.</p>
                            <div class="callout">
                                <p><strong>Titolare del trattamento:</strong> Comune di Lauco, Via Capoluogo 104, 33029 Lauco (UD).</p>
                                <p><strong>Email:</strong> <a href="mailto:protocollo@comune.lauco.ud.it">protocollo@comune.lauco.ud.it</a> · <strong>PEC:</strong> <a href="mailto:comune.lauco@certgov.fvg.it">comune.lauco@certgov.fvg.it</a></p>
                            </div>
                        </div>
                    </div>

                    <div class="row padding-onlytop-md">
                        <div class="col-md-12">
                            <table class="simple-table"><tbody>
                                <tr><th>Dati trattati nei moduli</th><td>Nome, email, contenuto del messaggio, eventuali dati tecnici collegati all’invio, indirizzo IP, user agent e informazioni volontariamente comunicate dall’utente.</td></tr>
                                <tr><th>Finalità dei moduli</th><td>Rispondere alle richieste, gestire segnalazioni, valutare contributi, aggiornare le informazioni del progetto e garantire il corretto funzionamento del sito.</td></tr>
                                <tr><th>Statistiche QR</th><td>Per i QR territoriali il database statistico conserva esclusivamente la data, il codice del QR e il numero aggregato di scansioni della giornata. Non salva nel sistema statistico indirizzo IP, user agent, geolocalizzazione, identificativi del dispositivo o identificativi del visitatore.</td></tr>
                                <tr><th>Finalità statistiche QR</th><td>Misurare in forma aggregata quali punti informativi vengono consultati e migliorare segnaletica, contenuti e servizio. I contatori non sono progettati per ricostruire il percorso del singolo visitatore né per effettuare profilazione.</td></tr>
                                <tr><th>Base giuridica</th><td>Esecuzione di attività istituzionali, gestione delle richieste dell’interessato, adempimento di obblighi di legge e legittima gestione tecnica del sito.</td></tr>
                                <tr><th>Conferimento</th><td>Il conferimento dei dati tramite modulo è facoltativo, ma necessario per ricevere una risposta o permettere la gestione della segnalazione.</td></tr>
                                <tr><th>Conservazione</th><td>I dati dei moduli sono conservati per il tempo necessario alla gestione della richiesta e secondo la normativa applicabile alla documentazione amministrativa. Le statistiche QR restano aggregate per giorno e codice.</td></tr>
                                <tr><th>Destinatari</th><td>I dati possono essere trattati da personale autorizzato, fornitori tecnici del sito e soggetti che supportano il Comune nella gestione dei servizi digitali.</td></tr>
                                <tr><th>Trasferimenti</th><td>Non sono previsti trasferimenti volontari verso Paesi extra UE, salvo eventuali servizi tecnici di terze parti impostati nel rispetto della normativa applicabile.</td></tr>
                                <tr><th>Diritti</th><td>L’interessato può chiedere accesso, rettifica, cancellazione, limitazione, opposizione e, ove applicabile, portabilità dei dati, contattando il Comune di Lauco.</td></tr>
                                <tr><th>Reclamo</th><td>L’interessato può proporre reclamo all’Autorità Garante per la protezione dei dati personali secondo le modalità indicate sul sito dell’Autorità.</td></tr>
                            </tbody></table>

                            <div class="info-card">
                                <h3>Misurazione QR privacy by design</h3>
                                <p>Il redirect dei QR incrementa direttamente un contatore giornaliero associato al codice. Non viene creata una riga per ogni visita e il componente non legge né imposta cookie analitici. Gli eventuali log tecnici generali dell’infrastruttura web restano distinti da questa funzione statistica.</p>
                            </div>
                            <p class="small-note">La presente pagina è una informativa operativa per il sito tematico Lauco Experience. Per gli aspetti generali di privacy, note legali e protezione dei dati si rinvia anche alle informazioni pubblicate sul sito istituzionale del Comune di Lauco.</p>
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
