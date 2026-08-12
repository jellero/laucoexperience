<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? 'create');

    if ($action === 'update_role') {
        $userId = (int) ($_POST['id'] ?? 0);
        $selectedRoles = admin_filter_roles($_POST['ruoli'] ?? []);
        $role = admin_roles_value($selectedRoles);
        if ($userId < 1 || $role === '') {
            $error = 'Utente o permessi non validi.';
        } elseif ($userId === admin_id() && !admin_role_can($role, 'admin.all')) {
            $error = 'Non puoi rimuovere il ruolo amministratore dall’account che stai usando.';
        } else {
            $stmt = $pdo->prepare('UPDATE utenti SET ruolo = :ruolo WHERE id = :id');
            $stmt->execute(['ruolo' => $role, 'id' => $userId]);
            $success = 'Permessi aggiornati correttamente.';
        }
    } else {
        $nome = trim((string) ($_POST['nome'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
        $selectedRoles = admin_filter_roles($_POST['ruoli'] ?? []);
        $role = admin_roles_value($selectedRoles);

        if ($nome === '') {
            $error = 'Inserisci il nome.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Inserisci una email valida.';
        } elseif (strlen($password) < 12) {
            $error = 'La password deve avere almeno 12 caratteri.';
        } elseif ($password !== $passwordConfirm) {
            $error = 'Le due password non coincidono.';
        } elseif ($role === '') {
            $error = 'Seleziona almeno un permesso.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM utenti WHERE LOWER(email) = LOWER(:email) LIMIT 1');
            $stmt->execute(['email' => $email]);
            if ($stmt->fetch()) {
                $error = 'Esiste già un account con questa email.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO utenti (nome, email, password_hash, ruolo) VALUES (:nome, :email, :password_hash, :ruolo)');
                $stmt->execute([
                    'nome' => $nome,
                    'email' => $email,
                    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                    'ruolo' => $role,
                ]);
                $success = 'Account creato correttamente.';
                $_POST = [];
            }
        }
    }
}

$utenti = $pdo->query('SELECT id, nome, email, ruolo, created_at FROM utenti ORDER BY created_at DESC, id DESC')->fetchAll();

admin_page_open('Utenti e permessi', 'account');
?>
<style>
    .permission-options { display:flex; gap:10px; flex-wrap:wrap; }
    .permission-option { display:flex; gap:9px; align-items:flex-start; flex:1 1 220px; margin:0; padding:12px; border:1px solid #ddd; background:#fafafa; font-weight:400; }
    .permission-option input { margin-top:3px; flex:0 0 auto; }
    .permission-option strong,.permission-option small { display:block; }
    .permission-option small { margin-top:3px; line-height:1.35; }
    .permissions-inline { display:flex; gap:8px; flex-wrap:wrap; align-items:center; min-width:390px; }
    .permissions-inline label { display:flex; gap:5px; align-items:center; margin:0; padding:7px 9px; background:#f4f4f4; font-size:12px; white-space:nowrap; }
</style>
<main class="wrap">
    <section class="page-title">
        <h1>Utenti e permessi</h1>
        <p>Crea gli accessi al backoffice e assegna a ogni persona soltanto le sezioni necessarie.</p>
    </section>

    <div class="grid" style="margin-bottom:24px;">
        <?php foreach (admin_roles() as $definition): ?>
            <section class="admin-card">
                <h2 style="margin-top:0;"><?= e($definition['label']) ?></h2>
                <p style="margin-bottom:0;color:#666;"><?= e($definition['description']) ?></p>
            </section>
        <?php endforeach; ?>
    </div>

    <div class="box" style="margin-bottom:24px;">
        <h2 style="margin-top:0;">Nuovo account</h2>

        <?php if ($error !== ''): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>
        <?php if ($success !== ''): ?>
            <div class="success"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create">

            <div class="grid">
                <div>
                    <label for="nome">Nome</label>
                    <input type="text" id="nome" name="nome" value="<?= e($_POST['nome'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= e($_POST['email'] ?? '') ?>" required>
                </div>
                <div>
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" minlength="12" required>
                    <div class="hint">Minimo 12 caratteri.</div>
                </div>
                <div>
                    <label for="password_confirm">Conferma password</label>
                    <input type="password" id="password_confirm" name="password_confirm" minlength="12" required>
                </div>
                <fieldset class="full" style="border:0;padding:0;margin:0;">
                    <legend style="font-weight:700;margin-bottom:8px;">Ruoli e permessi</legend>
                    <div class="permission-options">
                        <?php foreach (admin_roles() as $roleKey => $definition): ?>
                            <?php $newAccountRoles = admin_filter_roles($_POST['ruoli'] ?? ['collaboratore']); ?>
                            <label class="permission-option">
                                <input type="checkbox" name="ruoli[]" value="<?= e($roleKey) ?>" <?= in_array($roleKey, $newAccountRoles, true) ? 'checked' : '' ?>>
                                <span><strong><?= e($definition['label']) ?></strong><small><?= e($definition['description']) ?></small></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="hint">Puoi selezionare insieme Collaboratore e Operatore WhatsApp. Amministratore include già ogni permesso.</p>
                </fieldset>
                <div class="full">
                    <button class="btn" type="submit">Crea account</button>
                </div>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h2>Account esistenti</h2>
        <div style="overflow-x:auto;">
            <table>
                <thead>
                    <tr><th>Nome</th><th>Email</th><th>Ruolo e permessi</th><th>Creato il</th></tr>
                </thead>
                <tbody>
                    <?php if (!$utenti): ?>
                        <tr><td colspan="4">Nessun account presente.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($utenti as $utente): ?>
                        <tr>
                            <td><?= e($utente['nome']) ?></td>
                            <td><?= e($utente['email']) ?></td>
                            <td>
                                <?php $currentRoles = admin_normalize_roles((string) $utente['ruolo']); ?>
                                <form method="post" class="permissions-inline">
                                    <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="update_role">
                                    <input type="hidden" name="id" value="<?= (int) $utente['id'] ?>">
                                    <?php foreach (admin_roles() as $roleKey => $definition): ?>
                                        <label>
                                            <input type="checkbox" name="ruoli[]" value="<?= e($roleKey) ?>" <?= in_array($roleKey, $currentRoles, true) ? 'checked' : '' ?> <?= (int) $utente['id'] === admin_id() ? 'disabled' : '' ?>>
                                            <?= e($definition['label']) ?>
                                        </label>
                                    <?php endforeach; ?>
                                    <?php if ((int) $utente['id'] === admin_id()): ?>
                                        <span class="hint">Account in uso</span>
                                    <?php else: ?>
                                        <button class="mini-btn" type="submit">Salva</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                            <td><?= e($utente['created_at'] ?? '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>
<?php admin_page_close(); ?>
