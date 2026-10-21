<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/chat/DeepSeekClient.php';

use Services\Chat\DeepSeekClient;

echo "=== TEST: DeepSeekClient (Fase 1) ===\n";

$client = new DeepSeekClient();
echo "Configurado: " . ($client->isConfigured() ? "SÍ" : "NO") . "\n";
echo "Modelo: " . $client->getModel() . "\n";

$messages = [
    ['role' => 'system', 'content' => 'Eres un asistente JSON. Responde siempre con un objeto JSON: {"saludo": "hola"}'],
    ['role' => 'user', 'content' => 'Saluda']
];

$result = $client->chat($messages, ['json_mode' => true, 'max_tokens' => 50]);

echo "Resultado OK: " . ($result['ok'] ? "SÍ" : "NO") . "\n";
echo "HTTP Code: " . $result['http_code'] . "\n";
echo "Latencia: " . $result['latencia_ms'] . "ms\n";
echo "Tokens usados: " . $result['usage']['total_tokens'] . " (Cache hit: " . $result['usage']['cache_hit_tokens'] . ")\n";
echo "JSON Parseado: " . json_encode($result['json'], JSON_UNESCAPED_UNICODE) . "\n";

if ($result['ok'] && is_array($result['json'])) {
    echo "✔ PASÓ LA PRUEBA EXITOSAMENTE.\n";
    exit(0);
} else {
    echo "⚠ Error o respuesta no JSON: " . ($result['error'] ?? $result['content']) . "\n";
    exit(1);
}
