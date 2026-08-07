<?php
declare(strict_types=1);

if (!function_exists('lauco_env_load')) {
    /** @return array<string,string> */
    function lauco_env_load(): array
    {
        static $values;
        if (is_array($values)) {
            return $values;
        }

        $values = [];
        $path = dirname(__DIR__) . '/.env';
        if (!is_file($path) || !is_readable($path)) {
            return $values;
        }

        foreach (file($path, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ($key === '') {
                continue;
            }

            if (strlen($value) >= 2) {
                $first = $value[0];
                $last = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            $values[$key] = $value;
        }

        return $values;
    }
}

if (!function_exists('lauco_env')) {
    function lauco_env(string $key, ?string $default = null): ?string
    {
        $serverValue = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if (is_string($serverValue) && $serverValue !== '') {
            return $serverValue;
        }

        $values = lauco_env_load();
        return array_key_exists($key, $values) ? $values[$key] : $default;
    }
}

if (!function_exists('lauco_env_required')) {
    function lauco_env_required(string $key): string
    {
        $value = lauco_env($key);
        if ($value === null || trim($value) === '') {
            throw new RuntimeException('Configurazione mancante: ' . $key);
        }
        return $value;
    }
}

if (!function_exists('lauco_env_int')) {
    function lauco_env_int(string $key, int $default): int
    {
        $value = lauco_env($key);
        return $value !== null && preg_match('/^-?\d+$/', $value) ? (int) $value : $default;
    }
}

if (!function_exists('lauco_env_bool')) {
    function lauco_env_bool(string $key, bool $default = false): bool
    {
        $value = lauco_env($key);
        if ($value === null) {
            return $default;
        }
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
