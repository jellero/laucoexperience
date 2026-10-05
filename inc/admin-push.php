<?php
declare(strict_types=1);

/**
 * Web Push del backoffice senza dipendenze esterne.
 *
 * I push inviati dal server sono volutamente senza payload: il Service Worker
 * mostra un messaggio generico e porta l'utente alla dashboard. In questo modo
 * non serve implementare la cifratura del payload e le subscription restano
 * compatibili con Safari/iOS, Chromium/Android e Firefox.
 */

if (!function_exists('admin_push_base64url_encode')) {
    function admin_push_base64url_encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

if (!function_exists('admin_push_base64url_decode')) {
    function admin_push_base64url_decode(string $value): string|false
    {
        $value = strtr(trim($value), '-_', '+/');
        $padding = strlen($value) % 4;
        if ($padding !== 0) {
            $value .= str_repeat('=', 4 - $padding);
        }
        return base64_decode($value, true);
    }
}

if (!function_exists('admin_push_normalize_coordinate')) {
    function admin_push_normalize_coordinate(string $value, int $length = 32): string
    {
        $value = ltrim($value, "\x00");
        if (strlen($value) > $length) {
            $value = substr($value, -$length);
        }
        return str_pad($value, $length, "\x00", STR_PAD_LEFT);
    }
}

if (!function_exists('admin_push_generate_vapid_keypair')) {
    /** @return array{public:string,private:string} */
    function admin_push_generate_vapid_keypair(): array
    {
        if (!extension_loaded('openssl')) {
            throw new RuntimeException('Estensione OpenSSL non disponibile.');
        }

        $key = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);
        if ($key === false) {
            throw new RuntimeException('Impossibile generare la chiave VAPID.');
        }

        $details = openssl_pkey_get_details($key);
        $ec = is_array($details) ? ($details['ec'] ?? null) : null;
        if (!is_array($ec) || !isset($ec['x'], $ec['y'], $ec['d'])) {
            throw new RuntimeException('OpenSSL non espone i parametri EC necessari.');
        }

        $x = admin_push_normalize_coordinate((string) $ec['x']);
        $y = admin_push_normalize_coordinate((string) $ec['y']);
        $private = admin_push_normalize_coordinate((string) $ec['d']);
        $public = "\x04" . $x . $y;

        return [
            'public' => admin_push_base64url_encode($public),
            'private' => admin_push_base64url_encode($private),
        ];
    }
}

if (!function_exists('admin_push_vapid_keypair')) {
    /** @return array{public:string,private:string} */
    function admin_push_vapid_keypair(PDO $pdo): array
    {
        $stmt = $pdo->query('SELECT vapid_public_key, vapid_private_key FROM admin_push_settings WHERE id = 1 LIMIT 1');
        $row = $stmt->fetch();
        if (is_array($row) && !empty($row['vapid_public_key']) && !empty($row['vapid_private_key'])) {
            return [
                'public' => (string) $row['vapid_public_key'],
                'private' => (string) $row['vapid_private_key'],
            ];
        }

        $generated = admin_push_generate_vapid_keypair();
        $insert = $pdo->prepare(
            'INSERT IGNORE INTO admin_push_settings (id, vapid_public_key, vapid_private_key) VALUES (1, :public, :private)'
        );
        $insert->execute([
            'public' => $generated['public'],
            'private' => $generated['private'],
        ]);

        $stmt = $pdo->query('SELECT vapid_public_key, vapid_private_key FROM admin_push_settings WHERE id = 1 LIMIT 1');
        $row = $stmt->fetch();
        if (!is_array($row) || empty($row['vapid_public_key']) || empty($row['vapid_private_key'])) {
            throw new RuntimeException('Configurazione VAPID non disponibile.');
        }

        return [
            'public' => (string) $row['vapid_public_key'],
            'private' => (string) $row['vapid_private_key'],
        ];
    }
}

