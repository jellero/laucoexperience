<?php
declare(strict_types=1);

use Webklex\PHPIMAP\Folder;

if (!function_exists('admin_mail_date')) {
    function admin_mail_date(?DateTimeInterface $date, bool $full = false): string
    {
        if (!$date) {
            return '-';
        }
        $now = new DateTimeImmutable('now', $date->getTimezone());
        return !$full && $date->format('Y-m-d') === $now->format('Y-m-d')
            ? $date->format('H:i')
            : $date->format($full ? 'd.m.Y H:i' : 'd.m.Y');
    }
}

if (!function_exists('admin_mail_size')) {
    function admin_mail_size(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }
        if ($bytes < 1_048_576) {
            return number_format($bytes / 1024, 1, ',', '.') . ' KB';
        }
        return number_format($bytes / 1_048_576, 1, ',', '.') . ' MB';
    }
}

if (!function_exists('admin_mail_folder_url')) {
    /** @param array<string,scalar> $extra */
    function admin_mail_folder_url(string $folder, array $extra = []): string
    {
        return 'posta.php?' . http_build_query(['folder' => $folder] + $extra);
    }
}

if (!function_exists('admin_mail_styles')) {
    function admin_mail_styles(): void
    {
        ?>
        <style>
            .mail-shell { display:grid; grid-template-columns:230px minmax(0,1fr); gap:20px; align-items:start; }
            .mail-sidebar,.mail-panel,.mail-message { background:#fff; box-shadow:var(--admin-shadow); }
            .mail-sidebar { padding:14px; position:sticky; top:16px; }
            .mail-sidebar .btn { width:100%; text-align:center; margin-bottom:12px; }
            .mail-folder { display:block; padding:10px 11px; color:#333; text-decoration:none; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; border-left:3px solid transparent; }
            .mail-folder:hover,.mail-folder.active { background:#f1f1f1; border-left-color:#222; }
            .mail-panel { min-width:0; }
            .mail-toolbar { padding:15px; border-bottom:1px solid var(--admin-border); display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
            .mail-toolbar form { display:flex; gap:8px; flex:1; min-width:240px; }
            .mail-toolbar input { min-width:0; }
            .mail-list { margin:0; padding:0; list-style:none; }
            .mail-row { display:grid; grid-template-columns:minmax(150px,.75fr) minmax(220px,1.5fr) 105px; gap:16px; align-items:center; padding:15px 18px; border-bottom:1px solid var(--admin-border); color:#333; text-decoration:none; }
            .mail-row:last-child { border-bottom:0; }
            .mail-row:hover { background:#fafafa; }
            .mail-row.unread { background:#f5f8ff; font-weight:700; }
            .mail-row.unread:hover { background:#edf2ff; }
            .mail-sender,.mail-subject { overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
            .mail-date { text-align:right; color:#666; font-size:13px; white-space:nowrap; }
            .mail-icons { color:#777; font-size:12px; margin-left:5px; }
            .mail-empty,.mail-error { padding:28px; }
            .mail-pagination { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:15px 18px; border-top:1px solid var(--admin-border); }
            .mail-message { padding:26px; }
            .mail-message-head { padding-bottom:18px; margin-bottom:22px; border-bottom:1px solid var(--admin-border); }
            .mail-message-head h1 { margin:0 0 15px; font-size:26px; overflow-wrap:anywhere; }
            .mail-meta { display:grid; grid-template-columns:80px minmax(0,1fr); gap:7px 12px; color:#555; line-height:1.5; overflow-wrap:anywhere; }
            .mail-meta strong { color:#222; }
            .mail-body { line-height:1.6; overflow-wrap:anywhere; }
            .mail-body table { display:table; box-shadow:none; width:auto; max-width:100%; }
            .mail-body pre { white-space:pre-wrap; }
            .mail-attachments { margin-top:24px; padding-top:18px; border-top:1px solid var(--admin-border); display:flex; flex-wrap:wrap; gap:9px; }
            .mail-attachment { display:inline-block; border:1px solid #ddd; padding:9px 11px; text-decoration:none; background:#fafafa; }
            .mail-compose { background:#fff; box-shadow:var(--admin-shadow); padding:26px; }
            .mail-compose-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; }
            .mail-compose textarea { min-height:320px; resize:vertical; }
            .mail-compose .full { grid-column:1/-1; }
            @media(max-width:820px) {
                .mail-shell { grid-template-columns:1fr; }
                .mail-sidebar { position:static; }
                .mail-folders { display:flex; overflow-x:auto; }
                .mail-folder { flex:0 0 auto; }
                .mail-row { grid-template-columns:1fr auto; gap:6px 12px; }
                .mail-subject { grid-column:1/-1; grid-row:2; }
                .mail-date { grid-column:2; grid-row:1; }
                .mail-compose-grid { grid-template-columns:1fr; }
                .mail-compose .full { grid-column:auto; }
            }
        </style>
        <?php
    }
}

if (!function_exists('admin_mail_sidebar')) {
    /** @param list<Folder> $folders */
    function admin_mail_sidebar(array $folders, string $selected): void
    {
        ?>
        <aside class="mail-sidebar">
            <a class="btn" href="posta-scrivi.php">Scrivi email</a>
            <nav class="mail-folders" aria-label="Cartelle di posta">
                <?php foreach ($folders as $folder): ?>
                    <a class="mail-folder<?= $folder->path === $selected ? ' active' : '' ?>" href="<?= e(admin_mail_folder_url($folder->path)) ?>" title="<?= e($folder->full_name) ?>">
                        <?= e(strcasecmp($folder->path, 'INBOX') === 0 ? 'Posta in arrivo' : $folder->full_name) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>
        <?php
    }
}
