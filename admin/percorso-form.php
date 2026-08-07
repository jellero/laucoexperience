<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/gpx-stats.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

$uploadRoot = dirname(__DIR__);
$uploadBase = 'uploads/percorsi';

function ensure_dir(string $relativeDir): void
{
    global $uploadRoot;

    $absolute = $uploadRoot . '/' . trim($relativeDir, '/');

    if (!is_dir($absolute) && !mkdir($absolute, 0775, true) && !is_dir($absolute)) {
        throw new RuntimeException('Impossibile creare la cartella upload.');
    }
}

function upload_single(string $field, array $allowedExts, string $relativeDir): ?string
{
    global $uploadRoot;

    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Errore durante upload file: ' . $field);
    }

    $originalName = $_FILES[$field]['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($ext, $allowedExts, true)) {
        throw new RuntimeException('Formato file non consentito: ' . e($originalName));
    }

    ensure_dir($relativeDir);

    $filename = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $relativePath = trim($relativeDir, '/') . '/' . $filename;
    $absolutePath = $uploadRoot . '/' . $relativePath;

    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $absolutePath)) {
        throw new RuntimeException('Impossibile salvare il file caricato.');
    }

    return $relativePath;
}

function upload_multiple(string $field, array $allowedExts, string $relativeDir): array
{
    global $uploadRoot;

    if (empty($_FILES[$field]) || empty($_FILES[$field]['name'][0])) {
        return [];
    }

    ensure_dir($relativeDir);

    $paths = [];
    $count = count($_FILES[$field]['name']);

    for ($i = 0; $i < $count; $i++) {
        if ($_FILES[$field]['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($_FILES[$field]['error'][$i] !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Errore durante upload gallery.');
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
            throw new RuntimeException('Impossibile salvare una foto gallery.');
        }

        $paths[] = $relativePath;
    }

    return $paths;
}

function delete_file_if_exists(?string $relativePath): void
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
    'slug' => '',
    'tipo' => 'piedi',
    'sottotitolo' => '',
    'excerpt' => '',
    'descrizione' => '',
    'cover_image' => '',
    'gpx_file' => '',
    'localita' => '',
    'difficolta' => '',
    'tempo' => '',
    'ordine' => 0,
    'pubblicato' => 1,
    'consigliato' => 0,
    'speciale' => 0,
];

$percorso = $empty;
$gallery = [];

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM percorsi WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $percorso = $stmt->fetch();

    if (!$percorso) {
        http_response_code(404);
        exit('Percorso non trovato.');
    }

    $stmt = $pdo->prepare('SELECT * FROM percorso_gallery WHERE percorso_id = :id ORDER BY sort_order ASC, id ASC');
    $stmt->execute(['id' => $id]);
    $gallery = $stmt->fetchAll();
}

