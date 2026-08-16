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

if (!function_exists('sentieri_gpx_directory')) {
    function sentieri_gpx_directory(): string
    {
        return dirname(__DIR__) . '/gpx';
    }
}

if (!function_exists('sentieri_gpx_files')) {
    /** @return list<array{filename:string,path:string,absolute:string,size:int,modified_at:int}> */
    function sentieri_gpx_files(): array
    {
        $directory = sentieri_gpx_directory();
        if (!is_dir($directory)) {
            return [];
        }

        $root = realpath($directory);
        if ($root === false) {
            return [];
        }

        $files = [];
        foreach (new FilesystemIterator($root, FilesystemIterator::SKIP_DOTS) as $file) {
            if (!$file->isFile() || $file->isLink() || strtolower($file->getExtension()) !== 'gpx') {
                continue;
            }
            $filename = $file->getFilename();
            $files[] = [
                'filename' => $filename,
                'path' => 'gpx/' . $filename,
                'absolute' => $file->getPathname(),
                'size' => $file->getSize(),
                'modified_at' => $file->getMTime(),
            ];
        }
        usort($files, static fn (array $a, array $b): int => strnatcasecmp($a['filename'], $b['filename']));
        return $files;
    }
}

if (!function_exists('sentieri_name_from_filename')) {
    function sentieri_name_from_filename(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $name = preg_replace('/[_-]+/u', ' ', $name) ?? $name;
        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name) ?: 'Sentiero';
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
    function sentieri_store_gpx(array $file): string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            throw new RuntimeException('Seleziona un file GPX.');
        }
        if ($error !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Il caricamento del file GPX non è riuscito.');
        }

        $originalName = (string) ($file['name'] ?? '');
        $temporaryPath = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if (strtolower(pathinfo($originalName, PATHINFO_EXTENSION)) !== 'gpx') {
            throw new RuntimeException('È consentito soltanto il formato .gpx.');
        }
        if ($size < 1 || $size > 15 * 1024 * 1024) {
            throw new RuntimeException('Il file GPX deve avere una dimensione massima di 15 MB.');
        }
        if (!is_uploaded_file($temporaryPath) || count(gpx_extract_points($temporaryPath)) < 2) {
            throw new RuntimeException('Il file non contiene una traccia GPX valida.');
        }

        $directory = sentieri_gpx_directory();
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Impossibile creare la cartella GPX.');
        }

        $base = pathinfo($originalName, PATHINFO_FILENAME);
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $base);
        $base = is_string($converted) ? $converted : $base;
        $base = trim((string) preg_replace('/[^a-zA-Z0-9#_-]+/', '-', $base), '-_.');
        $base = $base !== '' ? $base : 'sentiero';
        $filename = $base . '.gpx';
        $counter = 2;
        while (is_file($directory . '/' . $filename)) {
            $filename = $base . '-' . $counter++ . '.gpx';
        }
        if (!move_uploaded_file($temporaryPath, $directory . '/' . $filename)) {
            throw new RuntimeException('Impossibile salvare il file nella cartella GPX.');
        }
        return 'gpx/' . $filename;
    }
}

if (!function_exists('sentieri_delete_gpx')) {
    function sentieri_delete_gpx(string $relativePath): void
    {
        $directory = realpath(sentieri_gpx_directory());
        $file = realpath(dirname(__DIR__) . '/' . ltrim($relativePath, '/'));
        if ($directory === false || $file === false || !is_file($file)) {
            return;
        }
        $prefix = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (!str_starts_with($file, $prefix)) {
            throw new RuntimeException('Il file indicato non appartiene alla cartella GPX.');
        }
        if (!unlink($file)) {
            throw new RuntimeException('Impossibile eliminare il file GPX.');
        }
    }
}

if (!function_exists('sentieri_sync_gpx_directory')) {
    function sentieri_sync_gpx_directory(PDO $pdo, ?int $adminId = null): void
    {
        $files = sentieri_gpx_files();
        $paths = array_column($files, 'path');
        $known = [];
        foreach ($pdo->query("SELECT id,gpx_file FROM sentieri WHERE gpx_file LIKE 'gpx/%'")->fetchAll() as $row) {
            $known[(string) $row['gpx_file']] = (int) $row['id'];
        }

        $pdo->beginTransaction();
        try {
            foreach ($files as $file) {
                if (isset($known[$file['path']])) {
                    continue;
                }
                $name = sentieri_name_from_filename($file['filename']);
                $pdo->prepare(
                    'INSERT INTO sentieri (nome,slug,gpx_file,stato,pubblicato,created_by,updated_by) VALUES (:nome,:slug,:gpx,\'in_verifica\',1,:created_by,:updated_by)'
                )->execute([
                    'nome' => $name,
                    'slug' => sentieri_unique_slug($pdo, $name),
                    'gpx' => $file['path'],
                    'created_by' => $adminId,
                    'updated_by' => $adminId,
                ]);
            }

            foreach ($known as $path => $id) {
                if (!in_array($path, $paths, true)) {
                    $pdo->prepare('DELETE FROM sentieri WHERE id = :id')->execute(['id' => $id]);
                }
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}

if (!function_exists('sentieri_directory_rows')) {
    /** @return list<array<string,mixed>> */
    function sentieri_directory_rows(PDO $pdo, bool $publishedOnly = false): array
    {
        $metadata = [];
        foreach ($pdo->query('SELECT * FROM sentieri')->fetchAll() as $row) {
            $metadata[(string) $row['gpx_file']] = $row;
        }

        $rows = [];
        foreach (sentieri_gpx_files() as $file) {
            $row = $metadata[$file['path']] ?? [
                'id' => 0,
                'nome' => sentieri_name_from_filename($file['filename']),
                'codice' => null,
                'slug' => sentieri_slugify($file['filename']),
                'localita' => null,
                'descrizione' => null,
                'gpx_file' => $file['path'],
                'stato' => 'in_verifica',
                'nota_pubblica' => null,
                'ultima_verifica_at' => null,
                'prossima_verifica_at' => null,
                'pubblicato' => 1,
                'ordine' => 0,
            ];
            if ($publishedOnly && empty($row['pubblicato'])) {
                continue;
            }
            $rows[] = array_merge($row, [
                'filename' => $file['filename'],
                'file_size' => $file['size'],
                'file_modified_at' => $file['modified_at'],
            ]);
        }
        return $rows;
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
