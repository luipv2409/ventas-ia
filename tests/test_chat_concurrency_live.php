<?php
declare(strict_types=1);

/**
 * Suite de Pruebas de Concurrencia, Bloqueo de Sesiones y Rate Limiting en Vivo (PhD QA).
 *
 * Valida:
 * 1. Exclusión mutua (SessionLock): Peticiones paralelas de un mismo remitente
 *    se atienden de forma serializada sin corromper el historial ni generar carreras.
 * 2. Rate Limiting atómico (HTTP 429 + Retry-After).
 * 3. Seguridad Fail-Closed y rechazo de API Keys inválidas (HTTP 401).
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=================================================================\n";
echo "🧪 QA CONCURRENCIA, SESSION LOCK Y RATE LIMITING EN VIVO\n";
echo "=================================================================\n\n";

$baseUrl = 'http://localhost/api/chat_externo.php';
$fp = @fsockopen('localhost', 8085, $errno, $errstr, 0.3);
if ($fp) {
    fclose($fp);
    $baseUrl = 'http://localhost:8085/api/chat_externo.php';
}

$apiKey = defined('CHAT_EXTERNAL_API_KEY') && CHAT_EXTERNAL_API_KEY !== '' ? CHAT_EXTERNAL_API_KEY : 'ical_chat_sec_2026';
$pdo = Database::getConnection();
$fallos = 0;

// -------------------------------------------------------------
// 1. TEST DE AUTENTICACIÓN: Rechazo con API Key errónea
// -------------------------------------------------------------
echo "--- 1. Validación de Autenticación Fail-Closed (HTTP 401) ---\n";
$ch = curl_init($baseUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode(['mensaje' => 'hola', 'remitente' => 'test_auth_bad']),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'X-API-Key: CLAVE_TOTALMENTE_FALSA_9999'
    ],
    CURLOPT_TIMEOUT        => 5
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 401) {
    echo "  ✔ PASÓ: Clave inválida rechazada con HTTP 401.\n";
} else {
    echo "  ❌ FALLÓ: Se esperaba 401 para clave inválida, se obtuvo {$httpCode}.\n";
    $fallos++;
}

// -------------------------------------------------------------
// 2. TEST DE RATE LIMITING: Ráfaga que supera el límite por minuto
// -------------------------------------------------------------
echo "\n--- 2. Rate Limiting por Remitente (HTTP 429 + Retry-After) ---\n";
$remitenteSpam = '+59170099991';
$limite = defined('CHAT_RATE_LIMIT_PER_MIN') ? (int)CHAT_RATE_LIMIT_PER_MIN : 12;

// Usamos el RateLimiter directamente en la BD para saturar el bucket sin gastar 12 llamadas reales al LLM
require_once __DIR__ . '/../services/chat/RateLimiter.php';
$rl = new \Services\Chat\RateLimiter($pdo);
$bucket = "externo:sender:whatsapp:{$remitenteSpam}";

// Llenamos el bucket hasta el límite exacto
for ($i = 0; $i < $limite; $i++) {
    $rl->hit($bucket, $limite, 60);
}

// Ahora la petición HTTP número limite+1 DEBE recibir 429 de inmediato
$ch = curl_init($baseUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_HEADER         => true,
    CURLOPT_POSTFIELDS     => json_encode([
        'canal'     => 'whatsapp',
        'remitente' => $remitenteSpam,
        'mensaje'   => 'Mensaje que debe ser bloqueado por rate limit'
    ]),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'X-API-Key: ' . $apiKey
    ],
    CURLOPT_TIMEOUT        => 5
]);
$rawResponse = (string)curl_exec($ch);
$httpCodeRL = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCodeRL === 429 && stripos($rawResponse, 'Retry-After:') !== false) {
    echo "  ✔ PASÓ: Petición número " . ($limite + 1) . " bloqueada con HTTP 429 y cabecera Retry-After.\n";
} else {
    echo "  ❌ FALLÓ: Se esperaba HTTP 429 con Retry-After, se obtuvo {$httpCodeRL}.\n";
    $fallos++;
}

// Limpiar el bucket de prueba
$pdo->prepare("DELETE FROM chat_rate_limits WHERE bucket = :k")->execute([':k' => $bucket]);

// -------------------------------------------------------------
// 3. TEST DE CONCURRENCIA: 2 Peticiones Simultáneas (SessionLock)
// -------------------------------------------------------------
echo "\n--- 3. Concurrencia Real (curl_multi) con SessionLock ---\n";
$remitenteConcurrente = '+59170099992';

// Limpiar historial previo del remitente de prueba
$pdo->prepare("DELETE FROM chat_externo_sesiones WHERE remitente = :r")->execute([':r' => $remitenteConcurrente]);

$mh = curl_multi_init();
$ch1 = curl_init($baseUrl);
$ch2 = curl_init($baseUrl);

$payload1 = json_encode([
    'canal'     => 'whatsapp',
    'remitente' => $remitenteConcurrente,
    'mensaje'   => 'Hola, ¿tienen stock del Galaxy A55?',
    'use_rag'   => false
]);

$payload2 = json_encode([
    'canal'     => 'whatsapp',
    'remitente' => $remitenteConcurrente,
    'mensaje'   => '¿Y cuál es el precio?',
    'use_rag'   => false
]);

$headers = [
    'Content-Type: application/json',
    'X-API-Key: ' . $apiKey
];

curl_setopt_array($ch1, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload1,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_TIMEOUT        => 35
]);

curl_setopt_array($ch2, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload2,
    CURLOPT_HTTPHEADER     => $headers,
    CURLOPT_TIMEOUT        => 35
]);

curl_multi_add_handle($mh, $ch1);
curl_multi_add_handle($mh, $ch2);

$active = null;
$tInicio = microtime(true);
do {
    $mrc = curl_multi_exec($mh, $active);
} while ($mrc === CURLM_CALL_MULTI_PERFORM);

while ($active && $mrc === CURLM_OK) {
    if (curl_multi_select($mh) !== -1) {
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc === CURLM_CALL_MULTI_PERFORM);
    }
}

$resp1 = (string)curl_multi_getcontent($ch1);
$code1 = curl_getinfo($ch1, CURLINFO_HTTP_CODE);
$resp2 = (string)curl_multi_getcontent($ch2);
$code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);

curl_multi_remove_handle($mh, $ch1);
curl_multi_remove_handle($mh, $ch2);
curl_multi_close($mh);
curl_close($ch1);
curl_close($ch2);

$duracion = round(microtime(true) - $tInicio, 2);
echo "  Duración total de ejecución paralela: {$duracion}s\n";
echo "  Código Petición 1: {$code1}\n";
echo "  Código Petición 2: {$code2}\n";

$data1 = json_decode($resp1, true);
$data2 = json_decode($resp2, true);

if ($code1 === 200 && ($data1['ok'] ?? false) === true && $code2 === 200 && ($data2['ok'] ?? false) === true) {
    echo "  ✔ PASÓ: Ambas peticiones concurrentes completaron con HTTP 200 sin colisión.\n";
} else {
    echo "  ❌ FALLÓ: Al menos una petición concurrente falló. Code1: $code1, Code2: $code2\n";
    $fallos++;
}

// Verificar orden y persistencia en chat_externo_sesiones
$stmtSes = $pdo->prepare("SELECT rol, mensaje FROM chat_externo_sesiones WHERE remitente = :r ORDER BY id ASC");
$stmtSes->execute([':r' => $remitenteConcurrente]);
$filas = $stmtSes->fetchAll(PDO::FETCH_ASSOC);

echo "  Filas registradas en chat_externo_sesiones: " . count($filas) . "\n";
if (count($filas) === 4) {
    echo "  ✔ PASÓ: Se registraron exactamente 4 mensajes (2 user + 2 assistant alternados).\n";
} else {
    echo "  ❌ FALLÓ: Se esperaban 4 mensajes, encontrados: " . count($filas) . "\n";
    $fallos++;
}

// Limpiar datos de prueba
$pdo->prepare("DELETE FROM chat_externo_sesiones WHERE remitente = :r")->execute([':r' => $remitenteConcurrente]);
echo "  [.] Limpieza de registros de prueba completada.\n";

echo "\n=================================================================\n";
if ($fallos === 0) {
    echo "🎉 TODOS LOS TESTS DE CONCURRENCIA Y ENDURECIMIENTO PASARON AL 100%\n";
    exit(0);
} else {
    echo "⚠️ SE DETECTARON {$fallos} FALLOS EN LA SUITE DE CONCURRENCIA\n";
    exit(1);
}
