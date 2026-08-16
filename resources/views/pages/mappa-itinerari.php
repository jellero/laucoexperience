<?php
declare(strict_types=1);

require_once LAUCO_ROOT . '/inc/db.php';
require_once LAUCO_ROOT . '/inc/gpx-stats.php';

$locale = content_language_from_request();
$labels = match ($locale) {
    'en' => [
        'title' => 'Route map', 'subtitle' => 'Explore all published routes on the map',
        'intro' => 'Select a route on the map or use the filters to find the right experience.',
        'all' => 'All', 'walking' => 'Walking', 'mtb' => 'MTB', 'special' => 'Special',
        'routes' => 'Routes', 'explore' => 'Explore', 'show_map' => 'Show on map',
        'distance' => 'Distance', 'ascent' => 'Ascent', 'duration' => 'Duration', 'difficulty' => 'Difficulty',
        'no_routes' => 'No published routes are available.', 'no_track' => 'Map track not available',
        'loading' => 'Loading routes…', 'loaded' => 'routes displayed', 'map_error' => 'No GPX track could be loaded.',
    ],
    'de' => [
        'title' => 'Routenkarte', 'subtitle' => 'Alle veröffentlichten Routen auf der Karte entdecken',
        'intro' => 'Wählen Sie eine Route auf der Karte oder nutzen Sie die Filter.',
        'all' => 'Alle', 'walking' => 'Zu Fuß', 'mtb' => 'MTB', 'special' => 'Besondere',
        'routes' => 'Routen', 'explore' => 'Entdecken', 'show_map' => 'Auf Karte zeigen',
        'distance' => 'Strecke', 'ascent' => 'Höhenmeter', 'duration' => 'Dauer', 'difficulty' => 'Schwierigkeit',
        'no_routes' => 'Keine veröffentlichten Routen verfügbar.', 'no_track' => 'Kartenspur nicht verfügbar',
        'loading' => 'Routen werden geladen…', 'loaded' => 'Routen angezeigt', 'map_error' => 'Keine GPX-Spur konnte geladen werden.',
    ],
    'sl' => [
        'title' => 'Zemljevid poti', 'subtitle' => 'Raziščite vse objavljene poti na zemljevidu',
        'intro' => 'Izberite pot na zemljevidu ali uporabite filtre.',
        'all' => 'Vse', 'walking' => 'Peš', 'mtb' => 'MTB', 'special' => 'Posebne',
        'routes' => 'Poti', 'explore' => 'Razišči', 'show_map' => 'Prikaži na zemljevidu',
        'distance' => 'Razdalja', 'ascent' => 'Vzpon', 'duration' => 'Trajanje', 'difficulty' => 'Težavnost',
        'no_routes' => 'Ni objavljenih poti.', 'no_track' => 'Sled na zemljevidu ni na voljo',
        'loading' => 'Nalaganje poti…', 'loaded' => 'prikazanih poti', 'map_error' => 'Nobene GPX-sledi ni bilo mogoče naložiti.',
    ],
    default => [
        'title' => 'Mappa itinerari', 'subtitle' => 'Esplora tutti gli itinerari pubblicati sulla mappa',
        'intro' => 'Seleziona un percorso sulla mappa oppure usa i filtri per trovare l’esperienza più adatta.',
        'all' => 'Tutti', 'walking' => 'A piedi', 'mtb' => 'MTB', 'special' => 'Speciali',
        'routes' => 'Itinerari', 'explore' => 'Esplora', 'show_map' => 'Mostra sulla mappa',
        'distance' => 'Distanza', 'ascent' => 'Dislivello', 'duration' => 'Durata', 'difficulty' => 'Difficoltà',
        'no_routes' => 'Nessun itinerario pubblicato.', 'no_track' => 'Traccia non disponibile sulla mappa',
        'loading' => 'Caricamento itinerari…', 'loaded' => 'itinerari visualizzati', 'map_error' => 'Non è stato possibile caricare alcuna traccia GPX.',
    ],
};

