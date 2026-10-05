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
        margin:0 0 24px;
    }
    .admin-push-preferences-head {
        width:100%;
        display:grid;
        grid-template-columns:minmax(0,1fr) auto;
        align-items:center;
        gap:16px;
        padding:20px 22px;
        border:0;
        background:#fff;
        color:#222;
        text-align:left;
        font:inherit;
        cursor:pointer;
        -webkit-tap-highlight-color:transparent;
    }
    .admin-push-preferences-head:hover { background:#fafafa; }
    .admin-push-preferences-head:focus-visible { outline:3px solid rgba(15,123,50,.22); outline-offset:-3px; }
    .admin-push-preferences-head-copy { min-width:0; }
    .admin-push-preferences h2 { margin:0 0 5px; font-size:18px; }
    .admin-push-preferences p { margin:0; color:#707070; font-size:13px; line-height:1.45; }
    .admin-push-preferences-head-side {
        display:flex;
        align-items:center;
        justify-content:flex-end;
        gap:12px;
    }
    .admin-push-preferences-status { font-size:12px; color:#707070; white-space:nowrap; }
    .admin-push-preferences-status.ok { color:#0f7b32; }
    .admin-push-preferences-status.error { color:#b00020; }
    .admin-push-preferences-chevron {
        position:relative;
        width:28px;
        height:28px;
        flex:0 0 auto;
        border-radius:50%;
        background:#f0f0f0;
    }
    .admin-push-preferences-chevron::before {
        content:'';
        position:absolute;
        left:9px;
        top:8px;
        width:8px;
        height:8px;
        border-right:2px solid #333;
        border-bottom:2px solid #333;
        transform:rotate(45deg);
        transition:transform .18s ease, top .18s ease;
    }
    .admin-push-preferences-head[aria-expanded="true"] .admin-push-preferences-chevron::before {
        top:11px;
        transform:rotate(225deg);
    }
    .admin-push-preferences-body {
        padding:0 22px 20px;
        border-top:1px solid #efefef;
    }
    .admin-push-preferences-body[hidden] { display:none; }
    .admin-push-pref-grid {
        display:grid;
        grid-template-columns:repeat(2,minmax(0,1fr));
        gap:10px;
        padding-top:16px;
    }
    .admin-push-pref {
        display:grid;
        grid-template-columns:minmax(0,1fr) auto;
        gap:14px;
        align-items:center;
        padding:14px 16px;
        background:#f7f7f7;
        border:1px solid #ececec;
        cursor:pointer;
        margin:0;
        min-height:72px;
        -webkit-tap-highlight-color:transparent;
    }
    .admin-push-pref:hover { background:#f2f2f2; }
    .admin-push-pref-copy { display:block; min-width:0; }
    .admin-push-pref strong { display:block; font-size:13px; margin-bottom:3px; }
    .admin-push-pref small { display:block; color:#777; line-height:1.35; font-weight:400; }
    .admin-push-pref-all { grid-column:1 / -1; background:#efefef; }

    .admin-push-toggle {
        position:relative;
        display:inline-block;
        width:52px;
        height:30px;
        flex:0 0 auto;
    }
    .admin-push-toggle input {
        position:absolute;
        inset:0;
        z-index:2;
        width:100%;
        height:100%;
        margin:0;
        opacity:0;
        cursor:pointer;
    }
    .admin-push-toggle-track {
        position:absolute;
        inset:0;
        display:block;
        border-radius:999px;
        background:#c8c8c8;
        box-shadow:inset 0 0 0 1px rgba(0,0,0,.08);
        transition:background .18s ease, box-shadow .18s ease;
    }
    .admin-push-toggle-track::after {
        content:'';
        position:absolute;
        top:3px;
        left:3px;
        width:24px;
        height:24px;
        border-radius:50%;
        background:#fff;
        box-shadow:0 1px 4px rgba(0,0,0,.28);
        transition:transform .18s ease;
    }
    .admin-push-toggle input:checked + .admin-push-toggle-track { background:#0f7b32; }
    .admin-push-toggle input:checked + .admin-push-toggle-track::after { transform:translateX(22px); }
    .admin-push-toggle input:indeterminate + .admin-push-toggle-track { background:#8b8b8b; }
    .admin-push-toggle input:indeterminate + .admin-push-toggle-track::after { transform:translateX(11px); }
    .admin-push-toggle input:focus-visible + .admin-push-toggle-track {
        box-shadow:0 0 0 3px rgba(15,123,50,.22), inset 0 0 0 1px rgba(0,0,0,.08);
    }

    @media(max-width:780px) {
        .admin-push-preferences-head { padding:18px; }
        .admin-push-preferences-body { padding:0 18px 18px; }
        .admin-push-preferences-head-side { gap:8px; }
        .admin-push-preferences-status { display:none; }
        .admin-push-pref-grid { grid-template-columns:1fr; }
        .admin-push-pref-all { grid-column:auto; }
        .admin-push-pref { min-height:76px; padding:14px; }
        .admin-push-toggle { width:56px; height:32px; }
        .admin-push-toggle-track::after { width:26px; height:26px; }
        .admin-push-toggle input:checked + .admin-push-toggle-track::after { transform:translateX(24px); }
        .admin-push-toggle input:indeterminate + .admin-push-toggle-track::after { transform:translateX(12px); }
    }
</style>

<section class="admin-push-preferences" id="adminPushPreferences" data-csrf="<?= e(csrf_token()) ?>">
    <button
        type="button"
        class="admin-push-preferences-head"
        id="adminPushPreferencesToggle"
        aria-expanded="false"
        aria-controls="adminPushPreferencesBody"
    >
        <span class="admin-push-preferences-head-copy">
            <h2>Preferenze notifiche</h2>
            <p>Scegli cosa vuoi ricevere. Le modifiche vengono salvate automaticamente per il tuo account.</p>
        </span>
        <span class="admin-push-preferences-head-side">
            <span class="admin-push-preferences-status" id="adminPushPreferencesStatus">Salvate</span>
            <span class="admin-push-preferences-chevron" aria-hidden="true"></span>
        </span>
    </button>

    <div class="admin-push-preferences-body" id="adminPushPreferencesBody" hidden>
        <div class="admin-push-pref-grid">
            <label class="admin-push-pref admin-push-pref-all">
                <span class="admin-push-pref-copy">
                    <strong>Tutte le notifiche</strong>
                    <small>Attiva o disattiva tutte le categorie disponibili per il tuo profilo.</small>
                </span>
                <span class="admin-push-toggle">
                    <input type="checkbox" id="adminPushPrefAll">
                    <span class="admin-push-toggle-track"></span>
                </span>
            </label>

            <?php foreach ($adminPushCategories as $key => $category): ?>
                <label class="admin-push-pref">
                    <span class="admin-push-pref-copy">
                        <strong><?= e($category['label']) ?></strong>
                        <small><?= e($category['description']) ?></small>
                    </span>
                    <span class="admin-push-toggle">
                        <input
                            type="checkbox"
                            class="admin-push-pref-input"
                            data-category="<?= e($key) ?>"
                            <?= !empty($adminPushPreferences[$key]) ? 'checked' : '' ?>
                        >
                        <span class="admin-push-toggle-track"></span>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
(function () {
    const panel = document.getElementById('adminPushPreferences');
    const toggle = document.getElementById('adminPushPreferencesToggle');
    const body = document.getElementById('adminPushPreferencesBody');
    const all = document.getElementById('adminPushPrefAll');
    const status = document.getElementById('adminPushPreferencesStatus');
    const inputs = Array.from(document.querySelectorAll('.admin-push-pref-input'));
    if (!panel || !toggle || !body || !all || !status || inputs.length === 0) return;

    toggle.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        body.hidden = !open;
    });

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
