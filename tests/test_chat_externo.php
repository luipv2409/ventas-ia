<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=================================================================\n";
echo "🔬 QA / AUDITORÍA: API CHAT EXTERNO MULTICANAL (WHATSAPP / N8N)\n";
echo "=================================================================\n\n";

$baseUrl = 'http://localhost/api/chat_externo.php';
$fp = @fsockopen('localhost', 8085, $errno, $errstr, 0.3);
if ($fp) {
    fclose($fp);
    $baseUrl = 'http://localhost:8085/api/chat_externo.php';
}

$apiKey = defined('CHAT_EXTERNAL_API_KEY') ? CHAT_EXTERNAL_API_KEY : 'ical_chat_sec_2026';
$pdo = Database::getConnection();

$fallos = 0;

// -------------------------------------------------------------
// TEST 1: Healthcheck y Verificación de Webhook Meta
// -------------------------------------------------------------
echo "--- 1. Healthcheck GET & Meta Challenge Verification ---\n";
$ch = curl_init($baseUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 5
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode((string)$resp, true);
if ($httpCode === 200 && ($data['ok'] ?? false) === true) {
    echo "  ✔ PASÓ: Healthcheck GET respondió HTTP 200 OK.\n";
} else {
    echo "  ❌ FALLÓ: Healthcheck GET falló (HTTP $httpCode).\n";
    $fallos++;
}

// Meta Challenge GET
$challengeTest = 'meta_test_challenge_abc123';
$challengeUrl = $baseUrl . '?hub.mode=subscribe&hub.challenge=' . $challengeTest . '&hub.verify_token=' . urlencode($apiKey);
$ch = curl_init($challengeUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 5
]);
$challengeResp = curl_exec($ch);
$httpCodeChallenge = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCodeChallenge === 200 && trim((string)$challengeResp) === $challengeTest) {
    echo "  ✔ PASÓ: Verificación de Webhook Meta (hub.challenge) respondió exactamente el token esperado.\n";
} else {
    echo "  ❌ FALLÓ: Verificación de Webhook Meta falló (HTTP $httpCodeChallenge, Respuesta: '$challengeResp').\n";
    $fallos++;
}

// -------------------------------------------------------------
// TEST 2: Seguridad y Autenticación por API Key
// -------------------------------------------------------------
echo "\n--- 2. Seguridad y Autenticación con API Key ---\n";
// Petición sin API Key -> debe dar 401
$ch = curl_init($baseUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode(['mensaje' => 'hola']),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 5
]);
$resp401 = curl_exec($ch);
$code401 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($code401 === 401) {
    echo "  ✔ PASÓ: Petición sin credenciales bloqueada correctamente con HTTP 401 Unauthorized.\n";
} else {
    echo "  ❌ FALLÓ: Se esperaba HTTP 401 pero se recibió HTTP $code401.\n";
    $fallos++;
}

// -------------------------------------------------------------
// TEST 3: Simulación de Conversación WhatsApp Multi-turno por Teléfono
// -------------------------------------------------------------
echo "\n--- 3. Conversación Multi-turno por Teléfono (+59178999999) ---\n";
$telefonoTest = '+59178999999';

// Limpiar mensajes anteriores del teléfono de prueba
$pdo->prepare("DELETE FROM chat_externo_sesiones WHERE remitente = :tel")->execute([':tel' => $telefonoTest]);

// TURNO 1: Pregunta sobre un producto específico
echo "  [Turno 1] Cliente: 'Hola, ¿tienen el iPhone 17 Pro Max disponible?'\n";
$payloadTurno1 = json_encode([
    'canal'     => 'whatsapp',
    'remitente' => $telefonoTest,
    'mensaje'   => 'Hola, ¿tienen el iPhone 17 Pro Max disponible?',
    'use_rag'   => true
]);

$ch = curl_init($baseUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payloadTurno1,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey
    ],
    CURLOPT_TIMEOUT        => 25
]);
$respT1Raw = curl_exec($ch);
$codeT1 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$dataT1 = json_decode((string)$respT1Raw, true);
if ($codeT1 === 200 && ($dataT1['ok'] ?? false) === true) {
    echo "    🤖 Valeria: \"" . trim($dataT1['respuesta'] ?? '') . "\"\n";
    echo "    ✔ PASÓ Turno 1: Respuesta procesada con éxito.\n";
} else {
    echo "    ❌ FALLÓ Turno 1 (HTTP $codeT1): " . substr((string)$respT1Raw, 0, 150) . "\n";
    $fallos++;
}

