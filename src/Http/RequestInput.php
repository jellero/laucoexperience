<?php
declare(strict_types=1);

namespace LaucoExperience\Http;

use Psr\Http\Message\ServerRequestInterface;

final class RequestInput
{
    /** @return array<string,mixed> */
    public static function form(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();
        return is_array($body) ? $body : [];
    }
}
