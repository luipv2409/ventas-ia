<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/chat/HumanPersona.php';
require_once __DIR__ . '/../services/chat/DeepSeekClient.php';

use Services\Chat\DeepSeekClient;
use Services\Chat\HumanPersona;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$pregunta = trim($body['pregunta'] ?? '');
$historial = $body['historial'] ?? [];
$isStreaming = (bool)($body['stream'] ?? ($_GET['stream'] ?? false));

// ------------------------------------------------------------
// 🛡️ CARACTERÍSTICA RAG (NON-BREAKING / FEATURE FLAG)
// ------------------------------------------------------------
$useRag = (bool)($body['use_rag'] ?? false);
$ragContexto = '';
$ragApplied = false;

if ($pregunta === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Pregunta vacía']);
    exit;
}

$pdo = Database::getConnection();

// ------------------------------------------------------------
// 1. Configuración de la tienda y moneda oficial
// ------------------------------------------------------------
$stmtInfo = $pdo->query("SELECT clave, valor FROM configuracion WHERE clave IN ('nombre_tienda', 'contacto_telefono', 'contacto_email', 'nombre_dueño', 'nombre_asesor', 'moneda_simbolo', 'tasa_cambio_usd')");
$infoNegocio = [];
if ($stmtInfo) {
    while ($row = $stmtInfo->fetch(PDO::FETCH_ASSOC)) {
        $infoNegocio[$row['clave']] = $row['valor'];
    }
}
$nombreTienda  = $infoNegocio['nombre_tienda'] ?? 'Nuestra Tienda';
$telefono      = $infoNegocio['contacto_telefono'] ?? 'No disponible';
$email         = $infoNegocio['contacto_email'] ?? 'No disponible';
$dueno         = $infoNegocio['nombre_dueño'] ?? 'No definido';
$nombreAsesor  = HumanPersona::nombreAsesor($infoNegocio);
$simboloMoneda = $infoNegocio['moneda_simbolo'] ?? 'Bs';
$tasaCambio    = (float)($infoNegocio['tasa_cambio_usd'] ?? 1.0);
if ($tasaCambio <= 0) $tasaCambio = 1.0;

if ($useRag) {
    try {
        require_once __DIR__ . '/../services/rag/RagService.php';
        $ragService = new \Services\Rag\RagService($pdo);
        $ragResult = $ragService->retrieveContext($pregunta, 3, \Services\Rag\RagService::DEFAULT_MIN_SCORE, is_array($historial) ? $historial : []);
        if ($ragResult['found']) {
            $ragContexto = "\n\n" . $ragResult['context'];
            $ragApplied = true;
        }
    } catch (\Throwable $e) {
        error_log('[RAG SAFE FALLBACK] chat_frontend: ' . $e->getMessage());
    }
}

