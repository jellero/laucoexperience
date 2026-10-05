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

if (!function_exists('admin_request_is_https')) {
    function admin_request_is_https(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
    }
}

if (!function_exists('admin_remember_cookie_name')) {
    function admin_remember_cookie_name(): string
    {
        return 'lauco_admin_remember';
    }
}

if (!function_exists('admin_remember_days')) {
    function admin_remember_days(): int
    {
        return max(1, min(90, lauco_env_int('ADMIN_REMEMBER_DAYS', 30)));
    }
}

if (!function_exists('admin_remember_token')) {
    function admin_remember_token(): ?string
    {
        $token = strtolower(trim((string) ($_COOKIE[admin_remember_cookie_name()] ?? '')));
        return preg_match('/^[a-f0-9]{64}$/D', $token) === 1 ? $token : null;
    }
}

if (!function_exists('admin_set_remember_cookie')) {
    function admin_set_remember_cookie(string $token, int $expiresAt): void
    {
        setcookie(admin_remember_cookie_name(), $token, [
            'expires' => $expiresAt,
            'path' => '/',
            'secure' => admin_request_is_https(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        $_COOKIE[admin_remember_cookie_name()] = $token;
    }
}

if (!function_exists('admin_clear_remember_cookie')) {
    function admin_clear_remember_cookie(): void
    {
        setcookie(admin_remember_cookie_name(), '', [
            'expires' => time() - 42000,
            'path' => '/',
            'secure' => admin_request_is_https(),
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        unset($_COOKIE[admin_remember_cookie_name()]);
    }
}

if (!function_exists('admin_populate_session')) {
    /** @param array<string,mixed> $user */
    function admin_populate_session(array $user, bool $remembered = false, int $rememberExpiresAt = 0): void
    {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $user['id'];
        $_SESSION['admin_nome'] = (string) ($user['nome'] ?? '');
        $_SESSION['admin_email'] = (string) $user['email'];
        $_SESSION['admin_ruolo'] = admin_normalize_role((string) ($user['ruolo'] ?? 'admin'));
        $_SESSION['admin_user'] = [
            'id' => (int) $user['id'],
            'nome' => (string) ($user['nome'] ?? ''),
            'email' => (string) $user['email'],
            'ruolo' => $_SESSION['admin_ruolo'],
        ];
        $_SESSION['admin_last_activity'] = time();
        $_SESSION['admin_remembered'] = $remembered;
        $_SESSION['admin_remember_expires_at'] = $remembered ? $rememberExpiresAt : 0;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

if (!function_exists('admin_issue_remember_token')) {
    function admin_issue_remember_token(PDO $pdo, int $adminId): void
    {
        $token = bin2hex(random_bytes(32));
        $tokenHash = hash('sha256', $token);
        $expiresAt = time() + (admin_remember_days() * 86400);

        $pdo->prepare('DELETE FROM admin_remember_tokens WHERE expires_at <= NOW()')->execute();
        $insert = $pdo->prepare(
            'INSERT INTO admin_remember_tokens (admin_id, token_hash, expires_at) '
            . 'VALUES (:admin_id, :token_hash, :expires_at)'
        );
        $insert->execute([
            'admin_id' => $adminId,
            'token_hash' => $tokenHash,
            'expires_at' => date('Y-m-d H:i:s', $expiresAt),
        ]);

        admin_set_remember_cookie($token, $expiresAt);
        $_SESSION['admin_remembered'] = true;
        $_SESSION['admin_remember_expires_at'] = $expiresAt;
    }
}

if (!function_exists('admin_revoke_remember_token')) {
    function admin_revoke_remember_token(PDO $pdo): void
    {
        $token = admin_remember_token();
        if ($token !== null) {
            try {
                $delete = $pdo->prepare('DELETE FROM admin_remember_tokens WHERE token_hash = :token_hash');
                $delete->execute(['token_hash' => hash('sha256', $token)]);
            } catch (Throwable $exception) {
                error_log('[Admin remember logout] ' . $exception->getMessage());
            }
        }
        admin_clear_remember_cookie();
    }
}

if (!function_exists('admin_restore_remembered_admin')) {
    function admin_restore_remembered_admin(): bool
    {
        global $pdo;
        static $attempted = false;
        if ($attempted) {
            return false;
        }
        $attempted = true;

        $token = admin_remember_token();
        if ($token === null || !$pdo instanceof PDO) {
            return false;
        }

        $tokenHash = hash('sha256', $token);
        try {
            $stmt = $pdo->prepare(
                'SELECT t.id AS remember_id, u.id, u.nome, u.email, u.ruolo '
                . 'FROM admin_remember_tokens t '
                . 'INNER JOIN utenti u ON u.id = t.admin_id '
                . 'WHERE t.token_hash = :token_hash AND t.expires_at > NOW() LIMIT 1'
            );
            $stmt->execute(['token_hash' => $tokenHash]);
            $user = $stmt->fetch();
            if (!is_array($user)) {
                admin_clear_remember_cookie();
                return false;
            }

            $newToken = bin2hex(random_bytes(32));
            $newHash = hash('sha256', $newToken);
            $expiresAt = time() + (admin_remember_days() * 86400);
            $rotate = $pdo->prepare(
                'UPDATE admin_remember_tokens '
                . 'SET token_hash = :new_hash, expires_at = :expires_at, last_used_at = CURRENT_TIMESTAMP '
                . 'WHERE id = :id AND token_hash = :old_hash'
            );
            $rotate->execute([
                'new_hash' => $newHash,
                'expires_at' => date('Y-m-d H:i:s', $expiresAt),
                'id' => (int) $user['remember_id'],
                'old_hash' => $tokenHash,
            ]);
            if ($rotate->rowCount() !== 1) {
                admin_clear_remember_cookie();
                return false;
            }

            admin_populate_session($user, true, $expiresAt);
            admin_set_remember_cookie($newToken, $expiresAt);
            return true;
        } catch (Throwable $exception) {
            error_log('[Admin remember restore] ' . $exception->getMessage());
            admin_clear_remember_cookie();
            return false;
        }
    }
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

        if (admin_restore_remembered_admin()) {
            $admin = $_SESSION['admin_user'] ?? null;
            return is_array($admin) ? $admin : null;
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
    function login_admin(string $email, string $password, bool $remember = false): bool
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

        unset($_SESSION['login_attempts']);
        admin_populate_session($user, false, 0);

        if ($remember) {
            try {
                admin_issue_remember_token($pdo, (int) $user['id']);
            } catch (Throwable $exception) {
                error_log('[Admin remember login] ' . $exception->getMessage());
                $_SESSION['admin_remembered'] = false;
                $_SESSION['admin_remember_expires_at'] = 0;
                admin_clear_remember_cookie();
            }
        } else {
            $existingToken = admin_remember_token();
            if ($existingToken !== null) {
                admin_revoke_remember_token($pdo);
            }
        }

        return true;
    }
}

if (!function_exists('logout_admin')) {
    function logout_admin(): void
    {
        global $pdo;
        if ($pdo instanceof PDO) {
            admin_revoke_remember_token($pdo);
        } else {
            admin_clear_remember_cookie();
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000,
                'path' => $params['path'] ?: '/',
                'domain' => $params['domain'] ?? '',
                'secure' => (bool) ($params['secure'] ?? false),
                'httponly' => (bool) ($params['httponly'] ?? true),
                'samesite' => 'Strict',
            ]);
        }
        session_destroy();
    }
}

if (!function_exists('require_admin')) {
    function require_admin(): void
    {
        global $pdo;
        $admin = current_admin();
        if (!$admin) {
            logout_admin();
            header('Location: ../login.php');
            exit;
        }

        $remembered = !empty($_SESSION['admin_remembered']);
        if ($remembered) {
            $rememberExpiresAt = (int) ($_SESSION['admin_remember_expires_at'] ?? 0);
            if ($rememberExpiresAt <= time()) {
                logout_admin();
                header('Location: ../login.php');
                exit;
            }
        } else {
            $maxIdle = max(900, lauco_env_int('ADMIN_IDLE_TIMEOUT_SECONDS', 7200));
            $lastActivity = (int) ($_SESSION['admin_last_activity'] ?? time());
            if ($lastActivity < time() - $maxIdle) {
                logout_admin();
                header('Location: ../login.php');
                exit;
            }
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
