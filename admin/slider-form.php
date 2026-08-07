<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

$uploadRoot = dirname(__DIR__);
$uploadDir = 'uploads/slider';

function ensure_slider_dir(string $relativeDir): void
{
    global $uploadRoot;

    $absolute = $uploadRoot . '/' . trim($relativeDir, '/');

    if (!is_dir($absolute) && !mkdir($absolute, 0775, true) && !is_dir($absolute)) {
        throw new RuntimeException('Impossibile creare la cartella upload.');
    }
}

function upload_slider_image(string $field, array $allowedExts, string $relativeDir): ?string
{
    global $uploadRoot;

    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Errore durante upload immagine.');
    }

    $originalName = $_FILES[$field]['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExts, true)) {
        throw new RuntimeException('Formato immagine non consentito: ' . e($originalName));
    }

    ensure_slider_dir($relativeDir);

    $filename = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $relativePath = trim($relativeDir, '/') . '/' . $filename;
    $absolutePath = $uploadRoot . '/' . $relativePath;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $absolutePath)) {
        throw new RuntimeException('Impossibile salvare immagine.');
    }

    return $relativePath;
}

function delete_slider_file_if_exists(?string $relativePath): void
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

function fetch_slider_options(PDO $pdo, string $table): array
{
    try {
        $stmt = $pdo->query("SELECT id, titolo FROM {$table} ORDER BY titolo ASC");
        return $stmt->fetchAll();
    } catch (Throwable $e) {
        return [];
    }
}

$eventi = fetch_slider_options($pdo, 'eventi');
$percorsi = fetch_slider_options($pdo, 'percorsi');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$error = '';

$empty = [
    'id' => null,
    'titolo' => '',
    'sottotitolo' => '',
    'image_path' => '',
    'button_label' => 'info',
    'link_type' => 'none',
    'custom_url' => '',
    'evento_id' => '',
    'percorso_id' => '',
    'ordine' => 0,
    'pubblicato' => 1,
];

