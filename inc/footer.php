<?php
/** Footer globale – Lauco Experience */
require_once __DIR__ . '/translations.php';
$newsletterUi = [
    'sending' => site_text('newsletter.sending', null, 'Invio…'),
    'subscribe' => site_text('legacy.footer.cadce36900', null, 'Iscriviti'),
    'invalidLog' => site_text('runtime.footer.ff62d8ef7f', null, 'Risposta newsletter non valida:'),
    'invalid' => site_text('runtime.footer.2203b64033', null, 'Il server ha restituito una risposta non valida.'),
    'success' => site_text('runtime.footer.a2356c1d53', null, 'Grazie! La richiesta è stata inviata correttamente.'),
    'failed' => site_text('runtime.footer.1dc35f0cb6', null, 'Non è stato possibile inviare la richiesta.'),
    'network' => site_text('runtime.footer.39498f5c04', null, 'Errore di comunicazione con il server. Riprova.'),
    'timeout' => site_text('runtime.footer.025e343a94', null, 'Il server non ha risposto in tempo. Riprova.'),
];
?>
<footer class="full-width">
    <div class="container">
        <div class="row no-margin">

            <!-- Sitemap principale -->
            <div class="col-sm-4 col-md-2 padding-leftright-null">
                <h6 class="heading white margin-bottom-extrasmall">Lauco&nbsp;Experience</h6>

                <ul class="sitemap">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="map">Mappa</a></li>
                    <li><a href="itinerari-piedi.php">Itinerari</a></li>
                    <li><a href="gestione-sentieri.php">Patrimonio</a></li>
                    <li><a href="contatti.php">Contatti</a></li>
                </ul>
            </div>

            <!-- Link utili -->
            <div class="col-sm-4 col-md-2 padding-leftright-null">
                <h6 class="heading white margin-bottom-extrasmall">Link utili</h6>

                <ul class="useful-links">
                    <li><a href="segnaletica.php">Segnaletica</a></li>
                    <li>
                        <a href="SCALA_DIFFICOLTA'_ESCURSIONISMO.pdf">
                            Scarica segnaletica
                        </a>
                    </li>
                    <li>
                        <a href="https://www.osmer.fvg.it/" target="_blank" rel="noopener noreferrer">
                            Meteo regionale
                        </a>
                    </li>
                    <li><a href="privacy.php">Privacy Policy</a></li>
                    <li><a href="cookie.php">Cookie Policy</a></li>
                </ul>
            </div>

            <!-- Contatti -->
            <div class="col-sm-4 col-md-4 padding-leftright-null">
                <h6 class="heading white margin-bottom-extrasmall">Contatti</h6>

                <ul class="info">
                    <li>
                        Email:
                        <a href="mailto:info@laucoexperience.it">
                            info@laucoexperience.it
                        </a>
                    </li>
                    <li>
                        <a
                            href="https://goo.gl/maps/xyz"
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            Via&nbsp;Capoluogo&nbsp;104, Lauco&nbsp;(UD)
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Newsletter -->
            <div class="col-md-4 padding-leftright-null">
                <h6 class="heading white margin-bottom-extrasmall">Rimani aggiornato</h6>

                <p class="grey-light">
                    Iscriviti e ricevi novità su percorsi, eventi e manutenzione sentieri.
                </p>

                <div id="newsletter-form" class="padding-onlytop-xs">
                    <form
                        id="newsletter-ajax-form"
                        class="search-form"
                        method="post"
                        action="<?= htmlspecialchars(content_language_url(content_language_from_request(), '/newsletter'), ENT_QUOTES, 'UTF-8') ?>"
                    >
                        <div class="form-input">
                            <input
                                type="email"
                                name="email"
                                placeholder="La tua email"
                                autocomplete="email"
                                maxlength="254"
                                required
                            >

                            <span class="form-button">
                                <input
                                    id="newsletter-submit"
                                    type="submit"
                                    value="Iscriviti"
                                >
                            </span>
                        </div>

                        <!-- Campo antispam invisibile -->
                        <input
                            class="newsletter-honeypot"
                            type="text"
                            name="company_website"
                            value=""
                            tabindex="-1"
                            autocomplete="off"
                            aria-hidden="true"
                        >

                        <!-- Messaggio AJAX -->
                        <div
                            id="newsletter-message"
                            class="newsletter-message"
                            role="status"
                            aria-live="polite"
                        ></div>
                    </form>
                </div>
            </div>

        </div>

        <!-- Copyright & Social -->
        <div class="copy">
            <div class="row no-margin">
                <div class="col-md-8 padding-leftright-null">
                    &copy; <?php echo date('Y'); ?>
                    <?= htmlspecialchars(site_text('footer.project', null, 'Lauco Experience – Progetto di valorizzazione del territorio di Lauco (UD)'), ENT_QUOTES, 'UTF-8') ?>
                </div>

                <div class="col-md-4 padding-leftright-null">
                    <ul class="social">
                        <li>
                            <a
                                href="https://facebook.com/laucoexperience"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Facebook"
                            >
                                <i class="fa fa-facebook" aria-hidden="true"></i>
                            </a>
                        </li>

                        <li>
                            <a
                                href="https://instagram.com/laucoexperience"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Instagram"
                            >
                                <i class="fa fa-instagram" aria-hidden="true"></i>
                            </a>
                        </li>

                        <li>
                            <a
                                href="https://youtube.com/@laucoexperience"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="YouTube"
                            >
                                <i class="fa fa-youtube-play" aria-hidden="true"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>
