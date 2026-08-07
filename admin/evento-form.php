<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

$root = dirname(__DIR__);
$base = 'uploads/eventi';

function ev_mkdir($dir) {
    global $root;
    $abs = $root . '/' . trim($dir, '/');
    if (!is_dir($abs) && !mkdir($abs, 0775, true) && !is_dir($abs)) {
        throw new RuntimeException('Impossibile creare la cartella upload.');
    }
}

function ev_upload_one($field, $dir) {
    global $root;
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Errore upload file.');
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) throw new RuntimeException('Formato immagine non consentito.');
    ev_mkdir($dir);
    $name = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
    $rel = trim($dir, '/') . '/' . $name;
    if (!move_uploaded_file($_FILES[$field]['tmp_name'], $root . '/' . $rel)) throw new RuntimeException('Impossibile salvare file.');
    return $rel;
}

function ev_upload_many($field, $dir) {
    global $root;
    if (empty($_FILES[$field]) || empty($_FILES[$field]['name'][0])) return [];
    ev_mkdir($dir);
    $out = [];
    foreach ($_FILES[$field]['name'] as $i => $n) {
        if ($_FILES[$field]['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
        if ($_FILES[$field]['error'][$i] !== UPLOAD_ERR_OK) throw new RuntimeException('Errore upload gallery.');
        $ext = strtolower(pathinfo($n, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) throw new RuntimeException('Formato gallery non consentito.');
        $name = date('YmdHis') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        $rel = trim($dir, '/') . '/' . $name;
        if (!move_uploaded_file($_FILES[$field]['tmp_name'][$i], $root . '/' . $rel)) throw new RuntimeException('Impossibile salvare gallery.');
        $out[] = $rel;
    }
    return $out;
}

function ev_delete_file($rel) {
    global $root;
    if (!$rel) return;
    $abs = realpath($root . '/' . $rel);
    $uploads = realpath($root . '/uploads');
    if ($abs && $uploads && strpos($abs, $uploads) === 0 && is_file($abs)) unlink($abs);
}

function evento_unique_slug(PDO $pdo, $title, $ignoreId = null) {
    $base = slugify($title);
    $slug = $base;
    $i = 2;
    while (true) {
        if ($ignoreId) {
            $s = $pdo->prepare('SELECT id FROM eventi WHERE slug = :slug AND id <> :id LIMIT 1');
            $s->execute(['slug'=>$slug,'id'=>$ignoreId]);
        } else {
            $s = $pdo->prepare('SELECT id FROM eventi WHERE slug = :slug LIMIT 1');
            $s->execute(['slug'=>$slug]);
        }
        if (!$s->fetch()) return $slug;
        $slug = $base . '-' . $i++;
    }
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$evento = [
    'id'=>null,'titolo'=>'','slug'=>'','data_evento'=>'','localita'=>'','categoria'=>'',
    'excerpt'=>'','contenuto'=>'','cover_image'=>'','ordine'=>0,'pubblicato'=>1
];
$gallery = [];

if ($id) {
    $s = $pdo->prepare('SELECT * FROM eventi WHERE id = :id LIMIT 1');
    $s->execute(['id'=>$id]);
    $evento = $s->fetch();
    if (!$evento) { http_response_code(404); exit('Evento non trovato.'); }
    $s = $pdo->prepare('SELECT * FROM evento_gallery WHERE evento_id = :id ORDER BY sort_order ASC, id ASC');
    $s->execute(['id'=>$id]);
    $gallery = $s->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $id = (int)($_POST['id'] ?? 0);
        $titolo = trim($_POST['titolo'] ?? '');
        if ($titolo === '') throw new RuntimeException('Il titolo è obbligatorio.');

        $old = null;
        if ($id) {
            $s = $pdo->prepare('SELECT * FROM eventi WHERE id = :id LIMIT 1');
            $s->execute(['id'=>$id]);
            $old = $s->fetch();
            if (!$old) throw new RuntimeException('Evento non trovato.');
        }

        $slugInput = trim($_POST['slug'] ?? '');
        $slug = $slugInput !== '' ? slugify($slugInput) : evento_unique_slug($pdo, $titolo, $id ?: null);
        if ($slugInput !== '') $slug = evento_unique_slug($pdo, $slug, $id ?: null);

        $cover = ev_upload_one('cover_image', $base . '/cover');
        $data = [
            'titolo'=>$titolo,
            'slug'=>$slug,
            'data_evento'=>trim($_POST['data_evento'] ?? '') ?: null,
            'localita'=>trim($_POST['localita'] ?? ''),
            'categoria'=>trim($_POST['categoria'] ?? ''),
            'excerpt'=>trim($_POST['excerpt'] ?? ''),
            'contenuto'=>trim($_POST['contenuto'] ?? ''),
            'cover_image'=>$cover ?: ($old['cover_image'] ?? null),
            'ordine'=>(int)($_POST['ordine'] ?? 0),
            'pubblicato'=>isset($_POST['pubblicato']) ? 1 : 0,
        ];

        if ($cover && !empty($old['cover_image'])) ev_delete_file($old['cover_image']);

        if ($id) {
            $data['id'] = $id;
            $sql = "UPDATE eventi SET titolo=:titolo, slug=:slug, data_evento=:data_evento, localita=:localita,
                    categoria=:categoria, excerpt=:excerpt, contenuto=:contenuto, cover_image=:cover_image,
                    ordine=:ordine, pubblicato=:pubblicato WHERE id=:id";
            $pdo->prepare($sql)->execute($data);
            $eventoId = $id;
        } else {
            $sql = "INSERT INTO eventi (titolo,slug,data_evento,localita,categoria,excerpt,contenuto,cover_image,ordine,pubblicato)
                    VALUES (:titolo,:slug,:data_evento,:localita,:categoria,:excerpt,:contenuto,:cover_image,:ordine,:pubblicato)";
            $pdo->prepare($sql)->execute($data);
            $eventoId = (int)$pdo->lastInsertId();
        }

        foreach (($_POST['delete_gallery'] ?? []) as $gid) {
            $s = $pdo->prepare('SELECT * FROM evento_gallery WHERE id = :id AND evento_id = :evento_id');
            $s->execute(['id'=>(int)$gid,'evento_id'=>$eventoId]);
            $row = $s->fetch();
            if ($row) {
                ev_delete_file($row['image_path']);
                $pdo->prepare('DELETE FROM evento_gallery WHERE id = :id')->execute(['id'=>(int)$gid]);
            }
        }

        $paths = ev_upload_many('gallery_images', $base . '/gallery');
        if ($paths) {
            $s = $pdo->prepare('SELECT COALESCE(MAX(sort_order),0) FROM evento_gallery WHERE evento_id = :id');
            $s->execute(['id'=>$eventoId]);
            $sort = (int)$s->fetchColumn();
            $ins = $pdo->prepare('INSERT INTO evento_gallery (evento_id,image_path,alt,sort_order) VALUES (:evento_id,:image_path,:alt,:sort_order)');
            foreach ($paths as $p) {
                $ins->execute(['evento_id'=>$eventoId,'image_path'=>$p,'alt'=>$titolo,'sort_order'=>++$sort]);
            }
        }

        header('Location: eventi.php');
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
        $evento = array_merge($evento, $_POST);
    }
}
?>

<?php admin_page_open('Evento', 'eventi'); ?>

<main class="wrap"><div class="box">
<?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">
<input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
<input type="hidden" name="id" value="<?= (int)($evento['id'] ?? 0) ?>">

<div class="grid">
    <div><label>Titolo *</label><input name="titolo" value="<?= e($evento['titolo']) ?>" required></div>
    <div><label>Slug</label><input name="slug" value="<?= e($evento['slug']) ?>"><div class="hint">Se vuoto viene generato dal titolo.</div></div>
    <div><label>Data evento</label><input type="date" name="data_evento" value="<?= e($evento['data_evento']) ?>"></div>
    <div><label>Categoria</label><input name="categoria" value="<?= e($evento['categoria']) ?>" placeholder="Corsa, Tradizione"></div>
    <div><label>Località</label><input name="localita" value="<?= e($evento['localita']) ?>"></div>
    <div><label>Stato</label><label><input type="checkbox" name="pubblicato" value="1" <?= !empty($evento['pubblicato']) ? 'checked' : '' ?> style="width:auto"> pubblicato</label></div>
    <div><label>Ordine</label><input type="number" name="ordine" value="<?= e($evento['ordine']) ?>"></div>
    <div><label>Foto copertina</label><input type="file" name="cover_image" accept=".jpg,.jpeg,.png,.webp,.gif,image/*">
        <?php if (!empty($evento['cover_image'])): ?><div class="hint">Attuale: <?= e($evento['cover_image']) ?></div><img src="../<?= e($evento['cover_image']) ?>" style="max-width:220px;margin-top:10px"><?php endif; ?>
    </div>
    <div class="full"><label>Descrizione breve</label><textarea name="excerpt"><?= e($evento['excerpt']) ?></textarea></div>
    <div class="full"><label>Testo completo</label><textarea name="contenuto" style="min-height:260px"><?= e($evento['contenuto']) ?></textarea></div>
    <div class="full"><label>Foto gallery</label><input type="file" name="gallery_images[]" accept=".jpg,.jpeg,.png,.webp,.gif,image/*" multiple>
        <?php if ($gallery): ?><div class="thumbs">
            <?php foreach ($gallery as $img): ?><label class="thumb"><img src="../<?= e($img['image_path']) ?>"><input type="checkbox" name="delete_gallery[]" value="<?= (int)$img['id'] ?>" style="width:auto"> elimina</label><?php endforeach; ?>
        </div><?php endif; ?>
    </div>
    <div class="full"><button class="btn" type="submit">Salva evento</button> <a class="btn secondary" href="eventi.php">Annulla</a></div>
</div>
</form>
</div></main>
<?php admin_page_close(); ?>
