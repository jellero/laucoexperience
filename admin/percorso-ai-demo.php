<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/gpx-stats.php';
require_once __DIR__ . '/../inc/content-ai.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

function ai_value_is_set($value): bool
{
    return $value !== null && trim((string) $value) !== '';
}

function ai_clean($value): string
{
    return trim(strip_tags((string) $value));
}

function ai_line(string $label, $value): string
{
    $value = ai_clean($value);

    if ($value === '') {
        $value = '-';
    }

    return $label . ': ' . $value;
}

function ai_bool_label($value): string
{
    return !empty($value) ? 'sì' : 'no';
}

function ai_route_stats(array $percorso): array
{
    $stats = gpx_stats($percorso['gpx_file'] ?? null, $percorso['tipo'] ?? 'piedi');

    $distance = ai_value_is_set($percorso['distanza_km'] ?? null)
        ? fmt_it($percorso['distanza_km'], ' km', 2)
        : ($stats['length_label'] ?? '-');

    $ascent = ai_value_is_set($percorso['dislivello_m'] ?? null)
        ? fmt_it($percorso['dislivello_m'], ' m', 0)
        : ($stats['ascent_label'] ?? '-');

    $duration = ai_value_is_set($percorso['tempo'] ?? null)
        ? trim((string) $percorso['tempo'])
        : (
            ai_value_is_set($percorso['durata'] ?? null)
                ? trim((string) $percorso['durata'])
                : ($stats['duration_label'] ?? '-')
        );

    $difficulty = ai_value_is_set($percorso['difficolta'] ?? null)
        ? trim((string) $percorso['difficolta'])
        : ($stats['difficulty'] ?? '-');

    return [
        'distanza' => $distance,
        'dislivello' => $ascent,
        'tempo' => $duration,
        'difficolta' => $difficulty,
        'aggiornamento_gpx' => $stats['updated_label'] ?? '-',
        'fonte_distanza' => ai_value_is_set($percorso['distanza_km'] ?? null) ? 'database' : 'gpx',
        'fonte_dislivello' => ai_value_is_set($percorso['dislivello_m'] ?? null) ? 'database' : 'gpx',
        'fonte_tempo' => (ai_value_is_set($percorso['tempo'] ?? null) || ai_value_is_set($percorso['durata'] ?? null)) ? 'database' : 'gpx',
        'fonte_difficolta' => ai_value_is_set($percorso['difficolta'] ?? null) ? 'database' : 'gpx',
    ];
}

function ai_gallery_count(PDO $pdo, int $percorsoId): int
{
    try {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM percorso_gallery WHERE percorso_id = :id');
        $stmt->execute(['id' => $percorsoId]);
        return (int) $stmt->fetchColumn();
    } catch (Throwable $e) {
        return 0;
    }
}

function ai_context_text(array $percorso, array $stats, int $galleryCount): string
{
    $tipo = ($percorso['tipo'] ?? '') === 'mtb' ? 'MTB' : 'trekking / a piedi';

    $lines = [
        'CONTESTO PERCORSO',
        ai_line('ID', $percorso['id'] ?? ''),
        ai_line('Titolo', $percorso['titolo'] ?? ''),
        ai_line('Slug', $percorso['slug'] ?? ''),
        ai_line('Tipo', $tipo),
        ai_line('Località', $percorso['localita'] ?? ''),
        ai_line('Sottotitolo', $percorso['sottotitolo'] ?? ''),
        ai_line('Percorso pubblicato', ai_bool_label($percorso['pubblicato'] ?? 0)),
        ai_line('Consigliato', ai_bool_label($percorso['consigliato'] ?? 0)),
        ai_line('Speciale', ai_bool_label($percorso['speciale'] ?? 0)),
        '',
        'DATI TECNICI DEFINITIVI DA USARE',
        ai_line('Distanza', $stats['distanza'] . ' [fonte: ' . $stats['fonte_distanza'] . ']'),
        ai_line('Dislivello', $stats['dislivello'] . ' [fonte: ' . $stats['fonte_dislivello'] . ']'),
        ai_line('Tempo di percorrenza', $stats['tempo'] . ' [fonte: ' . $stats['fonte_tempo'] . ']'),
        ai_line('Difficoltà', $stats['difficolta'] . ' [fonte: ' . $stats['fonte_difficolta'] . ']'),
        ai_line('Aggiornamento GPX', $stats['aggiornamento_gpx']),
        ai_line('File GPX', $percorso['gpx_file'] ?? ''),
        ai_line('Immagine copertina', $percorso['cover_image'] ?? ''),
        ai_line('Numero immagini gallery', $galleryCount),
        '',
        'TESTI ESISTENTI',
        ai_line('Descrizione breve / excerpt', $percorso['excerpt'] ?? ''),
        ai_line('Descrizione completa attuale', $percorso['descrizione'] ?? ''),
    ];

    return implode("\n", $lines);
}

