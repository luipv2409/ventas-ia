<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/chat/HumanPersona.php';

use Services\Chat\HumanPersona;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$pregunta = trim($body['pregunta'] ?? '');
$historial = $body['historial'] ?? [];

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
// 3. Catálogo completo con PRECIO OFICIAL CONVERTIDO (en Bs)
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
foreach ($rawProductos as $p) {
    $precioOficial = round(((float)$p['precio_base_usd']) * $tasaCambio, 2);
    $prodObj = [
        'id'               => (int)$p['id'],
        'nombre'           => $p['nombre'],
        'categoria_id'     => (int)$p['categoria_id'],
        'categoria'        => $p['categoria_nombre'] ?? 'General',
        'precio'           => $precioOficial, // PRECIO OFICIAL FINAL EN LA MONEDA DE LA TIENDA (Bs)
        'moneda'           => $simboloMoneda,
        'stock'            => (int)$p['stock'],
        'descripcion'      => $p['descripcion_corta'] ?? '',
        'imagen'           => $p['imagen_url'] ?? '',
    ];
    $productos[] = $prodObj;
    $mapaProductosPorNombre[mb_strtolower($p['nombre'])] = $prodObj;
}
$catalogoJSON = json_encode($productos, JSON_UNESCAPED_UNICODE);

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
// 6. System Prompt Asesor con Agente Inteligente de Filtros
// ------------------------------------------------------------
$reglasPersona = HumanPersona::reglas($nombreAsesor, $nombreTienda, $telefono);
$systemPrompt = <<<PROMPT
{$reglasPersona}

INFORMACIÓN DE LA TIENDA:
- Tienda: {$nombreTienda} | Dueño: {$dueno} | WhatsApp: {$telefono} | Email: {$email}
- Moneda oficial de venta: {$simboloMoneda} (Bolivianos). Tasa de cambio aplicada.
{$ragContexto}

CATÁLOGO ACTUAL (Precios 100% oficiales en {$simboloMoneda}):
{$catalogoJSON}

CATEGORÍAS DE LA TIENDA (con sus IDs para filtrar):
{$categoriasJSON}

Último producto conversado: "{$ultimoProducto}"

REGLAS CRÍTICAS DE PRECIOS Y MONEDA:
1. Todos los precios de venta son los indicados en el catálogo en {$simboloMoneda}.
   Ejemplos reales: Camiseta Polo Classic = {$simboloMoneda} 179.28. Zapatillas Runner Pro = {$simboloMoneda} 647.40. Smartphone Galaxy A55 = {$simboloMoneda} 3486.00.
2. JAMÁS menciones precios en números sin convertir (ej. no digas "18 Bs", di "{$simboloMoneda} 179.28"). Es peligroso dar precios erróneos. Cita siempre la cifra exacta en {$simboloMoneda}.

CAPACIDAD DE AGENTE INTELIGENTE — FILTRADO DE LA PÁGINA WEB:
En lugar de saturar la ventana del chat con muchas tarjetas, puedes controlar el catálogo de la página en vivo usando la acción "filtrar_catalogo".
- Cuando el cliente pida ver una categoría (ej. "tienen ropa?", "muéstrame celulares", "quiero ver productos de hogar", "qué tienen de comer"), DEBES emitir una acción "filtrar_catalogo" con el categoria_id correspondiente para que la página web del cliente se filtre y muestre automáticamente esos productos en pantalla.
- Cuando el cliente busque algo específico o por palabra (ej. "auriculares"), puedes emitir "filtrar_catalogo" con "texto": "auriculares".
- Habla con amabilidad indicando que ya le estás mostrando esa sección en pantalla (ej: "¡Por supuesto! Te acabo de filtrar nuestra sección de ropa en la pantalla para que puedas ver los modelos disponibles. ¿Buscas alguna talla o estilo en particular?").

ACCIONES DISPONIBLES EN "acciones":
1. Filtrar catálogo en pantalla:
   {"tipo": "filtrar_catalogo", "categoria_id": 2, "mensaje_filtro": "Mostrando Ropa"}
   o con texto de búsqueda libre:
   {"tipo": "filtrar_catalogo", "texto": "auriculares", "mensaje_filtro": "Buscando auriculares"}
2. Mostrar tarjeta individual de producto (solo si el cliente pide detalle específico de 1 producto puntual, máximo 1 o 2 tarjetas):
   {"tipo": "mostrar_producto", "nombre": "Nombre exacto", "precio": 179.28, "descripcion": "...", "imagen": "..."}
3. Agregar al carrito (cuando el cliente diga que sí, que lo quiere o que lo agregue):
   {"tipo": "agregar_carrito", "nombre": "Nombre exacto del producto", "precio": 179.28}

FORMATO DE SALIDA (OBLIGATORIO):
Responde siempre con un objeto JSON válido:
{
  "respuesta": "Tu mensaje natural como asesor aquí.",
  "acciones": []
}
PROMPT;

