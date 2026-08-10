<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    try {
        $subscriberId = (int) ($_POST['subscriber_id'] ?? 0);
        $action = (string) ($_POST['subscriber_action'] ?? '');
        if ($subscriberId <= 0) {
            throw new RuntimeException('Iscritto non valido.');
        }

        if ($action === 'unsubscribe') {
            $stmt = $pdo->prepare(
                "UPDATE newsletter_subscribers SET status = 'unsubscribed', unsubscribed_at = CURRENT_TIMESTAMP WHERE id = :id"
            );
            $stmt->execute(['id' => $subscriberId]);
            $success = 'Iscritto disattivato.';
        } elseif ($action === 'reactivate') {
            $stmt = $pdo->prepare(
                "UPDATE newsletter_subscribers SET status = 'active', unsubscribed_at = NULL, subscribed_at = CURRENT_TIMESTAMP WHERE id = :id"
            );
            $stmt->execute(['id' => $subscriberId]);
            $success = 'Iscritto riattivato.';
        } elseif ($action === 'delete') {
            $stmt = $pdo->prepare('DELETE FROM newsletter_subscribers WHERE id = :id');
            $stmt->execute(['id' => $subscriberId]);
            $success = 'Iscritto eliminato.';
        } else {
            throw new RuntimeException('Azione non valida.');
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
    }
}

$q = trim((string) ($_GET['q'] ?? ''));
try {
    $subscriberCounts = [
        'total' => (int) $pdo->query('SELECT COUNT(*) FROM newsletter_subscribers')->fetchColumn(),
        'active' => (int) $pdo->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'active'")->fetchColumn(),
        'unsubscribed' => (int) $pdo->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'unsubscribed'")->fetchColumn(),
    ];

    $campaignCounts = [
        'total' => (int) $pdo->query('SELECT COUNT(*) FROM newsletter_campaigns')->fetchColumn(),
        'draft' => (int) $pdo->query("SELECT COUNT(*) FROM newsletter_campaigns WHERE status = 'draft'")->fetchColumn(),
        'sent' => (int) $pdo->query("SELECT COUNT(*) FROM newsletter_campaigns WHERE status = 'sent'")->fetchColumn(),
    ];

    $campaigns = $pdo->query(
        'SELECT id, subject, status, sent_at, sent_count, failed_count, created_at, updated_at '
        . 'FROM newsletter_campaigns ORDER BY created_at DESC, id DESC LIMIT 100'
    )->fetchAll() ?: [];

    if ($q !== '') {
        $stmt = $pdo->prepare(
            'SELECT id, email, status, locale, subscribed_at, unsubscribed_at '
            . 'FROM newsletter_subscribers WHERE email LIKE :q ORDER BY subscribed_at DESC, id DESC LIMIT 250'
        );
        $stmt->execute(['q' => '%' . $q . '%']);
        $subscribers = $stmt->fetchAll() ?: [];
    } else {
        $subscribers = $pdo->query(
            'SELECT id, email, status, locale, subscribed_at, unsubscribed_at '
            . 'FROM newsletter_subscribers ORDER BY subscribed_at DESC, id DESC LIMIT 250'
        )->fetchAll() ?: [];
    }
} catch (Throwable $exception) {
    $subscriberCounts = ['total' => 0, 'active' => 0, 'unsubscribed' => 0];
    $campaignCounts = ['total' => 0, 'draft' => 0, 'sent' => 0];
    $campaigns = [];
    $subscribers = [];
    $error = $error ?: 'Le tabelle newsletter non sono disponibili. Applica la migrazione migrations/20260810_newsletter.sql.';
}

admin_page_open('Newsletter', 'newsletter');
?>