function ai_prompt_text(array $percorso, array $stats, int $galleryCount): string
{
    $context = ai_context_text($percorso, $stats, $galleryCount);

    return "Agisci come copywriter turistico e tecnico outdoor per il sito Lauco Experience.\n"
        . "Devi generare testi descrittivi chiari, utili e realistici per una scheda percorso.\n\n"
        . "REGOLE IMPORTANTI:\n"
        . "- Scrivi in italiano.\n"
        . "- Non inventare punti panoramici, rifugi, fontane, parcheggi, pericoli o servizi se non sono presenti nei dati.\n"
        . "- Usa solo i dati forniti sotto.\n"
        . "- Mantieni tono professionale, naturale, turistico, non esagerato.\n"
        . "- Se un dato tecnico ha fonte database, consideralo più affidabile del GPX.\n"
        . "- Se mancano informazioni, resta generico senza fingere precisione.\n\n"
        . "OUTPUT RICHIESTO:\n"
        . "1. Titolo SEO massimo 70 caratteri.\n"
        . "2. Sottotitolo breve massimo 130 caratteri.\n"
        . "3. Excerpt massimo 350 caratteri.\n"
        . "4. Descrizione completa in 3-5 paragrafi.\n"
        . "5. Box info pratiche con difficoltà, distanza, dislivello e tempo.\n"
        . "6. Meta description massimo 155 caratteri.\n"
        . "7. Testo breve per card homepage massimo 180 caratteri.\n\n"
        . $context;
}

function ai_local_draft(array $percorso, array $stats): string
{
    $titolo = ai_clean($percorso['titolo'] ?? 'Percorso');
    $localita = ai_clean($percorso['localita'] ?? '');
    $tipo = ($percorso['tipo'] ?? '') === 'mtb' ? 'itinerario MTB' : 'itinerario a piedi';
    $difficulty = ai_clean($stats['difficolta']);
    $distance = ai_clean($stats['distanza']);
    $ascent = ai_clean($stats['dislivello']);
    $duration = ai_clean($stats['tempo']);
    $excerpt = ai_clean($percorso['excerpt'] ?? '');

    $intro = $titolo . ' è un ' . $tipo;
    if ($localita !== '') {
        $intro .= ' nell’area di ' . $localita;
    }
    $intro .= '.';

    $tech = 'Il percorso presenta una lunghezza di ' . $distance . ', un dislivello di ' . $ascent . ', un tempo indicativo di percorrenza di ' . $duration . ' e difficoltà ' . $difficulty . '.';

    $body = $excerpt !== ''
        ? $excerpt
        : 'La scheda può essere completata con una descrizione più dettagliata dell’ambiente attraversato, dei punti di interesse e delle indicazioni utili per affrontare il percorso.';

    return $intro . "\n\n" . $tech . "\n\n" . $body . "\n\n" . 'Bozza locale generata senza AI: usare il prompt completo per ottenere una descrizione più curata.';
}

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$format = $_GET['format'] ?? 'html';

if (!$id) {
    http_response_code(404);
    exit('Percorso non trovato.');
}

