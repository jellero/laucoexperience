<?php
declare(strict_types=1);

use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address as SymfonyAddress;
use Symfony\Component\Mime\Email;
use Webklex\PHPIMAP\Address as ImapAddress;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Folder;
use Webklex\PHPIMAP\Message;

require_once __DIR__ . '/env.php';
require_once __DIR__ . '/newsletter.php';

$backofficeMailAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($backofficeMailAutoload)) {
    throw new RuntimeException('Dipendenze del client di posta non installate.');
}
require_once $backofficeMailAutoload;

if (!function_exists('backoffice_mail_config')) {
    /** @return array<string,mixed> */
    function backoffice_mail_config(): array
    {
        $username = trim((string) lauco_env('MAIL_IMAP_USERNAME', 'info@laucoexperience.it'));
        $sharedPassword = (string) lauco_env('MAIL_PASSWORD', '');
        $imapPassword = (string) lauco_env('MAIL_IMAP_PASSWORD', '');
        $smtpPassword = (string) lauco_env('MAIL_SMTP_PASSWORD', '');

        return [
            'imap_host' => trim((string) lauco_env('MAIL_IMAP_HOST', 'in.postassl.it')),
            'imap_port' => max(1, lauco_env_int('MAIL_IMAP_PORT', 993)),
            'imap_encryption' => trim((string) lauco_env('MAIL_IMAP_ENCRYPTION', 'ssl')),
            'imap_validate_cert' => lauco_env_bool('MAIL_IMAP_VALIDATE_CERT', true),
            'imap_username' => $username,
            'imap_password' => $imapPassword !== '' ? $imapPassword : $sharedPassword,
            'imap_timeout' => max(5, min(60, lauco_env_int('MAIL_IMAP_TIMEOUT_SECONDS', 20))),
            'imap_sent_folder' => trim((string) lauco_env('MAIL_IMAP_SENT_FOLDER', '')),
            'imap_trash_folder' => trim((string) lauco_env('MAIL_IMAP_TRASH_FOLDER', '')),
            'smtp_host' => trim((string) lauco_env('MAIL_SMTP_HOST', 'out.postassl.it')),
            'smtp_port' => max(1, lauco_env_int('MAIL_SMTP_PORT', 465)),
            'smtp_encryption' => strtolower(trim((string) lauco_env('MAIL_SMTP_ENCRYPTION', 'ssl'))),
            'smtp_username' => trim((string) lauco_env('MAIL_SMTP_USERNAME', $username)),
            'smtp_password' => $smtpPassword !== '' ? $smtpPassword : $sharedPassword,
            'from_address' => trim((string) lauco_env('MAIL_FROM_ADDRESS', $username)),
            'from_name' => trim((string) lauco_env('MAIL_FROM_NAME', 'Lauco Experience')),
            'attachment_max_bytes' => max(1_048_576, lauco_env_int('MAIL_ATTACHMENT_MAX_BYTES', 15_728_640)),
        ];
    }
}

if (!function_exists('backoffice_mail_assert_configured')) {
    function backoffice_mail_assert_configured(bool $smtp = false): void
    {
        $config = backoffice_mail_config();
        $required = $smtp
            ? ['smtp_host', 'smtp_username', 'smtp_password', 'from_address']
            : ['imap_host', 'imap_username', 'imap_password'];

        foreach ($required as $key) {
            if (trim((string) ($config[$key] ?? '')) === '') {
                throw new RuntimeException('Configurazione posta incompleta.');
            }
        }
    }
}

if (!function_exists('backoffice_mail_client')) {
    function backoffice_mail_client(): Client
    {
        static $client;
        if ($client instanceof Client && $client->isConnected()) {
            return $client;
        }

        backoffice_mail_assert_configured();
        $config = backoffice_mail_config();
        $manager = new ClientManager();
        $client = $manager->make([
            'host' => $config['imap_host'],
            'port' => $config['imap_port'],
            'encryption' => $config['imap_encryption'],
            'validate_cert' => $config['imap_validate_cert'],
            'username' => $config['imap_username'],
            'password' => $config['imap_password'],
            'protocol' => 'imap',
            'timeout' => $config['imap_timeout'],
        ]);
        $client->connect();

        return $client;
    }
}

