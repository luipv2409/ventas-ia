<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}


// Test simulation of chat_frontend API
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';

$payload = [
    'pregunta' => '¿Qué becas tienen y qué requisitos piden para postular a Odontología?',
    'historial' => [],
    'use_rag' => true
];

$rawJson = json_encode($payload);

// We can test ChatEngine directly as chat_frontend does
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/chat/ChatEngine.php';
require_once __DIR__ . '/../services/chat/DeepSeekClient.php';

use Services\Chat\ChatEngine;
use Services\Chat\DeepSeekClient;

$pdo = Database::getConnection();
$engine = new ChatEngine($pdo, new DeepSeekClient());

$res = $engine->procesarPregunta($payload['pregunta'], $payload['historial'], [
    'canal' => 'web',
    'sessionId' => 'test-frontend-session',
    'use_rag' => true
]);

echo "====================================================\n";
echo "🤖 TEST CONTRATO API FRONTEND ULAT\n";
echo "====================================================\n";
echo "OK: true\n";
echo "Respuesta: " . $res['respuesta'] . "\n";
echo "Acciones: " . json_encode($res['acciones'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
echo "RAG Applied: " . ($res['rag_applied'] ? 'SÍ' : 'NO') . "\n";

