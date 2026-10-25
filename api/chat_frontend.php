<?php
declare(strict_types=1);

/**
 * Endpoint de Chat Frontend (Widget Web).
 *
 * Actúa como adaptador HTTP delgado sobre Services\Chat\ChatEngine,
 * garantizando compatibilidad retrocompatible 100% con assets/js/chat.js
 * y soporte opcional para streaming Server-Sent Events (SSE).
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/chat/DeepSeekClient.php';
require_once __DIR__ . '/../services/chat/ChatEngine.php';
require_once __DIR__ . '/../services/chat/RateLimiter.php';

use Services\Chat\ChatEngine;
use Services\Chat\DeepSeekClient;
use Services\Chat\RateLimiter;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

// 1. Límite de tamaño de payload (32 KB) para prevenir DoS
$rawBody = file_get_contents('php://input');
if ($rawBody === false || strlen($rawBody) > 32768) {
    http_response_code(413);
    echo json_encode(['error' => 'Payload demasiado grande (máximo 32KB)']);
    exit;
}

$body = json_decode($rawBody, true);
if (!is_array($body)) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido']);
    exit;
}

$pregunta = trim((string)($body['pregunta'] ?? ''));
if ($pregunta === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Pregunta vacía']);
    exit;
}

$historial = is_array($body['historial'] ?? null) ? $body['historial'] : [];
$isStreaming = (bool)($body['stream'] ?? ($_GET['stream'] ?? false));
$useRag = (bool)($body['use_rag'] ?? false);

try {
    $pdo = Database::getConnection();

    // 2. Control de tasa de peticiones (Rate Limiter por IP)
    $clientIp = $_SERVER['HTTP_CF_CONNECTING_IP']
        ?? (isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]) : null)
        ?? $_SERVER['REMOTE_ADDR']
        ?? '127.0.0.1';

    $rateLimitPerMin = (int)(defined('CHAT_RATE_LIMIT_FRONTEND_PER_MIN') ? CHAT_RATE_LIMIT_FRONTEND_PER_MIN : (getenv('CHAT_RATE_LIMIT_FRONTEND_PER_MIN') ?: 60));
    $rateLimiter = new RateLimiter($pdo);
    $rateStatus = $rateLimiter->hit("frontend:ip:{$clientIp}", $rateLimitPerMin, 60);

    if (!$rateStatus['allowed']) {
        http_response_code(429);
        header('Retry-After: ' . $rateStatus['retry_after']);
        echo json_encode([
            'ok'    => false,
            'error' => 'Demasiadas solicitudes. Por favor espera unos momentos antes de continuar.'
        ]);
        exit;
    }

    // 3. Obtener identificador de sesión sin bloquear el archivo de sesión durante la llamada LLM
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $sessionId = session_id() ?: 'web-' . bin2hex(random_bytes(8));
    @session_write_close();

    // 4. Delegación a ChatEngine (motor unificado y endurecido)
    $llmClient = new DeepSeekClient();
    $engine = new ChatEngine($pdo, $llmClient);

    $resultado = $engine->procesarPregunta($pregunta, $historial, [
        'canal'     => 'web',
        'remitente' => $sessionId,
        'sessionId' => $sessionId,
        'use_rag'   => $useRag,
    ]);

    // 5. Streaming opcional Server-Sent Events (SSE)
    if ($isStreaming) {
        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache');
        header('X-Accel-Buffering: no');
        while (ob_get_level()) {
            ob_end_clean();
        }

        echo "event: meta\n";
        echo "data: " . json_encode([
            'acciones'    => $resultado['acciones'],
            'rag_applied' => (bool)($resultado['rag_applied'] ?? false),
        ], JSON_UNESCAPED_UNICODE) . "\n\n";
        flush();

        $palabras = preg_split('/(\s+)/u', (string)$resultado['respuesta'], -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
        foreach ($palabras as $p) {
            if ($p === '') continue;
            echo "data: " . json_encode(['delta' => $p], JSON_UNESCAPED_UNICODE) . "\n\n";
            flush();
            usleep(10000);
        }

        echo "event: done\n";
        echo "data: [DONE]\n\n";
        flush();
        exit;
    }

    // 6. Respuesta JSON estándar retrocompatible con assets/js/chat.js
    $resPayload = [
        'ok'        => true,
        'respuesta' => $resultado['respuesta'],
        'acciones'  => $resultado['acciones'],
    ];

    if ($useRag) {
        $resPayload['rag_applied'] = (bool)($resultado['rag_applied'] ?? false);
    }

    echo json_encode($resPayload, JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    error_log('[chat_frontend] Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => 'Error interno procesando la consulta'
    ]);
}