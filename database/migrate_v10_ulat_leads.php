<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

try {
    $pdo = Database::getConnection();

    $sql = "
    CREATE TABLE IF NOT EXISTS admisiones_prospectos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(150) NOT NULL,
        telefono VARCHAR(50) NOT NULL,
        email VARCHAR(150) DEFAULT NULL,
        carrera_interes VARCHAR(150) NOT NULL,
        facultad VARCHAR(150) DEFAULT NULL,
        origen ENUM('chatbot', 'formulario_web', 'whatsapp_directo') NOT NULL DEFAULT 'chatbot',
        notas TEXT DEFAULT NULL,
        estado ENUM('nuevo', 'contactado', 'en_seguimiento', 'matriculado', 'descartado') NOT NULL DEFAULT 'nuevo',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_carrera (carrera_interes),
        INDEX idx_estado (estado),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);
    echo "✔ Tabla 'admisiones_prospectos' creada o verificada correctamente.\n";
} catch (PDOException $e) {
    echo "❌ Error en migración v10: " . $e->getMessage() . "\n";
    exit(1);
}

