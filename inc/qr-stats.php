<?php
declare(strict_types=1);

function qr_root_path(): string
{
    return defined('LAUCO_ROOT') ? LAUCO_ROOT : dirname(__DIR__);
}

/** @return array<string,array{label:string,area:string,destination:string}> */
function qr_registry(): array
{
    $registry = require qr_root_path() . '/config/qr_codes.php';
    return is_array($registry) ? $registry : [];
}

/** @return list<string> */
function qr_stats_active_codes(): array
{
    return array_values(array_map('strval', array_keys(qr_registry())));
}

/** @return array{sql:string,params:array<string,string>} */
function qr_stats_active_filter(string $prefix = 'qr'): array
{
    $codes = qr_stats_active_codes();
    if ($codes === []) {
        return ['sql' => '1 = 0', 'params' => []];
    }

    $placeholders = [];
    $params = [];
    foreach ($codes as $index => $code) {
        $name = ':' . $prefix . $index;
        $placeholders[] = $name;
        $params[$name] = $code;
    }

    return [
        'sql' => 'qr_code IN (' . implode(', ', $placeholders) . ')',
        'params' => $params,
    ];
}

/** @return array{label:string,area:string,destination:string}|null */
function qr_definition(string $code): ?array
{
    $registry = qr_registry();
    $definition = $registry[$code] ?? null;
    if (!is_array($definition)) {
        return null;
    }

    $label = trim((string) ($definition['label'] ?? ''));
    $area = trim((string) ($definition['area'] ?? ''));
    $destination = trim((string) ($definition['destination'] ?? ''));
    if ($label === '' || $destination === '') {
        return null;
    }

    return ['label' => $label, 'area' => $area, 'destination' => $destination];
}

function qr_stats_open_pdo(): ?PDO
{
    require_once qr_root_path() . '/inc/env.php';

    try {
        $host = lauco_env_required('DB_HOST');
        $port = lauco_env_int('DB_PORT', 3306);
        $name = lauco_env_required('DB_NAME');
        $user = lauco_env_required('DB_USER');
        $pass = (string) lauco_env('DB_PASS', '');
        $charset = (string) lauco_env('DB_CHARSET', 'utf8mb4');
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $name, $charset);

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $e) {
        error_log('QR analytics DB unavailable: ' . $e->getMessage());
        return null;
    }
}

function qr_device_type(string $userAgent): string
{
    $ua = strtolower(trim($userAgent));
    if ($ua === '') {
        return 'unknown';
    }
    if (preg_match('/ipad|tablet|kindle|silk/', $ua)) {
        return 'tablet';
    }
    if (preg_match('/mobile|iphone|ipod|android.*mobile|windows phone/', $ua)) {
        return 'mobile';
    }
    if (str_contains($ua, 'android')) {
        return 'tablet';
    }
    return 'desktop';
}

function qr_scan_log_retention_days(): int
{
    return 90;
}

function qr_scan_log_available(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM qr_scan_log LIMIT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function qr_track_scan(PDO $pdo, string $code, string $userAgent = ''): void
{
    if (qr_definition($code) === null) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO qr_scan_daily (scan_date, qr_code, scans) VALUES (CURRENT_DATE, :qr_code, 1) '
        . 'ON DUPLICATE KEY UPDATE scans = scans + 1'
    );
    $stmt->execute(['qr_code' => $code]);

    if (!qr_scan_log_available($pdo)) {
        return;
    }

    $userAgent = trim($userAgent);
    $userAgent = function_exists('mb_substr') ? mb_substr($userAgent, 0, 512) : substr($userAgent, 0, 512);

    $detail = $pdo->prepare(
        'INSERT INTO qr_scan_log (qr_code, scanned_at, user_agent, device_type) '
        . 'VALUES (:qr_code, CURRENT_TIMESTAMP, :user_agent, :device_type)'
    );
    $detail->execute([
        'qr_code' => $code,
        'user_agent' => $userAgent,
        'device_type' => qr_device_type($userAgent),
    ]);

    $pdo->exec(
        'DELETE FROM qr_scan_log WHERE scanned_at < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL '
        . qr_scan_log_retention_days() . ' DAY)'
    );
}