<style>
.newsletter-stats{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px;margin-bottom:24px}
.newsletter-stat{background:#fff;box-shadow:var(--admin-shadow);padding:20px}
.newsletter-stat small{display:block;text-transform:uppercase;letter-spacing:.06em;color:#777;margin-bottom:8px}
.newsletter-stat strong{font-size:32px;line-height:1}
.newsletter-section{margin-top:24px}
.newsletter-head{display:flex;justify-content:space-between;gap:16px;align-items:center;flex-wrap:wrap}
.newsletter-head h2{margin:0}
.newsletter-search{display:flex;gap:8px;align-items:end;flex-wrap:wrap}
.newsletter-search input{min-width:280px}
.status-active{color:#0f7b32;font-weight:700}
.status-unsubscribed{color:#777;font-weight:700}
@media(max-width:900px){.newsletter-stats{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.newsletter-stats{grid-template-columns:1fr}.newsletter-search input{min-width:0}}
</style>

<main class="wrap">
    <section class="hero-admin">
        <h1>Newsletter</h1>
        <p>Gestisci iscritti, bozze e invii HTML della newsletter.</p>
    </section>

    <?php if ($error): ?><div class="error"><?= e($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success"><?= e($success) ?></div><?php endif; ?>

    <div class="actions">
        <a class="btn" href="newsletter-form.php">Crea newsletter</a>
    </div>

    <section class="newsletter-stats">
        <div class="newsletter-stat"><small>Iscritti totali</small><strong><?= (int) $subscriberCounts['total'] ?></strong></div>
        <div class="newsletter-stat"><small>Iscritti attivi</small><strong><?= (int) $subscriberCounts['active'] ?></strong></div>
        <div class="newsletter-stat"><small>Bozze</small><strong><?= (int) $campaignCounts['draft'] ?></strong></div>
        <div class="newsletter-stat"><small>Newsletter inviate</small><strong><?= (int) $campaignCounts['sent'] ?></strong></div>
    </section>

    <section class="box newsletter-section">
        <div class="newsletter-head">
            <div>
                <h2>Newsletter create</h2>
                <p class="hint"><?= (int) $campaignCounts['total'] ?> campagne totali.</p>
            </div>
            <a class="btn" href="newsletter-form.php">Nuova</a>
        </div>

        <?php if (!$campaigns): ?>
            <p>Nessuna newsletter creata.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Oggetto</th><th>Stato</th><th>Invio</th><th>Esito</th><th>Azioni</th></tr></thead>
                <tbody>
                <?php foreach ($campaigns as $campaign): ?>
                    <tr>
                        <td>
                            <strong><?= e($campaign['subject']) ?></strong><br>
                            <small>Creata: <?= e(date('d/m/Y H:i', strtotime((string) $campaign['created_at']))) ?></small>
                        </td>
                        <td><?= e((string) $campaign['status']) ?></td>
                        <td><?= $campaign['sent_at'] ? e(date('d/m/Y H:i', strtotime((string) $campaign['sent_at']))) : '-' ?></td>
                        <td><?= (int) $campaign['sent_count'] ?> inviate · <?= (int) $campaign['failed_count'] ?> errori</td>
                        <td><a class="mini-btn" href="newsletter-form.php?id=<?= (int) $campaign['id'] ?>"><?= $campaign['status'] === 'draft' ? 'Modifica' : 'Apri' ?></a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>

    <section class="box newsletter-section">
        <div class="newsletter-head">
            <div>
                <h2>Iscritti</h2>
                <p class="hint">Mostrati al massimo 250 risultati per volta.</p>
            </div>
            <form class="newsletter-search" method="get">
                <div>
                    <label for="q">Cerca email</label>
                    <input id="q" name="q" type="search" value="<?= e($q) ?>" placeholder="nome@example.it">
                </div>
                <button class="btn" type="submit">Cerca</button>
                <?php if ($q !== ''): ?><a class="btn secondary" href="newsletter.php">Azzera</a><?php endif; ?>
            </form>
        </div>

        <?php if (!$subscribers): ?>
            <p>Nessun iscritto trovato.</p>
        <?php else: ?>
            <table>
                <thead><tr><th>Email</th><th>Stato</th><th>Lingua</th><th>Iscrizione</th><th>Azioni</th></tr></thead>
                <tbody>
                <?php foreach ($subscribers as $subscriber): ?>
                    <tr>
                        <td><?= e($subscriber['email']) ?></td>
                        <td class="<?= $subscriber['status'] === 'active' ? 'status-active' : 'status-unsubscribed' ?>">
                            <?= $subscriber['status'] === 'active' ? 'Attivo' : 'Disiscritto' ?>
                        </td>
                        <td><?= e(strtoupper((string) $subscriber['locale'])) ?></td>
                        <td><?= e(date('d/m/Y H:i', strtotime((string) $subscriber['subscribed_at']))) ?></td>
                        <td>
                            <?php if ($subscriber['status'] === 'active'): ?>
                                <form method="post">
                                    <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="subscriber_id" value="<?= (int) $subscriber['id'] ?>">
                                    <input type="hidden" name="subscriber_action" value="unsubscribe">
                                    <button class="mini-btn secondary" type="submit">Disattiva</button>
                                </form>
                            <?php else: ?>
                                <form method="post">
                                    <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="subscriber_id" value="<?= (int) $subscriber['id'] ?>">
                                    <input type="hidden" name="subscriber_action" value="reactivate">
                                    <button class="mini-btn" type="submit">Riattiva</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" onsubmit="return confirm('Eliminare definitivamente questo iscritto?');">
                                <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="subscriber_id" value="<?= (int) $subscriber['id'] ?>">
                                <input type="hidden" name="subscriber_action" value="delete">
                                <button class="mini-btn danger" type="submit">Elimina</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</main>

<?php admin_page_close(); ?>
