<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

if (!function_exists('volontariato_interessi')) {
    /** @return array<string,string> */
    function volontariato_interessi(): array
    {
        return [
            'sentieri' => 'Controllo e cura dei sentieri',
            'pulizia' => 'Giornate ecologiche e pulizia',
            'segnaletica' => 'Segnaletica e manutenzione leggera',
            'foto' => 'Fotografie e documentazione',
            'memoria' => 'Memoria e racconti locali',
            'eventi' => 'Supporto alle attività comunitarie',
        ];
    }
}

if (!function_exists('volontariato_zone')) {
    /** @return list<string> */
    function volontariato_zone(): array
    {
        return ['Lauco', 'Allegnidis', 'Avaglio', 'Buttea', 'Chiassis', 'Trava', 'Vinaio', 'Fuori comune'];
    }
}

if (!function_exists('volontariato_disponibilita')) {
    /** @return list<string> */
    function volontariato_disponibilita(): array
    {
        return ['Occasionale', 'Fine settimana', 'Giorni feriali', 'Da concordare'];
    }
}

if (!function_exists('volontariato_normalize_phone')) {
    function volontariato_normalize_phone(string $phone, string $defaultCountryCode = '39'): ?string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return null;
        }
        $hadPlus = str_starts_with($phone, '+');
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
            $hadPlus = true;
        }
        if (!$hadPlus && preg_match('/^3\d{8,9}$/', $digits)) {
            $digits = $defaultCountryCode . $digits;
        }
        if (!preg_match('/^[1-9]\d{8,14}$/', $digits)) {
            return null;
        }
        return '+' . $digits;
    }
}

if (!function_exists('volontariato_code')) {
    function volontariato_code(): string
    {
        return 'VOL-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }
}

if (!function_exists('volontariato_decode_interessi')) {
    /** @return list<string> */
    function volontariato_decode_interessi(?string $json): array
    {
        try {
            $items = json_decode((string) $json, true, 16, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return [];
        }
        if (!is_array($items)) {
            return [];
        }
        $allowed = volontariato_interessi();
        return array_values(array_filter(array_map('strval', $items), static fn (string $item): bool => isset($allowed[$item])));
    }
}

if (!function_exists('volontariato_status_label')) {
    function volontariato_status_label(string $status): string
    {
        return [
            'da_confermare' => 'Da invitare', 'invitato' => 'Invitato', 'attivo' => 'Attivo',
            'in_pausa' => 'In pausa', 'ritirato' => 'Ritirato',
            'assegnato' => 'Assegnato', 'invito_in_coda' => 'Invito in coda',
            'entrato' => 'Nel gruppo', 'uscito' => 'Uscito', 'errore' => 'Errore',
            'bozza' => 'Bozza', 'raccolta_adesioni' => 'Raccolta adesioni',
            'programmata' => 'Programmata', 'in_corso' => 'In corso',
            'completata' => 'Completata', 'annullata' => 'Annullata',
            'verificato' => 'Verificato', 'attenzione' => 'Attenzione',
            'non_percorribile' => 'Temporaneamente non percorribile', 'in_verifica' => 'Segnalazione in verifica',
            'configurazione_mancante' => 'Configurazione mancante', 'in_coda' => 'In coda',
            'inviato' => 'Inviato', 'fallito' => 'Fallito',
        ][$status] ?? ucfirst(str_replace('_', ' ', $status));
    }
}

if (!function_exists('volontariato_default_group')) {
    /** @return array<string,mixed>|null */
    function volontariato_default_group(PDO $pdo, ?string $zone): ?array
    {
        if ($zone !== null && trim($zone) !== '') {
            $stmt = $pdo->prepare(
                "SELECT * FROM volontari_gruppi WHERE attivo = 1 AND tipo = 'zona' AND LOWER(zona) = LOWER(:zona) ORDER BY predefinito DESC, id ASC LIMIT 1"
            );
            $stmt->execute(['zona' => trim($zone)]);
            $group = $stmt->fetch();
            if (is_array($group)) {
                return $group;
            }
        }
        $stmt = $pdo->query('SELECT * FROM volontari_gruppi WHERE attivo = 1 ORDER BY predefinito DESC, id ASC LIMIT 1');
        $group = $stmt->fetch();
        return is_array($group) ? $group : null;
    }
}

if (!function_exists('volontariato_whatsapp_ready')) {
    function volontariato_whatsapp_ready(bool $groups = false): bool
    {
        $basic = lauco_env_bool('WHATSAPP_ENABLED')
            && trim((string) lauco_env('WHATSAPP_ACCESS_TOKEN', '')) !== ''
            && trim((string) lauco_env('WHATSAPP_PHONE_NUMBER_ID', '')) !== '';
        return $basic && (!$groups || lauco_env_bool('WHATSAPP_GROUPS_ENABLED'));
    }
}

if (!function_exists('volontariato_whatsapp_request')) {
    /** @param array<string,mixed> $payload @return array<string,mixed> */
    function volontariato_whatsapp_request(array $payload): array
    {
        if (!volontariato_whatsapp_ready()) {
            throw new RuntimeException('WhatsApp Cloud API non configurata.');
        }
        $version = trim((string) lauco_env('WHATSAPP_GRAPH_VERSION', 'v26.0')) ?: 'v26.0';
        $phoneId = rawurlencode((string) lauco_env_required('WHATSAPP_PHONE_NUMBER_ID'));
        $url = 'https://graph.facebook.com/' . rawurlencode($version) . '/' . $phoneId . '/messages';
        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('Impossibile inizializzare la richiesta WhatsApp.');
        }
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => max(5, lauco_env_int('WHATSAPP_TIMEOUT_SECONDS', 20)),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . lauco_env_required('WHATSAPP_ACCESS_TOKEN'),
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        ]);
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        if (!is_string($body)) {
            throw new RuntimeException('Errore di rete WhatsApp: ' . ($error ?: 'risposta vuota'));
        }
        try {
            $decoded = json_decode($body, true, 64, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            $decoded = ['raw' => mb_substr($body, 0, 1000)];
        }
        if ($status < 200 || $status >= 300) {
            $message = is_array($decoded) ? (string) ($decoded['error']['message'] ?? 'HTTP ' . $status) : 'HTTP ' . $status;
            throw new RuntimeException('WhatsApp: ' . $message);
        }
        return is_array($decoded) ? $decoded : [];
    }
}

