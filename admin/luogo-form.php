<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

function luogo_upload_dir(string $sub = ''): string
{
    $base = __DIR__ . '/../uploads/luoghi';
    if ($sub) {
        $base .= '/' . trim($sub, '/');
    }
    if (!is_dir($base)) {
        mkdir($base, 0775, true);
    }
    return $base;
}

function luogo_upload_public(string $filename, string $sub = ''): string
{
    $prefix = 'uploads/luoghi';
    if ($sub) {
        $prefix .= '/' . trim($sub, '/');
    }
    return $prefix . '/' . $filename;
}

function luogo_safe_filename(string $name): string
{
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $base = pathinfo($name, PATHINFO_FILENAME);
    $base = slugify($base ?: 'immagine');
    if (!$ext) {
        $ext = 'jpg';
    }
    return $base . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
}

function luogo_upload_image(array $file, string $sub = ''): ?string
{
    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return null;
    }

    if (!empty($file['error'])) {
        return null;
    }

    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed, true)) {
        throw new RuntimeException('Formato immagine non consentito. Usa jpg, png o webp.');
    }

    $filename = luogo_safe_filename($file['name']);
    $target = luogo_upload_dir($sub) . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new RuntimeException('Impossibile caricare l’immagine.');
    }

    return luogo_upload_public($filename, $sub);
}

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$isEdit = $id > 0;
$error = '';
$msg = trim($_GET['msg'] ?? '');

$luogo = [
    'titolo' => '',
    'slug' => '',
    'sottotitolo' => '',
    'categoria' => '',
    'localita' => '',
    'excerpt' => '',
    'descrizione' => '',
    'cover_image' => '',
    'lat' => '',
    'lng' => '',
    'periodo_consigliato' => '',
    'accessibilita' => '',
    'note' => '',
    'ordine' => 0,
    'pubblicato' => 1,
    'in_evidenza' => 0,
];

