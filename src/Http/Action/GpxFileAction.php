<?php
declare(strict_types=1);

namespace LaucoExperience\Http\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

        $gpxRoot = realpath($this->root . '/gpx');
        if ($gpxRoot === false || !is_dir($gpxRoot)) {
            return $this->notFound($response);
        }

        $filePath = realpath($gpxRoot . DIRECTORY_SEPARATOR . $filename);
        $prefix = rtrim($gpxRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        if ($filePath === false || !is_file($filePath) || !str_starts_with($filePath, $prefix)) {
            return $this->notFound($response);
        }

        if (strtoupper($request->getMethod()) !== 'HEAD') {
            $content = file_get_contents($filePath);
            if ($content === false) {
                return $this->notFound($response);
            }
            $response->getBody()->write($content);
        }

        return $response
            ->withHeader('Content-Type', 'application/gpx+xml; charset=utf-8')
            ->withHeader('Content-Length', (string) filesize($filePath))
            ->withHeader('Cache-Control', 'public, max-age=300')
            ->withHeader('X-Content-Type-Options', 'nosniff');
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
