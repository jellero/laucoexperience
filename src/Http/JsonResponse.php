<?php
declare(strict_types=1);

namespace LaucoExperience\Http;

use Psr\Http\Message\ResponseInterface;

final class JsonResponse
{
    /** @param array<string,mixed> $payload */
    public static function create(ResponseInterface $response, array $payload, int $status = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
        ));

        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}
