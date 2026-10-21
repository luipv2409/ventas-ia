<?php
// ============================================================
// ARCHIVO: diagnostico.php
// PROPÓSITO: Detectar qué está fallando en ia_handler.php
// ⚠️ Borrar este archivo antes de producción
// ============================================================
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "<h2>🔍 Diagnóstico del Sistema</h2><pre>";

// 1. Versión de PHP
echo "✅ PHP versión: " . PHP_VERSION . "\n";

// 2. Extensión cURL (necesaria para llamar a la API de IA)
echo (extension_loaded('curl'))
    ? "✅ cURL: habilitado\n"
    : "❌ cURL: DESHABILITADO — Actívalo en php.ini\n";

// 3. Extensión OpenSSL (para conexiones HTTPS)
echo (extension_loaded('openssl'))
    ? "✅ OpenSSL: habilitado\n"
    : "❌ OpenSSL: DESHABILITADO — Actívalo en php.ini\n";

// 4. Conexión a la BD
try {
    $pdo = Database::getConnection();
    echo "✅ Conexión BD: OK\n";
} catch (Exception $e) {
    echo "❌ Conexión BD: " . $e->getMessage() . "\n";
}

// 5. Constantes definidas correctamente
echo "\n--- Constantes de Config ---\n";
echo "APP_AI_PROVIDER : " . APP_AI_PROVIDER . "\n";
echo "GROQ_API_KEY    : " . substr(GROQ_API_KEY, 0, 10) . "...[oculto]\n";
echo "GROQ_MODEL      : " . GROQ_MODEL . "\n";
echo "GROQ_API_URL    : " . GROQ_API_URL . "\n";

// 6. Prueba de cURL hacia Groq (sin enviar pregunta real)
echo "\n--- Test de conectividad con API Groq ---\n";
if (extension_loaded('curl')) {
    $ch = curl_init(GROQ_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode([
            'model'    => GROQ_MODEL,
            'messages' => [
                ['role' => 'user', 'content' => 'Di solo: OK']
            ],
            'max_tokens' => 5,
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GROQ_API_KEY,
        ],
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false, // Para debug local
        CURLOPT_SSL_VERIFYHOST => false,
    ]);

    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        echo "❌ Error cURL: $curlError\n";
    } else {
        echo "HTTP Status : $httpCode\n";
        $data = json_decode($response, true);
        if ($httpCode === 200 && isset($data['choices'])) {
            echo "✅ API Groq respondió: " . $data['choices'][0]['message']['content'] . "\n";
        } else {
            echo "❌ Respuesta inesperada:\n$response\n";
        }
    }
} else {
    echo "❌ No se puede probar: cURL no está disponible.\n";
}

echo "</pre>";
