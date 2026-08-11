<?php
declare(strict_types=1);

namespace LaucoExperience\Http\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class QrRedirectAction
{
    public function __construct(private readonly string $root)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        require_once $this->root . '/inc/qr-stats.php';

        $query = $request->getQueryParams();
        $code = trim((string) ($query['c'] ?? ''));
        $path = rtrim($request->getUri()->getPath(), '/') ?: '/';

        // /map è l'URL stampato nel QR fisico: deve essere tracciato senza query string.
        if ($code === '' && in_array($path, ['/map', '/map.php'], true)) {
            $code = 'map';
        }

        $definition = $code !== '' ? qr_definition($code) : null;
        if ($definition === null) {
            $response->getBody()->write('QR non riconosciuto.');
            return $response
                ->withStatus(404)
                ->withHeader('Content-Type', 'text/plain; charset=utf-8')
                ->withHeader('Cache-Control', 'no-store');
        }

        if (strtoupper($request->getMethod()) === 'GET') {
            try {
                $pdo = qr_stats_open_pdo();
                if ($pdo !== null) {
                    qr_track_scan($pdo, $code);
                }
            } catch (Throwable $e) {
                error_log('QR analytics write failed: ' . $e->getMessage());
            }
        }

        return $response
            ->withStatus(302)
            ->withHeader('Location', $definition['destination'])
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('Referrer-Policy', 'no-referrer');
    }
}
