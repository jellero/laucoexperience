<?php
declare(strict_types=1);

namespace LaucoExperience\Http\Action;

use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class WhatsAppWebhookAction
{
    public function __construct(private readonly string $root)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        require_once $this->root . '/inc/env.php';
        require_once $this->root . '/inc/volontariato.php';
        if (strtoupper($request->getMethod()) === 'GET') {
            $query = $request->getQueryParams();
            if (($query['hub_mode'] ?? $query['hub.mode'] ?? '') === 'subscribe'
                && hash_equals((string) lauco_env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', ''), (string) ($query['hub_verify_token'] ?? $query['hub.verify_token'] ?? ''))) {
                $response->getBody()->write((string) ($query['hub_challenge'] ?? $query['hub.challenge'] ?? ''));
                return $response->withStatus(200)->withHeader('Content-Type', 'text/plain');
            }
            return $response->withStatus(403);
        }

        $raw = (string) $request->getBody();
        $secret = (string) lauco_env('WHATSAPP_APP_SECRET', '');
        if ($secret !== '') {
            $expected = 'sha256=' . hash_hmac('sha256', $raw, $secret);
            if (!hash_equals($expected, $request->getHeaderLine('X-Hub-Signature-256'))) {
                return $response->withStatus(401);
            }
        }
        try {
            $payload = json_decode($raw, true, 128, JSON_THROW_ON_ERROR);
            require $this->root . '/inc/db.php';
            $connection = $pdo ?? ($GLOBALS['pdo'] ?? null);
            if (!$connection instanceof PDO) {
                throw new \RuntimeException('Database non disponibile.');
            }
            $hash = hash('sha256', $raw);
            $store = $connection->prepare('INSERT IGNORE INTO whatsapp_webhook_events (event_hash,payload_json) VALUES (:hash,:payload)');
            $store->execute(['hash' => $hash, 'payload' => $raw]);
            if ($store->rowCount() > 0) {
                $this->process($connection, is_array($payload) ? $payload : []);
            }
            $response->getBody()->write('EVENT_RECEIVED');
            return $response->withStatus(200)->withHeader('Content-Type', 'text/plain');
        } catch (Throwable $error) {
            error_log('[WhatsApp webhook] ' . $error->getMessage());
            return $response->withStatus(400);
        }
    }

    /** @param array<string,mixed> $payload */
    private function process(PDO $pdo, array $payload): void
    {
        foreach ((array) ($payload['entry'] ?? []) as $entry) {
            foreach ((array) ($entry['changes'] ?? []) as $change) {
                $value = is_array($change['value'] ?? null) ? $change['value'] : [];
                foreach ((array) ($value['messages'] ?? []) as $message) {
                    if (!is_array($message)) continue;
                    $from = preg_replace('/\D+/', '', (string) ($message['from'] ?? '')) ?? '';
                    if ($from === '') continue;
                    $text = $this->messageText($message);
                    $volunteer = $pdo->prepare('SELECT id,nome FROM volontari WHERE telefono = :phone LIMIT 1');
                    $volunteer->execute(['phone' => '+' . $from]);
                    $volunteerRow = $volunteer->fetch();
                    $groupExternal = trim((string) (
                        $message['group_id']
                        ?? $message['context']['group_id']
                        ?? $value['group_id']
                        ?? ''
                    ));
                    $groupRow = null;
                    if ($groupExternal !== '') {
                        $group = $pdo->prepare('SELECT id,nome FROM volontari_gruppi WHERE meta_group_id = :external LIMIT 1');
                        $group->execute(['external' => $groupExternal]);
                        $groupRow = $group->fetch();
                        $sender = is_array($volunteerRow) ? (string) $volunteerRow['nome'] : '+' . $from;
                        $text = $sender . ': ' . $text;
                    }
                    $conversationExternal = $groupExternal !== '' ? $groupExternal : $from;
                    $conversationType = $groupExternal !== '' ? 'gruppo' : 'diretta';
                    $pdo->prepare(
                        "INSERT INTO whatsapp_conversazioni (tipo,volontario_id,gruppo_id,external_id,titolo,non_letti,ultimo_messaggio_at) "
                        . "VALUES (:tipo,:volontario,:gruppo,:external,:titolo,1,:message_at) ON DUPLICATE KEY UPDATE "
                        . "volontario_id=COALESCE(VALUES(volontario_id),volontario_id),gruppo_id=COALESCE(VALUES(gruppo_id),gruppo_id),titolo=COALESCE(VALUES(titolo),titolo),non_letti=non_letti+1,ultimo_messaggio_at=VALUES(ultimo_messaggio_at)"
                    )->execute([
                        'tipo' => $conversationType,
                        'volontario' => $conversationType === 'diretta' && is_array($volunteerRow) ? (int) $volunteerRow['id'] : null,
                        'gruppo' => is_array($groupRow) ? (int) $groupRow['id'] : null,
                        'external' => $conversationExternal,
                        'titolo' => is_array($groupRow) ? (string) $groupRow['nome'] : (is_array($volunteerRow) ? (string) $volunteerRow['nome'] : '+' . $from),
                        'message_at' => date('Y-m-d H:i:s', (int) ($message['timestamp'] ?? time())),
                    ]);
                    $find = $pdo->prepare('SELECT id FROM whatsapp_conversazioni WHERE external_id=:external');
                    $find->execute(['external' => $conversationExternal]);
                    $pdo->prepare(
                        "INSERT IGNORE INTO whatsapp_messaggi (conversazione_id,external_message_id,direzione,tipo,testo,stato,raw_json,messaggio_at) "
                        . "VALUES (:conversation,:external,'entrata',:tipo,:testo,'ricevuto',:raw,:message_at)"
                    )->execute([
                        'conversation' => (int) $find->fetchColumn(), 'external' => (string) ($message['id'] ?? ''),
                        'tipo' => (string) ($message['type'] ?? 'unknown'), 'testo' => $text,
                        'raw' => json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'message_at' => date('Y-m-d H:i:s', (int) ($message['timestamp'] ?? time())),
                    ]);
                }
                foreach ((array) ($value['statuses'] ?? []) as $status) {
                    if (!is_array($status) || empty($status['id'])) continue;
                    $mapped = ['sent' => 'inviato', 'delivered' => 'consegnato', 'read' => 'letto', 'failed' => 'fallito'][(string) ($status['status'] ?? '')] ?? null;
                    if ($mapped !== null) {
                        $pdo->prepare('UPDATE whatsapp_messaggi SET stato=:stato WHERE external_message_id=:external')
                            ->execute(['stato' => $mapped, 'external' => (string) $status['id']]);
                    }
                }
            }
        }
    }

    /** @param array<string,mixed> $message */
    private function messageText(array $message): string
    {
        return match ((string) ($message['type'] ?? '')) {
            'text' => (string) ($message['text']['body'] ?? ''),
            'button' => (string) ($message['button']['text'] ?? ''),
            'interactive' => (string) ($message['interactive']['button_reply']['title'] ?? $message['interactive']['list_reply']['title'] ?? ''),
            'image' => (string) ($message['image']['caption'] ?? '[Immagine]'),
            'document' => (string) ($message['document']['caption'] ?? '[Documento]'),
            default => '[' . (string) ($message['type'] ?? 'messaggio') . ']',
        };
    }
}
