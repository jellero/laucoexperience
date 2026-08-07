<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

$uploadRoot = dirname(__DIR__);
$uploadDir = 'uploads/galleria';

function ensure_gallery_dir(string $relativeDir): void
{
    global $uploadRoot;

    $absolute = $uploadRoot . '/' . trim($relativeDir, '/');

    if (!is_dir($absolute) && !mkdir($absolute, 0775, true) && !is_dir($absolute)) {
        throw new RuntimeException('Impossibile creare la cartella upload.');
    }
}

function upload_gallery_files(string $field, array $allowedExts, string $relativeDir): array
{
    global $uploadRoot;

    if (empty($_FILES[$field])) {
        return [];
    }

    ensure_gallery_dir($relativeDir);

    $paths = [];

    if (is_array($_FILES[$field]['name'])) {
        $count = count($_FILES[$field]['name']);

        for ($i = 0; $i < $count; $i++) {
            if ($_FILES[$field]['error'][$i] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if ($_FILES[$field]['error'][$i] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Errore durante upload immagine.');
            }

            $originalName = $_FILES[$field]['name'][$i];
            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExts, true)) {
                throw new RuntimeException('Formato immagine non consentito: ' . e($originalName));
            }

            $filename = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
            $relativePath = trim($relativeDir, '/') . '/' . $filename;
            $absolutePath = $uploadRoot . '/' . $relativePath;

            if (!move_uploaded_file($_FILES[$field]['tmp_name'][$i], $absolutePath)) {
                throw new RuntimeException('Impossibile salvare immagine.');
            }

            $paths[] = $relativePath;
        }

        return $paths;
    }

    if ($_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return [];
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Errore durante upload immagine.');
    }

    $originalName = $_FILES[$field]['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExts, true)) {
        throw new RuntimeException('Formato immagine non consentito: ' . e($originalName));
    }

    $filename = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $relativePath = trim($relativeDir, '/') . '/' . $filename;
    $absolutePath = $uploadRoot . '/' . $relativePath;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $absolutePath)) {
        throw new RuntimeException('Impossibile salvare immagine.');
    }

    return [$relativePath];
}

