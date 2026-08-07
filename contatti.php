<?php
require_once __DIR__ . '/inc/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['contact_token'])) {
    $_SESSION['contact_token'] = bin2hex(random_bytes(32));
}

$contactToken = $_SESSION['contact_token'];
$contactEmail = 'info@laucoexperience.it';

if (!function_exists('h_contact_debug')) {
    function h_contact_debug($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'inc/header.php'; ?>

    <style>
        #contact-form .contact-privacy {
            display: block;
            margin-top: 8px;
            margin-bottom: 0;
            color: #777;
            font-size: 13px;
            line-height: 1.45;
            font-weight: normal;
        }

        #contact-form .contact-privacy input {
            width: auto;
            margin-right: 8px;
            vertical-align: middle;
        }


        /*
         * Fallback grafico del form contatti.
         * Mantiene l'aspetto del template anche se parte dello stile era legato agli ID originali.
         */
        #contact-form {
            width: 100%;
        }

        #contact-form .form-field {
            width: 100%;
            display: block;
            box-sizing: border-box;
        }

        #contact-form textarea.form-field {
            resize: vertical;
        }

        #contact-form .submit-area {
            clear: both;
        }

        #submit-contact.btn-alt {
            cursor: pointer;
        }

        .website-field {
            position: absolute;
            left: -9999px;
            opacity: 0;
            height: 1px;
            overflow: hidden;
        }

        #msg.message {
            margin-top: 14px;
            font-size: 14px;
            line-height: 1.45;
        }

        #msg.success {
            color: #245b34;
        }

        #msg.error {
            color: #8a1f1f;
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
                    <li style="background-image:url(assets/img/contact.jpg)">
                        <div class="container text text-center">
                            <h1 class="white margin-bottom-small">Contatti</h1>
                            <p class="heading white"></p>
                        </div>
                        <div class="gradient dark"></div>
                    </li>

                    <ol class="breadcrumb">
                        <li><a href="index.php">Home</a></li>
                        <li class="active">Contatti</li>
                    </ol>
                </ul>
            </div>

            <div id="page-wrap" class="content-section fullpage-wrap">
                <div class="row margin-leftright-null">
                    <div class="container">

                        <div class="col-md-6 padding-leftright-null">
                            <div class="text">
                                <h2 class="margin-bottom-null title line left">Scrivici</h2>
                                <p class="heading center grey margin-bottom-null"></p>

                                <div class="padding-onlytop-md">
                                    <p class="margin-bottom">Lauco Experience nasce per valorizzare i percorsi e i sentieri del nostro territorio. Grazie a mappe interattive, foto, video e tracce scaricabili, ti offriamo un’esperienza immersiva alla scoperta della natura di Lauco.</p>

                                    <p>
                                        <span class="contact-info">Indirizzo <em>Via capoluogo 104 Lauco</em></span><br>
                                        
                                        <span class="contact-info">Email <a href="mailto:<?= h_contact_debug($contactEmail) ?>"><em> <?= h_contact_debug($contactEmail) ?></em></a></span>
                                    </p>

                                    <p class="margin-md-bottom-null">
                                        <span class="contact-info">Lun Ven <em>9.00  12.00 </em></span><br>
                                        <span class="contact-info"> <em></em></span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 padding-leftright-null">
                            <div class="text padding-onlybottom-sm padding-md-top-null">

                                <form id="contact-form" method="post" action="/send" class="padding-onlytop-md padding-md-topbottom-null">
                                    <input type="hidden" name="_csrf_token" value="<?= h_contact_debug($contactToken) ?>">

                                    <div class="website-field">
                                        <label for="website">Website</label>
                                        <input name="website" id="website" type="text" tabindex="-1" autocomplete="off">
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <input class="form-field" name="name" id="name" type="text" placeholder="Nome">
                                        </div>

                                        <div class="col-md-12">
                                            <input class="form-field" name="mail" id="mail" type="email" placeholder="Email">
                                        </div>

                                        <div class="col-md-12">
                                            <input class="form-field" name="subjectForm" id="subjectForm" type="text" placeholder="Oggetto">
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <textarea class="form-field" name="messageForm" id="messageForm" rows="6" placeholder="Messaggio"></textarea>

                                            <label class="contact-privacy">
                                                <input type="checkbox" name="privacy" value="1">
                                                Confermo che i dati inseriti possono essere utilizzati esclusivamente per rispondere alla mia richiesta.
                                            </label>

                                            <div class="submit-area padding-onlytop-sm">
                                                <input type="button" id="submit-contact" class="btn-alt" value="Invia">
                                                <div id="msg" class="message"></div>
                                            </div>
                                        </div>
                                    </div>
                                </form>

                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <?php include 'inc/footer.php'; ?>
    </div>

    <?php include 'inc/scripts.php'; ?>

    <script>
        (function ($) {
            "use strict";

            function showContactMessage(type, html) {
                $('#msg')
                    .hide()
                    .removeClass('success error')
                    .addClass(type)
                    .html(html)
                    .fadeIn('slow');
            }

            function sendContactForm(e) {
                if (e) {
                    e.preventDefault();
                    e.stopImmediatePropagation();
                }

                var form = $('#contact-form');
                var button = $('#submit-contact');

                if (!form.length) {
                    return false;
                }

                button.prop('disabled', true);

                $.ajax({
                    type: 'POST',
                    method: 'POST',
                    url: '/send',
                    dataType: 'json',
                    cache: false,
                    data: form.serialize(),
                    success: function (data) {
                        if (data && data.info === 'success') {
                            form.find('input[type=text], input[type=email], textarea, select').filter(':visible').val('');
                            form.find('input[type=checkbox]').prop('checked', false);
                            showContactMessage('success', data.msg || 'Messaggio inviato correttamente.');
                        } else {
                            showContactMessage('error', data && data.msg ? data.msg : 'Errore durante l’invio. Riprova tra poco.');
                        }
                    },
                    error: function () {
                        showContactMessage('error', 'Errore durante l’invio. Riprova tra poco.');
                    },
                    complete: function () {
                        button.prop('disabled', false);
                    }
                });

                return false;
            }

            $(function () {
                /*
                 * Manteniamo gli ID originali del template per la grafica.
                 * Rimuoviamo il vecchio handler di main.js e forziamo AJAX su /send.
                 */
                $('#submit-contact').off('click').on('click', sendContactForm);
                $('#contact-form').off('submit').on('submit', sendContactForm);
            });
        })(jQuery);
    </script>
</body>
</html>