$stmt = $pdo->prepare('SELECT * FROM percorsi WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$percorso = $stmt->fetch();

if (!$percorso) {
    http_response_code(404);
    exit('Percorso non trovato.');
}

$stats = ai_route_stats($percorso);
$galleryCount = ai_gallery_count($pdo, (int) $percorso['id']);

if ($format === 'txt') {
    header('Content-Type: text/plain; charset=utf-8');
    echo ai_prompt_text($percorso, $stats, $galleryCount);
    exit;
}

if ($format === 'json') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'percorso' => $percorso,
        'dati_tecnici' => $stats,
        'gallery_count' => $galleryCount,
        'prompt' => ai_prompt_text($percorso, $stats, $galleryCount),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    try {
        $result = content_ai_generate_route_bundle(
            $pdo,
            $id,
            trim((string) ($_POST['mode'] ?? 'full')),
            admin_id()
        );
        header('Location: percorso-ai-preview.php?batch=' . (int) $result['batch_id']);
        exit;
    } catch (Throwable $e) {
        $error = $e->getMessage();
    }
}

$context = ai_context_text($percorso, $stats, $galleryCount);
$prompt = ai_prompt_text($percorso, $stats, $galleryCount);
$draft = ai_local_draft($percorso, $stats);
$baseUrl = 'percorso-ai-demo.php?id=' . urlencode((string) $percorso['id']);
$drafts = content_ai_route_drafts($pdo, $id);
$openAiConfigured = trim((string) lauco_env('OPENAI_API_KEY', '')) !== ''
    && trim((string) lauco_env('OPENAI_MODEL', '')) !== '';

admin_page_open('AI descrizione percorso', 'percorsi');
?>

