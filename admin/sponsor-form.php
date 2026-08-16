<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';
require_once __DIR__ . '/_sponsor_helpers.php';

$id = max(0, (int) ($_GET['id'] ?? $_POST['id'] ?? 0));
$error = '';
$sponsor = [
    'id' => 0,
    'image_path' => '',
    'alt_text' => '',
    'url' => '',
    'ordine' => 0,
    'pubblicato' => 1,
];

if ($id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM sponsors WHERE id = :id LIMIT 1');
    $stmt->execute(['id' => $id]);
    $record = $stmt->fetch();
    if (!$record) {
        http_response_code(404);
        exit('Sponsor non trovato.');
    }
    $sponsor = $record;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $uploadedPath = null;

    try {
        $altText = trim((string) ($_POST['alt_text'] ?? ''));
        if ($altText === '') {
            throw new RuntimeException('Il testo alternativo è obbligatorio.');
        }
        if (mb_strlen($altText) > 255) {
            throw new RuntimeException('Il testo alternativo non può superare 255 caratteri.');
        }

        $url = sponsor_normalize_url((string) ($_POST['url'] ?? ''));
        $uploadedPath = sponsor_upload_image('image');
        $oldImagePath = (string) ($sponsor['image_path'] ?? '');
        $imagePath = $uploadedPath ?: $oldImagePath;
        if ($imagePath === '') {
            throw new RuntimeException('Carica il logo dello sponsor.');
        }

        $data = [
            'image_path' => $imagePath,
            'alt_text' => $altText,
            'url' => $url,
            'ordine' => (int) ($_POST['ordine'] ?? 0),
            'pubblicato' => isset($_POST['pubblicato']) ? 1 : 0,
        ];

        if ($id > 0) {
            $data['id'] = $id;
            $stmt = $pdo->prepare('UPDATE sponsors SET image_path = :image_path, alt_text = :alt_text, url = :url, ordine = :ordine, pubblicato = :pubblicato WHERE id = :id');
            $stmt->execute($data);
        } else {
            $stmt = $pdo->prepare('INSERT INTO sponsors (image_path, alt_text, url, ordine, pubblicato) VALUES (:image_path, :alt_text, :url, :ordine, :pubblicato)');
            $stmt->execute($data);
        }

        if ($uploadedPath !== null && $oldImagePath !== '') {
            sponsor_delete_uploaded_image($oldImagePath);
        }

        header('Location: sponsor.php?msg=' . urlencode($id > 0 ? 'Sponsor aggiornato.' : 'Sponsor aggiunto.'));
        exit;
    } catch (Throwable $e) {
        if ($uploadedPath !== null) {
            sponsor_delete_uploaded_image($uploadedPath);
        }
        $error = $e->getMessage();
        $sponsor = array_merge($sponsor, [
            'alt_text' => trim((string) ($_POST['alt_text'] ?? '')),
            'url' => trim((string) ($_POST['url'] ?? '')),
            'ordine' => (int) ($_POST['ordine'] ?? 0),
            'pubblicato' => isset($_POST['pubblicato']) ? 1 : 0,
        ]);
    }
}
?>

<?php admin_page_open($id > 0 ? 'Modifica sponsor' : 'Nuovo sponsor', 'sponsor'); ?>

<main class="wrap">
    <div class="page-title">
        <h1><?= $id > 0 ? 'Modifica sponsor' : 'Nuovo sponsor' ?></h1>
        <p>Inserisci logo, descrizione accessibile e sito collegato.</p>
    </div>

    <div class="box">
        <?php if ($error !== ''): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int) $id ?>">

            <div class="grid">
                <div>
                    <label for="image">Logo <?= $id === 0 ? '*' : '' ?></label>
                    <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/gif" <?= $id === 0 ? 'required' : '' ?>>
                    <div class="hint">JPG, PNG, WEBP o GIF. Massimo 5 MB. In modifica lascia vuoto per mantenere il logo attuale.</div>
                    <?php if (!empty($sponsor['image_path'])): ?>
                        <img class="preview" style="max-height:180px;object-fit:contain" src="../<?= e(ltrim((string) $sponsor['image_path'], '/')) ?>" alt="Anteprima logo attuale">
                    <?php endif; ?>
                </div>

                <div>
                    <label for="alt_text">Testo alternativo *</label>
                    <input type="text" id="alt_text" name="alt_text" maxlength="255" value="<?= e($sponsor['alt_text']) ?>" required>
                    <div class="hint">Descrive il logo a chi usa lettori di schermo, per esempio “Comune di Lauco”.</div>
                </div>

                <div class="full">
                    <label for="url">URL</label>
                    <input type="url" id="url" name="url" maxlength="2048" placeholder="https://www.esempio.it" value="<?= e($sponsor['url']) ?>">
                    <div class="hint">Facoltativo. Se compilato, il logo diventa cliccabile e apre il sito in una nuova scheda.</div>
                </div>

                <div>
                    <label for="ordine">Ordine</label>
                    <input type="number" id="ordine" name="ordine" value="<?= (int) $sponsor['ordine'] ?>">
                    <div class="hint">I numeri più bassi vengono mostrati per primi.</div>
                </div>

                <div>
                    <label>
                        <input type="checkbox" name="pubblicato" value="1" <?= (int) $sponsor['pubblicato'] === 1 ? 'checked' : '' ?>>
                        Visibile nella homepage
                    </label>
                </div>
            </div>

            <div class="actions" style="margin-top:24px;margin-bottom:0">
                <button class="btn" type="submit">Salva</button>
                <a class="btn secondary" href="sponsor.php">Annulla</a>
            </div>
        </form>
    </div>
</main>

<?php admin_page_close(); ?>
