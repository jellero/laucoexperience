<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/content-ai.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $action = (string) ($_POST['action'] ?? '');
        content_ai_review($pdo, $id, $action, admin_id());

        $success = $action === 'apply'
            ? 'Bozza applicata correttamente.'
            : ($action === 'approve' ? 'Bozza approvata.' : 'Bozza rifiutata.');
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

try {
    $draft = content_ai_find_draft($pdo, $id);
} catch (Throwable $e) {
    http_response_code(404);
    exit(e($e->getMessage()));
}

$data = $draft['generated'];
$fields = [
    'title' => 'Titolo',
    'subtitle' => 'Sottotitolo',
    'excerpt' => 'Excerpt',
    'description' => 'Descrizione',
    'seo_title' => 'Titolo SEO',
    'seo_description' => 'Meta description',
    'card_text' => 'Testo card',
];

admin_page_open('Revisione bozza AI', 'percorsi');
?>

<main class="wrap">
    <div class="page-title">
        <h1>Revisione bozza AI</h1>
        <p><?= e($draft['entity_title'] ?: 'Percorso') ?> · lingua <?= e(strtoupper((string) $draft['target_language'])) ?>. Nessun testo viene pubblicato senza azione esplicita.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if ($success !== ''): ?>
        <div class="success"><?= e($success) ?></div>
    <?php endif; ?>

    <div class="actions">
        <a class="btn secondary" href="percorso-ai-demo.php?id=<?= (int) $draft['entity_id'] ?>">Torna alla generazione</a>
        <a class="btn" href="percorso-form.php?id=<?= (int) $draft['entity_id'] ?>">Apri editor percorso</a>

        <?php if (!empty($draft['entity_slug'])): ?>
            <a class="btn secondary" href="../percorso.php?slug=<?= urlencode((string) $draft['entity_slug']) ?>&lang=<?= urlencode((string) $draft['target_language']) ?>" target="_blank">Anteprima lingua</a>
        <?php endif; ?>
    </div>

    <section class="grid">
        <div class="box">
            <strong>Stato</strong>
            <p><?= e($draft['status']) ?></p>
        </div>
        <div class="box">
            <strong>Lingua</strong>
            <p><?= e(strtoupper((string) $draft['target_language'])) ?></p>
        </div>
        <div class="box">
            <strong>Modalità</strong>
            <p><?= e($draft['mode']) ?></p>
        </div>
        <div class="box">
            <strong>Modello</strong>
            <p><?= e($draft['model'] ?: '-') ?></p>
        </div>
    </section>

    <div class="actions"></div>

    <section class="grid">
        <div class="box">
            <h2>Output generato</h2>

            <?php foreach ($fields as $key => $label): ?>
                <h3><?= e($label) ?></h3>
                <p><?= nl2br(e($data[$key] ?? '')) ?></p>
            <?php endforeach; ?>
        </div>

        <div class="box">
            <h2>Avvisi del modello</h2>

            <?php if (empty($data['warnings'])): ?>
                <p>Nessun avviso.</p>
            <?php else: ?>
                <ul>
                    <?php foreach ($data['warnings'] as $warning): ?>
                        <li><?= e($warning) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <h2>Azioni editoriali</h2>
            <p>Per l’italiano, “Applica” aggiorna soltanto sottotitolo, excerpt e descrizione del percorso esistente. Per le altre lingue pubblica la traduzione separata, lasciando intatti i contenuti italiani.</p>

            <?php if (in_array($draft['status'], ['review', 'approved'], true)): ?>
                <form method="post">
                    <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="id" value="<?= $id ?>">

                    <div class="actions">
                        <?php if ($draft['status'] === 'review'): ?>
                            <button class="btn secondary" name="action" value="approve" type="submit">Approva senza applicare</button>
                        <?php endif; ?>

                        <button class="btn" name="action" value="apply" type="submit" onclick="return confirm('Applicare questa bozza?');">Approva e applica</button>
                        <button class="btn danger" name="action" value="reject" type="submit">Rifiuta</button>
                    </div>
                </form>
            <?php else: ?>
                <p class="hint">Questa bozza è nello stato <strong><?= e($draft['status']) ?></strong> e non ammette altre azioni editoriali.</p>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php admin_page_close(); ?>
