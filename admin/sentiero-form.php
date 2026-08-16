<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/../inc/sentieri.php';
require_once __DIR__ . '/_admin_layout.php';

$id = max(0, (int) ($_GET['id'] ?? $_POST['id'] ?? 0));
$error = '';
$newGpx = null;
$trail = [
    'id' => 0, 'nome' => '', 'codice' => '', 'slug' => '', 'localita' => '', 'descrizione' => '',
    'gpx_file' => '', 'stato' => 'in_verifica', 'nota_pubblica' => '', 'ultima_verifica_at' => null,
    'prossima_verifica_at' => null, 'pubblicato' => 1, 'ordine' => 0,
];

try {
    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM sentieri WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $loaded = $stmt->fetch();
        if (!$loaded) {
            http_response_code(404);
            exit('Sentiero non trovato.');
        }
        $trail = $loaded;
    } else {
        $pdo->query('SELECT 1 FROM sentieri LIMIT 1');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        verify_csrf();
        $name = mb_substr(trim((string) ($_POST['nome'] ?? '')), 0, 190);
        $status = (string) ($_POST['stato'] ?? 'in_verifica');
        if ($name === '') {
            throw new RuntimeException('Il nome del sentiero è obbligatorio.');
        }
        if (!array_key_exists($status, sentieri_statuses())) {
            throw new RuntimeException('Lo stato selezionato non è valido.');
        }

        $newGpx = sentieri_store_gpx($_FILES['gpx_file'] ?? []);
        $gpxPath = $newGpx ?: (string) ($trail['gpx_file'] ?? '');
        if ($gpxPath === '') {
            throw new RuntimeException('Carica il file GPX del sentiero.');
        }

        $checkedAt = sentieri_normalize_datetime((string) ($_POST['ultima_verifica_at'] ?? ''));
        $nextCheck = trim((string) ($_POST['prossima_verifica_at'] ?? '')) ?: null;
        if ($nextCheck !== null) {
            $parsedNextCheck = DateTimeImmutable::createFromFormat('!Y-m-d', $nextCheck);
            if ($parsedNextCheck === false || $parsedNextCheck->format('Y-m-d') !== $nextCheck) {
                throw new RuntimeException('La data del prossimo controllo non è valida.');
            }
        }
        $note = trim((string) ($_POST['nota_pubblica'] ?? '')) ?: null;
        $slugInput = trim((string) ($_POST['slug'] ?? ''));
        $slug = sentieri_unique_slug($pdo, $slugInput !== '' ? $slugInput : $name, $id > 0 ? $id : null);
        $data = [
            'nome' => $name,
            'codice' => mb_substr(trim((string) ($_POST['codice'] ?? '')), 0, 80) ?: null,
            'slug' => $slug,
            'localita' => mb_substr(trim((string) ($_POST['localita'] ?? '')), 0, 190) ?: null,
            'descrizione' => trim((string) ($_POST['descrizione'] ?? '')) ?: null,
            'gpx' => $gpxPath,
            'stato' => $status,
            'nota' => $note,
            'checked' => $checkedAt,
            'next' => $nextCheck,
            'pubblicato' => isset($_POST['pubblicato']) ? 1 : 0,
            'ordine' => (int) ($_POST['ordine'] ?? 0),
            'admin' => admin_id(),
        ];

        $oldGpx = $id > 0 ? (string) $trail['gpx_file'] : null;
        $oldStatus = $id > 0 ? (string) $trail['stato'] : null;
        $oldChecked = $id > 0 ? (string) ($trail['ultima_verifica_at'] ?? '') : null;
        $oldNote = $id > 0 ? (string) ($trail['nota_pubblica'] ?? '') : null;

        $pdo->beginTransaction();
        if ($id > 0) {
            $data['id'] = $id;
            $pdo->prepare(
                'UPDATE sentieri SET nome=:nome,codice=:codice,slug=:slug,localita=:localita,descrizione=:descrizione,gpx_file=:gpx,stato=:stato,nota_pubblica=:nota,ultima_verifica_at=:checked,prossima_verifica_at=:next,pubblicato=:pubblicato,ordine=:ordine,updated_by=:admin WHERE id=:id'
            )->execute($data);
            $trailId = $id;
        } else {
            $insertData = $data;
            unset($insertData['admin']);
            $insertData['admin_created'] = admin_id();
            $insertData['admin_updated'] = admin_id();
            $pdo->prepare(
                'INSERT INTO sentieri (nome,codice,slug,localita,descrizione,gpx_file,stato,nota_pubblica,ultima_verifica_at,prossima_verifica_at,pubblicato,ordine,created_by,updated_by) VALUES (:nome,:codice,:slug,:localita,:descrizione,:gpx,:stato,:nota,:checked,:next,:pubblicato,:ordine,:admin_created,:admin_updated)'
            )->execute($insertData);
            $trailId = (int) $pdo->lastInsertId();
        }

        $verificationChanged = $checkedAt !== null && (
            $id === 0 || $oldStatus !== $status || $oldChecked !== $checkedAt || $oldNote !== (string) ($note ?? '')
        );
        if ($verificationChanged) {
            $pdo->prepare('INSERT INTO sentieri_verifiche (sentiero_id,stato,nota,verificato_at,created_by) VALUES (:sentiero,:stato,:nota,:checked,:admin)')->execute([
                'sentiero' => $trailId, 'stato' => $status, 'nota' => $note, 'checked' => $checkedAt, 'admin' => admin_id(),
            ]);
        }
        $pdo->commit();

        if ($newGpx !== null && $oldGpx) {
            sentieri_delete_gpx($oldGpx);
        }
        $_SESSION['sentieri_flash'] = $id > 0 ? 'Sentiero aggiornato.' : 'Sentiero creato.';
        header('Location: sentieri.php');
        exit;
    }
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($newGpx !== null) {
        sentieri_delete_gpx($newGpx);
    }
    $error = $exception->getMessage();
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $trail = array_merge($trail, $_POST);
        $trail['pubblicato'] = isset($_POST['pubblicato']) ? 1 : 0;
    }
}