<style>
    .ai-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 22px;
        align-items: start;
    }

    .ai-box {
        background: #fff;
        box-shadow: var(--admin-shadow);
        padding: 24px;
        min-width: 0;
    }

    .ai-box h2 {
        margin-top: 0;
    }

    .ai-field {
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid var(--admin-border);
    }

    .ai-field strong {
        display: block;
        margin-bottom: 4px;
    }

    .ai-textarea {
        width: 100%;
        min-height: 420px;
        padding: 14px;
        border: 1px solid var(--admin-border);
        font-family: Consolas, Monaco, monospace;
        font-size: 13px;
        line-height: 1.45;
        white-space: pre-wrap;
    }

    .ai-output {
        white-space: pre-wrap;
        line-height: 1.55;
        background: #fafafa;
        padding: 16px;
        border: 1px solid var(--admin-border);
    }

    .ai-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        margin-bottom: 18px;
    }

    .ai-note {
        background: #fff3cd;
        color: #664d03;
        padding: 14px 16px;
        margin-bottom: 18px;
    }

    @media (max-width: 900px) {
        .ai-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<main class="wrap">
    <div class="page-title">
        <h1>AI descrizione percorso</h1>
        <p>Raccoglie i dati del percorso, conserva gli strumenti di verifica esistenti e permette di generare bozze revisionabili senza pubblicazione automatica.</p>
    </div>

    <?php if ($error !== ''): ?>
        <div class="error"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="ai-actions">
        <a class="btn secondary" href="percorsi.php">Torna ai percorsi</a>
        <a class="btn" href="percorso-form.php?id=<?= (int) $percorso['id'] ?>">Modifica percorso</a>
        <a class="btn secondary" href="../percorso.php?slug=<?= urlencode($percorso['slug']) ?>" target="_blank">Vedi pubblico</a>
        <a class="btn secondary" href="<?= e($baseUrl) ?>&format=txt" target="_blank">URI prompt TXT</a>
        <a class="btn secondary" href="<?= e($baseUrl) ?>&format=json" target="_blank">URI dati JSON</a>
    </div>

    <div class="ai-note">
        Il prompt copiabile, l’esportazione TXT/JSON e la bozza locale restano disponibili. La chiamata OpenAI salva sempre una bozza separata, da revisionare prima di applicarla.
    </div>

    <section class="ai-box" style="margin-bottom:22px;">
        <h2>Genera con OpenAI</h2>
        <?php if (!$openAiConfigured): ?>
            <p class="hint">Configurare <strong>OPENAI_API_KEY</strong> e <strong>OPENAI_MODEL</strong> nel file <code>.env</code>.</p>
        <?php endif; ?>
        <form method="post">
            <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int) $percorso['id'] ?>">
            <div class="grid">
                <div>
                    <label>Lingue generate insieme</label>
                    <p class="hint">Italiano, English, Deutsch e Slovenščina. La preview permette di confrontarle prima di applicarle.</p>
                </div>
                <div>
                    <label for="mode">Operazione</label>
                    <select id="mode" name="mode">
                        <option value="full">Traduzione + descrizione + SEO</option>
                        <option value="translate">Traduzione controllata</option>
                        <option value="editorial">Testo editoriale</option>
                        <option value="seo">SEO</option>
                    </select>
                </div>
            </div>
            <div class="ai-actions" style="margin-top:18px;margin-bottom:0;">
                <button class="btn" type="submit"<?= !$openAiConfigured ? ' disabled' : '' ?>>Genera preview in tutte le lingue</button>
            </div>
        </form>
    </section>

    <section class="ai-grid">
        <div class="ai-box">
            <h2>Dati raccolti</h2>

            <div class="ai-field">
                <strong>Titolo</strong>
                <?= e($percorso['titolo']) ?>
            </div>

            <div class="ai-field">
                <strong>Tipo / località</strong>
                <?= e(strtoupper($percorso['tipo'])) ?><?= !empty($percorso['localita']) ? ' · ' . e($percorso['localita']) : '' ?>
            </div>

            <div class="ai-field">
                <strong>Dati tecnici usati</strong>
                Distanza: <?= e($stats['distanza']) ?> <small>(<?= e($stats['fonte_distanza']) ?>)</small><br>
                Dislivello: <?= e($stats['dislivello']) ?> <small>(<?= e($stats['fonte_dislivello']) ?>)</small><br>
                Tempo: <?= e($stats['tempo']) ?> <small>(<?= e($stats['fonte_tempo']) ?>)</small><br>
                Difficoltà: <?= e($stats['difficolta']) ?> <small>(<?= e($stats['fonte_difficolta']) ?>)</small>
            </div>

            <div class="ai-field">
                <strong>Flag</strong>
                Pubblicato: <?= e(ai_bool_label($percorso['pubblicato'] ?? 0)) ?><br>
                Consigliato: <?= e(ai_bool_label($percorso['consigliato'] ?? 0)) ?><br>
                Speciale: <?= e(ai_bool_label($percorso['speciale'] ?? 0)) ?>
            </div>

            <div class="ai-field">
                <strong>Contesto testuale</strong>
                <div class="ai-output"><?= e($context) ?></div>
            </div>
        </div>

        <div class="ai-box">
            <h2>Prompt pronto</h2>
            <div class="ai-actions">
                <button class="btn" type="button" onclick="copyAiPrompt()">Copia prompt</button>
            </div>

            <textarea id="aiPrompt" class="ai-textarea"><?= e($prompt) ?></textarea>
        </div>
    </section>

    <section class="ai-box" style="margin-top:22px;">
        <h2>Bozza locale di test</h2>
        <p>Questa non è AI: è solo una bozza generata dal sito per verificare che i dati arrivino correttamente.</p>
        <div class="ai-output"><?= e($draft) ?></div>
    </section>

    <section class="ai-box" style="margin-top:22px;">
        <h2>Bozze OpenAI precedenti</h2>
        <?php if (!$drafts): ?>
            <p class="hint">Nessuna bozza disponibile. Applicare la migrazione SQL prima di usare la generazione.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>Data</th><th>Lingua</th><th>Modalità</th><th>Modello</th><th>Stato</th><th>Azioni</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($drafts as $savedDraft): ?>
                        <tr>
                            <td><?= e($savedDraft['created_at']) ?></td>
                            <td><?= e(strtoupper((string) $savedDraft['target_language'])) ?></td>
                            <td><?= e($savedDraft['mode']) ?></td>
                            <td><?= e($savedDraft['model'] ?: '-') ?></td>
                            <td class="status"><?= e($savedDraft['status']) ?></td>
                            <td><a class="btn" href="percorso-ai-review.php?id=<?= (int) $savedDraft['id'] ?>">Revisiona</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>

<script>
    function copyAiPrompt() {
        const el = document.getElementById('aiPrompt');
        el.focus();
        el.select();
        document.execCommand('copy');
        alert('Prompt copiato.');
    }
</script>

<?php admin_page_close(); ?>
