<?php
require_once LAUCO_ROOT . '/inc/translations.php';
$consentLocale = content_language_from_request();
$consentCopies = [
    'it' => [
        'title' => 'Privacy e statistiche',
        'text' => 'Usiamo gli strumenti tecnici necessari al sito. Google Analytics viene attivato solo se accetti la misurazione statistica.',
        'accept' => 'Accetta analytics',
        'reject' => 'Solo necessari',
        'policy' => 'Cookie policy',
        'privacy' => 'Privacy policy',
        'manage' => 'Gestisci cookie',
        'close' => 'Chiudi preferenze cookie',
    ],
    'en' => [
        'title' => 'Privacy and statistics',
        'text' => 'We use the technical tools required by the website. Google Analytics is activated only if you accept statistical measurement.',
        'accept' => 'Accept analytics',
        'reject' => 'Necessary only',
        'policy' => 'Cookie policy',
        'privacy' => 'Privacy policy',
        'manage' => 'Manage cookies',
        'close' => 'Close cookie preferences',
    ],
    'de' => [
        'title' => 'Datenschutz und Statistik',
        'text' => 'Wir verwenden die für die Website erforderlichen technischen Funktionen. Google Analytics wird nur aktiviert, wenn Sie der statistischen Messung zustimmen.',
        'accept' => 'Analytics akzeptieren',
        'reject' => 'Nur notwendige',
        'policy' => 'Cookie-Richtlinie',
        'privacy' => 'Datenschutz',
        'manage' => 'Cookies verwalten',
        'close' => 'Cookie-Einstellungen schließen',
    ],
    'sl' => [
        'title' => 'Zasebnost in statistika',
        'text' => 'Uporabljamo tehnična orodja, potrebna za delovanje spletnega mesta. Google Analytics se aktivira samo, če dovolite statistično merjenje.',
        'accept' => 'Dovoli analitiko',
        'reject' => 'Samo nujni',
        'policy' => 'Pravilnik o piškotkih',
        'privacy' => 'Pravilnik o zasebnosti',
        'manage' => 'Upravljanje piškotkov',
        'close' => 'Zapri nastavitve piškotkov',
    ],
];
$consentCopy = $consentCopies[$consentLocale] ?? $consentCopies['it'];
?>
<div id="lauco-consent" class="lauco-consent" hidden role="dialog" aria-modal="true" aria-labelledby="lauco-consent-title">
    <div class="lauco-consent__inner">
        <button type="button" class="lauco-consent__close" data-consent="reject" aria-label="<?= htmlspecialchars($consentCopy['close'], ENT_QUOTES, 'UTF-8') ?>">&times;</button>
        <div class="lauco-consent__copy">
            <strong id="lauco-consent-title"><?= htmlspecialchars($consentCopy['title'], ENT_QUOTES, 'UTF-8') ?></strong>
            <p><?= htmlspecialchars($consentCopy['text'], ENT_QUOTES, 'UTF-8') ?> <a href="<?= htmlspecialchars(content_language_url($consentLocale, '/privacy'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($consentCopy['privacy'], ENT_QUOTES, 'UTF-8') ?></a> · <a href="<?= htmlspecialchars(content_language_url($consentLocale, '/cookie'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($consentCopy['policy'], ENT_QUOTES, 'UTF-8') ?></a>.</p>
        </div>
        <div class="lauco-consent__actions">
            <button type="button" class="lauco-consent__button lauco-consent__button--secondary" data-consent="reject"><?= htmlspecialchars($consentCopy['reject'], ENT_QUOTES, 'UTF-8') ?></button>
            <button type="button" class="lauco-consent__button" data-consent="accept"><?= htmlspecialchars($consentCopy['accept'], ENT_QUOTES, 'UTF-8') ?></button>
        </div>
    </div>
</div>
<button id="lauco-consent-manage" class="lauco-consent-manage" type="button" hidden><?= htmlspecialchars($consentCopy['manage'], ENT_QUOTES, 'UTF-8') ?></button>

<style>
.lauco-consent{position:fixed;z-index:10050;left:20px;right:20px;bottom:20px;max-width:980px;margin:0 auto;background:#fff;color:#222;border:1px solid rgba(0,0,0,.12);box-shadow:0 18px 55px rgba(0,0,0,.22)}
.lauco-consent__inner{position:relative;display:flex;align-items:center;gap:28px;padding:22px 48px 22px 24px}
.lauco-consent__close{position:absolute;right:12px;top:8px;border:0;background:transparent;color:#333;font-size:28px;line-height:1;cursor:pointer;padding:4px 7px}.lauco-consent__copy{flex:1;min-width:0}
.lauco-consent__copy strong{display:block;font-size:17px;margin-bottom:5px}
.lauco-consent__copy p{margin:0;color:#555;line-height:1.55;font-size:14px}
.lauco-consent__copy a{text-decoration:underline}
.lauco-consent__actions{display:flex;gap:10px;flex:0 0 auto}
.lauco-consent__button{border:1px solid #222;background:#222;color:#fff;padding:11px 16px;font-size:13px;font-weight:700;cursor:pointer}
.lauco-consent__button--secondary{background:#fff;color:#222}
.lauco-consent-manage{position:fixed;z-index:10040;right:14px;bottom:14px;border:1px solid rgba(0,0,0,.18);background:#fff;color:#333;padding:7px 10px;font-size:11px;box-shadow:0 5px 18px rgba(0,0,0,.12);cursor:pointer}
@media(max-width:767px){.lauco-consent{left:10px;right:10px;bottom:10px}.lauco-consent__inner{display:block;padding:18px}.lauco-consent__actions{margin-top:15px;display:grid;grid-template-columns:1fr 1fr}.lauco-consent__button{width:100%}.lauco-consent-manage{right:10px;bottom:10px}}
</style>
<script>
(function () {
    'use strict';
    var banner = document.getElementById('lauco-consent');
    var manage = document.getElementById('lauco-consent-manage');
    if (!banner || !manage || !window.LaucoAnalytics) {
        return;
    }

    function openBanner() {
        banner.hidden = false;
        manage.hidden = true;
    }

    function closeBanner() {
        banner.hidden = true;
        manage.hidden = false;
    }

    banner.addEventListener('click', function (event) {
        var button = event.target.closest('[data-consent]');
        if (!button) {
            return;
        }
        window.LaucoAnalytics.setConsent(button.getAttribute('data-consent') === 'accept');
        closeBanner();
    });

    manage.addEventListener('click', openBanner);
    window.addEventListener('lauco:consent-open', openBanner);

    var choice = window.LaucoAnalytics.getChoice();
    if (choice) {
        closeBanner();
    } else {
        openBanner();
    }
})();
</script>
