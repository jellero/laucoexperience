<?php
require_once __DIR__ . '/../inc/auth.php';
require_admin();
require_once __DIR__ . '/_admin_layout.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

    if ($nome === '') {
        $error = 'Inserisci il nome.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Inserisci una email valida.';
    } elseif (strlen($password) < 8) {
        $error = 'La password deve avere almeno 8 caratteri.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Le due password non coincidono.';
    } else {
        $stmt = $pdo->prepare('SELECT id FROM utenti WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);

        if ($stmt->fetch()) {
            $error = 'Esiste già un account con questa email.';
        } else {
            $stmt = $pdo->prepare('
                INSERT INTO utenti (nome, email, password_hash)
                VALUES (:nome, :email, :password_hash)
            ');

            $stmt->execute([
                'nome' => $nome,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);

            $success = 'Account creato correttamente.';
            $_POST = [];
        }
    }
}

$stmt = $pdo->query('SELECT id, nome, email, created_at FROM utenti ORDER BY created_at DESC, id DESC');
$utenti = $stmt->fetchAll();

admin_page_open('Account admin', 'account');
?>
<main class="wrap">
    <section class="page-title">
        <h1>Account admin</h1>
        <p>Crea nuovi account amministratore solo da utente già autenticato.</p>
    </section>

    <div class="box" style="margin-bottom:24px;">
        <?php if ($error): ?>
            <div class="error"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?= e($success) ?></div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="_csrf_token" value="<?= e(csrf_token()) ?>">

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
                    <input type="password" id="password" name="password" required>
                    <div class="hint">Minimo 8 caratteri.</div>
                </div>

                <div>
                    <label for="password_confirm">Conferma password</label>
                    <input type="password" id="password_confirm" name="password_confirm" required>
                </div>

                <div class="full">
                    <button class="btn" type="submit">Crea account</button>
                </div>
            </div>
        </form>
    </div>

    <div class="admin-card">
        <h2>Account esistenti</h2>
        <table>
            <thead>
                <tr><th>Nome</th><th>Email</th><th>Creato il</th></tr>
            </thead>
            <tbody>
                <?php if (!$utenti): ?>
                    <tr><td colspan="3">Nessun account presente.</td></tr>
                <?php endif; ?>

                <?php foreach ($utenti as $utente): ?>
                    <tr>
                        <td><?= e($utente['nome']) ?></td>
                        <td><?= e($utente['email']) ?></td>
                        <td><?= e($utente['created_at'] ?? '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>
<?php admin_page_close(); ?>
