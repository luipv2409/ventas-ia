<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$username = trim((string)($argv[1] ?? ''));
$password = (string)($argv[2] ?? '');
$nombre   = trim((string)($argv[3] ?? 'Administrador ULAT'));

if ($username === '' || strlen($password) < 12) {
    fwrite(STDERR, "Uso: php scripts/create_admin.php <usuario> <password_min_12_chars> [nombre]\n");
    exit(1);
}

$weak = ['admin123', 'admin1234567', 'password1234', '123456789012', 'ulat12345678'];
if (in_array(strtolower($password), $weak, true)) {
    fwrite(STDERR, "Error: La contraseña elegida está en la lista de claves débiles prohibidas.\n");
    exit(1);
}

$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
$pdo = Database::getConnection();

$stmt = $pdo->prepare("
    INSERT INTO usuarios_admin (username, password_hash, nombre)
    VALUES (:u, :h, :n)
    ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), nombre = VALUES(nombre)
");
$stmt->execute([
    ':u' => $username,
    ':h' => $hash,
    ':n' => $nombre,
]);

echo "OK: Usuario administrativo '{$username}' creado/actualizado con hash bcrypt (cost=12).\n";
