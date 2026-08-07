<?php
require_once __DIR__ . '/contact-config.php';

function contact_clean_header(string $value): string
{
    return trim(str_replace(["\r", "\n"], '', $value));
}

function contact_subject(string $subject): string
{
    $subject = contact_clean_header($subject);

    if (function_exists('mb_encode_mimeheader')) {
        return mb_encode_mimeheader($subject, 'UTF-8');
    }

    return $subject;
}

function contact_send_mail(string $to, string $subject, string $body, ?string $replyTo = null): bool
{
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'Content-Transfer-Encoding: 8bit';
    $headers[] = 'From: ' . contact_clean_header(CONTACT_FROM_NAME) . ' <' . contact_clean_header(CONTACT_FROM_EMAIL) . '>';

    if ($replyTo) {
        $headers[] = 'Reply-To: ' . contact_clean_header($replyTo);
    }

    return @mail(
        contact_clean_header($to),
        contact_subject($subject),
        $body,
        implode("\r\n", $headers)
    );
}

function contact_admin_body(array $data, string $codice): string
{
    return
        "Nuovo messaggio dal sito Lauco Experience\n\n" .
        "Codice: {$codice}\n" .
        "Nome: {$data['nome']}\n" .
        "Email: {$data['email']}\n" .
        "Oggetto: {$data['oggetto']}\n\n" .
        "Messaggio:\n{$data['messaggio']}\n\n" .
        "IP: " . ($_SERVER['REMOTE_ADDR'] ?? '-') . "\n" .
        "Browser: " . ($_SERVER['HTTP_USER_AGENT'] ?? '-') . "\n";
}

function contact_customer_body(array $data, string $codice): string
{
    return
        "Buongiorno {$data['nome']},\n\n" .
        "abbiamo ricevuto il tuo messaggio tramite il sito Lauco Experience.\n\n" .
        "Codice riferimento: {$codice}\n" .
        "Oggetto: {$data['oggetto']}\n\n" .
        "Il messaggio verrà preso in carico appena possibile.\n\n" .
        "Riepilogo del messaggio inviato:\n" .
        "{$data['messaggio']}\n\n" .
        "Cordiali saluti\n" .
        CONTACT_TO_NAME . "\n";
}
