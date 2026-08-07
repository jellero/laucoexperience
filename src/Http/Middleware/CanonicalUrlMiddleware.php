<?php
declare(strict_types=1);

namespace LaucoExperience\Http\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CanonicalUrlMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly ResponseFactoryInterface $responses)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!in_array(strtoupper($request->getMethod()), ['GET', 'HEAD'], true)) {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();
        if (preg_match('~^/admin(?:/|$)~i', $path)) {
            return $handler->handle($request);
        }

        $target = match (true) {
            $path === '/index', $path === '/index.php' => '/',
            str_ends_with(strtolower($path), '.php') => substr($path, 0, -4),
            $path !== '/' && str_ends_with($path, '/') => rtrim($path, '/'),
            default => null,
        };
        if ($target === null || $target === $path) {
            return $handler->handle($request);
        }

        $query = $request->getUri()->getQuery();
        return $this->responses->createResponse(301)
            ->withHeader('Location', $target . ($query !== '' ? '?' . $query : ''));
    }
}