// ------------------------------------------------------------
// 7. Construir mensajes para la IA
// ------------------------------------------------------------
$mensajes = [['role' => 'system', 'content' => $systemPrompt]];
$historialReciente = array_slice($historial, -8);
foreach ($historialReciente as $msg) {
    if (isset($msg['role'], $msg['content'])) {
        $mensajes[] = ['role' => $msg['role'] === 'user' ? 'user' : 'assistant', 'content' => (string)$msg['content']];
    }
}
$mensajes[] = ['role' => 'user', 'content' => $pregunta];

function llamarIA(array $mensajes): ?string {
    $payload = json_encode([
        'model'       => DEEPSEEK_MODEL,
        'messages'    => $mensajes,
        'temperature' => 0.6,
        'max_tokens'  => 550,
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
        CURLOPT_TIMEOUT        => 20,
    ]);
    
    $response = curl_exec($ch);
    $httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        error_log('[CHAT ERROR] DeepSeek HTTP ' . $httpCode . ' Body: ' . $response);
        return null;
    }
    
    $data = json_decode($response, true);
    return $data['choices'][0]['message']['content'] ?? null;
}

$rawRespuesta = llamarIA($mensajes);

// ------------------------------------------------------------
// 8. Decodificación y saneamiento del JSON
// ------------------------------------------------------------
$jsonRespuesta = null;
if ($rawRespuesta !== null) {
    $cleaned = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($rawRespuesta));
    $jsonRespuesta = json_decode($cleaned, true);
    if (!$jsonRespuesta && preg_match('/\{[\s\S]*"respuesta"[\s\S]*\}/', $rawRespuesta, $matches)) {
        $jsonRespuesta = json_decode($matches[0], true);
    }
}

if (!$jsonRespuesta || !isset($jsonRespuesta['respuesta'])) {
    $jsonRespuesta = [
        'respuesta' => ($rawRespuesta !== null && trim($rawRespuesta) !== '')
            ? $rawRespuesta
            : HumanPersona::mensajeFallo($telefono),
        'acciones' => []
    ];
}

$acciones = $jsonRespuesta['acciones'] ?? [];
if (!is_array($acciones)) {
    $acciones = [];
}

// Si la IA colocó una acción JSON adentro del texto de respuesta, la rescatamos a 'acciones'
if (preg_match('/\{[^{}]*"tipo"\s*:\s*"[^"]+"[^{}]*\}/s', (string)$jsonRespuesta['respuesta'], $embAcc)) {
    $parsedEmb = json_decode($embAcc[0], true);
    if ($parsedEmb && isset($parsedEmb['tipo'])) {
        $acciones[] = $parsedEmb;
    }
}

$jsonRespuesta['respuesta'] = HumanPersona::limpiarRespuesta((string)$jsonRespuesta['respuesta']);

// ------------------------------------------------------------
// 9. FALLBACK INTELIGENTE DETERMINISTA (SOLO SOBRE EL TURNO ACTUAL)
// Evita concatenar historial pasado para no disparar tarjetas en "que pena" o "gracias"
// ------------------------------------------------------------
$preguntaActual = mb_strtolower($pregunta);
$respuestaActual = mb_strtolower($jsonRespuesta['respuesta']);

if (empty($acciones)) {
    // A. Detectar intención de ver o filtrar categoría en la pregunta actual
    $categoriaDetectadaId = null;
    $categoriaDetectadaNombre = '';

    // Mapeo de términos de usuario a categorías dinámicas
    $terminosCategoria = [
        'ropa'         => 'Ropa',
        'prendas'      => 'Ropa',
        'vestimenta'   => 'Ropa',
        'camiseta'     => 'Ropa',
        'camisetas'    => 'Ropa',
        'polo'         => 'Ropa',
        'polos'        => 'Ropa',
        'zapatilla'    => 'Ropa',
        'zapatillas'   => 'Ropa',
        'calzado'      => 'Ropa',
        'celular'      => 'Electrónica',
        'celulares'    => 'Electrónica',
        'smartphone'   => 'Electrónica',
        'smartphones'  => 'Electrónica',
        'telefono'     => 'Electrónica',
        'telefonos'    => 'Electrónica',
        'electronica'  => 'Electrónica',
        'electrónica'  => 'Electrónica',
        'auricular'    => 'Electrónica',
        'auriculares'  => 'Electrónica',
        'audifono'     => 'Electrónica',
        'audifonos'    => 'Electrónica',
        'tablet'       => 'Electrónica',
        'tablets'      => 'Electrónica',
        'hogar'        => 'Hogar',
        'casa'         => 'Hogar',
        'licuadora'    => 'Hogar',
        'licuadoras'   => 'Hogar',
        'sabana'       => 'Hogar',
        'sabanas'      => 'Hogar',
        'sábanas'      => 'Hogar',
        'alimentos'    => 'Alimentos',
        'comida'       => 'Alimentos',
        'cafe'         => 'Alimentos',
        'café'         => 'Alimentos',
    ];

    // Verificar si el usuario está preguntando por una categoría o tipo de productos
    $esConsultaCategoria = preg_match('/(tienen|tendr[aá]s?|busco|quiero|mu[eé]strame|ver|secci[oó]n|cat[aá]logo|hay|tienes|recomienda)/ui', $preguntaActual)
        || count(explode(' ', trim($preguntaActual))) <= 3;

    if ($esConsultaCategoria) {
        foreach ($terminosCategoria as $term => $catNombre) {
            if (mb_stripos($preguntaActual, $term) !== false) {
                if (isset($mapaCategoriasId[mb_strtolower($catNombre)])) {
                    $categoriaDetectadaId = $mapaCategoriasId[mb_strtolower($catNombre)];
                    $categoriaDetectadaNombre = $catNombre;
                    break;
                }
            }
        }
        // También verificar nombres literales de categorías en la DB
        if (!$categoriaDetectadaId) {
            foreach ($mapaCategoriasId as $catNomLower => $cid) {
                if (mb_stripos($preguntaActual, $catNomLower) !== false) {
                    $categoriaDetectadaId = $cid;
                    $categoriaDetectadaNombre = ucfirst($catNomLower);
                    break;
                }
            }
        }
    }

    if ($categoriaDetectadaId) {
        $acciones[] = [
            'tipo'           => 'filtrar_catalogo',
            'categoria_id'   => $categoriaDetectadaId,
            'mensaje_filtro' => "Mostrando {$categoriaDetectadaNombre} en el catálogo"
        ];
    } else {
        // B. Si el usuario pregunta puntualmente por un solo producto específico
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
                break; // Máximo 1 tarjeta puntual
            }
        }
    }
}

