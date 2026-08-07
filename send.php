<?php
ob_start();

ini_set('display_errors', '0');
error_reporting(E_ALL);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$__finished = false;

function send_json(string $info, string $msg, int $status = 200): void
{
    global $__finished;
    $__finished = true;

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    echo json_encode([
        'info' => $info,
        'msg' => $msg,
    ]);
    exit;
}

register_shutdown_function(function () {
    global $__finished;

    if ($__finished) {
        return;
    }

    $error = error_get_last();

    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        send_json('error', 'Errore interno del server durante l’invio.', 500);
    }
});

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    throw new ErrorException($message, 0, $severity, $file, $line);
});

function clean_header_send(string $value): string
{
    return trim(str_replace(["\r", "\n"], '', $value));
}

function send_mail_contact(string $to, string $subject, string $body, ?string $replyTo = null): bool
{
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $headers[] = 'Content-Transfer-Encoding: 8bit';
    $headers[] = 'From: Lauco Experience <no-reply@laucoexperience.it>';

    if ($replyTo) {
        $headers[] = 'Reply-To: ' . clean_header_send($replyTo);
    }

    return @mail(
        clean_header_send($to),
        clean_header_send($subject),
        $body,
        implode("\r\n", $headers)
    );
}

function ensure_contact_table(PDO $pdo): void
{
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `contatti_messaggi` (
          `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
          `codice` VARCHAR(32) NOT NULL,
          `stato` ENUM('nuovo','letto','risposto','archiviato') NOT NULL DEFAULT 'nuovo',
          `nome` VARCHAR(150) NOT NULL,
          `email` VARCHAR(190) NOT NULL,
          `oggetto` VARCHAR(190) NOT NULL,
          `messaggio` TEXT NOT NULL,
          `privacy` TINYINT(1) NOT NULL DEFAULT 1,
          `mail_admin_sent` TINYINT(1) NOT NULL DEFAULT 0,
          `mail_customer_sent` TINYINT(1) NOT NULL DEFAULT 0,
          `ip_address` VARCHAR(80) NULL,
          `user_agent` VARCHAR(255) NULL,
          `note_admin` TEXT NULL,
          `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uniq_contatti_codice` (`codice`),
          KEY `idx_contatti_stato_created` (`stato`, `created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        send_json('error', 'Richiesta non valida. Ricarica la pagina contatti e riprova.', 200);
    }

    require_once __DIR__ . '/inc/db.php';

    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('Connessione database non disponibile.');
    }

    ensure_contact_table($pdo);

    $token = (string) ($_POST['_csrf_token'] ?? '');

    if (!$token || !isset($_SESSION['contact_token']) || !hash_equals($_SESSION['contact_token'], $token)) {
        throw new RuntimeException('Sessione scaduta. Ricarica la pagina e riprova.');
    }

    if (!empty($_POST['website'] ?? '')) {
        throw new RuntimeException('Richiesta non valida.');
    }

    $nome = trim($_POST['name'] ?? '');
    $email = trim($_POST['mail'] ?? '');
    $oggetto = trim($_POST['subjectForm'] ?? '');
    $messaggio = trim($_POST['messageForm'] ?? '');

    if ($nome === '') {
        throw new RuntimeException('Inserisci il nome.');
    }

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('Inserisci una email valida.');
    }

    if ($oggetto === '') {
        throw new RuntimeException('Inserisci l’oggetto.');
    }

    if ($messaggio === '' || strlen($messaggio) < 10) {
        throw new RuntimeException('Inserisci un messaggio di almeno 10 caratteri.');
    }

    if (empty($_POST['privacy'])) {
        throw new RuntimeException('Devi confermare il consenso al trattamento del messaggio.');
    }

    $codice = 'MSG-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));

    $adminBody =
        "Nuovo messaggio dal sito Lauco Experience\n\n" .
        "Codice: {$codice}\n" .
        "Nome: {$nome}\n" .
        "Email: {$email}\n" .
        "Oggetto: {$oggetto}\n\n" .
        "Messaggio:\n{$messaggio}\n";

    $customerBody =
        "Buongiorno {$nome},\n\n" .
        "abbiamo ricevuto il tuo messaggio tramite il sito Lauco Experience.\n\n" .
        "Codice riferimento: {$codice}\n" .
        "Oggetto: {$oggetto}\n\n" .
        "Cordiali saluti\n" .
        "Lauco Experience\n";

    $adminSent = send_mail_contact(
        'comune.lauco@certgov.fvg.it',
        '[Lauco Experience] ' . $oggetto,
        $adminBody,
        $email
    ) ? 1 : 0;

    $customerSent = send_mail_contact(
        $email,
        'Abbiamo ricevuto il tuo messaggio - ' . $codice,
        $customerBody,
        'comune.lauco@certgov.fvg.it'
    ) ? 1 : 0;

    $stmt = $pdo->prepare("
        INSERT INTO contatti_messaggi
            (codice, nome, email, oggetto, messaggio, privacy, mail_admin_sent, mail_customer_sent, ip_address, user_agent)
        VALUES
            (:codice, :nome, :email, :oggetto, :messaggio, 1, :mail_admin_sent, :mail_customer_sent, :ip_address, :user_agent)
    ");

    $stmt->execute([
        'codice' => $codice,
        'nome' => $nome,
        'email' => $email,
        'oggetto' => $oggetto,
        'messaggio' => $messaggio,
        'mail_admin_sent' => $adminSent,
        'mail_customer_sent' => $customerSent,
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
    ]);

    unset($_SESSION['contact_token']);

    $msg = 'Messaggio inviato correttamente. Codice riferimento: ' . htmlspecialchars($codice, ENT_QUOTES, 'UTF-8') . '.';

    if (!$adminSent || !$customerSent) {
        $msg .= '<br>Il messaggio è stato salvato nel database, ma il server potrebbe non aver inviato una o entrambe le email.';
    }

    send_json('success', $msg, 200);
} catch (Throwable $e) {
    send_json('error', $e->getMessage(), 200);
}
