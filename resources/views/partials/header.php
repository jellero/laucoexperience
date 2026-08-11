<?php
require_once LAUCO_ROOT . '/inc/translations.php';
require_once LAUCO_ROOT . '/inc/seo.php';
/** Meta, SEO, consenso analytics e fogli di stile – Lauco Experience */
foreach (['percorso', 'luogo', 'evento'] as $seoEntityVariable) {
    if (isset($$seoEntityVariable) && is_array($$seoEntityVariable)) {
        $GLOBALS[$seoEntityVariable] = $$seoEntityVariable;
    }
}
$seo = seo_metadata();

// /map è riservato al QR tracciato; /mappa è la pagina pubblica indicizzabile.
if (seo_path() === '/mappa') {
    $mapTitle = match (content_language_from_request()) {
        'en' => 'Trail map',
        'de' => 'Wanderkarte',
        'sl' => 'Zemljevid poti',
        default => 'Mappa dei sentieri',
    };
    $mapDescription = match (content_language_from_request()) {
        'en' => 'Interactive map of Lauco trails with GPX tracks, elevation profiles and useful information about the local hiking network.',
        'de' => 'Interaktive Karte der Wege von Lauco mit GPX-Tracks, Höhenprofilen und Informationen zum örtlichen Wegenetz.',
        'sl' => 'Interaktivni zemljevid poti v Laucu z GPX-sledmi, višinskimi profili in informacijami o lokalni pohodniški mreži.',
        default => 'Mappa interattiva dei sentieri di Lauco con tracce GPX, profili altimetrici e informazioni utili per conoscere la rete escursionistica locale.',
    };
    $seo['title'] = $mapTitle . ' | Lauco Experience';
    $seo['description'] = $mapDescription;

    if (isset($seo['json_ld']['@graph']) && is_array($seo['json_ld']['@graph'])) {
        foreach ($seo['json_ld']['@graph'] as &$seoGraphNode) {
            if (($seoGraphNode['@type'] ?? null) !== 'BreadcrumbList' || !isset($seoGraphNode['itemListElement']) || !is_array($seoGraphNode['itemListElement'])) {
                continue;
            }
            $lastIndex = array_key_last($seoGraphNode['itemListElement']);
            if ($lastIndex !== null && is_array($seoGraphNode['itemListElement'][$lastIndex])) {
                $seoGraphNode['itemListElement'][$lastIndex]['name'] = $mapTitle;
            }
        }
        unset($seoGraphNode);
    }
}
?>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?= htmlspecialchars((string) $seo['title'], ENT_QUOTES, 'UTF-8') ?></title>
<meta name="description" content="<?= htmlspecialchars((string) $seo['description'], ENT_QUOTES, 'UTF-8') ?>">
<meta name="robots" content="<?= htmlspecialchars((string) $seo['robots'], ENT_QUOTES, 'UTF-8') ?>">
<link rel="canonical" href="<?= htmlspecialchars((string) $seo['canonical'], ENT_QUOTES, 'UTF-8') ?>">
<?php foreach ((array) $seo['alternates'] as $alternateLanguage => $alternateUrl): ?>
<link rel="alternate" hreflang="<?= htmlspecialchars((string) $alternateLanguage, ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars((string) $alternateUrl, ENT_QUOTES, 'UTF-8') ?>">
<?php endforeach; ?>

