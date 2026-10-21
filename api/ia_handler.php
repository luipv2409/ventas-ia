<?php
// ============================================================
// ARCHIVO: api/ia_handler.php (v3 - IA LIBRE CON CONTROL)
// ============================================================

declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// --- Validación de entrada (sin cambios) ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido. Usa POST.']);
    exit;
}
$body     = file_get_contents('php://input');
$data     = json_decode($body, true);
$pregunta = trim($data['pregunta'] ?? '');
if ($pregunta === '') {
    http_response_code(400);
    echo json_encode(['error' => 'El campo "pregunta" es obligatorio.']);
    exit;
}
if (mb_strlen($pregunta) > 500) {
    http_response_code(400);
    echo json_encode(['error' => 'La pregunta no puede superar 500 caracteres.']);
    exit;
}
$pregunta = strip_tags($pregunta);

// ============================================================
// FUNCIÓN: Obtener catálogo completo (productos con stock > 0)
// ============================================================
function obtenerCatalogoCompleto(PDO $pdo): array
{
    $stmt = $pdo->prepare("
        SELECT p.nombre, p.descripcion, p.precio, p.stock, c.nombre AS categoria
        FROM productos p
        INNER JOIN categorias c ON c.id = p.categoria_id
        WHERE p.stock > 0
        ORDER BY c.nombre, p.nombre
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ============================================================
// FUNCIÓN: Construir el system prompt (ESTILO LIBRE)
// ============================================================
function construirSystemPrompt(array $productos): string
{
    $nombreTienda = $_SERVER['SERVER_NAME'] ?? 'Nuestra Tienda';
    $jsonProductos = json_encode($productos, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    return <<<PROMPT
Eres un vendedor virtual amable, empático y con gran capacidad de conversación. 
Trabajas para la tienda "{$nombreTienda}" y conoces a fondo este catálogo (JSON):
{$jsonProductos}

COMPORTAMIENTO ESPERADO:
- Saluda, conversa y trata al cliente como una persona real. Puedes dar consejos de compra, explicar características y ayudar a comparar productos.
- Si el cliente te pide una recomendación (ej. "¿qué me regalarías para mamá?"), usa el catálogo y tu conocimiento general para sugerir opciones lógicas.
- Si hacen una pregunta ajena a la tienda (ej. "¿cómo estás?"), responde con naturalidad y luego reconduce la charla hacia los productos.
- NUNCA inventes productos, precios o disponibilidad. Si no sabes algo del catálogo, admítelo y ofrece lo que sí tienes.
- Responde siempre en español, con frases claras y un tono cálido, pero sin ser demasiado extenso.
PROMPT;
}

// ============================================================
// LLAMADAS A LAS APIs (Groq/Gemini) - SIN CAMBIOS
// ============================================================
function llamarGroq(string $systemPrompt, string $pregunta): string
{
    $payload = json_encode([
        'model'    => GROQ_MODEL,
        'messages' => [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user',   'content' => $pregunta],
        ],
        'temperature' => 0.7,
        'max_tokens'  => 512,
    ]);

    $ch = curl_init(GROQ_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GROQ_API_KEY,
        ],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new RuntimeException('Error cURL (Groq): ' . $curlError);
    }
    if ($httpCode !== 200) {
        throw new RuntimeException("Error API Groq [{$httpCode}]: " . $response);
    }
    $data = json_decode($response, true);
    return $data['choices'][0]['message']['content'] ?? 'Sin respuesta de la IA.';
}

function llamarGemini(string $systemPrompt, string $pregunta): string
{
    $url     = GEMINI_API_URL . '?key=' . GEMINI_API_KEY;
    $payload = json_encode([
        'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
        'contents' => [['role' => 'user', 'parts' => [['text' => $pregunta]]]],
        'generationConfig' => ['temperature' => 0.7, 'maxOutputTokens' => 512],
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => !APP_DEBUG,
    ]);
    $response  = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        throw new RuntimeException('Error cURL (Gemini): ' . $curlError);
    }
    if ($httpCode !== 200) {
        throw new RuntimeException("Error API Gemini [{$httpCode}]: " . $response);
    }
    $data = json_decode($response, true);
    return $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Sin respuesta de la IA.';
}

// ============================================================
// GUARDAR LOG (sin cambios)
// ============================================================
function guardarLog(PDO $pdo, string $pregunta, string $respuesta): void
{
    $sessionId = session_id() ?: 'no-session';
    $stmt = $pdo->prepare("
        INSERT INTO chat_logs (session_id, pregunta, respuesta)
        VALUES (:session_id, :pregunta, :respuesta)
    ");
    $stmt->execute([
        ':session_id' => $sessionId,
        ':pregunta'   => $pregunta,
        ':respuesta'  => $respuesta,
    ]);
}

// ============================================================
// FLUJO PRINCIPAL
// ============================================================
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_start();
    }

    $pdo = Database::getConnection();

    // 1. Obtener TODO el catálogo (sin filtros previos)
    $productos = obtenerCatalogoCompleto($pdo);

    if (empty($productos)) {
        echo json_encode([
            'ok' => true,
            'respuesta' => 'Lo siento, en este momento no tenemos productos disponibles. Vuelve pronto.',
            'proveedor' => APP_AI_PROVIDER
        ]);
        exit;
    }

    // 2. Construir prompt libre
    $systemPrompt = construirSystemPrompt($productos);

    // 3. Llamar al proveedor de IA
    $respuesta = match (APP_AI_PROVIDER) {
        'groq'   => llamarGroq($systemPrompt, $pregunta),
        'gemini' => llamarGemini($systemPrompt, $pregunta),
        default  => throw new RuntimeException('Proveedor de IA no válido en config.php'),
    };

    // 4. Guardar log
    guardarLog($pdo, $pregunta, $respuesta);

    echo json_encode([
        'ok'        => true,
        'respuesta' => $respuesta,
        'proveedor' => APP_AI_PROVIDER,
    ]);

} catch (RuntimeException $e) {
    $mensajeError = APP_DEBUG ? $e->getMessage() : 'Error al procesar tu pregunta. Inténtalo de nuevo.';
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $mensajeError]);
}