if (!function_exists('backoffice_mail_folders')) {
    /** @return list<Folder> */
    function backoffice_mail_folders(?Client $client = null): array
    {
        $client ??= backoffice_mail_client();
        $folders = [];
        foreach ($client->getFolders(false) as $folder) {
            if ($folder instanceof Folder && !$folder->no_select) {
                $folders[] = $folder;
            }
        }

        usort($folders, static function (Folder $a, Folder $b): int {
            if (strcasecmp($a->path, 'INBOX') === 0) {
                return -1;
            }
            if (strcasecmp($b->path, 'INBOX') === 0) {
                return 1;
            }
            return strcasecmp($a->full_name, $b->full_name);
        });

        return $folders;
    }
}

if (!function_exists('backoffice_mail_folder')) {
    function backoffice_mail_folder(string $requested = 'INBOX', ?Client $client = null): Folder
    {
        $client ??= backoffice_mail_client();
        $requested = trim($requested) ?: 'INBOX';
        foreach (backoffice_mail_folders($client) as $folder) {
            if ($folder->path === $requested || strcasecmp($folder->path, $requested) === 0) {
                return $folder;
            }
        }

        throw new RuntimeException('Cartella di posta non disponibile.');
    }
}

if (!function_exists('backoffice_mail_message')) {
    function backoffice_mail_message(Folder $folder, int $uid, bool $markAsRead = false): Message
    {
        if ($uid < 1) {
            throw new RuntimeException('Messaggio non valido.');
        }

        $query = $folder->query()->setFetchBody(true);
        $markAsRead ? $query->markAsRead() : $query->leaveUnread();
        return $query->getMessageByUid($uid);
    }
}

if (!function_exists('backoffice_mail_attribute_first')) {
    function backoffice_mail_attribute_first(mixed $attribute): mixed
    {
        return is_object($attribute) && method_exists($attribute, 'first') ? $attribute->first() : null;
    }
}

if (!function_exists('backoffice_mail_address')) {
    /** @return array{name:string,email:string,full:string} */
    function backoffice_mail_address(mixed $address): array
    {
        if ($address instanceof ImapAddress) {
            return [
                'name' => trim($address->personal),
                'email' => trim($address->mail),
                'full' => trim($address->full ?: $address->mail),
            ];
        }

        $value = trim(is_scalar($address) ? (string) $address : '');
        return ['name' => '', 'email' => $value, 'full' => $value];
    }
}

if (!function_exists('backoffice_mail_addresses')) {
    /** @return list<array{name:string,email:string,full:string}> */
    function backoffice_mail_addresses(mixed $attribute): array
    {
        $values = is_object($attribute) && method_exists($attribute, 'all') ? $attribute->all() : [];
        return array_values(array_filter(
            array_map('backoffice_mail_address', $values),
            static fn (array $address): bool => $address['email'] !== '' || $address['full'] !== ''
        ));
    }
}

if (!function_exists('backoffice_mail_message_data')) {
    /** @return array<string,mixed> */
    function backoffice_mail_message_data(Message $message): array
    {
        $date = backoffice_mail_attribute_first($message->getDate());
        $flags = array_map('strtolower', array_map('strval', $message->getFlags()->all()));
        $from = backoffice_mail_addresses($message->getFrom());

        return [
            'uid' => (int) $message->getUid(),
            'subject' => trim((string) (backoffice_mail_attribute_first($message->getSubject()) ?? '')) ?: '(Senza oggetto)',
            'date' => $date instanceof DateTimeInterface ? $date : null,
            'from' => $from,
            'from_label' => $from[0]['full'] ?? '(Mittente sconosciuto)',
            'to' => backoffice_mail_addresses($message->getTo()),
            'reply_to' => backoffice_mail_addresses($message->getReplyTo()),
            'message_id' => trim((string) (backoffice_mail_attribute_first($message->getMessageId()) ?? '')),
            'in_reply_to' => trim((string) (backoffice_mail_attribute_first($message->getInReplyTo()) ?? '')),
            'unread' => !in_array('seen', $flags, true),
            'flagged' => in_array('flagged', $flags, true),
            'has_attachments' => $message->hasAttachments(),
            'size' => (int) $message->getSize(),
        ];
    }
}

