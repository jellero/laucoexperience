<?php
declare(strict_types=1);

namespace LaucoExperience\Localization;

final class HtmlLocalizer
{
    public function __construct(private readonly SiteCatalogRepository $catalogs)
    {
    }

    public function localize(string $html, string $locale): string
    {
        // The versioned Italian catalog is the stable lookup source. Runtime
        // overrides (including Italian edits) are always the rendered target.
        $source = $this->catalogs->loadDefault(LocaleResolver::DEFAULT_LOCALE);
        $target = $this->catalogs->load($locale);
        $replacements = [];
        foreach ($source as $key => $italian) {
            $translated = trim((string) ($target[$key] ?? ''));
            if ($italian !== '' && $translated !== '' && $translated !== $italian) {
                $replacements[$italian] = $translated;
            }
        }
        uksort($replacements, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        $patterns = [];
        if ($replacements !== []) {
            foreach (array_chunk(array_keys($replacements), 40) as $values) {
                $alternatives = array_map(
                    static function (string $value): string {
                        $parts = preg_split('/\\s+/u', trim($value)) ?: [$value];
                        return implode(
                            '(?:\\s|&nbsp;)+',
                            array_map(
                                static fn (string $part): string => $part === '©' ? '(?:©|&copy;)' : preg_quote($part, '~'),
                                $parts
                            )
                        );
                    },
                    $values
                );
                $patterns[] = '~(?<![\\p{L}\\p{N}])(?:' . implode('|', $alternatives) . ')(?![\\p{L}\\p{N}])~u';
            }
        }

        $replace = static function (string $value, bool $attribute = false) use ($patterns, $replacements): string {
            $replacementTokens = [];
            foreach ($patterns as $pattern) {
                $value = preg_replace_callback(
                    $pattern,
                    static function (array $match) use ($replacements, $attribute, &$replacementTokens): string {
                        $decoded = html_entity_decode((string) $match[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        $normalized = preg_replace('/\\s+/u', ' ', trim($decoded)) ?? $decoded;
                        if (!isset($replacements[$normalized])) {
                            return (string) $match[0];
                        }
                        $token = "\x1A" . count($replacementTokens) . "\x1A";
                        $replacementTokens[$token] = htmlspecialchars(
                            $replacements[$normalized],
                            ($attribute ? ENT_QUOTES : ENT_NOQUOTES) | ENT_SUBSTITUTE | ENT_HTML5,
                            'UTF-8'
                        );
                        return $token;
                    },
                    $value
                ) ?? $value;
            }
            return $replacementTokens === [] ? $value : strtr($value, $replacementTokens);
        };

        $tokens = preg_split(
            '#(<(?:script|style|pre)\\b[^>]*>.*?</(?:script|style|pre)>|<[^>]+>)#is',
            $html,
            -1,
            PREG_SPLIT_DELIM_CAPTURE
        );
        if (!is_array($tokens)) {
            return $html;
        }

        foreach ($tokens as $index => $token) {
            if ($token === '' || $token[0] !== '<') {
                $tokens[$index] = $replace($token);
                continue;
            }
            if (preg_match('#^<(?:script|style|pre)\\b#i', $token)) {
                continue;
            }
            $tokens[$index] = preg_replace_callback(
                "/\\b(placeholder|title|aria-label|value)=([\"'])(.*?)\\2/is",
                static fn (array $match): string => $match[1] . '=' . $match[2] . $replace($match[3], true) . $match[2],
                $token
            ) ?? $token;
        }

        $localized = implode('', $tokens);
        return preg_replace(
            "/<html\\b([^>]*?)\\blang=([\"'])(?:en|it|de|sl)\\2/i",
            '<html$1lang="' . $locale . '"',
            $localized
        ) ?? $localized;
    }
}