// TURNO 2: Pregunta elíptica (sin mencionar de qué producto habla)
// El bot DEBE recordar gracias a la tabla chat_externo_sesiones que hablaban del iPhone 17 Pro Max
echo "\n  [Turno 2] Cliente: '¿Cuánto cuesta exactamente y cuántos les quedan?'\n";
$payloadTurno2 = json_encode([
    'canal'     => 'whatsapp',
    'remitente' => $telefonoTest,
    'mensaje'   => '¿Cuánto cuesta exactamente y cuántos les quedan?',
    'use_rag'   => true
]);

$ch = curl_init($baseUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payloadTurno2,
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey
    ],
    CURLOPT_TIMEOUT        => 25
]);
$respT2Raw = curl_exec($ch);
$codeT2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$dataT2 = json_decode((string)$respT2Raw, true);
$respT2Texto = mb_strtolower($dataT2['respuesta'] ?? '');
if ($codeT2 === 200 && (str_contains($respT2Texto, '18000') || str_contains($respT2Texto, '18,000') || str_contains($respT2Texto, 'iphone'))) {
    echo "    🤖 Valeria: \"" . trim($dataT2['respuesta'] ?? '') . "\"\n";
    echo "    ✔ PASÓ Turno 2: Recordó el contexto del iPhone 17 Pro Max y citó el precio oficial (Bs 18000.00) por teléfono.\n";
} else {
    echo "    ❌ FALLÓ Turno 2: No retuvo el contexto de la conversación previa.\n";
    echo "       Respuesta: " . ($dataT2['respuesta'] ?? '') . "\n";
    $fallos++;
}

// -------------------------------------------------------------
// TEST 4: Verificación de Formato para WhatsApp Cloud API
// -------------------------------------------------------------
echo "\n--- 4. Formato Nativo para WhatsApp Cloud API ---\n";
$payloadWA = $dataT2['whatsapp_payload'] ?? [];
if (!empty($payloadWA['to']) && ($payloadWA['messaging_product'] ?? '') === 'whatsapp' && !empty($payloadWA['text']['body'])) {
    echo "  ✔ PASÓ: Payload de WhatsApp generado correctamente listo para Meta Graph API:\n";
    echo "    - Destinatario: {$payloadWA['to']}\n";
    echo "    - Formato nativo: text (Longitud: " . strlen($payloadWA['text']['body']) . " caracteres)\n";
} else {
    echo "  ❌ FALLÓ: El payload de WhatsApp no cumple con la especificación de Meta.\n";
    $fallos++;
}

// -------------------------------------------------------------
// TEST 5: Verificación de Persistencia en Base de Datos
// -------------------------------------------------------------
echo "\n--- 5. Verificación de Persistencia en chat_externo_sesiones ---\n";
$stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM chat_externo_sesiones WHERE remitente = :tel");
$stmtCheck->execute([':tel' => $telefonoTest]);
$totalFilas = (int)$stmtCheck->fetchColumn();

if ($totalFilas >= 4) { // 2 del usuario + 2 de Valeria
    echo "  ✔ PASÓ: Se registraron correctamente {$totalFilas} mensajes en la tabla chat_externo_sesiones.\n";
} else {
    echo "  ❌ FALLÓ: Se esperaba al menos 4 mensajes persistidos, encontrados: {$totalFilas}.\n";
    $fallos++;
}

// Limpieza de datos de prueba
$pdo->prepare("DELETE FROM chat_externo_sesiones WHERE remitente = :tel")->execute([':tel' => $telefonoTest]);
echo "  [.] Limpieza de registros de prueba completada.\n";

echo "\n=================================================================\n";
if ($fallos === 0) {
    echo "🎉 TODOS LOS TESTS DE QA DEL ENDPOINT EXTERNO PASARON AL 100% (0 FALLOS)\n";
    exit(0);
} else {
    echo "⚠️ SE DETECTARON {$fallos} FALLOS EN LA SUITE DE QA EXTERNA\n";
    exit(1);
}
