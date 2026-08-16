(function () {
    'use strict';

    var root = document.querySelector('[data-lauco-share]');
    if (!root) {
        return;
    }

    var trigger = root.querySelector('[data-share-open]');
    var overlay = root.querySelector('[data-share-overlay]');
    var dialog = root.querySelector('.lauco-share-dialog');
    var closeButton = root.querySelector('[data-share-close]');
    var facebook = root.querySelector('[data-share-facebook]');
    var whatsapp = root.querySelector('[data-share-whatsapp]');
    var email = root.querySelector('[data-share-email]');
    var copyButton = root.querySelector('[data-share-copy]');
    var nativeButton = root.querySelector('[data-share-native]');
    var status = root.querySelector('[data-share-status]');
    var lastFocused = null;

    function metaContent(selector) {
        var element = document.querySelector(selector);
        return element ? String(element.getAttribute('content') || '').trim() : '';
    }

    function shareData() {
        var canonical = document.querySelector('link[rel="canonical"]');
        return {
            title: metaContent('meta[property="og:title"]') || document.title,
            text: metaContent('meta[property="og:description"]'),
            url: canonical && canonical.href ? canonical.href : window.location.href
        };
    }

    function updateDestinations() {
        var data = shareData();
        var message = data.title + ' ' + data.url;
        facebook.href = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(data.url);
        whatsapp.href = 'https://api.whatsapp.com/send?text=' + encodeURIComponent(message);
        email.href = 'mailto:?subject=' + encodeURIComponent(data.title) + '&body=' + encodeURIComponent(data.title + '\n\n' + data.url);
    }

    function focusableElements() {
        return Array.prototype.slice.call(dialog.querySelectorAll('a[href], button:not([hidden]):not([disabled]), [tabindex]:not([tabindex="-1"])'));
    }

    function openDialog() {
        updateDestinations();
        status.textContent = '';
        lastFocused = document.activeElement;
        overlay.hidden = false;
        trigger.setAttribute('aria-expanded', 'true');
        document.body.classList.add('lauco-share-open');
        closeButton.focus();
    }

    function closeDialog() {
        overlay.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
        document.body.classList.remove('lauco-share-open');
        status.textContent = '';
        if (lastFocused && typeof lastFocused.focus === 'function') {
            lastFocused.focus();
        }
    }

    function legacyCopy(value) {
        var field = document.createElement('textarea');
        field.value = value;
        field.setAttribute('readonly', '');
        field.style.position = 'fixed';
        field.style.opacity = '0';
        document.body.appendChild(field);
        field.select();
        var copied = document.execCommand('copy');
        document.body.removeChild(field);
        return copied;
    }

    function copyLink() {
        var value = shareData().url;
        var operation = navigator.clipboard && typeof navigator.clipboard.writeText === 'function'
            ? navigator.clipboard.writeText(value).catch(function () { return legacyCopy(value) ? undefined : Promise.reject(); })
            : (legacyCopy(value) ? Promise.resolve() : Promise.reject());

        operation.then(function () {
            status.textContent = root.getAttribute('data-copied-label') || '';
        }).catch(function () {
            status.textContent = root.getAttribute('data-copy-error-label') || '';
        });
    }

    trigger.addEventListener('click', openDialog);
    closeButton.addEventListener('click', closeDialog);
    copyButton.addEventListener('click', copyLink);

    [facebook, whatsapp, email].forEach(function (link) {
        link.addEventListener('click', function () {
            window.setTimeout(closeDialog, 0);
        });
    });

    if (typeof navigator.share === 'function') {
        nativeButton.hidden = false;
        nativeButton.addEventListener('click', function () {
            navigator.share(shareData()).then(closeDialog).catch(function (error) {
                if (!error || error.name !== 'AbortError') {
                    status.textContent = root.getAttribute('data-share-error-label') || '';
                }
            });
        });
    }

    overlay.addEventListener('click', function (event) {
        if (event.target === overlay) {
            closeDialog();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (overlay.hidden) {
            return;
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            closeDialog();
            return;
        }
        if (event.key !== 'Tab') {
            return;
        }

        var elements = focusableElements();
        if (elements.length === 0) {
            event.preventDefault();
            dialog.focus();
            return;
        }
        var first = elements[0];
        var last = elements[elements.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });
})();
