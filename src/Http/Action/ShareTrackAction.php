<?php
declare(strict_types=1);

namespace LaucoExperience\Http\Action;

use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class ShareTrackAction
{
    public function __construct(private readonly string $root)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $raw = (string) $request->getBody();
        if (strlen($raw) > 4096) {
            return $response->withStatus(413);
        }

        try {
            $payload = json_decode($raw, true, 16, JSON_THROW_ON_ERROR);
            if (!is_array($payload)) {
                return $response->withStatus(400);
            }

            require_once $this->root . '/inc/share-stats.php';
            $channel = share_stats_channel((string) ($payload['channel'] ?? ''));
            $page = $this->pageParts((string) ($payload['url'] ?? ''), $request);
            if ($channel === null || $page === null) {
                return $response->withStatus(400);
            }

            $language = strtolower(substr(trim((string) ($payload['language'] ?? 'it')), 0, 2));
            if (!in_array($language, ['it', 'en', 'de', 'sl'], true)) {
                $language = 'it';
            }

            require $this->root . '/inc/db.php';
            $connection = $pdo ?? ($GLOBALS['pdo'] ?? null);
            if ($connection instanceof PDO) {
                share_stats_track($connection, $channel, $page['path'], $page['query'], $language);
            }
        } catch (Throwable $error) {
            error_log('Share analytics write failed: ' . $error->getMessage());
        }

        return $response
            ->withStatus(204)
            ->withHeader('Cache-Control', 'no-store')
            ->withHeader('Referrer-Policy', 'same-origin');
    }

    /** @return array{path:string,query:array<string,mixed>}|null */
    private function pageParts(string $url, ServerRequestInterface $request): ?array
    {
        $url = trim($url);
        if ($url === '' || strlen($url) > 2048) {
            return null;
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return null;
        }
        $host = strtolower((string) ($parts['host'] ?? ''));
        $requestHost = strtolower($request->getUri()->getHost());
        $configuredHost = strtolower((string) (parse_url((string) lauco_env('APP_URL', ''), PHP_URL_HOST) ?: ''));
        $allowedHosts = array_values(array_unique(array_filter([$requestHost, $configuredHost])));
        if ($host !== '' && !in_array($host, $allowedHosts, true)) {
            return null;
        }

        $path = (string) ($parts['path'] ?? '/');
        if ($path === '' || !str_starts_with($path, '/') || str_contains($path, '..')) {
            return null;
        }
        $query = [];
        parse_str((string) ($parts['query'] ?? ''), $query);
        $query = isset($query['slug']) && is_scalar($query['slug'])
            ? ['slug' => (string) $query['slug']]
            : [];

        return ['path' => $path, 'query' => $query];
    }
}
