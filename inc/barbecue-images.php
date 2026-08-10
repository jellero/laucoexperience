<?php
declare(strict_types=1);

if (!function_exists('lauco_barbecue_image_data_uri')) {
    function lauco_barbecue_image_data_uri(string $name): string
    {
        $parts = [
            'vinaio' => 4,
            'porteal' => 4,
        ];

        if (!isset($parts[$name])) {
            return '';
        }

        $encoded = '';
        for ($part = 1; $part <= $parts[$name]; $part++) {
            $path = LAUCO_ROOT . '/resources/media/barbecue/' . $name . '-' . $part . '.b64';
            if (!is_file($path)) {
                return '';
            }

            $chunk = file_get_contents($path);
            if ($chunk === false) {
                return '';
            }

            $encoded .= trim($chunk);
        }

        return $encoded === '' ? '' : 'data:image/webp;base64,' . $encoded;
    }
}
