<?php
declare(strict_types=1);

namespace LaucoExperience\Http\Action;

use LaucoExperience\Http\JsonResponse;
use LaucoExperience\Http\RequestInput;
use LaucoExperience\Localization\LocaleResolver;
use PDO;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;

final class VolunteerSignupAction
{
    public function __construct(private readonly string $root, private readonly LocaleResolver $locales)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        require_once $this->root . '/inc/translations.php';
        require_once $this->root . '/inc/volontariato.php';
        $locale = $this->locales->resolve($request);
        $_GET['lang'] = $locale;
        if (strtoupper($request->getMethod()) !== 'POST') {
            return $this->respond($response, false, site_text('volunteer.error.method', $locale, 'Metodo non consentito.'), 405, $locale);
        }

        $data = RequestInput::form($request);
        if (trim((string) ($data['company_website'] ?? '')) !== '') {
            return $this->respond($response, true, site_text('volunteer.success', $locale, 'Disponibilità registrata.'), 200, $locale);
        }
        $name = trim((string) ($data['nome'] ?? ''));
        $phone = volontariato_normalize_phone((string) ($data['telefono'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $zone = trim((string) ($data['zona'] ?? ''));
        $availability = trim((string) ($data['disponibilita'] ?? ''));
        $submittedInterests = is_array($data['interessi'] ?? null) ? $data['interessi'] : [];
        $interests = array_values(array_intersect(array_keys(volontariato_interessi()), array_map('strval', $submittedInterests)));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 150) {
            return $this->respond($response, false, site_text('volunteer.error.name', $locale, 'Inserisci nome e cognome.'), 422, $locale);
        }
        if ($phone === null) {
            return $this->respond($response, false, site_text('volunteer.error.phone', $locale, 'Inserisci un numero WhatsApp valido.'), 422, $locale);
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->respond($response, false, site_text('volunteer.error.email', $locale, 'Inserisci un indirizzo email valido.'), 422, $locale);
        }
        if ($zone !== '' && !in_array($zone, volontariato_zone(), true)) {
            return $this->respond($response, false, site_text('volunteer.error.zone', $locale, 'Seleziona una zona valida.'), 422, $locale);
        }
        if ($availability !== '' && !in_array($availability, volontariato_disponibilita(), true)) {
            return $this->respond($response, false, site_text('volunteer.error.availability', $locale, 'Seleziona una disponibilità valida.'), 422, $locale);
        }
        if ($interests === []) {
            return $this->respond($response, false, site_text('volunteer.error.interests', $locale, 'Scegli almeno un’attività.'), 422, $locale);
        }
        foreach (['consenso_privacy', 'consenso_whatsapp', 'consenso_gruppo', 'maggiorenne'] as $required) {
            if (empty($data[$required])) {
                return $this->respond($response, false, site_text('volunteer.error.consent', $locale, 'Per procedere devi confermare tutti i consensi richiesti.'), 422, $locale);
            }
        }

        try {
            require $this->root . '/inc/db.php';
            $connection = $pdo ?? ($GLOBALS['pdo'] ?? null);
            if (!$connection instanceof PDO) {
                throw new RuntimeException('Connessione database non disponibile.');
            }
            $server = $request->getServerParams();
            $connection->beginTransaction();
            $find = $connection->prepare('SELECT id FROM volontari WHERE telefono = :telefono LIMIT 1 FOR UPDATE');
            $find->execute(['telefono' => $phone]);
            $volunteerId = (int) $find->fetchColumn();
            $params = [
                'nome' => $name, 'telefono' => $phone, 'email' => $email !== '' ? $email : null,
                'zona' => $zone !== '' ? $zone : null,
                'interessi' => json_encode($interests, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'disponibilita' => $availability !== '' ? $availability : null, 'locale' => $locale,
                'ip' => mb_substr((string) ($server['REMOTE_ADDR'] ?? ''), 0, 80) ?: null,
                'ua' => mb_substr($request->getHeaderLine('User-Agent'), 0, 255) ?: null,
            ];
            if ($volunteerId > 0) {
                $params['id'] = $volunteerId;
                $connection->prepare(
                    "UPDATE volontari SET nome=:nome,email=:email,zona=:zona,interessi_json=:interessi,disponibilita=:disponibilita,locale=:locale,"
                    . "consenso_privacy=1,consenso_whatsapp=1,consenso_visibilita_gruppo=1,maggiorenne=1,consenso_at=NOW(),ip_address=:ip,user_agent=:ua,"
                    . "stato=IF(stato='ritirato','da_confermare',stato) WHERE id=:id"
                )->execute($params);
            } else {
                $params['codice'] = volontariato_code();
                $connection->prepare(
                    'INSERT INTO volontari (codice,nome,telefono,email,zona,interessi_json,disponibilita,locale,ip_address,user_agent) '
                    . 'VALUES (:codice,:nome,:telefono,:email,:zona,:interessi,:disponibilita,:locale,:ip,:ua)'
                )->execute($params);
                $volunteerId = (int) $connection->lastInsertId();
            }

            $group = volontariato_default_group($connection, $zone);
            $outboxId = null;
            if (is_array($group)) {
                $connection->prepare(
                    'INSERT INTO volontari_gruppi_membri (gruppo_id,volontario_id,stato) VALUES (:gruppo,:volontario,\'assegnato\') '
                    . 'ON DUPLICATE KEY UPDATE stato = IF(stato IN (\'uscito\',\'errore\'),\'assegnato\',stato), ultimo_errore = NULL'
                )->execute(['gruppo' => (int) $group['id'], 'volontario' => $volunteerId]);
                $membership = $connection->prepare('SELECT stato FROM volontari_gruppi_membri WHERE gruppo_id=:gruppo AND volontario_id=:volontario');
                $membership->execute(['gruppo' => (int) $group['id'], 'volontario' => $volunteerId]);
                if (in_array((string) $membership->fetchColumn(), ['assegnato', 'errore'], true)) {
                    $outboxId = volontariato_queue_invite($connection, $volunteerId, (int) $group['id']);
                }
            }
            $connection->commit();
            if ($outboxId !== null) {
                volontariato_dispatch_outbox($connection, $outboxId, 1);
            }
            return $this->respond($response, true, site_text(
                'volunteer.success', $locale,
                'Grazie! La disponibilità è registrata. Riceverai su WhatsApp il link per entrare nel gruppo operativo.'
            ), 200, $locale);
        } catch (Throwable $error) {
            if (isset($connection) && $connection instanceof PDO && $connection->inTransaction()) {
                $connection->rollBack();
            }
            error_log('[Volontariato] ' . $error->getMessage());
            return $this->respond($response, false, site_text('volunteer.error.generic', $locale, 'Non è stato possibile registrare la disponibilità. Riprova più tardi.'), 500, $locale);
        }
    }

    private function respond(ResponseInterface $response, bool $success, string $message, int $status, string $locale): ResponseInterface
    {
        return JsonResponse::create($response, ['success' => $success, 'message' => $message], $status)
            ->withHeader('Content-Language', $locale)
            ->withHeader('Cache-Control', 'no-store');
    }
}
