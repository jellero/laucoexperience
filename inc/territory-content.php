<?php
declare(strict_types=1);

require_once __DIR__ . '/translations.php';

if (!function_exists('territory_locale')) {
    function territory_locale(): string
    {
        $locale = content_language_from_request();
        return in_array($locale, ['it', 'en', 'de', 'sl'], true) ? $locale : 'it';
    }
}

if (!function_exists('territory_content_file')) {
    function territory_content_file(string $key): string
    {
        $base = dirname(__DIR__) . '/resources/content/';
        return match ($key) {
            'history' => $base . 'territory-history.json',
            'nature' => $base . 'territory-nature.json',
            'arrive' => $base . 'territory-arrive.json',
            'home', 'nav', 'common' => $base . 'territory-ui.json',
            default => '',
        };
    }
}

if (!function_exists('territory_content_catalog')) {
    /** @return array<string, mixed> */
    function territory_content_catalog(string $key): array
    {
        static $cache = [];
        $file = territory_content_file($key);
        if ($file === '' || !is_file($file)) {
            return [];
        }
        if (!isset($cache[$file])) {
            $decoded = json_decode((string) file_get_contents($file), true);
            $cache[$file] = is_array($decoded) ? $decoded : [];
        }
        return $cache[$file];
    }
}

if (!function_exists('territory_content')) {
    /** @return array<string, mixed> */
    function territory_content(string $key): array
    {
        $catalog = territory_content_catalog($key);
        $locale = territory_locale();
        $localized = $catalog[$locale] ?? $catalog['it'] ?? [];
        if (!is_array($localized)) {
            return [];
        }
        if (in_array($key, ['home', 'nav', 'common'], true)) {
            $value = $localized[$key] ?? [];
            return is_array($value) ? $value : [];
        }
        return $localized;
    }
}

if (!function_exists('territory_navigation')) {
    /** @return array<string, string> */
    function territory_navigation(): array
    {
        return territory_content('nav');
    }
}
