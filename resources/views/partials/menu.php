<?php
require_once LAUCO_ROOT . '/inc/translations.php';
require_once LAUCO_ROOT . '/inc/territory-content.php';

$currentLanguage = content_language_from_request();
$languageCodes = ['it' => 'ITA', 'de' => 'DEU', 'en' => 'ENG', 'sl' => 'SLO'];
$territoryNav = territory_navigation();
$participateLabel = match ($currentLanguage) {
    'en' => 'Participate',
    'de' => 'Mitmachen',
    'sl' => 'Sodelujte',
    default => 'Partecipa',
};
$fractionsLabel = match ($currentLanguage) {
    'en' => 'Villages and hamlets',
    'de' => 'Fraktionen und Weiler',
    'sl' => 'Vasi in zaselki',
    default => 'Frazioni e borgate',
};
$trailMapLabel = match ($currentLanguage) {
    'en' => 'Trail map',
    'de' => 'Wanderkarte',
    'sl' => 'Zemljevid poti',
    default => 'Mappa dei sentieri',
};
$trailStatusLabel = match ($currentLanguage) {
    'en' => 'Check trail status',
    'de' => 'Zustand der Wege prüfen',
    'sl' => 'Preveri stanje poti',
    default => 'Consulta lo stato dei sentieri',
};
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
                            <a href="javascript:void(0)" class="<?= isCurrent(['map.php','segnaletica.php','consigli.php','stato-sentieri.php']) ? 'active-item' : '' ?>">Mappa</a>
                            <ul class="sub-menu">
                                <li><a href="/mappa"><?= htmlspecialchars($trailMapLabel, ENT_QUOTES, 'UTF-8') ?></a></li>
                                <li><a href="/segnaletica">Segnaletica</a></li>
                                <li><a href="/consigli">Consigli escursionistici</a></li>
                                <li><a href="<?= htmlspecialchars(content_language_url($currentLanguage, '/stato-sentieri'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($trailStatusLabel, ENT_QUOTES, 'UTF-8') ?></a></li>
                            </ul>
                        </li>

                        <li class="submenu">
                            <a href="javascript:void(0)" class="<?= isCurrent(['itinerari-piedi.php','itinerari-mtb.php','itinerari-speciali.php']) ? 'active-item' : '' ?>">Itinerari</a>
                            <ul class="sub-menu">
                                <li><a href="/itinerari-piedi">A piedi</a></li>
                                <li><a href="/itinerari-mtb">In MTB</a></li>
                                <li><a href="/itinerari-speciali">Itinerari speciali</a></li>
                            </ul>
                        </li>

                        <li class="submenu">
                            <a href="javascript:void(0)" class="<?= isCurrent(['luoghi.php','luogo.php','frazioni.php','forra.php','barbecue.php','storia.php','natura.php','come-arrivare.php']) ? 'active-item' : '' ?>"><?= htmlspecialchars((string) ($territoryNav['territory'] ?? 'Territorio'), ENT_QUOTES, 'UTF-8') ?></a>
                            <ul class="sub-menu">
                                <li><a href="/luoghi">Luoghi</a></li>
                                <li><a href="<?= htmlspecialchars(content_language_url($currentLanguage, '/frazioni'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($fractionsLabel, ENT_QUOTES, 'UTF-8') ?></a></li>
                                <li><a href="<?= htmlspecialchars(content_language_url($currentLanguage, '/storia'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($territoryNav['history'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a></li>
                                <li><a href="<?= htmlspecialchars(content_language_url($currentLanguage, '/natura'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($territoryNav['nature'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a></li>
                                <li><a href="/forra">Forra del Vinadia</a></li>
                                <li><a href="/barbecue">Aree barbecue</a></li>
                                <li><a href="<?= htmlspecialchars(content_language_url($currentLanguage, '/come-arrivare'), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) ($territoryNav['arrive'] ?? ''), ENT_QUOTES, 'UTF-8') ?></a></li>
                            </ul>
                        </li>

                        <li class="submenu">
                            <a href="javascript:void(0)" class="<?= isCurrent(['gestione-sentieri.php','contribuisci.php','segnala-problema.php']) ? 'active-item' : '' ?>"><?= htmlspecialchars($participateLabel, ENT_QUOTES, 'UTF-8') ?></a>
                            <ul class="sub-menu">
                                <li><a href="/gestione-sentieri">Gestione sentieri</a></li>
                                <li><a href="/contribuisci">Contribuisci</a></li>
                                <li><a href="/segnala-problema">Segnala problema</a></li>
                            </ul>
                        </li>

                        <li><a href="/eventi" class="<?= isCurrent(['eventi.php','evento.php','eventi-archivio.php']) ? 'active-item' : '' ?>">Eventi</a></li>
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