function qr_stats_available(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM qr_scan_daily LIMIT 1');
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

/** @return array{available:bool,today:int,last30:int,total:int} */
function qr_stats_summary(PDO $pdo): array
{
    if (!qr_stats_available($pdo)) {
        return ['available' => false, 'today' => 0, 'last30' => 0, 'total' => 0];
    }

    $filter = qr_stats_active_filter('summary_qr');
    $stmt = $pdo->prepare(
        'SELECT '
        . 'COALESCE(SUM(CASE WHEN scan_date = CURRENT_DATE THEN scans ELSE 0 END), 0) AS today, '
        . 'COALESCE(SUM(CASE WHEN scan_date >= DATE_SUB(CURRENT_DATE, INTERVAL 29 DAY) THEN scans ELSE 0 END), 0) AS last30, '
        . 'COALESCE(SUM(scans), 0) AS total '
        . 'FROM qr_scan_daily WHERE ' . $filter['sql']
    );
    $stmt->execute($filter['params']);
    $row = $stmt->fetch() ?: [];

    return [
        'available' => true,
        'today' => (int) ($row['today'] ?? 0),
        'last30' => (int) ($row['last30'] ?? 0),
        'total' => (int) ($row['total'] ?? 0),
    ];
}

/** @return list<array{qr_code:string,period_scans:int,total_scans:int}> */
function qr_stats_top(PDO $pdo, int $days = 30): array
{
    if (!qr_stats_available($pdo)) {
        return [];
    }

    $days = max(1, min(3650, $days));
    $offset = $days - 1;
    $filter = qr_stats_active_filter('top_qr');
    $sql = 'SELECT qr_code, '
        . 'SUM(CASE WHEN scan_date >= DATE_SUB(CURRENT_DATE, INTERVAL ' . $offset . ' DAY) THEN scans ELSE 0 END) AS period_scans, '
        . 'SUM(scans) AS total_scans '
        . 'FROM qr_scan_daily WHERE ' . $filter['sql'] . ' '
        . 'GROUP BY qr_code ORDER BY period_scans DESC, total_scans DESC, qr_code ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($filter['params']);
    $rows = $stmt->fetchAll();
    return array_map(static fn (array $row): array => [
        'qr_code' => (string) $row['qr_code'],
        'period_scans' => (int) $row['period_scans'],
        'total_scans' => (int) $row['total_scans'],
    ], $rows ?: []);
}

/** @return list<array{scan_date:string,scans:int}> */
function qr_stats_daily(PDO $pdo, int $days = 30): array
{
    if (!qr_stats_available($pdo)) {
        return [];
    }

    $days = max(1, min(3650, $days));
    $offset = $days - 1;
    $filter = qr_stats_active_filter('daily_qr');
    $sql = 'SELECT scan_date, SUM(scans) AS scans FROM qr_scan_daily '
        . 'WHERE scan_date >= DATE_SUB(CURRENT_DATE, INTERVAL ' . $offset . ' DAY) '
        . 'AND ' . $filter['sql'] . ' '
        . 'GROUP BY scan_date ORDER BY scan_date ASC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($filter['params']);
    $rows = $stmt->fetchAll();

    return array_map(static fn (array $row): array => [
        'scan_date' => (string) $row['scan_date'],
        'scans' => (int) $row['scans'],
    ], $rows ?: []);
}

/** @return list<array{id:int,qr_code:string,scanned_at:string,user_agent:string,device_type:string}> */
function qr_stats_recent(PDO $pdo, int $limit = 100): array
{
    if (!qr_scan_log_available($pdo)) {
        return [];
    }

    $limit = max(1, min(500, $limit));
    $filter = qr_stats_active_filter('recent_qr');
    $sql = 'SELECT id, qr_code, scanned_at, user_agent, device_type '
        . 'FROM qr_scan_log WHERE ' . $filter['sql'] . ' '
        . 'ORDER BY scanned_at DESC, id DESC LIMIT ' . $limit;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($filter['params']);
    $rows = $stmt->fetchAll() ?: [];

    return array_map(static fn (array $row): array => [
        'id' => (int) ($row['id'] ?? 0),
        'qr_code' => (string) ($row['qr_code'] ?? ''),
        'scanned_at' => (string) ($row['scanned_at'] ?? ''),
        'user_agent' => (string) ($row['user_agent'] ?? ''),
        'device_type' => (string) ($row['device_type'] ?? 'unknown'),
    ], $rows);
}
