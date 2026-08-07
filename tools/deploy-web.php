<?php
declare(strict_types=1);

/**
 * Lauco Experience - deploy web del branch conservativo.
 * Caricare come deploy.php nel document root dello staging.
 */

const DEPLOY_REPOSITORY = 'jellero/laucoexperience.it';
const DEPLOY_BRANCH = 'main';
const DEPLOY_APP_URL = 'https://dev.laucoexperience.it';
const DEPLOY_LIVE_DIR = 'lauco-site';
const DEPLOY_OLD_LIVE_DIR = 'lauco-v2';
const DEPLOY_STATE_DIR = '.lauco-deploy';
const DEPLOY_MAX_ARCHIVE_BYTES = 250_000_000;
const DEPLOY_BACKUPS_TO_KEEP = 3;
const DEPLOY_DIR_MODE = 0755;
const DEPLOY_FILE_MODE = 0644;
const DEPLOY_UPLOAD_DIR_MODE = 0775;
const DEPLOY_UPLOAD_FILE_MODE = 0664;
const DEPLOY_SECRET_MODE = 0600;

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
set_time_limit(300);

securityHeaders();
secureSession();

$root = __DIR__;
$stateDir = $root . '/' . DEPLOY_STATE_DIR;
$liveDir = $root . '/' . DEPLOY_LIVE_DIR;
$stateFile = $stateDir . '/state.json';
ensurePrivateDirectory($stateDir);

if (!isHttps() && !isLocal()) {
    page('HTTPS richiesto', '<div class="alert error">Apri questo file tramite HTTPS.</div>');
    exit;
}

$state = readJson($stateFile);
$action = (string) ($_POST['action'] ?? '');

if ($state === null) {
    firstRun($stateFile, $action);
    exit;
}

if ($action === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    $_SESSION = [];
    session_regenerate_id(true);
    redirectSelf();
}

if (!($_SESSION['deploy_authenticated'] ?? false)) {
    login($state, $action);
    exit;
}

if ($action === 'deploy' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrfCheck();
    deploy($root, $stateDir, $liveDir);
    exit;
}

form($root, $liveDir);

function securityHeaders(): void
{
    header_remove('X-Powered-By');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer');
    header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
}

function secureSession(): void
{
    session_name('lauco_deployer');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => isHttps(),
        'samesite' => 'Strict',
        'path' => '/',
    ]);
    session_start();
    $_SESSION['deploy_csrf'] ??= bin2hex(random_bytes(32));
}