if (!function_exists('itinerary_map_category')) {
    function itinerary_map_category(array $route): string
    {
        if (!empty($route['speciale'])) {
            return 'speciali';
        }
        return (string) ($route['tipo'] ?? '') === 'mtb' ? 'mtb' : 'piedi';
    }
}

if (!function_exists('itinerary_map_value')) {
    function itinerary_map_value(mixed $stored, mixed $fallback, string $suffix = '', int $decimals = 0): string
    {
        if ($stored !== null && trim((string) $stored) !== '') {
            return fmt_it($stored, $suffix, $decimals);
        }
        $fallback = trim((string) $fallback);
        return $fallback !== '' ? $fallback : '-';
    }
}

$routes = $pdo->query(
    'SELECT * FROM percorsi WHERE pubblicato=1 ORDER BY speciale DESC, consigliato DESC, ordine ASC, titolo ASC'
)->fetchAll() ?: [];

$mapRoutes = [];
foreach ($routes as &$route) {
    $category = itinerary_map_category($route);
    $stats = gpx_stats($route['gpx_file'] ?? null, (string) ($route['tipo'] ?? 'piedi'));
    $gpxPath = trim((string) ($route['gpx_file'] ?? ''));
    $hasGpx = $gpxPath !== '' && gpx_abs_path($gpxPath) !== null;
    $route['_map_category'] = $category;
    $route['_map_has_gpx'] = $hasGpx;
    $route['_map_detail_url'] = '/percorso?slug=' . rawurlencode((string) $route['slug']);
    $route['_map_distance'] = itinerary_map_value($route['distanza_km'] ?? null, $stats['length_label'] ?? '-', ' km', 2);
    $route['_map_ascent'] = itinerary_map_value($route['dislivello_m'] ?? null, $stats['ascent_label'] ?? '-', ' m');
    $route['_map_duration'] = trim((string) ($route['tempo'] ?: ($route['durata'] ?: ($stats['duration_label'] ?? '-'))));
    $route['_map_difficulty'] = trim((string) ($route['difficolta'] ?: ($stats['difficulty'] ?? '-')));

    if ($hasGpx) {
        $mapRoutes[] = [
            'id' => 'itinerary-' . (int) $route['id'],
            'title' => (string) $route['titolo'],
            'category' => $category,
            'categoryLabel' => $labels[$category === 'piedi' ? 'walking' : ($category === 'mtb' ? 'mtb' : 'special')],
            'gpxUrl' => '/gpx/' . rawurlencode(basename(str_replace('\\', '/', $gpxPath))),
            'detailUrl' => $route['_map_detail_url'],
            'distance' => $route['_map_distance'],
            'ascent' => $route['_map_ascent'],
            'duration' => $route['_map_duration'],
            'difficulty' => $route['_map_difficulty'],
            'locality' => trim((string) ($route['localita'] ?: $route['sottotitolo'])),
        ];
    }
}
unset($route);

$cssVersion = (int) (filemtime(LAUCO_ROOT . '/assets/css/mappa-itinerari.css') ?: 1);
$jsVersion = (int) (filemtime(LAUCO_ROOT . '/assets/js/mappa-itinerari.js') ?: 1);
?>
<!DOCTYPE html>
<html lang="<?= e($locale) ?>">
<head>
    <?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet-gesture-handling/dist/leaflet-gesture-handling.min.css">
    <link rel="stylesheet" href="/assets/css/mappa-itinerari.css?v=<?= $cssVersion ?>">
