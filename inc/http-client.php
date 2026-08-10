<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

if (!function_exists('lauco_http_assert_url')) {
    /** @param list<string> $allowedHosts */
    function lauco_http_assert_url(string $url, array $allowedHosts): void
    {
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'https' || $host === '') {
            throw new RuntimeException('Sono consentiti esclusivamente URL HTTPS.');
        }

        $allowed = false;
        foreach ($allowedHosts as $allowedHost) {
            $allowedHost = strtolower(trim($allowedHost));
            if ($host === $allowedHost || str_ends_with($host, '.' . $allowedHost)) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            throw new RuntimeException('Host non consentito dalla configurazione.');
        }
    }
}

if (!function_exists('lauco_http_resolve_url')) {
    function lauco_http_resolve_url(string $baseUrl, string $location): string
    {
        $location = trim($location);
        if ($location === '') {
            throw new RuntimeException('Redirect privo di destinazione.');
        }
        if (preg_match('~^https://~i', $location)) {
            return $location;
        }
        if (str_starts_with($location, '//')) {
            return 'https:' . $location;
        }

        $base = parse_url($baseUrl);
        if (!isset($base['scheme'], $base['host'])) {
            throw new RuntimeException('URL base non valido.');
        }

        $port = isset($base['port']) ? ':' . $base['port'] : '';
        $origin = $base['scheme'] . '://' . $base['host'] . $port;
        if (str_starts_with($location, '/')) {
            return $origin . $location;
        }

        $path = (string) ($base['path'] ?? '/');
        $directory = rtrim(str_replace('\\', '/', dirname($path)), '/');
        return $origin . ($directory !== '' ? $directory : '') . '/' . $location;
    }
}

if (!function_exists('lauco_http_request')) {
    /**
     * @param list<string> $headers
     * @return array{status:int,body:string,headers:array<string,string>}
     */
    function lauco_http_request(string $method, string $url, array $headers = [], ?string $body = null, int $timeout = 30): array
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('L’estensione cURL non è disponibile.');
        }

        $responseHeaders = [];
        $maxBytes = max(1024, lauco_env_int('HTTP_MAX_RESPONSE_BYTES', 5_000_000));
        $received = 0;
        $responseBody = '';
        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('Impossibile inizializzare cURL.');
        }

        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => max(5, $timeout),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERAGENT => lauco_env('HTTP_USER_AGENT', 'LaucoExperience/1.0 (+https://laucoexperience.it)'),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADERFUNCTION => static function ($handle, string $line) use (&$responseHeaders): int {
                $length = strlen($line);
                if (str_contains($line, ':')) {
                    [$name, $value] = explode(':', $line, 2);
                    $responseHeaders[strtolower(trim($name))] = trim($value);
                }
                return $length;
            },
            CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$responseBody, &$received, $maxBytes): int {
                $received += strlen($chunk);
                if ($received > $maxBytes) {
                    return 0;
                }
                $responseBody .= $chunk;
                return strlen($chunk);
            },
        ]);

        if ($body !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $ok = curl_exec($curl);
        if ($ok === false) {
            $message = curl_error($curl);
            curl_close($curl);
            if ($received > $maxBytes) {
                throw new RuntimeException('La risposta remota supera il limite consentito.');
            }
            throw new RuntimeException('Errore HTTP: ' . $message);
        }

        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        return ['status' => $status, 'body' => $responseBody, 'headers' => $responseHeaders];
    }
}

if (!function_exists('lauco_http_get_allowlisted')) {
    /** @param list<string> $allowedHosts */
    function lauco_http_get_allowlisted(string $url, array $allowedHosts, int $timeout = 30): string
    {
        $currentUrl = $url;
        $redirects = 0;
        $maxRedirects = max(0, min(8, lauco_env_int('HTTP_MAX_REDIRECTS', 5)));
        $headers = [
            'Accept: text/html,application/ld+json,application/json;q=0.9,*/*;q=0.8',
            'Accept-Language: it-IT,it;q=0.9,en;q=0.6',
        ];

        while (true) {
            lauco_http_assert_url($currentUrl, $allowedHosts);
            $response = lauco_http_request('GET', $currentUrl, $headers, null, $timeout);

            if ($response['status'] >= 200 && $response['status'] < 300) {
                return $response['body'];
            }

            if (
                in_array($response['status'], [301, 302, 303, 307, 308], true)
                && isset($response['headers']['location'])
            ) {
                if ($redirects >= $maxRedirects) {
                    throw new RuntimeException('La fonte ha superato il numero massimo di redirect consentiti.');
                }
                $currentUrl = lauco_http_resolve_url($currentUrl, $response['headers']['location']);
                lauco_http_assert_url($currentUrl, $allowedHosts);
                $redirects++;
                continue;
            }

            throw new RuntimeException('La fonte ha restituito HTTP ' . $response['status'] . '.');
        }
    }
}

if (!function_exists('lauco_http_post_json')) {
    /** @param array<string,mixed> $payload @param list<string> $headers */
    function lauco_http_post_json(string $url, array $payload, array $headers = [], int $timeout = 90): array
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        return lauco_http_request('POST', $url, array_merge(['Content-Type: application/json'], $headers), $encoded, $timeout);
    }
}
