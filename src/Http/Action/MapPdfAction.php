<?php
declare(strict_types=1);

namespace LaucoExperience\Http\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class MapPdfAction
{
    public function __construct(private readonly string $root)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if (strtoupper($request->getMethod()) === 'GET') {
            try {
                require_once $this->root . '/inc/download-stats.php';
                $pdo = download_stats_open_pdo();
                if ($pdo !== null) {
                    download_track($pdo, 'map_pdf', 'mappa', $request->getHeaderLine('User-Agent'));
                }
            } catch (Throwable $e) {
                error_log('Map PDF analytics write failed: ' . $e->getMessage());
            }
        }

        return $response
            ->withStatus(302)
            ->withHeader('Location', '/mappa?print=1')
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('Referrer-Policy', 'same-origin');
    }
}
