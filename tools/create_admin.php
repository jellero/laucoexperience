<?php
declare(strict_types=1);

require_once __DIR__ . '/../inc/db.php';

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Questo script va eseguito da terminale.\n");
}

$email = $argv[1] ?? '';
$password = $argv[2] ?? '';
$nome = $argv[3] ?? 'Admin';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
    exit("Uso: php tools/create_admin.php email password [nome]\nLa password deve avere almeno 8 caratteri.\n");
}

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO utenti (nome, email, password_hash)
    VALUES (:nome, :email, :password_hash)
    ON DUPLICATE KEY UPDATE
        nome = VALUES(nome),
        password_hash = VALUES(password_hash)
");

$stmt->execute([
    'nome' => $nome,
    'email' => $email,
    'password_hash' => $hash,
]);

echo "Utente admin creato/aggiornato: {$email}\n";
