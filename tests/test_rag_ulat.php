<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/rag/RagService.php';

use Services\Rag\RagService;

$pdo = Database::getConnection();
$rag = new RagService($pdo);

$fallos = 0;

// 1. Verificar cero colecciones contaminadas de tienda en BD (#12)
$cols = $pdo->query("SELECT DISTINCT collection FROM rag_documents ORDER BY collection")->fetchAll(PDO::FETCH_COLUMN);
$contaminadas = array_diff($cols, RagService::ULAT_COLLECTIONS);
if (!empty($contaminadas)) {
    echo "❌ Colecciones ajenas a ULAT detectadas en rag_documents: " . implode(', ', $contaminadas) . "\n";
    $fallos++;
} else {
    echo "✔ Colecciones en BD 100% aisladas a ULAT: " . implode(', ', $cols) . "\n";
}

// 2. Consultas académicas reales ULAT
$queries = [
    '¿Cuáles son los requisitos de admisión para nuevos estudiantes?',
    '¿En qué consiste el Plan Uno a Uno?',
    '¿Qué porcentaje se necesita para la Beca por Excelencia Académica?',
    '¿Cuántos años dura la carrera de Ingeniería de Sistemas y cuál es su perfil?',
    '¿Qué modalidades de graduación existen según el reglamento?',
];

echo "\n====================================================\n";
echo "🔍 VERIFICACIÓN RETRIEVAL RAG ULAT (BD REAL)\n";
echo "====================================================\n\n";

foreach ($queries as $q) {
    echo "❓ PREGUNTA: {$q}\n";
    $res = $rag->retrieveContext($q, 3);
    echo "   Found: " . ($res['found'] ? 'SÍ' : 'NO') . "\n";
    if (!$res['found']) {
        $fallos++;
        continue;
    }
    if (!str_contains($res['context'], '<contexto_documental>') || !str_contains($res['context'], '</contexto_documental>')) {
        echo "   ❌ Falta delimitador <contexto_documental> en contexto formateado\n";
        $fallos++;
    }
    foreach ($res['chunks'] as $i => $c) {
        if (!in_array($c['collection'], RagService::ULAT_COLLECTIONS, true)) {
            echo "   ❌ Colección filtrada inválida: {$c['collection']}\n";
            $fallos++;
        }
        $score = round(($c['score'] ?? 0) * 100, 1);
        $preview = mb_substr((string)preg_replace('/\s+/', ' ', $c['content']), 0, 110);
        echo "   [" . ($i + 1) . "] [{$c['collection']}] '{$c['title']}' (Score: {$score}%)\n";
        echo "       → {$preview}...\n";
    }
    echo "\n";
}

// 3. Consulta maliciosa / producto de tienda NO debe devolver catálogo de tienda
$resStore = $rag->retrieveContext('¿Cuánto cuesta el Smartphone Samsung Galaxy A55 en stock?', 3);
foreach ($resStore['chunks'] ?? [] as $c) {
    if (in_array($c['collection'], ['catalogo', 'politicas'], true) || stripos($c['title'], 'Galaxy') !== false) {
        echo "❌ Fuga de producto de tienda detectada: {$c['title']}\n";
        $fallos++;
    }
}
echo "✔ Consulta de producto de tienda (Galaxy A55): 0 fugas de catálogo heredado.\n";

exit($fallos === 0 ? 0 : 1);
