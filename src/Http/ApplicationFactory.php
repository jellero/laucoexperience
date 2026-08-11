<?php
declare(strict_types=1);

namespace LaucoExperience\Http;

use LaucoExperience\Http\Action\AccountSetupAction;
use LaucoExperience\Http\Action\ContactSubmitAction;
use LaucoExperience\Http\Action\GpxFileAction;
use LaucoExperience\Http\Action\LoginAction;
use LaucoExperience\Http\Action\LogoutAction;
use LaucoExperience\Http\Action\NewsletterSubscribeAction;
use LaucoExperience\Http\Action\QrRedirectAction;
use LaucoExperience\Http\Action\SitemapAction;
use LaucoExperience\Http\Middleware\CanonicalUrlMiddleware;
use LaucoExperience\Localization\HtmlLocalizer;
use LaucoExperience\Localization\LocaleResolver;
use LaucoExperience\Localization\SiteCatalogRepository;
use LaucoExperience\View\PhpView;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
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

        $locales = new LocaleResolver();
        $pages = new PageAction(
            new PhpView($root . '/resources/views'),
            $locales,
            new HtmlLocalizer($catalogs)
        );
        $handlers = [
            'login' => new LoginAction($root, $pages),
            'logout' => new LogoutAction($root),
            'account-setup' => new AccountSetupAction($root, $pages),
            'contact-submit' => new ContactSubmitAction($root),
            'newsletter' => new NewsletterSubscribeAction($root, $locales),
            'qr-redirect' => new QrRedirectAction($root),
            'gpx-file' => new GpxFileAction($root),
            'sitemap' => new SitemapAction($root),
        ];

        $definitions = require $root . '/config/routes.php';
        if (!is_array($definitions)) {
            throw new RuntimeException('Configurazione route non valida.');
        }
        foreach ($definitions as $definition) {
            self::register($app, $definition, $pages, $handlers);
        }

        $app->get('/api/v1/health', static function (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface {
            return JsonResponse::create($response, [
                'status' => 'ok',
                'application' => 'lauco-experience',
                'framework' => 'slim-4',
                'architecture' => 'front-controller',
                'locales' => array_keys(LocaleResolver::LOCALES),
            ]);
        });

        $app->get('/api/v1/site-texts/{locale}', static function (ServerRequestInterface $request, ResponseInterface $response, array $args) use ($catalogs): ResponseInterface {
            $locale = strtolower((string) ($args['locale'] ?? ''));
            if (!isset(LocaleResolver::LOCALES[$locale])) {
                return JsonResponse::create($response, ['error' => 'unsupported_locale'], 404);
            }
            return JsonResponse::create($response, [
                'locale' => $locale,
                'messages' => $catalogs->load($locale),
            ])->withHeader('Cache-Control', 'public, max-age=300');
        });

        $app->map(['GET', 'HEAD', 'POST'], '/{path:.*}', static function (ServerRequestInterface $request, ResponseInterface $response) use ($pages): ResponseInterface {
            $path = $request->getUri()->getPath();
            if ($path !== '/' && str_ends_with($path, '/')) {
                $target = rtrim($path, '/');
                $query = $request->getUri()->getQuery();
                return $response
                    ->withHeader('Location', $target . ($query !== '' ? '?' . $query : ''))
                    ->withStatus(308);
            }
            return $pages->render($request, $response, '400.php', '400.php', [], 404);
        });

        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();
        $app->add(new CanonicalUrlMiddleware($app->getResponseFactory()));
        $app->addErrorMiddleware(lauco_env_bool('APP_DEBUG'), true, true);
        return $app;
    }

    /**
     * @param array<string,mixed> $definition
     * @param array<string,callable> $handlers
     */
    private static function register(App $app, array $definition, PageAction $pages, array $handlers): void
    {
        $name = (string) ($definition['name'] ?? '');
        $methods = array_values(array_filter($definition['methods'] ?? [], 'is_string'));
        $paths = array_values(array_filter($definition['paths'] ?? [], 'is_string'));
        $handlerName = (string) ($definition['handler'] ?? '');
        if ($name === '' || $methods === [] || $paths === [] || $handlerName === '') {
            throw new RuntimeException('Definizione route incompleta.');
        }
        if (in_array('GET', $methods, true) && !in_array('HEAD', $methods, true)) {
            $methods[] = 'HEAD';
        }

        if ($handlerName === 'page') {
            $template = (string) ($definition['template'] ?? '');
            $script = (string) ($definition['script'] ?? '');
            $status = max(100, min(599, (int) ($definition['status'] ?? 200)));
            $callable = static fn (ServerRequestInterface $request, ResponseInterface $response): ResponseInterface =>
                $pages->render($request, $response, $template, $script, [], $status);
        } elseif (isset($handlers[$handlerName])) {
            $callable = $handlers[$handlerName];
        } else {
            throw new RuntimeException('Handler route sconosciuto: ' . $handlerName);
        }

        $alias = 0;
        foreach ($paths as $path) {
            $route = $app->map($methods, $path, $callable);
            $route->setName($alias === 0 ? $name : $name . '.alias-' . $alias);
            $alias++;
        }
    }
}
