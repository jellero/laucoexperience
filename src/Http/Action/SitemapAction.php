<?php
declare(strict_types=1);

namespace LaucoExperience\Http\Action;

use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class SitemapAction
{
    public function __construct(private readonly string $root)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        require_once $this->root . '/inc/env.php';
        require_once $this->root . '/inc/fractions-content.php';

        $baseUrl = rtrim((string) lauco_env('APP_URL', 'https://laucoexperience.it'), '/');
        if (!preg_match('~^https?://~i', $baseUrl)) {
            $baseUrl = 'https://laucoexperience.it';
        }

        $locales = ['it', 'en', 'de', 'sl'];
        $entries = [];
        foreach ($this->staticPaths() as $path) {
            $entries[] = ['path' => $path, 'slug' => null, 'lastmod' => null];
        }

        $pdo = $this->connect();
        if ($pdo instanceof PDO) {
            $this->appendDynamic($entries, $pdo, 'percorsi', '/percorso');
            $this->appendDynamic($entries, $pdo, 'luoghi', '/luogo');
            $this->appendDynamic($entries, $pdo, 'eventi', '/evento');
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        foreach ($entries as $entry) {
            foreach ($locales as $locale) {
                $loc = $this->url($baseUrl, (string) $entry['path'], $locale, $entry['slug']);
                $xml .= "  <url>\n";
                $xml .= '    <loc>' . $this->escape($loc) . "</loc>\n";
                if (is_string($entry['lastmod']) && $entry['lastmod'] !== '') {
                    $xml .= '    <lastmod>' . $this->escape($entry['lastmod']) . "</lastmod>\n";
                }
                foreach ($locales as $alternateLocale) {
                    $alternateUrl = $this->url($baseUrl, (string) $entry['path'], $alternateLocale, $entry['slug']);
                    $xml .= '    <xhtml:link rel="alternate" hreflang="' . $alternateLocale . '" href="' . $this->escape($alternateUrl) . '" />' . "\n";
                }
                $defaultUrl = $this->url($baseUrl, (string) $entry['path'], 'it', $entry['slug']);
                $xml .= '    <xhtml:link rel="alternate" hreflang="x-default" href="' . $this->escape($defaultUrl) . '" />' . "\n";
                $xml .= "  </url>\n";
            }
        }

        $xml .= "</urlset>\n";
        $response->getBody()->write($xml);
        return $response
            ->withHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->withHeader('Cache-Control', 'public, max-age=900');
    }

    /** @return list<string> */
    private function staticPaths(): array
    {
        $paths = [
            '/', '/mappa', '/segnaletica', '/consigli', '/itinerari-piedi', '/itinerari-mtb',
            '/itinerari-speciali', '/forra', '/barbecue', '/gestione-sentieri', '/luoghi',
            '/frazioni', '/storia', '/natura', '/come-arrivare', '/eventi', '/eventi/archivio',
            '/contatti', '/contribuisci', '/segnala-problema', '/privacy', '/cookie',
        ];

        if (function_exists('fractions_items')) {
            foreach (fractions_items('it') as $fraction) {
                $slug = trim((string) ($fraction['slug'] ?? ''));
                if ($slug !== '') {
                    $paths[] = '/frazioni/' . $slug;
                }
            }
        }

        return array_values(array_unique($paths));
    }

    private function connect(): ?PDO
    {
        try {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
                lauco_env_required('DB_HOST'),
                lauco_env_int('DB_PORT', 3306),
                lauco_env_required('DB_NAME')
            );
            return new PDO($dsn, lauco_env_required('DB_USER'), lauco_env_required('DB_PASS'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => max(1, lauco_env_int('DB_CONNECT_TIMEOUT_SECONDS', 2)),
            ]);
        } catch (Throwable $e) {
            return null;
        }
    }

    /** @param list<array{path:string,slug:?string,lastmod:?string}> $entries */
    private function appendDynamic(array &$entries, PDO $pdo, string $table, string $path): void
    {
        if (!in_array($table, ['percorsi', 'luoghi', 'eventi'], true)) {
            return;
        }
        try {
            $stmt = $pdo->query("SELECT slug, COALESCE(updated_at, created_at) AS lastmod FROM {$table} WHERE pubblicato = 1 ORDER BY id ASC");
            foreach ($stmt->fetchAll() as $row) {
                $slug = trim((string) ($row['slug'] ?? ''));
                if ($slug === '') {
                    continue;
                }
                $lastmod = null;
                if (!empty($row['lastmod'])) {
                    $timestamp = strtotime((string) $row['lastmod']);
                    $lastmod = $timestamp ? date('Y-m-d', $timestamp) : null;
                }
                $entries[] = ['path' => $path, 'slug' => $slug, 'lastmod' => $lastmod];
            }
        } catch (Throwable $e) {
            // La sitemap statica resta disponibile anche durante una migrazione o un problema DB.
        }
    }

    private function url(string $baseUrl, string $path, string $locale, ?string $slug): string
    {
        $query = [];
        if (is_string($slug) && $slug !== '') {
            $query['slug'] = $slug;
        }
        if ($locale !== 'it') {
            $query['lang'] = $locale;
        }
        $url = $baseUrl . ($path === '/' ? '/' : $path);
        return $url . ($query !== [] ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '');
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
