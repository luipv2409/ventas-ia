<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/admin/AnalyticsService.php';
require_once __DIR__ . '/../services/rag/RagService.php';

use Services\Admin\AnalyticsService;
use Services\Rag\RagService;

session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Acceso no autorizado']);
    exit;
}

$pdo = Database::getConnection();

// Configuración de moneda y tasa
$stmtConf = $pdo->query("SELECT clave, valor FROM configuracion WHERE clave IN ('moneda_simbolo', 'tasa_cambio_usd', 'nombre_tienda')");
$cfgs = $stmtConf ? ($stmtConf->fetchAll(PDO::FETCH_KEY_PAIR) ?: []) : [];
$simboloMoneda = $cfgs['moneda_simbolo'] ?? 'Bs';
$tasaCambio    = (float)($cfgs['tasa_cambio_usd'] ?? 1.0);
if ($tasaCambio <= 0) $tasaCambio = 1.0;
$nombreTienda  = $cfgs['nombre_tienda'] ?? 'Nuestra Tienda';

$analytics = new AnalyticsService($pdo, $tasaCambio, $simboloMoneda);
$ragService = new RagService($pdo);

// Gestión de hilos (Conversaciones persistentes en DB)
$action = $_GET['action'] ?? $_POST['action'] ?? '';
if ($action === 'listar_hilos') {
    $stmt = $pdo->prepare("
        SELECT id, titulo, DATE_FORMAT(actualizado_en, '%d/%m %H:%i') as fecha 
        FROM chat_admin_hilos 
        WHERE admin_id = :aid 
        ORDER BY actualizado_en DESC 
        LIMIT 30
    ");
    $stmt->execute([':aid' => $_SESSION['admin_id']]);
    echo json_encode(['ok' => true, 'hilos' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if ($action === 'cargar_hilo') {
    $hiloId = (int)($_GET['hilo_id'] ?? 0);
    $stmt = $pdo->prepare("
        SELECT role, content, tipo, datos_json, creado_en 
        FROM chat_admin_mensajes 
        WHERE hilo_id = :hid 
        ORDER BY id ASC
    ");
    $stmt->execute([':hid' => $hiloId]);
    $mensajes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($mensajes as &$m) {
        if ($m['datos_json']) {
            $m['datos'] = json_decode($m['datos_json'], true);
        }
    }
    echo json_encode(['ok' => true, 'mensajes' => $mensajes]);
    exit;
}

if ($action === 'eliminar_hilo') {
    $hiloId = (int)($_POST['hilo_id'] ?? 0);
    $stmt = $pdo->prepare("DELETE FROM chat_admin_hilos WHERE id = :hid AND admin_id = :aid");
    $stmt->execute([':hid' => $hiloId, ':aid' => $_SESSION['admin_id']]);
    echo json_encode(['ok' => true]);
    exit;
}

// Procesar mensaje del chat
$body = json_decode(file_get_contents('php://input'), true);
$pregunta = trim($body['pregunta'] ?? '');
$hiloId   = !empty($body['hilo_id']) ? (int)$body['hilo_id'] : null;

if ($pregunta === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Pregunta vacía']);
    exit;
}

// Crear o resolver hilo persistente
if (!$hiloId) {
    $tituloCorto = mb_substr($pregunta, 0, 45);
    $stmtH = $pdo->prepare("INSERT INTO chat_admin_hilos (titulo, admin_id) VALUES (:t, :aid)");
    $stmtH->execute([':t' => $tituloCorto, ':aid' => $_SESSION['admin_id']]);
    $hiloId = (int)$pdo->lastInsertId();
}

// Cargar contexto reciente del hilo
$stmtRec = $pdo->prepare("SELECT role, content FROM chat_admin_mensajes WHERE hilo_id = :hid ORDER BY id DESC LIMIT 6");
$stmtRec->execute([':hid' => $hiloId]);
$ultimos = array_reverse($stmtRec->fetchAll(PDO::FETCH_ASSOC));

$diccionarioPath = __DIR__ . '/../diccionario_bd.json';
$diccionario = file_exists($diccionarioPath) ? file_get_contents($diccionarioPath) : '';

// ==================== HERRAMIENTAS EJECUTIVAS Y ANALÍTICAS ====================
$herramientas = [
    [
        'nombre' => 'analisis_estrategico',
        'descripcion' => 'Inteligencia de negocios (BI): Diagnóstico de stock crítico, rotación de productos, alertas de márgenes y recomendaciones para toma de decisiones.',
        'parametros' => []
    ],
    [
        'nombre' => 'generar_reporte',
        'descripcion' => 'Genera y descarga un reporte formal estructurado (CSV o HTML ejecutivo) de ventas o de inventario valorizado.',
        'parametros' => [
            'tipo'    => 'ejecutivo, ventas o inventario',
            'formato' => 'csv o html'
        ]
    ],
    [
        'nombre' => 'consultar_conocimiento_rag',
        'descripcion' => 'Consulta la base de conocimiento RAG (políticas internas, garantías, métodos de pago, horarios y fichas de productos).',
        'parametros' => ['consulta' => 'tema o política a buscar']
    ],
    [
        'nombre' => 'consultar_producto',
        'descripcion' => 'Busca productos por nombre o descripción, mostrando ficha técnica, precio oficial Bs y USD, y stock.',
        'parametros' => ['busqueda' => 'nombre del producto']
    ],
    [
        'nombre' => 'resumen_general',
        'descripcion' => 'Métricas clave rápidas del negocio: ventas hoy, del mes y stock bajo.',
        'parametros' => []
    ],
    [
        'nombre' => 'ventas_dia',
        'descripcion' => 'Ventas totales y número de pedidos de hoy.',
        'parametros' => []
    ],
    [
        'nombre' => 'ventas_mes',
        'descripcion' => 'Ventas totales y pedidos del mes en curso.',
        'parametros' => []
    ],
    [
        'nombre' => 'top_productos',
        'descripcion' => 'Productos más vendidos por volumen.',
        'parametros' => ['limite' => 'número (opcional, default 5)']
    ],
    [
        'nombre' => 'top_clientes',
        'descripcion' => 'Clientes más valiosos por gasto total acumulado.',
        'parametros' => ['limite' => 'número (opcional, default 5)']
    ],
    [
        'nombre' => 'pedidos_pendientes',
        'descripcion' => 'Cantidad de pedidos pendientes de despacho.',
        'parametros' => []
    ],
    [
        'nombre' => 'actualizar_precio',
        'descripcion' => 'Actualiza el precio de un producto. Acepta producto_id o nombre.',
        'parametros' => [
            'nombre'       => 'Nombre del producto',
            'nuevo_precio' => 'monto numérico',
            'moneda'       => 'bs o usd'
        ]
    ],
    [
        'nombre' => 'agregar_stock',
        'descripcion' => 'Aumenta el stock de un producto.',
        'parametros' => ['nombre' => 'Nombre del producto', 'cantidad' => 'unidades a añadir']
    ],
    [
        'nombre' => 'grafico_ventas_mes',
        'descripcion' => 'Datos para gráfico de barras de ventas diarias del mes.',
        'parametros' => []
    ],
    [
        'nombre' => 'grafico_top_productos',
        'descripcion' => 'Datos para gráfico circular de productos más vendidos.',
        'parametros' => ['limite' => 'número (opcional, default 5)']
    ],
    [
        'nombre' => 'stock_bajo',
        'descripcion' => 'Lista de productos por debajo del umbral mínimo de seguridad.',
        'parametros' => []
    ],
    [
        'nombre' => 'buscar_internet',
        'descripcion' => 'Búsqueda web en vivo de tendencias del mercado o proveedores.',
        'parametros' => ['consulta' => 'tema a investigar']
    ],
];

$herramientasJSON = json_encode($herramientas, JSON_UNESCAPED_UNICODE);

$systemPrompt = <<<PROMPT
Eres Jarvis, el Asesor Ejecutivo de Inteligencia de Negocios (BI) y Asistente Administrativo de "{$nombreTienda}".
Moneda oficial: {$simboloMoneda} (Tasa oficial: 1 USD = {$tasaCambio} {$simboloMoneda}).
Base de datos: La tabla 'productos' almacena 'precio' en USD base, que al multiplicarse por {$tasaCambio} da el precio de venta al público en {$simboloMoneda}.

Capacidades de Decisión:
- Cuentas con herramientas de BI para diagnóstico estratégico ('analisis_estrategico'), reportes descargables ('generar_reporte') y base de conocimiento RAG ('consultar_conocimiento_rag').
- Si el administrador te pide consejos, cómo mejorar ventas, qué productos reponer o un análisis profundo, usa 'analisis_estrategico'.
- Si te pide un reporte en Excel o CSV o un documento formal, usa 'generar_reporte'.
- Si te pregunta por políticas de la tienda o fichas técnicas profundas, usa 'consultar_conocimiento_rag' o 'consultar_producto'.

Herramientas disponibles:
{$herramientasJSON}

Estructura de la base de datos:
{$diccionario}

Reglas estrictas de formato:
- Responde SIEMPRE con un ÚNICO objeto JSON válido:
  {"accion": "nombre_herramienta", "parametros": {...}, "mensaje": "texto explicativo opcional"}
- Si no necesitas herramientas, usa:
  {"accion": "responder", "mensaje": "tu respuesta analítica aquí"}
PROMPT;

function llamarIA(array $mensajes): array {
    $payload = json_encode([
        'model'       => DEEPSEEK_MODEL,
        'messages'    => $mensajes,
        'temperature' => 0.25,
        'max_tokens'  => 600,
    ]);
    
    $ch = curl_init(DEEPSEEK_API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . DEEPSEEK_API_KEY,
        ],
        CURLOPT_TIMEOUT        => 22,
    ]);
    
    $response = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        return ['accion' => 'responder', 'mensaje' => 'Error de conexión con la IA (código ' . $httpCode . ').'];
    }
    
    $data = json_decode($response, true);
    $raw = $data['choices'][0]['message']['content'] ?? '';
    
    $cleaned = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($raw));
    $decoded = json_decode($cleaned, true);
    if (!$decoded && preg_match('/\{[\s\S]*"accion"[\s\S]*\}/', $raw, $m)) {
        $decoded = json_decode($m[0], true);
    }
    
    if (!$decoded || !isset($decoded['accion'])) {
        return ['accion' => 'responder', 'mensaje' => $raw ?: 'No pude procesar la consulta.'];
    }
    return $decoded;
}

// Construir mensajes para el modelo
$mensajes = [['role' => 'system', 'content' => $systemPrompt]];
foreach ($ultimos as $msg) {
    if (isset($msg['role'], $msg['content'])) {
        $mensajes[] = ['role' => $msg['role'], 'content' => (string)$msg['content']];
    }
}
$mensajes[] = ['role' => 'user', 'content' => $pregunta];

$decision = llamarIA($mensajes);
$accion = $decision['accion'] ?? 'responder';
$parametros = $decision['parametros'] ?? [];
$mensajeIA = $decision['mensaje'] ?? '';

$resultado = null;
$tipoRespuesta = 'texto';
$datosExtra = null;

try {
    switch ($accion) {
        case 'analisis_estrategico':
            $resumen = $analytics->getExecutiveSummary();
            $consejos = $analytics->getStrategicAdvice();
            $tipoRespuesta = 'analisis_bi';
            $datosExtra = [
                'resumen'  => $resumen,
                'consejos' => $consejos
            ];
            $lineas = ["📈 **DIAGNÓSTICO ESTRATÉGICO Y BI (TOMA DE DECISIONES):**\n"];
            foreach ($consejos as $c) {
                $lineas[] = "{$c['icono']} **[{$c['prioridad']}] {$c['titulo']}**\n   {$c['detalle']}\n";
            }
            $resultado = implode("\n", $lineas);
            break;

        case 'generar_reporte':
            $tipoRep = !empty($parametros['tipo']) ? (string)$parametros['tipo'] : 'inventario';
            $formato = !empty($parametros['formato']) ? (string)$parametros['formato'] : 'csv';
            $rep = $analytics->generateReportFile($tipoRep, $formato);
            $tipoRespuesta = 'reporte_descarga';
            $datosExtra = $rep;
            $resultado = "📄 He generado el reporte solicitado ({$rep['tipo']} en formato {$rep['formato']}).\nPuedes descargarlo directamente en tu equipo con el botón adjunto.";
            break;

        case 'consultar_conocimiento_rag':
            $consulta = $parametros['consulta'] ?? $pregunta;
            $ragRes = $ragService->retrieveContext($consulta, 3);
            if ($ragRes['found']) {
                $resultado = "📚 Información extraída de la Base de Conocimiento oficial:\n\n" . $ragRes['context'];
            } else {
                $resultado = "No encontré documentación específica en la base de conocimiento para esa consulta.";
            }
            break;

        case 'consultar_producto':
            $busqueda = $parametros['busqueda'] ?? '';
            $stmt = $pdo->prepare("
                SELECT id, nombre, precio, stock, descripcion, especificaciones 
                FROM productos 
                WHERE nombre LIKE :b OR descripcion LIKE :b2 
                LIMIT 5
            ");
            $stmt->execute([':b' => "%$busqueda%", ':b2' => "%$busqueda%"]);
            $prods = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($prods) {
                $lineas = [];
                foreach ($prods as $p) {
                    $pBs = round(((float)$p['precio']) * $tasaCambio, 2);
                    $det = "- **{$p['nombre']}** (ID #{$p['id']}) | Precio oficial: **{$simboloMoneda} {$pBs}** (\${$p['precio']} USD) | Stock: **{$p['stock']} u.**";
                    if (!empty($p['especificaciones'])) {
                        $sp = json_decode($p['especificaciones'], true);
                        if (!empty($sp['pantalla'])) $det .= "\n  • Pantalla: {$sp['pantalla']}";
                        if (!empty($sp['procesador'])) $det .= "\n  • Procesador: {$sp['procesador']}";
                        if (!empty($sp['kimovil_score'])) $det .= "\n  • Kimovil Score: ⭐ {$sp['kimovil_score']}";
                    }
                    $lineas[] = $det;
                }
                $resultado = "📦 **Productos Encontrados:**\n\n" . implode("\n\n", $lineas);
            } else {
                $resultado = "No se encontraron productos coincidentes con '$busqueda'.";
            }
            break;

        case 'resumen_general':
            $res = $analytics->getExecutiveSummary();
            $resultado = "📊 **Resumen General de Operaciones:**\n"
                       . "• Ventas hoy: {$res['ventas_hoy']['pedidos']} pedidos ({$simboloMoneda} {$res['ventas_hoy']['total_bs']} / \${$res['ventas_hoy']['total_usd']} USD)\n"
                       . "• Ventas del mes: {$res['ventas_mes']['pedidos']} pedidos ({$simboloMoneda} {$res['ventas_mes']['total_bs']} / \${$res['ventas_mes']['total_usd']} USD)\n"
                       . "• Ticket promedio: {$simboloMoneda} {$res['ventas_mes']['ticket_promedio_bs']}\n"
                       . "• Inventario valorizado: {$simboloMoneda} " . number_format($res['inventario']['valor_venta_bs'], 2) . " ({$res['inventario']['unidades_totales']} unidades)\n"
                       . "• Productos críticos: " . count($res['productos_criticos']) . " SKUs por agotarse.";
            break;

        case 'ventas_dia':
            $v = $pdo->query("SELECT COUNT(*) as num, COALESCE(SUM(total), 0) as total FROM pedidos WHERE DATE(creado_en) = CURDATE()")->fetch(PDO::FETCH_ASSOC);
            $totalBs = round(((float)$v['total']) * $tasaCambio, 2);
            $resultado = "📅 Hoy se han registrado {$v['num']} pedido(s).\nTotal ventas: {$simboloMoneda} {$totalBs} (\${$v['total']} USD).";
            break;

        case 'ventas_mes':
            $v = $pdo->query("SELECT COUNT(*) as num, COALESCE(SUM(total), 0) as total FROM pedidos WHERE MONTH(creado_en) = MONTH(CURDATE()) AND YEAR(creado_en) = YEAR(CURDATE())")->fetch(PDO::FETCH_ASSOC);
            $totalBs = round(((float)$v['total']) * $tasaCambio, 2);
            $resultado = "📅 Ventas de este mes: {$v['num']} pedido(s).\nTotal acumulado: {$simboloMoneda} {$totalBs} (\${$v['total']} USD).";
            break;

        case 'top_productos':
            $limite = (int)($parametros['limite'] ?? 5);
            $stmt = $pdo->prepare("SELECT p.nombre, SUM(dp.cantidad) as total FROM detalle_pedidos dp JOIN productos p ON dp.producto_id = p.id GROUP BY p.id ORDER BY total DESC LIMIT :lim");
            $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
            $stmt->execute();
            $top = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $resultado = $top ? "🏆 Top {$limite} productos más vendidos:\n" . implode("\n", array_map(fn($t) => "- {$t['nombre']}: {$t['total']} unidades", $top)) : "Aún no hay ventas registradas.";
            break;

        case 'top_clientes':
            $limite = (int)($parametros['limite'] ?? 5);
            $stmt = $pdo->prepare("SELECT c.nombre, SUM(p.total) as gastado FROM pedidos p JOIN clientes c ON p.cliente_id = c.id GROUP BY c.id ORDER BY gastado DESC LIMIT :lim");
            $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
            $stmt->execute();
            $cli = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($cli) {
                $lineas = [];
                foreach ($cli as $c) {
                    $bs = round(((float)$c['gastado']) * $tasaCambio, 2);
                    $lineas[] = "- {$c['nombre']}: {$simboloMoneda} {$bs} (\${$c['gastado']} USD)";
                }
                $resultado = "🌟 Mejores clientes:\n" . implode("\n", $lineas);
            } else {
                $resultado = "No hay registros de compras de clientes aún.";
            }
            break;

        case 'pedidos_pendientes':
            $num = $pdo->query("SELECT COUNT(*) FROM pedidos WHERE estado='pendiente'")->fetchColumn();
            $resultado = "Actualmente hay {$num} pedido(s) en estado pendiente.";
            break;

        case 'actualizar_precio':
            $nombre = $parametros['nombre'] ?? '';
            $precioEntrante = (float)($parametros['nuevo_precio'] ?? 0);
            $monedaEntrante = mb_strtolower(trim((string)($parametros['moneda'] ?? '')));
            
            $stmt = $pdo->prepare("SELECT id FROM productos WHERE nombre LIKE :n LIMIT 1");
            $stmt->execute([':n' => "%$nombre%"]);
            $pid = (int)($stmt->fetchColumn() ?: 0);

            if ($pid && $precioEntrante > 0) {
                $precioFinalUsd = ($monedaEntrante === 'bs' || $monedaEntrante === 'bolivianos')
                    ? round($precioEntrante / $tasaCambio, 2)
                    : $precioEntrante;
                $precioFinalBs = round($precioFinalUsd * $tasaCambio, 2);

                $stmt = $pdo->prepare("UPDATE productos SET precio = :p WHERE id = :id");
                $stmt->execute([':p' => $precioFinalUsd, ':id' => $pid]);
                $resultado = "✔ Precio actualizado para el producto #{$pid}: Precio público {$simboloMoneda} {$precioFinalBs} (\${$precioFinalUsd} USD base).";
            } else {
                $resultado = "No se pudo actualizar: Verifica el nombre del producto y que el monto sea válido.";
            }
            break;

        case 'agregar_stock':
            $nombre = $parametros['nombre'] ?? '';
            $cant = (int)($parametros['cantidad'] ?? 0);
            $stmt = $pdo->prepare("SELECT id FROM productos WHERE nombre LIKE :n LIMIT 1");
            $stmt->execute([':n' => "%$nombre%"]);
            $pid = (int)($stmt->fetchColumn() ?: 0);
            if ($pid && $cant > 0) {
                $stmt = $pdo->prepare("UPDATE productos SET stock = stock + :c WHERE id = :id");
                $stmt->execute([':c' => $cant, ':id' => $pid]);
                $nuevoStock = $pdo->query("SELECT stock FROM productos WHERE id = $pid")->fetchColumn();
                $resultado = "✔ Stock incrementado en {$cant} unidades. Nuevo stock total: {$nuevoStock}.";
            } else {
                $resultado = "No se encontró el producto o la cantidad no es válida.";
            }
            break;

        case 'grafico_ventas_mes':
            $stmt = $pdo->query("SELECT DATE(creado_en) as dia, SUM(total) as total FROM pedidos WHERE MONTH(creado_en)=MONTH(CURDATE()) AND YEAR(creado_en)=YEAR(CURDATE()) GROUP BY dia ORDER BY dia");
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $tipoRespuesta = 'grafico';
            $resultado = "Gráfico generado.";
            $datosExtra = [
                'tipo'   => 'bar',
                'labels' => array_column($datos, 'dia'),
                'datos'  => array_map(fn($t) => round(((float)$t) * $tasaCambio, 2), array_column($datos, 'total')),
                'label'  => "Ventas diarias del mes ({$simboloMoneda})"
            ];
            break;

        case 'grafico_top_productos':
            $limite = (int)($parametros['limite'] ?? 5);
            $stmt = $pdo->prepare("SELECT p.nombre, SUM(dp.cantidad) as total FROM detalle_pedidos dp JOIN productos p ON dp.producto_id = p.id GROUP BY p.id ORDER BY total DESC LIMIT :lim");
            $stmt->bindValue(':lim', $limite, PDO::PARAM_INT);
            $stmt->execute();
            $datos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $tipoRespuesta = 'grafico';
            $resultado = "Gráfico circular generado.";
            $datosExtra = [
                'tipo'   => 'pie',
                'labels' => array_column($datos, 'nombre'),
                'datos'  => array_column($datos, 'total'),
                'label'  => 'Productos más vendidos'
            ];
            break;

        case 'stock_bajo':
            $stmtUmbral = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'umbral_stock'");
            $umbral = (int)($stmtUmbral ? ($stmtUmbral->fetchColumn() ?: 5) : 5);
            $stmt = $pdo->prepare("SELECT nombre, stock FROM productos WHERE activo = 1 AND stock < :umbral ORDER BY stock ASC");
            $stmt->execute([':umbral' => $umbral]);
            $bajos = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($bajos) {
                $resultado = "⚠️ Productos con stock bajo (menos de {$umbral} unidades):\n" . implode("\n", array_map(fn($p) => "- {$p['nombre']} ({$p['stock']} u. restantes)", $bajos));
            } else {
                $resultado = "✔ No hay productos con stock por debajo del umbral ({$umbral} u.).";
            }
            break;

        case 'buscar_internet':
            $consulta = $parametros['consulta'] ?? '';
            $resultado = "Consulta de mercado para: '$consulta'. Se sugiere comparar precios en proveedores autorizados.";
            break;

        case 'responder':
        default:
            $resultado = $mensajeIA ?: "Entendido, estoy listo para apoyarte en cualquier análisis operativo o comercial.";
    }
} catch (Throwable $e) {
    $resultado = "Error al ejecutar acción: " . $e->getMessage();
}

// Persistir la interacción en la base de datos (Historial de hilo)
try {
    $stmtInsMsg = $pdo->prepare("
        INSERT INTO chat_admin_mensajes (hilo_id, role, content, tipo, datos_json) 
        VALUES (:hid, :role, :content, :tipo, :datos)
    ");
    $stmtInsMsg->execute([
        ':hid'     => $hiloId,
        ':role'    => 'user',
        ':content' => $pregunta,
        ':tipo'    => 'texto',
        ':datos'   => null
    ]);

    $stmtInsMsg->execute([
        ':hid'     => $hiloId,
        ':role'    => 'assistant',
        ':content' => is_string($resultado) ? $resultado : ($mensajeIA ?: 'Completado'),
        ':tipo'    => $tipoRespuesta,
        ':datos'   => $datosExtra ? json_encode($datosExtra, JSON_UNESCAPED_UNICODE) : null
    ]);

    // Actualizar timestamp del hilo
    $pdo->prepare("UPDATE chat_admin_hilos SET actualizado_en = NOW() WHERE id = :hid")->execute([':hid' => $hiloId]);
} catch (Throwable $e) {
    error_log('[CHAT ADMIN PERSIST ERROR] ' . $e->getMessage());
}

echo json_encode([
    'ok'       => true,
    'hilo_id'  => $hiloId,
    'tipo'     => $tipoRespuesta,
    'mensaje'  => is_string($resultado) ? $resultado : ($mensajeIA ?: 'Hecho'),
    'datos'    => $datosExtra,
], JSON_UNESCAPED_UNICODE);