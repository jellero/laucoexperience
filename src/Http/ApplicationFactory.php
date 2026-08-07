<?php
declare(strict_types=1);

namespace LaucoExperience\Http;

use LaucoExperience\Localization\HtmlLocalizer;
use LaucoExperience\Localization\LocaleResolver;
use LaucoExperience\Localization\SiteCatalogRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Factory\AppFactory as SlimAppFactory;

final class ApplicationFactory
{
    public static function create(string $root, SiteCatalogRepository $catalogs): App
    {
        $app = SlimAppFactory::create();
        $basePath = trim((string) lauco_env('APP_BASE_PATH', ''));
        if ($basePath !== '') {
            $app->setBasePath('/' . trim($basePath, '/'));
        }

        $resolver = new LocaleResolver();
        $legacy = new LegacyPageAction($root, $resolver, new HtmlLocalizer($catalogs));
        foreach (self::publicPages() as $route => $file) {
            $app->get(
                $route,
                static fn (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface => $legacy->render($request, $response, $file)
            );
        }

        $app->get('/api/v1/health', static function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            $payload = json_encode([
                'status' => 'ok',
                'application' => 'lauco-experience',
                'framework' => 'slim-4',
                'locales' => array_keys(LocaleResolver::LOCALES),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
        });

        $app->get('/api/v1/site-texts/{locale}', static function (ServerRequestInterface $request, ResponseInterface $response, array $args) use ($catalogs): ResponseInterface {
            $locale = strtolower((string) ($args['locale'] ?? ''));
            if (!isset(LocaleResolver::LOCALES[$locale])) {
                $response->getBody()->write('{"error":"unsupported_locale"}');
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json; charset=utf-8');
            }
            $payload = json_encode([
                'locale' => $locale,
                'messages' => $catalogs->load($locale),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json; charset=utf-8')
                ->withHeader('Cache-Control', 'public, max-age=300');
        });

        $app->addRoutingMiddleware();
        $app->addErrorMiddleware(lauco_env_bool('APP_DEBUG'), true, true);
        return $app;
    }

    /** @return array<string,string> */
    private static function publicPages(): array
    {
        $files = [
            'index', 'map', 'mappa1', 'segnaletica', 'consigli',
            'itinerari-piedi', 'itinerari-mtb', 'itinerari-speciali',
            'gestione-sentieri', 'contribuisci', 'segnala-problema',
            'luoghi', 'luogo', 'eventi', 'evento', 'percorso',
            'contatti', 'newsletter', 'privacy', 'cookie', '400',
        ];
        $routes = ['/' => 'index.php'];
        foreach ($files as $file) {
            $routes['/' . $file] = $file . '.php';
            $routes['/' . $file . '.php'] = $file . '.php';
        }
        return $routes;
    }
}
