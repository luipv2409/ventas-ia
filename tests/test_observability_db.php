<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getConnection();
$stmt = $pdo->query("SELECT id, pregunta, latencia_ms, tokens_prompt, tokens_completado, rag_score_max, rag_fuentes, acciones_json FROM chat_logs ORDER BY id DESC LIMIT 2");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== VERIFICACIÓN: TELEMETRÍA EN chat_logs ===\n";
foreach ($rows as $r) {
    echo "ID: {$r['id']} | Pregunta: '{$r['pregunta']}'\n";
    echo "  -> Latencia: {$r['latencia_ms']} ms\n";
    echo "  -> Tokens Prompt: {$r['tokens_prompt']} | Tokens Completado: {$r['tokens_completado']}\n";
    echo "  -> RAG Max Score: {$r['rag_score_max']} | Fuentes: {$r['rag_fuentes']}\n";
    echo "  -> Acciones: {$r['acciones_json']}\n\n";
}

if (!empty($rows)) {
    echo "✔ REGISTROS DE OBSERVABILIDAD VERIFICADOS EN BASE DE DATOS.\n";
    exit(0);
} else {
    echo "❌ Sin registros de observabilidad.\n";
    exit(1);
}