if ($isEdit) {
    $stmt = $pdo->prepare('SELECT * FROM luoghi WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $found = $stmt->fetch();

    if (!$found) {
        http_response_code(404);
        exit('Luogo non trovato.');
    }

    $luogo = array_merge($luogo, $found);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        verify_csrf();

        $luogo = array_merge($luogo, [
            'titolo' => trim($_POST['titolo'] ?? ''),
            'sottotitolo' => trim($_POST['sottotitolo'] ?? ''),
            'categoria' => trim($_POST['categoria'] ?? ''),
            'localita' => trim($_POST['localita'] ?? ''),
            'excerpt' => trim($_POST['excerpt'] ?? ''),
            'descrizione' => trim($_POST['descrizione'] ?? ''),
            'lat' => trim($_POST['lat'] ?? ''),
            'lng' => trim($_POST['lng'] ?? ''),
            'periodo_consigliato' => trim($_POST['periodo_consigliato'] ?? ''),
            'accessibilita' => trim($_POST['accessibilita'] ?? ''),
            'note' => trim($_POST['note'] ?? ''),
            'ordine' => (int) ($_POST['ordine'] ?? 0),
            'pubblicato' => !empty($_POST['pubblicato']) ? 1 : 0,
            'in_evidenza' => !empty($_POST['in_evidenza']) ? 1 : 0,
        ]);

        if ($luogo['titolo'] === '') {
            throw new RuntimeException('Inserisci il titolo.');
        }

        $slugInput = trim($_POST['slug'] ?? '');
        $baseSlug = $slugInput !== '' ? slugify($slugInput) : slugify($luogo['titolo']);

        if ($baseSlug === '') {
            throw new RuntimeException('Slug non valido.');
        }

        $cover = luogo_upload_image($_FILES['cover_image'] ?? [], '');

        if ($cover) {
            $luogo['cover_image'] = $cover;
        }

        $lat = $luogo['lat'] !== '' ? $luogo['lat'] : null;
        $lng = $luogo['lng'] !== '' ? $luogo['lng'] : null;

        if ($isEdit) {
            $stmt = $pdo->prepare('SELECT id FROM luoghi WHERE slug = :slug AND id <> :id LIMIT 1');
            $slug = $baseSlug;
            $counter = 2;

            while (true) {
                $stmt->execute(['slug' => $slug, 'id' => $id]);
                if (!$stmt->fetch()) {
                    break;
                }
                $slug = $baseSlug . '-' . $counter++;
            }

            $sql = "
                UPDATE luoghi SET
                    titolo = :titolo,
                    slug = :slug,
                    sottotitolo = :sottotitolo,
                    categoria = :categoria,
                    localita = :localita,
                    excerpt = :excerpt,
                    descrizione = :descrizione,
                    cover_image = :cover_image,
                    lat = :lat,
                    lng = :lng,
                    periodo_consigliato = :periodo_consigliato,
                    accessibilita = :accessibilita,
                    note = :note,
                    ordine = :ordine,
                    pubblicato = :pubblicato,
                    in_evidenza = :in_evidenza
                WHERE id = :id
            ";

            $params = [
                'titolo' => $luogo['titolo'],
                'slug' => $slug,
                'sottotitolo' => $luogo['sottotitolo'],
                'categoria' => $luogo['categoria'],
                'localita' => $luogo['localita'],
                'excerpt' => $luogo['excerpt'],
                'descrizione' => $luogo['descrizione'],
                'cover_image' => $luogo['cover_image'],
                'lat' => $lat,
                'lng' => $lng,
                'periodo_consigliato' => $luogo['periodo_consigliato'],
                'accessibilita' => $luogo['accessibilita'],
                'note' => $luogo['note'],
                'ordine' => $luogo['ordine'],
                'pubblicato' => $luogo['pubblicato'],
                'in_evidenza' => $luogo['in_evidenza'],
                'id' => $id,
            ];

            $pdo->prepare($sql)->execute($params);
            $luogoId = $id;
        } else {
            $slug = unique_slug($pdo, 'luoghi', $baseSlug);

            $sql = "
                INSERT INTO luoghi (
                    titolo, slug, sottotitolo, categoria, localita, excerpt, descrizione,
                    cover_image, lat, lng, periodo_consigliato, accessibilita, note,
                    ordine, pubblicato, in_evidenza
                ) VALUES (
                    :titolo, :slug, :sottotitolo, :categoria, :localita, :excerpt, :descrizione,
                    :cover_image, :lat, :lng, :periodo_consigliato, :accessibilita, :note,
                    :ordine, :pubblicato, :in_evidenza
                )
            ";

            $params = [
                'titolo' => $luogo['titolo'],
                'slug' => $slug,
                'sottotitolo' => $luogo['sottotitolo'],
                'categoria' => $luogo['categoria'],
                'localita' => $luogo['localita'],
                'excerpt' => $luogo['excerpt'],
                'descrizione' => $luogo['descrizione'],
                'cover_image' => $luogo['cover_image'],
                'lat' => $lat,
                'lng' => $lng,
                'periodo_consigliato' => $luogo['periodo_consigliato'],
                'accessibilita' => $luogo['accessibilita'],
                'note' => $luogo['note'],
                'ordine' => $luogo['ordine'],
                'pubblicato' => $luogo['pubblicato'],
                'in_evidenza' => $luogo['in_evidenza'],
            ];

            $pdo->prepare($sql)->execute($params);
            $luogoId = (int) $pdo->lastInsertId();
        }

        $deleteGallery = $_POST['delete_gallery'] ?? [];
        if ($deleteGallery && is_array($deleteGallery)) {
            foreach ($deleteGallery as $galleryId) {
                $galleryId = (int) $galleryId;
                if ($galleryId > 0) {
                    $del = $pdo->prepare('DELETE FROM luogo_gallery WHERE id = :id AND luogo_id = :luogo_id');
                    $del->execute(['id' => $galleryId, 'luogo_id' => $luogoId]);
                }
            }
        }

        if (!empty($_FILES['gallery_images']['name']) && is_array($_FILES['gallery_images']['name'])) {
            $count = count($_FILES['gallery_images']['name']);

            for ($i = 0; $i < $count; $i++) {
                $file = [
                    'name' => $_FILES['gallery_images']['name'][$i],
                    'type' => $_FILES['gallery_images']['type'][$i] ?? '',
                    'tmp_name' => $_FILES['gallery_images']['tmp_name'][$i],
                    'error' => $_FILES['gallery_images']['error'][$i],
                    'size' => $_FILES['gallery_images']['size'][$i] ?? 0,
                ];

                $path = luogo_upload_image($file, 'gallery');

                if ($path) {
                    $order = (int) ($_POST['gallery_order'][$i] ?? 0);
                    $caption = trim($_POST['gallery_caption'][$i] ?? '');

                    $g = $pdo->prepare("
                        INSERT INTO luogo_gallery (luogo_id, image_path, caption, ordine)
                        VALUES (:luogo_id, :image_path, :caption, :ordine)
                    ");
                    $g->execute([
                        'luogo_id' => $luogoId,
                        'image_path' => $path,
                        'caption' => $caption,
                        'ordine' => $order,
                    ]);
                }
            }
        }

        header('Location: luogo-form.php?id=' . $luogoId . '&msg=' . urlencode('Luogo salvato correttamente.'));
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$gallery = [];
if ($isEdit) {
    $g = $pdo->prepare('SELECT * FROM luogo_gallery WHERE luogo_id = :luogo_id ORDER BY ordine ASC, id ASC');
    $g->execute(['luogo_id' => $id]);
    $gallery = $g->fetchAll();
}

admin_page_open($isEdit ? 'Modifica luogo' : 'Nuovo luogo', 'luoghi');
?>
<style>
    .place-form-grid {
        display: grid;
        grid-template-columns: 1.2fr .8fr;
        gap: 22px;
        align-items: start;
    }

    .place-form-box {
        background: #fff;
        box-shadow: var(--admin-shadow);
        padding: 24px;
    }

    .place-form-box label {
        display: block;
        font-weight: 700;
        margin: 15px 0 7px;
    }

    .place-form-box input[type="text"],
    .place-form-box input[type="number"],
    .place-form-box textarea,
    .place-form-box select {
        width: 100%;
        box-sizing: border-box;
        padding: 11px;
        border: 1px solid #ddd;
    }

    .place-form-box textarea {
        min-height: 130px;
        resize: vertical;
    }

    .place-form-box textarea.big {
        min-height: 260px;
    }

    .place-checks {
        display: flex;
        gap: 18px;
        flex-wrap: wrap;
        margin-top: 16px;
    }

    .place-checks label {
        margin: 0;
        font-weight: 600;
    }

    .current-cover {
        width: 100%;
        min-height: 170px;
        background-size: cover;
        background-position: center;
        background-color: #eee;
        margin: 10px 0;
    }

    .gallery-admin-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
        margin-top: 12px;
    }

    .gallery-admin-item {
        border: 1px solid #eee;
        padding: 10px;
    }

    .gallery-admin-thumb {
        min-height: 110px;
        background-size: cover;
        background-position: center;
        background-color: #eee;
        margin-bottom: 8px;
    }

    .gallery-new-row {
        display: grid;
        grid-template-columns: 1fr 90px;
        gap: 8px;
        margin-bottom: 10px;
    }

    @media (max-width: 900px) {
        .place-form-grid {
            grid-template-columns: 1fr;
        }

        .gallery-admin-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<main class="wrap">
    <section class="hero-admin">
        <h1><?= $isEdit ? 'Modifica luogo' : 'Nuovo luogo' ?></h1>
        <p>Gestione scheda, copertina, galleria e pubblicazione.</p>
    </section>

    <?php if ($msg): ?>
        <div class="notice"><?= e($msg) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="notice error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a class="btn secondary" href="luoghi.php">Torna ai luoghi</a>
        <?php if ($isEdit && !empty($luogo['slug'])): ?>
            <a class="btn secondary" href="../luogo?slug=<?= urlencode($luogo['slug']) ?>" target="_blank">Vedi pubblico</a>
        <?php endif; ?>
    </div>

    <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int) $id ?>">

        <div class="place-form-grid">
            <section class="place-form-box">
                <h2>Contenuto</h2>

                <label for="titolo">Titolo *</label>
                <input type="text" id="titolo" name="titolo" value="<?= e($luogo['titolo']) ?>" required>

                <label for="slug">Slug</label>
                <input type="text" id="slug" name="slug" value="<?= e($luogo['slug']) ?>" placeholder="Automatico se vuoto">

                <label for="sottotitolo">Sottotitolo</label>
                <input type="text" id="sottotitolo" name="sottotitolo" value="<?= e($luogo['sottotitolo']) ?>">

                <label for="excerpt">Descrizione breve</label>
                <textarea id="excerpt" name="excerpt"><?= e($luogo['excerpt']) ?></textarea>

                <label for="descrizione">Descrizione completa</label>
                <textarea class="big" id="descrizione" name="descrizione"><?= e($luogo['descrizione']) ?></textarea>
            </section>

            <aside class="place-form-box">
                <h2>Impostazioni</h2>

                <label for="categoria">Categoria</label>
                <input type="text" id="categoria" name="categoria" value="<?= e($luogo['categoria']) ?>" placeholder="Storia, Natura, Panorama...">

                <label for="localita">Località / frazione</label>
                <input type="text" id="localita" name="localita" value="<?= e($luogo['localita']) ?>">

                <label for="periodo_consigliato">Periodo consigliato</label>
                <input type="text" id="periodo_consigliato" name="periodo_consigliato" value="<?= e($luogo['periodo_consigliato']) ?>">

                <label for="ordine">Ordine</label>
                <input type="number" id="ordine" name="ordine" value="<?= (int) $luogo['ordine'] ?>">

                <div class="place-checks">
                    <label><input type="checkbox" name="pubblicato" value="1" <?= !empty($luogo['pubblicato']) ? 'checked' : '' ?>> Pubblicato</label>
                    <label><input type="checkbox" name="in_evidenza" value="1" <?= !empty($luogo['in_evidenza']) ? 'checked' : '' ?>> In evidenza</label>
                </div>

                <label for="lat">Latitudine</label>
                <input type="text" id="lat" name="lat" value="<?= e($luogo['lat']) ?>">

                <label for="lng">Longitudine</label>
                <input type="text" id="lng" name="lng" value="<?= e($luogo['lng']) ?>">
            </aside>

            <section class="place-form-box">
                <h2>Note di visita</h2>

                <label for="accessibilita">Accessibilità / note di visita</label>
                <textarea id="accessibilita" name="accessibilita"><?= e($luogo['accessibilita']) ?></textarea>

                <label for="note">Note interne o pubbliche aggiuntive</label>
                <textarea id="note" name="note"><?= e($luogo['note']) ?></textarea>
            </section>

            <aside class="place-form-box">
                <h2>Immagine copertina</h2>

                <?php if (!empty($luogo['cover_image'])): ?>
                    <div class="current-cover" style="background-image:url(<?= e('../' . $luogo['cover_image']) ?>)"></div>
                <?php endif; ?>

                <input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp">
                <p class="muted">Carica jpg, png o webp.</p>
            </aside>

            <section class="place-form-box">
                <h2>Galleria esistente</h2>

                <?php if (!$gallery): ?>
                    <p class="muted">Nessuna immagine in galleria.</p>
                <?php else: ?>
                    <div class="gallery-admin-grid">
                        <?php foreach ($gallery as $img): ?>
                            <div class="gallery-admin-item">
                                <div class="gallery-admin-thumb" style="background-image:url(<?= e('../' . $img['image_path']) ?>)"></div>
                                <div><?= e($img['caption'] ?: 'Senza didascalia') ?></div>
                                <label><input type="checkbox" name="delete_gallery[]" value="<?= (int) $img['id'] ?>"> elimina</label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>

            <aside class="place-form-box">
                <h2>Aggiungi galleria</h2>

                <label>Immagini</label>
                <input type="file" name="gallery_images[]" multiple accept=".jpg,.jpeg,.png,.webp">

                <p class="muted">Le didascalie e l’ordine valgono per le prime immagini caricate, nello stesso ordine.</p>

                <?php for ($i = 0; $i < 5; $i++): ?>
                    <div class="gallery-new-row">
                        <input type="text" name="gallery_caption[]" placeholder="Didascalia <?= $i + 1 ?>">
                        <input type="number" name="gallery_order[]" placeholder="Ordine">
                    </div>
                <?php endfor; ?>
            </aside>
        </div>

        <div class="actions" style="margin-top:22px;">
            <button class="btn" type="submit">Salva luogo</button>
            <a class="btn secondary" href="luoghi.php">Annulla</a>
        </div>
    </form>
</main>
<?php admin_page_close(); ?>
