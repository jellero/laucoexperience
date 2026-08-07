<?php
declare(strict_types=1);

namespace LaucoExperience\Http;

use LaucoExperience\Localization\HtmlLocalizer;
use LaucoExperience\Localization\LocaleResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

final class LegacyPageAction
{
    public function __construct(
        private readonly string $root,
        private readonly LocaleResolver $locales,
        private readonly HtmlLocalizer $localizer,
    ) {
    }

    public function render(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $file,
    ): ResponseInterface {
        if (!preg_match('/^[a-z0-9-]+\.php$/', $file)) {
            throw new RuntimeException('Template legacy non valido.');
        }
        $path = $this->root . '/' . $file;
        if (!is_file($path)) {
            return $response->withStatus(404);
        }

        $locale = $this->locales->resolve($request);
        $_GET = array_replace($_GET, $request->getQueryParams(), ['lang' => $locale]);

        $previousScript = $_SERVER['SCRIPT_NAME'] ?? null;
        $previousSelf = $_SERVER['PHP_SELF'] ?? null;
        $_SERVER['SCRIPT_NAME'] = '/' . $file;
        $_SERVER['PHP_SELF'] = '/' . $file;

        ob_start();
        try {
            require $path;
            $html = (string) ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        } finally {
            $this->restoreServerValue('SCRIPT_NAME', $previousScript);
            $this->restoreServerValue('PHP_SELF', $previousSelf);
        }

        $html = $this->localizer->localize($html, $locale);
        $response->getBody()->write($html);
        $status = http_response_code();
        $response = $response
            ->withStatus(is_int($status) && $status >= 400 ? $status : 200)
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withHeader('Content-Language', $locale)
            ->withHeader('Vary', 'Accept-Language, Cookie');

        if ($this->locales->shouldPersist($request)) {
            $secure = $request->getUri()->getScheme() === 'https';
            $response = $response->withAddedHeader('Set-Cookie', $this->locales->cookieHeader($locale, $secure));
        }
        return $response;
    }

    private function restoreServerValue(string $key, mixed $value): void
    {
        if ($value === null) {
            unset($_SERVER[$key]);
            return;
        }
        $_SERVER[$key] = $value;
    }
}