$currentStats = gpx_stats($percorso['gpx_file'] ?? null, $percorso['tipo'] ?? 'piedi');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $id = (int) ($_POST['id'] ?? 0);
        $titolo = trim($_POST['titolo'] ?? '');
        $tipo = $_POST['tipo'] ?? 'piedi';

        if ($titolo === '') {
            throw new RuntimeException('Il titolo è obbligatorio.');
        }

        if (!in_array($tipo, ['piedi', 'mtb'], true)) {
            throw new RuntimeException('Tipo percorso non valido.');
        }

        $old = null;

        if ($id) {
            $stmt = $pdo->prepare('SELECT * FROM percorsi WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $id]);
            $old = $stmt->fetch();

            if (!$old) {
                throw new RuntimeException('Percorso non trovato.');
            }
        }

        $slugInput = trim($_POST['slug'] ?? '');
        $slug = $slugInput !== '' ? slugify($slugInput) : unique_slug($pdo, $titolo, $id ?: null);

        if ($slugInput !== '') {
            $slug = unique_slug($pdo, $slug, $id ?: null);
        }

        $cover = upload_single('cover_image', ['jpg', 'jpeg', 'png', 'webp', 'gif'], $uploadBase . '/cover');
        $gpx = upload_single('gpx_file', ['gpx'], $uploadBase . '/gpx');

        $data = [
            'titolo' => $titolo,
            'slug' => $slug,
            'tipo' => $tipo,
            'sottotitolo' => trim($_POST['sottotitolo'] ?? ''),
            'excerpt' => trim($_POST['excerpt'] ?? ''),
            'descrizione' => trim($_POST['descrizione'] ?? ''),
            'cover_image' => $cover ?: ($old['cover_image'] ?? null),
            'gpx_file' => $gpx ?: ($old['gpx_file'] ?? null),
            'localita' => trim($_POST['localita'] ?? ''),
            'difficolta' => trim($_POST['difficolta'] ?? ''),
            'tempo' => trim($_POST['tempo'] ?? ''),
            'ordine' => (int) ($_POST['ordine'] ?? 0),
            'pubblicato' => isset($_POST['pubblicato']) ? 1 : 0,
            'consigliato' => isset($_POST['consigliato']) ? 1 : 0,
            'speciale' => isset($_POST['speciale']) ? 1 : 0,
        ];

        if ($cover && !empty($old['cover_image'])) {
            delete_file_if_exists($old['cover_image']);
        }

        if ($gpx && !empty($old['gpx_file'])) {
            delete_file_if_exists($old['gpx_file']);
        }

        if ($id) {
            $data['id'] = $id;
            $stmt = $pdo->prepare("
                UPDATE percorsi SET
                    titolo = :titolo,
                    slug = :slug,
                    tipo = :tipo,
                    sottotitolo = :sottotitolo,
                    excerpt = :excerpt,
                    descrizione = :descrizione,
                    cover_image = :cover_image,
                    gpx_file = :gpx_file,
                    localita = :localita,
                    difficolta = :difficolta,
                    tempo = :tempo,
                    ordine = :ordine,
                    pubblicato = :pubblicato,
                    consigliato = :consigliato,
                    speciale = :speciale
                WHERE id = :id
            ");
            $stmt->execute($data);
            $percorsoId = $id;
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO percorsi (
                    titolo, slug, tipo, sottotitolo, excerpt, descrizione,
                    cover_image, gpx_file, localita, difficolta, tempo, ordine, pubblicato, consigliato, speciale
                ) VALUES (
                    :titolo, :slug, :tipo, :sottotitolo, :excerpt, :descrizione,
                    :cover_image, :gpx_file, :localita, :difficolta, :tempo, :ordine, :pubblicato, :consigliato, :speciale
                )
            ");
            $stmt->execute($data);
            $percorsoId = (int) $pdo->lastInsertId();
        }

        $deleteGallery = $_POST['delete_gallery'] ?? [];

        if ($deleteGallery) {
            foreach ($deleteGallery as $galleryId) {
                $stmt = $pdo->prepare('SELECT * FROM percorso_gallery WHERE id = :id AND percorso_id = :percorso_id');
                $stmt->execute(['id' => (int) $galleryId, 'percorso_id' => $percorsoId]);
                $row = $stmt->fetch();

                if ($row) {
                    delete_file_if_exists($row['image_path']);
                    $del = $pdo->prepare('DELETE FROM percorso_gallery WHERE id = :id');
                    $del->execute(['id' => (int) $galleryId]);
                }
            }
        }

        $galleryPaths = upload_multiple('gallery_images', ['jpg', 'jpeg', 'png', 'webp', 'gif'], $uploadBase . '/gallery');

        if ($galleryPaths) {
            $sort = 0;
            $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) FROM percorso_gallery WHERE percorso_id = :id');
            $stmt->execute(['id' => $percorsoId]);
            $sort = (int) $stmt->fetchColumn();

            $insertGallery = $pdo->prepare("
                INSERT INTO percorso_gallery (percorso_id, image_path, alt, sort_order)
                VALUES (:percorso_id, :image_path, :alt, :sort_order)
            ");

            foreach ($galleryPaths as $path) {
                $sort++;
                $insertGallery->execute([
                    'percorso_id' => $percorsoId,
                    'image_path' => $path,
                    'alt' => $titolo,
                    'sort_order' => $sort,
                ]);
            }
        }

        header('Location: percorsi.php');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $percorso = array_merge($empty, $_POST);
    }
}
?>

<?php
$displayDifficulty = trim((string) ($percorso['difficolta'] ?? '')) ?: ($currentStats['difficulty'] ?? '-');
$displayTime = trim((string) ($percorso['tempo'] ?? '')) ?: ($currentStats['duration_label'] ?? '-');
?>

<?php admin_page_open('Percorso', 'percorsi'); ?>

