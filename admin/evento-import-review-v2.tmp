<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/event-import.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$error = '';
$success = '';
$eventId = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $eventId = event_import_review(
            $pdo,
            $id,
            (string) ($_POST['action'] ?? ''),
            admin_id()
        );

        $success = $eventId
            ? 'Evento creato come bozza nel gestionale. Controllare e completare i campi prima della pubblicazione.'
            : 'Candidato rifiutato.';
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

try {
    $candidate = event_import_candidate($pdo, $id);
} catch (Throwable $e) {
    http_response_code(404);
    exit(e($e->getMessage()));
}

admin_page_open('Revisione evento importato', 'eventi');
?>

<main class="wrap">
    <div class="page-title">
        <h1>Revisione evento importato</h1>
        <p>Confronta i dati con la pagina ufficiale. L’approvazione crea un normale evento in bozza, modificabile con tutte le funzioni già presenti.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="success"><?= e($success) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a class="btn secondary" href="eventi-importa.php">Torna alla coda</a>

        <?php if ($eventId): ?>
            <a class="btn" href="evento-form.php?id=<?= (int) $eventId ?>">Apri evento nel gestionale</a>
        <?php endif; ?>

        <a class="btn secondary" href="<?= e($candidate['source_url']) ?>" target="_blank" rel="noopener">Apri fonte ufficiale</a>
    </div>

    <section class="grid">
        <div class="box">
            <h2><?= e($candidate['title']) ?></h2>

            <?php if (!empty($candidate['image_url'])): ?>
                <img src="<?= e($candidate['image_url']) ?>" alt="<?= e($candidate['title']) ?>" style="display:block;width:100%;max-height:420px;object-fit:cover;margin:0 0 22px;border-radius:5px">
            <?php endif; ?>

            <p><strong>Data originale:</strong> <?= e($candidate['start_at_raw'] ?: '-') ?></p>
            <p><strong>Località:</strong> <?= e($candidate['locality'] ?: $candidate['location_name']) ?></p>
            <p><strong>Organizzatore:</strong> <?= e($candidate['organizer'] ?: '-') ?></p>

            <h3>Descrizione</h3>
            <p><?= nl2br(e($candidate['description'])) ?></p>
        </div>

        <div class="box">
            <h2>Controlli</h2>
            <p>Verificare data, luogo, organizzatore, eventuali orari, immagine e completezza della descrizione sulla pagina ufficiale.</p>
            <?php if (!empty($candidate['image_url'])): ?>
                <p><strong>Foto scelta:</strong> <a href="<?= e($candidate['image_url']) ?>" target="_blank" rel="noopener">apri immagine originale</a></p>
            <?php else: ?>
                <p class="hint">Nessuna immagine affidabile selezionata automaticamente.</p>
            <?php endif; ?>
            <p><strong>Stato:</strong> <?= e($candidate['review_status']) ?></p>

            <?php if ($candidate['review_status'] === 'pending' && empty($candidate['published_event_id'])): ?>
                <form method="post">
                    <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <div class="actions">
                        <button class="btn" type="submit" name="action" value="approve" onclick="return confirm('Creare l’evento come bozza?');">Approva e crea bozza</button>
                        <button class="btn danger" type="submit" name="action" value="reject">Rifiuta</button>
                    </div>
                </form>
            <?php elseif (!empty($candidate['published_event_id'])): ?>
                <div class="actions">
                    <a class="btn" href="evento-form.php?id=<?= (int) $candidate['published_event_id'] ?>">Apri evento creato</a>
                </div>
            <?php else: ?>
                <p class="hint">Il candidato è stato rifiutato e non può essere trasformato in evento.</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php admin_page_close(); ?>
