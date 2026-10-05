<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$root = dirname(__DIR__);
require_once $root . '/inc/db.php';
require_once $root . '/inc/admin-push-preferences.php';
require_once $root . '/inc/backoffice-mail.php';

$connection = $GLOBALS['pdo'] ?? null;
if (!$connection instanceof PDO) {
    fwrite(STDERR, "Database non disponibile.\n");
    exit(1);
}

$lock = $connection->query("SELECT GET_LOCK('lauco_admin_push_worker', 0)")->fetchColumn();
if ((int) $lock !== 1) {
    exit(0);
}

try {
    $rows = $connection->query(
        'SELECT id, category FROM admin_push_event_queue ORDER BY id ASC LIMIT 50'
    )->fetchAll() ?: [];

    foreach ($rows as $row) {
        $id = (int) ($row['id'] ?? 0);
        $category = (string) ($row['category'] ?? '');
        if ($id <= 0 || !isset(admin_push_categories()[$category])) {
            if ($id > 0) {
                $connection->prepare('DELETE FROM admin_push_event_queue WHERE id = :id')->execute(['id' => $id]);
            }
            continue;
        }

        admin_push_notify($connection, $category);
        $connection->prepare('DELETE FROM admin_push_event_queue WHERE id = :id')->execute(['id' => $id]);
    }

    try {
        $client = backoffice_mail_client();
        $inbox = backoffice_mail_folder('INBOX', $client);
        $unread = max(0, (int) $inbox->query()->unseen()->count());

        $state = $connection->prepare(
            "SELECT event_value FROM admin_push_event_state WHERE event_key = 'mail_unread' LIMIT 1"
        );
        $state->execute();
        $previous = $state->fetchColumn();

        if ($previous !== false && $unread > (int) $previous) {
            admin_push_notify($connection, 'mail');
        }

        $save = $connection->prepare(
            "INSERT INTO admin_push_event_state (event_key, event_value) VALUES ('mail_unread', :value) "
            . 'ON DUPLICATE KEY UPDATE event_value = VALUES(event_value), updated_at = CURRENT_TIMESTAMP'
        );
        $save->execute(['value' => (string) $unread]);
    } catch (Throwable $exception) {
        error_log('[Lauco Push mail worker] ' . $exception->getMessage());
    }
} finally {
    $connection->query("SELECT RELEASE_LOCK('lauco_admin_push_worker')");
}
