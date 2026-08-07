<?php
require_once LAUCO_ROOT . '/inc/translations.php';
/** Meta e fogli di stile – Lauco Experience */
?>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">

<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-NCKVWM2EQ0"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-NCKVWM2EQ0');
</script>

<title><?= htmlspecialchars(site_text('meta.title', null, 'Lauco Experience | Outdoor, Itinerari e Natura'), ENT_QUOTES, 'UTF-8') ?></title>
<?php foreach (array_keys(content_supported_languages()) as $alternateLanguage): ?>
<link rel="alternate" hreflang="<?= htmlspecialchars($alternateLanguage, ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars(content_language_url($alternateLanguage), ENT_QUOTES, 'UTF-8') ?>">
<?php endforeach; ?>


<!-- Bootstrap -->
<link rel="stylesheet" href="assets/css/bootstrap/bootstrap.min.css">
<link rel="stylesheet" href="assets/css/bootstrap/bootstrap-theme.min.css">

<!-- Template CSS -->
<link rel="stylesheet" href="assets/css/style.css">
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
