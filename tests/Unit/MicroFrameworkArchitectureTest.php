<?php
declare(strict_types=1);

namespace LaucoExperience\Tests\Unit;

use LaucoExperience\View\PhpView;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MicroFrameworkArchitectureTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = dirname(__DIR__, 2);
    }

    public function testRootContainsNoPhpEndpoints(): void
    {
        self::assertSame([], glob($this->root . '/*.php') ?: []);
        self::assertFileExists($this->root . '/public/index.php');
    }

    public function testEveryPageRouteTargetsAnOrganizedView(): void
    {
        $routes = require $this->root . '/config/routes.php';
        self::assertNotEmpty($routes);

        $names = [];
        foreach ($routes as $route) {
            self::assertIsArray($route);
            self::assertNotEmpty($route['name'] ?? null);
            self::assertNotContains($route['name'], $names, 'Nome route duplicato.');
            $names[] = $route['name'];
            self::assertNotEmpty($route['methods'] ?? null);
            self::assertNotEmpty($route['paths'] ?? null);

            if (($route['handler'] ?? null) === 'page') {
                self::assertFileExists(
                    $this->root . '/resources/views/pages/' . $route['template'],
                    'View mancante per la route ' . $route['name']
                );
            }
        }

        self::assertContains('home', $names);
        self::assertContains('contact.submit', $names);
        self::assertContains('newsletter.subscribe', $names);

        $routedTemplates = array_values(array_unique(array_map(
            static fn (array $route): string => (string) $route['template'],
            array_filter($routes, static fn (array $route): bool => isset($route['template']))
        )));
        sort($routedTemplates);
        $viewTemplates = array_map('basename', glob($this->root . '/resources/views/pages/*.php') ?: []);
        sort($viewTemplates);
        self::assertSame($viewTemplates, $routedTemplates, 'Ogni pagina deve essere raggiungibile da una route dichiarata.');
    }

    public function testLegacyAdapterWasRemoved(): void
    {
        self::assertFileDoesNotExist($this->root . '/src/Http/LegacyPageAction.php');
        self::assertFileExists($this->root . '/src/Http/PageAction.php');
        self::assertFileExists($this->root . '/src/View/PhpView.php');
    }

    public function testViewRendererRejectsTraversal(): void
    {
        $renderer = new PhpView($this->root . '/resources/views');
        $this->expectException(RuntimeException::class);
        $renderer->render('../config/routes.php');
    }

    public function testPublicViewsDoNotEmitLegacyPhpUrls(): void
    {
        $files = glob($this->root . '/resources/views/{pages,partials,sections}/*.php', GLOB_BRACE) ?: [];
        self::assertNotEmpty($files);
        foreach ($files as $file) {
            $source = (string) file_get_contents($file);
            self::assertDoesNotMatchRegularExpression(
                '~(?:href|action)\\s*=\\s*["\'][^"\']*\\.php(?:[?"\'])~i',
                $source,
                'URL PHP pubblica residua in ' . $file
            );
        }
    }
}
