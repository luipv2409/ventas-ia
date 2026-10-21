<?php
// Simulación de la conversación multi-turno del usuario
$mensajesConversacion = [
    "hola",
    "si compro un producto lo puedo pagar por QR?",
    "auriculares tienes?",
    "si, agrega al carrito esos auriculares"
];

$historial = [];

echo "========================================================\n";
echo "🧪 SIMULACIÓN DE CONVERSACIÓN MULTI-TURNO CON DEEPSEEK\n";
echo "========================================================\n\n";

foreach ($mensajesConversacion as $idx => $pregunta) {
    echo "👤 CLIENTE (" . ($idx + 1) . "): \"$pregunta\"\n";

    $payload = json_encode([
        'pregunta'  => $pregunta,
        'historial' => $historial,
        'use_rag'   => true
    ]);

    $ch = curl_init('http://localhost:8085/api/chat_frontend.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 20
    ]);
    $resp = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($resp, true);
    $respuestaBot = $data['respuesta'] ?? 'Error';
    $acciones = $data['acciones'] ?? [];

    echo "🤖 ASESOR: $respuestaBot\n";
    if (!empty($acciones)) {
        echo "   [Acciones emitidas: " . json_encode($acciones, JSON_UNESCAPED_UNICODE) . "]\n";
    }
    echo "--------------------------------------------------------\n";

    // Simular memoria de chat.js
    $historial[] = ['role' => 'user', 'content' => $pregunta];
    $historial[] = ['role' => 'assistant', 'content' => $respuestaBot];
}
