<?php
require_once __DIR__ . '/translations.php';
/**
 * Header + navigazione principale – Lauco Experience
 * - Logo ora include le 4 versioni (normal, retina, white, white retina)
 * - Menu invariato rispetto all’ultima revisione (senza “cerca”)
 */

$current = basename($_SERVER['PHP_SELF']);
$currentLanguage = content_language_from_request();
$languageCodes = ['it' => 'ITA', 'de' => 'DEU', 'en' => 'ENG', 'sl' => 'SLO'];
function isCurrent($pages) {
    global $current;
    return in_array($current, (array)$pages);
}
?>
<header id="header" class="fixed transparent full-width">
    <div class="container">
        <nav class="navbar navbar-default white">

            <!-- Logo (mostra white quando l’header è trasparente, normal dopo scroll) -->
            <div id="logo">
                <a class="navbar-brand" href="<?= htmlspecialchars(content_language_url($currentLanguage, '/index'), ENT_QUOTES, 'UTF-8') ?>">
                    <!-- versione su background chiaro (dopo scroll) -->
                    <img src="assets/img/logo.png"           class="normal"         alt="Lauco Experience">
                    <img src="assets/img/logo.png"          class="retina"         alt="Lauco Experience">
                    <!-- versione su header trasparente (white) -->
                    <img src="assets/img/logo_white.png"     class="normal white-logo"  alt="Lauco Experience">
                    <img src="assets/img/logo_white.png"  class="retina white-logo"  alt="Lauco Experience">
                </a>
            </div>

            <!-- Menu desktop -->
            <div id="menu-classic">
                <div class="menu-holder">
                    <ul>
                        <li><a href="index.php" class="<?= isCurrent('index.php') ? 'active-item' : '' ?>">Home</a></li>

                        <!-- MAPPA -->
                        <li class="submenu">
                            <a href="javascript:void(0)" class="<?= isCurrent(['map.php','segnaletica.php','consigli.php']) ? 'active-item' : '' ?>">Mappa</a>
                            <ul class="sub-menu">
                                <li><a href="map.php">Mappa</a></li>
                                <li><a href="segnaletica.php">Info segnaletica</a></li>
                                <li><a href="consigli.php">Consigli escursionistici</a></li>
                            </ul>
                        </li>

                        <!-- ITINERARI CONSIGLIATI -->
                        <li class="submenu">
                            <a href="javascript:void(0)" class="<?= isCurrent(['itinerari-piedi.php','itinerari-mtb.php']) ? 'active-item' : '' ?>">Itinerari</a>
                            <ul class="sub-menu">
                                <li><a href="itinerari-piedi.php">A piedi</a></li>
                                <li><a href="itinerari-mtb.php">In MTB</a></li>
                            </ul>
                        </li>

                        <!-- ITINERARI SPECIALI -->
                        <li>
                            <a href="itinerari-speciali.php" class="<?= isCurrent(['itinerari-speciali.php']) ? 'active-item' : '' ?>">Speciali</a>
                        </li>

                        <!-- PATRIMONIO -->
                        <li class="submenu">
                            <a href="javascript:void(0)" class="<?= isCurrent(['gestione-sentieri.php','contribuisci.php','segnala-problema.php']) ? 'active-item' : '' ?>">Patrimonio</a>
                            <ul class="sub-menu">
                                <li><a href="gestione-sentieri.php">Gestione sentieri</a></li>
                                <li><a href="contribuisci.php">Contribuisci</a></li>
                                <li><a href="segnala-problema.php">Segnala problema</a></li>
                            </ul>
                        </li>

                        <!-- LUOGHI -->
                        <li>
                            <a href="luoghi" class="<?= isCurrent(['luoghi-celti.php','luoghi-trava.php','luoghi-terrazza.php','luoghi-clap.php']) ? 'active-item' : '' ?>">Luoghi</a>
                            
                        </li>

                        <li><a href="eventi.php" class="<?= isCurrent('eventi.php') ? 'active-item' : '' ?>">Eventi</a></li>
                        <li><a href="contatti.php"          class="<?= isCurrent('contatti.php')         ? 'active-item' : '' ?>">Contatti</a></li>
                    
                    
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

            <!-- QUI: selettore lingua -->
            <div class="mobile-lang-inline" aria-label="Seleziona lingua">
                <?php foreach ($languageCodes as $language => $label): ?>
                    <a href="<?= htmlspecialchars(content_language_url($language), ENT_QUOTES, 'UTF-8') ?>"<?= $language === $currentLanguage ? ' class="active"' : '' ?> hreflang="<?= htmlspecialchars($language, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
                <?php endforeach; ?>
            </div>
            <!-- Bottone hamburger (mobile) -->
            <div id="menu-responsive-classic">
                <div class="menu-button">
                    <span class="bar bar-1"></span><span class="bar bar-2"></span><span class="bar bar-3"></span>
                </div>
            </div>

        </nav>
    </div>
</header>
