<?php
declare(strict_types=1);

/**
 * Lettura statistiche da file GPX.
 *
 * Calcola:
 * - lunghezza km;
 * - dislivello positivo metri;
 * - durata stimata;
 * - difficoltà stimata;
 * - calorie stimate;
 * - data aggiornamento da filemtime GPX.
 */

if (!function_exists('gpx_project_root')) {
    function gpx_project_root(): string
    {
        return dirname(__DIR__);
    }
}

if (!function_exists('gpx_abs_path')) {
    function gpx_abs_path(?string $relativePath): ?string
    {
        if (!$relativePath) {
            return null;
        }

        $relativePath = ltrim($relativePath, '/');
        $candidate = gpx_project_root() . '/' . $relativePath;

        if (!is_file($candidate)) {
            return null;
        }

        return $candidate;
    }
}

if (!function_exists('gpx_haversine_km')) {
    function gpx_haversine_km(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadiusKm = 6371.0088;

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusKm * $c;
    }
}

if (!function_exists('gpx_extract_points')) {
    function gpx_extract_points(string $absolutePath): array
    {
        $xmlContent = file_get_contents($absolutePath);
        if ($xmlContent === false || trim($xmlContent) === '') {
            return [];
        }

        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);
        libxml_clear_errors();

        if (!$xml) {
            return [];
        }

        $namespaces = $xml->getNamespaces(true);
        $defaultNs = $namespaces[''] ?? null;

        if ($defaultNs) {
            $xml->registerXPathNamespace('g', $defaultNs);
            $nodes = $xml->xpath('//g:trkpt | //g:rtept');
        } else {
            $nodes = $xml->xpath('//trkpt | //rtept');
        }

        if (!$nodes) {
            return [];
        }

        $points = [];

        foreach ($nodes as $node) {
            $attrs = $node->attributes();
            $lat = isset($attrs['lat']) ? (float) $attrs['lat'] : null;
            $lon = isset($attrs['lon']) ? (float) $attrs['lon'] : null;

            if ($lat === null || $lon === null) {
                continue;
            }

            $ele = null;

            if ($defaultNs) {
                $children = $node->children($defaultNs);
                if (isset($children->ele)) {
                    $ele = (float) $children->ele;
                }
            } elseif (isset($node->ele)) {
                $ele = (float) $node->ele;
            }

            $points[] = [
                'lat' => $lat,
                'lon' => $lon,
                'ele' => $ele,
            ];
        }

        return $points;
    }
}

if (!function_exists('gpx_format_duration')) {
    function gpx_format_duration(float $hours): string
    {
        if ($hours <= 0) {
            return '-';
        }

        $minutes = (int) round($hours * 60);

        if ($minutes < 60) {
            return $minutes . ' min';
        }

        $h = intdiv($minutes, 60);
        $m = $minutes % 60;

        if ($m === 0) {
            return $h . ' h';
        }

        return $h . ' h ' . $m . ' min';
    }
}

if (!function_exists('gpx_difficulty')) {
    function gpx_difficulty(float $distanceKm, int $ascentM, string $tipo): string
    {
        if ($distanceKm <= 0) {
            return '-';
        }

        if ($tipo === 'mtb') {
            if ($distanceKm <= 15 && $ascentM <= 300) {
                return 'Facile';
            }

            if ($distanceKm <= 35 && $ascentM <= 900) {
                return 'Media';
            }

            return 'Difficile';
        }

        if ($distanceKm <= 8 && $ascentM <= 300) {
            return 'T';
        }

        if ($distanceKm <= 15 && $ascentM <= 800) {
            return 'E';
        }

        return 'EE';
    }
}

if (!function_exists('gpx_duration_hours')) {
    function gpx_duration_hours(float $distanceKm, int $ascentM, string $tipo): float
    {
        if ($distanceKm <= 0) {
            return 0.0;
        }

        if ($tipo === 'mtb') {
            return ($distanceKm / 10.0) + ($ascentM / 700.0);
        }

        return ($distanceKm / 4.0) + ($ascentM / 600.0);
    }
}

if (!function_exists('gpx_calories')) {
    function gpx_calories(float $distanceKm, int $ascentM, string $tipo): int
    {
        if ($distanceKm <= 0) {
            return 0;
        }

        if ($tipo === 'mtb') {
            return (int) round(($distanceKm * 35) + ($ascentM * 0.12));
        }

        return (int) round(($distanceKm * 60) + ($ascentM * 0.10));
    }
}

if (!function_exists('gpx_stats')) {
    function gpx_stats(?string $relativePath, string $tipo = 'piedi'): array
    {
        static $cache = [];

        $cacheKey = ($relativePath ?: '') . '|' . $tipo;

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $empty = [
            'length_km' => null,
            'length_label' => '-',
            'ascent_m' => null,
            'ascent_label' => '-',
            'duration_hours' => null,
            'duration_label' => '-',
            'difficulty' => '-',
            'calories' => null,
            'calories_label' => '-',
            'updated_label' => '-',
            'has_gpx' => false,
        ];

        $absolutePath = gpx_abs_path($relativePath);

        if (!$absolutePath) {
            $cache[$cacheKey] = $empty;
            return $empty;
        }

        $points = gpx_extract_points($absolutePath);

        if (count($points) < 2) {
            $empty['has_gpx'] = true;
            $empty['updated_label'] = date('d/m/Y', filemtime($absolutePath));
            $cache[$cacheKey] = $empty;
            return $empty;
        }

        $distanceKm = 0.0;
        $ascentM = 0;

        for ($i = 1; $i < count($points); $i++) {
            $prev = $points[$i - 1];
            $curr = $points[$i];

            $distanceKm += gpx_haversine_km(
                (float) $prev['lat'],
                (float) $prev['lon'],
                (float) $curr['lat'],
                (float) $curr['lon']
            );

            if ($prev['ele'] !== null && $curr['ele'] !== null) {
                $delta = (float) $curr['ele'] - (float) $prev['ele'];

                // Filtro minimo per ridurre rumore GPS/altimetrico.
                if ($delta > 1.5) {
                    $ascentM += (int) round($delta);
                }
            }
        }

        $distanceKm = round($distanceKm, 2);
        $durationHours = gpx_duration_hours($distanceKm, $ascentM, $tipo);
        $calories = gpx_calories($distanceKm, $ascentM, $tipo);

        $result = [
            'length_km' => $distanceKm,
            'length_label' => number_format($distanceKm, 2, ',', '.') . ' km',
            'ascent_m' => $ascentM,
            'ascent_label' => number_format($ascentM, 0, ',', '.') . ' m',
            'duration_hours' => $durationHours,
            'duration_label' => gpx_format_duration($durationHours),
            'difficulty' => gpx_difficulty($distanceKm, $ascentM, $tipo),
            'calories' => $calories,
            'calories_label' => number_format($calories, 0, ',', '.') . ' kcal',
            'updated_label' => date('d/m/Y', filemtime($absolutePath)),
            'has_gpx' => true,
        ];

        $cache[$cacheKey] = $result;
        return $result;
    }
}
