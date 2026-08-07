<?php
declare(strict_types=1);

namespace LaucoExperience\Http;

use LaucoExperience\Localization\HtmlLocalizer;
use LaucoExperience\Localization\LocaleResolver;
use LaucoExperience\View\PhpView;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class PageAction
{
    public function __construct(
        private readonly PhpView $views,
        private readonly LocaleResolver $locales,
        private readonly HtmlLocalizer $localizer,
    ) {
    }

    /** @param array<string,mixed> $data */
    public function render(
        ServerRequestInterface $request,
        ResponseInterface $response,
        string $template,
        string $scriptName,
        array $data = [],
        int $defaultStatus = 200,
    ): ResponseInterface {
        $locale = $this->locales->resolve($request);
        $previousGet = $_GET;
        $previousPost = $_POST;
        $previousServer = [];
        foreach (['SCRIPT_NAME', 'PHP_SELF', 'REQUEST_METHOD'] as $key) {
            $previousServer[$key] = $_SERVER[$key] ?? null;
        }

        $_GET = array_replace($_GET, $request->getQueryParams(), ['lang' => $locale]);
        $_POST = array_replace($_POST, RequestInput::form($request));
        $_SERVER['SCRIPT_NAME'] = '/' . ltrim($scriptName, '/');
        $_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'];
        $_SERVER['REQUEST_METHOD'] = strtoupper($request->getMethod());
        http_response_code($defaultStatus);

        try {
            $html = $this->views->render('pages/' . $template, $data);
            $status = http_response_code();
            if ($status === 404 && trim($html) === '' && $template !== '400.php') {
                $_SERVER['SCRIPT_NAME'] = '/400.php';
                $_SERVER['PHP_SELF'] = '/400.php';
                $html = $this->views->render('pages/400.php');
            }
        } finally {
            $_GET = $previousGet;
            $_POST = $previousPost;
            foreach ($previousServer as $key => $value) {
                if ($value === null) {
                    unset($_SERVER[$key]);
                } else {
                    $_SERVER[$key] = $value;
                }
            }
        }

        $response->getBody()->write($this->localizer->localize($html, $locale));
        $response = $response
            ->withStatus(is_int($status) && $status >= 400 ? $status : $defaultStatus)
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withHeader('Content-Language', $locale)
            ->withHeader('Vary', 'Accept-Language, Cookie');

        if ($this->locales->shouldPersist($request)) {
            $secure = $request->getUri()->getScheme() === 'https';
            $response = $response->withAddedHeader('Set-Cookie', $this->locales->cookieHeader($locale, $secure));
        }

        return $response;
    }
}
