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
        $role = (string) ($_POST['ruolo'] ?? '');
        if ($userId < 1 || !array_key_exists($role, admin_roles())) {
            $error = 'Utente o ruolo non valido.';
        } elseif ($userId === admin_id() && $role !== 'admin') {
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
        $role = (string) ($_POST['ruolo'] ?? 'collaboratore');

        if ($nome === '') {
            $error = 'Inserisci il nome.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Inserisci una email valida.';
        } elseif (strlen($password) < 12) {
            $error = 'La password deve avere almeno 12 caratteri.';
        } elseif ($password !== $passwordConfirm) {
            $error = 'Le due password non coincidono.';
        } elseif (!array_key_exists($role, admin_roles())) {
            $error = 'Seleziona un ruolo valido.';
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
                <div class="full">
                    <label for="ruolo">Ruolo</label>
                    <select id="ruolo" name="ruolo" required>
                        <?php foreach (admin_roles() as $roleKey => $definition): ?>
                            <option value="<?= e($roleKey) ?>" <?= ($_POST['ruolo'] ?? 'collaboratore') === $roleKey ? 'selected' : '' ?>>
                                <?= e($definition['label']) ?> — <?= e($definition['description']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
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
                                <form method="post" style="display:flex;gap:8px;align-items:center;min-width:300px;">
                                    <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="update_role">
                                    <input type="hidden" name="id" value="<?= (int) $utente['id'] ?>">
                                    <select name="ruolo" aria-label="Ruolo di <?= e($utente['nome']) ?>" <?= (int) $utente['id'] === admin_id() ? 'disabled' : '' ?>>
                                        <?php foreach (admin_roles() as $roleKey => $definition): ?>
                                            <option value="<?= e($roleKey) ?>" <?= admin_normalize_role((string) $utente['ruolo']) === $roleKey ? 'selected' : '' ?>><?= e($definition['label']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
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
