<?php
require_once LAUCO_ROOT . '/inc/translations.php';

$currentLanguage = content_language_from_request();
$languageCodes = ['it' => 'ITA', 'de' => 'DEU', 'en' => 'ENG', 'sl' => 'SLO'];
if (!function_exists('isCurrent')) {
    function isCurrent($pages): bool {
        $current = basename((string) ($_SERVER['PHP_SELF'] ?? ''));
        return in_array($current, (array) $pages, true);
    }
}
?>
<header id="header" class="fixed transparent full-width">
    <div class="container">
        <nav class="navbar navbar-default white">
            <div id="logo">
                <a class="navbar-brand" href="<?= htmlspecialchars(content_language_url($currentLanguage, '/index'), ENT_QUOTES, 'UTF-8') ?>">
                    <img src="assets/img/logo.png" class="normal" alt="Lauco Experience">
                    <img src="assets/img/logo.png" class="retina" alt="Lauco Experience">
                    <img src="assets/img/logo_white.png" class="normal white-logo" alt="Lauco Experience">
                    <img src="assets/img/logo_white.png" class="retina white-logo" alt="Lauco Experience">
                </a>
            </div>

            <div id="menu-classic">
                <div class="menu-holder">
                    <ul>
                        <li><a href="/" class="<?= isCurrent('index.php') ? 'active-item' : '' ?>">Home</a></li>
                        <li class="submenu">
                            <a href="javascript:void(0)" class="<?= isCurrent(['map.php','segnaletica.php','consigli.php']) ? 'active-item' : '' ?>">Mappa</a>
                            <ul class="sub-menu">
                                <li><a href="/map">Mappa</a></li>
                                <li><a href="/segnaletica">Info segnaletica</a></li>
                                <li><a href="/consigli">Consigli escursionistici</a></li>
                            </ul>
                        </li>
                        <li class="submenu">
                            <a href="javascript:void(0)" class="<?= isCurrent(['itinerari-piedi.php','itinerari-mtb.php']) ? 'active-item' : '' ?>">Itinerari</a>
                            <ul class="sub-menu">
                                <li><a href="/itinerari-piedi">A piedi</a></li>
                                <li><a href="/itinerari-mtb">In MTB</a></li>
                            </ul>
                        </li>
                        <li><a href="/itinerari-speciali" class="<?= isCurrent('itinerari-speciali.php') ? 'active-item' : '' ?>">Speciali</a></li>
                        <li class="submenu">
                            <a href="javascript:void(0)" class="<?= isCurrent(['gestione-sentieri.php','contribuisci.php','segnala-problema.php']) ? 'active-item' : '' ?>">Patrimonio</a>
                            <ul class="sub-menu">
                                <li><a href="/gestione-sentieri">Gestione sentieri</a></li>
                                <li><a href="/contribuisci">Contribuisci</a></li>
                                <li><a href="/segnala-problema">Segnala problema</a></li>
                            </ul>
                        </li>
                        <li><a href="/luoghi" class="<?= isCurrent(['luoghi.php','luogo.php']) ? 'active-item' : '' ?>">Luoghi</a></li>
                        <li><a href="/eventi" class="<?= isCurrent(['eventi.php','evento.php']) ? 'active-item' : '' ?>">Eventi</a></li>
                        <li><a href="/forra" class="<?= isCurrent('forra.php') ? 'active-item' : '' ?>">Forra del Vinadia</a></li>
                        <li><a href="/contatti" class="<?= isCurrent('contatti.php') ? 'active-item' : '' ?>">Contatti</a></li>
                        <li class="submenu"><a><?= htmlspecialchars($languageCodes[$currentLanguage], ENT_QUOTES, 'UTF-8') ?></a>
                            <ul class="sub-menu">
                            <?php foreach ($languageCodes as $language => $label): ?>
                                <?php if ($language !== $currentLanguage): ?>
                                    <a href="<?= htmlspecialchars(content_language_url($language), ENT_QUOTES, 'UTF-8') ?>" hreflang="<?= htmlspecialchars($language, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            </ul>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mobile-lang-inline" aria-label="Seleziona lingua">
                <?php foreach ($languageCodes as $language => $label): ?>
                    <a href="<?= htmlspecialchars(content_language_url($language), ENT_QUOTES, 'UTF-8') ?>"<?= $language === $currentLanguage ? ' class="active"' : '' ?> hreflang="<?= htmlspecialchars($language, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
                <?php endforeach; ?>
            </div>
            <div id="menu-responsive-classic">
                <div class="menu-button">
                    <span class="bar bar-1"></span><span class="bar bar-2"></span><span class="bar bar-3"></span>
                </div>
            </div>
        </nav>
    </div>
</header>
