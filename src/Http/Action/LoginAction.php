<?php
declare(strict_types=1);

namespace LaucoExperience\Http\Action;

use LaucoExperience\Http\PageAction;
use LaucoExperience\Http\RequestInput;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class LoginAction
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
        if ($connection instanceof \PDO) {
            $GLOBALS['pdo'] = $connection;
        }

        if (current_admin()) {
            return $response->withHeader('Location', '/admin/index.php')->withStatus(302);
        }

        $error = '';
        if (strtoupper($request->getMethod()) === 'POST') {
            $data = RequestInput::form($request);
            $token = (string) ($data['_csrf_token'] ?? '');
            if ($token === '' || !hash_equals(csrf_token(), $token)) {
                $error = 'Sessione scaduta o richiesta non valida.';
            } elseif (login_admin(trim((string) ($data['email'] ?? '')), (string) ($data['password'] ?? ''))) {
                return $response->withHeader('Location', '/admin/index.php')->withStatus(302);
            } else {
                $error = 'Email o password non corretti.';
            }
        }

        return $this->pages->render($request, $response, 'login.php', 'login.php', ['error' => $error]);
    }
}