function isHttps(): bool
{
    return (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';
}

function isLocal(): bool
{
    return in_array((string) ($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1', '::1'], true);
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . h((string) $_SESSION['deploy_csrf']) . '">';
}

function csrfCheck(): void
{
    $actual = (string) ($_POST['csrf'] ?? '');
    $expected = (string) ($_SESSION['deploy_csrf'] ?? '');
    if ($actual === '' || $expected === '' || !hash_equals($expected, $actual)) {
        throw new RuntimeException('Token CSRF non valido. Ricarica la pagina.');
    }
}

function redirectSelf(): never
{
    header('Location: ' . (string) ($_SERVER['SCRIPT_NAME'] ?? '/deploy.php'));
    exit;
}

function ensurePrivateDirectory(string $directory): void
{
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Impossibile creare la directory di stato.');
    }
    chmodSafe($directory, 0700);
    $deny = "Options -Indexes\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n";
    if (!is_file($directory . '/.htaccess')) {
        file_put_contents($directory . '/.htaccess', $deny, LOCK_EX);
    }
    if (!is_file($directory . '/index.html')) {
        file_put_contents($directory . '/index.html', '', LOCK_EX);
    }
    chmodSafe($directory . '/.htaccess', 0600);
    chmodSafe($directory . '/index.html', 0600);
}

function firstRun(string $stateFile, string $action): void
{
    $error = '';
    if ($action === 'setup' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            csrfCheck();
            $password = (string) ($_POST['deploy_password'] ?? '');
            $confirm = (string) ($_POST['deploy_password_confirm'] ?? '');
            if (strlen($password) < 16) {
                throw new RuntimeException('La password deve contenere almeno 16 caratteri.');
            }
            if (!hash_equals($password, $confirm)) {
                throw new RuntimeException('Le password non coincidono.');
            }
            writeJson($stateFile, [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'created_at' => gmdate(DATE_ATOM),
            ]);
            session_regenerate_id(true);
            $_SESSION['deploy_authenticated'] = true;
            redirectSelf();
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }

    page('Configura deploy', '<div class="card"><h1>Configura il deploy</h1>'
        . ($error ? '<div class="alert error">' . h($error) . '</div>' : '')
        . '<form method="post">' . csrfField() . '<input type="hidden" name="action" value="setup">'
        . input('Password deploy', 'deploy_password', 'password', '', true, 'minlength="16"')
        . input('Ripeti password', 'deploy_password_confirm', 'password', '', true, 'minlength="16"')
        . '<button>Attiva installer</button></form></div>');
}

function login(array $state, string $action): void
{
    $error = '';
    if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        try {
            csrfCheck();
            $blocked = (int) ($_SESSION['deploy_blocked_until'] ?? 0);
            if ($blocked > time()) {
                throw new RuntimeException('Troppi tentativi. Riprova più tardi.');
            }
            $password = (string) ($_POST['deploy_password'] ?? '');
            if (!password_verify($password, (string) ($state['password_hash'] ?? ''))) {
                $attempts = (int) ($_SESSION['deploy_attempts'] ?? 0) + 1;
                $_SESSION['deploy_attempts'] = $attempts;
                if ($attempts >= 5) {
                    $_SESSION['deploy_blocked_until'] = time() + 300;
                    $_SESSION['deploy_attempts'] = 0;
                }
                throw new RuntimeException('Password non valida.');
            }
            session_regenerate_id(true);
            $_SESSION['deploy_authenticated'] = true;
            unset($_SESSION['deploy_attempts'], $_SESSION['deploy_blocked_until']);
            redirectSelf();
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }

    page('Login deploy', '<div class="card"><h1>Deploy Lauco Experience</h1>'
        . '<p>Branch: <code>' . h(DEPLOY_BRANCH) . '</code></p>'
        . ($error ? '<div class="alert error">' . h($error) . '</div>' : '')
        . '<form method="post">' . csrfField() . '<input type="hidden" name="action" value="login">'
        . input('Password deploy', 'deploy_password', 'password', '', true, 'autofocus')
        . '<button>Accedi</button></form></div>');
}

function form(string $root, string $liveDir, string $error = '', ?array $result = null): void
{
    $env = findExistingEnv($root, $liveDir);
    $uploads = findExistingUploads($root, $liveDir);
    $meta = readJson($liveDir . '/.deploy-meta.json');
    $current = $meta ? (string) ($meta['commit'] ?? 'sconosciuto') : 'nessuna release gestita';

    $body = '<div class="topbar"><div><strong>Lauco Experience</strong><small>Deploy del sito originale</small></div>'
        . '<form method="post">' . csrfField() . '<input type="hidden" name="action" value="logout"><button class="secondary">Esci</button></form></div>'
        . '<div class="card wide"><h1>Aggiorna da GitHub</h1>'
        . '<div class="status"><span>Branch</span><code>' . h(DEPLOY_BRANCH) . '</code><span>Release</span><code>' . h($current) . '</code></div>'
        . ($error ? '<div class="alert error">' . h($error) . '</div>' : '')
        . ($result ? resultHtml($result) : '')
        . '<form method="post" autocomplete="off">' . csrfField() . '<input type="hidden" name="action" value="deploy">'
        . '<fieldset><legend>Applicazione</legend>'
        . input('URL staging', 'app_url', 'url', DEPLOY_APP_URL, true)
        . '</fieldset>'
        . '<fieldset><legend>Configurazione esistente</legend>';

    if ($env !== null) {
        $body .= '<label class="check"><input type="checkbox" name="preserve_env" value="1" checked>'
            . '<span><strong>Riusa automaticamente il file .env esistente</strong><br><code>' . h(displayPath($root, $env)) . '</code><br>'
            . '<small>Lascia selezionato: database, OpenAI e configurazione vengono copiati senza reinserire alcun dato.</small></span></label>';
    } else {
        $body .= '<div class="alert info">Nessun .env esistente rilevato. Compila i dati database per il primo deploy.</div>'
            . '<div class="grid">'
            . input('Host database', 'db_host', 'text', 'localhost', true)
            . input('Porta', 'db_port', 'number', '3306', true)
            . input('Nome database', 'db_name', 'text', '', true)
            . input('Utente database', 'db_user', 'text', '', true)
            . '</div>'
            . input('Password database', 'db_pass', 'password', '', true)
            . input('API key OpenAI (opzionale)', 'openai_api_key', 'password');
    }

    $body .= '</fieldset><fieldset><legend>Dati persistenti</legend>'
        . '<label class="check"><input type="checkbox" name="preserve_uploads" value="1" checked><span>Conserva uploads'
        . ($uploads ? ' da <code>' . h(displayPath($root, $uploads)) . '</code>' : '') . '</span></label>'
        . '<label class="check"><input type="checkbox" name="install_router" value="1" checked><span>Installa il router .htaccess</span></label>'
        . '<p class="hint">Permessi finali: directory 0755, file 0644, directory upload 0775, file upload 0664, .env 0600.</p>'
        . '</fieldset><fieldset><legend>Conferma</legend>'
        . input('Scrivi DEPLOY', 'confirmation', 'text', '', true, 'pattern="DEPLOY"')
        . '</fieldset><button>Scarica, verifica e pubblica</button></form></div>';

    page('Deploy Lauco Experience', $body);
}

function deploy(string $root, string $stateDir, string $liveDir): void
{
    $lock = null;
    $work = null;
    $release = null;
    $backup = null;
    $activated = false;

    try {
        runtimeCheck();
        if ((string) ($_POST['confirmation'] ?? '') !== 'DEPLOY') {
            throw new RuntimeException('Scrivi DEPLOY per confermare.');
        }

        $lock = fopen($stateDir . '/deploy.lock', 'c+');
        if ($lock === false || !flock($lock, LOCK_EX | LOCK_NB)) {
            throw new RuntimeException('È già in corso un deploy.');
        }

        $existingEnv = findExistingEnv($root, $liveDir);
        $preserveEnv = isset($_POST['preserve_env']) && $existingEnv !== null;
        $preserveUploads = isset($_POST['preserve_uploads']);
        $appUrl = rtrim((string) ($_POST['app_url'] ?? ''), '/');
        if (!filter_var($appUrl, FILTER_VALIDATE_URL) || parse_url($appUrl, PHP_URL_SCHEME) !== 'https') {
            throw new RuntimeException('URL staging non valido.');
        }

        $work = $stateDir . '/tmp-' . bin2hex(random_bytes(6));
        $release = $stateDir . '/release-' . gmdate('YmdHis') . '-' . bin2hex(random_bytes(3));
        mkdirOrFail($work, 0700);
        mkdirOrFail($release, DEPLOY_DIR_MODE);

        $commit = branchCommit();
        $zip = $work . '/source.zip';
        $extract = $work . '/extract';
        mkdirOrFail($extract, 0700);
        download('https://codeload.github.com/' . DEPLOY_REPOSITORY . '/zip/' . $commit, $zip, DEPLOY_MAX_ARCHIVE_BYTES);
        extractZip($zip, $extract);
        $roots = glob($extract . '/*', GLOB_ONLYDIR) ?: [];
        if (count($roots) !== 1) {
            throw new RuntimeException('Archivio GitHub non valido.');
        }
        validateSource($roots[0]);
        copyTree($roots[0], $release, '');
        installDependencies($release, $stateDir);

        if ($preserveEnv && $existingEnv !== null) {
            if (!copy($existingEnv, $release . '/.env')) {
                throw new RuntimeException('Impossibile copiare il .env esistente.');
            }
        } else {
            $env = newEnvFromPost($appUrl);
            if (file_put_contents($release . '/.env', $env, LOCK_EX) === false) {
                throw new RuntimeException('Impossibile creare il .env.');
            }
        }
        chmodSafe($release . '/.env', DEPLOY_SECRET_MODE);

        $uploadsSource = $preserveUploads ? findExistingUploads($root, $liveDir) : null;
        if ($uploadsSource !== null) {
            copyTree($uploadsSource, $release . '/uploads', 'uploads');
        }
        $translationsSource = findExistingTranslations($root, $liveDir);
        if ($translationsSource !== null) {
            copyTree($translationsSource, $release . '/storage/translations', 'storage/translations');
        }
        ensureUploads($release);
        fixPermissions($release);
        verifyRelease($release);

        $pdo = databaseFromEnv($release . '/.env');
        if (!tableExists($pdo, 'percorsi')) {
            throw new RuntimeException('Il database configurato non contiene la tabella percorsi.');
        }
        $migrations = migrate($pdo, $release . '/migrations');

        writeJson($release . '/.deploy-meta.json', [
            'repository' => DEPLOY_REPOSITORY,
            'branch' => DEPLOY_BRANCH,
            'commit' => $commit,
            'deployed_at' => gmdate(DATE_ATOM),
        ], DEPLOY_FILE_MODE);
        fixPermissions($release);

        $backup = switchRelease($liveDir, $release, $stateDir, $commit);
        $release = null;
        $activated = true;
        fixPermissions($liveDir);
        verifyRelease($liveDir);

        if (isset($_POST['install_router'])) {
            installRouter($root, $stateDir);
        }

        cleanupBackups($stateDir . '/backups', DEPLOY_BACKUPS_TO_KEEP);
        removeTree($work);
        $work = null;

        form($root, $liveDir, '', [
            'commit' => $commit,
            'migrations' => $migrations,
            'health' => health($appUrl),
            'env_source' => $existingEnv,
            'uploads_source' => $uploadsSource,
            'translations_source' => $translationsSource,
        ]);
    } catch (Throwable $e) {
        if ($activated) {
            rollback($liveDir, $backup, $stateDir);
        }
        if ($release !== null) {
            removeTree($release);
        }
        if ($work !== null) {
            removeTree($work);
        }
        form($root, $liveDir, $e->getMessage());
    } finally {
        if (is_resource($lock)) {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}

function runtimeCheck(): void
{
    if (PHP_VERSION_ID < 80300) {
        throw new RuntimeException('Serve PHP 8.3 o superiore.');
    }
    foreach (['curl', 'json', 'mbstring', 'pdo_mysql', 'zip'] as $extension) {
        if (!extension_loaded($extension)) {
            throw new RuntimeException('Estensione PHP mancante: ' . $extension);
        }
    }
}

function releaseRoots(string $root, string $liveDir): array
{
    $roots = [$liveDir, $root . '/' . DEPLOY_LIVE_DIR, $root . '/' . DEPLOY_OLD_LIVE_DIR, $root];
    $backups = glob($root . '/' . DEPLOY_STATE_DIR . '/backups/*', GLOB_ONLYDIR) ?: [];
    usort($backups, static fn (string $a, string $b): int => (filemtime($b) ?: 0) <=> (filemtime($a) ?: 0));
    array_push($roots, ...$backups);
    return array_values(array_unique(array_map(static fn (string $path): string => rtrim($path, '/\\'), $roots)));
}

function findExistingEnv(string $root, string $liveDir): ?string
{
    foreach (releaseRoots($root, $liveDir) as $candidateRoot) {
        $file = $candidateRoot . '/.env';
        if (is_file($file) && is_readable($file)) {
            return $file;
        }
    }
    return null;
}

function findExistingUploads(string $root, string $liveDir): ?string
{
    foreach (releaseRoots($root, $liveDir) as $candidateRoot) {
        $directory = $candidateRoot . '/uploads';
        if (is_dir($directory) && is_readable($directory)) {
            return $directory;
        }
    }
    return null;
}

function findExistingTranslations(string $root, string $liveDir): ?string
{
    foreach (releaseRoots($root, $liveDir) as $candidateRoot) {
        $directory = $candidateRoot . '/storage/translations';
        if (is_dir($directory) && is_readable($directory)) {
            return $directory;
        }
    }
    return null;
}

function branchCommit(): string
{
    $json = request('https://api.github.com/repos/' . DEPLOY_REPOSITORY . '/commits/' . rawurlencode(DEPLOY_BRANCH), 30, 2_000_000);
    $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    $sha = (string) ($data['sha'] ?? '');
    if (!preg_match('/^[a-f0-9]{40}$/', $sha)) {
        throw new RuntimeException('SHA del branch non valido.');
    }
    return $sha;
}

function request(string $url, int $timeout, int $maxBytes): string
{
    $body = '';
    $curl = curl_init($url);
    if ($curl === false) {
        throw new RuntimeException('Impossibile inizializzare cURL.');
    }
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => false,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_USERAGENT => 'LaucoExperience-Deployer/3.0',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body, $maxBytes): int {
            if (strlen($body) + strlen($chunk) > $maxBytes) {
                return 0;
            }
            $body .= $chunk;
            return strlen($chunk);
        },
    ]);
    if (curl_exec($curl) === false) {
        $message = curl_error($curl);
        curl_close($curl);
        throw new RuntimeException('Richiesta HTTPS fallita: ' . $message);
    }
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);
    if ($status < 200 || $status >= 300) {
        throw new RuntimeException('GitHub ha risposto HTTP ' . $status . '.');
    }
    return $body;
}

