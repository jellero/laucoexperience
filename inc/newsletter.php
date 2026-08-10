<?php
declare(strict_types=1);

require_once __DIR__ . '/env.php';

if (!function_exists('newsletter_public_base_url')) {
    function newsletter_public_base_url(): string
    {
        $configured = trim((string) lauco_env('APP_URL', ''));
        if ($configured !== '') {
            return rtrim($configured, '/');
        }

        $https = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
        $host = trim((string) ($_SERVER['HTTP_HOST'] ?? 'laucoexperience.it'));
        return ($https ? 'https://' : 'http://') . $host;
    }
}


if (!function_exists('newsletter_sanitize_editor_html')) {
    function newsletter_sanitize_editor_html(string $html): string
    {
        $html = trim($html);
        if ($html === '') {
            return '';
        }

        if (!class_exists('DOMDocument')) {
            $html = preg_replace('#<(script|iframe|object|embed|form|input|button|textarea|select|meta|base)\\b[^>]*>.*?</\\1>#is', '', $html) ?? $html;
            $html = preg_replace('/\\son[a-z]+\\s*=\\s*(["\\\']).*?\\1/is', '', $html) ?? $html;
            $html = preg_replace('/(href|src)\\s*=\\s*(["\\\'])\\s*javascript:[^"\\\']*\\2/is', '$1="#"', $html) ?? $html;
            return trim($html);
        }

        $previous = libxml_use_internal_errors(true);
        $document = new DOMDocument('1.0', 'UTF-8');
        $wrapped = '<!doctype html><html><body><div id="newsletter-root">' . $html . '</div></body></html>';
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//script|//iframe|//object|//embed|//form|//input|//button|//textarea|//select|//meta|//base') ?: [] as $node) {
            $node->parentNode?->removeChild($node);
        }

        foreach ($xpath->query('//*[@*]') ?: [] as $node) {
            if (!$node instanceof DOMElement) {
                continue;
            }

            foreach (iterator_to_array($node->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                $value = trim($attribute->value);
                if (str_starts_with($name, 'on')) {
                    $node->removeAttribute($attribute->name);
                    continue;
                }
                if (in_array($name, ['href', 'src'], true) && preg_match('/^\s*javascript:/i', $value)) {
                    $node->removeAttribute($attribute->name);
                }
            }
        }

        $rootList = $xpath->query('//*[@id="newsletter-root"]');
        $root = $rootList && $rootList->length > 0 ? $rootList->item(0) : null;
        if (!$root) {
            return '';
        }

        $clean = '';
        foreach ($root->childNodes as $child) {
            $clean .= $document->saveHTML($child);
        }
        return trim($clean);
    }
}

if (!function_exists('newsletter_render_html')) {
    function newsletter_render_html(string $subject, string $preheader, string $htmlBody): string
    {
        $htmlBody = trim($htmlBody);
        if (preg_match('/<html\b/i', $htmlBody)) {
            return $htmlBody;
        }

        $safeSubject = htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $safePreheader = htmlspecialchars($preheader, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return '<!doctype html>'
            . '<html lang="it"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . $safeSubject . '</title></head>'
            . '<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,sans-serif;color:#222;">'
            . ($preheader !== '' ? '<div style="display:none;max-height:0;overflow:hidden;opacity:0;">' . $safePreheader . '</div>' : '')
            . '<div style="max-width:680px;margin:0 auto;background:#fff;padding:32px 28px;">'
            . $htmlBody
            . '</div></body></html>';
    }
}

if (!function_exists('newsletter_send_campaign')) {
    /**
     * @return array{sent:int,failed:int,total:int}
     */
    function newsletter_send_campaign(PDO $pdo, array $campaign): array
    {
        $stmt = $pdo->query(
            "SELECT email FROM newsletter_subscribers WHERE status = 'active' ORDER BY id ASC"
        );
        $recipients = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
        if ($recipients === []) {
            throw new RuntimeException('Non ci sono iscritti attivi a cui inviare la newsletter.');
        }

        $subject = trim((string) ($campaign['subject'] ?? ''));
        $subject = str_replace(["\r", "\n"], ' ', $subject);
        $preheader = trim((string) ($campaign['preheader'] ?? ''));
        $body = newsletter_render_html($subject, $preheader, (string) ($campaign['html_body'] ?? ''));

        $encodedSubject = function_exists('mb_encode_mimeheader')
            ? mb_encode_mimeheader($subject, 'UTF-8', 'B', "\r\n")
            : $subject;

        $sent = 0;
        $failed = 0;
        foreach ($recipients as $recipient) {
            $recipient = trim((string) $recipient);
            if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                $failed++;
                continue;
            }

            $headers = [
                'From: Lauco Experience <info@laucoexperience.it>',
                'Reply-To: info@laucoexperience.it',
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: 8bit',
                'List-Unsubscribe: <mailto:info@laucoexperience.it?subject=Disiscrizione%20newsletter>',
                'X-Mailer: PHP/' . phpversion(),
            ];

            if (mail($recipient, $encodedSubject, $body, implode("\r\n", $headers))) {
                $sent++;
            } else {
                $failed++;
                error_log('[Newsletter] Invio fallito verso ' . $recipient);
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'total' => count($recipients)];
    }
}
