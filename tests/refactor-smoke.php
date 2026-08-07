<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/inc/env.php';
require_once dirname(__DIR__) . '/inc/translations.php';
require_once dirname(__DIR__) . '/inc/event-import.php';
require_once dirname(__DIR__) . '/inc/content-ai.php';

$failures = [];
$assert = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) { $failures[] = $message; }
};

$assert(array_keys(content_supported_languages()) === ['it', 'en', 'de', 'sl'], 'Elenco lingue inatteso.');

if (function_exists('mb_substr') && function_exists('mb_strlen')) {
    $normalized = content_ai_normalize_payload([
        'title' => str_repeat('x', 300),
        'subtitle' => ' Test ',
        'warnings' => [' attenzione ', ''],
    ], 'de');
    $assert($normalized['language'] === 'de', 'La lingua normalizzata non coincide con quella richiesta.');
    $assert(mb_strlen($normalized['title']) === 255, 'Il titolo AI non viene limitato correttamente.');
    $assert($normalized['subtitle'] === 'Test', 'Il sottotitolo AI non viene ripulito.');
    $assert($normalized['warnings'] === ['attenzione'], 'Gli avvisi AI non vengono normalizzati.');
} else {
    echo "mbstring non disponibile nel runtime locale: test normalizzazione AI saltato.\n";
}
$assert(event_import_https_url('https://example.test/image.jpg') !== null, 'URL HTTPS valido rifiutato.');
$assert(event_import_https_url('http://example.test/image.jpg') === null, 'URL immagine non HTTPS accettato.');
$sources = event_import_sources();
$pattern = (string) $sources['turismofvg_carnia']['link_pattern'];
$assert((bool) preg_match($pattern, '/eventi/lauco-celtic-fest'), 'Percorso eventi italiano non riconosciuto.');
$assert((bool) preg_match($pattern, '/events/parole-in-vetta'), 'Percorso eventi inglese non riconosciuto.');

if (class_exists('DOMDocument')) {
    $html = <<<'HTML'
    <!doctype html><html><head><script type="application/ld+json">
    {"@context":"https://schema.org","@type":"Event","name":"Festa a Lauco","description":"Evento di prova","startDate":"2026-08-20T18:00:00+02:00","location":{"@type":"Place","name":"Piazza","address":{"addressLocality":"Lauco"}},"organizer":{"@type":"Organization","name":"Comune"}}
    </script></head><body></body></html>
    HTML;
    $events = event_import_page_events($html, 'https://example.test/eventi/festa');
    $assert(count($events) === 1, 'Il parser JSON-LD non ha trovato l’evento.');
    $assert(($events[0]['title'] ?? '') === 'Festa a Lauco', 'Titolo evento non normalizzato.');
    $assert(event_import_locality_allowed($events[0], ['Lauco']), 'Filtro località non funzionante.');
    $assert(event_import_date('2026-08-20T18:00:00+02:00') === '2026-08-20', 'Normalizzazione data non funzionante.');
} else {
    echo "DOM non disponibile nel runtime locale: test JSON-LD saltato.\n";
}

if ($failures) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}

echo "Smoke test superati.\n";