if (!function_exists('admin_push_endpoint_allowed')) {
    function admin_push_endpoint_allowed(string $endpoint): bool
    {
        $parts = parse_url($endpoint);
        if (!is_array($parts) || strtolower((string) ($parts['scheme'] ?? '')) !== 'https') {
            return false;
        }

        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
        if ($host === '') {
            return false;
        }

        if ($host === 'fcm.googleapis.com' || $host === 'updates.push.services.mozilla.com') {
            return true;
        }

        foreach (['.push.apple.com', '.push.services.mozilla.com', '.notify.windows.com'] as $suffix) {
            if (str_ends_with($host, $suffix) && strlen($host) > strlen($suffix)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('admin_push_validate_subscription')) {
    /** @param array<string,mixed> $subscription */
    function admin_push_validate_subscription(array $subscription): array
    {
        $endpoint = trim((string) ($subscription['endpoint'] ?? ''));
        $keys = is_array($subscription['keys'] ?? null) ? $subscription['keys'] : [];
        $p256dh = trim((string) ($keys['p256dh'] ?? ''));
        $auth = trim((string) ($keys['auth'] ?? ''));

        if ($endpoint === '' || strlen($endpoint) > 4096 || !admin_push_endpoint_allowed($endpoint)) {
            throw new InvalidArgumentException('Endpoint push non valido.');
        }

        $publicKey = admin_push_base64url_decode($p256dh);
        $authKey = admin_push_base64url_decode($auth);
        if ($publicKey === false || strlen($publicKey) !== 65 || $publicKey[0] !== "\x04") {
            throw new InvalidArgumentException('Chiave p256dh non valida.');
        }
        if ($authKey === false || strlen($authKey) < 16 || strlen($authKey) > 32) {
            throw new InvalidArgumentException('Chiave auth non valida.');
        }

        $platform = strtolower(trim((string) ($subscription['platform'] ?? 'web')));
        if (!in_array($platform, ['ios', 'android', 'desktop', 'web'], true)) {
            $platform = 'web';
        }

        return [
            'endpoint' => $endpoint,
            'p256dh' => $p256dh,
            'auth' => $auth,
            'platform' => $platform,
        ];
    }
}

if (!function_exists('admin_push_store_subscription')) {
    /** @param array<string,mixed> $subscription */
    function admin_push_store_subscription(PDO $pdo, int $adminId, array $subscription, string $userAgent = ''): string
    {
        if ($adminId <= 0) {
            throw new InvalidArgumentException('Utente amministrativo non valido.');
        }

        $data = admin_push_validate_subscription($subscription);
        $endpointHash = hash('sha256', $data['endpoint']);
        $stmt = $pdo->prepare(
            'INSERT INTO admin_push_subscriptions
                (admin_id, endpoint, endpoint_hash, p256dh, auth, platform, user_agent)
             VALUES
                (:admin_id, :endpoint, :endpoint_hash, :p256dh, :auth, :platform, :user_agent)
             ON DUPLICATE KEY UPDATE
                admin_id = VALUES(admin_id),
                endpoint = VALUES(endpoint),
                p256dh = VALUES(p256dh),
                auth = VALUES(auth),
                platform = VALUES(platform),
                user_agent = VALUES(user_agent),
                failure_count = 0,
                last_failure_at = NULL,
                updated_at = CURRENT_TIMESTAMP'
        );
        $stmt->execute([
            'admin_id' => $adminId,
            'endpoint' => $data['endpoint'],
            'endpoint_hash' => $endpointHash,
            'p256dh' => $data['p256dh'],
            'auth' => $data['auth'],
            'platform' => $data['platform'],
            'user_agent' => mb_substr($userAgent, 0, 500),
        ]);

        return $endpointHash;
    }
}

if (!function_exists('admin_push_remove_subscription')) {
    function admin_push_remove_subscription(PDO $pdo, int $adminId, string $endpoint): void
    {
        $stmt = $pdo->prepare('DELETE FROM admin_push_subscriptions WHERE admin_id = :admin_id AND endpoint_hash = :endpoint_hash');
        $stmt->execute([
            'admin_id' => $adminId,
            'endpoint_hash' => hash('sha256', $endpoint),
        ]);
    }
}

if (!function_exists('admin_push_ec_private_pem')) {
    function admin_push_ec_private_pem(string $publicKey, string $privateKey): string
    {
        $public = admin_push_base64url_decode($publicKey);
        $private = admin_push_base64url_decode($privateKey);
        if ($public === false || strlen($public) !== 65 || $private === false || strlen($private) !== 32) {
            throw new RuntimeException('Chiavi VAPID corrotte.');
        }

        // RFC 5915 ECPrivateKey per prime256v1/P-256.
        $der = hex2bin('30770201010420')
            . $private
            . hex2bin('a00a06082a8648ce3d030107a144034200')
            . $public;
        if (!is_string($der)) {
            throw new RuntimeException('Impossibile costruire la chiave privata VAPID.');
        }

        return "-----BEGIN EC PRIVATE KEY-----\n"
            . chunk_split(base64_encode($der), 64, "\n")
            . "-----END EC PRIVATE KEY-----\n";
    }
}

if (!function_exists('admin_push_der_length')) {
    function admin_push_der_length(string $der, int &$offset): int
    {
        if (!isset($der[$offset])) {
            throw new RuntimeException('Firma ECDSA non valida.');
        }
        $first = ord($der[$offset++]);
        if (($first & 0x80) === 0) {
            return $first;
        }
        $count = $first & 0x7f;
        if ($count < 1 || $count > 4 || strlen($der) < $offset + $count) {
            throw new RuntimeException('Firma ECDSA non valida.');
        }
        $length = 0;
        for ($i = 0; $i < $count; $i++) {
            $length = ($length << 8) | ord($der[$offset++]);
        }
        return $length;
    }
}

if (!function_exists('admin_push_der_integer')) {
    function admin_push_der_integer(string $der, int &$offset): string
    {
        if (!isset($der[$offset]) || ord($der[$offset++]) !== 0x02) {
            throw new RuntimeException('Firma ECDSA non valida.');
        }
        $length = admin_push_der_length($der, $offset);
        $value = substr($der, $offset, $length);
        $offset += $length;
        $value = ltrim($value, "\x00");
        if ($value === '' || strlen($value) > 32) {
            throw new RuntimeException('Firma ECDSA non valida.');
        }
        return str_pad($value, 32, "\x00", STR_PAD_LEFT);
    }
}

if (!function_exists('admin_push_ecdsa_der_to_raw')) {
    function admin_push_ecdsa_der_to_raw(string $der): string
    {
        $offset = 0;
        if (!isset($der[$offset]) || ord($der[$offset++]) !== 0x30) {
            throw new RuntimeException('Firma ECDSA non valida.');
        }
        $sequenceLength = admin_push_der_length($der, $offset);
        if ($sequenceLength <= 0 || strlen($der) < $offset + $sequenceLength) {
            throw new RuntimeException('Firma ECDSA non valida.');
        }
        return admin_push_der_integer($der, $offset) . admin_push_der_integer($der, $offset);
    }
}

if (!function_exists('admin_push_audience')) {
    function admin_push_audience(string $endpoint): string
    {
        $parts = parse_url($endpoint);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            throw new InvalidArgumentException('Endpoint push non valido.');
        }
        $audience = strtolower((string) $parts['scheme']) . '://' . strtolower((string) $parts['host']);
        if (!empty($parts['port']) && !in_array((int) $parts['port'], [80, 443], true)) {
            $audience .= ':' . (int) $parts['port'];
        }
        return $audience;
    }
}

if (!function_exists('admin_push_vapid_authorization')) {
    function admin_push_vapid_authorization(string $endpoint, string $publicKey, string $privateKey): string
    {
        $subject = trim(lauco_env('PUSH_VAPID_SUBJECT', 'mailto:postmaster@laucoexperience.it'));
        if ($subject === '' || (!str_starts_with($subject, 'mailto:') && !str_starts_with($subject, 'https://'))) {
            $subject = 'mailto:postmaster@laucoexperience.it';
        }

        $header = admin_push_base64url_encode(json_encode(['typ' => 'JWT', 'alg' => 'ES256'], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $payload = admin_push_base64url_encode(json_encode([
            'aud' => admin_push_audience($endpoint),
            'exp' => time() + 12 * 60 * 60,
            'sub' => $subject,
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        $unsigned = $header . '.' . $payload;

        $key = openssl_pkey_get_private(admin_push_ec_private_pem($publicKey, $privateKey));
        if ($key === false) {
            throw new RuntimeException('Chiave privata VAPID non leggibile.');
        }
        $signatureDer = '';
        if (!openssl_sign($unsigned, $signatureDer, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Firma VAPID non riuscita.');
        }
        $signature = admin_push_base64url_encode(admin_push_ecdsa_der_to_raw($signatureDer));

        return 'vapid t=' . $unsigned . '.' . $signature . ', k=' . $publicKey;
    }
}

if (!function_exists('admin_push_deliver_endpoint')) {
    /** @return array{ok:bool,status:int,error:string} */
    function admin_push_deliver_endpoint(PDO $pdo, string $endpoint): array
    {
        if (!admin_push_endpoint_allowed($endpoint)) {
            return ['ok' => false, 'status' => 0, 'error' => 'Endpoint non consentito'];
        }

        $keys = admin_push_vapid_keypair($pdo);
        $authorization = admin_push_vapid_authorization($endpoint, $keys['public'], $keys['private']);
        $curl = curl_init($endpoint);
        if ($curl === false) {
            return ['ok' => false, 'status' => 0, 'error' => 'Impossibile inizializzare cURL'];
        }

        curl_setopt_array($curl, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => '',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'TTL: 300',
                'Urgency: high',
                'Authorization: ' . $authorization,
                'Content-Length: 0',
            ],
        ]);
        $response = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = $response === false ? curl_error($curl) : '';
        curl_close($curl);

        return [
            'ok' => $status >= 200 && $status < 300,
            'status' => $status,
            'error' => $error,
        ];
    }
}

if (!function_exists('admin_push_send_to_admin')) {
    /** @return array{sent:int,failed:int,removed:int} */
    function admin_push_send_to_admin(PDO $pdo, int $adminId): array
    {
        $stmt = $pdo->prepare('SELECT id, endpoint FROM admin_push_subscriptions WHERE admin_id = :admin_id ORDER BY id');
        $stmt->execute(['admin_id' => $adminId]);
        $rows = $stmt->fetchAll() ?: [];
        $result = ['sent' => 0, 'failed' => 0, 'removed' => 0];

        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $endpoint = (string) ($row['endpoint'] ?? '');
            if ($id <= 0 || $endpoint === '') {
                continue;
            }

            try {
                $delivery = admin_push_deliver_endpoint($pdo, $endpoint);
            } catch (Throwable $exception) {
                error_log('[Lauco Push] ' . $exception->getMessage());
                $delivery = ['ok' => false, 'status' => 0, 'error' => $exception->getMessage()];
            }

            if ($delivery['ok']) {
                $result['sent']++;
                $update = $pdo->prepare('UPDATE admin_push_subscriptions SET failure_count = 0, last_success_at = NOW() WHERE id = :id');
                $update->execute(['id' => $id]);
                continue;
            }

            if (in_array($delivery['status'], [404, 410], true)) {
                $delete = $pdo->prepare('DELETE FROM admin_push_subscriptions WHERE id = :id');
                $delete->execute(['id' => $id]);
                $result['removed']++;
                continue;
            }

            $result['failed']++;
            $update = $pdo->prepare(
                'UPDATE admin_push_subscriptions SET failure_count = failure_count + 1, last_failure_at = NOW() WHERE id = :id'
            );
            $update->execute(['id' => $id]);
        }

        return $result;
    }
}
