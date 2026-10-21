<?php
require_once __DIR__ . '/../config/config.php';

echo "Probando llamada directa a DeepSeek:\n";
echo "URL: " . DEEPSEEK_API_URL . "\n";
echo "Modelo: " . DEEPSEEK_MODEL . "\n";
echo "Key: " . substr(DEEPSEEK_API_KEY, 0, 8) . "...\n";

$ch = curl_init(DEEPSEEK_API_URL);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode([
        'model' => DEEPSEEK_MODEL,
        'messages' => [
            ['role' => 'user', 'content' => 'Di: Hola mundo']
        ]
    ]),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . DEEPSEEK_API_KEY,
    ],
    CURLOPT_TIMEOUT        => 15
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlErr = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Curl Err: $curlErr\n";
echo "Response: $response\n";