function download(string $url, string $destination, int $maxBytes): void
{
    $body = request($url, 120, $maxBytes);
    if (file_put_contents($destination, $body, LOCK_EX) === false) {
        throw new RuntimeException('Impossibile salvare l’archivio.');
    }
}

function extractZip(string $archive, string $destination): void
{
    $zip = new ZipArchive();
    if ($zip->open($archive) !== true) {
        throw new RuntimeException('Archivio non leggibile.');
    }
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = str_replace('\\', '/', (string) $zip->getNameIndex($i));
        if ($name === '' || str_starts_with($name, '/') || str_contains($name, "\0") || preg_match('~(^|/)\.\.(?:/|$)~', $name)) {
            $zip->close();
            throw new RuntimeException('Archivio non sicuro.');
        }
    }
    if (!$zip->extractTo($destination)) {
        $zip->close();
        throw new RuntimeException('Estrazione fallita.');
    }
    $zip->close();
}

function validateSource(string $source): void
{
    foreach (['index.php', '.htaccess', 'composer.json', 'composer.lock', 'public/index.php', 'admin/index.php', 'admin/_admin_layout.php', 'inc/db.php', 'inc/env.php', 'tools/migrate-logic.php'] as $required) {
        if (!is_file($source . '/' . $required)) {
            throw new RuntimeException('Release incompleta: manca ' . $required);
        }
    }
}

