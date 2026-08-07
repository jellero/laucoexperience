<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/inc/db.php';
require_once dirname(__DIR__) . '/inc/event-import.php';

$requested = $argv[1] ?? null;
foreach (event_import_sources() as $key => $source) {
    if (empty($source['enabled']) || ($requested !== null && $requested !== $key)) {
        continue;
    }
    try {
        $events = event_import_fetch($key);
        $runId = event_import_stage($pdo, $key, $events, 0);
        echo $key . ': ' . count($events) . ' candidati, run #' . $runId . PHP_EOL;
    } catch (Throwable $e) {
        fwrite(STDERR, $key . ': ' . $e->getMessage() . PHP_EOL);
    }
}
