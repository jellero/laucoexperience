<?php
require_once LAUCO_ROOT . '/inc/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function contributo_token(): string
{
    if (empty($_SESSION['contributo_token'])) {
        $_SESSION['contributo_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['contributo_token'];
}

function contributo_verify_token(): void
{
    $token = (string) ($_POST['_csrf_token'] ?? '');

    if (!$token || !hash_equals($_SESSION['contributo_token'] ?? '', $token)) {
        throw new RuntimeException('Sessione scaduta. Ricarica la pagina e riprova.');
    }
}

function contributo_codice(): string
{
    return 'CON-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function contributo_upload(string $field): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Errore durante il caricamento dell’allegato.');
    }

    if ($_FILES[$field]['size'] > 15 * 1024 * 1024) {
        throw new RuntimeException('Allegato troppo grande. Massimo 15 MB.');
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf', 'gpx', 'zip'];
    $original = $_FILES[$field]['name'];
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Formato allegato non consentito. Usa JPG, PNG, WEBP, PDF, GPX o ZIP.');
    }

    $dir = LAUCO_ROOT . '/uploads/contributi';

    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Impossibile creare la cartella degli allegati.');
    }

    $filename = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $relativePath = 'uploads/contributi/' . $filename;
    $absolutePath = LAUCO_ROOT . '/' . $relativePath;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $absolutePath)) {
        throw new RuntimeException('Impossibile salvare l’allegato.');
    }

    return $relativePath;
}

function contributo_lauco_gpx_label(string $filename): string
{
    $name = pathinfo($filename, PATHINFO_FILENAME);

    if (preg_match('/^LAUCO_#_([0-9]+(?:-[A-Z0-9]+)?)$/i', $name, $m)) {
        return 'Sentiero ' . strtoupper($m[1]);
    }

    return trim(str_replace(['_', '-'], ' ', $name));
}

function contributo_gpx_options(): array
{
    $files = glob(LAUCO_ROOT . '/gpx/*.gpx') ?: [];

    usort($files, function ($a, $b) {
        return strnatcasecmp(
            contributo_lauco_gpx_label(basename($a)),
            contributo_lauco_gpx_label(basename($b))
        );
    });

    $options = [];

    foreach ($files as $file) {
        $filename = basename($file);
        $options[] = [
            'value' => '/gpx/' . $filename,
            'label' => contributo_lauco_gpx_label($filename),
        ];
    }

    return $options;
}

function contributo_gpx_valid(string $value, array $options): bool
{
    if ($value === '') {
        return true;
    }

    foreach ($options as $option) {
        if ($option['value'] === $value) {
            return true;
        }
    }

    return false;
}

$tipi = [
    'Fotografie',
    'Traccia GPX',
    'Descrizione percorso',
    'Luogo da scoprire',
    'Informazione storica',
    'Correzione contenuto',
    'Materiale documentale',
    'Altro',
];

$gpxOptions = contributo_gpx_options();

