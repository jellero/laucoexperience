<?php
/**
 * Layout unico backoffice Lauco Experience.
 * Include questo file dopo inc/auth.php e require_admin().
 */

if (!function_exists('admin_current_email')) {
    function admin_current_email(): string
    {
        return (string) ($_SESSION['admin_email'] ?? $_SESSION['admin_user']['email'] ?? '');
    }
}

if (!function_exists('admin_current_role_label')) {
    function admin_current_role_label(): string
    {
        return admin_role_label(admin_role());
    }
}

if (!function_exists('admin_nav_active')) {
    function admin_nav_active(string $current, string $item): string
    {
        return $current === $item ? ' class="active"' : '';
    }
}

if (!function_exists('admin_page_open')) {
    /**
     * @param list<array{href:string,label:string,number:int,text:string,urgent?:bool}> $dashboardCards
     */
    function admin_page_open(string $title, string $active = '', array $dashboardCards = []): void
    {
        $email = admin_current_email();
        $roleLabel = admin_current_role_label();
        ?>
        <!DOCTYPE html>
        <html lang="it">
        <head>
            <meta charset="utf-8">
            <title><?= e($title) ?> | Backoffice Lauco Experience</title>
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <style>
                :root {
                    --admin-dark:#202020;
                    --admin-dark-soft:#2c2c2c;
                    --admin-bg:#f4f4f4;
                    --admin-card:#fff;
                    --admin-border:#e6e6e6;
                    --admin-muted:#707070;
                    --admin-danger:#b00020;
                    --admin-ok:#0f7b32;
                    --admin-shadow:0 8px 28px rgba(0,0,0,.07);
                }
                * { box-sizing:border-box; }
                body { font-family:Arial,sans-serif; background:var(--admin-bg); margin:0; color:#222; }
                a { color:inherit; }
                .admin-shell-header { background:var(--admin-dark); color:#fff; box-shadow:0 4px 18px rgba(0,0,0,.18); }
                .admin-shell-mainbar { max-width:1280px; margin:0 auto; padding:16px 22px 12px; display:flex; justify-content:space-between; align-items:center; gap:20px; }
                .admin-brand { display:flex; flex-direction:column; gap:2px; min-width:210px; }
                .admin-brand strong { font-size:17px; letter-spacing:.02em; }
                .admin-brand span { font-size:12px; color:rgba(255,255,255,.72); }
                .admin-role { display:inline-block; margin-top:4px; width:max-content; padding:4px 7px; background:rgba(255,255,255,.12); color:#fff!important; font-size:10px!important; text-transform:uppercase; letter-spacing:.06em; }
                .admin-top-actions { display:flex; align-items:center; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
                .admin-top-actions a { display:inline-block; color:#fff; text-decoration:none; border:1px solid rgba(255,255,255,.25); padding:8px 10px; font-size:12px; line-height:1; opacity:.95; }
                .admin-top-actions a:hover { background:#fff; color:#222; opacity:1; }
                .admin-nav-wrap { border-top:1px solid rgba(255,255,255,.08); background:var(--admin-dark-soft); }
                .admin-nav { max-width:1280px; margin:0 auto; padding:0 22px; display:flex; align-items:center; gap:0; overflow-x:auto; white-space:nowrap; }
                .admin-nav a { color:#fff; text-decoration:none; padding:14px 12px; font-size:13px; border-left:1px solid rgba(255,255,255,.06); opacity:.86; }
                .admin-nav a:last-child { border-right:1px solid rgba(255,255,255,.06); }
                .admin-nav a:hover,.admin-nav a.active { opacity:1; background:#fff; color:#222; }
                .wrap { max-width:1280px; margin:34px auto; padding:0 22px; }
                .page-title,.hero-admin { background:#fff; padding:28px 30px; box-shadow:var(--admin-shadow); margin-bottom:24px; }
                .page-title h1,.hero-admin h1 { margin:0 0 8px; font-size:30px; line-height:1.15; }
                .page-title p,.hero-admin p { margin:0; color:var(--admin-muted); line-height:1.5; }
                .actions,.card-actions { display:flex; gap:10px; flex-wrap:wrap; margin-bottom:22px; }
                .btn,button.btn,.mini-btn { display:inline-block; background:#222; color:#fff!important; padding:10px 14px; text-decoration:none!important; border:0; cursor:pointer; font-family:Arial,sans-serif; font-size:13px; line-height:1.2; border-radius:0; }
                .btn:hover,.mini-btn:hover { background:#000; color:#fff!important; }
                .btn.secondary,.mini-btn.secondary,.secondary { background:#666; color:#fff!important; }
                .btn.danger,.danger { background:var(--admin-danger); color:#fff!important; }
                .box,.admin-card,table { background:#fff; box-shadow:var(--admin-shadow); }
                .box,.admin-card { padding:26px; }
                table { width:100%; border-collapse:collapse; margin-top:18px; }
                th,td { padding:12px; border-bottom:1px solid var(--admin-border); text-align:left; vertical-align:top; line-height:1.45; }
                th { background:#fafafa; font-size:13px; text-transform:uppercase; letter-spacing:.03em; }
                tr:last-child td { border-bottom:0; }
                .status { font-weight:bold; }
                .status.ok { color:var(--admin-ok); }
                .status.draft { color:var(--admin-muted); }
                form.inline,table form { display:inline; }
                small,small.muted,.hint { color:#777; font-size:13px; }
                .grid { display:grid; grid-template-columns:1fr 1fr; gap:18px; }
                .full { grid-column:1 / -1; }
                label { display:block; font-weight:700; margin-bottom:7px; }
                input,textarea,select { width:100%; padding:11px; border:1px solid #ddd; box-sizing:border-box; font-family:Arial,sans-serif; background:#fff; }
                input[type="checkbox"],input[type="radio"] { width:auto; }
                textarea { min-height:140px; }
                .error { background:#f8d7da; color:#842029; padding:12px; margin-bottom:18px; }
                .success { background:#d1e7dd; color:#0f5132; padding:12px; margin-bottom:18px; }
                .thumb { width:130px; height:84px; object-fit:cover; display:block; background:#eee; margin-bottom:6px; }
                .thumbs { display:flex; flex-wrap:wrap; gap:14px; margin-top:10px; }
                .thumbs .thumb,label.thumb { width:150px; height:auto; background:#fafafa; border:1px solid #ddd; padding:8px; }
                label.thumb img { width:100%; height:90px; object-fit:cover; display:block; margin-bottom:6px; }
                .preview { max-width:360px; height:auto; display:block; margin-top:10px; }
                .dashboard-grid { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:18px; margin-bottom:28px; }
                .dashboard-card { display:block; background:#fff; color:#222; text-decoration:none; box-shadow:var(--admin-shadow); padding:24px; min-height:178px; border:1px solid transparent; transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease; }
                .dashboard-card:hover { transform:translateY(-3px); box-shadow:0 14px 38px rgba(0,0,0,.11); border-color:#ddd; color:#222; text-decoration:none; }
                .dashboard-card small { display:block; text-transform:uppercase; letter-spacing:.08em; margin-bottom:14px; font-size:11px; }
                .dashboard-card h2 { margin:0 0 12px; font-size:22px; }
                .dashboard-card .number { display:block; font-size:34px; font-weight:700; margin-bottom:8px; }
                .dashboard-card p { margin:0; color:#707070; line-height:1.45; }
                .dashboard-card.urgent { border-color:var(--admin-danger); }
                .dashboard-card.urgent .number { color:var(--admin-danger); }
                .dashboard-columns { display:grid; grid-template-columns:1fr 1fr; gap:22px; }
                .table-missing { display:inline-block; background:#fff3cd; color:#664d03; padding:3px 6px; font-size:11px; margin-left:5px; }
                .dashboard-summary { max-width:1280px; margin:24px auto 0; padding:0 22px; }
                .dashboard-summary .dashboard-card { min-height:132px; }
                @media(max-width:1100px){.dashboard-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.dashboard-columns{grid-template-columns:1fr}}
                @media(max-width:780px){.admin-shell-mainbar{align-items:flex-start;flex-direction:column}.admin-top-actions{justify-content:flex-start}.wrap{margin:24px auto}.grid{grid-template-columns:1fr}.dashboard-grid{grid-template-columns:1fr}table{display:block;overflow-x:auto;white-space:nowrap}}
            </style>
        </head>
        <body>
            <header class="admin-shell-header">
                <div class="admin-shell-mainbar">
                    <div class="admin-brand">
                        <strong>Backoffice Lauco Experience</strong>
                        <span><?= e($email) ?></span>
                        <span class="admin-role"><?= e($roleLabel) ?></span>
                    </div>
                    <div class="admin-top-actions">
                        <a href="../index.php" target="_blank">Vedi sito</a>
                        <a href="../logout.php">Logout</a>
                    </div>
                </div>
                <div class="admin-nav-wrap">
                    <nav class="admin-nav">
                        <a href="index.php"<?= admin_nav_active($active, 'dashboard') ?>>Dashboard</a>
                        <?php if (admin_can('admin.all')): ?>
                            <a href="percorsi.php"<?= admin_nav_active($active, 'percorsi') ?>>Itinerari</a>
                            <a href="eventi.php"<?= admin_nav_active($active, 'eventi') ?>>Eventi</a>
                            <a href="galleria.php"<?= admin_nav_active($active, 'galleria') ?>>Galleria</a>
                            <a href="slider.php"<?= admin_nav_active($active, 'slider') ?>>Slider</a>
                            <a href="luoghi.php"<?= admin_nav_active($active, 'luoghi') ?>>Luoghi</a>
                            <a href="statistiche-qr.php"<?= admin_nav_active($active, 'qr-stats') ?>>Statistiche</a>
                            <a href="traduzioni-contenuti.php"<?= admin_nav_active($active, 'traduzioni') ?>>Traduzioni</a>
                            <a href="testi-sito.php"<?= admin_nav_active($active, 'testi-sito') ?>>Testi sito</a>
                        <?php endif; ?>
                        <?php if (admin_can('communications.respond')): ?>
                            <a href="contatti-messaggi.php"<?= admin_nav_active($active, 'messaggi') ?>>Messaggi</a>
                            <a href="posta.php"<?= admin_nav_active($active, 'posta') ?>>Posta</a>
                            <a href="contributi.php"<?= admin_nav_active($active, 'contributi') ?>>Contributi</a>
                            <a href="segnalazioni.php"<?= admin_nav_active($active, 'segnalazioni') ?>>Segnalazioni</a>
                        <?php endif; ?>
                        <?php if (admin_can('admin.all')): ?>
                            <a href="newsletter.php"<?= admin_nav_active($active, 'newsletter') ?>>Newsletter</a>
                        <?php endif; ?>
                        <?php if (admin_can('whatsapp.manage')): ?>
                            <a href="volontariato.php<?= !admin_can('admin.all') ? '?view=chat' : '' ?>"<?= admin_nav_active($active, 'volontariato') ?>><?= !admin_can('admin.all') ? 'WhatsApp' : 'Volontariato' ?></a>
                        <?php endif; ?>
                        <?php if (admin_can('admin.all')): ?>
                            <a href="crea-account.php"<?= admin_nav_active($active, 'account') ?>>Utenti</a>
                        <?php endif; ?>
                    </nav>
                </div>
            </header>
            <?php if ($active === 'dashboard'): ?>
                <section class="dashboard-summary" aria-label="Riepilogo dashboard">
                    <div class="dashboard-grid">
                        <?php foreach ($dashboardCards as $card): ?>
                            <a class="dashboard-card<?= !empty($card['urgent']) ? ' urgent' : '' ?>" href="<?= e($card['href']) ?>">
                                <small><?= e($card['label']) ?></small>
                                <span class="number"><?= (int) $card['number'] ?></span>
                                <p><?= e($card['text']) ?></p>
                            </a>
                        <?php endforeach; ?>
                        <?php if (admin_can('communications.respond')): ?>
                        <a class="dashboard-card" href="posta.php?folder=INBOX">
                            <small>Posta · nuove email</small>
                            <span class="number" id="dashboardMailUnread">…</span>
                            <p id="dashboardMailStatus">Controllo della casella in corso.</p>
                        </a>
                        <?php endif; ?>
                    </div>
                </section>
                <?php if (admin_can('communications.respond')): ?>
                <script>
                (function () {
                    const unread = document.getElementById('dashboardMailUnread');
                    const status = document.getElementById('dashboardMailStatus');
                    if (!unread || !status) return;

                    fetch('posta-count.php', {credentials: 'same-origin', headers: {'Accept': 'application/json'}})
                        .then((response) => response.json())
                        .then((payload) => {
                            if (!payload.success) throw new Error('mailbox unavailable');
                            const count = Math.max(0, Number.parseInt(payload.unread, 10) || 0);
                            unread.textContent = String(count);
                            status.textContent = count === 1
                                ? '1 email da leggere. Apri la casella.'
                                : count + ' email da leggere. Apri la casella.';
                        })
                        .catch(() => {
                            unread.textContent = '—';
                            status.textContent = 'Casella non disponibile o non configurata.';
                        });
                })();
                </script>
                <?php endif; ?>
            <?php endif; ?>
        <?php
    }
}

if (!function_exists('admin_page_close')) {
    function admin_page_close(): void
    {
        ?>
        </body>
        </html>
        <?php
    }
}
