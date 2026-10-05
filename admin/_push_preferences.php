<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/admin-push-preferences.php';

$adminPushPdo = $GLOBALS['pdo'] ?? null;
$adminPushCategories = admin_push_allowed_categories(admin_role());
$adminPushPreferences = $adminPushPdo instanceof PDO
    ? admin_push_preferences($adminPushPdo, admin_id(), admin_role())
    : array_fill_keys(array_keys($adminPushCategories), true);
?>

<style>
    .admin-push-preferences {
        background:#fff;
        box-shadow:var(--admin-shadow);
        padding:20px 22px;
        margin:0 0 24px;
    }
    .admin-push-preferences-head {
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:16px;
        margin-bottom:14px;
    }
    .admin-push-preferences h2 { margin:0 0 5px; font-size:18px; }
    .admin-push-preferences p { margin:0; color:#707070; font-size:13px; line-height:1.45; }
    .admin-push-preferences-status { font-size:12px; color:#707070; white-space:nowrap; }
    .admin-push-preferences-status.ok { color:#0f7b32; }
    .admin-push-preferences-status.error { color:#b00020; }
    .admin-push-pref-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:10px;
    }
    .admin-push-pref {
        display:flex;
        gap:10px;
        align-items:flex-start;
        padding:12px 14px;
        background:#f7f7f7;
        border:1px solid #ececec;
        cursor:pointer;
        margin:0;
    }
    .admin-push-pref input { margin-top:2px; flex:0 0 auto; }
    .admin-push-pref span { display:block; min-width:0; }
    .admin-push-pref strong { display:block; font-size:13px; margin-bottom:3px; }
    .admin-push-pref small { display:block; color:#777; line-height:1.35; font-weight:400; }
    .admin-push-pref-all { grid-column:1 / -1; background:#efefef; }
    @media(max-width:780px) {
        .admin-push-preferences { padding:18px; }
        .admin-push-preferences-head { flex-direction:column; }
        .admin-push-pref-grid { grid-template-columns:1fr; }
        .admin-push-pref-all { grid-column:auto; }
    }
</style>

<section class="admin-push-preferences" id="adminPushPreferences" data-csrf="<?= e(csrf_token()) ?>">
    <div class="admin-push-preferences-head">
        <div>
            <h2>Preferenze notifiche</h2>
            <p>Scegli cosa vuoi ricevere. Le modifiche vengono salvate automaticamente per il tuo account.</p>
        </div>
        <span class="admin-push-preferences-status" id="adminPushPreferencesStatus">Salvate</span>
    </div>

    <div class="admin-push-pref-grid">
        <label class="admin-push-pref admin-push-pref-all">
            <input type="checkbox" id="adminPushPrefAll">
            <span>
                <strong>Tutte le notifiche</strong>
                <small>Attiva o disattiva tutte le categorie disponibili per il tuo profilo.</small>
            </span>
        </label>

        <?php foreach ($adminPushCategories as $key => $category): ?>
            <label class="admin-push-pref">
                <input
                    type="checkbox"
                    class="admin-push-pref-input"
                    data-category="<?= e($key) ?>"
                    <?= !empty($adminPushPreferences[$key]) ? 'checked' : '' ?>
                >
                <span>
                    <strong><?= e($category['label']) ?></strong>
                    <small><?= e($category['description']) ?></small>
                </span>
            </label>
        <?php endforeach; ?>
    </div>
</section>

<script>
(function () {
    const panel = document.getElementById('adminPushPreferences');
    const all = document.getElementById('adminPushPrefAll');
    const status = document.getElementById('adminPushPreferencesStatus');
    const inputs = Array.from(document.querySelectorAll('.admin-push-pref-input'));
    if (!panel || !all || !status || inputs.length === 0) return;

    function refreshAll() {
        const checked = inputs.filter((input) => input.checked).length;
        all.checked = checked === inputs.length;
        all.indeterminate = checked > 0 && checked < inputs.length;
    }

    async function save() {
        status.textContent = 'Salvataggio…';
        status.className = 'admin-push-preferences-status';
        const preferences = {};
        inputs.forEach((input) => {
            preferences[input.dataset.category] = input.checked;
        });

        try {
            const response = await fetch('push.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': panel.dataset.csrf || ''
                },
                body: JSON.stringify({action: 'preferences', preferences})
            });
            const payload = await response.json();
            if (!response.ok || !payload.success) {
                throw new Error(payload.error || 'Salvataggio non riuscito');
            }
            status.textContent = 'Salvate';
            status.className = 'admin-push-preferences-status ok';
        } catch (error) {
            status.textContent = 'Errore di salvataggio';
            status.className = 'admin-push-preferences-status error';
        }
    }

    all.addEventListener('change', () => {
        inputs.forEach((input) => { input.checked = all.checked; });
        all.indeterminate = false;
        save();
    });

    inputs.forEach((input) => input.addEventListener('change', () => {
        refreshAll();
        save();
    }));

    refreshAll();
})();
</script>
