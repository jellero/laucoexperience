<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/translations.php';

if (!class_exists('LaucoPDOStatement')) {
    final class LaucoPDOStatement extends PDOStatement
    {
        protected function __construct() {}

        public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
        {
            return $this->translateRow(parent::fetch($mode, $cursorOrientation, $cursorOffset));
        }

        public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array
        {
            $rows = parent::fetchAll($mode, ...$args);
            if (!$this->shouldTranslate()) {
                return $rows;
            }
            return array_map(fn ($row) => $this->translateRow($row), $rows);
        }

        private function shouldTranslate(): bool
        {
            $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
            return !str_contains($script, '/admin/') && content_language_from_request() !== 'it';
        }

        private function translateRow(mixed $row): mixed
        {
            if (!is_array($row) || !$this->shouldTranslate() || empty($row['id'])) {
                return $row;
            }
            $query = strtolower($this->queryString);
            $entityType = match (true) {
                (bool) preg_match('/\bfrom\s+`?percorsi`?\b/', $query) => 'percorso',
                (bool) preg_match('/\bfrom\s+`?eventi`?\b/', $query) => 'evento',
                (bool) preg_match('/\bfrom\s+`?luoghi`?\b/', $query) => 'luogo',
                (bool) preg_match('/\bfrom\s+`?galleria`?\b/', $query) => 'galleria',
                (bool) preg_match('/\bfrom\s+`?home_slider`?\b/', $query) => 'slider',
                default => null,
            };
            if ($entityType === null) {
                return $row;
            }
            global $pdo;
            return $pdo instanceof PDO
                ? content_apply_translation($pdo, $entityType, $row, content_language_from_request())
                : $row;
        }
    }
}

date_default_timezone_set(lauco_env('APP_TIMEZONE', 'Europe/Rome') ?: 'Europe/Rome');

try {
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        lauco_env_required('DB_HOST'),
        lauco_env_int('DB_PORT', 3306),
        lauco_env_required('DB_NAME')
    );
    $pdo = new PDO(
        $dsn,
        lauco_env_required('DB_USER'),
        lauco_env_required('DB_PASS'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_STRINGIFY_FETCHES => false,
            PDO::ATTR_TIMEOUT => max(1, lauco_env_int('DB_CONNECT_TIMEOUT_SECONDS', 5)),
        ]
    );
    $pdo->setAttribute(PDO::ATTR_STATEMENT_CLASS, [LaucoPDOStatement::class]);
} catch (Throwable $e) {
    error_log('[Lauco DB] ' . $e->getMessage());
    http_response_code(500);

    $debug = lauco_env_bool('APP_DEBUG');
    $message = $debug
        ? 'Errore database: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
        : 'Configurazione o connessione database non disponibile.';

    if (PHP_SAPI !== 'cli' && headers_sent()) {
        echo '<style id="lauco-bootstrap-error">#myloader{display:none!important}</style>';
        echo '<div role="alert" style="max-width:900px;margin:140px auto 40px;padding:24px;font-family:Arial,sans-serif;background:#fff;color:#222">';
        echo '<h1 style="margin-top:0">Sito temporaneamente non disponibile</h1>';
        echo '<p>' . $message . '</p>';
        echo '</div>';
        exit;
    }

    exit($message);
}

if (!function_exists('e')) {
    function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('slugify')) {
    function slugify(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $ascii = is_string($ascii) ? $ascii : $text;
        $ascii = strtolower($ascii);
        $ascii = preg_replace('/[^a-z0-9]+/', '-', $ascii) ?? '';
        return trim($ascii, '-');
    }
}

if (!function_exists('unique_slug')) {
    function unique_slug(PDO $pdo, string $title, ?int $excludeId = null): string
    {
        $base = slugify($title) ?: 'percorso';
        $slug = $base;
        $counter = 2;
        while (true) {
            $sql = 'SELECT COUNT(*) FROM percorsi WHERE slug = :slug';
            $params = ['slug' => $slug];
            if ($excludeId !== null) {
                $sql .= ' AND id <> :id';
                $params['id'] = $excludeId;
            }
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            if ((int) $stmt->fetchColumn() === 0) {
                return $slug;
            }
            $slug = $base . '-' . $counter++;
        }
    }
}

if (!function_exists('fmt_it')) {
    function fmt_it($value, string $suffix = '', int $decimals = 0): string
    {
        if ($value === null || $value === '' || !is_numeric($value)) {
            return '-';
        }
        return number_format((float) $value, $decimals, ',', '.') . $suffix;
    }
}
