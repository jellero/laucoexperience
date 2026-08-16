<?php
declare(strict_types=1);

namespace LaucoExperience\Http\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class GpxFileAction
{
    public function __construct(private readonly string $root)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        $filename = trim((string) ($args['filename'] ?? $request->getQueryParams()['f'] ?? ''));
        $filename = rawurldecode($filename);

        if ($filename === '' || basename($filename) !== $filename || !preg_match('/\.gpx$/i', $filename)) {
            return $this->notFound($response);
        }

        $filePath = $this->resolveFile($filename);
        if ($filePath === null) {
            return $this->notFound($response);
        }

        $isExplicitDownload = (string) ($request->getQueryParams()['download'] ?? '') === '1';
        if ($isExplicitDownload && strtoupper($request->getMethod()) === 'GET') {
            try {
                require_once $this->root . '/inc/download-stats.php';
                $pdo = download_stats_open_pdo();
                if ($pdo !== null) {
                    download_track($pdo, 'gpx', basename($filePath), $request->getHeaderLine('User-Agent'));
                }
            } catch (Throwable $e) {
                error_log('GPX download analytics write failed: ' . $e->getMessage());
            }
        }

        if (strtoupper($request->getMethod()) !== 'HEAD') {
            $content = file_get_contents($filePath);
            if ($content === false) {
                return $this->notFound($response);
            }
            $response->getBody()->write($content);
        }

        $response = $response
            ->withHeader('Content-Type', 'application/gpx+xml; charset=utf-8')
            ->withHeader('Content-Length', (string) filesize($filePath))
            ->withHeader('Cache-Control', 'public, max-age=300')
            ->withHeader('X-Content-Type-Options', 'nosniff');

        if ($isExplicitDownload) {
            $response = $response->withHeader(
                'Content-Disposition',
                'attachment; filename="' . str_replace('"', '', basename($filePath)) . '"'
            );
        }

        return $response;
    }

    private function resolveFile(string $filename): ?string
    {
        $roots = [
            $this->root . '/gpx',
            $this->root . '/uploads/percorsi/gpx',
        ];

        foreach ($roots as $candidateRoot) {
            $root = realpath($candidateRoot);
            if ($root === false || !is_dir($root)) {
                continue;
            }

            $filePath = realpath($root . DIRECTORY_SEPARATOR . $filename);
            $prefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
            if ($filePath !== false && is_file($filePath) && str_starts_with($filePath, $prefix)) {
                return $filePath;
            }
        }

        return null;
    }

    private function notFound(ResponseInterface $response): ResponseInterface
    {
        $response->getBody()->write('Traccia GPX non trovata.');

        return $response
            ->withStatus(404)
            ->withHeader('Content-Type', 'text/plain; charset=utf-8')
            ->withHeader('Cache-Control', 'no-store');
    }
}
