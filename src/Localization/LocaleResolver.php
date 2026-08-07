<?php
declare(strict_types=1);

namespace LaucoExperience\Localization;

use Psr\Http\Message\ServerRequestInterface;

final class LocaleResolver
{
    public const DEFAULT_LOCALE = 'it';

    /** @var array<string,string> */
    public const LOCALES = [
        'it' => 'Italiano',
        'en' => 'English',
        'de' => 'Deutsch',
        'sl' => 'Slovenščina',
    ];

    public function resolve(ServerRequestInterface $request): string
    {
        $query = $request->getQueryParams();
        $requested = strtolower(trim((string) ($query['lang'] ?? '')));
        if (isset(self::LOCALES[$requested])) {
            return $requested;
        }

        $cookies = $request->getCookieParams();
        $stored = strtolower(trim((string) ($cookies['lauco_lang'] ?? '')));
        if (isset(self::LOCALES[$stored])) {
            return $stored;
        }

        $accepted = strtolower($request->getHeaderLine('Accept-Language'));
        foreach (preg_split('/\s*,\s*/', $accepted) ?: [] as $candidate) {
            $code = substr(trim(explode(';', $candidate, 2)[0]), 0, 2);
            if (isset(self::LOCALES[$code])) {
                return $code;
            }
        }

        return self::DEFAULT_LOCALE;
    }

    public function shouldPersist(ServerRequestInterface $request): bool
    {
        $requested = strtolower(trim((string) ($request->getQueryParams()['lang'] ?? '')));
        return isset(self::LOCALES[$requested]);
    }

    public function cookieHeader(string $locale, bool $secure): string
    {
        $parts = [
            'lauco_lang=' . rawurlencode($locale),
            'Path=/',
            'Max-Age=31536000',
            'SameSite=Lax',
        ];
        if ($secure) {
            $parts[] = 'Secure';
        }
        return implode('; ', $parts);
    }
}
