<?php
declare(strict_types=1);

namespace LaucoExperience\Http\Action;

use LaucoExperience\Http\JsonResponse;
use LaucoExperience\Http\RequestInput;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

final class ContactSubmitAction
{
    public function __construct(private readonly string $root)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        if (strtoupper($request->getMethod()) !== 'POST') {
            return JsonResponse::create($response, [
                'info' => 'error',
                'msg' => 'Metodo non consentito.',
            ], 405);
        }

        try {
            require_once $this->root . '/inc/db.php';
            require_once $this->root . '/inc/contact-mail.php';
            $connection = $pdo ?? ($GLOBALS['pdo'] ?? null);
            if (!$connection instanceof PDO) {
                throw new RuntimeException('Connessione database non disponibile.');
            }
            if (session_status() !== PHP_SESSION_ACTIVE) {
                session_start();
            }

            $data = RequestInput::form($request);
            $token = (string) ($data['_csrf_token'] ?? '');
            if ($token === '' || !is_string($_SESSION['contact_token'] ?? null) || !hash_equals($_SESSION['contact_token'], $token)) {
                throw new RuntimeException('Sessione scaduta. Ricarica la pagina e riprova.');
            }
            if (trim((string) ($data['website'] ?? '')) !== '') {
                throw new RuntimeException('Richiesta non valida.');
            }

            $message = [
                'nome' => trim((string) ($data['name'] ?? '')),
                'email' => trim((string) ($data['mail'] ?? '')),
                'oggetto' => trim((string) ($data['subjectForm'] ?? '')),
                'messaggio' => trim((string) ($data['messageForm'] ?? '')),
            ];
            if ($message['nome'] === '') {
                throw new RuntimeException('Inserisci il nome.');
            }
            if (!filter_var($message['email'], FILTER_VALIDATE_EMAIL)) {
                throw new RuntimeException('Inserisci una email valida.');
            }
            if ($message['oggetto'] === '') {
                throw new RuntimeException('Inserisci l’oggetto.');
            }
            if (mb_strlen($message['messaggio']) < 10) {
                throw new RuntimeException('Inserisci un messaggio di almeno 10 caratteri.');
            }
            if (empty($data['privacy'])) {
                throw new RuntimeException('Devi confermare il consenso al trattamento del messaggio.');
            }

            $code = 'MSG-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
            $adminSent = contact_send_mail(
                CONTACT_TO_EMAIL,
                '[Lauco Experience] ' . $message['oggetto'],
                contact_admin_body($message, $code),
                $message['email']
            ) ? 1 : 0;
            $customerSent = contact_send_mail(
                $message['email'],
                CONTACT_REPLY_SUBJECT_PREFIX . ' - ' . $code,
                contact_customer_body($message, $code),
                CONTACT_TO_EMAIL
            ) ? 1 : 0;

            $statement = $connection->prepare(
                'INSERT INTO contatti_messaggi '
                . '(codice, nome, email, oggetto, messaggio, privacy, mail_admin_sent, mail_customer_sent, ip_address, user_agent) '
                . 'VALUES (:codice, :nome, :email, :oggetto, :messaggio, 1, :mail_admin_sent, :mail_customer_sent, :ip_address, :user_agent)'
            );
            $statement->execute([
                'codice' => $code,
                ...$message,
                'mail_admin_sent' => $adminSent,
                'mail_customer_sent' => $customerSent,
                'ip_address' => $request->getServerParams()['REMOTE_ADDR'] ?? null,
                'user_agent' => mb_substr($request->getHeaderLine('User-Agent'), 0, 255),
            ]);
            unset($_SESSION['contact_token']);

            $result = 'Messaggio inviato correttamente. Codice riferimento: '
                . htmlspecialchars($code, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '.';
            if (!$adminSent || !$customerSent) {
                $result .= '<br>Il messaggio è stato salvato, ma il server potrebbe non aver inviato una o entrambe le email.';
            }
            return JsonResponse::create($response, ['info' => 'success', 'msg' => $result]);
        } catch (Throwable $exception) {
            error_log('[Contact submit] ' . $exception->getMessage());
            $message = $exception instanceof RuntimeException
                ? $exception->getMessage()
                : 'Errore interno del server durante l’invio.';
            return JsonResponse::create($response, ['info' => 'error', 'msg' => $message]);
        }
    }
}
