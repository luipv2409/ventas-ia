<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/rag/RagService.php';

use Services\Rag\RagService;

$pdo = Database::getConnection();
$rag = new RagService($pdo);

$queries = [
    '¿Cuáles son los requisitos de admisión para nuevos estudiantes?',
    '¿En qué consiste el Plan Uno a Uno?',
    '¿Qué porcentaje se necesita para la Beca por Excelencia Académica?',
    '¿Cuántos años dura la carrera de Ingeniería de Sistemas y cuál es su perfil?',
    '¿Qué modalidades de graduación existen según el reglamento?'
];

echo "====================================================\n";
echo "🔍 VERIFICACIÓN RETRIEVAL RAG ULAT\n";
echo "====================================================\n\n";

foreach ($queries as $q) {
    echo "❓ PREGUNTA: $q\n";
    $res = $rag->retrieveContext($q, 3);
    echo "   Found: " . ($res['found'] ? 'SÍ' : 'NO') . "\n";
    if ($res['found']) {
        foreach ($res['chunks'] as $i => $c) {
            $score = round(($c['score'] ?? 0) * 100, 1);
            $preview = substr(preg_replace('/\s+/', ' ', $c['content']), 0, 120);
            echo "   [" . ($i + 1) . "] [{$c['collection']}] '{$c['title']}' (Score: {$score}%)\n";
            echo "       → {$preview}...\n";
        }
    }
    echo "\n";
}