</head>
<body>
<div id="myloader"><span class="loader"><div class="inner-loader"></div></span></div>
<div id="main-wrap" class="full-width">
    <?php require LAUCO_VIEW_PATH . '/partials/menu.php'; ?>
    <div id="page-content" class="header-static footer-fixed">
        <main class="itinerary-map-page">
            <section class="itinerary-map-stage">
                <div id="itinerary-map" aria-label="<?= e($labels['title']) ?>"></div>
                <div class="itinerary-map-toolbar">
                    <h1><?= e($labels['title']) ?></h1>
                    <div class="itinerary-map-filters" role="group" aria-label="<?= e($labels['title']) ?>">
                        <button type="button" class="is-active" data-filter="all" aria-pressed="true"><?= e($labels['all']) ?></button>
                        <button type="button" data-filter="piedi" aria-pressed="false"><?= e($labels['walking']) ?></button>
                        <button type="button" data-filter="mtb" aria-pressed="false"><?= e($labels['mtb']) ?></button>
                        <button type="button" data-filter="speciali" aria-pressed="false"><?= e($labels['special']) ?></button>
                    </div>
                </div>
                <div id="itinerary-map-status" class="itinerary-map-status" role="status"><?= e($labels['loading']) ?></div>
            </section>

            <section class="itinerary-map-list-section">
                <div class="container">
                    <div class="text-center itinerary-list-title">
                        <h2 class="margin-bottom-null title line center"><?= e($labels['routes']) ?></h2>
                    </div>
                    <div id="itinerary-map-list" class="itinerary-map-grid">
                        <?php if ($routes === []): ?><p class="itinerary-map-empty"><?= e($labels['no_routes']) ?></p><?php endif; ?>
                        <?php foreach ($routes as $route): ?>
                            <?php $mapId = 'itinerary-' . (int) $route['id']; ?>
                            <article class="itinerary-map-card" data-category="<?= e($route['_map_category']) ?>" data-route-id="<?= e($mapId) ?>">
                                <div class="itinerary-map-card-body">
                                    <span class="itinerary-kind <?= e($route['_map_category']) ?>"><?= e($labels[$route['_map_category'] === 'piedi' ? 'walking' : ($route['_map_category'] === 'mtb' ? 'mtb' : 'special')]) ?></span>
                                    <h3><?= e($route['titolo']) ?></h3>
                                    <?php if (!empty($route['localita']) || !empty($route['sottotitolo'])): ?><p class="itinerary-locality"><?= e($route['localita'] ?: $route['sottotitolo']) ?></p><?php endif; ?>
                                    <dl class="itinerary-map-stats">
                                        <div><dt><?= e($labels['distance']) ?></dt><dd><?= e($route['_map_distance']) ?></dd></div>
                                        <div><dt><?= e($labels['ascent']) ?></dt><dd><?= e($route['_map_ascent']) ?></dd></div>
                                        <div><dt><?= e($labels['duration']) ?></dt><dd><?= e($route['_map_duration']) ?></dd></div>
                                        <div><dt><?= e($labels['difficulty']) ?></dt><dd><?= e($route['_map_difficulty']) ?></dd></div>
                                    </dl>
                                    <div class="itinerary-map-actions">
                                        <?php if ($route['_map_has_gpx']): ?>
                                            <button type="button" class="itinerary-map-focus" data-route-id="<?= e($mapId) ?>"><?= e($labels['show_map']) ?></button>
                                        <?php else: ?>
                                            <span class="itinerary-no-track"><?= e($labels['no_track']) ?></span>
                                        <?php endif; ?>
                                        <a href="<?= e($route['_map_detail_url']) ?>" class="itinerary-explore"><?= e($labels['explore']) ?></a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>
    <?php require LAUCO_VIEW_PATH . '/partials/footerf.php'; ?>
</div>
<?php require LAUCO_VIEW_PATH . '/partials/scripts.php'; ?>
<script>
window.ITINERARY_MAP_ROUTES = <?= json_encode($mapRoutes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.ITINERARY_MAP_LABELS = <?= json_encode(['explore' => $labels['explore'], 'loaded' => $labels['loaded'], 'mapError' => $labels['map_error']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-gesture-handling/dist/leaflet-gesture-handling.min.js"></script>
<script src="https://unpkg.com/@tmcw/togeojson@5.8.1/dist/togeojson.umd.js"></script>
<script src="/assets/js/mappa-itinerari.js?v=<?= $jsVersion ?>"></script>
</body>
</html>