$slide = $empty;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM home_slider WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $slide = $stmt->fetch();

    if (!$slide) {
        http_response_code(404);
        exit('Slide non trovata.');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $id = (int) ($_POST['id'] ?? 0);
        $titolo = trim($_POST['titolo'] ?? '');

        if ($titolo === '') {
            throw new RuntimeException('Il titolo è obbligatorio.');
        }

        $old = null;

        if ($id) {
            $stmt = $pdo->prepare('SELECT * FROM home_slider WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $old = $stmt->fetch();

            if (!$old) {
                throw new RuntimeException('Slide non trovata.');
            }
        }

        $linkType = $_POST['link_type'] ?? 'none';

        if (!in_array($linkType, ['none', 'free', 'evento', 'percorso'], true)) {
            $linkType = 'none';
        }

        $customUrl = trim($_POST['custom_url'] ?? '');
        $eventoId = !empty($_POST['evento_id']) ? (int) $_POST['evento_id'] : null;
        $percorsoId = !empty($_POST['percorso_id']) ? (int) $_POST['percorso_id'] : null;

        if ($linkType !== 'free') {
            $customUrl = null;
        }

        if ($linkType !== 'evento') {
            $eventoId = null;
        }

        if ($linkType !== 'percorso') {
            $percorsoId = null;
        }

        $image = upload_slider_image('image', ['jpg', 'jpeg', 'png', 'webp', 'gif'], $uploadDir);
        $imagePath = $image ?: ($old['image_path'] ?? null);

        if (!$imagePath) {
            throw new RuntimeException('Carica una immagine per la slide.');
        }

        if ($image && !empty($old['image_path'])) {
            delete_slider_file_if_exists($old['image_path']);
        }

        $data = [
            'titolo' => $titolo,
            'sottotitolo' => trim($_POST['sottotitolo'] ?? ''),
            'image_path' => $imagePath,
            'button_label' => trim($_POST['button_label'] ?? '') ?: 'info',
            'link_type' => $linkType,
            'custom_url' => $customUrl,
            'evento_id' => $eventoId,
            'percorso_id' => $percorsoId,
            'ordine' => (int) ($_POST['ordine'] ?? 0),
            'pubblicato' => isset($_POST['pubblicato']) ? 1 : 0,
        ];

        if ($id) {
            $data['id'] = $id;

            $stmt = $pdo->prepare("
                UPDATE home_slider SET
                    titolo = :titolo,
                    sottotitolo = :sottotitolo,
                    image_path = :image_path,
                    button_label = :button_label,
                    link_type = :link_type,
                    custom_url = :custom_url,
                    evento_id = :evento_id,
                    percorso_id = :percorso_id,
                    ordine = :ordine,
                    pubblicato = :pubblicato
                WHERE id = :id
            ");
            $stmt->execute($data);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO home_slider (
                    titolo, sottotitolo, image_path, button_label,
                    link_type, custom_url, evento_id, percorso_id,
                    ordine, pubblicato
                ) VALUES (
                    :titolo, :sottotitolo, :image_path, :button_label,
                    :link_type, :custom_url, :evento_id, :percorso_id,
                    :ordine, :pubblicato
                )
            ");
            $stmt->execute($data);
        }

        header('Location: slider.php');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $slide = array_merge($empty, $_POST);
    }
}
?>

<?php admin_page_open('Slide', 'slider'); ?>
<script>
        function toggleSliderLinkFields() {
            var type = document.getElementById('link_type').value;

            document.getElementById('field_custom_url').style.display = type === 'free' ? 'block' : 'none';
            document.getElementById('field_evento_id').style.display = type === 'evento' ? 'block' : 'none';
            document.getElementById('field_percorso_id').style.display = type === 'percorso' ? 'block' : 'none';
        }

        document.addEventListener('DOMContentLoaded', toggleSliderLinkFields);
    </script>

<main class="wrap">
        <div class="box">
            <?php if ($error): ?>
                <div class="error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) ($slide['id'] ?? 0) ?>">

                <div class="grid">
                    <div>
                        <label for="titolo">Titolo *</label>
                        <input type="text" id="titolo" name="titolo" value="<?= e($slide['titolo']) ?>" required>
                        <div class="hint">Per andare a capo usa il tag: &lt;br&gt;</div>
                    </div>

                    <div>
                        <label for="sottotitolo">Sottotitolo</label>
                        <input type="text" id="sottotitolo" name="sottotitolo" value="<?= e($slide['sottotitolo']) ?>">
                    </div>

                    <div>
                        <label for="button_label">Testo bottone</label>
                        <input type="text" id="button_label" name="button_label" value="<?= e($slide['button_label'] ?: 'info') ?>">
                    </div>

                    <div>
                        <label for="ordine">Ordine</label>
                        <input type="number" id="ordine" name="ordine" value="<?= e($slide['ordine']) ?>">
                    </div>

                    <div>
                        <label for="link_type">Link bottone</label>
                        <select id="link_type" name="link_type" onchange="toggleSliderLinkFields()">
                            <option value="none" <?= $slide['link_type'] === 'none' ? 'selected' : '' ?>>Nessun link</option>
                            <option value="evento" <?= $slide['link_type'] === 'evento' ? 'selected' : '' ?>>Evento</option>
                            <option value="percorso" <?= $slide['link_type'] === 'percorso' ? 'selected' : '' ?>>Percorso</option>
                            <option value="free" <?= $slide['link_type'] === 'free' ? 'selected' : '' ?>>Libero</option>
                        </select>
                    </div>

                    <div>
                        <label>Stato</label>
                        <label>
                            <input type="checkbox" id="pubblicato" name="pubblicato" value="1" <?= !empty($slide['pubblicato']) ? 'checked' : '' ?> style="width:auto;">
                            pubblicata
                        </label>
                    </div>

                    <div id="field_evento_id" class="full">
                        <label for="evento_id">Evento collegato</label>
                        <select id="evento_id" name="evento_id">
                            <option value="">Seleziona evento</option>
                            <?php foreach ($eventi as $evento): ?>
                                <option value="<?= (int) $evento['id'] ?>" <?= (int) ($slide['evento_id'] ?? 0) === (int) $evento['id'] ? 'selected' : '' ?>>
                                    <?= e($evento['titolo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="field_percorso_id" class="full">
                        <label for="percorso_id">Percorso collegato</label>
                        <select id="percorso_id" name="percorso_id">
                            <option value="">Seleziona percorso</option>
                            <?php foreach ($percorsi as $percorso): ?>
                                <option value="<?= (int) $percorso['id'] ?>" <?= (int) ($slide['percorso_id'] ?? 0) === (int) $percorso['id'] ? 'selected' : '' ?>>
                                    <?= e($percorso['titolo']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="field_custom_url" class="full">
                        <label for="custom_url">URL libero</label>
                        <input type="text" id="custom_url" name="custom_url" value="<?= e($slide['custom_url']) ?>" placeholder="https://... oppure pagina.php">
                    </div>

                    <div class="full">
                        <label for="image">Immagine slide</label>
                        <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,.gif,image/*" <?= $slide['id'] ? '' : 'required' ?>>

                        <?php if (!empty($slide['image_path'])): ?>
                            <div class="hint">Attuale: <?= e($slide['image_path']) ?></div>
                            <img class="preview" src="../<?= e($slide['image_path']) ?>" alt="<?= e($slide['titolo']) ?>">
                        <?php endif; ?>
                    </div>

                    <div class="full">
                        <button class="btn" type="submit">Salva slide</button>
                        <a class="btn secondary" href="slider.php">Annulla</a>
                    </div>
                </div>
            </form>
        </div>
    </main>
<?php admin_page_close(); ?>
