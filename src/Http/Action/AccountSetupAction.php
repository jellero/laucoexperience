<?php
declare(strict_types=1);

namespace LaucoExperience\Http\Action;

use LaucoExperience\Http\PageAction;
use LaucoExperience\Http\RequestInput;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class AccountSetupAction
{
    public function __construct(
        private readonly string $root,
        private readonly PageAction $pages,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        require_once $this->root . '/inc/auth.php';
        $connection = $pdo ?? ($GLOBALS['pdo'] ?? null);
        if (!$connection instanceof PDO) {
            throw new \RuntimeException('Connessione database non disponibile.');
        }
        $GLOBALS['pdo'] = $connection;

        $existingCount = (int) $connection->query('SELECT COUNT(*) FROM utenti')->fetchColumn();
        if ($existingCount > 0 && !current_admin()) {
            return $response->withHeader('Location', '/login')->withStatus(302);
        }
        if ($existingCount > 0) {
            $current = current_admin();
            $role = 'admin';
            try {
                $roleStatement = $connection->prepare('SELECT ruolo FROM utenti WHERE id = :id LIMIT 1');
                $roleStatement->execute(['id' => (int) ($current['id'] ?? 0)]);
                $storedRole = $roleStatement->fetchColumn();
                $role = $storedRole === false ? 'collaboratore' : admin_normalize_role((string) $storedRole);
            } catch (Throwable $exception) {
                $message = strtolower($exception->getMessage());
                if (!str_contains($message, 'unknown column') && !str_contains($message, 'no such column')) {
                    throw $exception;
                }
            }
            if (!admin_role_can($role, 'admin.all')) {
                $response->getBody()->write('Accesso non consentito.');
                return $response->withStatus(403)->withHeader('Content-Type', 'text/plain; charset=UTF-8');
            }
        }

        $setupToken = $_SESSION['_setup_csrf_token'] ?? null;
        if (!is_string($setupToken) || $setupToken === '') {
            $setupToken = bin2hex(random_bytes(32));
            $_SESSION['_setup_csrf_token'] = $setupToken;
        }

        $error = '';
        $success = '';
        if (strtoupper($request->getMethod()) === 'POST') {
            $data = RequestInput::form($request);
            $submittedToken = (string) ($data['_setup_csrf_token'] ?? '');
            $name = trim((string) ($data['nome'] ?? ''));
            $email = trim((string) ($data['email'] ?? ''));
            $password = (string) ($data['password'] ?? '');
            $confirmation = (string) ($data['password_confirm'] ?? '');
            $selectedRoles = $existingCount === 0
                ? ['admin']
                : admin_filter_roles($data['ruoli'] ?? []);
            $role = admin_roles_value($selectedRoles);

            if ($submittedToken === '' || !hash_equals($setupToken, $submittedToken)) {
                $error = 'Token di sicurezza non valido.';
            } elseif ($name === '') {
                $error = 'Inserisci il nome.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Inserisci una email valida.';
            } elseif (strlen($password) < 12) {
                $error = 'La password deve avere almeno 12 caratteri.';
            } elseif ($password !== $confirmation) {
                $error = 'Le due password non coincidono.';
            } elseif ($role === '') {
                $error = 'Seleziona almeno un permesso.';
            } else {
                try {
                    $statement = $connection->prepare('SELECT id FROM utenti WHERE LOWER(email) = LOWER(:email) LIMIT 1');
                    $statement->execute(['email' => $email]);
                    if ($statement->fetch()) {
                        $error = 'Esiste già un account con questa email.';
                    } else {
                        $statement = $connection->prepare('INSERT INTO utenti (nome, email, password_hash, ruolo) VALUES (:nome, :email, :password_hash, :ruolo)');
                        $statement->execute([
                            'nome' => $name,
                            'email' => $email,
                            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                            'ruolo' => $role,
                        ]);
                        $success = 'Account creato correttamente.';
                        $setupToken = bin2hex(random_bytes(32));
                        $_SESSION['_setup_csrf_token'] = $setupToken;
                    }
                } catch (Throwable $exception) {
                    error_log('[Account setup] ' . $exception->getMessage());
                    $error = 'Errore durante la creazione account.';
                }
            }
        }

        $users = $connection->query('SELECT id, nome, email, ruolo, created_at FROM utenti ORDER BY created_at DESC, id DESC')->fetchAll();
        return $this->pages->render($request, $response, 'crea-account.php', 'crea-account.php', [
            'error' => $error,
            'success' => $success,
            'setupToken' => $setupToken,
            'utenti' => $users,
            'existingCount' => $existingCount,
        ]);
    }
}