function installDependencies(string $release, string $stateDir): void
{
    if (is_readable($release . '/vendor/autoload.php')) {
        return;
    }
    if (!function_exists('proc_open')) {
        throw new RuntimeException('proc_open non è disponibile: impossibile installare le dipendenze Composer.');
    }

    $signature = trim(request('https://composer.github.io/installer.sig', 30, 1_000));
    if (!preg_match('/^[a-f0-9]{96}$/i', $signature)) {
        throw new RuntimeException('Firma Composer non valida.');
    }
    $composer = $stateDir . '/composer.phar';
    if (!is_file($composer) || !hash_equals(strtolower($signature), (string) hash_file('sha384', $composer))) {
        download('https://getcomposer.org/download/latest-stable/composer.phar', $composer, 5_000_000);
    }
    if (!hash_equals(strtolower($signature), (string) hash_file('sha384', $composer))) {
        throw new RuntimeException('Verifica crittografica di Composer fallita.');
    }
    chmodSafe($composer, 0700);

    $php = null;
    foreach (array_unique([PHP_BINDIR . '/php', '/usr/local/bin/php', '/usr/bin/php', PHP_BINARY]) as $candidate) {
        if (is_file($candidate) && is_executable($candidate) && !preg_match('/(?:fpm|cgi)$/i', basename($candidate))) {
            $php = $candidate;
            break;
        }
    }
    if ($php === null) {
        throw new RuntimeException('Interprete PHP CLI non disponibile per Composer.');
    }

    $composerHome = $stateDir . '/composer-home';
    mkdirOrFail($composerHome, 0700);
    $environment = getenv();
    $environment = is_array($environment) ? $environment : [];
    $environment['COMPOSER_HOME'] = $composerHome;
    $environment['COMPOSER_ALLOW_SUPERUSER'] = '1';
    $process = proc_open(
        [
            $php,
            $composer,
            'install',
            '--working-dir=' . $release,
            '--no-dev',
            '--prefer-dist',
            '--optimize-autoloader',
            '--no-interaction',
            '--no-progress',
        ],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        $release,
        $environment
    );
    if (!is_resource($process)) {
        throw new RuntimeException('Impossibile avviare Composer.');
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    if ($exitCode !== 0 || !is_readable($release . '/vendor/autoload.php')) {
        $details = trim((string) ($stderr !== '' ? $stderr : $stdout));
        throw new RuntimeException('Installazione Composer fallita' . ($details !== '' ? ': ' . mb_substr($details, 0, 1200) : '.'));
    }
}

function newEnvFromPost(string $appUrl): string
{
    $fields = [
        'DB_HOST' => trim((string) ($_POST['db_host'] ?? '')),
        'DB_PORT' => trim((string) ($_POST['db_port'] ?? '3306')),
        'DB_NAME' => trim((string) ($_POST['db_name'] ?? '')),
        'DB_USER' => trim((string) ($_POST['db_user'] ?? '')),
        'DB_PASS' => (string) ($_POST['db_pass'] ?? ''),
    ];
    foreach ($fields as $key => $value) {
        if ($value === '') {
            throw new RuntimeException('Campo obbligatorio mancante: ' . $key);
        }
    }
    $values = [
        'APP_ENV' => 'staging',
        'APP_DEBUG' => 'false',
        'APP_TIMEZONE' => 'Europe/Rome',
        ...$fields,
        'OPENAI_API_KEY' => (string) ($_POST['openai_api_key'] ?? ''),
        'OPENAI_MODEL' => 'gpt-5-mini',
        'OPENAI_TIMEOUT_SECONDS' => '90',
        'OPENAI_MAX_OUTPUT_TOKENS' => '3500',
        'ADMIN_IDLE_TIMEOUT_SECONDS' => '7200',
        'HTTP_USER_AGENT' => 'LaucoExperience/1.0 (+' . $appUrl . ')',
        'HTTP_MAX_RESPONSE_BYTES' => '5000000',
        'EVENT_IMPORT_MAX_ITEMS' => '40',
    ];
    $lines = [];
    foreach ($values as $key => $value) {
        $lines[] = $key . '="' . str_replace(["\\", '"', "\r", "\n"], ["\\\\", '\\"', '', '\\n'], (string) $value) . '"';
    }
    return implode("\n", $lines) . "\n";
}

function parseEnv(string $file): array
{
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException('Impossibile leggere il .env.');
    }
    $env = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $value = trim($value);
        if (strlen($value) >= 2 && $value[0] === '"' && $value[-1] === '"') {
            $value = stripcslashes(substr($value, 1, -1));
        }
        $env[trim($key)] = $value;
    }
    return $env;
}

