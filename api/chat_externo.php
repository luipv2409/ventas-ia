<?php
declare(strict_types=1);

/**
 * Endpoint de Integración Externa Multicanal (WhatsApp Cloud API, Meta Messenger, n8n).
 *
 * Características de Endurecimiento Empresarial:
 * 1. Autenticación Fail-Closed estricta (X-API-Key / Bearer) con comparación timing-safe.
 * 2. Bloqueo de sesiones concurrentes (SessionLock) para evitar carreras de mensajes en WhatsApp.
 * 3. Rate Limiting atómico dual (por remitente y por IP de origen).
 * 4. Memoria conversacional 100% del lado del servidor (previene inyección de historial no confiable).
 * 5. Sanitización de entradas, normalización de canales y límite de tamaño de payload (32KB).
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/chat/InputSanitizer.php';
require_once __DIR__ . '/../services/chat/DeepSeekClient.php';
require_once __DIR__ . '/../services/chat/ChatEngine.php';
require_once __DIR__ . '/../services/chat/RateLimiter.php';
require_once __DIR__ . '/../services/chat/SessionLock.php';

use Services\Chat\ChatEngine;
use Services\Chat\InputSanitizer;
use Services\Chat\RateLimiter;
use Services\Chat\SessionLock;

// ------------------------------------------------------------
// 1. Verificación GET (Healthcheck y Validación de Webhook Meta)
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $hubMode      = $_GET['hub_mode'] ?? $_GET['hub.mode'] ?? null;
    $hubChallenge = $_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? null;
    $hubToken     = (string)($_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '');

    $expectedToken = defined('WHATSAPP_VERIFY_TOKEN') && WHATSAPP_VERIFY_TOKEN !== ''
        ? WHATSAPP_VERIFY_TOKEN
        : (defined('CHAT_EXTERNAL_API_KEY') ? CHAT_EXTERNAL_API_KEY : '');

    if ($hubMode === 'subscribe' && $hubChallenge !== null) {
        if ($expectedToken !== '' && hash_equals($expectedToken, $hubToken)) {
            header('Content-Type: text/plain; charset=utf-8');
            echo $hubChallenge;
            exit;
        }

        http_response_code(403);
        echo 'Token de verificación de Meta inválido';
        exit;
    }

    echo json_encode([
        'ok'        => true,
        'servicio'  => 'API Multicanal Chatbot IA (WhatsApp, Messenger, n8n)',
        'estado'    => 'online',
        'timestamp' => date('c'),
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido. Use POST.']);
    exit;
}

// ------------------------------------------------------------
// 2. Validación de Seguridad y Autenticación Fail-Closed (API Key)
// ------------------------------------------------------------
$apiKeyEsperada = defined('CHAT_EXTERNAL_API_KEY') ? (string)CHAT_EXTERNAL_API_KEY : '';
if ($apiKeyEsperada === '') {
    http_response_code(503);
    echo json_encode([
        'ok'    => false,
        'error' => 'Servicio multicanal no configurado: defina CHAT_EXTERNAL_API_KEY en el entorno (.env)'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['HTTP_X_API_KEY'] ?? '';
$tokenRecibido = '';

if (str_starts_with($authHeader, 'Bearer ')) {
    $tokenRecibido = trim(substr($authHeader, 7));
} else {
    $tokenRecibido = trim($authHeader);
}

// Nota de seguridad: No se acepta api_key en query string para evitar filtración en logs de acceso
if ($tokenRecibido === '' || !hash_equals($apiKeyEsperada, $tokenRecibido)) {
    http_response_code(401);
    echo json_encode([
        'ok'    => false,
        'error' => 'Acceso no autorizado: API Key no válida o ausente en el encabezado X-API-Key o Authorization: Bearer'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ------------------------------------------------------------
// 3. Procesar y Sanitizar Payload JSON (Máximo 32 KB)
// ------------------------------------------------------------
$bodyRaw = file_get_contents('php://input');
if ($bodyRaw === false || strlen($bodyRaw) > 32768) {
    http_response_code(413);
    echo json_encode(['ok' => false, 'error' => 'Payload demasiado grande (máximo 32KB)']);
    exit;
}

$body = json_decode($bodyRaw, true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Cuerpo JSON malformado o vacío.']);
    exit;
}

// Admite campos flexibles para compatibilidad con n8n, Meta y otros orquestadores
$rawMensaje   = (string)($body['mensaje'] ?? $body['message'] ?? $body['pregunta'] ?? $body['text'] ?? '');
$rawRemitente = (string)($body['remitente'] ?? $body['sender'] ?? $body['user_id'] ?? $body['from'] ?? 'anonimo');
$rawCanal     = (string)($body['canal'] ?? $body['channel'] ?? 'whatsapp');
$useRag       = isset($body['use_rag']) ? (bool)$body['use_rag'] : true;

$canal = InputSanitizer::normalizeChannel($rawCanal);
if ($canal === null) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Canal no válido o no soportado.']);
    exit;
}

$remitente = InputSanitizer::cleanIdentifier($rawRemitente, 64);
if ($remitente === '') {
    $remitente = 'anonimo';
}

$mensaje = InputSanitizer::cleanText($rawMensaje, InputSanitizer::MAX_MESSAGE_CHARS);
if ($mensaje === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El campo mensaje/pregunta es obligatorio y no puede estar vacío.']);
    exit;
}

try {
    $pdo = Database::getConnection();

    // ------------------------------------------------------------
    // 4. Rate Limiting Dual (Por Remitente y por IP de Origen)
    // ------------------------------------------------------------
    $rateLimiter = new RateLimiter($pdo);

    // Límite por remitente (ej. número telefónico WhatsApp)
    $limitPerSender = defined('CHAT_RATE_LIMIT_PER_MIN') ? (int)CHAT_RATE_LIMIT_PER_MIN : 12;
    $statusSender = $rateLimiter->hit("externo:sender:{$canal}:{$remitente}", $limitPerSender, 60);
    if (!$statusSender['allowed']) {
        http_response_code(429);
        header('Retry-After: ' . $statusSender['retry_after']);
        echo json_encode([
            'ok'    => false,
            'error' => 'Demasiados mensajes consecutivos. Por favor espera unos momentos antes de enviar otro mensaje.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Límite por IP de origen (protege contra inundaciones de orquestadores o scripts)
    $clientIp = $_SERVER['HTTP_CF_CONNECTING_IP']
        ?? (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]) : null)
        ?? $_SERVER['REMOTE_ADDR']
        ?? '127.0.0.1';
    $limitIp = defined('CHAT_RATE_LIMIT_IP_PER_MIN') ? (int)CHAT_RATE_LIMIT_IP_PER_MIN : 600;
    $statusIp = $rateLimiter->hit("externo:ip:{$clientIp}", $limitIp, 60);
    if (!$statusIp['allowed']) {
        http_response_code(429);
        header('Retry-After: ' . $statusIp['retry_after']);
        echo json_encode([
            'ok'    => false,
            'error' => 'Límite de tasa por IP superado. Contacta al administrador.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ------------------------------------------------------------
    // 5. Candado de Sesión Concurrente (Previene carreras de turnos)
    // ------------------------------------------------------------
    $lock = new SessionLock($pdo, $canal, $remitente);
    if (!$lock->acquire(25)) {
        http_response_code(429);
        echo json_encode([
            'ok'    => false,
            'error' => 'El asistente aún está respondiendo tu mensaje anterior. Por favor aguarda un segundo.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $engine = new ChatEngine($pdo);

        // 6. Gestión de Memoria y Sesión
        // En canales externos, el historial SIEMPRE proviene de la BD para garantizar integridad
        $historial = $engine->obtenerHistorialSesion($canal, $remitente, 8);

        // 7. Procesamiento central del ChatEngine
        $resultado = $engine->procesarMensaje($mensaje, $historial, $useRag, [
            'canal'     => $canal,
            'remitente' => $remitente
        ]);

        // 8. Persistencia automática de la conversación para canales externos
        if ($remitente !== 'anonimo') {
            $engine->guardarMensajeSesion($canal, $remitente, 'user', $mensaje);
            $engine->guardarMensajeSesion($canal, $remitente, 'assistant', $resultado['respuesta'], [
                'acciones' => $resultado['acciones'] ?? [],
                'rag'      => $resultado['rag_applied'] ?? false
            ]);
        }

        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);

    } finally {
        $lock->release();
    }

} catch (\Throwable $e) {
    error_log('[chat_externo] Error crítico: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => 'Error interno procesando la solicitud del chat externo'
    ], JSON_UNESCAPED_UNICODE);
}
