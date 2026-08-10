<?php
declare(strict_types=1);

namespace LaucoExperience\Http\Action;

use DateTimeImmutable;
use DateTimeZone;
use LaucoExperience\Http\JsonResponse;
use LaucoExperience\Http\RequestInput;
use LaucoExperience\Localization\LocaleResolver;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

final class NewsletterSubscribeAction
{
    public function __construct(
        private readonly string $root,
        private readonly LocaleResolver $locales,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        require_once $this->root . '/inc/translations.php';
        $locale = $this->locales->resolve($request);
        $_GET['lang'] = $locale;

        if (strtoupper($request->getMethod()) !== 'POST') {
            return $this->respond($response, false, site_text(
                'runtime.newsletter.75be03f41b',
                null,
                'Metodo non consentito. Utilizza il modulo newsletter.'
            ), 405, $locale);
        }

        $data = RequestInput::form($request);
        if (trim((string) ($data['company_website'] ?? '')) !== '') {
            return $this->respond($response, true, site_text(
                'runtime.footer.a2356c1d53',
                null,
                'Grazie! La richiesta è stata inviata correttamente.'
            ), 200, $locale);
        }

        $email = trim((string) ($data['email'] ?? ''));
        if ($email === '') {
            return $this->respond($response, false, site_text('runtime.newsletter.1e00992632', null, 'Inserisci il tuo indirizzo email.'), 422, $locale);
        }
        if (preg_match('/[\r\n]/', $email)) {
            return $this->respond($response, false, site_text('runtime.newsletter.038d5f0253', null, 'L’indirizzo email contiene caratteri non consentiti.'), 422, $locale);
        }
        if (strlen($email) > 254) {
            return $this->respond($response, false, site_text('runtime.newsletter.f64d7b7f76', null, 'L’indirizzo email inserito è troppo lungo.'), 422, $locale);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->respond($response, false, site_text('runtime.newsletter.4da262a4a9', null, 'Inserisci un indirizzo email valido.'), 422, $locale);
        }

        try {
            require_once $this->root . '/inc/db.php';
            $connection = $pdo ?? ($GLOBALS['pdo'] ?? null);
            if (!$connection instanceof PDO) {
                throw new RuntimeException('Connessione database non disponibile.');
            }

            $server = $request->getServerParams();
            $ipAddress = mb_substr((string) ($server['REMOTE_ADDR'] ?? ''), 0, 80);
            $userAgent = mb_substr($request->getHeaderLine('User-Agent'), 0, 255);

            $find = $connection->prepare(
                'SELECT id, status FROM newsletter_subscribers WHERE LOWER(email) = LOWER(:email) LIMIT 1'
            );
            $find->execute(['email' => $email]);
            $existing = $find->fetch();

            $notifyAdmin = false;
            if (is_array($existing)) {
                $reactivating = (string) ($existing['status'] ?? '') !== 'active';
                $subscribedAtSql = $reactivating ? ', subscribed_at = CURRENT_TIMESTAMP' : '';
                $update = $connection->prepare(
                    'UPDATE newsletter_subscribers SET '
                    . "email = :email, status = 'active', locale = :locale, ip_address = :ip_address, "
                    . 'user_agent = :user_agent, unsubscribed_at = NULL'
                    . $subscribedAtSql
                    . ' WHERE id = :id'
                );
                $update->execute([
                    'email' => $email,
                    'locale' => $locale,
                    'ip_address' => $ipAddress !== '' ? $ipAddress : null,
                    'user_agent' => $userAgent !== '' ? $userAgent : null,
                    'id' => (int) $existing['id'],
                ]);
                $notifyAdmin = $reactivating;
            } else {
                $insert = $connection->prepare(
                    'INSERT INTO newsletter_subscribers '
                    . '(email, status, locale, ip_address, user_agent) '
                    . "VALUES (:email, 'active', :locale, :ip_address, :user_agent)"
                );
                $insert->execute([
                    'email' => $email,
                    'locale' => $locale,
                    'ip_address' => $ipAddress !== '' ? $ipAddress : null,
                    'user_agent' => $userAgent !== '' ? $userAgent : null,
                ]);
                $notifyAdmin = true;
            }

            if ($notifyAdmin) {
                $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Rome'));
                $body = "Nuova iscrizione alla newsletter.\n\n"
                    . "Email: {$email}\n"
                    . 'Data: ' . $now->format('d/m/Y H:i:s') . "\n"
                    . 'Indirizzo IP: ' . ($ipAddress !== '' ? $ipAddress : 'non disponibile') . "\n"
                    . 'Browser: ' . ($userAgent !== '' ? $userAgent : 'non disponibile') . "\n";
                $subject = function_exists('mb_encode_mimeheader')
                    ? mb_encode_mimeheader('Nuova iscrizione alla newsletter', 'UTF-8', 'B', "\r\n")
                    : 'Nuova iscrizione alla newsletter';
                $headers = [
                    'From: Lauco Experience <info@laucoexperience.it>',
                    'Reply-To: ' . $email,
                    'MIME-Version: 1.0',
                    'Content-Type: text/plain; charset=UTF-8',
                    'Content-Transfer-Encoding: 8bit',
                    'X-Mailer: PHP/' . phpversion(),
                ];

                if (!mail('info@laucoexperience.it', $subject, wordwrap($body, 70), implode("\r\n", $headers))) {
                    error_log('[Newsletter] Notifica amministratore non riuscita.');
                }
            }

            return $this->respond($response, true, site_text(
                'runtime.newsletter.584c399065',
                null,
                'Grazie! La tua iscrizione alla newsletter è stata registrata.'
            ), 200, $locale);
        } catch (Throwable $exception) {
            error_log('[Newsletter] ' . $exception->getMessage());
            return $this->respond($response, false, site_text(
                'runtime.newsletter.3ae0c3e48e',
                null,
                'Non è stato possibile registrare l’iscrizione. Riprova più tardi.'
            ), 500, $locale);
        }
    }

    private function respond(
        ResponseInterface $response,
        bool $success,
        string $message,
        int $status,
        string $locale,
    ): ResponseInterface {
        return JsonResponse::create($response, ['success' => $success, 'message' => $message], $status)
            ->withHeader('Content-Language', $locale);
    }
}
