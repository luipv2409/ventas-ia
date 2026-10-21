<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/rag/RagService.php';
require_once __DIR__ . '/../services/chat/HumanPersona.php';

use Services\Chat\HumanPersona;
use Services\Rag\RagService;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];
$pregunta = trim($body['pregunta'] ?? '');
$historial = $body['historial'] ?? [];

if ($pregunta === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Pregunta vacía']);
    exit;
}

$pdo = Database::getConnection();

// 1. Ejecutar Recuperación RAG (Non-blocking / Safe Fallback)
$ragService = new RagService($pdo);
$ragResult = $ragService->retrieveContext($pregunta, 3, RagService::DEFAULT_MIN_SCORE, is_array($historial) ? $historial : []);
$ragContext = $ragResult['found'] ? ("\n\n" . $ragResult['context']) : "";

// 2. Datos de configuración de la tienda
$stmtInfo = $pdo->query("SELECT clave, valor FROM configuracion WHERE clave IN ('nombre_tienda', 'contacto_telefono', 'contacto_email', 'nombre_dueño', 'nombre_asesor', 'moneda_simbolo', 'tasa_cambio_usd')");
$infoNegocio = [];
while ($row = $stmtInfo->fetch(PDO::FETCH_ASSOC)) {
    $infoNegocio[$row['clave']] = $row['valor'];
}
$nombreTienda  = $infoNegocio['nombre_tienda'] ?? 'Nuestra Tienda';
$telefono      = $infoNegocio['contacto_telefono'] ?? 'No disponible';
$email         = $infoNegocio['contacto_email'] ?? 'No disponible';
$dueno         = $infoNegocio['nombre_dueño'] ?? 'No definido';
$nombreAsesor  = HumanPersona::nombreAsesor($infoNegocio);
$simboloMoneda = $infoNegocio['moneda_simbolo'] ?? 'Bs';
$tasaCambio    = (float)($infoNegocio['tasa_cambio_usd'] ?? 1.0);
if ($tasaCambio <= 0) $tasaCambio = 1.0;

// 3. Catálogo completo con precios oficiales convertidos
$rawProds = $pdo->query("
    SELECT p.id, p.nombre, SUBSTRING(p.descripcion, 1, 80) AS descripcion_corta, p.precio, p.stock, p.imagen_url, p.categoria_id, c.nombre as categoria_nombre
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    WHERE p.activo = 1 AND p.stock > 0
")->fetchAll(PDO::FETCH_ASSOC);

$productos = [];
$catalogoTexto = "";
foreach ($rawProds as $p) {
    $precioOficial = round(((float)$p['precio']) * $tasaCambio, 2);
    $prodObj = [
        'id'          => (int)$p['id'],
        'nombre'      => $p['nombre'],
        'precio'      => $precioOficial,
        'stock'       => (int)$p['stock'],
        'categoria'   => $p['categoria_nombre'] ?? 'General',
        'descripcion' => $p['descripcion_corta'] ?? '',
        'imagen'      => $p['imagen_url'] ?? '',
    ];
    $productos[] = $prodObj;
    $catalogoTexto .= sprintf("  - [ID: %d] %s (%s) — %s %.2f | Stock: %d\n", $prodObj['id'], $prodObj['nombre'], $prodObj['categoria'], $simboloMoneda, $prodObj['precio'], $prodObj['stock']);
}
$catalogoJSON = json_encode($productos, JSON_UNESCAPED_UNICODE);

// 4. Identificar último producto en la conversación
$ultimoProducto = '';
if (!empty($historial)) {
    foreach (array_reverse($historial) as $msg) {
        $msgContent = (string)($msg['content'] ?? '');
        foreach ($productos as $p) {
            if (mb_stripos($msgContent, $p['nombre']) !== false) {
                $ultimoProducto = $p['nombre'];
                break 2;
            }
        }
    }
}

// 5. System Prompt de Asesor de Ventas
$reglasPersona = HumanPersona::reglas($nombreAsesor, $nombreTienda, $telefono);
$systemPrompt = <<<PROMPT
{$reglasPersona}

INFORMACIÓN DE LA TIENDA:
- WhatsApp: {$telefono} | Email: {$email} | Tienda: {$nombreTienda}
- Moneda oficial: {$simboloMoneda}
{$ragContext}

CATÁLOGO COMPLETO ACTUAL (precios oficiales en {$simboloMoneda}):
{$catalogoTexto}
Último producto conversado: "{$ultimoProducto}"

REGLAS DE CONVERSACIÓN:
1. Responde solo a lo que el cliente pregunta ahora. No repitas temas ya contestados salvo que vuelva a preguntarlos.
2. AGREGAR AL CARRITO: si el cliente pide agregar ("sí", "agrégalo", "lo quiero"), NO vuelvas a preguntar. Confirma breve y variado (ej. "Listo, ya te agregué {$ultimoProducto} al carrito 🛒") y genera la acción: {"tipo": "agregar_carrito", "nombre": "NombreExacto"}.

FORMATO OBLIGATORIO (objeto JSON válido):
{
  "respuesta": "Tu mensaje natural aquí.",
  "acciones": []
}
PROMPT;

$mensajes = [
    ['role' => 'system', 'content' => $systemPrompt]
];

// Agregar historial reciente
$historialReciente = array_slice($historial, -6);
foreach ($historialReciente as $msg) {
    if (isset($msg['role'], $msg['content'])) {
        $mensajes[] = [
            'role'    => $msg['role'] === 'user' ? 'user' : 'assistant',
            'content' => (string)$msg['content']
        ];
    }
}
$mensajes[] = ['role' => 'user', 'content' => $pregunta];

// 5. Llamada segura a DeepSeek
function llamarDeepSeek(array $mensajes): ?string {
    $payload = json_encode([
        'model'       => defined('DEEPSEEK_MODEL') ? DEEPSEEK_MODEL : 'deepseek-chat',
        'messages'    => $mensajes,
        'temperature' => 0.6,
        'max_tokens'  => 600,
    ]);

    $ch = curl_init(defined('DEEPSEEK_API_URL') ? DEEPSEEK_API_URL : 'https://api.deepseek.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . (defined('DEEPSEEK_API_KEY') ? DEEPSEEK_API_KEY : ''),
        ],
        CURLOPT_TIMEOUT        => 20,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        error_log('[CHAT RAG] DeepSeek respondió HTTP ' . $httpCode);
        return null;
    }

    $data = json_decode($response, true);
    return $data['choices'][0]['message']['content'] ?? null;
}

$raw = llamarDeepSeek($mensajes);
$clean = $raw !== null ? json_decode($raw, true) : null;

if (!$clean || !isset($clean['respuesta'])) {
    $clean = [
        'respuesta' => ($raw !== null && trim($raw) !== '') ? $raw : HumanPersona::mensajeFallo($telefono),
        'acciones'  => []
    ];
}

// Retornar respuesta enriquecida con metadatos RAG para testing/inspección
echo json_encode([
    'ok'             => true,
    'respuesta'      => HumanPersona::limpiarRespuesta((string)$clean['respuesta']),
    'acciones'       => $clean['acciones'] ?? [],
    'rag_enabled'    => true,
    'rag_applied'    => $ragResult['found'],
    'rag_expanded'   => $ragResult['expanded_query'] ?? null,
    'rag_sources'    => array_map(fn($c) => ['title' => $c['title'], 'score' => $c['score']], $ragResult['chunks'])
], JSON_UNESCAPED_UNICODE);
