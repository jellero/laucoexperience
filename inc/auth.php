<?php
declare(strict_types=1);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/admin-permissions.php';

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
            $admin['ruolo'] = admin_normalize_role((string) ($admin['ruolo'] ?? $_SESSION['admin_ruolo'] ?? 'admin'));
            return $admin;
        }
        if (!empty($_SESSION['admin_id']) && !empty($_SESSION['admin_email'])) {
            return [
                'id' => (int) $_SESSION['admin_id'],
                'nome' => (string) ($_SESSION['admin_nome'] ?? ''),
                'email' => (string) $_SESSION['admin_email'],
                'ruolo' => admin_normalize_role((string) ($_SESSION['admin_ruolo'] ?? 'admin')),
            ];
        }
        return null;
    }
}

if (!function_exists('admin_role')) {
    function admin_role(): string
    {
        return admin_normalize_role((string) (current_admin()['ruolo'] ?? 'admin'));
    }
}

if (!function_exists('admin_can')) {
    function admin_can(string $capability): bool
    {
        return current_admin() !== null && admin_role_can(admin_role(), $capability);
    }
}

if (!function_exists('admin_access_denied')) {
    function admin_access_denied(string $message = 'Non hai i permessi per accedere a questa sezione.'): never
    {
        http_response_code(403);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>Accesso non consentito</title><style>body{margin:0;background:#f4f4f4;color:#222;font-family:Arial,sans-serif}'
            . '.box{max-width:620px;margin:12vh auto;background:#fff;padding:32px;box-shadow:0 12px 36px rgba(0,0,0,.1)}'
            . 'a{display:inline-block;margin-top:14px;background:#222;color:#fff;padding:11px 14px;text-decoration:none}</style></head><body>'
            . '<main class="box"><h1>Accesso non consentito</h1><p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<a href="index.php">Torna alla dashboard</a></main></body></html>';
        exit;
    }
}

if (!function_exists('require_admin_permission')) {
    function require_admin_permission(string $capability): void
    {
        if (!admin_can($capability)) {
            admin_access_denied();
        }
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

        try {
            $stmt = $pdo->prepare('SELECT id, nome, email, password_hash, ruolo FROM utenti WHERE LOWER(email) = LOWER(:email) LIMIT 1');
            $stmt->execute(['email' => trim($email)]);
        } catch (Throwable) {
            // Compatibilità durante il breve intervallo tra deploy del codice e migrazione DB.
            $stmt = $pdo->prepare('SELECT id, nome, email, password_hash FROM utenti WHERE LOWER(email) = LOWER(:email) LIMIT 1');
            $stmt->execute(['email' => trim($email)]);
        }
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
        $_SESSION['admin_ruolo'] = admin_normalize_role((string) ($user['ruolo'] ?? 'admin'));
        $_SESSION['admin_user'] = [
            'id' => (int) $user['id'],
            'nome' => (string) $user['nome'],
            'email' => (string) $user['email'],
            'ruolo' => $_SESSION['admin_ruolo'],
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
        global $pdo;
        $admin = current_admin();
        $maxIdle = max(900, lauco_env_int('ADMIN_IDLE_TIMEOUT_SECONDS', 7200));
        $lastActivity = (int) ($_SESSION['admin_last_activity'] ?? time());
        if (!$admin || $lastActivity < time() - $maxIdle) {
            logout_admin();
            header('Location: ../login.php');
            exit;
        }

        try {
            $statement = $pdo->prepare('SELECT ruolo FROM utenti WHERE id = :id LIMIT 1');
            $statement->execute(['id' => (int) $admin['id']]);
            $databaseRole = $statement->fetchColumn();
            if ($databaseRole === false) {
                logout_admin();
                header('Location: ../login.php');
                exit;
            }
            $role = admin_normalize_role((string) $databaseRole);
            $_SESSION['admin_ruolo'] = $role;
            $_SESSION['admin_user']['ruolo'] = $role;
        } catch (Throwable $exception) {
            $message = strtolower($exception->getMessage());
            if (str_contains($message, 'unknown column') || str_contains($message, 'no such column')) {
                // Prima della migrazione dei ruoli gli account esistenti restano amministratori.
                $_SESSION['admin_ruolo'] = 'admin';
                $_SESSION['admin_user']['ruolo'] = 'admin';
            } else {
                logout_admin();
                header('Location: ../login.php');
                exit;
            }
        }

        $_SESSION['admin_last_activity'] = time();
        $script = (string) ($_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '');
        require_admin_permission(admin_script_capability($script));
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
