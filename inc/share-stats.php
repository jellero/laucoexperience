<?php
declare(strict_types=1);

require_once __DIR__ . '/page-stats.php';

/** @return array<string,string> */
function share_stats_channels(): array
{
    return [
        'facebook' => 'Facebook',
        'whatsapp' => 'WhatsApp',
        'email' => 'Email',
        'copy_link' => 'Copia link',
        'native' => 'Altre app',
    ];
}

function share_stats_channel(string $channel): ?string
{
    $channel = strtolower(trim($channel));
    return $channel === 'open' || isset(share_stats_channels()[$channel]) ? $channel : null;
}

function share_stats_available(PDO $pdo): bool
{
    try {
        $pdo->query('SELECT 1 FROM share_action_daily LIMIT 1');
        return true;
    } catch (Throwable) {
        return false;
    }
}

/** @param array<string,mixed> $query */
function share_stats_track(PDO $pdo, string $channel, string $path, array $query, string $language): void
{
    $channel = share_stats_channel($channel) ?? '';
    if ($channel === '' || !share_stats_available($pdo)) {
        return;
    }

    $language = in_array($language, ['it', 'en', 'de', 'sl'], true) ? $language : 'it';
    $stmt = $pdo->prepare(
        'INSERT INTO share_action_daily (action_date, page_key, language, channel, actions) '
        . 'VALUES (CURRENT_DATE, :page_key, :language, :channel, 1) '
        . 'ON DUPLICATE KEY UPDATE actions = actions + 1'
    );
    $stmt->execute([
        'page_key' => page_stats_key($path, $query),
        'language' => $language,
        'channel' => $channel,
    ]);
}

/** @return array{available:bool,today:int,last30:int,total:int,opens_today:int,opens_last30:int,opens_total:int} */
function share_stats_summary(PDO $pdo): array
{
    $empty = [
        'available' => false,
        'today' => 0,
        'last30' => 0,
        'total' => 0,
        'opens_today' => 0,
        'opens_last30' => 0,
        'opens_total' => 0,
    ];
    if (!share_stats_available($pdo)) {
        return $empty;
    }

    $row = $pdo->query(
        'SELECT '
        . "COALESCE(SUM(CASE WHEN channel <> 'open' AND action_date = CURRENT_DATE THEN actions ELSE 0 END), 0) AS today, "
        . "COALESCE(SUM(CASE WHEN channel <> 'open' AND action_date >= DATE_SUB(CURRENT_DATE, INTERVAL 29 DAY) THEN actions ELSE 0 END), 0) AS last30, "
        . "COALESCE(SUM(CASE WHEN channel <> 'open' THEN actions ELSE 0 END), 0) AS total, "
        . "COALESCE(SUM(CASE WHEN channel = 'open' AND action_date = CURRENT_DATE THEN actions ELSE 0 END), 0) AS opens_today, "
        . "COALESCE(SUM(CASE WHEN channel = 'open' AND action_date >= DATE_SUB(CURRENT_DATE, INTERVAL 29 DAY) THEN actions ELSE 0 END), 0) AS opens_last30, "
        . "COALESCE(SUM(CASE WHEN channel = 'open' THEN actions ELSE 0 END), 0) AS opens_total "
        . 'FROM share_action_daily'
    )->fetch() ?: [];

    return [
        'available' => true,
        'today' => (int) ($row['today'] ?? 0),
        'last30' => (int) ($row['last30'] ?? 0),
        'total' => (int) ($row['total'] ?? 0),
        'opens_today' => (int) ($row['opens_today'] ?? 0),
        'opens_last30' => (int) ($row['opens_last30'] ?? 0),
        'opens_total' => (int) ($row['opens_total'] ?? 0),
    ];
}

/** @return list<array{action_date:string,actions:int}> */
function share_stats_daily(PDO $pdo, int $days = 30): array
{
    if (!share_stats_available($pdo)) {
        return [];
    }

    $days = max(1, min(3650, $days));
    $offset = $days - 1;
    $rows = $pdo->query(
        "SELECT action_date, SUM(actions) AS actions FROM share_action_daily WHERE channel <> 'open' "
        . 'AND action_date >= DATE_SUB(CURRENT_DATE, INTERVAL ' . $offset . ' DAY) '
        . 'GROUP BY action_date ORDER BY action_date ASC'
    )->fetchAll() ?: [];

    return array_map(static fn (array $row): array => [
        'action_date' => (string) ($row['action_date'] ?? ''),
        'actions' => (int) ($row['actions'] ?? 0),
    ], $rows);
}

/** @return list<array{channel:string,period_actions:int,total_actions:int}> */
function share_stats_by_channel(PDO $pdo, int $days = 30): array
{
    if (!share_stats_available($pdo)) {
        return [];
    }

    $days = max(1, min(3650, $days));
    $offset = $days - 1;
    $rows = $pdo->query(
        'SELECT channel, '
        . 'SUM(CASE WHEN action_date >= DATE_SUB(CURRENT_DATE, INTERVAL ' . $offset . ' DAY) THEN actions ELSE 0 END) AS period_actions, '
        . "SUM(actions) AS total_actions FROM share_action_daily WHERE channel <> 'open' "
        . 'GROUP BY channel ORDER BY period_actions DESC, total_actions DESC, channel ASC'
    )->fetchAll() ?: [];

    return array_map(static fn (array $row): array => [
        'channel' => (string) ($row['channel'] ?? ''),
        'period_actions' => (int) ($row['period_actions'] ?? 0),
        'total_actions' => (int) ($row['total_actions'] ?? 0),
    ], $rows);
}

/** @return list<array{page_key:string,period_actions:int,total_actions:int}> */
function share_stats_top_pages(PDO $pdo, int $days = 30, int $limit = 30): array
{
    if (!share_stats_available($pdo)) {
        return [];
    }

    $days = max(1, min(3650, $days));
    $offset = $days - 1;
    $limit = max(1, min(100, $limit));
    $rows = $pdo->query(
        'SELECT page_key, '
        . 'SUM(CASE WHEN action_date >= DATE_SUB(CURRENT_DATE, INTERVAL ' . $offset . ' DAY) THEN actions ELSE 0 END) AS period_actions, '
        . "SUM(actions) AS total_actions FROM share_action_daily WHERE channel <> 'open' "
        . 'GROUP BY page_key ORDER BY period_actions DESC, total_actions DESC, page_key ASC '
        . 'LIMIT ' . $limit
    )->fetchAll() ?: [];

    return array_map(static fn (array $row): array => [
        'page_key' => (string) ($row['page_key'] ?? ''),
        'period_actions' => (int) ($row['period_actions'] ?? 0),
        'total_actions' => (int) ($row['total_actions'] ?? 0),
    ], $rows);
}
