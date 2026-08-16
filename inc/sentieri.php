<?php
declare(strict_types=1);

require_once __DIR__ . '/gpx-stats.php';

if (!function_exists('sentieri_statuses')) {
    /** @return array<string,string> */
    function sentieri_statuses(): array
    {
        return [
            'verificato' => 'Verificato',
            'attenzione' => 'Attenzione',
            'non_percorribile' => 'Temporaneamente non percorribile',
            'in_verifica' => 'In verifica',
        ];
    }
}

if (!function_exists('sentieri_status_label')) {
    function sentieri_status_label(string $status): string
    {
        return sentieri_statuses()[$status] ?? 'In verifica';
    }
}

if (!function_exists('sentieri_slugify')) {
    function sentieri_slugify(string $value): string
    {
        $value = trim($value);
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($converted !== false) {
            $value = $converted;
        }
        $value = strtolower((string) preg_replace('/[^a-zA-Z0-9]+/', '-', $value));
        return trim($value, '-') ?: 'sentiero';
    }
}

if (!function_exists('sentieri_unique_slug')) {
    function sentieri_unique_slug(PDO $pdo, string $value, ?int $excludeId = null): string
    {
        $base = sentieri_slugify($value);
        $slug = $base;
        $suffix = 2;

        do {
            $sql = 'SELECT COUNT(*) FROM sentieri WHERE slug = :slug';
            $params = ['slug' => $slug];
            if ($excludeId !== null) {
                $sql .= ' AND id <> :id';
                $params['id'] = $excludeId;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $exists = (int) $stmt->fetchColumn() > 0;
            if ($exists) {
                $slug = $base . '-' . $suffix++;
            }
        } while ($exists);

        return $slug;
    }
}

if (!function_exists('sentieri_store_gpx')) {
    /** @param array<string,mixed> $file */
    function sentieri_store_gpx(array $file): ?string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Il caricamento del file GPX non è riuscito.');
        }

        $name = (string) ($file['name'] ?? '');
        $temporaryPath = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'gpx') {
            throw new RuntimeException('È consentito soltanto il formato .gpx.');
        }
        if ($size < 1 || $size > 15 * 1024 * 1024) {
            throw new RuntimeException('Il file GPX deve avere una dimensione massima di 15 MB.');
        }
        if (!is_uploaded_file($temporaryPath) || count(gpx_extract_points($temporaryPath)) < 2) {
            throw new RuntimeException('Il file non contiene una traccia GPX valida.');
        }

        $relativeDirectory = 'uploads/sentieri/gpx';
        $absoluteDirectory = dirname(__DIR__) . '/' . $relativeDirectory;
        if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0775, true) && !is_dir($absoluteDirectory)) {
            throw new RuntimeException('Impossibile creare la cartella dei GPX dei sentieri.');
        }

        $filename = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.gpx';
        $relativePath = $relativeDirectory . '/' . $filename;
        if (!move_uploaded_file($temporaryPath, dirname(__DIR__) . '/' . $relativePath)) {
            throw new RuntimeException('Impossibile salvare il file GPX.');
        }

        return $relativePath;
    }
}

if (!function_exists('sentieri_delete_gpx')) {
    function sentieri_delete_gpx(?string $relativePath): void
    {
        if (!$relativePath) {
            return;
        }
        $root = realpath(dirname(__DIR__) . '/uploads/sentieri/gpx');
        $file = realpath(dirname(__DIR__) . '/' . ltrim($relativePath, '/'));
        if ($root === false || $file === false || !is_file($file)) {
            return;
        }
        $prefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (str_starts_with($file, $prefix)) {
            unlink($file);
        }
    }
}

if (!function_exists('sentieri_normalize_datetime')) {
    function sentieri_normalize_datetime(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }
        $value = str_replace('T', ' ', $value);
        foreach (['Y-m-d H:i', 'Y-m-d H:i:s'] as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            if ($date !== false && $date->format($format) === $value) {
                return $date->format('Y-m-d H:i:s');
            }
        }
        throw new RuntimeException('La data di verifica non è valida.');
    }
}
