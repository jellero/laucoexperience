<?php
require_once __DIR__ . '/inc/db.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function segnalazione_token(): string
{
    if (empty($_SESSION['segnalazione_token'])) {
        $_SESSION['segnalazione_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['segnalazione_token'];
}

function segnalazione_verify_token(): void
{
    $token = (string) ($_POST['_csrf_token'] ?? '');

    if (!$token || !hash_equals($_SESSION['segnalazione_token'] ?? '', $token)) {
        throw new RuntimeException('Sessione scaduta. Ricarica la pagina e riprova.');
    }
}

function segnalazione_codice(): string
{
    return 'SEG-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function segnalazione_upload(string $field): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Errore durante il caricamento dell’allegato.');
    }

    if ($_FILES[$field]['size'] > 8 * 1024 * 1024) {
        throw new RuntimeException('Allegato troppo grande. Massimo 8 MB.');
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
    $original = $_FILES[$field]['name'];
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Formato allegato non consentito. Usa JPG, PNG, WEBP o PDF.');
    }

    $dir = __DIR__ . '/uploads/segnalazioni';

    if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
        throw new RuntimeException('Impossibile creare la cartella degli allegati.');
    }

    $filename = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $relativePath = 'uploads/segnalazioni/' . $filename;
    $absolutePath = __DIR__ . '/' . $relativePath;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $absolutePath)) {
        throw new RuntimeException('Impossibile salvare l’allegato.');
    }

    return $relativePath;
}