function delete_gallery_file_if_exists(?string $relativePath): void
{
    global $uploadRoot;

    if (!$relativePath) {
        return;
    }

    $absolute = realpath($uploadRoot . '/' . $relativePath);
    $root = realpath($uploadRoot . '/uploads');

    if ($absolute && $root && strpos($absolute, $root) === 0 && is_file($absolute)) {
        unlink($absolute);
    }
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$error = '';

$empty = [
    'id' => null,
    'titolo' => '',
    'image_path' => '',
    'alt' => '',
    'categoria' => '',
    'ordine' => 0,
    'pubblicato' => 1,
];

$immagine = $empty;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM galleria WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $immagine = $stmt->fetch();

    if (!$immagine) {
        http_response_code(404);
        exit('Immagine non trovata.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $id = (int) ($_POST['id'] ?? 0);
        $titolo = trim($_POST['titolo'] ?? '');
        $alt = trim($_POST['alt'] ?? '');
        $categoria = trim($_POST['categoria'] ?? '');
        $ordine = (int) ($_POST['ordine'] ?? 0);
        $pubblicato = isset($_POST['pubblicato']) ? 1 : 0;

        $old = null;

        if ($id) {
            $stmt = $pdo->prepare('SELECT * FROM galleria WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $old = $stmt->fetch();

            if (!$old) {
                throw new RuntimeException('Immagine non trovata.');
            }
        }

        $paths = upload_gallery_files('images', ['jpg', 'jpeg', 'png', 'webp', 'gif'], $uploadDir);

        if ($id) {
            $imagePath = $paths[0] ?? ($old['image_path'] ?? null);

            if (!$imagePath) {
                throw new RuntimeException('Immagine mancante.');
            }

            if (!empty($paths[0]) && !empty($old['image_path'])) {
                delete_gallery_file_if_exists($old['image_path']);
            }

            $stmt = $pdo->prepare("
                UPDATE galleria SET
                    titolo = :titolo,
                    image_path = :image_path,
                    alt = :alt,
                    categoria = :categoria,
                    ordine = :ordine,
                    pubblicato = :pubblicato
                WHERE id = :id
            ");

            $stmt->execute([
                'titolo' => $titolo,
                'image_path' => $imagePath,
                'alt' => $alt,
                'categoria' => $categoria,
                'ordine' => $ordine,
                'pubblicato' => $pubblicato,
                'id' => $id,
            ]);
        } else {
            if (!$paths) {
                throw new RuntimeException('Carica almeno una immagine.');
            }

            $stmt = $pdo->prepare('SELECT COALESCE(MAX(ordine), 0) FROM galleria');
            $stmt->execute();
            $baseOrder = (int) $stmt->fetchColumn();

            $insert = $pdo->prepare("
                INSERT INTO galleria (titolo, image_path, alt, categoria, ordine, pubblicato)
                VALUES (:titolo, :image_path, :alt, :categoria, :ordine, :pubblicato)
            ");

            foreach ($paths as $index => $path) {
                $insert->execute([
                    'titolo' => $titolo ?: pathinfo($path, PATHINFO_FILENAME),
                    'image_path' => $path,
                    'alt' => $alt ?: $titolo,
                    'categoria' => $categoria,
                    'ordine' => $ordine ?: ($baseOrder + $index + 1),
                    'pubblicato' => $pubblicato,
                ]);
            }
        }

        header('Location: galleria.php');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $immagine = array_merge($empty, $_POST);
    }
}
?>

<?php admin_page_open('Galleria', 'galleria'); ?>

<main class="wrap">
        <div class="box">
            <?php if ($error): ?>
                <div class="error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) ($immagine['id'] ?? 0) ?>">

                <div class="grid">
                    <div>
                        <label for="titolo">Titolo</label>
                        <input type="text" id="titolo" name="titolo" value="<?= e($immagine['titolo']) ?>">
                    </div>

                    <div>
                        <label for="categoria">Categoria</label>
                        <input type="text" id="categoria" name="categoria" value="<?= e($immagine['categoria']) ?>" placeholder="es. Natura, Eventi, Sentieri">
                    </div>

                    <div>
                        <label for="alt">Testo alt</label>
                        <input type="text" id="alt" name="alt" value="<?= e($immagine['alt']) ?>">
                    </div>

                    <div>
                        <label for="ordine">Ordine</label>
                        <input type="number" id="ordine" name="ordine" value="<?= e($immagine['ordine']) ?>">
                        <div class="hint">Se carichi più immagini e lasci 0, vengono accodate automaticamente.</div>
                    </div>

                    <div>
                        <label>Stato</label>
                        <label>
                            <input type="checkbox" id="pubblicato" name="pubblicato" value="1" <?= !empty($immagine['pubblicato']) ? 'checked' : '' ?> style="width:auto;">
                            pubblicata
                        </label>
                    </div>

                    <div class="full">
                        <label for="images">Immagini</label>
                        <input type="file" id="images" name="images[]" accept=".jpg,.jpeg,.png,.webp,.gif,image/*" <?= $immagine['id'] ? '' : 'multiple required' ?>>
                        <div class="hint">
                            In creazione puoi selezionare più immagini. In modifica puoi caricare una nuova immagine per sostituire quella attuale.
                        </div>

                        <?php if (!empty($immagine['image_path'])): ?>
                            <div class="hint">Attuale: <?= e($immagine['image_path']) ?></div>
                            <img class="preview" src="../<?= e($immagine['image_path']) ?>" alt="<?= e($immagine['alt'] ?: $immagine['titolo']) ?>">
                        <?php endif; ?>
                    </div>

                    <div class="full">
                        <button class="btn" type="submit">Salva</button>
                        <a class="btn secondary" href="galleria.php">Annulla</a>
                    </div>
                </div>
            </form>
        </div>
    </main>
<?php admin_page_close(); ?>