</footer>

<style>
    /*
     * Campo honeypot antispam.
     * Non occupa spazio e non modifica il layout.
     */
    .newsletter-honeypot {
        position: absolute !important;
        left: -9999px !important;
        top: auto !important;
        width: 1px !important;
        height: 1px !important;
        overflow: hidden !important;
        opacity: 0 !important;
        pointer-events: none !important;
    }

    /*
     * Messaggio nascosto prima dell'invio.
     */
    .newsletter-message {
        display: none;
        margin-top: 12px;
        padding: 10px 12px;
        font-size: 13px;
        line-height: 1.5;
    }

    .newsletter-message.is-success {
        display: block;
        color: #dff7df;
        background: rgba(35, 135, 65, 0.25);
        border: 1px solid rgba(155, 225, 170, 0.45);
    }

    .newsletter-message.is-error {
        display: block;
        color: #ffe1e1;
        background: rgba(160, 35, 35, 0.25);
        border: 1px solid rgba(255, 155, 155, 0.45);
    }

    #newsletter-submit:disabled {
        cursor: wait;
        opacity: 0.65;
    }
</style>

<script>
(function () {
    'use strict';

    var newsletterMessages = <?= json_encode($newsletterUi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

    function initNewsletterForm() {
        var form = document.getElementById('newsletter-ajax-form');
        var messageBox = document.getElementById('newsletter-message');
        var submitButton = document.getElementById('newsletter-submit');

        if (!form || !messageBox || !submitButton) {
            return;
        }

        /*
         * Evita una doppia inizializzazione se il footer
         * viene caricato dinamicamente o incluso più volte.
         */
        if (form.getAttribute('data-ajax-ready') === 'true') {
            return;
        }

        form.setAttribute('data-ajax-ready', 'true');

        function showMessage(type, message) {
            messageBox.className = 'newsletter-message ' + type;
            messageBox.textContent = message;
        }

        function resetMessage() {
            messageBox.className = 'newsletter-message';
            messageBox.textContent = '';
        }

        function setLoading(loading) {
            submitButton.disabled = loading;
            submitButton.value = loading ? newsletterMessages.sending : newsletterMessages.subscribe;
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            event.stopPropagation();

            resetMessage();

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            setLoading(true);

            var xhr = new XMLHttpRequest();
            var endpoint = form.getAttribute('action');

            xhr.open('POST', form.getAttribute('action'), true);

            xhr.setRequestHeader(
                'X-Requested-With',
                'XMLHttpRequest'
            );

            xhr.setRequestHeader(
                'Accept',
                'application/json'
            );

            xhr.timeout = 15000;

            xhr.onload = function () {
                var result;

                try {
                    result = JSON.parse(xhr.responseText);
                } catch (error) {
                    console.error(
                        newsletterMessages.invalidLog,
                        xhr.responseText
                    );

                    showMessage(
                        'is-error',
                        newsletterMessages.invalid
                    );

                    setLoading(false);
                    return;
                }

                if (
                    xhr.status >= 200 &&
                    xhr.status < 300 &&
                    result.success === true
                ) {
                    showMessage(
                        'is-success',
                        result.message ||
                        newsletterMessages.success
                    );

                    form.reset();
                } else {
                    showMessage(
                        'is-error',
                        result.message ||
                        newsletterMessages.failed
                    );
                }

                setLoading(false);
            };

            xhr.onerror = function () {
                showMessage(
                    'is-error',
                    newsletterMessages.network
                );

                setLoading(false);
            };

            xhr.ontimeout = function () {
                showMessage(
                    'is-error',
                    newsletterMessages.timeout
                );

                setLoading(false);
            };

            xhr.send(new FormData(form));
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener(
            'DOMContentLoaded',
            initNewsletterForm
        );
    } else {
        initNewsletterForm();
    }
})();
</script>
