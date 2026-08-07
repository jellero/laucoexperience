<?php
declare(strict_types=1);

namespace LaucoExperience\View;

use RuntimeException;
use Throwable;

final class PhpView
{
    public function __construct(private readonly string $directory)
    {
    }

    /** @param array<string,mixed> $data */
    public function render(string $template, array $data = []): string
    {
        if (!preg_match('~^[a-z0-9][a-z0-9._/-]*\.php$~', $template) || str_contains($template, '..')) {
            throw new RuntimeException('Nome della view non valido.');
        }

        $base = realpath($this->directory);
        $path = realpath($this->directory . '/' . $template);
        if ($base === false || $path === false || !is_file($path)) {
            throw new RuntimeException('View non trovata: ' . $template);
        }

        $base = rtrim(str_replace('\\', '/', $base), '/') . '/';
        if (!str_starts_with(str_replace('\\', '/', $path), $base)) {
            throw new RuntimeException('La view richiesta è fuori dalla directory consentita.');
        }

        if (!array_key_exists('pdo', $data) && ($GLOBALS['pdo'] ?? null) instanceof \PDO) {
            $data['pdo'] = $GLOBALS['pdo'];
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            require $path;
            return (string) ob_get_clean();
        } catch (Throwable $exception) {
            ob_end_clean();
            throw $exception;
        }
    }
}
