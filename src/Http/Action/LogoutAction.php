<?php
declare(strict_types=1);

namespace LaucoExperience\Http\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class LogoutAction
{
    public function __construct(private readonly string $root)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        require_once $this->root . '/inc/auth.php';
        logout_admin();
        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
