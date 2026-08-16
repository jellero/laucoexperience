<?php
require_once LAUCO_ROOT . '/inc/translations.php';
$backToTopLocale = content_language_from_request();
$backToTopLabels = [
    'it' => 'Torna in cima',
    'en' => 'Back to top',
    'de' => 'Nach oben',
    'sl' => 'Nazaj na vrh',
];
$backToTopLabel = $backToTopLabels[$backToTopLocale] ?? $backToTopLabels['it'];
?>
<!-- jQuery core -->
<script src="assets/js/jquery.min.js"></script>

<!-- Bootstrap -->
<script src="assets/js/bootstrap/bootstrap.min.js"></script>

<!-- Plugin JS -->
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

<!-- Template main -->
<script src="assets/js/main.js"></script>

<button
    id="lauco-back-to-top"
    class="lauco-back-to-top"
    type="button"
    aria-label="<?= htmlspecialchars($backToTopLabel, ENT_QUOTES, 'UTF-8') ?>"
    title="<?= htmlspecialchars($backToTopLabel, ENT_QUOTES, 'UTF-8') ?>"
>
    <span aria-hidden="true">&#8593;</span>
</button>

<style>
.lauco-back-to-top {
    position: fixed;
    right: 20px;
    bottom: 20px;
    z-index: 10020;
    width: 46px;
    height: 46px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 1px solid rgba(0, 0, 0, .14);
    border-radius: 50%;
    background: #fff;
    color: #222;
    box-shadow: 0 6px 22px rgba(0, 0, 0, .16);
    font-size: 24px;
    line-height: 1;
    cursor: pointer;
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    pointer-events: none;
    transition: opacity .2s ease, transform .2s ease, visibility .2s ease, box-shadow .2s ease;
}
.lauco-back-to-top.is-visible {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
    pointer-events: auto;
}
.lauco-back-to-top:hover,
.lauco-back-to-top:focus-visible {
    box-shadow: 0 8px 26px rgba(0, 0, 0, .24);
}
.lauco-back-to-top:focus-visible {
    outline: 2px solid currentColor;
    outline-offset: 3px;
}
@media (max-width: 767px) {
    .lauco-back-to-top {
        right: 14px;
        bottom: 14px;
        width: 44px;
        height: 44px;
    }
}
@media (prefers-reduced-motion: reduce) {
    .lauco-back-to-top {
        transition: none;
    }
}
</style>

<script>
(function () {
    'use strict';

    var button = document.getElementById('lauco-back-to-top');
    if (!button) {
        return;
    }

    function updateVisibility() {
        var visible = window.scrollY > 500;
        button.classList.toggle('is-visible', visible);
    }

    button.addEventListener('click', function () {
        var reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        window.scrollTo({
            top: 0,
            behavior: reduceMotion ? 'auto' : 'smooth'
        });
    });

    window.addEventListener('scroll', updateVisibility, { passive: true });
    updateVisibility();
})();
</script>

<script>
(function () {
    'use strict';

    function trackedGpxUrl(href) {
        try {
            var url = new URL(href, window.location.origin);
            if (!/\.gpx$/i.test(url.pathname)) {
                return null;
            }
            var parts = url.pathname.split('/');
            var filename = decodeURIComponent(parts[parts.length - 1] || '');
            if (!filename || !/\.gpx$/i.test(filename)) {
                return null;
            }
            return '/gpx/' + encodeURIComponent(filename) + '?download=1';
        } catch (e) {
            return null;
        }
    }

    document.querySelectorAll('a[download][href]').forEach(function (link) {
        var tracked = trackedGpxUrl(link.getAttribute('href') || '');
        if (tracked) {
            link.setAttribute('href', tracked);
        }
    });
})();
</script>

<?php require LAUCO_VIEW_PATH . '/partials/consent.php'; ?>
<?php require LAUCO_VIEW_PATH . '/partials/share.php'; ?>