if (!function_exists('volontariato_queue_invite')) {
    function volontariato_queue_invite(PDO $pdo, int $volunteerId, int $groupId): int
    {
        $stmt = $pdo->prepare(
            'SELECT m.id AS membro_id, v.nome, v.telefono, g.nome AS gruppo_nome, g.invite_link '
            . 'FROM volontari_gruppi_membri m JOIN volontari v ON v.id = m.volontario_id '
            . 'JOIN volontari_gruppi g ON g.id = m.gruppo_id WHERE m.volontario_id = :volontario AND m.gruppo_id = :gruppo LIMIT 1'
        );
        $stmt->execute(['volontario' => $volunteerId, 'gruppo' => $groupId]);
        $row = $stmt->fetch();
        if (!is_array($row)) {
            throw new RuntimeException('Assegnazione al gruppo non trovata.');
        }
        $existing = $pdo->prepare(
            "SELECT id FROM whatsapp_outbox WHERE membro_id=:membro AND tipo='invito' AND stato IN ('configurazione_mancante','in_coda') ORDER BY id DESC LIMIT 1"
        );
        $existing->execute(['membro' => (int) $row['membro_id']]);
        $existingId = (int) $existing->fetchColumn();
        if ($existingId > 0) {
            return $existingId;
        }
        $template = trim((string) lauco_env('WHATSAPP_INVITE_TEMPLATE_NAME', ''));
        $ready = volontariato_whatsapp_ready() && $template !== '' && trim((string) $row['invite_link']) !== '';
        $payload = [
            'mode' => 'template',
            'template' => $template,
            'language' => trim((string) lauco_env('WHATSAPP_INVITE_TEMPLATE_LANGUAGE', 'it')) ?: 'it',
            'parameters' => [(string) $row['nome'], (string) $row['gruppo_nome'], (string) $row['invite_link']],
        ];
        $insert = $pdo->prepare(
            'INSERT INTO whatsapp_outbox (tipo,destinatario,payload_json,volontario_id,gruppo_id,membro_id,stato) '
            . 'VALUES (\'invito\',:destinatario,:payload,:volontario,:gruppo,:membro,:stato)'
        );
        $insert->execute([
            'destinatario' => ltrim((string) $row['telefono'], '+'),
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'volontario' => $volunteerId,
            'gruppo' => $groupId,
            'membro' => (int) $row['membro_id'],
            'stato' => $ready ? 'in_coda' : 'configurazione_mancante',
        ]);
        $outboxId = (int) $pdo->lastInsertId();
        $pdo->prepare('UPDATE volontari_gruppi_membri SET stato = :stato, ultimo_errore = :errore WHERE id = :id')->execute([
            'stato' => $ready ? 'invito_in_coda' : 'assegnato',
            'errore' => $ready ? null : 'Configura API, template e link di invito.',
            'id' => (int) $row['membro_id'],
        ]);
        return $outboxId;
    }
}