function databaseFromEnv(string $file): PDO
{
    $env = parseEnv($file);
    foreach (['DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $key) {
        if (!array_key_exists($key, $env)) {
            throw new RuntimeException('Il .env non contiene ' . $key . '.');
        }
    }
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $env['DB_HOST'], (int) $env['DB_PORT'], $env['DB_NAME']);
    return new PDO($dsn, (string) $env['DB_USER'], (string) $env['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table');
    $stmt->execute(['table' => $table]);
    return (int) $stmt->fetchColumn() > 0;
}

function migrate(PDO $pdo, string $directory): array
{
    $pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (migration VARCHAR(190) NOT NULL PRIMARY KEY, applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $result = ['applied' => [], 'skipped' => []];
    $files = glob($directory . '/*.sql') ?: [];
    sort($files, SORT_STRING);
    foreach ($files as $file) {
        $name = basename($file);
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM schema_migrations WHERE migration = :migration');
        $stmt->execute(['migration' => $name]);
        if ((int) $stmt->fetchColumn() > 0) {
            $result['skipped'][] = $name;
            continue;
        }
        $sql = file_get_contents($file);
        if (!is_string($sql)) {
            throw new RuntimeException('Impossibile leggere la migrazione ' . $name . '.');
        }
        $pdo->exec($sql);
        $stmt = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (:migration)');
        $stmt->execute(['migration' => $name]);
        $result['applied'][] = $name;
    }
    return $result;
}

function ensureUploads(string $release): void
{
    foreach (['uploads', 'uploads/percorsi/cover', 'uploads/percorsi/gpx', 'uploads/percorsi/gallery', 'uploads/eventi/cover', 'uploads/eventi/gallery', 'uploads/galleria', 'uploads/slider', 'uploads/luoghi', 'storage/translations'] as $relative) {
        mkdirOrFail($release . '/' . $relative, DEPLOY_UPLOAD_DIR_MODE);
    }
}

function copyTree(string $source, string $destination, string $prefix): void
{
    mkdirOrFail($destination, isUploadPath($prefix) ? DEPLOY_UPLOAD_DIR_MODE : DEPLOY_DIR_MODE);
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $item) {
        if ($item->isLink()) {
            throw new RuntimeException('Link simbolico non consentito.');
        }
        $relative = str_replace('\\', '/', $iterator->getSubPathName());
        $logical = trim($prefix . '/' . $relative, '/');
        $target = $destination . '/' . $relative;
        if ($item->isDir()) {
            mkdirOrFail($target, isUploadPath($logical) ? DEPLOY_UPLOAD_DIR_MODE : DEPLOY_DIR_MODE);
        } else {
            mkdirOrFail(dirname($target), isUploadPath(dirname($logical)) ? DEPLOY_UPLOAD_DIR_MODE : DEPLOY_DIR_MODE);
            if (!copy($item->getPathname(), $target)) {
                throw new RuntimeException('Copia fallita: ' . $logical);
            }
            chmodSafe($target, fileMode($logical));
        }
    }
}

function fixPermissions(string $release): void
{
    chmodSafe($release, DEPLOY_DIR_MODE);
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($release, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
    foreach ($iterator as $item) {
        $relative = str_replace('\\', '/', $iterator->getSubPathName());
        if ($item->isLink()) {
            throw new RuntimeException('Link simbolico non consentito.');
        }
        chmodSafe($item->getPathname(), $item->isDir() ? (isUploadPath($relative) ? DEPLOY_UPLOAD_DIR_MODE : DEPLOY_DIR_MODE) : fileMode($relative));
    }
}

function isUploadPath(string $relative): bool
{
    $relative = trim(str_replace('\\', '/', $relative), '/');
    return $relative === 'uploads'
        || str_starts_with($relative, 'uploads/')
        || $relative === 'storage/translations'
        || str_starts_with($relative, 'storage/translations/');
}

function fileMode(string $relative): int
{
    $relative = trim(str_replace('\\', '/', $relative), '/');
    if ($relative === '.env') {
        return DEPLOY_SECRET_MODE;
    }
    return isUploadPath($relative) ? DEPLOY_UPLOAD_FILE_MODE : DEPLOY_FILE_MODE;
}

function verifyRelease(string $release): void
{
    $checks = [
        is_readable($release . '/index.php'),
        is_readable($release . '/.htaccess'),
        is_readable($release . '/assets'),
        is_writable($release . '/uploads'),
        is_writable($release . '/storage/translations'),
        is_readable($release . '/.env'),
        is_readable($release . '/vendor/autoload.php'),
    ];
    if (in_array(false, $checks, true)) {
        throw new RuntimeException('Verifica permessi della release fallita.');
    }
}

function installRouter(string $root, string $stateDir): void
{
    $path = $root . '/.htaccess';
    if (is_file($path)) {
        copy($path, $stateDir . '/root-htaccess-' . gmdate('YmdHis') . '.bak');
    }
    $live = preg_quote(DEPLOY_LIVE_DIR, '~');
    $router = "# BEGIN LAUCO CONSERVATIVE DEPLOY ROUTER\n"
        . "Options -Indexes\n<IfModule mod_rewrite.c>\nRewriteEngine On\n"
        . "RewriteRule ^deploy\\.php$ - [L]\nRewriteRule ^\\.well-known/acme-challenge/ - [L]\n"
        . "RewriteRule ^\\.lauco-deploy(?:/|$) - [F,L]\n"
        . "RewriteRule ^(?:inc|config|migrations|tools|tests|\\.github)(?:/|$) - [F,L,NC]\n"
        . "RewriteRule ^(?:\\.env(?:\\.example)?|REFACTOR_LOGICA\\.md)$ - [F,L,NC]\n"
        . "RewriteCond %{THE_REQUEST} \\s/+" . $live . "(?:[/?\\s]) [NC]\nRewriteRule ^" . $live . "(?:/|$) - [F,L]\n"
        . "RewriteRule ^" . $live . "/ - [L]\nRewriteRule ^(.*)$ " . DEPLOY_LIVE_DIR . "/$1 [L,QSA]\n</IfModule>\n"
        . "# END LAUCO CONSERVATIVE DEPLOY ROUTER\n";
    if (file_put_contents($path, $router, LOCK_EX) === false) {
        throw new RuntimeException('Impossibile installare il router.');
    }
    chmodSafe($path, DEPLOY_FILE_MODE);
}

function switchRelease(string $live, string $release, string $stateDir, string $commit): ?string
{
    $backups = $stateDir . '/backups';
    mkdirOrFail($backups, 0700);
    $backup = null;
    if (is_dir($live)) {
        $backup = $backups . '/' . gmdate('YmdHis') . '-' . substr($commit, 0, 12);
        if (!rename($live, $backup)) {
            throw new RuntimeException('Impossibile creare il backup della release precedente.');
        }
    }
    if (!rename($release, $live)) {
        if ($backup !== null) {
            @rename($backup, $live);
        }
        throw new RuntimeException('Attivazione fallita; rollback eseguito.');
    }
    return $backup;
}

function rollback(string $live, ?string $backup, string $stateDir): void
{
    if (is_dir($live)) {
        @rename($live, $stateDir . '/failed-' . gmdate('YmdHis') . '-' . bin2hex(random_bytes(3)));
    }
    if ($backup !== null && is_dir($backup)) {
        @rename($backup, $live);
        fixPermissions($live);
    }
}

function health(string $url): array
{
    try {
        request(rtrim($url, '/') . '/', 20, 2_000_000);
        return ['ok' => true];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}

function cleanupBackups(string $directory, int $keep): void
{
    $items = glob($directory . '/*', GLOB_ONLYDIR) ?: [];
    usort($items, static fn (string $a, string $b): int => (filemtime($b) ?: 0) <=> (filemtime($a) ?: 0));
    foreach (array_slice($items, $keep) as $item) {
        removeTree($item);
    }
}

function mkdirOrFail(string $path, int $mode): void
{
    if (!is_dir($path) && !mkdir($path, $mode, true) && !is_dir($path)) {
        throw new RuntimeException('Impossibile creare: ' . $path);
    }
    chmodSafe($path, $mode);
}

function chmodSafe(string $path, int $mode): void
{
    if (file_exists($path) && !@chmod($path, $mode)) {
        throw new RuntimeException('Impossibile applicare i permessi a: ' . $path);
    }
}

function removeTree(string $path): void
{
    if (!is_dir($path)) {
        if (is_file($path)) {
            @unlink($path);
        }
        return;
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $item) {
        $item->isDir() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }
    @rmdir($path);
}

function readJson(string $file): ?array
{
    if (!is_file($file)) {
        return null;
    }
    $data = json_decode((string) file_get_contents($file), true);
    return is_array($data) ? $data : null;
}

function writeJson(string $file, array $data, int $mode = DEPLOY_SECRET_MODE): void
{
    if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n", LOCK_EX) === false) {
        throw new RuntimeException('Impossibile scrivere il file JSON.');
    }
    chmodSafe($file, $mode);
}

function displayPath(string $root, string $path): string
{
    $root = rtrim(str_replace('\\', '/', $root), '/');
    $path = str_replace('\\', '/', $path);
    return str_starts_with($path, $root . '/') ? substr($path, strlen($root) + 1) : $path;
}

function resultHtml(array $result): string
{
    $applied = $result['migrations']['applied'] ?? [];
    $skipped = $result['migrations']['skipped'] ?? [];
    return '<div class="alert success"><strong>Deploy completato.</strong><br>'
        . 'Commit: <code>' . h((string) $result['commit']) . '</code><br>'
        . 'Configurazione riusata: ' . h(!empty($result['env_source']) ? 'sì' : 'no') . '<br>'
        . 'Uploads conservati: ' . h(!empty($result['uploads_source']) ? 'sì' : 'no') . '<br>'
        . 'Migrazioni applicate: ' . h($applied ? implode(', ', $applied) : 'nessuna') . '<br>'
        . 'Migrazioni già presenti: ' . h($skipped ? implode(', ', $skipped) : 'nessuna') . '<br>'
        . 'Health check: ' . h(($result['health']['ok'] ?? false) ? 'OK' : 'non riuscito')
        . '</div>';
}

function input(string $label, string $name, string $type = 'text', string $value = '', bool $required = false, string $extra = ''): string
{
    $valueHtml = $type === 'password' ? '' : ' value="' . h($value) . '"';
    return '<label><span>' . h($label) . '</span><input type="' . h($type) . '" name="' . h($name) . '"' . $valueHtml . ($required ? ' required' : '') . ' ' . $extra . '></label>';
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function page(string $title, string $body): void
{
    echo '<!doctype html><html lang="it"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . h($title) . '</title><style>'
        . ':root{font-family:Arial,sans-serif;color:#222;background:#f4f4f4}*{box-sizing:border-box}body{margin:0;padding:24px}.card{max-width:560px;margin:5vh auto;background:#fff;padding:28px;box-shadow:0 8px 28px #0001}.wide{max-width:980px;margin:24px auto}.topbar{max-width:980px;margin:auto;display:flex;justify-content:space-between;align-items:center}.topbar small{display:block;color:#707070;margin-top:4px}fieldset{border:1px solid #ddd;padding:18px;margin:20px 0}legend{font-weight:bold}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}label{display:block;margin:13px 0}label span{display:block;font-weight:bold;margin-bottom:7px}input{width:100%;padding:11px;border:1px solid #ddd}.check{display:flex;gap:10px;align-items:flex-start}.check input{width:auto;margin-top:4px}.check span{font-weight:normal}button{border:0;padding:11px 15px;background:#222;color:#fff;cursor:pointer}.secondary{background:#666}.alert{padding:13px;margin:16px 0}.error{background:#f8d7da;color:#842029}.success{background:#d1e7dd;color:#0f5132}.info{background:#fff3cd;color:#664d03}.hint,small{color:#707070}.status{display:grid;grid-template-columns:120px 1fr;gap:8px;background:#fafafa;padding:14px}code{overflow-wrap:anywhere}@media(max-width:700px){body{padding:12px}.grid,.status{grid-template-columns:1fr}.card{padding:20px}.topbar{align-items:flex-start}}'
        . '</style></head><body>' . $body . '</body></html>';
}
