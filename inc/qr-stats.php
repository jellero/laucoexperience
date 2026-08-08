<?php
declare(strict_types=1);

/** @return array<string,array{label:string,area:string,destination:string}> */
function qr_registry(): array
{
    $registry = require LAUCO_ROOT . '/config/qr_codes.php';
    return is_array($registry) ? $registry : [];
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
    require_once LAUCO_ROOT . '/inc/env.php';

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

function qr_track_scan(PDO $pdo, string $code): void
{
    if (qr_definition($code) === null) {
        return;
    }

    $stmt = $pdo->prepare(
        'INSERT INTO qr_scan_daily (scan_date, qr_code, scans) VALUES (CURRENT_DATE, :qr_code, 1) '
        . 'ON DUPLICATE KEY UPDATE scans = scans + 1'
    );
    $stmt->execute(['qr_code' => $code]);
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

    $row = $pdo->query(
        'SELECT '
        . 'COALESCE(SUM(CASE WHEN scan_date = CURRENT_DATE THEN scans ELSE 0 END), 0) AS today, '
        . 'COALESCE(SUM(CASE WHEN scan_date >= DATE_SUB(CURRENT_DATE, INTERVAL 29 DAY) THEN scans ELSE 0 END), 0) AS last30, '
        . 'COALESCE(SUM(scans), 0) AS total '
        . 'FROM qr_scan_daily'
    )->fetch();

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
    $sql = 'SELECT qr_code, '
        . 'SUM(CASE WHEN scan_date >= DATE_SUB(CURRENT_DATE, INTERVAL ' . $offset . ' DAY) THEN scans ELSE 0 END) AS period_scans, '
        . 'SUM(scans) AS total_scans '
        . 'FROM qr_scan_daily GROUP BY qr_code ORDER BY period_scans DESC, total_scans DESC, qr_code ASC';

    $rows = $pdo->query($sql)->fetchAll();
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
    $sql = 'SELECT scan_date, SUM(scans) AS scans FROM qr_scan_daily '
        . 'WHERE scan_date >= DATE_SUB(CURRENT_DATE, INTERVAL ' . $offset . ' DAY) '
        . 'GROUP BY scan_date ORDER BY scan_date ASC';
    $rows = $pdo->query($sql)->fetchAll();

    return array_map(static fn (array $row): array => [
        'scan_date' => (string) $row['scan_date'],
        'scans' => (int) $row['scans'],
    ], $rows ?: []);
}
