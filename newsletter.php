<?php

declare(strict_types=1);

require_once __DIR__ . '/inc/translations.php';
$responseLanguage = content_language_from_request();

/*
 * Risposte esclusivamente JSON.
 */
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Content-Language: ' . $responseLanguage);

/**
 * Restituisce una risposta JSON e termina lo script.
 */
function jsonResponse(
    bool $success,
    string $message,
    int $statusCode = 200
): void {
    http_response_code($statusCode);

    echo json_encode(
        [
            'success' => $success,
            'message' => $message,
        ],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    );

    exit;
}

/*
 * Gestione degli errori PHP non catturati.
 */
set_exception_handler(
    static function (Throwable $exception): void {
        error_log(
            'Newsletter exception: ' .
            $exception->getMessage()
        );

        jsonResponse(
            false,
            site_text('runtime.newsletter.db899df867', null, 'Si è verificato un errore interno. Riprova più tardi.'),
            500
        );
    }
);

/*
 * Il file accetta solamente richieste POST.
 *
 * Aprendo newsletter.php direttamente nel browser viene effettuata
 * una richiesta GET e quindi viene mostrato "Metodo non consentito".
 * Il test corretto deve essere fatto dal modulo del footer.
 */
$requestMethod = strtoupper(
    (string) ($_SERVER['REQUEST_METHOD'] ?? '')
);

if ($requestMethod !== 'POST') {
    jsonResponse(
        false,
        site_text('runtime.newsletter.75be03f41b', null, 'Metodo non consentito. Utilizza il modulo newsletter.'),
        405
    );
}

/*
 * Honeypot antispam.
 * Un utente reale non può vedere o compilare questo campo.
 */
$companyWebsite = trim(
    (string) ($_POST['company_website'] ?? '')
);

if ($companyWebsite !== '') {
    /*
     * Restituiamo una risposta apparentemente positiva
     * per non fornire informazioni utili ai bot.
     */
    jsonResponse(
        true,
        site_text('runtime.footer.a2356c1d53', null, 'Grazie! La richiesta è stata inviata correttamente.')
    );
}

/*
 * Lettura e validazione dell'indirizzo email.
 */
$email = trim(
    (string) ($_POST['email'] ?? '')
);

if ($email === '') {
    jsonResponse(
        false,
        site_text('runtime.newsletter.1e00992632', null, 'Inserisci il tuo indirizzo email.'),
        422
    );
}

/*
 * Protezione contro header injection.
 */
if (preg_match('/[\r\n]/', $email)) {
    jsonResponse(
        false,
        site_text('runtime.newsletter.038d5f0253', null, 'L’indirizzo email contiene caratteri non consentiti.'),
        422
    );
}

if (strlen($email) > 254) {
    jsonResponse(
        false,
        site_text('runtime.newsletter.f64d7b7f76', null, 'L’indirizzo email inserito è troppo lungo.'),
        422
    );
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(
        false,
        site_text('runtime.newsletter.4da262a4a9', null, 'Inserisci un indirizzo email valido.'),
        422
    );
}

/*
 * Configurazione del messaggio.
 */
$recipient = 'info@laucoexperience.it';
$subject = 'Nuova iscrizione alla newsletter';

$timezone = new DateTimeZone('Europe/Rome');
$currentDate = new DateTimeImmutable('now', $timezone);

$ipAddress = trim(
    (string) ($_SERVER['REMOTE_ADDR'] ?? 'non disponibile')
);

$userAgent = trim(
    (string) ($_SERVER['HTTP_USER_AGENT'] ?? 'non disponibile')
);

$message = "Nuova richiesta di iscrizione alla newsletter.\n\n";
$message .= "Email: {$email}\n";
$message .= 'Data: ' . $currentDate->format('d/m/Y H:i:s') . "\n";
$message .= "Indirizzo IP: {$ipAddress}\n";
$message .= "Browser: {$userAgent}\n";

$message = wordwrap($message, 70);

/*
 * Il mittente deve appartenere al dominio del sito.
 * L'indirizzo dell'utente viene utilizzato solamente come Reply-To.
 */
$headers = [
    'From: Lauco Experience <info@laucoexperience.it>',
    'Reply-To: ' . $email,
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
    'X-Mailer: PHP/' . phpversion(),
];

if (function_exists('mb_encode_mimeheader')) {
    $encodedSubject = mb_encode_mimeheader(
        $subject,
        'UTF-8',
        'B',
        "\r\n"
    );
} else {
    $encodedSubject = $subject;
}

/*
 * Invio dell'email.
 *
 * mail() restituisce true quando il server accetta il messaggio,
 * ma non garantisce la consegna effettiva nella casella destinataria.
 */
$mailSent = mail(
    $recipient,
    $encodedSubject,
    $message,
    implode("\r\n", $headers)
);

if (!$mailSent) {
    error_log(
        sprintf(
            'Invio newsletter fallito. Email: %s; IP: %s',
            $email,
            $ipAddress
        )
    );

    jsonResponse(
        false,
        site_text('runtime.newsletter.3ae0c3e48e', null, 'Non è stato possibile inviare la richiesta. Riprova più tardi.'),
        500
    );
}

jsonResponse(
    true,
    site_text('runtime.newsletter.584c399065', null, 'Grazie! La richiesta di iscrizione è stata inviata correttamente.')
);
