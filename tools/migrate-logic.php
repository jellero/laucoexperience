<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/inc/db.php';

$pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (migration VARCHAR(190) NOT NULL PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
$files = glob(dirname(__DIR__) . '/migrations/*.sql') ?: [];
sort($files, SORT_STRING);
foreach ($files as $file) {
    $name = basename($file);
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM schema_migrations WHERE migration = :migration');
    $stmt->execute(['migration' => $name]);
    if ((int) $stmt->fetchColumn() > 0) {
        echo "SKIP {$name}\n";
        continue;
    }
    $sql = file_get_contents($file);
    if (!is_string($sql)) {
        throw new RuntimeException('Impossibile leggere ' . $name);
    }
    $pdo->exec($sql);
    $stmt = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:migration)');
    $stmt->execute(['migration' => $name]);
    echo "OK   {$name}\n";
}
