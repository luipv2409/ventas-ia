<?php
declare(strict_types=1);

/**
 * Migración V9: Endurecimiento Operativo (Rate Limiting y Caché de Consultas RAG).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=== MIGRACIÓN V9: TABLAS DE RATE LIMITING Y CACHÉ RAG ===" . PHP_EOL;

try {
    $pdo = Database::getConnection();

    // 1. Tabla de Rate Limiting atómico
    $sqlRateLimits = "CREATE TABLE IF NOT EXISTS chat_rate_limits (
        bucket VARCHAR(190) NOT NULL,
        window_start INT UNSIGNED NOT NULL,
        hits INT UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (bucket, window_start),
        INDEX idx_window (window_start)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sqlRateLimits);
    echo " [+] Tabla 'chat_rate_limits' creada o verificada con éxito." . PHP_EOL;

    // 2. Tabla de Caché RAG para acelerar consultas frecuentes y reducir latencia P50
    $sqlRagCache = "CREATE TABLE IF NOT EXISTS rag_query_cache (
        query_hash CHAR(64) NOT NULL PRIMARY KEY,
        query_raw VARCHAR(255) NOT NULL,
        rag_context MEDIUMTEXT NOT NULL,
        rag_chunks JSON NULL,
        score_max DECIMAL(5,4) NULL,
        creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expira_en TIMESTAMP NOT NULL,
        INDEX idx_expira (expira_en)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    $pdo->exec($sqlRagCache);
    echo " [+] Tabla 'rag_query_cache' creada o verificada con éxito." . PHP_EOL;

    echo "✅ Migración V9 completada sin errores." . PHP_EOL;
} catch (Throwable $e) {
    echo "❌ Error en migración V9: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
