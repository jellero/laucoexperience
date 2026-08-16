<?php
declare(strict_types=1);

require_once LAUCO_ROOT . '/inc/seo.php';

$sharePath = seo_path();
$shareExcludedPaths = [
    '/400',
    '/login',
    '/crea-account',
    '/contatti',
    '/contribuisci',
    '/segnala-problema',
    '/privacy',
    '/cookie',
];

if (in_array($sharePath, $shareExcludedPaths, true)) {
    return;
}

$shareLocale = content_language_from_request();
$shareLabels = [
    'it' => [
        'share' => 'Condividi',
        'title' => 'Condividi questa pagina',
        'intro' => 'Scegli come inviare il collegamento.',
        'close' => 'Chiudi',
        'facebook' => 'Facebook',
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
        'copy' => 'Copia link',
        'native' => 'Altre app',
        'copied' => 'Link copiato.',
        'copy_error' => 'Non è stato possibile copiare il link.',
        'share_error' => 'Non è stato possibile aprire la condivisione.',
    ],
    'en' => [
        'share' => 'Share',
        'title' => 'Share this page',
        'intro' => 'Choose how to send the link.',
        'close' => 'Close',
        'facebook' => 'Facebook',
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
        'copy' => 'Copy link',
        'native' => 'Other apps',
        'copied' => 'Link copied.',
        'copy_error' => 'The link could not be copied.',
        'share_error' => 'Sharing could not be opened.',
    ],
    'de' => [
        'share' => 'Teilen',
        'title' => 'Diese Seite teilen',
        'intro' => 'Wählen Sie, wie Sie den Link senden möchten.',
        'close' => 'Schließen',
        'facebook' => 'Facebook',
        'whatsapp' => 'WhatsApp',
        'email' => 'E-Mail',
        'copy' => 'Link kopieren',
        'native' => 'Andere Apps',
        'copied' => 'Link kopiert.',
        'copy_error' => 'Der Link konnte nicht kopiert werden.',
        'share_error' => 'Die Freigabe konnte nicht geöffnet werden.',
    ],
    'sl' => [
        'share' => 'Deli',
        'title' => 'Deli to stran',
        'intro' => 'Izberite način pošiljanja povezave.',
        'close' => 'Zapri',
        'facebook' => 'Facebook',
        'whatsapp' => 'WhatsApp',
        'email' => 'E-pošta',
        'copy' => 'Kopiraj povezavo',
        'native' => 'Druge aplikacije',
        'copied' => 'Povezava je kopirana.',
        'copy_error' => 'Povezave ni bilo mogoče kopirati.',
        'share_error' => 'Deljenja ni bilo mogoče odpreti.',
    ],
];
$shareText = $shareLabels[$shareLocale] ?? $shareLabels['it'];
?>

<div
    class="lauco-share"
    data-lauco-share
    data-copied-label="<?= e($shareText['copied']) ?>"
    data-copy-error-label="<?= e($shareText['copy_error']) ?>"
    data-share-error-label="<?= e($shareText['share_error']) ?>"
    data-share-track-url="/api/share"
>
    <button
        class="lauco-share-trigger"
        type="button"
        aria-haspopup="dialog"
        aria-controls="lauco-share-dialog"
        aria-expanded="false"
        data-share-open
    >
        <i class="fa fa-share-alt" aria-hidden="true"></i>
        <span><?= e($shareText['share']) ?></span>
    </button>

    <div class="lauco-share-overlay" data-share-overlay hidden>
        <section
            id="lauco-share-dialog"
            class="lauco-share-dialog"
            role="dialog"
            aria-modal="true"
            aria-labelledby="lauco-share-title"
            aria-describedby="lauco-share-intro"
            tabindex="-1"
        >
            <button class="lauco-share-close" type="button" aria-label="<?= e($shareText['close']) ?>" data-share-close>
                <i class="fa fa-times" aria-hidden="true"></i>
            </button>

            <h2 id="lauco-share-title"><?= e($shareText['title']) ?></h2>
            <p id="lauco-share-intro"><?= e($shareText['intro']) ?></p>

            <div class="lauco-share-options">
                <a class="lauco-share-option is-facebook" href="#" target="_blank" rel="noopener noreferrer" data-share-facebook data-share-channel="facebook">
                    <i class="fa fa-facebook" aria-hidden="true"></i>
                    <span><?= e($shareText['facebook']) ?></span>
                </a>
                <a class="lauco-share-option is-whatsapp" href="#" target="_blank" rel="noopener noreferrer" data-share-whatsapp data-share-channel="whatsapp">
                    <i class="fa fa-whatsapp" aria-hidden="true"></i>
                    <span><?= e($shareText['whatsapp']) ?></span>
                </a>
                <a class="lauco-share-option" href="#" data-share-email data-share-channel="email">
                    <i class="fa fa-envelope-o" aria-hidden="true"></i>
                    <span><?= e($shareText['email']) ?></span>
                </a>
                <button class="lauco-share-option" type="button" data-share-copy>
                    <i class="fa fa-link" aria-hidden="true"></i>
                    <span><?= e($shareText['copy']) ?></span>
                </button>
                <button class="lauco-share-option is-native" type="button" data-share-native hidden>
                    <i class="fa fa-share-alt" aria-hidden="true"></i>
                    <span><?= e($shareText['native']) ?></span>
                </button>
            </div>

            <p class="lauco-share-status" role="status" aria-live="polite" data-share-status></p>
        </section>
    </div>
</div>

<script src="/assets/js/share.js?v=<?= (int) filemtime(LAUCO_ROOT . '/assets/js/share.js') ?>"></script>
