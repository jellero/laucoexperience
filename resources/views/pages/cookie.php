<?php
require_once LAUCO_ROOT . '/inc/translations.php';
$locale = content_language_from_request();
$copy = [
'it'=>[
 'title'=>'Cookie Policy','subtitle'=>'Cookie tecnici, preferenze e Google Analytics','intro'=>'Questa pagina descrive gli strumenti di memorizzazione e misurazione usati da Lauco Experience. Google Analytics è disattivato per impostazione predefinita e viene caricato solo dopo una scelta esplicita dell’utente.',
 'sections'=>[
  ['Cookie e strumenti necessari','Il sito usa solo le funzionalità tecniche necessarie al funzionamento. La preferenza relativa agli analytics viene salvata nel localStorage del browser con la chiave lauco_consent_v1, così il sito può ricordare la scelta senza attivare Google Analytics.'],
  ['Google Analytics 4','Se accetti la misurazione statistica, viene caricato Google Analytics 4 con identificativo G-NCKVWM2EQ0. Prima del consenso analytics_storage, ad_storage, ad_user_data e ad_personalization sono impostati su denied. Dopo l’accettazione viene concesso soltanto analytics_storage; le funzioni pubblicitarie restano disabilitate. Google Signals e la personalizzazione pubblicitaria sono disabilitati.'],
  ['Cookie Analytics','Con il consenso, Google Analytics può impostare cookie come _ga e _ga_<container-id>. La durata predefinita indicata da Google per questi cookie è fino a 2 anni, salvo limiti del browser o cancellazione anticipata.'],
  ['Revoca e modifica della scelta','Puoi modificare la scelta in qualsiasi momento tramite il pulsante “Gestisci cookie”. La revoca disabilita nuovamente Google Analytics e il sito tenta di rimuovere i cookie _ga accessibili dal dominio.'],
  ['Statistiche aggregate lato server','Le statistiche dei QR, i conteggi giornalieri delle pagine e le azioni avviate dal pulsante Condividi sono elaborati lato server e non richiedono cookie analytics né Google Analytics. Per accessi e condivisioni vengono salvati soltanto giorno, pagina, lingua, canale scelto e conteggio aggregato.'],
 ],
 'manage'=>'Gestisci cookie','updated'=>'Ultimo aggiornamento: 16 agosto 2026',
],
'en'=>[
 'title'=>'Cookie Policy','subtitle'=>'Technical storage, preferences and Google Analytics','intro'=>'This page describes the storage and measurement tools used by Lauco Experience. Google Analytics is disabled by default and is loaded only after the user makes an explicit choice.',
 'sections'=>[
  ['Necessary tools','The website uses only the technical functions required to operate. The analytics preference is stored in browser localStorage under the key lauco_consent_v1, allowing the website to remember the choice without activating Google Analytics.'],
  ['Google Analytics 4','If you accept statistical measurement, Google Analytics 4 is loaded with measurement ID G-NCKVWM2EQ0. Before consent, analytics_storage, ad_storage, ad_user_data and ad_personalization are denied. After acceptance only analytics_storage is granted; advertising functions remain disabled. Google Signals and ad personalisation are disabled.'],
  ['Analytics cookies','With consent, Google Analytics may set cookies such as _ga and _ga_<container-id>. Google states a default lifetime of up to 2 years, subject to browser limits or earlier deletion.'],
  ['Withdraw or change consent','You can change your choice at any time using “Manage cookies”. Withdrawal disables Google Analytics again and the website attempts to remove accessible _ga cookies.'],
  ['Aggregate server-side statistics','QR statistics, daily page-view counts and actions started from the Share button are processed server-side and do not require analytics cookies or Google Analytics. Only the day, page, language, selected channel and aggregate count are stored for views and shares.'],
 ],
 'manage'=>'Manage cookies','updated'=>'Last updated: 16 August 2026',
],
'de'=>[
 'title'=>'Cookie-Richtlinie','subtitle'=>'Technische Speicherung, Einstellungen und Google Analytics','intro'=>'Diese Seite beschreibt die von Lauco Experience verwendeten Speicher- und Messwerkzeuge. Google Analytics ist standardmäßig deaktiviert und wird erst nach einer ausdrücklichen Auswahl geladen.',
 'sections'=>[
  ['Erforderliche Funktionen','Die Website verwendet nur die für den Betrieb erforderlichen technischen Funktionen. Die Analytics-Einstellung wird im localStorage des Browsers unter lauco_consent_v1 gespeichert, damit die Auswahl ohne Aktivierung von Google Analytics gemerkt werden kann.'],
  ['Google Analytics 4','Bei Zustimmung wird Google Analytics 4 mit der Mess-ID G-NCKVWM2EQ0 geladen. Vor der Zustimmung stehen analytics_storage, ad_storage, ad_user_data und ad_personalization auf denied. Danach wird nur analytics_storage freigegeben; Werbefunktionen, Google Signals und Anzeigenpersonalisierung bleiben deaktiviert.'],
  ['Analytics-Cookies','Mit Zustimmung kann Google Analytics Cookies wie _ga und _ga_<container-id> setzen. Google nennt eine Standarddauer von bis zu 2 Jahren, vorbehaltlich Browsergrenzen oder früherer Löschung.'],
  ['Einwilligung ändern','Die Auswahl kann jederzeit über „Cookies verwalten“ geändert werden. Ein Widerruf deaktiviert Google Analytics erneut und die Website versucht, zugängliche _ga-Cookies zu entfernen.'],
  ['Aggregierte Serverstatistiken','QR-Statistiken, tägliche Seitenaufrufe und über die Teilen-Schaltfläche gestartete Aktionen werden serverseitig verarbeitet und benötigen weder Analytics-Cookies noch Google Analytics. Gespeichert werden nur Tag, Seite, Sprache, gewählter Kanal und aggregierte Anzahl.'],
 ],
 'manage'=>'Cookies verwalten','updated'=>'Letzte Aktualisierung: 16. August 2026',
],
'sl'=>[
 'title'=>'Pravilnik o piškotkih','subtitle'=>'Tehnično shranjevanje, nastavitve in Google Analytics','intro'=>'Ta stran opisuje orodja za shranjevanje in merjenje, ki jih uporablja Lauco Experience. Google Analytics je privzeto izklopljen in se naloži šele po izrecni izbiri uporabnika.',
 'sections'=>[
  ['Nujne funkcije','Spletno mesto uporablja le tehnične funkcije, potrebne za delovanje. Nastavitev analitike je shranjena v localStorage brskalnika pod ključem lauco_consent_v1, zato si spletno mesto zapomni izbiro brez aktiviranja storitve Google Analytics.'],
  ['Google Analytics 4','Če dovolite statistično merjenje, se naloži Google Analytics 4 z ID G-NCKVWM2EQ0. Pred soglasjem so analytics_storage, ad_storage, ad_user_data in ad_personalization nastavljeni na denied. Po sprejetju je dovoljen le analytics_storage; oglaševalske funkcije, Google Signals in personalizacija oglasov ostanejo izključeni.'],
  ['Analitični piškotki','Po soglasju lahko Google Analytics nastavi piškotke, kot sta _ga in _ga_<container-id>. Google navaja privzeto trajanje do 2 let, ob upoštevanju omejitev brskalnika ali predčasnega izbrisa.'],
  ['Sprememba ali preklic','Izbiro lahko kadar koli spremenite z gumbom »Upravljanje piškotkov«. Preklic znova onemogoči Google Analytics in spletno mesto poskusi odstraniti dostopne piškotke _ga.'],
  ['Združena strežniška statistika','Statistika kod QR, dnevno število ogledov in dejanja, začeta z gumbom za deljenje, se obdelujejo na strežniku brez analitičnih piškotkov ali Google Analytics. Shranijo se le dan, stran, jezik, izbrani kanal in združeno število.'],
 ],
 'manage'=>'Upravljanje piškotkov','updated'=>'Zadnja posodobitev: 16. avgust 2026',
],
];
$c=$copy[$locale] ?? $copy['it'];
?>
<!DOCTYPE html><html lang="<?= htmlspecialchars($locale, ENT_QUOTES, 'UTF-8') ?>"><head><?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>
<style>.policy-page .lead{font-size:18px;line-height:1.75;color:#555}.policy-card{background:#fff;padding:28px;margin:0 0 22px;box-shadow:0 10px 30px rgba(0,0,0,.06)}.policy-card h3{margin-top:0}.policy-card p{color:#666;line-height:1.75}.policy-note{font-size:13px;color:#777;margin-top:30px}</style></head><body>
<div id="myloader"><span class="loader"><div class="inner-loader"></div></span></div><div id="main-wrap" class="full-width"><?php require LAUCO_VIEW_PATH . '/partials/menu.php'; ?>
<div id="page-content" class="header-static"><div id="flexslider" class="fullpage-wrap small"><ul class="slides"><li style="background-image:url(assets/img/contact.jpg)"><div class="container text text-center"><h1 class="white margin-bottom-small"><?= htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8') ?></h1><p class="heading white"><?= htmlspecialchars($c['subtitle'], ENT_QUOTES, 'UTF-8') ?></p></div><div class="gradient dark"></div></li></ul></div>
<div id="page-wrap" class="content-section fullpage-wrap policy-page"><div class="container text"><p class="lead"><?= htmlspecialchars($c['intro'], ENT_QUOTES, 'UTF-8') ?></p>
<?php foreach ($c['sections'] as [$heading,$text]): ?><section class="policy-card"><h3><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?></h3><p><?= htmlspecialchars($text, ENT_QUOTES, 'UTF-8') ?></p></section><?php endforeach; ?>
<p><button type="button" class="btn-alt small" onclick="window.LaucoAnalytics && window.LaucoAnalytics.reopen()"><?= htmlspecialchars($c['manage'], ENT_QUOTES, 'UTF-8') ?></button></p><p class="policy-note"><?= htmlspecialchars($c['updated'], ENT_QUOTES, 'UTF-8') ?></p></div></div></div>
<?php require LAUCO_VIEW_PATH . '/partials/footer.php'; ?></div><?php require LAUCO_VIEW_PATH . '/partials/scripts.php'; ?></body></html>