// ------------------------------------------------------------
// 2. Categorías dinámicas de la base de datos
// ------------------------------------------------------------
$categoriasRaw = $pdo->query("SELECT id, nombre FROM categorias ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$categoriasLista = [];
$mapaCategoriasId = [];
foreach ($categoriasRaw as $c) {
    $cid = (int)$c['id'];
    $cnom = trim($c['nombre']);
    $categoriasLista[] = ['id' => $cid, 'nombre' => $cnom];
    $mapaCategoriasId[mb_strtolower($cnom)] = $cid;
}
$categoriasJSON = json_encode($categoriasLista, JSON_UNESCAPED_UNICODE);

// ------------------------------------------------------------
// 3. Catálogo completo en memoria (Precios oficiales garantizados)
// ------------------------------------------------------------
$stmtProd = $pdo->query("
    SELECT p.id, p.nombre,
           SUBSTRING(p.descripcion, 1, 90) AS descripcion_corta,
           p.precio AS precio_base_usd, p.stock, p.imagen_url, p.categoria_id,
           c.nombre AS categoria_nombre
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    WHERE p.activo = 1 AND p.stock > 0
");
$rawProductos = $stmtProd ? $stmtProd->fetchAll(PDO::FETCH_ASSOC) : [];

$productos = [];
$mapaProductosPorNombre = [];
$resumenCategorias = [];

foreach ($rawProductos as $p) {
    $precioOficial = round(((float)$p['precio_base_usd']) * $tasaCambio, 2);
    $catNom = $p['categoria_nombre'] ?? 'General';
    $prodObj = [
        'id'               => (int)$p['id'],
        'nombre'           => $p['nombre'],
        'categoria_id'     => (int)$p['categoria_id'],
        'categoria'        => $catNom,
        'precio'           => $precioOficial, // PRECIO OFICIAL FINAL EN LA MONEDA DE LA TIENDA (Bs)
        'moneda'           => $simboloMoneda,
        'stock'            => (int)$p['stock'],
        'descripcion'      => $p['descripcion_corta'] ?? '',
        'imagen'           => $p['imagen_url'] ?? '',
    ];
    $productos[] = $prodObj;
    $mapaProductosPorNombre[mb_strtolower($p['nombre'])] = $prodObj;

    if (!isset($resumenCategorias[$catNom])) {
        $resumenCategorias[$catNom] = ['cantidad' => 0, 'precio_min' => $precioOficial, 'precio_max' => $precioOficial];
    }
    $resumenCategorias[$catNom]['cantidad']++;
    $resumenCategorias[$catNom]['precio_min'] = min($resumenCategorias[$catNom]['precio_min'], $precioOficial);
    $resumenCategorias[$catNom]['precio_max'] = max($resumenCategorias[$catNom]['precio_max'], $precioOficial);
}

// ------------------------------------------------------------
// 4. Sinónimos y normalización
// ------------------------------------------------------------
$sinonimos = [
    'celus'     => 'smartphone',
    'celular'   => 'smartphone',
    'celulares' => 'smartphone',
    'móvil'     => 'smartphone',
    'movil'     => 'smartphone',
    'telefonos' => 'smartphone',
    'audifonos' => 'auriculares',
    'audífonos' => 'auriculares',
    'zapatos'   => 'zapatillas',
    'tenis'     => 'zapatillas',
    'ipad'      => 'tablet',
    'ipads'     => 'tablet',
];
$preguntaLower = mb_strtolower($pregunta);
foreach ($sinonimos as $clave => $valor) {
    if (strpos($preguntaLower, $clave) !== false) {
        $pregunta = str_ireplace($clave, $valor, $pregunta);
    }
}

// ------------------------------------------------------------
// 5. Presupuesto detectado y último producto
// ------------------------------------------------------------
if (preg_match('/^[\d]+(\.[\d]+)?\s*\$?$/', $pregunta, $m)) {
    $presupuestoDetectado = (float)$m[1];
    $pregunta = "Mi presupuesto es de aproximadamente {$simboloMoneda} {$presupuestoDetectado}. ¿Qué me recomiendas?";
}

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

// ------------------------------------------------------------
// 5.B. Catálogo Completo Estructurado por Categoría (Cero Amnesia)
// Mantiene el catálogo 100% íntegro para evitar contradicciones entre productos
// ------------------------------------------------------------
$productosPorCategoria = [];
foreach ($productos as $p) {
    $cat = $p['categoria'];
    if (!isset($productosPorCategoria[$cat])) {
        $productosPorCategoria[$cat] = [];
    }
    $productosPorCategoria[$cat][] = $p;
}

$catalogoEstructuradoTexto = "";
foreach ($productosPorCategoria as $catNom => $prods) {
    $catId = $prods[0]['categoria_id'] ?? 0;
    $catalogoEstructuradoTexto .= "• CATEGORÍA [ID: {$catId}] {$catNom} (" . count($prods) . " artículos disponibles):\n";
    foreach ($prods as $p) {
        $catalogoEstructuradoTexto .= sprintf(
            "   - [ID: %d] %s | %s %.2f | Stock: %d | Info: %s\n",
            $p['id'],
            $p['nombre'],
            $simboloMoneda,
            $p['precio'],
            $p['stock'],
            $p['descripcion']
        );
    }
    $catalogoEstructuradoTexto .= "\n";
}

$catalogoJSON = json_encode($productos, JSON_UNESCAPED_UNICODE);

// ------------------------------------------------------------
// 6. System Prompt Asesor con Agente Inteligente y Tarjetas en Chat
// ------------------------------------------------------------
$reglasPersona = HumanPersona::reglas($nombreAsesor, $nombreTienda, $telefono);
$systemPrompt = <<<PROMPT
{$reglasPersona}

INFORMACIÓN DE LA TIENDA:
- Tienda: {$nombreTienda} | Dueño: {$dueno} | WhatsApp: {$telefono} | Email: {$email}
- Moneda oficial de venta: {$simboloMoneda} (Bolivianos).
{$ragContexto}

CATÁLOGO COMPLETO DE LA TIENDA (Precios 100% oficiales en {$simboloMoneda}):
{$catalogoEstructuradoTexto}

CATEGORÍAS DE LA TIENDA (con sus IDs para filtrar):
{$categoriasJSON}

Último producto conversado: "{$ultimoProducto}"

REGLAS CRÍTICAS DE PRECIOS Y MONEDA:
1. Todos los precios de venta son los indicados en el catálogo en {$simboloMoneda}.
   Cita siempre la cifra exacta en {$simboloMoneda} (ej. "Bs 4200.00"). JAMÁS inventes precios ni digas montos sin convertir.
2. CONOCIMIENTO INTEGRAL DEL CATÁLOGO (CERO AMNESIA):
   - Conoces todos los productos agrupados arriba.
   - Si el cliente pregunta qué tienes en una categoría (celulares, zapatillas, tablets, auriculares, etc.), menciónale las opciones reales.
   - Si el cliente pregunta "solo tienes eso?" o "tienes más?", NUNCA digas que solo hay uno si la categoría tiene varios. Revisa la categoría y menciónale los demás.
   - Al listar modelos, menciona siempre los nombres oficiales del catálogo sin repetir el mismo modelo.

GESTIÓN DE ACCIONES Y TARJETAS EN EL CHAT ("acciones"):
1. Filtrar catálogo en la página web ("filtrar_catalogo"):
   Úsalo cuando el cliente pregunte en general qué categorías o secciones hay (ej: "¿qué tienen en la tienda?", "¿tienen calzado?"). Filtra la página en vivo.
   Ejemplo: {"tipo": "filtrar_catalogo", "categoria_id": 4, "mensaje_filtro": "Mostrando Zapatillas"}
2. Mostrar tarjeta individual de producto en el chat ("mostrar_producto"):
   ¡OBLIGATORIO Y PRIORITARIO! Emite de 1 a 3 acciones "mostrar_producto" cuando:
   - El cliente pida ver opciones o fotos ("muéstrame", "quiero verlos", "enséñame", "a ver", "pásamelos").
   - El cliente diga que NO ve los productos en pantalla ("no los veo", "dónde están", "no me sale nada", "sigo esperando").
   - El cliente pida información o detalle de productos específicos.
   Estas tarjetas aparecerán directamente dentro de la ventana de chat del cliente con su foto, precio oficial y botón de compra.
   Ejemplo:
   {"tipo": "mostrar_producto", "nombre": "Nike Air Zoom Pegasus 40", "precio": 1560.00}
3. Agregar al carrito ("agregar_carrito"):
   - Si le preguntaste al cliente si quería agregarlo al carrito (ej. "¿te lo agrego al carrito?", "¿lo añadimos?") y el cliente responde afirmativamente ("sí", "si", "dale", "claro", "bueno", "por favor", "porfa", "ok", "lo quiero", "agrégalo"):
     ¡ES OBLIGATORIO Y ESTRICTO EMITIR LA ACCIÓN "agregar_carrito" en el array "acciones"!
   - Cuando el cliente decida comprar o diga "lo quiero", "agrégalo", "me lo llevo", "sí":
     {"tipo": "agregar_carrito", "nombre": "Nombre exacto del producto", "precio": 660.00}
   - REGLA DE INTEGRIDAD: NUNCA digas en tu texto que el producto "ya está en el carrito" si no estás emitiendo al mismo tiempo la acción {"tipo": "agregar_carrito"} en el JSON.

FORMATO DE SALIDA (OBLIGATORIO JSON ESTRICTO):
Responde ÚNICAMENTE con un objeto JSON válido, sin texto antes ni después:
{
  "respuesta": "Tu mensaje natural como asesor aquí.",
  "acciones": []
}
PROMPT;

// ------------------------------------------------------------
// 7. Construir mensajes para la IA y llamar DeepSeekClient
// ------------------------------------------------------------
$mensajes = [['role' => 'system', 'content' => $systemPrompt]];
$historialReciente = array_slice($historial, -8);
foreach ($historialReciente as $msg) {
    if (isset($msg['role'], $msg['content'])) {
        $mensajes[] = ['role' => $msg['role'] === 'user' ? 'user' : 'assistant', 'content' => (string)$msg['content']];
    }
}
$mensajes[] = ['role' => 'user', 'content' => $pregunta];

$deepSeekClient = new DeepSeekClient();
$aiResult = $deepSeekClient->chat($mensajes, [
    'json_mode'   => false,
    'temperature' => 0.5,
    'max_tokens'  => 650,
]);

// ------------------------------------------------------------
// 8. Decodificación y saneamiento del JSON
// ------------------------------------------------------------
$jsonRespuesta = $aiResult['json'];
$rawRespuesta  = $aiResult['content'];

if (!$jsonRespuesta && $rawRespuesta !== null && trim($rawRespuesta) !== '') {
    $cleaned = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($rawRespuesta));
    $jsonRespuesta = json_decode($cleaned, true);
    if (!$jsonRespuesta && preg_match('/\{[\s\S]*"respuesta"[\s\S]*\}/', $rawRespuesta, $matches)) {
        $jsonRespuesta = json_decode($matches[0], true);
    }
}

if (!$jsonRespuesta || !isset($jsonRespuesta['respuesta'])) {
    $textoRespaldo = ($rawRespuesta !== null && trim($rawRespuesta) !== '')
        ? trim($rawRespuesta)
        : HumanPersona::mensajeFallo($telefono);

    $jsonRespuesta = [
        'respuesta' => $textoRespaldo,
        'acciones'  => []
    ];
}

$acciones = $jsonRespuesta['acciones'] ?? [];
if (!is_array($acciones)) {
    $acciones = [];
}

// Si la IA colocó acciones JSON adentro del texto de respuesta, las rescatamos a 'acciones'
if (preg_match_all('/\{[^{}]*"tipo"\s*:\s*"[^"]+"[^{}]*\}/s', (string)$jsonRespuesta['respuesta'], $embMatches)) {
    foreach ($embMatches[0] as $embStr) {
        $parsedEmb = json_decode($embStr, true);
        if ($parsedEmb && isset($parsedEmb['tipo'])) {
            $acciones[] = $parsedEmb;
        }
    }
}

$jsonRespuesta['respuesta'] = HumanPersona::limpiarRespuesta((string)$jsonRespuesta['respuesta']);

// ------------------------------------------------------------
// 9. FALLBACK INTELIGENTE DETERMINISTA (TURNO ACTUAL Y CONTEXTO)
// ------------------------------------------------------------
$preguntaActual = mb_strtolower($pregunta);
$respuestaActual = mb_strtolower($jsonRespuesta['respuesta']);

// A. Detectar si el usuario está pidiendo VER productos visualmente o dice que no los ve
$esPeticionVisual = (bool)preg_match('/(no (los? )?veo|mu[eé]strame|ver(los)?|ens[eé][ñn]ame|d[oó]nde est[aá]n|sigo esperando|p[aá]samelo|a ver|no veo)/ui', $preguntaActual);

// Mapeo exhaustivo de términos a IDs reales de categorías de la base de datos
$terminosACategoriaId = [
    // 1: Celulares & Smartphones
    'celular' => 1, 'celulares' => 1, 'smartphone' => 1, 'smartphones' => 1,
    'telefono' => 1, 'telefonos' => 1, 'teléfono' => 1, 'teléfonos' => 1,
    'iphone' => 1, 'galaxy' => 1, 'xiaomi' => 1, 'motorola' => 1, 'movil' => 1, 'móvil' => 1,
    // 2: Tablets
    'tablet' => 2, 'tablets' => 2, 'ipad' => 2, 'ipads' => 2, 'tab' => 2,
    // 3: Audio & Auriculares
    'audio' => 3, 'auricular' => 3, 'auriculares' => 3, 'audifono' => 3, 'audifonos' => 3,
    'audífono' => 3, 'audífonos' => 3, 'airpods' => 3, 'sony' => 3, 'bluetooth' => 3,
    // 4: Zapatillas & Calzado
    'zapatilla' => 4, 'zapatillas' => 4, 'calzado' => 4, 'tenis' => 4, 'sneakers' => 4,
    'pegasus' => 4, 'ultraboost' => 4, 'runner' => 4, 'nike' => 4, 'adidas' => 4,
    // 5: Poleras & Camisetas
    'polera' => 5, 'poleras' => 5, 'camiseta' => 5, 'camisetas' => 5, 'polo' => 5,
    'polos' => 5, 'ropa' => 5, 'prendas' => 5,
    // 6: Electrodomésticos de Cocina
    'cocina' => 6, 'licuadora' => 6, 'licuadoras' => 6, 'freidora' => 6, 'freidoras' => 6,
    'electrodomestico' => 6, 'electrodoméstico' => 6,
    // 7: Ropa de Cama & Hogar
    'hogar' => 7, 'cama' => 7, 'sabana' => 7, 'sabanas' => 7, 'sábanas' => 7,
    'edredon' => 7, 'edredón' => 7, 'plumon' => 7, 'plumón' => 7,
    // 8: Café & Alimentos Gourmet
    'cafe' => 8, 'café' => 8, 'alimentos' => 8, 'comida' => 8, 'gourmet' => 8, 'yungas' => 8,
];

// Identificar categoría activa en la pregunta actual
$categoriaDetectadaId = null;
foreach ($terminosACategoriaId as $term => $catId) {
    if (mb_stripos($preguntaActual, $term) !== false) {
        $categoriaDetectadaId = $catId;
        break;
    }
}

// Si es una petición visual ("muestrame", "no los veo", "sigo esperando") y no especificó la categoría en la pregunta:
// Buscar la categoría activa en el historial reciente
if ($esPeticionVisual && !$categoriaDetectadaId) {
    if (!empty($historial)) {
        foreach (array_reverse($historial) as $hMsg) {
            $hText = mb_strtolower((string)($hMsg['content'] ?? ''));
            foreach ($terminosACategoriaId as $term => $catId) {
                if (mb_stripos($hText, $term) !== false) {
                    $categoriaDetectadaId = $catId;
                    break 2;
                }
            }
        }
    }
    if (!$categoriaDetectadaId && !empty($ultimoProducto) && isset($mapaProductosPorNombre[mb_strtolower($ultimoProducto)])) {
        $categoriaDetectadaId = (int)$mapaProductosPorNombre[mb_strtolower($ultimoProducto)]['categoria_id'];
    }
}

// Verificar si ya se incluyeron tarjetas 'mostrar_producto'
$tieneMostrarProducto = false;
foreach ($acciones as $a) {
    if (isset($a['tipo']) && $a['tipo'] === 'mostrar_producto') {
        $tieneMostrarProducto = true;
        break;
    }
}

// Si el usuario pidió verlos o dice que no los ve, y no hay tarjetas en el chat:
// GENERAR TARJETAS 'mostrar_producto' DIRECTAMENTE
if ($esPeticionVisual && !$tieneMostrarProducto && $categoriaDetectadaId) {
    $prodsCat = array_values(array_filter($productos, fn($p) => (int)$p['categoria_id'] === (int)$categoriaDetectadaId));
    $prodsParaMostrar = array_slice($prodsCat, 0, 3);
    foreach ($prodsParaMostrar as $pm) {
        $acciones[] = [
            'tipo'        => 'mostrar_producto',
            'id'          => $pm['id'],
            'nombre'      => $pm['nombre'],
            'precio'      => $pm['precio'],
            'moneda'      => $simboloMoneda,
            'descripcion' => $pm['descripcion'],
            'imagen'      => $pm['imagen'],
        ];
    }
}

// Si la lista de acciones está completamente vacía (fallback determinista estándar)
if (empty($acciones)) {
    if ($categoriaDetectadaId) {
        $nomCat = '';
        foreach ($categoriasLista as $cl) {
            if ($cl['id'] === $categoriaDetectadaId) {
                $nomCat = $cl['nombre'];
                break;
            }
        }
        $acciones[] = [
            'tipo'           => 'filtrar_catalogo',
            'categoria_id'   => $categoriaDetectadaId,
            'mensaje_filtro' => "Mostrando {$nomCat} en el catálogo"
        ];
    } else {
        // Coincidencia con producto específico
        foreach ($productos as $prod) {
            $prodNomLower = mb_strtolower($prod['nombre']);
            if (mb_stripos($preguntaActual, $prodNomLower) !== false) {
                $acciones[] = [
                    'tipo'        => 'mostrar_producto',
                    'id'          => $prod['id'],
                    'nombre'      => $prod['nombre'],
                    'precio'      => $prod['precio'],
                    'moneda'      => $simboloMoneda,
                    'descripcion' => $prod['descripcion'],
                    'imagen'      => $prod['imagen'],
                ];
                break;
            }
        }
    }
}

// ------------------------------------------------------------
// 9.B. PROTECCIÓN Y DESESCALAMIENTO ANTE RECLAMOS (MODO SOPORTE)
// Si el cliente expresa una queja, retraso o reclamo, se desactivan ventas y se prioriza WhatsApp
// ------------------------------------------------------------
$esReclamoOQueja = (bool)preg_match('/(queja|reclamo|no lleg[oó]|demora|estafador|estafa|roto|dañado|fallado|garant[ií]a|devoluci[oó]n|quiero mi dinero|reembolso|enviaron mal|pedido no llega|mi pedido retrasado)/ui', $preguntaActual);

if ($esReclamoOQueja) {
    $acciones = [
        [
            'tipo'     => 'contacto_soporte',
            'telefono' => $telefono,
            'mensaje'  => "Atención prioritaria por WhatsApp: {$telefono}"
        ]
    ];
}

// ------------------------------------------------------------
// 9.C. RESOLUCIÓN DE REFERENCIAS ORDINALES Y RELATIVAS
// Resuelve: "el segundo", "el primero", "el último", "el más barato", "el más caro"
// ------------------------------------------------------------
$ultimosProductosMencionados = [];
if (!empty($historial)) {
    foreach (array_reverse($historial) as $h) {
        if (($h['role'] ?? '') === 'assistant') {
            $hContent = (string)($h['content'] ?? '');
            foreach ($productos as $p) {
                $pos = mb_stripos($hContent, $p['nombre']);
                if ($pos === false) {
                    $nomSimplificado = trim((string)preg_replace('/^(Smartphone|Zapatillas|Camiseta|Plumón Edredón|Juego de)\s+/ui', '', $p['nombre']));
                    if ($nomSimplificado !== '') {
                        $pos = mb_stripos($hContent, $nomSimplificado);
                    }
                }
                if ($pos !== false) {
                    $ultimosProductosMencionados[$pos] = $p;
                }
            }
            if (!empty($ultimosProductosMencionados)) {
                ksort($ultimosProductosMencionados);
                $ultimosProductosMencionados = array_values($ultimosProductosMencionados);
                break;
            }
        }
    }
}

$productoOrdinalDetectado = null;
if (!empty($ultimosProductosMencionados)) {
    if (preg_match('/(el|la)\s+(primer[oa]?|1er?o?)\b/ui', $preguntaActual)) {
        $productoOrdinalDetectado = $ultimosProductosMencionados[0] ?? null;
    } elseif (preg_match('/(el|la)\s+(segund[oa]|2d[oa])\b/ui', $preguntaActual)) {
        $productoOrdinalDetectado = $ultimosProductosMencionados[1] ?? null;
    } elseif (preg_match('/(el|la)\s+(tercer[oa]?|3er?o?)\b/ui', $preguntaActual)) {
        $productoOrdinalDetectado = $ultimosProductosMencionados[2] ?? null;
    } elseif (preg_match('/(el|la)\s+([uú]ltim[oa])\b/ui', $preguntaActual)) {
        $productoOrdinalDetectado = end($ultimosProductosMencionados) ?: null;
    } elseif (preg_match('/(el|la)\s+m[aá]s\s+(barat[oa]|econ[oó]mic[oa])\b/ui', $preguntaActual)) {
        $copiaProds = $ultimosProductosMencionados;
        usort($copiaProds, fn($a, $b) => $a['precio'] <=> $b['precio']);
        $productoOrdinalDetectado = $copiaProds[0] ?? null;
    } elseif (preg_match('/(el|la)\s+m[aá]s\s+(car[oa]|costos[oa]|tope de gama)\b/ui', $preguntaActual)) {
        $copiaProds = $ultimosProductosMencionados;
        usort($copiaProds, fn($a, $b) => $b['precio'] <=> $a['precio']);
        $productoOrdinalDetectado = $copiaProds[0] ?? null;
    }
}

// Producto identificado directamente en la respuesta generada por el asistente en este turno
$productoMencionadoEnRespuestaActual = null;
foreach ($productos as $p) {
    if (mb_stripos($jsonRespuesta['respuesta'], $p['nombre']) !== false) {
        $productoMencionadoEnRespuestaActual = $p;
        break;
    }
    $nomSimplificado = trim((string)preg_replace('/^(Smartphone|Zapatillas|Camiseta|Plumón Edredón|Juego de)\s+/ui', '', $p['nombre']));
    if ($nomSimplificado !== '' && mb_stripos($jsonRespuesta['respuesta'], $nomSimplificado) !== false) {
        $productoMencionadoEnRespuestaActual = $p;
        break;
    }
}

// Detección de Variante (Talla de calzado o ropa) en la pregunta
$varianteDetectada = null;
if (preg_match('/\b(?:talla|n[uú]mero|medida)?\s*(3[5-9]|4[0-6])\b/ui', $pregunta, $mSize)) {
    $varianteDetectada = 'Talla ' . trim($mSize[1]);
} elseif (preg_match('/\b(?:talla|medida)\s*(XS|S|M|L|XL|XXL)\b/ui', $pregunta, $mSizeLetra)) {
    $varianteDetectada = 'Talla ' . strtoupper(trim($mSizeLetra[1]));
}

// ------------------------------------------------------------
// 10. Validación y normalización de acciones (Seguridad y Precios Oficiales)
// ------------------------------------------------------------
$accionesNormalizadas = [];
foreach ($acciones as $acc) {
    if (!isset($acc['tipo'])) continue;

    if ($acc['tipo'] === 'contacto_soporte') {
        $accionesNormalizadas[] = [
            'tipo'     => 'contacto_soporte',
            'telefono' => $telefono,
            'mensaje'  => $acc['mensaje'] ?? "Atención prioritaria por WhatsApp: {$telefono}",
        ];
    } elseif ($acc['tipo'] === 'filtrar_catalogo') {
        $accionesNormalizadas[] = [
            'tipo'           => 'filtrar_catalogo',
            'categoria_id'   => isset($acc['categoria_id']) ? (int)$acc['categoria_id'] : null,
            'texto'          => $acc['texto'] ?? null,
            'mensaje_filtro' => $acc['mensaje_filtro'] ?? '✨ Catálogo actualizado',
        ];
    } elseif ($acc['tipo'] === 'mostrar_producto') {
        $nombre = $acc['nombre'] ?? '';
        $matched = null;
        if (isset($mapaProductosPorNombre[mb_strtolower($nombre)])) {
            $matched = $mapaProductosPorNombre[mb_strtolower($nombre)];
        } else {
            foreach ($productos as $p) {
                if (mb_stripos($nombre, $p['nombre']) !== false || mb_stripos($p['nombre'], $nombre) !== false) {
                    $matched = $p;
                    break;
                }
            }
        }
        if ($matched) {
            $accionesNormalizadas[] = [
                'tipo'        => 'mostrar_producto',
                'id'          => $matched['id'],
                'nombre'      => $matched['nombre'],
                'precio'      => $matched['precio'], // PRECIO OFICIAL GARANTIZADO DE LA BASE DE DATOS
                'moneda'      => $simboloMoneda,
                'descripcion' => $matched['descripcion'],
                'imagen'      => $matched['imagen'],
            ];
        }
    } elseif ($acc['tipo'] === 'agregar_carrito') {
        $nombre = trim($acc['nombre'] ?? '');
        $matched = null;

        // 1. Si el cliente usó una referencia ordinal explícita ("el segundo", "el primero", etc.), reconciliar con exactitud
        if ($productoOrdinalDetectado) {
            $matched = $productoOrdinalDetectado;
        } elseif ($productoMencionadoEnRespuestaActual) {
            $matched = $productoMencionadoEnRespuestaActual;
        } elseif (isset($mapaProductosPorNombre[mb_strtolower($nombre)])) {
            $matched = $mapaProductosPorNombre[mb_strtolower($nombre)];
        } else {
            foreach ($productos as $p) {
                $pNomLower = mb_strtolower($p['nombre']);
                $nomLower = mb_strtolower($nombre);
                if (mb_stripos($pNomLower, $nomLower) !== false || mb_stripos($nomLower, $pNomLower) !== false) {
                    $matched = $p;
                    break;
                }
            }
        }
        $accionesNormalizadas[] = [
            'tipo'     => 'agregar_carrito',
            'id'       => $matched['id'] ?? (int)($acc['id'] ?? 0),
            'nombre'   => $matched['nombre'] ?? ($nombre ?: $ultimoProducto),
            'precio'   => $matched['precio'] ?? (float)($acc['precio'] ?? 0), // Precios oficiales garantizados, inmune a regateos
            'stock'    => $matched['stock'] ?? 10,
            'imagen'   => $matched['imagen'] ?? '',
            'moneda'   => $simboloMoneda,
            'variante' => $varianteDetectada ?: ($acc['variante'] ?? null),
        ];
    }
}

// ------------------------------------------------------------
// 10.B. DETECCIÓN DETERMINISTA DE AGREGAR AL CARRITO
// Soporta referencias directas, búsqueda por palabra clave y resolución ordinal ("el segundo")
// ------------------------------------------------------------
$tieneAccionCarrito = false;
foreach ($accionesNormalizadas as $an) {
    if ($an['tipo'] === 'agregar_carrito') {
        $tieneAccionCarrito = true;
        break;
    }
}

if (!$tieneAccionCarrito && !$esReclamoOQueja) {
    // 1. ¿El usuario respondió afirmativamente ("si", "sí", "dale", "claro", "bueno", "ok") después de que el bot ofreció agregar al carrito?
    $esAfirmacion = (bool)preg_match('/^(s[ií]|dale|claro|bueno|ok|por\s*favor|porfa|por\s*fa|de\s*una|ya|obvio|perfecto|va|me\s*parece|anota|an[oó]talo|agr[ée]galo|agrega|comprar|lo\s*quiero|la\s*quiero|me\s*lo\s*llevo|me\s*la\s*llevo|adelante|an[oó]tame)\b/ui', trim($preguntaActual));

    $asistentePreguntoAgregar = false;
    if (!empty($historial)) {
        foreach (array_reverse($historial) as $h) {
            if (($h['role'] ?? '') === 'assistant') {
                $ultimoAsistenteMsg = (string)($h['content'] ?? '');
                if (preg_match('/(agr[ée]go|a[ñn]ad|carrito|bolsa|lo llevas|te lo preparo|te lo anoto|lo cerramos|lo agregamos|la agregamos)/ui', $ultimoAsistenteMsg)) {
                    $asistentePreguntoAgregar = true;
                }
                break;
            }
        }
    }

    // 2. ¿El propio texto generado por el asistente en este turno afirma que ya lo agregó al carrito?
    $asistenteAfirmaQueAgrego = (bool)preg_match('/(ya est[aá] en tu carrito|ya te (lo|la)?\s*(agregu[eé]|añad[ií])|agregad[oa] al carrito|añadid[oa] al carrito|te (lo|la)?\s*dejo en el carrito|lo tienes en tu carrito|la tienes en tu carrito)/ui', $jsonRespuesta['respuesta']);

    $esIntencionAgregar = preg_match('/(agr[ée]g|aña[de]|pon(me)?|m[ée]te(lo)?|cargar|comprar|quiero comprar|anota|an[oó]tame|llevar).*(carrito|bolsa|cesta)/ui', $pregunta)
        || preg_match('/(al carrito|a mi carrito|a la bolsa)/ui', $pregunta)
        || preg_match('/^(agrega|agr[ée]galo|a[ñn]ade|me llevo|lo quiero|la quiero|quiero|compro)\s+(el|la|un[oa]?)?\s*/ui', $pregunta)
        || ($esAfirmacion && $asistentePreguntoAgregar)
        || $asistenteAfirmaQueAgrego;

    if ($esIntencionAgregar) {
        $productoDetectado = null;

        // 1. Si hubo referencia ordinal explícita ("el segundo", "el primero", "el último", "el más barato")
        if ($productoOrdinalDetectado) {
            $productoDetectado = $productoOrdinalDetectado;
        }

        // 2. Si el asistente mencionó el producto específico en su respuesta actual
        if (!$productoDetectado && $productoMencionadoEnRespuestaActual) {
            $productoDetectado = $productoMencionadoEnRespuestaActual;
        }

        // 3. Buscar coincidencia directa por nombre de producto
        if (!$productoDetectado) {
            foreach ($productos as $p) {
                if (mb_stripos($pregunta, $p['nombre']) !== false) {
                    $productoDetectado = $p;
                    break;
                }
            }
        }

        // 4. Buscar palabras clave (ej: "galaxy", "iphone", "polo", "pegasus", "airpods", "licuadora")
        if (!$productoDetectado) {
            foreach ($productos as $p) {
                $palabras = explode(' ', mb_strtolower($p['nombre']));
                foreach ($palabras as $pal) {
                    if (mb_strlen($pal) >= 4 && mb_stripos($pregunta, $pal) !== false) {
                        $productoDetectado = $p;
                        break 2;
                    }
                }
            }
        }

        // 5. Fallback al último producto discutido en la conversación
        if (!$productoDetectado && !empty($ultimoProducto)) {
            if (isset($mapaProductosPorNombre[mb_strtolower($ultimoProducto)])) {
                $productoDetectado = $mapaProductosPorNombre[mb_strtolower($ultimoProducto)];
            }
        }

        if ($productoDetectado) {
            $accionesNormalizadas[] = [
                'tipo'     => 'agregar_carrito',
                'id'       => $productoDetectado['id'],
                'nombre'   => $productoDetectado['nombre'],
                'precio'   => $productoDetectado['precio'],
                'stock'    => $productoDetectado['stock'],
                'imagen'   => $productoDetectado['imagen'],
                'moneda'   => $simboloMoneda,
                'variante' => $varianteDetectada,
            ];
        }
    }
}

$resPayload = [
    'ok'        => true,
    'respuesta' => $jsonRespuesta['respuesta'],
    'acciones'  => $accionesNormalizadas,
];
if ($useRag) {
    $resPayload['rag_applied'] = $ragApplied;
}

// ------------------------------------------------------------
// 11. REGISTRO DE OBSERVABILIDAD Y TELEMETRÍA (DB v7)
// ------------------------------------------------------------
try {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $sessionId = session_id() ?: 'no-session';
    $tokensUsage = $aiResult['usage'] ?? [];
    $stmtLog = $pdo->prepare("
        INSERT INTO chat_logs 
        (session_id, pregunta, respuesta, tokens_usados, latencia_ms, tokens_prompt, tokens_completado, tokens_cache_hit, rag_score_max, rag_fuentes, acciones_json)
        VALUES 
        (:sid, :preg, :resp, :tok_tot, :lat, :tok_p, :tok_c, :tok_ch, :r_score, :r_fuentes, :acc)
    ");
    $stmtLog->execute([
        ':sid'        => $sessionId,
        ':preg'       => $pregunta,
        ':resp'       => $jsonRespuesta['respuesta'],
        ':tok_tot'    => (int)($tokensUsage['total_tokens'] ?? 0),
        ':lat'        => (int)($aiResult['latencia_ms'] ?? 0),
        ':tok_p'      => (int)($tokensUsage['prompt_tokens'] ?? 0),
        ':tok_c'      => (int)($tokensUsage['completion_tokens'] ?? 0),
        ':tok_ch'     => (int)($tokensUsage['cache_hit_tokens'] ?? 0),
        ':r_score'    => isset($ragResult['chunks'][0]['score']) ? (float)$ragResult['chunks'][0]['score'] : null,
        ':r_fuentes'  => isset($ragResult['chunks']) ? implode(', ', array_slice(array_column($ragResult['chunks'], 'title'), 0, 3)) : null,
        ':acc'        => json_encode($accionesNormalizadas, JSON_UNESCAPED_UNICODE),
    ]);
} catch (Throwable $e) {
    error_log('[OBSERVABILITY LOG ERROR] ' . $e->getMessage());
}

// ------------------------------------------------------------
// 12. RESPUESTA AL CLIENTE (SSE Opt-in o JSON Estándar)
// ------------------------------------------------------------
if ($isStreaming) {
    header('Content-Type: text/event-stream; charset=utf-8');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Evento inicial con metadatos y acciones del agente
    echo "event: meta\n";
    echo "data: " . json_encode(['acciones' => $accionesNormalizadas, 'rag_applied' => $ragApplied], JSON_UNESCAPED_UNICODE) . "\n\n";
    flush();

    // Transmitir chunks progresivos del texto conversacional
    $palabras = preg_split('/(\s+)/u', (string)$jsonRespuesta['respuesta'], -1, PREG_SPLIT_DELIM_CAPTURE) ?: [];
    foreach ($palabras as $p) {
        if ($p === '') continue;
        echo "data: " . json_encode(['delta' => $p], JSON_UNESCAPED_UNICODE) . "\n\n";
        flush();
        usleep(10000); // 10ms para suavidad visual
    }

    echo "event: done\n";
    echo "data: [DONE]\n\n";
    flush();
    exit;
}

// Respuesta normal síncrona JSON (100% retrocompatible con assets/js/chat.js)
echo json_encode($resPayload, JSON_UNESCAPED_UNICODE);