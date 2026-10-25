<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=== MIGRACIÓN V8: TABLA DE SESIONES EXTERNAS MULTICANAL (WHATSAPP/MESSENGER/N8N) ===" . PHP_EOL;

try {
    $pdo = Database::getConnection();

    $sql = "CREATE TABLE IF NOT EXISTS chat_externo_sesiones (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        canal VARCHAR(32) NOT NULL DEFAULT 'whatsapp' COMMENT 'Canal: whatsapp, messenger, telegram, api, web',
        remitente VARCHAR(128) NOT NULL COMMENT 'Identificador único del cliente o teléfono ej: +59178901234',
        rol ENUM('user', 'assistant') NOT NULL,
        mensaje TEXT NOT NULL,
        metadata JSON NULL COMMENT 'Metadatos adicionales (acciones emitidas, productos consultados, tokens)',
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_canal_remitente_fecha (canal, remitente, creado_en)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sql);
    echo " [+] Tabla 'chat_externo_sesiones' creada o verificada con éxito." . PHP_EOL;
    echo "✅ Migración V8 completada sin errores." . PHP_EOL;
} catch (Throwable $e) {
    echo "❌ Error en migración V8: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