function segnalazione_get_options(PDO $pdo, string $table): array
{
    try {
        $stmt = $pdo->query("SELECT id, titolo FROM {$table} WHERE pubblicato = 1 ORDER BY titolo ASC");
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

$percorsi = segnalazione_get_options($pdo, 'percorsi');
$eventi = segnalazione_get_options($pdo, 'eventi');

$categorie = [
    'Sentiero o percorso',
    'Segnaletica',
    'GPX / mappa / altimetria',
    'Pagina sito',
    'Evento',
    'Accessibilità',
    'Sicurezza',
    'Altro',
];

$error = '';
$successCode = '';
$old = [
    'categoria' => '',
    'titolo' => '',
    'descrizione' => '',
    'luogo' => '',
    'pagina_url' => $_GET['pagina'] ?? '',
    'percorso_id' => $_GET['percorso_id'] ?? '',
    'evento_id' => $_GET['evento_id'] ?? '',
    'nome' => '',
    'email' => '',
    'telefono' => '',
    'privacy' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = array_merge($old, $_POST);

    try {
        segnalazione_verify_token();

        if (!empty($_POST['website'] ?? '')) {
            throw new RuntimeException('Richiesta non valida.');
        }

        $categoria = trim($_POST['categoria'] ?? '');
        $titolo = trim($_POST['titolo'] ?? '');
        $descrizione = trim($_POST['descrizione'] ?? '');
        $luogo = trim($_POST['luogo'] ?? '');
        $paginaUrl = trim($_POST['pagina_url'] ?? '');
        $percorsoId = !empty($_POST['percorso_id']) ? (int) $_POST['percorso_id'] : null;
        $eventoId = !empty($_POST['evento_id']) ? (int) $_POST['evento_id'] : null;
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telefono = trim($_POST['telefono'] ?? '');

        if ($categoria === '' || !in_array($categoria, $categorie, true)) {
            throw new RuntimeException('Seleziona una categoria valida.');
        }

        if ($titolo === '') {
            throw new RuntimeException('Inserisci un titolo breve.');
        }

        if (mb_strlen($titolo) > 190) {
            throw new RuntimeException('Il titolo è troppo lungo.');
        }

        if ($descrizione === '' || mb_strlen($descrizione) < 15) {
            throw new RuntimeException('Descrivi il problema con almeno 15 caratteri.');
        }

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Inserisci una email valida oppure lascia il campo vuoto.');
        }

        if (empty($_POST['privacy'])) {
            throw new RuntimeException('Devi confermare il consenso al trattamento della segnalazione.');
        }

        $allegato = segnalazione_upload('allegato');
        $codice = segnalazione_codice();

        $stmt = $pdo->prepare("
            INSERT INTO segnalazioni_problemi (
                codice, categoria, titolo, descrizione, luogo, pagina_url,
                percorso_id, evento_id, nome, email, telefono, allegato_path,
                ip_address, user_agent
            ) VALUES (
                :codice, :categoria, :titolo, :descrizione, :luogo, :pagina_url,
                :percorso_id, :evento_id, :nome, :email, :telefono, :allegato_path,
                :ip_address, :user_agent
            )
        ");

        $stmt->execute([
            'codice' => $codice,
            'categoria' => $categoria,
            'titolo' => $titolo,
            'descrizione' => $descrizione,
            'luogo' => $luogo ?: null,
            'pagina_url' => $paginaUrl ?: null,
            'percorso_id' => $percorsoId,
            'evento_id' => $eventoId,
            'nome' => $nome ?: null,
            'email' => $email ?: null,
            'telefono' => $telefono ?: null,
            'allegato_path' => $allegato,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
            'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);

        unset($_SESSION['segnalazione_token']);
        $successCode = $codice;
        $old = [
            'categoria' => '',
            'titolo' => '',
            'descrizione' => '',
            'luogo' => '',
            'pagina_url' => '',
            'percorso_id' => '',
            'evento_id' => '',
            'nome' => '',
            'email' => '',
            'telefono' => '',
            'privacy' => '',
        ];
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <?php include 'inc/header.php'; ?>
    <style>
        .report-section {
            padding-top: 70px;
            padding-bottom: 80px;
        }

        .report-box {
            background: #fff;
            padding: 34px;
            box-shadow: 0 10px 34px rgba(0,0,0,.08);
        }

        .report-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .report-field.full {
            grid-column: 1 / -1;
        }

        .report-field label {
            display: block;
            font-weight: 700;
            margin-bottom: 7px;
        }

        .report-field input,
        .report-field select,
        .report-field textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            background: #fff;
            box-sizing: border-box;
        }

        .report-field textarea {
            min-height: 150px;
            resize: vertical;
        }

        .report-note {
            color: #777;
            font-size: 13px;
            margin-top: 5px;
        }

        .report-alert {
            padding: 16px 18px;
            margin-bottom: 20px;
        }

        .report-alert.error {
            background: #f8d7da;
            color: #842029;
        }

        .report-alert.success {
            background: #d1e7dd;
            color: #0f5132;
        }

        .report-privacy {
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .report-privacy input {
            width: auto;
            margin-top: 4px;
        }

        .website-field {
            position: absolute;
            left: -9999px;
            opacity: 0;
        }

        @media (max-width: 767px) {
            .report-section {
                padding-top: 45px;
                padding-bottom: 55px;
            }

            .report-box {
                padding: 22px;
            }

            .report-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div id="myloader">
        <span class="loader"><div class="inner-loader"></div></span>
    </div>

    <div id="main-wrap" class="full-width">
        <?php include 'inc/menu.php'; ?>

        <div id="page-content" class="header-static">
            <div id="flexslider" class="fullpage-wrap small">
                <ul class="slides">
                    <li style="background-image:url(assets/img/segnalazioni.jpg)">
                        <div class="container text text-center">
                            <h1 class="white margin-bottom-small">Segnala un problema</h1>
                            <p class="heading white">Aiutaci a mantenere aggiornati percorsi, eventi e informazioni del territorio.</p>
                        </div>
                        <div class="gradient dark"></div>
                    </li>
                    <ol class="breadcrumb">
                        <li><a href="index.php">Home</a></li>
                        <li class="active">Segnala un problema</li>
                    </ol>
                </ul>
            </div>

            <div id="page-wrap" class="content-section fullpage-wrap">
                <div class="row margin-leftright-null grey-background report-section">
                    <div class="container">
                        <div class="col-md-12 padding-leftright-null text padding-bottom-null text-center">
                            <h2 class="margin-bottom-null title line center">SEGNALAZIONE</h2>
                            <p class="heading center grey margin-bottom-null">
                                Usa questo modulo per comunicare errori sul sito, problemi sui sentieri, segnaletica mancante o informazioni da correggere.
                            </p>
                        </div>

                        <div class="col-md-12">
                            <div class="report-box">
                                <?php if ($error): ?>
                                    <div class="report-alert error"><?= e($error) ?></div>
                                <?php endif; ?>

                                <?php if ($successCode): ?>
                                    <div class="report-alert success">
                                        <strong>Segnalazione inviata.</strong><br>
                                        Codice riferimento: <strong><?= e($successCode) ?></strong>
                                    </div>
                                <?php endif; ?>

                                <form method="post" enctype="multipart/form-data">
                                    <input type="hidden" name="_csrf_token" value="<?= e(segnalazione_token()) ?>">

                                    <div class="website-field">
                                        <label for="website">Website</label>
                                        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                                    </div>

                                    <div class="report-grid">
                                        <div class="report-field">
                                            <label for="categoria">Categoria *</label>
                                            <select id="categoria" name="categoria" required>
                                                <option value="">Seleziona</option>
                                                <?php foreach ($categorie as $categoria): ?>
                                                    <option value="<?= e($categoria) ?>" <?= ($old['categoria'] ?? '') === $categoria ? 'selected' : '' ?>>
                                                        <?= e($categoria) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="report-field">
                                            <label for="titolo">Titolo breve *</label>
                                            <input type="text" id="titolo" name="titolo" value="<?= e($old['titolo']) ?>" maxlength="190" required>
                                        </div>

                                        <div class="report-field full">
                                            <label for="descrizione">Descrizione *</label>
                                            <textarea id="descrizione" name="descrizione" required><?= e($old['descrizione']) ?></textarea>
                                            <div class="report-note">Indica cosa non va, dove si trova il problema e quando è stato notato.</div>
                                        </div>

                                        <div class="report-field">
                                            <label for="luogo">Luogo / località</label>
                                            <input type="text" id="luogo" name="luogo" value="<?= e($old['luogo']) ?>" placeholder="es. Lauco, Allegnidis, sentiero...">
                                        </div>

                                        <div class="report-field">
                                            <label for="pagina_url">Pagina o link interessato</label>
                                            <input type="text" id="pagina_url" name="pagina_url" value="<?= e($old['pagina_url']) ?>" placeholder="https://... oppure nome pagina">
                                        </div>

<?php
if (!function_exists('lauco_gpx_sentiero_label')) {
    function lauco_gpx_sentiero_label(string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);

        if (preg_match('/^LAUCO_#_([0-9]+(?:-[A-Z0-9]+)?)$/i', $name, $m)) {
            return 'Sentiero ' . strtoupper($m[1]);
        }

        return str_replace(['_', '-'], ' ', $name);
    }
}

$gpxFiles = glob(__DIR__ . '/gpx/*.gpx') ?: [];

usort($gpxFiles, function ($a, $b) {
    return strnatcasecmp(
        lauco_gpx_sentiero_label(basename($a)),
        lauco_gpx_sentiero_label(basename($b))
    );
});
?>

<div class="report-field">
    <label for="percorso_id">Percorso collegato</label>
    <select id="percorso_id" name="percorso_id">
        <option value="">Nessuno</option>

        <?php foreach ($gpxFiles as $gpxFile): ?>
            <?php
                $fileName = basename($gpxFile);
                $value = '/gpx/' . $fileName;
                $label = lauco_gpx_sentiero_label($fileName);
            ?>

            <option value="<?= e($value) ?>" <?= (string) ($old['percorso_id'] ?? '') === (string) $value ? 'selected' : '' ?>>
                <?= e($label) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>

                                        <div class="report-field">
                                            <label for="evento_id">Evento collegato</label>
                                            <select id="evento_id" name="evento_id">
                                                <option value="">Nessuno</option>
                                                <?php foreach ($eventi as $evento): ?>
                                                    <option value="<?= (int) $evento['id'] ?>" <?= (int) ($old['evento_id'] ?? 0) === (int) $evento['id'] ? 'selected' : '' ?>>
                                                        <?= e($evento['titolo']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="report-field">
                                            <label for="allegato">Foto / screenshot / PDF</label>
                                            <input type="file" id="allegato" name="allegato" accept=".jpg,.jpeg,.png,.webp,.pdf,image/*,application/pdf">
                                            <div class="report-note">Opzionale. Max 8 MB.</div>
                                        </div>

                                        <div class="report-field">
                                            <label for="nome">Nome</label>
                                            <input type="text" id="nome" name="nome" value="<?= e($old['nome']) ?>">
                                        </div>

                                        <div class="report-field">
                                            <label for="email">Email</label>
                                            <input type="email" id="email" name="email" value="<?= e($old['email']) ?>">
                                            <div class="report-note">Serve solo se vuoi essere ricontattato.</div>
                                        </div>

                                        <div class="report-field">
                                            <label for="telefono">Telefono</label>
                                            <input type="text" id="telefono" name="telefono" value="<?= e($old['telefono']) ?>">
                                        </div>

                                        <div class="report-field full">
                                            <label class="report-privacy">
                                                <input type="checkbox" name="privacy" value="1" <?= !empty($old['privacy']) ? 'checked' : '' ?> required>
                                                <span>Confermo che i dati inseriti possono essere usati esclusivamente per gestire questa segnalazione.</span>
                                            </label>
                                        </div>

                                        <div class="report-field full">
                                            <button type="submit" class="btn-alt active shadow small margin-null">Invia segnalazione</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include 'inc/footer.php'; ?>
    </div>

    <?php include 'inc/scripts.php'; ?>
</body>
</html>
