<?php
declare(strict_types=1);

require_once __DIR__ . '/qr-stats.php';

function page_stats_open_pdo(): ?PDO
{
    return qr_stats_open_pdo();
}

function page_stats_available(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM page_view_daily LIMIT 1');
        return true;
    } catch (Throwable) {
        return false;
    }
}

/** @param array<string,mixed> $query */
function page_stats_key(string $path, array $query = []): string
{
    $path = '/' . trim(preg_replace('~/+~', '/', $path) ?? '/', '/');
    if ($path === '/index' || $path === '/index.php') {
        $path = '/';
    } elseif ($path !== '/' && str_ends_with(strtolower($path), '.php')) {
        $path = substr($path, 0, -4);
    }

    if (in_array($path, ['/percorso', '/luogo', '/evento'], true)) {
        $slug = strtolower(trim((string) ($query['slug'] ?? '')));
        if ($slug !== '' && preg_match('/^[a-z0-9][a-z0-9-]{0,159}$/', $slug)) {
            return $path . '?slug=' . $slug;
        }
    }

    return function_exists('mb_substr') ? mb_substr($path, 0, 255) : substr($path, 0, 255);
}

function page_stats_should_track(string $method, int $status, string $template, string $userAgent = ''): bool
{
    if (strtoupper($method) !== 'GET' || $status < 200 || $status >= 400) {
        return false;
    }
    if (in_array($template, ['login.php', 'crea-account.php', '400.php'], true)) {
        return false;
    }

    return !preg_match(
        '/(?:bot|crawler|spider|slurp|bingpreview|facebookexternalhit|whatsapp|telegrambot|uptime|monitor|headlesschrome|lighthouse|curl|wget)/i',
        $userAgent
    );
}

/** @param array<string,mixed> $query */
function page_stats_track(PDO $pdo, string $path, array $query, string $language): void
{
    if (!page_stats_available($pdo)) {
        return;
    }

    $language = in_array($language, ['it', 'en', 'de', 'sl'], true) ? $language : 'it';
    $stmt = $pdo->prepare(
        'INSERT INTO page_view_daily (view_date, page_key, language, views) '
        . 'VALUES (CURRENT_DATE, :page_key, :language, 1) '
        . 'ON DUPLICATE KEY UPDATE views = views + 1'
    );
    $stmt->execute([
        'page_key' => page_stats_key($path, $query),
        'language' => $language,
    ]);
}

/** @return array{available:bool,today:int,last30:int,total:int} */
function page_stats_summary(PDO $pdo): array
{
    if (!page_stats_available($pdo)) {
        return ['available' => false, 'today' => 0, 'last30' => 0, 'total' => 0];
    }

    $row = $pdo->query(
        'SELECT '
        . 'COALESCE(SUM(CASE WHEN view_date = CURRENT_DATE THEN views ELSE 0 END), 0) AS today, '
        . 'COALESCE(SUM(CASE WHEN view_date >= DATE_SUB(CURRENT_DATE, INTERVAL 29 DAY) THEN views ELSE 0 END), 0) AS last30, '
        . 'COALESCE(SUM(views), 0) AS total FROM page_view_daily'
    )->fetch() ?: [];

    return [
        'available' => true,
        'today' => (int) ($row['today'] ?? 0),
        'last30' => (int) ($row['last30'] ?? 0),
        'total' => (int) ($row['total'] ?? 0),
    ];
}

/** @return list<array{view_date:string,views:int}> */
function page_stats_daily(PDO $pdo, int $days = 30): array
{
    if (!page_stats_available($pdo)) {
        return [];
    }

    $days = max(1, min(3650, $days));
    $offset = $days - 1;
    $rows = $pdo->query(
        'SELECT view_date, SUM(views) AS views FROM page_view_daily '
        . 'WHERE view_date >= DATE_SUB(CURRENT_DATE, INTERVAL ' . $offset . ' DAY) '
        . 'GROUP BY view_date ORDER BY view_date ASC'
    )->fetchAll() ?: [];

    return array_map(static fn (array $row): array => [
        'view_date' => (string) ($row['view_date'] ?? ''),
        'views' => (int) ($row['views'] ?? 0),
    ], $rows);
}

/** @return list<array{page_key:string,period_views:int,total_views:int}> */
function page_stats_top(PDO $pdo, int $days = 30, int $limit = 30): array
{
    if (!page_stats_available($pdo)) {
        return [];
    }

    $days = max(1, min(3650, $days));
    $offset = $days - 1;
    $limit = max(1, min(100, $limit));
    $rows = $pdo->query(
        'SELECT page_key, '
        . 'SUM(CASE WHEN view_date >= DATE_SUB(CURRENT_DATE, INTERVAL ' . $offset . ' DAY) THEN views ELSE 0 END) AS period_views, '
        . 'SUM(views) AS total_views FROM page_view_daily '
        . 'GROUP BY page_key ORDER BY period_views DESC, total_views DESC, page_key ASC '
        . 'LIMIT ' . $limit
    )->fetchAll() ?: [];

    return array_map(static fn (array $row): array => [
        'page_key' => (string) ($row['page_key'] ?? ''),
        'period_views' => (int) ($row['period_views'] ?? 0),
        'total_views' => (int) ($row['total_views'] ?? 0),
    ], $rows);
}

/** @return list<array{language:string,period_views:int,total_views:int}> */
function page_stats_languages(PDO $pdo, int $days = 30): array
{
    if (!page_stats_available($pdo)) {
        return [];
    }

    $days = max(1, min(3650, $days));
    $offset = $days - 1;
    $rows = $pdo->query(
        'SELECT language, '
        . 'SUM(CASE WHEN view_date >= DATE_SUB(CURRENT_DATE, INTERVAL ' . $offset . ' DAY) THEN views ELSE 0 END) AS period_views, '
        . 'SUM(views) AS total_views FROM page_view_daily '
        . 'GROUP BY language ORDER BY period_views DESC, total_views DESC, language ASC'
    )->fetchAll() ?: [];

    return array_map(static fn (array $row): array => [
        'language' => (string) ($row['language'] ?? 'it'),
        'period_views' => (int) ($row['period_views'] ?? 0),
        'total_views' => (int) ($row['total_views'] ?? 0),
    ], $rows);
}
