<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/../inc/admin-push.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: private, no-store, max-age=0');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['success' => false, 'error' => 'Metodo non consentito.'], JSON_UNESCAPED_UNICODE);
    exit;
}

verify_csrf();

$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Richiesta non valida.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = strtolower(trim((string) ($payload['action'] ?? '')));

try {
    if ($action === 'subscribe') {
        $subscription = is_array($payload['subscription'] ?? null) ? $payload['subscription'] : [];
        $subscription['platform'] = (string) ($payload['platform'] ?? 'web');
        admin_push_store_subscription(
            $pdo,
            admin_id(),
            $subscription,
            (string) ($_SERVER['HTTP_USER_AGENT'] ?? '')
        );
        echo json_encode(['success' => true, 'enabled' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'unsubscribe') {
        $endpoint = trim((string) ($payload['endpoint'] ?? ''));
        if ($endpoint !== '') {
            admin_push_remove_subscription($pdo, admin_id(), $endpoint);
        }
        echo json_encode(['success' => true, 'enabled' => false], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Azione non valida.'], JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $exception) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    error_log('[Lauco Push] ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Notifiche temporaneamente non disponibili.'], JSON_UNESCAPED_UNICODE);
}