<main class="wrap">
        <div class="box">
            <?php if ($error): ?>
                <div class="error"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int) ($percorso['id'] ?? 0) ?>">

                <div class="grid">
                    <div>
                        <label for="titolo">Titolo *</label>
                        <input type="text" id="titolo" name="titolo" value="<?= e($percorso['titolo']) ?>" required>
                    </div>

                    <div>
                        <label for="slug">Slug</label>
                        <input type="text" id="slug" name="slug" value="<?= e($percorso['slug']) ?>">
                        <div class="hint">Se vuoto, viene generato dal titolo.</div>
                    </div>

                    <div>
                        <label for="tipo">Tipo *</label>
                        <select id="tipo" name="tipo" required>
                            <option value="piedi" <?= $percorso['tipo'] === 'piedi' ? 'selected' : '' ?>>Piedi</option>
                            <option value="mtb" <?= $percorso['tipo'] === 'mtb' ? 'selected' : '' ?>>MTB</option>
                        </select>
                    </div>

                    <div>
                        <label>Stato</label>
                        <label>
                            <input type="checkbox" id="pubblicato" name="pubblicato" value="1" <?= !empty($percorso['pubblicato']) ? 'checked' : '' ?> style="width:auto;">
                            pubblicato
                        </label>

                        <label style="margin-top:10px;">
                            <input type="checkbox" id="consigliato" name="consigliato" value="1" <?= !empty($percorso['consigliato']) ? 'checked' : '' ?> style="width:auto;">
                            consigliato
                        </label>

                        <label style="margin-top:10px;">
                            <input type="checkbox" id="speciale" name="speciale" value="1" <?= !empty($percorso['speciale']) ? 'checked' : '' ?> style="width:auto;">
                            speciale
                        </label>
                    </div>

                    <div class="full stats">
                        <strong>Dati percorso</strong><br>
                        Lunghezza GPX: <?= e($currentStats['length_label']) ?> ·
                        Dislivello GPX: <?= e($currentStats['ascent_label']) ?> ·
                        Tempo pubblicato: <?= e($displayTime) ?> ·
                        Difficoltà pubblicata: <?= e($displayDifficulty) ?> ·
                        Calorie GPX: <?= e($currentStats['calories_label']) ?> ·
                        Aggiornamento GPX: <?= e($currentStats['updated_label']) ?>
                        <div class="hint">Difficoltà e tempo di percorrenza possono essere compilati manualmente qui sotto. Se li lasci vuoti, vengono usati i dati automatici dal GPX.</div>
                    </div>

                    <div class="full">
                        <label for="sottotitolo">Sottotitolo</label>
                        <input type="text" id="sottotitolo" name="sottotitolo" value="<?= e($percorso['sottotitolo']) ?>">
                    </div>

                    <div class="full">
                        <label for="excerpt">Descrizione breve</label>
                        <textarea id="excerpt" name="excerpt"><?= e($percorso['excerpt']) ?></textarea>
                    </div>

                    <div class="full">
                        <label for="descrizione">Descrizione completa</label>
                        <textarea id="descrizione" name="descrizione" style="min-height:260px;"><?= e($percorso['descrizione']) ?></textarea>
                    </div>

                    <div>
                        <label for="localita">Località</label>
                        <input type="text" id="localita" name="localita" value="<?= e($percorso['localita']) ?>">
                    </div>

                    <div>
                        <label for="difficolta">Difficoltà</label>
                        <input type="text" id="difficolta" name="difficolta" value="<?= e($percorso['difficolta'] ?? '') ?>" placeholder="es. T, E, EE, Facile, Media">
                        <div class="hint">Opzionale. Se vuoto, viene mostrata la difficoltà calcolata dal GPX.</div>
                    </div>

                    <div>
                        <label for="tempo">Tempo di percorrenza</label>
                        <input type="text" id="tempo" name="tempo" value="<?= e($percorso['tempo'] ?? '') ?>" placeholder="es. 1 h 30 min">
                        <div class="hint">Opzionale. Se vuoto, viene mostrato il tempo calcolato dal GPX.</div>
                    </div>

                    <div>
                        <label for="ordine">Ordine</label>
                        <input type="number" id="ordine" name="ordine" value="<?= e($percorso['ordine']) ?>">
                    </div>

                    <div>
                        <label for="cover_image">Foto copertina</label>
                        <input type="file" id="cover_image" name="cover_image" accept=".jpg,.jpeg,.png,.webp,.gif,image/*">
                        <?php if (!empty($percorso['cover_image'])): ?>
                            <div class="hint">Attuale: <?= e($percorso['cover_image']) ?></div>
                            <img src="../<?= e($percorso['cover_image']) ?>" alt="" style="max-width:220px; margin-top:10px;">
                        <?php endif; ?>
                    </div>

                    <div>
                        <label for="gpx_file">Percorso GPX</label>
                        <input type="file" id="gpx_file" name="gpx_file" accept=".gpx">
                        <?php if (!empty($percorso['gpx_file'])): ?>
                            <div class="hint">Attuale: <?= e($percorso['gpx_file']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="full">
                        <label for="gallery_images">Foto gallery</label>
                        <input type="file" id="gallery_images" name="gallery_images[]" accept=".jpg,.jpeg,.png,.webp,.gif,image/*" multiple>

                        <?php if ($gallery): ?>
                            <div class="thumbs">
                                <?php foreach ($gallery as $img): ?>
                                    <label class="thumb">
                                        <img src="../<?= e($img['image_path']) ?>" alt="<?= e($img['alt'] ?? '') ?>">
                                        <input type="checkbox" name="delete_gallery[]" value="<?= (int) $img['id'] ?>" style="width:auto;">
                                        elimina
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="full">
                        <button class="btn" type="submit">Salva percorso</button>
                        <a class="btn secondary" href="percorsi.php">Annulla</a>
                    </div>
                </div>
            </form>
        </div>
    </main>
<?php admin_page_close(); ?>