if (!function_exists('volontariato_queue_text')) {
    function volontariato_queue_text(PDO $pdo, string $type, string $recipient, string $text, ?int $volunteerId = null, ?int $groupId = null): int
    {
        if (!in_array($type, ['diretto', 'gruppo'], true)) {
            throw new InvalidArgumentException('Tipo messaggio non valido.');
        }
        $ready = volontariato_whatsapp_ready($type === 'gruppo');
        $stmt = $pdo->prepare(
            'INSERT INTO whatsapp_outbox (tipo,destinatario,payload_json,volontario_id,gruppo_id,stato) '
            . 'VALUES (:tipo,:destinatario,:payload,:volontario,:gruppo,:stato)'
        );
        $stmt->execute([
            'tipo' => $type,
            'destinatario' => ltrim($recipient, '+'),
            'payload' => json_encode(['mode' => 'text', 'text' => $text], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'volontario' => $volunteerId,
            'gruppo' => $groupId,
            'stato' => $ready ? 'in_coda' : 'configurazione_mancante',
        ]);
        return (int) $pdo->lastInsertId();
    }
}

if (!function_exists('volontariato_dispatch_outbox')) {
    /** @return array{sent:int,failed:int} */
    function volontariato_dispatch_outbox(PDO $pdo, ?int $onlyId = null, int $limit = 20): array
    {
        if (!volontariato_whatsapp_ready()) {
            return ['sent' => 0, 'failed' => 0];
        }
        volontariato_refresh_waiting_outbox($pdo);
        $sql = "SELECT * FROM whatsapp_outbox WHERE stato = 'in_coda' AND scheduled_at <= NOW()";
        $params = [];
        if ($onlyId !== null) {
            $sql .= ' AND id = :id';
            $params['id'] = $onlyId;
        }
        $sql .= ' ORDER BY id ASC LIMIT ' . max(1, min(100, $limit));
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $sent = 0;
        $failed = 0;
        foreach ($stmt->fetchAll() as $row) {
            try {
                $data = json_decode((string) $row['payload_json'], true, 32, JSON_THROW_ON_ERROR);
                if (($data['mode'] ?? '') === 'template') {
                    $parameters = array_map(static fn ($value): array => ['type' => 'text', 'text' => (string) $value], (array) ($data['parameters'] ?? []));
                    $payload = [
                        'messaging_product' => 'whatsapp', 'to' => (string) $row['destinatario'], 'type' => 'template',
                        'template' => ['name' => (string) $data['template'], 'language' => ['code' => (string) $data['language']], 'components' => [['type' => 'body', 'parameters' => $parameters]]],
                    ];
                } else {
                    $payload = [
                        'messaging_product' => 'whatsapp', 'to' => (string) $row['destinatario'], 'type' => 'text',
                        'text' => ['preview_url' => false, 'body' => (string) ($data['text'] ?? '')],
                    ];
                    if ((string) $row['tipo'] === 'gruppo') {
                        if (!volontariato_whatsapp_ready(true)) {
                            throw new RuntimeException('Groups API non abilitata.');
                        }
                        $payload['recipient_type'] = 'group';
                    }
                }
                $result = volontariato_whatsapp_request($payload);
                $messageId = (string) ($result['messages'][0]['id'] ?? '');
                $pdo->prepare("UPDATE whatsapp_outbox SET stato = 'inviato', sent_at = NOW(), tentativi = tentativi + 1, ultimo_errore = NULL WHERE id = :id")
                    ->execute(['id' => (int) $row['id']]);
                if (!empty($row['membro_id'])) {
                    $pdo->prepare("UPDATE volontari_gruppi_membri SET stato = 'invitato', invited_at = NOW(), ultimo_errore = NULL WHERE id = :id")
                        ->execute(['id' => (int) $row['membro_id']]);
                    $pdo->prepare("UPDATE volontari SET stato = IF(stato = 'da_confermare','invitato',stato) WHERE id = :id")
                        ->execute(['id' => (int) $row['volontario_id']]);
                }
                volontariato_store_outgoing($pdo, $row, $data, $messageId);
                $sent++;
            } catch (Throwable $error) {
                $pdo->prepare("UPDATE whatsapp_outbox SET stato = 'fallito', tentativi = tentativi + 1, ultimo_errore = :errore WHERE id = :id")
                    ->execute(['errore' => mb_substr($error->getMessage(), 0, 1000), 'id' => (int) $row['id']]);
                if (!empty($row['membro_id'])) {
                    $pdo->prepare("UPDATE volontari_gruppi_membri SET stato = 'errore', ultimo_errore = :errore WHERE id = :id")
                        ->execute(['errore' => mb_substr($error->getMessage(), 0, 1000), 'id' => (int) $row['membro_id']]);
                }
                $failed++;
            }
        }
        return ['sent' => $sent, 'failed' => $failed];
    }
}

if (!function_exists('volontariato_refresh_waiting_outbox')) {
    function volontariato_refresh_waiting_outbox(PDO $pdo): void
    {
        if (!volontariato_whatsapp_ready()) {
            return;
        }
        $pdo->exec("UPDATE whatsapp_outbox SET stato='in_coda', ultimo_errore=NULL WHERE stato='configurazione_mancante' AND tipo='diretto'");
        if (volontariato_whatsapp_ready(true)) {
            $pdo->exec("UPDATE whatsapp_outbox SET stato='in_coda', ultimo_errore=NULL WHERE stato='configurazione_mancante' AND tipo='gruppo'");
        }
        $template = trim((string) lauco_env('WHATSAPP_INVITE_TEMPLATE_NAME', ''));
        if ($template === '') {
            return;
        }
        $stmt = $pdo->query(
            "SELECT o.id,o.membro_id,v.nome,g.nome AS gruppo_nome,g.invite_link FROM whatsapp_outbox o "
            . "JOIN volontari v ON v.id=o.volontario_id JOIN volontari_gruppi g ON g.id=o.gruppo_id "
            . "WHERE o.stato='configurazione_mancante' AND o.tipo='invito' AND g.invite_link IS NOT NULL AND g.invite_link<>''"
        );
        $update = $pdo->prepare("UPDATE whatsapp_outbox SET payload_json=:payload,stato='in_coda',ultimo_errore=NULL WHERE id=:id");
        foreach ($stmt->fetchAll() as $row) {
            $payload = [
                'mode' => 'template', 'template' => $template,
                'language' => trim((string) lauco_env('WHATSAPP_INVITE_TEMPLATE_LANGUAGE', 'it')) ?: 'it',
                'parameters' => [(string) $row['nome'], (string) $row['gruppo_nome'], (string) $row['invite_link']],
            ];
            $update->execute([
                'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
                'id' => (int) $row['id'],
            ]);
            if (!empty($row['membro_id'])) {
                $pdo->prepare("UPDATE volontari_gruppi_membri SET stato='invito_in_coda',ultimo_errore=NULL WHERE id=:id AND stato<>'entrato'")
                    ->execute(['id' => (int) $row['membro_id']]);
            }
        }
    }
}

if (!function_exists('volontariato_store_outgoing')) {
    /** @param array<string,mixed> $outbox @param array<string,mixed> $data */
    function volontariato_store_outgoing(PDO $pdo, array $outbox, array $data, string $messageId): void
    {
        $external = (string) $outbox['destinatario'];
        $type = (string) $outbox['tipo'] === 'gruppo' ? 'gruppo' : 'diretta';
        $stmt = $pdo->prepare(
            'INSERT INTO whatsapp_conversazioni (tipo,volontario_id,gruppo_id,external_id,titolo,ultimo_messaggio_at) '
            . 'VALUES (:tipo,:volontario,:gruppo,:external,:titolo,NOW()) ON DUPLICATE KEY UPDATE '
            . 'ultimo_messaggio_at = NOW(), volontario_id = COALESCE(VALUES(volontario_id),volontario_id), gruppo_id = COALESCE(VALUES(gruppo_id),gruppo_id)'
        );
        $stmt->execute([
            'tipo' => $type, 'volontario' => $outbox['volontario_id'] ?: null, 'gruppo' => $outbox['gruppo_id'] ?: null,
            'external' => $external, 'titolo' => null,
        ]);
        $find = $pdo->prepare('SELECT id FROM whatsapp_conversazioni WHERE external_id = :external LIMIT 1');
        $find->execute(['external' => $external]);
        $conversationId = (int) $find->fetchColumn();
        if ($conversationId > 0) {
            $pdo->prepare(
                "INSERT IGNORE INTO whatsapp_messaggi (conversazione_id,external_message_id,direzione,tipo,testo,stato,messaggio_at) VALUES (:conversation,:external,'uscita',:tipo,:testo,'inviato',NOW())"
            )->execute([
                'conversation' => $conversationId, 'external' => $messageId !== '' ? $messageId : null,
                'tipo' => (string) ($data['mode'] ?? 'text'),
                'testo' => ($data['mode'] ?? '') === 'text' ? (string) ($data['text'] ?? '') : 'Invito al gruppo WhatsApp',
            ]);
        }
    }
}