if (!function_exists('backoffice_mail_parse_recipients')) {
    /** @return list<SymfonyAddress> */
    function backoffice_mail_parse_recipients(string $value): array
    {
        $value = str_replace(["\r\n", "\r", "\n", ';'], ',', trim($value));
        if ($value === '') {
            return [];
        }

        $recipients = [];
        foreach (str_getcsv($value, ',', '"', '\\') as $part) {
            $part = trim((string) $part);
            if ($part !== '') {
                $recipients[] = SymfonyAddress::create($part);
            }
        }
        return $recipients;
    }
}

if (!function_exists('backoffice_mail_normalize_message_id')) {
    function backoffice_mail_normalize_message_id(string $value): string
    {
        $value = trim(str_replace(["\r", "\n"], '', $value));
        if ($value === '') {
            return '';
        }
        return str_starts_with($value, '<') && str_ends_with($value, '>') ? $value : '<' . trim($value, '<>') . '>';
    }
}

if (!function_exists('backoffice_mail_sent_folder')) {
    function backoffice_mail_sent_folder(Client $client): ?Folder
    {
        $configured = trim((string) backoffice_mail_config()['imap_sent_folder']);
        $candidates = array_filter([
            $configured,
            'Sent',
            'INBOX/Sent',
            'INBOX.Sent',
            'Posta inviata',
            'INBOX/Posta inviata',
        ]);
        $folders = backoffice_mail_folders($client);

        foreach ($folders as $folder) {
            foreach ($candidates as $candidate) {
                if (strcasecmp($folder->path, $candidate) === 0 || strcasecmp($folder->name, $candidate) === 0) {
                    return $folder;
                }
            }
            if (in_array(strtolower($folder->name), ['sent', 'sent messages', 'posta inviata', 'inviata', 'inviati'], true)) {
                return $folder;
            }
        }
        return null;
    }
}

if (!function_exists('backoffice_mail_trash_folder')) {
    function backoffice_mail_trash_folder(Client $client): ?Folder
    {
        $configured = trim((string) backoffice_mail_config()['imap_trash_folder']);
        $candidates = array_filter([
            $configured,
            'Trash',
            'INBOX/Trash',
            'INBOX.Trash',
            'Cestino',
            'INBOX/Cestino',
            'INBOX.Cestino',
            'Deleted',
            'Deleted Messages',
            'INBOX/Deleted Messages',
            'Posta eliminata',
            'INBOX/Posta eliminata',
        ]);
        $trashNames = ['trash', 'cestino', 'deleted', 'deleted messages', 'posta eliminata', 'eliminata'];

        foreach (backoffice_mail_folders($client) as $folder) {
            foreach ($candidates as $candidate) {
                if (strcasecmp($folder->path, $candidate) === 0 || strcasecmp($folder->name, $candidate) === 0) {
                    return $folder;
                }
            }
            if (in_array(strtolower($folder->name), $trashNames, true)) {
                return $folder;
            }
        }
        return null;
    }
}

