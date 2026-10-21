<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=== MIGRACIÓN V7: OBSERVABILIDAD Y TELEMETRÍA DEL CHATBOT ===" . PHP_EOL;

try {
    $pdo = Database::getConnection();

    // Obtener columnas existentes en chat_logs
    $stmt = $pdo->query("SHOW COLUMNS FROM chat_logs");
    $columnasExistentes = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $columnasNuevas = [
        'latencia_ms'       => "INT UNSIGNED NULL COMMENT 'Tiempo de respuesta total en milisegundos'",
        'tokens_prompt'     => "INT UNSIGNED NULL COMMENT 'Tokens de entrada enviados al modelo'",
        'tokens_completado' => "INT UNSIGNED NULL COMMENT 'Tokens generados por el modelo'",
        'tokens_cache_hit'  => "INT UNSIGNED NULL DEFAULT 0 COMMENT 'Tokens servidos desde caché de DeepSeek'",
        'rag_score_max'     => "DECIMAL(5,4) NULL COMMENT 'Puntuación máxima de relevancia RAG recuperada'",
        'rag_fuentes'       => "VARCHAR(255) NULL COMMENT 'Fuentes y fragmentos consultados'",
        'acciones_json'     => "TEXT NULL COMMENT 'Acciones emitidas y validadas por el bot'",
    ];

    foreach ($columnasNuevas as $columna => $definicion) {
        if (!in_array($columna, $columnasExistentes, true)) {
            $sql = "ALTER TABLE chat_logs ADD COLUMN `{$columna}` {$definicion}";
            $pdo->exec($sql);
            echo " [+] Columna agregada a chat_logs: {$columna}" . PHP_EOL;
        } else {
            echo " [.] Columna ya existe en chat_logs: {$columna}" . PHP_EOL;
        }
    }

    echo "✅ Migración V7 completada con éxito." . PHP_EOL;
} catch (Throwable $e) {
    echo "❌ Error en migración V7: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
