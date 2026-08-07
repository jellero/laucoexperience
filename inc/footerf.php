<?php
/** Footer globale – Lauco Experience */
require_once __DIR__ . '/translations.php';
?>
<footer class="fixed full-width">
    <div class="container">
        <div class="row no-margin">

            <!-- Sitemap principale -->
            <div class="col-sm-4 col-md-2 padding-leftright-null">
                <h6 class="heading white margin-bottom-extrasmall">Lauco&nbsp;Experience</h6>
                <ul class="sitemap">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="mappa.php">Mappa</a></li>
                    <li><a href="itinerari-piedi.php">Itinerari</a></li>
                    <li><a href="servizi.php">Servizi</a></li>
                    <li><a href="gestione-sentieri.php">Patrimonio</a></li>
                    <li><a href="contatti.php">Contatti</a></li>
                </ul>
            </div>

            <!-- Link utili -->
            <div class="col-sm-4 col-md-2 padding-leftright-null">
                <h6 class="heading white margin-bottom-extrasmall">Link utili</h6>
                <ul class="useful-links">
                    <li><a href="segnaletica.php">Segnaletica</a></li>
                    <li><a href="meteo.php">Meteo regionale</a></li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="cookie.php">Cookie Policy</a></li>
                </ul>
            </div>

            <!-- Contatti -->
            <div class="col-sm-4 col-md-4 padding-leftright-null">
                <h6 class="heading white margin-bottom-extrasmall">Contatti</h6>
                <ul class="info">
                    <li>Telefono: <a href="tel:+">+39&nbsp;</a></li>
                    <li>Email: <a href="mailto:info@laucoexperience.it">info@laucoexperience.it</a></li>
                    <li><a href="https://goo.gl/maps/xyz" target="_blank">Via&nbsp;Capoluogo&nbsp;104, Lauco&nbsp;(UD)</a></li>
                </ul>
            </div>

            <!-- Newsletter -->
            <div class="col-md-4 padding-leftright-null">
                <h6 class="heading white margin-bottom-extrasmall">Rimani aggiornato</h6>
                <p class="grey-light">Iscriviti e ricevi novità su percorsi, eventi e manutenzione sentieri.</p>
                <div id="newsletter-form" class="padding-onlytop-xs">
                    <form class="search-form" method="post" action="https://www.aweber.com/scripts/addlead.pl">
                        <input type="hidden" name="listname"  value="[LIST_ID]">
                        <input type="hidden" name="redirect"  value="https://www.laucoexperience.it/thanks.php">
                        <input type="hidden" name="meta_required" value="email">
                        <div class="form-input">
                            <input type="email" name="email" placeholder="La tua email" required>
                            <span class="form-button"><input type="submit" value="Iscriviti"></span>
                        </div>
                    </form>
                </div>
            </div>

        </div>

        <!-- Copyright & Social -->
        <div class="copy">
            <div class="row no-margin">
                <div class="col-md-8 padding-leftright-null">
                    &copy; <?php echo date('Y'); ?> <?= htmlspecialchars(site_text('footer.project', null, 'Lauco Experience – Progetto di valorizzazione del territorio di Lauco (UD)'), ENT_QUOTES, 'UTF-8') ?>
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
