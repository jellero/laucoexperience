<?php
declare(strict_types=1);

namespace LaucoExperience\Http\Action;

use DateTimeImmutable;
use DateTimeZone;
use LaucoExperience\Http\JsonResponse;
use LaucoExperience\Http\RequestInput;
use LaucoExperience\Localization\LocaleResolver;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

        $now = new DateTimeImmutable('now', new DateTimeZone('Europe/Rome'));
        $server = $request->getServerParams();
        $body = "Nuova richiesta di iscrizione alla newsletter.\n\n"
            . "Email: {$email}\n"
            . 'Data: ' . $now->format('d/m/Y H:i:s') . "\n"
            . 'Indirizzo IP: ' . ($server['REMOTE_ADDR'] ?? 'non disponibile') . "\n"
            . 'Browser: ' . ($request->getHeaderLine('User-Agent') ?: 'non disponibile') . "\n";
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
            error_log('[Newsletter] Invio non riuscito.');
            return $this->respond($response, false, site_text(
                'runtime.newsletter.3ae0c3e48e',
                null,
                'Non è stato possibile inviare la richiesta. Riprova più tardi.'
            ), 500, $locale);
        }

        return $this->respond($response, true, site_text(
            'runtime.newsletter.584c399065',
            null,
            'Grazie! La richiesta di iscrizione è stata inviata correttamente.'
        ), 200, $locale);
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
