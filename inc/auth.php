<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    session_name('lauco_admin');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

if (!function_exists('current_admin')) {
    /** @return array<string,mixed>|null */
    function current_admin(): ?array
    {
        $admin = $_SESSION['admin_user'] ?? null;
        if (is_array($admin) && !empty($admin['id']) && !empty($admin['email'])) {
            return $admin;
        }
        if (!empty($_SESSION['admin_id']) && !empty($_SESSION['admin_email'])) {
            return [
                'id' => (int) $_SESSION['admin_id'],
                'nome' => (string) ($_SESSION['admin_nome'] ?? ''),
                'email' => (string) $_SESSION['admin_email'],
            ];
        }
        return null;
    }
}

if (!function_exists('admin_id')) {
    function admin_id(): int
    {
        return (int) (current_admin()['id'] ?? 0);
    }
}

if (!function_exists('login_admin')) {
    function login_admin(string $email, string $password): bool
    {
        global $pdo;
        $now = time();
        $attempts = array_values(array_filter(
            is_array($_SESSION['login_attempts'] ?? null) ? $_SESSION['login_attempts'] : [],
            static fn ($timestamp): bool => is_int($timestamp) && $timestamp > $now - 600
        ));
        if (count($attempts) >= 5) {
            return false;
        }

        $stmt = $pdo->prepare('SELECT id, nome, email, password_hash FROM utenti WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $stmt->execute(['email' => trim($email)]);
        $user = $stmt->fetch();
        if (!is_array($user) || !password_verify($password, (string) $user['password_hash'])) {
            $attempts[] = $now;
            $_SESSION['login_attempts'] = $attempts;
            return false;
        }

        session_regenerate_id(true);
        unset($_SESSION['login_attempts']);
        $_SESSION['admin_id'] = (int) $user['id'];
        $_SESSION['admin_nome'] = (string) $user['nome'];
        $_SESSION['admin_email'] = (string) $user['email'];
        $_SESSION['admin_user'] = [
            'id' => (int) $user['id'],
            'nome' => (string) $user['nome'],
            'email' => (string) $user['email'],
        ];
        $_SESSION['admin_last_activity'] = time();
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return true;
    }
}

if (!function_exists('logout_admin')) {
    function logout_admin(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}

if (!function_exists('require_admin')) {
    function require_admin(): void
    {
        $admin = current_admin();
        $maxIdle = max(900, lauco_env_int('ADMIN_IDLE_TIMEOUT_SECONDS', 7200));
        $lastActivity = (int) ($_SESSION['admin_last_activity'] ?? time());
        if (!$admin || $lastActivity < time() - $maxIdle) {
            logout_admin();
            header('Location: ../login.php');
            exit;
        }
        $_SESSION['admin_last_activity'] = time();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf')) {
    function verify_csrf(?string $token = null): void
    {
        $token ??= (string) ($_POST['_csrf_token'] ?? $_GET['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if ($token === '' || !hash_equals(csrf_token(), $token)) {
            http_response_code(419);
            exit('Sessione scaduta o richiesta non valida.');
        }
    }
}
