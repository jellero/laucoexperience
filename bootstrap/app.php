<?php
declare(strict_types=1);

use LaucoExperience\Http\ApplicationFactory;
use LaucoExperience\Localization\SiteCatalogRepository;

$root = dirname(__DIR__);
require_once $root . '/inc/env.php';

$autoload = $root . '/vendor/autoload.php';
if (!is_file($autoload)) {
    throw new RuntimeException('Dipendenze mancanti: eseguire composer install prima di avviare il nuovo framework.');
}
require_once $autoload;

$catalogs = new SiteCatalogRepository(
    $root . '/resources/lang',
    $root . '/storage/translations'
);

return ApplicationFactory::create($root, $catalogs);
