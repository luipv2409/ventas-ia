<?php
declare(strict_types=1);

/**
 * Endpoint de Chat Frontend — Portal Universitario ULAT.
 *
 * Controles activos:
 *  - Cabeceras HTTP de seguridad y cookies de sesión HttpOnly/SameSite.
 *  - Resolución de IP anti-spoofing gobernada por TRUSTED_PROXY_CIDRS.
 *  - Rate Limiting dual por minuto y por día (anti Denial-of-Wallet).
 *  - Historial multi-turno canónico en sesión del servidor (impide inyectar turnos assistant falsos).
 *  - Anonimización de session_id con HMAC-SHA256 antes de registrar telemetría en chat_logs.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

use Services\Chat\ChatEngine;
use Services\Chat\ClientIpResolver;
use Services\Chat\DeepSeekClient;
use Services\Chat\InputSanitizer;
use Services\Chat\RateLimiter;
use Services\Security\SecurityKernel;

SecurityKernel::applySecurityHeaders('api');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || strlen($rawBody) > 32768) {
    http_response_code(413);
    echo json_encode(['ok' => false, 'error' => 'Payload demasiado grande (máximo 32KB)']);
    exit;
}

$body = json_decode($rawBody, true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON inválido']);
    exit;
}

// Reinicio limpio de conversación en sesión del servidor
if (!empty($body['reset'])) {
    SecurityKernel::startSession(false);
    $_SESSION['ulat_chat_history'] = [];
    session_write_close();
    echo json_encode(['ok' => true]);
    exit;
}

$pregunta = trim((string)($body['pregunta'] ?? ''));
if ($pregunta === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Pregunta vacía']);
    exit;
}

$isStreaming = (bool)($body['stream'] ?? ($_GET['stream'] ?? false));
$useRag      = isset($body['use_rag']) ? (bool)$body['use_rag'] : true;

try {
    $pdo = Database::getConnection();

    // 1. Resolución segura de IP real + Rate Limiting dual (por minuto y cuota diaria)
    $clientIp = ClientIpResolver::resolve();
    $rateLimiter = new RateLimiter($pdo);

    $limitMin = defined('CHAT_RATE_LIMIT_FRONTEND_PER_MIN') ? (int)CHAT_RATE_LIMIT_FRONTEND_PER_MIN : 15;
    $statusMin = $rateLimiter->hit("frontend:min:{$clientIp}", $limitMin, 60);
    if (!$statusMin['allowed']) {
        http_response_code(429);
        header('Retry-After: ' . $statusMin['retry_after']);
        echo json_encode([
            'ok'    => false,
            'error' => 'Has enviado muchas consultas seguidas. Espera unos segundos antes de continuar.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $limitDay = defined('CHAT_RATE_LIMIT_FRONTEND_PER_DAY') ? (int)CHAT_RATE_LIMIT_FRONTEND_PER_DAY : 150;
    $statusDay = $rateLimiter->hit("frontend:day:{$clientIp}", $limitDay, 86400);
    if (!$statusDay['allowed']) {
        http_response_code(429);
        header('Retry-After: ' . $statusDay['retry_after']);
        echo json_encode([
            'ok'    => false,
            'error' => 'Alcanzaste el límite diario de consultas automatizadas. Escríbenos directo al WhatsApp 79795778.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. Sesión endurecida + historial canónico del servidor + hash HMAC de session_id
    SecurityKernel::startSession(false);
    $rawSessionId = session_id() ?: ('anon-' . bin2hex(random_bytes(8)));
    $hashedSessionId = 'sid_' . substr(hash_hmac('sha256', $rawSessionId, APP_SECRET_KEY), 0, 32);

    $historialServidor = is_array($_SESSION['ulat_chat_history'] ?? null) ? $_SESSION['ulat_chat_history'] : [];

    // Si la sesión PHP expiró pero el navegador tenía mensajes previos del usuario, solo rescatamos turnos 'user'
    // jamás turnos 'assistant' enviados desde el cliente (previene falsificación de respuestas del asistente).
    if (empty($historialServidor) && !empty($body['historial']) && is_array($body['historial'])) {
        foreach (array_slice($body['historial'], -6) as $turn) {
            if (is_array($turn) && ($turn['role'] ?? '') === 'user' && !empty($turn['content'])) {
                $cleanUserTurn = InputSanitizer::cleanText((string)$turn['content'], 600);
                if ($cleanUserTurn !== '' && $cleanUserTurn !== $pregunta) {
                    $historialServidor[] = ['role' => 'user', 'content' => $cleanUserTurn];
                }
            }
        }
    }
    session_write_close();

    // 3. Ejecución en ChatEngine (sin bloquear el archivo de sesión durante la espera de red)
    $llmClient = new DeepSeekClient();
    $engine    = new ChatEngine($pdo, $llmClient);

    $resultado = $engine->procesarPregunta($pregunta, $historialServidor, [
        'canal'     => 'web',
        'remitente' => $hashedSessionId,
        'sessionId' => $hashedSessionId,
        'use_rag'   => $useRag,
    ]);

    // 4. Persistir el turno real validado en la sesión del servidor (omitir si hubo fallo degradado de red/IA)
    if (empty($resultado['degraded'])) {
        SecurityKernel::startSession(false);
        $histActual = is_array($_SESSION['ulat_chat_history'] ?? null) ? $_SESSION['ulat_chat_history'] : [];
        $histActual[] = ['role' => 'user', 'content' => InputSanitizer::cleanText($pregunta, 800)];
        $histActual[] = ['role' => 'assistant', 'content' => (string)$resultado['respuesta']];
        $_SESSION['ulat_chat_history'] = array_slice($histActual, -14);
        session_write_close();
    }

    // 5. Streaming opcional Server-Sent Events (SSE)
    if ($isStreaming) {
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        while (ob_get_level()) {
            ob_end_clean();
        }

        echo "event: meta\n";
        echo 'data: ' . json_encode([
            'acciones'    => $resultado['acciones'],
            'rag_applied' => (bool)($resultado['rag_applied'] ?? false),
        ], JSON_UNESCAPED_UNICODE) . "\n\n";
        flush();

        $palabras = preg_split('/(\s+)/u', (string)$resultado['respuesta'], -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        foreach ($palabras as $p) {
            if ($p === '') {
                continue;
            }
            echo 'data: ' . json_encode(['delta' => $p], JSON_UNESCAPED_UNICODE) . "\n\n";
            flush();
            usleep(10000);
        }

        echo "event: done\n";
        echo "data: [DONE]\n\n";
        flush();
        exit;
    }

    $resPayload = [
        'ok'        => true,
        'respuesta' => $resultado['respuesta'],
        'acciones'  => $resultado['acciones'],
        'degraded'  => (bool)($resultado['degraded'] ?? false),
    ];
    if ($useRag) {
        $resPayload['rag_applied'] = (bool)($resultado['rag_applied'] ?? false);
    }

    echo json_encode($resPayload, JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    error_log('[chat_frontend] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => 'Error interno procesando la consulta',
    ]);
}