$error = '';
$successCode = '';
$old = [
    'tipo' => '',
    'titolo' => '',
    'descrizione' => '',
    'localita' => '',
    'percorso_gpx' => $_GET['percorso_gpx'] ?? '',
    'pagina_url' => $_GET['pagina'] ?? '',
    'nome' => '',
    'email' => '',
    'telefono' => '',
    'consenso' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = array_merge($old, $_POST);

    try {
        contributo_verify_token();

        if (!empty($_POST['website'] ?? '')) {
            throw new RuntimeException('Richiesta non valida.');
        }

        $tipo = trim($_POST['tipo'] ?? '');
        $titolo = trim($_POST['titolo'] ?? '');
        $descrizione = trim($_POST['descrizione'] ?? '');
        $localita = trim($_POST['localita'] ?? '');
        $percorsoGpx = trim($_POST['percorso_gpx'] ?? '');
        $paginaUrl = trim($_POST['pagina_url'] ?? '');
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');

        if ($tipo === '' || !in_array($tipo, $tipi, true)) {
            throw new RuntimeException('Seleziona un tipo di contributo valido.');
        }

        if ($titolo === '') {
            throw new RuntimeException('Inserisci un titolo breve.');
        }

        if (mb_strlen($titolo) > 190) {
            throw new RuntimeException('Il titolo è troppo lungo.');
        }

        if ($descrizione === '' || mb_strlen($descrizione) < 20) {
            throw new RuntimeException('Descrivi il contributo con almeno 20 caratteri.');
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Inserisci una email valida oppure lascia il campo vuoto.');
        }

        if (!contributo_gpx_valid($percorsoGpx, $gpxOptions)) {
            throw new RuntimeException('Sentiero collegato non valido.');
        }

        if (empty($_POST['consenso'])) {
            throw new RuntimeException('Devi confermare il consenso all’utilizzo del contributo inviato.');
        }

        $allegato = contributo_upload('allegato');
        $codice = contributo_codice();

        $stmt = $pdo->prepare("
            INSERT INTO contributi (
                codice, tipo, titolo, descrizione, localita, percorso_gpx, pagina_url,
                nome, email, telefono, allegato_path, consenso, ip_address, user_agent
            ) VALUES (
                :codice, :tipo, :titolo, :descrizione, :localita, :percorso_gpx, :pagina_url,
                :nome, :email, :telefono, :allegato_path, :consenso, :ip_address, :user_agent
            )
        ");

        $stmt->execute([
            'codice' => $codice,
            'tipo' => $tipo,
            'titolo' => $titolo,
            'descrizione' => $descrizione,
            'localita' => $localita ?: null,
            'percorso_gpx' => $percorsoGpx ?: null,
            'pagina_url' => $paginaUrl ?: null,
            'nome' => $nome ?: null,
            'email' => $email ?: null,
            'telefono' => $telefono ?: null,
            'allegato_path' => $allegato,
            'consenso' => 1,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);

        unset($_SESSION['contributo_token']);
        $successCode = $codice;

        $old = [
            'tipo' => '',
            'titolo' => '',
            'descrizione' => '',
            'localita' => '',
            'percorso_gpx' => '',
            'pagina_url' => '',
            'nome' => '',
            'email' => '',
            'telefono' => '',
            'consenso' => '',
        ];
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php require LAUCO_VIEW_PATH . '/partials/header.php'; ?>

    <style>
        .contribute-page .lead-text {
            font-size: 18px;
            line-height: 1.75;
            color: #555;
            margin-bottom: 32px;
        }

        .contribute-page .info-card {
            background: #fff;
            padding: 28px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,.06);
            border: 1px solid rgba(0,0,0,.04);
            min-height: 220px;
        }

        .contribute-page .info-card h3 {
            margin-top: 0;
            margin-bottom: 14px;
        }

        .contribute-page .info-card p,
        .contribute-page .info-card li {
            color: #666;
            line-height: 1.75;
        }

        .contribute-page .info-card ul {
            padding-left: 19px;
            margin-bottom: 0;
        }

        .contribute-page .callout {
            background: #f7f7f7;
            padding: 28px;
            margin: 28px 0;
            border-left: 4px solid #222;
        }

        .contribution-form-section {
            padding-top: 70px;
            padding-bottom: 80px;
        }

        .contribution-box {
            background: #fff;
            padding: 34px;
            box-shadow: 0 10px 34px rgba(0,0,0,.08);
        }

        .contribution-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .contribution-field.full {
            grid-column: 1 / -1;
        }

        .contribution-field label {
            display: block;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .contribution-field input,
        .contribution-field select,
        .contribution-field textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            background: #fff;
            box-sizing: border-box;
        }

        .contribution-field textarea {
            min-height: 155px;
            resize: vertical;
        }

        .contribution-note {
            color: #777;
            font-size: 13px;
            margin-top: 5px;
            line-height: 1.5;
        }

        .contribution-alert {
            padding: 16px 18px;
            margin-bottom: 20px;
        }

        .contribution-alert.error {
            background: #f8d7da;
            color: #842029;
        }

        .contribution-alert.success {
            background: #d1e7dd;
            color: #0f5132;
        }

        .contribution-consent {
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .contribution-consent input {
            width: auto;
            margin-top: 4px;
        }

        .website-field {
            position: absolute;
            left: -9999px;
            opacity: 0;
        }

        @media (max-width: 767px) {
            .contribution-form-section {
                padding-top: 45px;
                padding-bottom: 55px;
            }

            .contribution-box {
                padding: 22px;
            }

            .contribution-grid {
                grid-template-columns: 1fr;
            }

            .contribute-page .info-card {
                min-height: auto;
                padding: 22px;
            }
        }
    </style>
</head>
<body>
    <div id="myloader">
        <span class="loader"><div class="inner-loader"></div></span>
    </div>

    <div id="main-wrap" class="full-width">
        <?php require LAUCO_VIEW_PATH . '/partials/menu.php'; ?>

        <div id="page-content" class="header-static">
            <div id="flexslider" class="fullpage-wrap small">
                <ul class="slides">
                    <li style="background-image:url(assets/img/contribuisci.webp)">
                        <div class="container text text-center">
                            <h1 class="white margin-bottom-small">Contribuisci</h1>
                            <p class="heading white">Fotografie, tracce, testi e informazioni per migliorare Lauco Experience.</p>
                        </div>
                        <div class="gradient dark"></div>
                    </li>

                    <ol class="breadcrumb">
                        <li><a href="/">Home</a></li>
                        <li class="active">Contribuisci</li>
                    </ol>
                </ul>
            </div>

            <div id="page-wrap" class="content-section fullpage-wrap contribute-page">
                <div class="container text">
                    <div class="row margin-null">
                        <div class="col-md-12 padding-leftright-null">
                            <h2 class="margin-bottom-null title line left">Contribuisci al progetto</h2>
                            <p class="heading left grey margin-bottom">Materiali, correzioni e conoscenze dal territorio.</p>

                            <p class="lead-text">
                                Lauco Experience può crescere grazie a cittadini, escursionisti, associazioni e persone che conoscono il territorio.
                                Puoi inviare materiali utili al miglioramento delle schede, dei luoghi, delle tracce e delle informazioni pubblicate.
                            </p>

                            <div class="callout">
                                <p>
                                    I contributi non vengono pubblicati automaticamente. Il Comune di Lauco può verificarli, integrarli,
                                    sintetizzarli o utilizzarli per aggiornare il sito e i contenuti territoriali.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row padding-onlytop-md">
                        <div class="col-md-4">
                            <div class="info-card">
                                <h3>Foto e materiali</h3>
                                <p>
                                    Puoi inviare fotografie del territorio, immagini di punti di interesse, documenti o materiali utili
                                    alla valorizzazione dei percorsi e dei luoghi di Lauco.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="info-card">
                                <h3>Tracce e percorsi</h3>
                                <p>
                                    Puoi proporre tracce GPX, descrizioni, correzioni o informazioni pratiche sui sentieri.
                                    Le tracce vengono valutate prima di ogni eventuale uso pubblico.
                                </p>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="info-card">
                                <h3>Luoghi e memoria</h3>
                                <p>
                                    Puoi segnalare luoghi da scoprire, elementi storici, panorami, chiesette, particolarità naturali
                                    o racconti legati alla memoria locale.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row margin-leftright-null grey-background contribution-form-section">
                    <div class="container">
                        <div class="col-md-12 padding-leftright-null text padding-bottom-null text-center">
                            <h2 class="margin-bottom-null title line center">INVIA UN CONTRIBUTO</h2>
                            <p class="heading center grey margin-bottom-null">
                                Compila il modulo con una descrizione chiara e allega eventuali materiali.
                            </p>
                        </div>

                        <div class="col-md-12">
                            <div class="contribution-box">
                                <?php if ($error): ?>
                                    <div class="contribution-alert error"><?= e($error) ?></div>
                                <?php endif; ?>

                                <?php if ($successCode): ?>
                                    <div class="contribution-alert success">
                                        <strong>Contributo inviato.</strong><br>
                                        Codice riferimento: <strong><?= e($successCode) ?></strong>
                                    </div>
                                <?php endif; ?>

                                <form method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="_csrf_token" value="<?= e(contributo_token()) ?>">

                                    <div class="website-field">
                                        <label for="website">Website</label>
                                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                                    </div>

                                    <div class="contribution-grid">
                                        <div class="contribution-field">
                                            <label for="tipo">Tipo contributo *</label>
                                            <select id="tipo" name="tipo" required>
                                                <option value="">Seleziona</option>
                                                <?php foreach ($tipi as $tipo): ?>
                                                    <option value="<?= e($tipo) ?>" <?= ($old['tipo'] ?? '') === $tipo ? 'selected' : '' ?>>
                                                        <?= e($tipo) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="contribution-field">
                                            <label for="titolo">Titolo breve *</label>
                                            <input type="text" id="titolo" name="titolo" value="<?= e($old['titolo']) ?>" maxlength="190" required>
                                        </div>

                                        <div class="contribution-field full">
                                            <label for="descrizione">Descrizione *</label>
                                            <textarea id="descrizione" name="descrizione" required><?= e($old['descrizione']) ?></textarea>
                                            <div class="contribution-note">Spiega cosa stai inviando, perché è utile, dove si trova o a quale contenuto si riferisce.</div>
                                        </div>

                                        <div class="contribution-field">
                                            <label for="localita">Luogo / località</label>
                                            <input type="text" id="localita" name="localita" value="<?= e($old['localita']) ?>" placeholder="es. Trava, Lauco, sentiero...">
                                        </div>

                                        <div class="contribution-field">
                                            <label for="pagina_url">Pagina o link collegato</label>
                                            <input type="text" id="pagina_url" name="pagina_url" value="<?= e($old['pagina_url']) ?>" placeholder="https://... oppure nome pagina">
                                        </div>

                                        <div class="contribution-field">
                                            <label for="percorso_gpx">Sentiero collegato</label>
                                            <select id="percorso_gpx" name="percorso_gpx">
                                                <option value="">Nessuno</option>
                                                <?php foreach ($gpxOptions as $option): ?>
                                                    <option value="<?= e($option['value']) ?>" <?= (string) ($old['percorso_gpx'] ?? '') === (string) $option['value'] ? 'selected' : '' ?>>
                                                        <?= e($option['label']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="contribution-note">Elenco letto automaticamente dalla cartella <code>/gpx/</code>.</div>
                                        </div>

                                        <div class="contribution-field">
                                            <label for="allegato">Allegato</label>
                                            <input type="file" id="allegato" name="allegato" accept=".jpg,.jpeg,.png,.webp,.pdf,.gpx,.zip,image/*,application/pdf">
                                            <div class="contribution-note">Opzionale. JPG, PNG, WEBP, PDF, GPX o ZIP. Max 15 MB.</div>
                                        </div>

                                        <div class="contribution-field">
                                            <label for="nome">Nome</label>
                                            <input type="text" id="nome" name="nome" value="<?= e($old['nome']) ?>">
                                        </div>

                                        <div class="contribution-field">
                                            <label for="email">Email</label>
                                            <input type="email" id="email" name="email" value="<?= e($old['email']) ?>">
                                            <div class="contribution-note">Serve solo se vuoi essere ricontattato.</div>
                                        </div>

                                        <div class="contribution-field">
                                            <label for="telefono">Telefono</label>
                                            <input type="text" id="telefono" name="telefono" value="<?= e($old['telefono']) ?>">
                                        </div>

                                        <div class="contribution-field full">
                                            <label class="contribution-consent">
                                                <input type="checkbox" name="consenso" value="1" <?= !empty($old['consenso']) ? 'checked' : '' ?> required>
                                                <span>
                                                    Confermo di avere la disponibilità del materiale inviato e autorizzo il Comune di Lauco a valutarlo
                                                    e utilizzarlo per il progetto Lauco Experience, anche mediante adattamenti editoriali.
                                                </span>
                                            </label>
                                        </div>

                                        <div class="contribution-field full">
                                            <button type="submit" class="btn-alt active shadow small margin-null">Invia contributo</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="container text">
                    <div class="row padding-onlytop-md">
                        <div class="col-md-6">
                            <div class="info-card">
                                <h3>Cosa indicare</h3>
                                <ul>
                                    <li>luogo o sentiero interessato;</li>
                                    <li>descrizione chiara del materiale inviato;</li>
                                    <li>data o periodo di riferimento, se utile;</li>
                                    <li>eventuali coordinate, link o riferimenti;</li>
                                    <li>recapito per eventuali chiarimenti.</li>
                                </ul>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-card">
                                <h3>Prima di inviare</h3>
                                <ul>
                                    <li>non inviare foto con persone riconoscibili senza consenso;</li>
                                    <li>non inviare materiali di cui non hai disponibilità;</li>
                                    <li>evita contenuti non pertinenti al territorio;</li>
                                    <li>per urgenze o pericoli immediati usa i canali di emergenza.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <?php require LAUCO_VIEW_PATH . '/partials/footer.php'; ?>
    </div>

    <?php require LAUCO_VIEW_PATH . '/partials/scripts.php'; ?>
</body>
</html>
