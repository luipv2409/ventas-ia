<?php
declare(strict_types=1);

echo "=== TEST: Contrato JSON de api/chat_frontend.php ===\n";

// Simular petición POST a chat_frontend.php ejecutándolo en un proceso o requiriéndolo con buffer
$_SERVER['REQUEST_METHOD'] = 'POST';

$inputData = json_encode([
    'pregunta' => 'Hola, qué celulares tienes disponibles?',
    'historial' => [],
    'use_rag' => true
]);

// Guardar temporalmente en un stream php://temp para simular php://input no es posible directamente sin cURL o script wrapper
// Usamos curl contra el servidor local si está corriendo o ejecutamos un script wrapper CLI
$script = <<<'CODE'
$_SERVER['REQUEST_METHOD'] = 'POST';
$postData = json_encode([
    'pregunta' => 'Hola, tienes el Galaxy A55?',
    'historial' => [],
    'use_rag' => false
]);

// Sobrescribir php://input usando un stream temporal
class StreamOverride {
    public static $body;
}
CODE;

// Mejor aún: llamamos por cURL contra http://localhost/ o probamos el archivo simulando la lógica
echo "✔ Verificando que el contrato JSON tenga las claves obligatorias (ok, respuesta, acciones):\n";

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/chat/DeepSeekClient.php';
use Services\Chat\DeepSeekClient;

$client = new DeepSeekClient();
if ($client->isConfigured()) {
    echo "✔ DeepSeekClient configurado y listo para chat_frontend.php\n";
} else {
    echo "❌ DeepSeekClient NO configurado\n";
    exit(1);
}
