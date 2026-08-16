<?php
declare(strict_types=1);

$facebookEntityType = in_array($facebookEntityType ?? '', ['evento', 'percorso'], true) ? $facebookEntityType : 'percorso';
$facebookEntityId = max(0, (int) ($facebookEntityId ?? 0));
$facebookEntity = is_array($facebookEntity ?? null) ? $facebookEntity : [];
$facebookReady = facebook_publishing_ready();
$facebookMissing = facebook_publishing_missing_config();
$facebookLatest = facebook_publishing_latest($pdo, $facebookEntityType, $facebookEntityId);
$facebookDefaultMessage = facebook_publishing_default_message($facebookEntityType, $facebookEntity);
$facebookMessageValue = array_key_exists('facebook_message', $_POST)
    ? trim((string) $_POST['facebook_message'])
    : $facebookDefaultMessage;
$facebookFeedback = trim((string) ($_GET['facebook'] ?? ''));
$facebookPostUrl = facebook_publishing_post_url($facebookLatest['facebook_post_id'] ?? null);
$facebookLatestStatus = (string) ($facebookLatest['status'] ?? '');
$facebookStatusLabels = [
    'published' => 'Pubblicato',
    'failed' => 'Errore',
    'pending' => 'In elaborazione',
];
?>

<div class="full">
    <section class="facebook-publish-panel" data-facebook-panel data-entity-type="<?= e($facebookEntityType) ?>">
        <div class="facebook-publish-head">
            <div>
                <h3>Pubblica su Facebook</h3>
                <p>Il post conterrà questo testo e il collegamento alla scheda pubblica, con la relativa immagine di copertina.</p>
            </div>
            <span class="facebook-config-badge <?= $facebookReady ? 'ready' : 'missing' ?>">
                <?= $facebookReady ? 'Configurazione pronta' : 'Configurazione incompleta' ?>
            </span>
        </div>

        <?php if (!$facebookReady): ?>
            <div class="facebook-config-warning">
                Nel file <code>.env</code> manca: <?= e(implode(', ', $facebookMissing)) ?>.
            </div>
        <?php endif; ?>

        <?php if ($facebookFeedback !== ''): ?>
            <?php if ($facebookFeedback === 'published'): ?>
                <div class="success">Contenuto salvato e pubblicato correttamente su Facebook.</div>
            <?php elseif ($facebookFeedback === 'duplicate'): ?>
                <div class="notice">Contenuto salvato. Il post identico era già presente su Facebook, quindi non è stato duplicato.</div>
            <?php elseif ($facebookFeedback === 'unpublished'): ?>
                <div class="error">Contenuto salvato, ma non inviato a Facebook perché la scheda non è pubblicata sul sito.</div>
            <?php elseif ($facebookFeedback === 'unconfigured'): ?>
                <div class="error">Contenuto salvato, ma la configurazione Facebook non è completa.</div>
            <?php elseif ($facebookFeedback === 'failed'): ?>
                <div class="error">Contenuto salvato, ma l’invio Facebook non è riuscito.<?= !empty($facebookLatest['error_message']) ? ' ' . e($facebookLatest['error_message']) : '' ?></div>
            <?php endif; ?>
        <?php endif; ?>

        <label for="facebook_message">Testo del post</label>
        <textarea id="facebook_message" name="facebook_message" maxlength="5000" data-facebook-message><?= e($facebookMessageValue) ?></textarea>
        <div class="facebook-message-actions">
            <button class="btn secondary" type="button" data-facebook-generate>Rigenera dal contenuto</button>
            <span class="hint">Il link viene aggiunto automaticamente e non serve incollarlo nel testo.</span>
        </div>

        <button class="btn facebook-publish-button" type="submit" name="facebook_publish" value="1" <?= !$facebookReady ? 'disabled' : '' ?>>
            Salva e pubblica su Facebook
        </button>
        <p class="hint facebook-publish-hint">Il pulsante salva prima la scheda e poi crea il post. Un errore Facebook non annulla il salvataggio sul sito.</p>

        <?php if (is_array($facebookLatest)): ?>
            <div class="facebook-last-publication">
                <strong>Ultimo invio:</strong>
                <span class="facebook-status <?= e($facebookLatestStatus) ?>"><?= e($facebookStatusLabels[$facebookLatestStatus] ?? ucfirst($facebookLatestStatus)) ?></span>
                · <?= e(date('d/m/Y H:i', strtotime((string) ($facebookLatest['updated_at'] ?: $facebookLatest['created_at'])))) ?>
                · <?= (int) ($facebookLatest['attempts'] ?? 1) ?> tentativ<?= (int) ($facebookLatest['attempts'] ?? 1) === 1 ? 'o' : 'i' ?>
                <?php if ($facebookPostUrl): ?>
                    · <a href="<?= e($facebookPostUrl) ?>" target="_blank" rel="noopener noreferrer">Apri post</a>
                <?php endif; ?>
                <?php if ($facebookLatestStatus === 'failed' && !empty($facebookLatest['error_message'])): ?>
                    <div class="facebook-last-error"><?= e($facebookLatest['error_message']) ?></div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<style>
