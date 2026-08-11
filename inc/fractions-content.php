<?php
declare(strict_types=1);

require_once __DIR__ . '/territory-content.php';

if (!function_exists('fractions_content_catalog')) {
    /** @return array<string,mixed> */
    function fractions_content_catalog(): array
    {
        static $catalog = null;
        if (is_array($catalog)) {
            return $catalog;
        }

        $file = dirname(__DIR__) . '/resources/content/territory-fractions.json';
        if (!is_file($file)) {
            return $catalog = [];
        }

        $decoded = json_decode((string) file_get_contents($file), true);
        return $catalog = is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('fractions_content')) {
    /** @return array<string,mixed> */
    function fractions_content(?string $locale = null): array
    {
        $catalog = fractions_content_catalog();
        $locale = $locale ?: territory_locale();
        $localized = $catalog[$locale] ?? $catalog['it'] ?? [];
        return is_array($localized) ? $localized : [];
    }
}

if (!function_exists('fractions_items')) {
    /** @return list<array<string,mixed>> */
    function fractions_items(?string $locale = null): array
    {
        $items = fractions_content($locale)['items'] ?? [];
        return is_array($items) ? array_values(array_filter($items, 'is_array')) : [];
    }
}

if (!function_exists('fraction_content')) {
    /** @return array<string,mixed>|null */
    function fraction_content(string $slug, ?string $locale = null): ?array
    {
        $slug = strtolower(trim($slug));
        foreach (fractions_items($locale) as $item) {
            if (strtolower((string) ($item['slug'] ?? '')) === $slug) {
                return $item;
            }
        }
        return null;
    }
}
