<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=== MIGRACIÓN V6: AUTENTICACIÓN Y PERFIL DE CLIENTES ===" . PHP_EOL;

try {
    $pdo = Database::getConnection();

    // Obtener columnas existentes en clientes
    $stmt = $pdo->query("SHOW COLUMNS FROM clientes");
    $columnasExistentes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $columnasNuevas = [
        'password_hash' => "VARCHAR(255) NULL COMMENT 'Hash de contraseña segura'",
        'google_id' => "VARCHAR(100) NULL UNIQUE COMMENT 'ID único de usuario de Google OAuth'",
        'avatar_url' => "VARCHAR(500) NULL COMMENT 'Foto de perfil del cliente o Google'",
        'token_recuperacion' => "VARCHAR(100) NULL COMMENT 'Token de restablecimiento de contraseña'",
        'token_expira' => "DATETIME NULL COMMENT 'Fecha y hora de expiración del token'",
        'ultimo_login' => "DATETIME NULL COMMENT 'Último inicio de sesión registrado'",
        'activo' => "TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = Activo, 0 = Inactivo'"
    ];

    foreach ($columnasNuevas as $columna => $definicion) {
        if (!in_array($columna, $columnasExistentes, true)) {
            $sql = "ALTER TABLE clientes ADD COLUMN `{$columna}` {$definicion}";
            $pdo->exec($sql);
            echo " [+] Columna agregada: {$columna}" . PHP_EOL;
        } else {
            echo " [.] Columna ya existe: {$columna}" . PHP_EOL;
        }
    }

    // Asegurar índice en email
    try {
        $pdo->exec("ALTER TABLE clientes ADD INDEX idx_cliente_email (email)");
        echo " [+] Índice idx_cliente_email creado." . PHP_EOL;
    } catch (Exception $e) {
        // El índice ya existe
        echo " [.] Índice en email ya configurado." . PHP_EOL;
    }

    echo "✅ Migración V6 completada con éxito." . PHP_EOL;

} catch (Exception $e) {
    echo "❌ Error en migración V6: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