if (!function_exists('backoffice_mail_send')) {
    /**
     * @param list<SymfonyAddress> $to
     * @param list<SymfonyAddress> $cc
     * @param list<SymfonyAddress> $bcc
     * @param list<array{path:string,name:string,mime:string}> $attachments
     */
    function backoffice_mail_send(
        array $to,
        array $cc,
        array $bcc,
        string $subject,
        string $body,
        string $htmlBody = '',
        array $attachments = [],
        string $inReplyTo = '',
        string $references = ''
    ): SentMessage {
        backoffice_mail_assert_configured(true);
        if ($to === []) {
            throw new InvalidArgumentException('Inserisci almeno un destinatario.');
        }

        $config = backoffice_mail_config();
        $scheme = $config['smtp_encryption'] === 'ssl' ? 'smtps' : 'smtp';
        $dsn = sprintf(
            '%s://%s:%s@%s:%d?verify_peer=1',
            $scheme,
            rawurlencode((string) $config['smtp_username']),
            rawurlencode((string) $config['smtp_password']),
            (string) $config['smtp_host'],
            (int) $config['smtp_port']
        );

        $htmlBody = newsletter_sanitize_editor_html($htmlBody);
        $renderedHtml = $htmlBody !== ''
            ? '<div style="font-family:Arial,sans-serif;line-height:1.55">' . $htmlBody . '</div>'
            : '<div style="font-family:Arial,sans-serif;line-height:1.55">' . nl2br(htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</div>';

        $email = (new Email())
            ->from(new SymfonyAddress((string) $config['from_address'], (string) $config['from_name']))
            ->to(...$to)
            ->subject(trim($subject) ?: '(Senza oggetto)')
            ->text($body)
            ->html($renderedHtml);

        if ($cc !== []) {
            $email->cc(...$cc);
        }
        if ($bcc !== []) {
            $email->bcc(...$bcc);
        }

        $inReplyTo = backoffice_mail_normalize_message_id($inReplyTo);
        $references = trim(str_replace(["\r", "\n"], '', $references));
        if ($inReplyTo !== '') {
            $email->getHeaders()->addTextHeader('In-Reply-To', $inReplyTo);
            $email->getHeaders()->addTextHeader('References', trim($references . ' ' . $inReplyTo));
        }

        foreach ($attachments as $attachment) {
            $email->attachFromPath($attachment['path'], $attachment['name'], $attachment['mime']);
        }

        $sent = Transport::fromDsn($dsn)->send($email);

        try {
            $client = backoffice_mail_client();
            $sentFolder = backoffice_mail_sent_folder($client);
            $sentFolder?->appendMessage($sent->toString(), ['\\Seen']);
        } catch (Throwable $exception) {
            backoffice_mail_log_exception($exception, 'salvataggio posta inviata');
        }

        return $sent;
    }
}

if (!function_exists('backoffice_mail_html_to_text')) {
    function backoffice_mail_html_to_text(string $html): string
    {
        $text = preg_replace('/<\s*br\s*\/?\s*>/i', "\n", $html) ?? $html;
        $text = preg_replace('/<\s*\/\s*(p|div|h[1-6]|blockquote|tr|ul|ol)\s*>/i', "\n", $text) ?? $text;
        $text = preg_replace('/<\s*li\b[^>]*>/i', '- ', $text) ?? $text;
        $text = preg_replace('/<\s*\/\s*li\s*>/i', "\n", $text) ?? $text;
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace('/[ \t]+\n/', "\n", $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;

        return trim($text);
    }
}

if (!function_exists('backoffice_mail_uploads')) {
    /** @return list<array{path:string,name:string,mime:string}> */
    function backoffice_mail_uploads(array $files): array
    {
        if (!isset($files['name']) || !is_array($files['name'])) {
            return [];
        }

        $config = backoffice_mail_config();
        $maxBytes = (int) $config['attachment_max_bytes'];
        $total = 0;
        $attachments = [];
        $finfo = class_exists(finfo::class) ? new finfo(FILEINFO_MIME_TYPE) : null;

        foreach ($files['name'] as $index => $originalName) {
            $error = (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if ($error !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Uno degli allegati non è stato caricato correttamente.');
            }

            $path = (string) ($files['tmp_name'][$index] ?? '');
            $size = (int) ($files['size'][$index] ?? 0);
            if ($path === '' || !is_uploaded_file($path) || $size < 0) {
                throw new RuntimeException('Allegato non valido.');
            }
            $total += $size;
            if ($size > $maxBytes || $total > $maxBytes) {
                throw new RuntimeException('Gli allegati superano il limite complessivo consentito.');
            }

            $name = trim(str_replace(["\0", "\r", "\n", '/', '\\'], '_', basename((string) $originalName)));
            $name = $name !== '' ? mb_substr($name, 0, 180) : 'allegato';
            $mime = $finfo?->file($path) ?: 'application/octet-stream';
            $attachments[] = ['path' => $path, 'name' => $name, 'mime' => $mime];
        }

        return $attachments;
    }
}

if (!function_exists('backoffice_mail_sanitize_html')) {
    function backoffice_mail_sanitize_html(string $html): string
    {
        if (trim($html) === '' || !class_exists(DOMDocument::class)) {
            return '';
        }

        $allowedTags = [
            'a', 'b', 'blockquote', 'br', 'code', 'div', 'em', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
            'hr', 'i', 'li', 'ol', 'p', 'pre', 'span', 'strong', 'table', 'tbody', 'td', 'th', 'thead', 'tr', 'u', 'ul',
        ];
        $removeWithContent = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'input', 'button', 'textarea', 'select', 'svg', 'math', 'img', 'video', 'audio', 'canvas'];

        $previous = libxml_use_internal_errors(true);
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->loadHTML(
            '<!doctype html><html><body><div id="mail-safe-root">' . $html . '</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET
        );
        $xpath = new DOMXPath($dom);
        $root = $xpath->query('//*[@id="mail-safe-root"]')->item(0);

        if ($root instanceof DOMElement) {
            $nodes = [];
            foreach ($xpath->query('.//*', $root) ?: [] as $node) {
                $nodes[] = $node;
            }
            foreach (array_reverse($nodes) as $node) {
                if (!$node instanceof DOMElement || !$node->parentNode) {
                    continue;
                }
                $tag = strtolower($node->tagName);
                if (in_array($tag, $removeWithContent, true)) {
                    $node->parentNode->removeChild($node);
                    continue;
                }
                if (!in_array($tag, $allowedTags, true)) {
                    while ($node->firstChild) {
                        $node->parentNode->insertBefore($node->firstChild, $node);
                    }
                    $node->parentNode->removeChild($node);
                    continue;
                }

                foreach (iterator_to_array($node->attributes) as $attribute) {
                    $name = strtolower($attribute->name);
                    if ($tag !== 'a' || $name !== 'href') {
                        $node->removeAttribute($attribute->name);
                    }
                }
                if ($tag === 'a' && $node->hasAttribute('href')) {
                    $href = trim($node->getAttribute('href'));
                    $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));
                    if (!in_array($scheme, ['http', 'https', 'mailto'], true)) {
                        $node->removeAttribute('href');
                    } else {
                        $node->setAttribute('rel', 'noopener noreferrer nofollow');
                        if ($scheme !== 'mailto') {
                            $node->setAttribute('target', '_blank');
                        }
                    }
                }
            }
        }

        $result = '';
        if ($root instanceof DOMElement) {
            foreach ($root->childNodes as $child) {
                $result .= $dom->saveHTML($child);
            }
        }
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $result;
    }
}

if (!function_exists('backoffice_mail_log_exception')) {
    function backoffice_mail_log_exception(Throwable $exception, string $context = 'posta'): void
    {
        $message = $exception->getMessage();
        foreach (['imap_password', 'smtp_password'] as $key) {
            $secret = (string) (backoffice_mail_config()[$key] ?? '');
            if ($secret !== '') {
                $message = str_replace([$secret, rawurlencode($secret)], '[secret]', $message);
            }
        }
        error_log(sprintf('[%s] %s: %s', $context, $exception::class, $message));
    }
}

if (!function_exists('backoffice_mail_user_error')) {
    function backoffice_mail_user_error(Throwable $exception): string
    {
        backoffice_mail_log_exception($exception);
        if ($exception instanceof InvalidArgumentException) {
            return $exception->getMessage();
        }
        if (str_contains(strtolower($exception->getMessage()), 'configurazione')) {
            return 'La casella non è ancora configurata sul server.';
        }
        return 'Impossibile collegarsi alla casella di posta. Riprova tra poco o verifica la configurazione IMAP/SMTP.';
    }
}