// ------------------------------------------------------------
// 10. Validación y normalización de acciones (Seguridad y Precio Oficial)
// ------------------------------------------------------------
$accionesNormalizadas = [];
foreach ($acciones as $acc) {
    if (!isset($acc['tipo'])) continue;

    if ($acc['tipo'] === 'filtrar_catalogo') {
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
                'precio'      => $matched['precio'], // PRECIO OFICIAL GARANTIZADO
                'moneda'      => $simboloMoneda,
                'descripcion' => $matched['descripcion'],
                'imagen'      => $matched['imagen'],
            ];
        }
    } elseif ($acc['tipo'] === 'agregar_carrito') {
        $nombre = trim($acc['nombre'] ?? '');
        $matched = null;
        if (isset($mapaProductosPorNombre[mb_strtolower($nombre)])) {
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
            'tipo'   => 'agregar_carrito',
            'id'     => $matched['id'] ?? (int)($acc['id'] ?? 0),
            'nombre' => $matched['nombre'] ?? ($nombre ?: $ultimoProducto),
            'precio' => $matched['precio'] ?? (float)($acc['precio'] ?? 0),
            'stock'  => $matched['stock'] ?? 10,
            'imagen' => $matched['imagen'] ?? '',
            'moneda' => $simboloMoneda,
        ];
    }
}

// ------------------------------------------------------------
// 10.B. DETECCIÓN DETERMINISTA DE AGREGAR AL CARRITO
// Si el cliente pide expresamente agregar/añadir/comprar un producto al carrito
// ------------------------------------------------------------
$tieneAccionCarrito = false;
foreach ($accionesNormalizadas as $an) {
    if ($an['tipo'] === 'agregar_carrito') {
        $tieneAccionCarrito = true;
        break;
    }
}

if (!$tieneAccionCarrito) {
    $esIntencionAgregar = preg_match('/(agr[ée]g|aña[de]|pon(me)?|m[ée]te(lo)?|cargar|comprar|quiero comprar|anota|an[oó]tame|llevar).*(carrito|bolsa|cesta)/ui', $pregunta)
        || preg_match('/(al carrito|a mi carrito|a la bolsa)/ui', $pregunta);

    if ($esIntencionAgregar) {
        $productoDetectado = null;
        // 1. Buscar coincidencia directa por nombre de producto
        foreach ($productos as $p) {
            if (mb_stripos($pregunta, $p['nombre']) !== false) {
                $productoDetectado = $p;
                break;
            }
        }
        // 2. Si no mencionó el nombre entero, buscar palabras clave (ej: "galaxy", "iphone", "polo", "pegasus", "airpods")
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
        // 3. Fallback al último producto discutido en la conversación
        if (!$productoDetectado && !empty($ultimoProducto)) {
            if (isset($mapaProductosPorNombre[mb_strtolower($ultimoProducto)])) {
                $productoDetectado = $mapaProductosPorNombre[mb_strtolower($ultimoProducto)];
            }
        }

        if ($productoDetectado) {
            $accionesNormalizadas[] = [
                'tipo'   => 'agregar_carrito',
                'id'     => $productoDetectado['id'],
                'nombre' => $productoDetectado['nombre'],
                'precio' => $productoDetectado['precio'],
                'stock'  => $productoDetectado['stock'],
                'imagen' => $productoDetectado['imagen'],
                'moneda' => $simboloMoneda,
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
echo json_encode($resPayload, JSON_UNESCAPED_UNICODE);