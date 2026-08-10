<?php
require_once LAUCO_ROOT . '/inc/translations.php';
$currentLanguage = content_language_from_request();
$languageCodes = ['it' => 'ITA', 'de' => 'DEU', 'en' => 'ENG', 'sl' => 'SLO'];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLanguage, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Scala difficoltà escursionismo | Lauco Experience</title>
    <?php foreach (array_keys(content_supported_languages()) as $alternateLanguage): ?>
    <link rel="alternate" hreflang="<?= htmlspecialchars($alternateLanguage, ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars(content_language_url($alternateLanguage), ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>

    <!-- Bootstrap -->
    <link rel="stylesheet" href="assets/css/bootstrap/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/bootstrap/bootstrap-theme.min.css">

    <!-- Template CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/font-awesome.min.css">
    <link rel="stylesheet" href="assets/css/ionicons.min.css">
    <link rel="stylesheet" href="assets/css/puredesign.css">
    <link rel="stylesheet" href="assets/css/flexslider.css">
    <link rel="stylesheet" href="assets/css/owl.carousel.css">
    <link rel="stylesheet" href="assets/css/magnific-popup.css">
    <link rel="stylesheet" href="assets/css/jquery.fullPage.css">

    <style>
        .difficulty-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 24px;
            margin-top: 30px;
        }

        .difficulty-card {
            background: #fff;
            padding: 30px;
            box-shadow: 0 8px 28px rgba(0,0,0,.08);
            min-height: 100%;
        }

        .difficulty-card .code {
            display: inline-block;
            min-width: 54px;
            height: 54px;
            line-height: 54px;
            text-align: center;
            background: #222;
            color: #fff;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 18px;
        }

        .difficulty-card h3 {
            margin-top: 0;
            margin-bottom: 12px;
        }

        .difficulty-card p {
            margin-bottom: 0;
        }

        .pdf-box {
            background: #f7f7f7;
            padding: 30px;
            box-shadow: 0 8px 28px rgba(0,0,0,.06);
        }

        .pdf-viewer {
            width: 100%;
            height: 720px;
            border: 0;
            box-shadow: 0 8px 28px rgba(0,0,0,.12);
            background: #f5f5f5;
        }

        .info-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .info-list li {
            margin-bottom: 12px;
            padding-left: 26px;
            position: relative;
        }

        .info-list li:before {
            content: "\f3fe";
            font-family: "Ionicons";
            position: absolute;
            left: 0;
            top: 0;
        }

        .signage-gallery .signage-photo-card {
            margin: 0;
            background: #fff;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,.10);
        }

        .signage-gallery .signage-photo-card img {
            display: block;
            width: 100%;
            aspect-ratio: 3 / 4;
            object-fit: cover;
        }

        .signage-gallery .signage-note {
            max-width: 900px;
            margin: 28px auto 0;
            line-height: 1.75;
        }

        .contact-strip {
            background: #222;
            color: #fff;
        }

        .contact-strip h3,
        .contact-strip p {
            color: #fff;
        }

        .contact-strip a {
            color: #fff;
            text-decoration: underline;
        }

        @media (max-width: 991px) {
            .difficulty-grid {
                grid-template-columns: 1fr;
            }

            .pdf-viewer {
                height: 560px;
            }

            .signage-gallery .signage-photo-card {
                margin-bottom: 24px;
            }
        }

        @media (max-width: 600px) {
            .pdf-viewer {
                height: 420px;
            }

            .difficulty-card {
                padding: 24px;
            }
        }
    </style>

    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>

    <!-- Loader -->
    <div id="myloader">
        <span class="loader"><div class="inner-loader"></div></span>
    </div>

    <!-- Main Wrap -->
    <div id="main-wrap" class="full-width">

        <!-- Header & Menu -->
        <header id="header" class="fixed transparent full-width">
            <div class="container">
                <nav class="navbar navbar-default white">

                    <div id="logo">
                        <a class="navbar-brand" href="/">
                            <img src="assets/img/logo.png" class="normal" alt="Lauco Experience">
                            <img src="assets/img/logo.png" class="retina" alt="Lauco Experience">
                            <img src="assets/img/logo_white.png" class="normal white-logo" alt="Lauco Experience">
                            <img src="assets/img/logo_white.png" class="retina white-logo" alt="Lauco Experience">
                        </a>
                    </div>

                    <div id="menu-classic">
                        <div class="menu-holder">
                            <ul>
                                <li><a href="/">Home</a></li>

                                <li class="submenu">
                                    <a href="javascript:void(0)" class="active-item">Mappa</a>
                                    <ul class="sub-menu">
                                        <li><a href="/map">Mappa</a></li>
                                        <li><a href="/segnaletica" class="active-item">Info segnaletica</a></li>
                                        <li><a href="/consigli">Consigli escursionistici</a></li>
                                    </ul>
                                </li>

                                <li class="submenu">
                                    <a href="javascript:void(0)">Itinerari</a>
                                    <ul class="sub-menu">
                                        <li><a href="/itinerari-piedi">A piedi</a></li>
                                        <li><a href="/itinerari-mtb">In MTB</a></li>
                                    </ul>
                                </li>

                                <li class="submenu">
                                    <a href="javascript:void(0)">Speciali</a>
                                    <ul class="sub-menu">
                                        <li><a href="/itinerari-speciali">UTMA</a></li>
                                        <li><a href="/itinerari-speciali">Trail dai&nbsp;Cramârs</a></li>
                                        <li><a href="/itinerari-speciali">Cronoradime</a></li>
                                    </ul>
                                </li>

                                <li class="submenu">
                                    <a href="javascript:void(0)">Patrimonio</a>
                                    <ul class="sub-menu">
                                        <li><a href="/gestione-sentieri">Gestione sentieri</a></li>
                                        <li><a href="/contribuisci">Contribuisci</a></li>
                                        <li><a href="/segnala-problema">Segnala problema</a></li>
                                    </ul>
                                </li>

                                <li class="submenu">
                                    <a href="javascript:void(0)">Luoghi</a>
                                    <ul class="sub-menu">
                                        <li><a href="/luoghi">I Celti</a></li>
                                        <li><a href="/luoghi">Chiesette di Trava</a></li>
                                        <li><a href="/luoghi">Terrazza panoramica</a></li>
                                        <li><a href="/luoghi">Clap “Centenari”</a></li>
                                    </ul>
                                </li>

                                <li><a href="/eventi">Eventi</a></li>
                                <li><a href="/contatti">Contatti</a></li>
                                <li class="submenu">
                                    <a href="javascript:void(0)"><?= htmlspecialchars($languageCodes[$currentLanguage], ENT_QUOTES, 'UTF-8') ?></a>
                                    <ul class="sub-menu">
                                        <?php foreach ($languageCodes as $language => $label): ?>
                                            <?php if ($language !== $currentLanguage): ?>
                                                <li><a href="<?= htmlspecialchars(content_language_url($language), ENT_QUOTES, 'UTF-8') ?>" hreflang="<?= htmlspecialchars($language, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a></li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div id="menu-responsive-classic">
                        <div class="menu-button">
                            <span class="bar bar-1"></span><span class="bar bar-2"></span><span class="bar bar-3"></span>
                        </div>
                    </div>

                </nav>
            </div>
        </header>
        <!-- END Header & Menu -->

        <div id="page-content" class="header-static ">

            <!-- Slider -->
            <div id="flexslider" class="fullpage-wrap small">
                <ul class="slides">
                    <li style="background-image:url(assets/img/scala.jpg)">
                        <div class="container text text-center">
                            <h1 class="white margin-bottom-small">Scala difficoltà escursionismo</h1>
                            <p class="heading white">Classificazione dei percorsi, ferrate, ciaspole e tracciati accessibili.</p>
                        </div>
                        <div class="gradient dark"></div>
                    </li>
                    <ol class="breadcrumb">
                        <li><a href="/">Home</a></li>
                        <li><a href="/map">Mappa</a></li>
                        <li class="active">Info segnaletica</li>
                    </ol>
                </ul>
            </div>
            <!-- END Slider -->

            <div id="page-wrap" class="content-section fullpage-wrap">

                <!-- Intro + download -->
                <div class="row margin-leftright-null">
                    <div class="container">
                        <div class="col-md-7 padding-leftright-null">
                            <div class="text">
                                <h2 class="margin-bottom-null title line left">Come leggere la difficoltà dei percorsi</h2>
                                <p class="heading left grey margin-bottom-null">Scala escursionistica secondo la classificazione CAI.</p>

                                <div class="padding-onlytop-md">
                                    <p>
                                        Questa pagina integra la scala di valutazione delle difficoltà escursionistiche utilizzata per
                                        interpretare correttamente i percorsi presenti su Lauco Experience.
                                    </p>
                                    <p>
                                        Le sigle aiutano a distinguere percorsi turistici, escursionistici, per escursionisti esperti,
                                        itinerari attrezzati, percorsi in ambiente innevato e percorsi montani accessibili con ausili.
                                    </p>

                                    <ul class="info-list">
                                        <li>Consulta la sigla prima di iniziare il percorso.</li>
                                        <li>Valuta allenamento, equipaggiamento, meteo e condizioni del fondo.</li>
                                        <li>Scarica il PDF originale e tienilo disponibile anche offline.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-5 padding-leftright-null">
                            <div class="text padding-md-top-null">
                                <div class="pdf-box">
                                    <h3 class="margin-bottom-small">Documento PDF</h3>
                                    <p>Scarica la scala completa in formato PDF.</p>
                                    <a href="SCALA_DIFFICOLTA%27_ESCURSIONISMO.pdf" class="btn-alt active margin-null" download>Scarica PDF</a>

                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Segnaletica reale sul territorio -->
                <div class="row margin-leftright-null signage-gallery">
                    <div class="container">
                        <div class="col-md-12 text padding-bottom-null text-center">
                            <h2 class="margin-bottom-null title line center">Info segnaletica</h2>
                            <p class="heading center grey margin-bottom-null">Consulta la sigla prima di iniziare il percorso.</p>
                        </div>

                        <div class="col-md-12 text padding-top-null">
                            <div class="row">
                                <div class="col-md-6 col-sm-6">
                                    <figure class="signage-photo-card">
                                        <img src="assets/img/segnaletica-panoramiche.webp" loading="lazy" width="700" height="933" alt="Palina con indicazioni Panoramica del Cretis e Panoramica del Forcadana e dischi numerati con QR">
                                    </figure>
                                </div>
                                <div class="col-md-6 col-sm-6">
                                    <figure class="signage-photo-card">
                                        <img src="assets/img/segnaletica-segnavia-arancione.webp" loading="lazy" width="700" height="933" alt="Segnavia arancione dipinto su un albero lungo un percorso nel bosco">
                                    </figure>
                                </div>
                            </div>

                            <p class="text-center signage-note">
                                La segnaletica aiuta, ma può essere danneggiata, coperta dalla vegetazione o temporaneamente assente. Non proseguire alla cieca se perdi la traccia: torna all’ultimo punto certo e rivaluta il percorso.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Escursionismo -->
                <div class="row margin-leftright-null grey-background">
                    <div class="container">
                        <div class="col-md-12 text padding-bottom-null text-center">
                            <h2 class="margin-bottom-null title line center">Escursionismo</h2>
                            <p class="heading center grey margin-bottom-null">Sigle principali per i percorsi a piedi.</p>
                        </div>

                        <div class="col-md-12 text">
                            <div class="difficulty-grid">
                                <div class="difficulty-card">
                                    <span class="code">T</span>
                                    <h3>Turistico</h3>
                                    <p>
                                        Itinerari su stradine, mulattiere o comodi sentieri, con percorso evidente e senza problemi
                                        particolari di orientamento. Richiedono conoscenza base dell’ambiente montano e preparazione alla camminata.
                                    </p>
                                </div>

                                <div class="difficulty-card">
                                    <span class="code">E</span>
                                    <h3>Escursionistico</h3>
                                    <p>
                                        Itinerari su sentieri o tracce in terreno vario, generalmente segnalati. Possono includere pendii
                                        ripidi, brevi passaggi su roccia non esposti o tratti attrezzati non impegnativi.
                                    </p>
                                </div>

                                <div class="difficulty-card">
                                    <span class="code">EE</span>
                                    <h3>Escursionisti esperti</h3>
                                    <p>
                                        Percorsi che richiedono esperienza, passo sicuro, assenza di vertigini, conoscenza dell’ambiente
                                        alpino ed equipaggiamento adeguato. Possono presentare terreno impervio, infido o tratti rocciosi.
                                    </p>
                                </div>

                                <div class="difficulty-card">
                                    <span class="code">EEA</span>
                                    <h3>Escursionisti esperti con attrezzatura</h3>
                                    <p>
                                        Percorsi attrezzati o vie ferrate per cui sono necessari dispositivi di autoassicurazione e
                                        protezione personale, come imbragatura, dissipatore, moschettoni, casco e guanti.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ferrate -->
                <div class="row margin-leftright-null">
                    <div class="container">
                        <div class="col-md-12 text padding-bottom-null text-center">
                            <h2 class="margin-bottom-null title line center">Classificazione ferrate</h2>
                            <p class="heading center grey margin-bottom-null">Sottoclassi dei percorsi EEA.</p>
                        </div>

                        <div class="col-md-12 text">
                            <div class="difficulty-grid">
                                <div class="difficulty-card">
                                    <span class="code">F</span>
                                    <h3>Ferrata facile</h3>
                                    <p>
                                        Sentiero attrezzato poco esposto e poco impegnativo, con lunghi tratti di cammino e strutture
                                        metalliche limitate al solo cavo o catena.
                                    </p>
                                </div>

                                <div class="difficulty-card">
                                    <span class="code">PD</span>
                                    <h3>Ferrata poco difficile</h3>
                                    <p>
                                        Ferrata con sviluppo contenuto e poco esposta, articolata con canali, camini e qualche breve tratto
                                        verticale facilitato da catene, cavi, pioli o scale.
                                    </p>
                                </div>

                                <div class="difficulty-card">
                                    <span class="code">D</span>
                                    <h3>Ferrata difficile</h3>
                                    <p>
                                        Ferrata di maggiore sviluppo che richiede buona preparazione fisica e tecnica. Può presentare tratti
                                        verticali, esposizione prolungata e brevi strapiombi.
                                    </p>
                                </div>

                                <div class="difficulty-card">
                                    <span class="code">EAI</span>
                                    <h3>Ambiente innevato - ciaspole</h3>
                                    <p>
                                        Itinerari in ambiente innevato percorribili con racchette da neve, su tracciati evidenti,
                                        riconoscibili e con difficoltà generalmente contenute.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Accessibili -->
                <div class="row margin-leftright-null grey-background">
                    <div class="container">
                        <div class="col-md-12 text padding-bottom-null text-center">
                            <h2 class="margin-bottom-null title line center">Percorsi montani accessibili con ausili</h2>
                            <p class="heading center grey margin-bottom-null">Classificazione AT, AE e AEE.</p>
                        </div>

                        <div class="col-md-12 text">
                            <div class="difficulty-grid">
                                <div class="difficulty-card">
                                    <span class="code">AT</span>
                                    <h3>Accessibili turisti</h3>
                                    <p>
                                        Percorsi su carrarecce, sterrati o tratturi inerbiti, con pendenze modeste, larghezza superiore
                                        a 1,5 metri, dislivello contenuto e lunghezza inferiore a 3 km.
                                    </p>
                                </div>

                                <div class="difficulty-card">
                                    <span class="code">AE</span>
                                    <h3>Accessibili escursionisti</h3>
                                    <p>
                                        Percorsi su sentieri evidenti o mulattiere selciate, con pendenze moderate, larghezza tra uno e
                                        un metro e mezzo, dislivello inferiore a 300 m e lunghezza da 3 a 6 km.
                                    </p>
                                </div>

                                <div class="difficulty-card">
                                    <span class="code">AEE</span>
                                    <h3>Accessibili escursionisti esperti</h3>
                                    <p>
                                        Percorsi su mulattiere e sentieri con fondo sconnesso, ostacoli, larghezze inferiori, dislivelli
                                        maggiori o tratti che richiedono equipaggio ed esperienza adeguati.
                                    </p>
                                </div>

                                <div class="difficulty-card">
                                    <span class="code"><i class="icon ion-alert"></i></span>
                                    <h3>Nota di sicurezza</h3>
                                    <p>
                                        La classificazione non sostituisce la valutazione sul posto. Prima della partenza controlla meteo,
                                        stato del percorso, equipaggiamento, allenamento e autonomia del gruppo.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>



                <!-- Contatti -->
                <div class="row margin-leftright-null contact-strip">
                    <div class="container">
                        <div class="col-md-12 text text-center">
                            <h3 class="big margin-bottom-small">Hai dubbi su segnaletica o difficoltà di un percorso?</h3>
                            <p>
                                Contatta Lauco Experience: 
                                <a href="mailto:info@laucoexperience.it">info@laucoexperience.it</a> · 
                              
                            </p>
                            <a href="/contatti" class="btn-alt small white margin-null active shadow">Vai ai contatti</a>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <!-- Footer -->
        <footer class="full-width">
            <div class="container">
                <div class="row no-margin">

                    <div class="col-sm-4 col-md-2 padding-leftright-null">
                        <h6 class="heading white margin-bottom-extrasmall">Lauco&nbsp;Experience</h6>
                        <ul class="sitemap">
                            <li><a href="/">Home</a></li>
                            <li><a href="/map">Mappa</a></li>
                            <li><a href="/itinerari-piedi">Itinerari</a></li>
                            <li><a href="/#services">Servizi</a></li>
                            <li><a href="/gestione-sentieri">Patrimonio</a></li>
                            <li><a href="/contatti">Contatti</a></li>
                        </ul>
                    </div>

                    <div class="col-sm-4 col-md-2 padding-leftright-null">
                        <h6 class="heading white margin-bottom-extrasmall">Link utili</h6>
                        <ul class="useful-links">
                            <li><a href="/segnaletica">Segnaletica</a></li>
                            <li><a href="SCALA_DIFFICOLTA%27_ESCURSIONISMO.pdf" download>Scarica scala difficoltà</a></li>
                            <li><a href="/privacy">Privacy Policy</a></li>
                            <li><a href="/cookie">Cookie Policy</a></li>
                        </ul>
                    </div>

                    <div class="col-sm-4 col-md-4 padding-leftright-null">
                        <h6 class="heading white margin-bottom-extrasmall">Contatti</h6>
                        <ul class="info">
                            <li>Email: <a href="mailto:info@laucoexperience.it">info@laucoexperience.it</a></li>
                            <li><a href="https://goo.gl/maps/xyz" target="_blank">Via&nbsp;Capoluogo&nbsp;104, Lauco&nbsp;(UD)</a></li>
                        </ul>
                    </div>

                    <div class="col-md-4 padding-leftright-null">
                        <h6 class="heading white margin-bottom-extrasmall">Rimani aggiornato</h6>
                        <p class="grey-light">Iscriviti e ricevi novità su percorsi, eventi e manutenzione sentieri.</p>
                        <div id="newsletter-form" class="padding-onlytop-xs">
                            <form class="search-form" method="post" action="https://www.aweber.com/scripts/addlead.pl">
                                <input type="hidden" name="listname" value="[LIST_ID]">
                                <input type="hidden" name="redirect" value="/">
                                <input type="hidden" name="meta_required" value="email">
                                <div class="form-input">
                                    <input type="email" name="email" placeholder="La tua email" required>
                                    <span class="form-button"><input type="submit" value="Iscriviti"></span>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>

                <div class="copy">
                    <div class="row no-margin">
                        <div class="col-md-8 padding-leftright-null">
                            &copy; 2026 Lauco&nbsp;Experience – Progetto di valorizzazione del territorio di Lauco (UD)
                        </div>
                        <div class="col-md-4 padding-leftright-null">
                            <ul class="social">
                                <li><a href="https://facebook.com/laucoexperience"><i class="fa fa-facebook"></i></a></li>
                                <li><a href="https://instagram.com/laucoexperience"><i class="fa fa-instagram"></i></a></li>
                                <li><a href="https://youtube.com/@laucoexperience"><i class="fa fa-youtube-play"></i></a></li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>
        </footer>

    </div><!-- /#main-wrap -->

    <!-- Scripts -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap/bootstrap.min.js"></script>
    <script src="assets/js/jquery.flexslider-min.js"></script>
    <script src="assets/js/jquery.fullPage.min.js"></script>
    <script src="assets/js/owl.carousel.min.js"></script>
    <script src="assets/js/isotope.min.js"></script>
    <script src="assets/js/jquery.magnific-popup.min.js"></script>
    <script src="assets/js/jquery.scrollTo.min.js"></script>
    <script src="assets/js/smooth.scroll.min.js"></script>
    <script src="assets/js/jquery.appear.js"></script>
    <script src="assets/js/jquery.countTo.js"></script>
    <script src="assets/js/jquery.scrolly.js"></script>
    <script src="assets/js/plugins-scroll.js"></script>
    <script src="assets/js/imagesloaded.min.js"></script>
    <script src="assets/js/pace.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
