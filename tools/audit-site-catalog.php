<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$locales = ['it', 'en', 'de', 'sl'];
$catalogs = [];
$errors = [];

foreach ($locales as $locale) {
    $paths = [$root . '/resources/lang/site.' . $locale . '.json'];
    $featurePaths = glob($root . '/resources/lang/feature-*.' . $locale . '.json') ?: [];
    sort($featurePaths, SORT_STRING);
    $paths = array_merge($paths, $featurePaths);

    $catalog = [];
    foreach ($paths as $path) {
        if (!is_file($path)) {
            continue;
        }
        try {
            $part = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable $e) {
            $errors[] = "{$locale}: JSON non valido in " . basename($path) . " ({$e->getMessage()})";
            continue;
        }
        if (!is_array($part)) {
            $errors[] = "{$locale}: " . basename($path) . " non è un oggetto JSON";
            continue;
        }
        $catalog = array_replace($catalog, $part);
    }

    ksort($catalog, SORT_STRING);
    foreach ($catalog as $key => $value) {
        if (!is_string($key) || !preg_match('/^[a-z0-9][a-z0-9_.-]*$/', $key)) {
            $errors[] = "{$locale}: chiave non valida " . (string) $key;
        }
        if (!is_string($value) || trim($value) === '') {
            $errors[] = "{$locale}: valore vuoto o non testuale per " . (string) $key;
        }
    }
    $catalogs[$locale] = $catalog;
}

if (isset($catalogs['it'])) {
    $sourceKeys = array_keys($catalogs['it']);
    foreach ($locales as $locale) {
        if (isset($catalogs[$locale]) && array_keys($catalogs[$locale]) !== $sourceKeys) {
            $errors[] = "{$locale}: le chiavi non coincidono con il catalogo italiano";
        }
    }

    $normalize = static fn (string $value): string => trim(preg_replace('/\s+/u', ' ', html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8')) ?? '');
    $known = array_fill_keys(array_map($normalize, array_values($catalogs['it'])), true);
    $files = array_merge(
        array_values(array_filter(
            glob($root . '/resources/views/pages/*.php') ?: [],
            static fn (string $path): bool => !in_array(basename($path), ['login.php', 'crea-account.php'], true)
        )),
        array_values(array_filter(
            glob($root . '/resources/views/sections/*.php') ?: [],
            static fn (string $path): bool => basename($path) !== 'mapok.php'
        )),
        array_map(
            static fn (string $name): string => $root . '/resources/views/partials/' . $name,
            ['header.php', 'menu.php', 'footer.php', 'footerf.php', 'scripts.php']
        )
    );

    foreach ($files as $path) {
        $source = (string) file_get_contents($path);
        $source = preg_replace('/<\?(?:php|=).*?(?:\?>|$)/s', ' ', $source) ?? $source;
        $source = preg_replace('#<(?:script|style)\b.*?</(?:script|style)>#is', ' ', $source) ?? $source;
        foreach (preg_split('/<[^>]+>/', $source) ?: [] as $raw) {
            $value = $normalize($raw);
            if (mb_strlen($value) >= 2 && preg_match('/[A-Za-zÀ-ž]/u', $value) && !isset($known[$value])) {
                $errors[] = str_replace('\\', '/', substr($path, strlen($root) + 1)) . ': testo non catalogato: ' . $value;
            }
        }
        if (preg_match_all('/\b(?:placeholder|title|aria-label|value)\s*=\s*([\'"])(.*?)\1/is', $source, $matches)) {
            foreach ($matches[2] as $raw) {
                $value = $normalize((string) $raw);
                if (mb_strlen($value) >= 2 && preg_match('/[A-Za-zÀ-ž]/u', $value) && !isset($known[$value])) {
                    $errors[] = str_replace('\\', '/', substr($path, strlen($root) + 1)) . ': attributo non catalogato: ' . $value;
                }
            }
        }
    }
}

if ($errors !== []) {
    fwrite(STDERR, implode(PHP_EOL, array_values(array_unique($errors))) . PHP_EOL);
    exit(1);
}

printf("Cataloghi sito verificati: %d chiavi complete in IT, EN, DE e SL.%s", count($catalogs['it'] ?? []), PHP_EOL);
