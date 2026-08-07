<?php
declare(strict_types=1);

namespace LaucoExperience\Localization;

use JsonException;
use RuntimeException;

final class SiteCatalogRepository
{
    public function __construct(
        private readonly string $defaultsDirectory,
        private readonly string $runtimeDirectory,
    ) {
    }

    /** @return array<string,string> */
    public function load(string $locale): array
    {
        $this->assertLocale($locale);
        $defaults = $this->loadDefault($locale);
        $runtimePath = $this->runtimePath($locale);
        if (!is_file($runtimePath)) {
            return $defaults;
        }

        return array_replace($defaults, $this->read($runtimePath));
    }

    /** @return array<string,string> */
    public function loadDefault(string $locale): array
    {
        $this->assertLocale($locale);
        return $this->read($this->defaultPath($locale));
    }

    /** @return array<string,array<string,string>> */
    public function loadAll(): array
    {
        $catalogs = [];
        foreach (array_keys(LocaleResolver::LOCALES) as $locale) {
            $catalogs[$locale] = $this->load($locale);
        }
        return $catalogs;
    }

    /** @param array<string,array<string,string>> $catalogs */
    public function saveAll(array $catalogs): void
    {
        $expectedLocales = array_keys(LocaleResolver::LOCALES);
        if (array_keys($catalogs) !== $expectedLocales) {
            throw new RuntimeException('Il salvataggio deve contenere esattamente i cataloghi IT, EN, DE e SL.');
        }

        $normalized = [];
        foreach ($expectedLocales as $locale) {
            $normalized[$locale] = $this->normalize($catalogs[$locale]);
        }
        $sourceKeys = array_keys($normalized['it']);
        foreach ($expectedLocales as $locale) {
            if (array_keys($normalized[$locale]) !== $sourceKeys) {
                throw new RuntimeException("Le chiavi del catalogo {$locale} non coincidono con il catalogo italiano.");
            }
        }
        foreach ($expectedLocales as $locale) {
            $this->write($locale, $normalized[$locale]);
        }
    }

    /** @param array<string,string> $catalog */
    public function save(string $locale, array $catalog): void
    {
        $this->assertLocale($locale);
        $normalized = $this->normalize($catalog);
        if ($locale !== 'it' && array_keys($normalized) !== array_keys($this->load('it'))) {
            throw new RuntimeException("Le chiavi del catalogo {$locale} non coincidono con il catalogo italiano.");
        }
        $this->write($locale, $normalized);
    }

    /** @param array<string,array<string,string>> $catalogs */
    public function revision(array $catalogs): string
    {
        return hash('sha256', json_encode($catalogs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private function defaultPath(string $locale): string
    {
        return $this->defaultsDirectory . '/site.' . $locale . '.json';
    }

    private function runtimePath(string $locale): string
    {
        return $this->runtimeDirectory . '/site.' . $locale . '.json';
    }

    /** @return array<string,string> */
    private function read(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException('Catalogo non disponibile: ' . $path);
        }
        try {
            $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('Catalogo JSON non valido: ' . $path, 0, $e);
        }
        if (!is_array($decoded)) {
            throw new RuntimeException('Catalogo JSON non valido: ' . $path);
        }
        return $this->normalize($decoded);
    }

    /** @param array<mixed,mixed> $catalog @return array<string,string> */
    private function normalize(array $catalog): array
    {
        $normalized = [];
        foreach ($catalog as $key => $value) {
            $key = trim((string) $key);
            if ($key === '' || !preg_match('/^[a-z0-9][a-z0-9_.-]*$/', $key)) {
                throw new RuntimeException('Chiave catalogo non valida: ' . $key);
            }
            if (!is_scalar($value) && $value !== null) {
                throw new RuntimeException('Valore catalogo non testuale per la chiave: ' . $key);
            }
            $normalized[$key] = trim((string) $value);
        }
        ksort($normalized, SORT_STRING);
        return $normalized;
    }

    /** @param array<string,string> $catalog */
    private function write(string $locale, array $catalog): void
    {
        if (!is_dir($this->runtimeDirectory) && !mkdir($this->runtimeDirectory, 0770, true) && !is_dir($this->runtimeDirectory)) {
            throw new RuntimeException('Impossibile creare la directory dei cataloghi runtime.');
        }

        $path = $this->runtimePath($locale);
        if (is_file($path)) {
            @copy($path, $path . '.bak');
        }
        $json = json_encode($catalog, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
        if (file_put_contents($path, $json, LOCK_EX) === false) {
            throw new RuntimeException('Impossibile salvare il catalogo: ' . $path);
        }
        @chmod($path, 0660);
    }

    private function assertLocale(string $locale): void
    {
        if (!isset(LocaleResolver::LOCALES[$locale])) {
            throw new RuntimeException('Lingua non supportata: ' . $locale);
        }
    }
}
