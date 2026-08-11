<?php
declare(strict_types=1);

require_once __DIR__ . '/qr-stats.php';

function download_stats_open_pdo(): ?PDO
{
    return qr_stats_open_pdo();
}

function download_stats_available(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM download_daily LIMIT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function download_log_available(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM download_log LIMIT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function download_stats_type(string $type): ?string
{
    return in_array($type, ['gpx', 'map_pdf'], true) ? $type : null;
}

function download_track(PDO $pdo, string $type, string $resourceKey, string $userAgent = ''): void
{
    $type = download_stats_type($type) ?? '';
    $resourceKey = trim($resourceKey);
    if ($type === '' || $resourceKey === '' || !download_stats_available($pdo)) {
        return;
    }

    $resourceKey = function_exists('mb_substr') ? mb_substr($resourceKey, 0, 255) : substr($resourceKey, 0, 255);
    $stmt = $pdo->prepare(
        'INSERT INTO download_daily (download_date, download_type, resource_key, downloads) '
        . 'VALUES (CURRENT_DATE, :download_type, :resource_key, 1) '
        . 'ON DUPLICATE KEY UPDATE downloads = downloads + 1'
    );
    $stmt->execute([
        'download_type' => $type,
        'resource_key' => $resourceKey,
    ]);

    if (!download_log_available($pdo)) {
        return;
    }

    $userAgent = trim($userAgent);
    $userAgent = function_exists('mb_substr') ? mb_substr($userAgent, 0, 512) : substr($userAgent, 0, 512);
    $detail = $pdo->prepare(
        'INSERT INTO download_log (download_type, resource_key, downloaded_at, user_agent, device_type) '
        . 'VALUES (:download_type, :resource_key, CURRENT_TIMESTAMP, :user_agent, :device_type)'
    );
    $detail->execute([
        'download_type' => $type,
        'resource_key' => $resourceKey,
        'user_agent' => $userAgent,
        'device_type' => qr_device_type($userAgent),
    ]);

    $pdo->exec('DELETE FROM download_log WHERE downloaded_at < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 90 DAY)');
}

/** @return array{available:bool,today:int,last30:int,total:int} */
function download_stats_summary(PDO $pdo, string $type): array
{
    $type = download_stats_type($type) ?? '';
    if ($type === '' || !download_stats_available($pdo)) {
        return ['available' => false, 'today' => 0, 'last30' => 0, 'total' => 0];
    }

    $stmt = $pdo->prepare(
        'SELECT '
        . 'COALESCE(SUM(CASE WHEN download_date = CURRENT_DATE THEN downloads ELSE 0 END), 0) AS today, '
        . 'COALESCE(SUM(CASE WHEN download_date >= DATE_SUB(CURRENT_DATE, INTERVAL 29 DAY) THEN downloads ELSE 0 END), 0) AS last30, '
        . 'COALESCE(SUM(downloads), 0) AS total '
        . 'FROM download_daily WHERE download_type = :download_type'
    );
    $stmt->execute(['download_type' => $type]);
    $row = $stmt->fetch() ?: [];

    return [
        'available' => true,
        'today' => (int) ($row['today'] ?? 0),
        'last30' => (int) ($row['last30'] ?? 0),
        'total' => (int) ($row['total'] ?? 0),
    ];
}

/** @return list<array{download_date:string,downloads:int}> */
function download_stats_daily(PDO $pdo, string $type, int $days = 30): array
{
    $type = download_stats_type($type) ?? '';
    if ($type === '' || !download_stats_available($pdo)) {
        return [];
    }

    $days = max(1, min(3650, $days));
    $offset = $days - 1;
    $stmt = $pdo->prepare(
        'SELECT download_date, SUM(downloads) AS downloads FROM download_daily '
        . 'WHERE download_type = :download_type '
        . 'AND download_date >= DATE_SUB(CURRENT_DATE, INTERVAL ' . $offset . ' DAY) '
        . 'GROUP BY download_date ORDER BY download_date ASC'
    );
    $stmt->execute(['download_type' => $type]);
    $rows = $stmt->fetchAll() ?: [];

    return array_map(static fn (array $row): array => [
        'download_date' => (string) ($row['download_date'] ?? ''),
        'downloads' => (int) ($row['downloads'] ?? 0),
    ], $rows);
}

/** @return list<array{resource_key:string,period_downloads:int,total_downloads:int}> */
function download_stats_top(PDO $pdo, string $type, int $days = 30, int $limit = 20): array
{
    $type = download_stats_type($type) ?? '';
    if ($type === '' || !download_stats_available($pdo)) {
        return [];
    }

    $days = max(1, min(3650, $days));
    $offset = $days - 1;
    $limit = max(1, min(100, $limit));
    $stmt = $pdo->prepare(
        'SELECT resource_key, '
        . 'SUM(CASE WHEN download_date >= DATE_SUB(CURRENT_DATE, INTERVAL ' . $offset . ' DAY) THEN downloads ELSE 0 END) AS period_downloads, '
        . 'SUM(downloads) AS total_downloads '
        . 'FROM download_daily WHERE download_type = :download_type '
        . 'GROUP BY resource_key ORDER BY period_downloads DESC, total_downloads DESC, resource_key ASC '
        . 'LIMIT ' . $limit
    );
    $stmt->execute(['download_type' => $type]);
    $rows = $stmt->fetchAll() ?: [];

    return array_map(static fn (array $row): array => [
        'resource_key' => (string) ($row['resource_key'] ?? ''),
        'period_downloads' => (int) ($row['period_downloads'] ?? 0),
        'total_downloads' => (int) ($row['total_downloads'] ?? 0),
    ], $rows);
}

/** @return list<array{id:int,download_type:string,resource_key:string,downloaded_at:string,user_agent:string,device_type:string}> */
function download_stats_recent(PDO $pdo, int $limit = 100): array
{
    if (!download_log_available($pdo)) {
        return [];
    }

    $limit = max(1, min(500, $limit));
    $rows = $pdo->query(
        'SELECT id, download_type, resource_key, downloaded_at, user_agent, device_type '
        . 'FROM download_log ORDER BY downloaded_at DESC, id DESC LIMIT ' . $limit
    )->fetchAll() ?: [];

    return array_map(static fn (array $row): array => [
        'id' => (int) ($row['id'] ?? 0),
        'download_type' => (string) ($row['download_type'] ?? ''),
        'resource_key' => (string) ($row['resource_key'] ?? ''),
        'downloaded_at' => (string) ($row['downloaded_at'] ?? ''),
        'user_agent' => (string) ($row['user_agent'] ?? ''),
        'device_type' => (string) ($row['device_type'] ?? 'unknown'),
    ], $rows);
}