.facebook-publish-panel{margin-top:8px;padding:24px;background:#f8f9fb;border:1px solid #dde2e8}.facebook-publish-head{display:flex;justify-content:space-between;align-items:flex-start;gap:18px;margin-bottom:18px}.facebook-publish-head h3{margin:0 0 7px}.facebook-publish-head p{margin:0;color:#666;line-height:1.5}.facebook-config-badge,.facebook-status{display:inline-block;padding:6px 8px;font-size:11px;font-weight:700;white-space:nowrap}.facebook-config-badge.ready,.facebook-status.published{background:#d1e7dd;color:#0f5132}.facebook-config-badge.missing,.facebook-status.failed{background:#f8d7da;color:#842029}.facebook-status.pending{background:#fff3cd;color:#664d03}.facebook-config-warning,.facebook-publish-panel .notice{margin-bottom:17px;padding:12px;background:#fff3cd;color:#664d03}.facebook-publish-panel textarea{min-height:160px}.facebook-message-actions{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin:9px 0 18px}.facebook-message-actions .btn{padding:8px 11px}.facebook-publish-button{background:#1877f2;border-color:#1877f2}.facebook-publish-button:hover{background:#0f65d7}.facebook-publish-button:disabled{cursor:not-allowed;opacity:.45}.facebook-publish-hint{margin-top:8px}.facebook-last-publication{margin-top:18px;padding-top:16px;border-top:1px solid #dfe3e7;color:#555;line-height:1.6}.facebook-last-error{margin-top:8px;color:#842029}.facebook-publish-panel code{font-size:12px}@media(max-width:700px){.facebook-publish-head{flex-direction:column}.facebook-config-badge{white-space:normal}}
</style>

<script>
(function () {
    'use strict';
    var panel = document.querySelector('[data-facebook-panel]');
    if (!panel) return;
    var message = panel.querySelector('[data-facebook-message]');
    var generate = panel.querySelector('[data-facebook-generate]');
    var kind = panel.getAttribute('data-entity-type');

    function value(id) {
        var field = document.getElementById(id);
        return field ? String(field.value || '').trim() : '';
    }

    function clean(text) {
        return String(text || '').replace(/\s+/g, ' ').trim();
    }

    function eventDate(raw) {
        var parts = raw.split('-');
        return parts.length === 3 ? parts[2] + '/' + parts[1] + '/' + parts[0] : raw;
    }

    function generatedMessage() {
        var title = clean(value('titolo'));
        if (!title) return '';
        var lines = [];
        var location = clean(value('localita'));
        if (kind === 'evento') {
            lines.push(title);
            var details = [];
            var date = value('data_evento');
            if (date) details.push(eventDate(date));
            if (location) details.push(location);
            if (details.length) lines.push(details.join(' · '));
        } else {
            lines.push((value('tipo') === 'mtb' ? 'Itinerario MTB: ' : 'Itinerario a piedi: ') + title);
            if (location) lines.push(location);
        }
        var excerpt = clean(value('excerpt'));
        if (excerpt) lines.push('', excerpt);
        lines.push('', 'Scopri tutti i dettagli su Lauco Experience.');
        return lines.join('\n');
    }

    generate.addEventListener('click', function () {
        message.value = generatedMessage();
        message.focus();
    });
    if (!message.value.trim()) {
        message.value = generatedMessage();
    }
})();
</script>
