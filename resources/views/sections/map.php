<?php
/** Sezione pagina mappa */

$gpxDirFs  = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/gpx';
$gpxDirWeb = '/gpx';
$routes    = [];

if (is_dir($gpxDirFs)) {
    $files = glob($gpxDirFs . '/*.gpx') ?: [];
    natsort($files);

    foreach ($files as $filePath) {
        $filename = basename($filePath);

        // LAUCO_#_8-V.gpx -> 8-V
        if (preg_match('/#_([^\.]+)\.gpx$/i', $filename, $matches)) {
            $title = trim($matches[1]);
        } else {
            $title = pathinfo($filename, PATHINFO_FILENAME);
        }

        $routes[] = [
            'id'       => 'route_' . md5($filename),
            'title'    => $title,
            'filename' => $filename,
            'url'      => $gpxDirWeb . '/' . rawurlencode($filename),
            'updated'  => date('d/m/Y', filemtime($filePath)),
        ];
    }
}

$firstRouteUrl = !empty($routes) ? $routes[0]['url'] : '#';
?>

<script>
window.GPX_ROUTES = <?= json_encode($routes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<div class='sottom'></div>

<div class="container">
    <div class="col-md-12 text padding-bottom-null text-center">
        <h2 class="margin-bottom-null title line center">Mappa interattiva</h2>

        <div class="panel" id="statusPanel">
            <h2>La tua posizione</h2>

            <div id="permissionDenied" style="display:none;">
                <p>
                    Non hai autorizzato l'app ad accedere alla tua posizione quindi non possiamo indicarti il sentiero su cui ti trovi.
                </p>
                <div class="row">
                    <button class="btn " id="btnEnableGps">Abilita GPS</button>
                </div>
            </div>

            <div id="permissionOk" style="display:none;">
                <p class="muted" id="gpsInfo">Rilevamento posizione in corso…</p>
                <p id="trailInfo"><strong>Sentiero:</strong> <span class="muted">non ancora determinato</span></p>
                <p id="trailDetails" class="muted"></p>

                <div class="row" style="margin-top:10px;">
                    <button class="btn btn-primary" id="btnInteractive" disabled>
                        Visualizza il percorso più vicino
                    </button>
                    <button class="btn btn-secondary" id="btnFallback">Vai all'elenco percorsi</button>
                </div>
            </div>
        </div>

        <div class="col-sm-4 col-md-2">
            <i class="pd-icon-hour service big margin-bottom-null"></i>
            <em>Data aggiornamento</em>
            <h3 class="color" id="statUpdated">-</h3>
        </div>
        <div class="col-sm-4 col-md-2">
            <i class="pd-icon-camp-bag service big margin-bottom-null"></i>
            <em>Difficoltà</em>
            <h3 class="color" id="statDifficulty">-</h3>
        </div>
        <div class="col-sm-4 col-md-2">
            <i class="pd-icon-male service big margin-bottom-null"></i>
            <em>Calorie</em>
            <h3 class="color" id="statCalories">-</h3>
        </div>
        <div class="col-sm-4 col-md-2">
            <i class="pd-icon-watch service big margin-bottom-null"></i>
            <em>Tempo percorrenza</em>
            <h3 class="color" id="statDuration">-</h3>
        </div>
        <div class="col-sm-4 col-md-2">
            <i class="pd-icon-distance service big margin-bottom-null"></i>
            <em>Lunghezza</em>
            <h3 class="color" id="statLength">-</h3>
        </div>
        <div class="col-sm-4 col-md-2">
            <i class="ion-ios-analytics-outline service big margin-bottom-null"></i>
            <em>Dislivello</em>
            <h3 class="color" id="statAscent">-</h3>
        </div>
    </div>
<br><br>
</div>

<main>
    <div>
        <div id="map"></div>

        <div class="panel">
            <div id="elevation"></div>
        </div>
    </div>
</main>

<!-- MODAL PERMESSI -->
<div id="permModal" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="permTitle">
        <h3 id="permTitle">Abilita la posizione</h3>
        <p class="muted" style="margin:0;">
            Clicca su <strong>Autorizzazioni</strong> (o <strong>Impostazioni sito</strong>) del tuo browser e permetti
            l’utilizzo della posizione per questo sito.
            Poi premi <strong>Riprova</strong>.
        </p>

        <div class="box">
            <strong>Android (Chrome)</strong>
            <div class="muted" style="margin-top:6px;">
                Lucchetto nella barra indirizzi → Autorizzazioni → Posizione → Consenti.<br>
                Oppure: ⋮ → Impostazioni → Impostazioni sito → Posizione.
            </div>
        </div>

        <div class="box">
            <strong>iPhone (Safari)</strong>
            <div class="muted" style="margin-top:6px;">
                Impostazioni → Safari → Posizione → Durante l’uso (o simile).<br>
                Oppure: Impostazioni → Privacy e Sicurezza → Servizi di localizzazione → Safari.
            </div>
        </div>

        <div class="row" style="justify-content:flex-end;">
            <button class="btn btn-secondary" id="btnClosePermModal">Chiudi</button>
            <button class="btn " id="btnRetryGps">Riprova</button>
        </div>
    </div>
</div>

<section style="margin-top:45px"
    class="elementor-section elementor-top-section elementor-element elementor-element-2e8523d elementor-section-full_width elementor-section-height-default elementor-section-height-default"
    data-id="2e8523d" data-element_type="section">
    <div class="elementor-container elementor-column-gap-no">
        <div class="elementor-column elementor-col-100 elementor-top-column elementor-element elementor-element-d396140"
            data-id="d396140" data-element_type="column">
            <div class="elementor-widget-wrap elementor-element-populated">
                <div class="elementor-element elementor-element-45ca457 elementor-align-left elementor-widget elementor-widget-bdevs-blockquote"
                    data-id="45ca457" data-element_type="widget" data-widget_type="bdevs-blockquote.default">
                    <div class="elementor-widget-container">
                        <div id="home-wrap" class="content-section fullpage-wrap">
                            <div class="row margin-leftright-null grey-background">
                                <div class="bg-img overlay simple-parallax responsive"
                                    style="width:100%;background-image:url(https://shtheme.com/demosd/dolomia/wp-content/uploads/2022/11/testimonials.jpg)">
                                    <div class="container">
                                        <section class="testimonials-carousel-simple col-md-12 text padding-bottom-null">
                                            <div class="item padding-leftright-null">
                                                <div class="padding-top-null padding-bottom-null">
                                                    <blockquote class="margin-bottom-small white">
                                                        Lauco si scopre un passo alla volta: tra boschi, silenzi e panorami che restano nel cuore.
                                                        <em class="small grey-light">Lauco Experience</em>
                                                    </blockquote>
                                                </div>
                                            </div>
                                        </section>
                                    </div>
                                </div>
                            </div>
                            <!-- END Section Image Background with overlay -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section
    class="elementor-section elementor-top-section elementor-element elementor-element-8e51bdc elementor-section-full_width elementor-section-height-default elementor-section-height-default"
    data-id="8e51bdc" data-element_type="section">
    <div class="elementor-container elementor-column-gap-no">
        <div class="elementor-column elementor-col-100 elementor-top-column elementor-element elementor-element-7f275d6"
            data-id="7f275d6" data-element_type="column">
            <div class="elementor-widget-wrap elementor-element-populated">
                <div class="elementor-element elementor-element-67ff8b1 elementor-align-left elementor-widget elementor-widget-bdevs-trip"
                    data-id="67ff8b1" data-element_type="widget" data-widget_type="bdevs-trip.default">
                    <div class="elementor-widget-container">
                        <div id="showcase-treks" class="row margin-leftright-null grey-background">
                            <div class="container">
                                <div class="col-md-12 text padding-bottom-null text-center">
                                    <h2 class="margin-bottom-null title line center">ELENCO SENTIERI</h2>
                                </div>

                                <div class="col-md-12 text" id="treks">
                                    <?php if (!empty($routes)): ?>
                                        <?php foreach ($routes as $route): ?>
                                            
									<div class="item col-md-6">
    <div
        class="js-route-item"
        data-route-id="<?= htmlspecialchars($route['id'], ENT_QUOTES, 'UTF-8') ?>"
        role="button"
        tabindex="0"
        style="display:block;width:100%;text-align:left;background:#fff;border:1px solid #e5e5e5;border-radius:8px;padding:18px 20px;margin-bottom:20px;cursor:pointer;"
    >
        <div style="font-size:22px;font-weight:700;line-height:1.2;">
            <?= htmlspecialchars($route['title'], ENT_QUOTES, 'UTF-8') ?>
        </div>

        <div class="muted" style="margin-top:10px;font-size:14px;">
            <div class="js-route-length" data-route-id="<?= htmlspecialchars($route['id'], ENT_QUOTES, 'UTF-8') ?>">
                Lunghezza: -
            </div>
            <div class="js-route-ascent" data-route-id="<?= htmlspecialchars($route['id'], ENT_QUOTES, 'UTF-8') ?>">
                Dislivello: -
            </div>
        </div>

        <div style="margin-top:14px;display:flex;gap:10px;flex-wrap:wrap;">
            <a
                href="<?= htmlspecialchars($route['url'], ENT_QUOTES, 'UTF-8') ?>"
                download
                class="btn "
                style="display:inline-block;"
                onclick="event.stopPropagation();"
            >
                Scarica GPX
            </a>
        </div>
    </div>
</div>
									
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="col-md-12 text-center" style="padding:30px 15px;">
                                            <p>Nessun file GPX trovato nella cartella <strong>/gpx/</strong>.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <!-- END Showcase Trip -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section
    class="elementor-section elementor-top-section elementor-element elementor-element-b8f8309 elementor-section-full_width elementor-section-height-default elementor-section-height-default"
    data-id="b8f8309" data-element_type="section">
    <div class="elementor-container elementor-column-gap-no">
        <div class="elementor-column elementor-col-100 elementor-top-column elementor-element elementor-element-5ea17b0"
            data-id="5ea17b0" data-element_type="column">
            <div class="elementor-widget-wrap elementor-element-populated">
                <div class="elementor-element elementor-element-81584da elementor-align-left elementor-widget elementor-widget-bdevs-cta"
                    data-id="81584da" data-element_type="widget" data-widget_type="bdevs-cta.default">
                    <div class="elementor-widget-container">
                        <div class="row text margin-leftright-null color-background">
                            <div class="col-md-12 text-center">
                                <h4 class="big white">Informazioni</h4>
                                <h4 class="big margin-bottom-small white">
                                     <a href="/contatti" class="btn-pro simple white">Scrivici</a>
                                </h4>
                            </div>
                        </div>
                        <!-- END Call to Action -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>