<meta property="og:site_name" content="Lauco Experience">
<meta property="og:type" content="<?= htmlspecialchars((string) $seo['type'], ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:title" content="<?= htmlspecialchars((string) $seo['title'], ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:description" content="<?= htmlspecialchars((string) $seo['description'], ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:url" content="<?= htmlspecialchars((string) $seo['canonical'], ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:image" content="<?= htmlspecialchars((string) $seo['image'], ENT_QUOTES, 'UTF-8') ?>">
<meta property="og:locale" content="<?= htmlspecialchars((string) $seo['og_locale'], ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= htmlspecialchars((string) $seo['title'], ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:description" content="<?= htmlspecialchars((string) $seo['description'], ENT_QUOTES, 'UTF-8') ?>">
<meta name="twitter:image" content="<?= htmlspecialchars((string) $seo['image'], ENT_QUOTES, 'UTF-8') ?>">
<script type="application/ld+json"><?= json_encode($seo['json_ld'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

<script>
(function () {
    'use strict';

    var measurementId = 'G-NCKVWM2EQ0';
    var storageKey = 'lauco_consent_v1';
    var loaded = false;

    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };

    window.gtag('consent', 'default', {
        ad_storage: 'denied',
        analytics_storage: 'denied',
        ad_user_data: 'denied',
        ad_personalization: 'denied',
        wait_for_update: 500
    });
    window.gtag('set', 'ads_data_redaction', true);

    function readChoice() {
        try {
            var parsed = JSON.parse(window.localStorage.getItem(storageKey) || 'null');
            return parsed && parsed.version === 1 && typeof parsed.analytics === 'boolean' ? parsed : null;
        } catch (error) {
            return null;
        }
    }

    function saveChoice(granted) {
        try {
            window.localStorage.setItem(storageKey, JSON.stringify({
                version: 1,
                analytics: granted === true,
                updatedAt: new Date().toISOString()
            }));
        } catch (error) {
            // Il consenso resta valido per la pagina corrente anche se localStorage non è disponibile.
        }
    }

    function deleteAnalyticsCookies() {
        var host = window.location.hostname;
        var parts = host.split('.').filter(Boolean);
        var parent = parts.length > 2 ? '.' + parts.slice(-2).join('.') : '.' + host;
        var domains = ['', '; domain=' + host, '; domain=' + parent];

        document.cookie.split(';').forEach(function (rawCookie) {
            var name = rawCookie.split('=')[0].trim();
            if (name !== '_ga' && name.indexOf('_ga_') !== 0) {
                return;
            }
            domains.forEach(function (domain) {
                document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/' + domain + '; SameSite=Lax';
            });
        });
    }

    function loadAnalytics() {
        if (loaded || document.querySelector('script[data-lauco-google-analytics]')) {
            loaded = true;
            return;
        }
        loaded = true;
        var script = document.createElement('script');
        script.async = true;
        script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(measurementId);
        script.setAttribute('data-lauco-google-analytics', 'true');
        document.head.appendChild(script);

        window.gtag('js', new Date());
        window.gtag('config', measurementId, {
            allow_google_signals: false,
            allow_ad_personalization_signals: false
        });
    }

    function applyChoice(granted, persist) {
        granted = granted === true;
        if (persist !== false) {
            saveChoice(granted);
        }

        if (granted) {
            window['ga-disable-' + measurementId] = false;
            window.gtag('consent', 'update', {
                analytics_storage: 'granted',
                ad_storage: 'denied',
                ad_user_data: 'denied',
                ad_personalization: 'denied'
            });
            loadAnalytics();
        } else {
            window['ga-disable-' + measurementId] = true;
            window.gtag('consent', 'update', {
                analytics_storage: 'denied',
                ad_storage: 'denied',
                ad_user_data: 'denied',
                ad_personalization: 'denied'
            });
            deleteAnalyticsCookies();
        }

        window.dispatchEvent(new CustomEvent('lauco:consent-changed', {
            detail: { analytics: granted }
        }));
    }

    var initialChoice = readChoice();
    window.LaucoAnalytics = {
        getChoice: readChoice,
        setConsent: function (granted) { applyChoice(granted, true); },
        reopen: function () { window.dispatchEvent(new CustomEvent('lauco:consent-open')); }
    };

    if (initialChoice && initialChoice.analytics === true) {
        applyChoice(true, false);
    } else {
        window['ga-disable-' + measurementId] = true;
    }
})();
</script>

<!-- Bootstrap -->
<link rel="stylesheet" href="assets/css/bootstrap/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/bootstrap/bootstrap-theme.min.css">

<!-- Template CSS -->
<link rel="stylesheet" href="/assets/css/style.css?v=<?= (int) filemtime(LAUCO_ROOT . '/assets/css/style.css') ?>">
<link rel="stylesheet" href="assets/css/font-awesome.min.css">
<link rel="stylesheet" href="assets/css/ionicons.min.css">
<link rel="stylesheet" href="assets/css/puredesign.css">
<link rel="stylesheet" href="assets/css/flexslider.css">
<link rel="stylesheet" href="assets/css/owl.carousel.css">
<link rel="stylesheet" href="assets/css/magnific-popup.css">
<link rel="stylesheet" href="assets/css/jquery.fullPage.css">

<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
<![endif]-->