$stats = gpx_stats((string) ($trail['gpx_file'] ?? ''), 'piedi');
admin_page_open($id > 0 ? 'Modifica sentiero' : 'Nuovo sentiero', 'sentieri');
?>
<main class="wrap">
    <section class="hero-admin">
        <h1><?= $id > 0 ? 'Modifica sentiero' : 'Nuovo sentiero' ?></h1>
        <p>Il sentiero è indipendente dagli itinerari turistici e viene pubblicato nella pagina “Stato dei sentieri”.</p>
    </section>
    <?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" enctype="multipart/form-data" class="box grid">
        <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="id" value="<?= (int) $id ?>">
        <label>Nome del sentiero<input name="nome" maxlength="190" required value="<?= e($trail['nome']) ?>"></label>
        <label>Codice / numero<input name="codice" maxlength="80" value="<?= e($trail['codice']) ?>" placeholder="Es. CAI 165"></label>
        <label>Slug<input name="slug" maxlength="220" value="<?= e($trail['slug']) ?>" placeholder="Generato automaticamente"></label>
        <label>Località / zona<input name="localita" maxlength="190" value="<?= e($trail['localita']) ?>"></label>
        <label class="full">Descrizione pubblica<textarea name="descrizione"><?= e($trail['descrizione']) ?></textarea></label>
        <label class="full">File GPX<?= $id === 0 ? ' (obbligatorio)' : '' ?><input type="file" name="gpx_file" accept=".gpx,application/gpx+xml" <?= $id === 0 ? 'required' : '' ?>>
            <small>Massimo 15 MB. Deve contenere almeno due punti traccia.</small>
            <?php if (!empty($trail['gpx_file'])): ?><br><a href="../gpx/<?= rawurlencode(basename((string) $trail['gpx_file'])) ?>?download=1">Scarica il GPX attuale</a> · <?= e($stats['length_label']) ?> · +<?= (int) ($stats['ascent_m'] ?? 0) ?> m<?php endif; ?>
        </label>
        <label>Stato<select name="stato"><?php foreach (sentieri_statuses() as $value => $label): ?><option value="<?= e($value) ?>" <?= (string) $trail['stato'] === $value ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach; ?></select></label>
        <label>Ultima verifica<input type="datetime-local" name="ultima_verifica_at" value="<?= !empty($trail['ultima_verifica_at']) ? e(date('Y-m-d\TH:i', strtotime((string) $trail['ultima_verifica_at']))) : '' ?>"></label>
        <label>Prossimo controllo<input type="date" name="prossima_verifica_at" value="<?= e($trail['prossima_verifica_at']) ?>"></label>
        <label>Ordine<input type="number" name="ordine" value="<?= (int) $trail['ordine'] ?>"></label>
        <label class="full">Nota sullo stato visibile al pubblico<textarea name="nota_pubblica"><?= e($trail['nota_pubblica']) ?></textarea></label>
        <label><input type="checkbox" name="pubblicato" value="1" <?= !empty($trail['pubblicato']) ? 'checked' : '' ?>> Pubblica il sentiero</label>
        <div class="full actions"><button class="btn" type="submit">Salva sentiero</button><a class="btn secondary" href="sentieri.php">Annulla</a></div>
    </form>
</main>
<?php admin_page_close(); ?>
