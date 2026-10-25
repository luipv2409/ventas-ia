<?php
declare(strict_types=1);

namespace Services\Chat;

use PDO;
use Throwable;
use Services\Rag\RagService;

require_once __DIR__ . '/HumanPersona.php';
require_once __DIR__ . '/DeepSeekClient.php';
require_once __DIR__ . '/DomainProfile.php';
require_once __DIR__ . '/InputSanitizer.php';
require_once __DIR__ . '/ContextWindow.php';

/**
 * Motor centralizado y reutilizable para el chatbot de ventas con IA.
 *
 * ÚNICO punto de lógica conversacional: lo consumen api/chat_frontend.php (web),
 * api/chat_externo.php (WhatsApp / Messenger / n8n / API) y cualquier canal futuro.
 * Contiene RAG, catálogo, guardarraíles, validación de acciones y telemetría.
 *
 * Todo lo específico del rubro (alias, sinónimos, tallas, venta cruzada) vive en el
 * DomainProfile (config/domain_profile.json), no en este archivo.
 *
 * Principios de seguridad:
 *  - Los precios, ids y stock de cualquier acción salen SIEMPRE del catálogo de la BD; jamás del LLM
 *    ni del cliente. Si una acción no puede asociarse a un producto real, se descarta.
 *  - Toda entrada (mensaje e historial) se sanea y se acota antes de llegar al LLM.
 */
class ChatEngine
{
    /** Canales de mensajería donde no existe carrito web ni tarjetas visuales. */
    private const CANALES_SIN_CARRITO = ['whatsapp', 'messenger', 'telegram', 'instagram', 'sms'];

    private const REGEX_RECLAMO = '/(?<![\p{L}\p{N}])(queja|reclamo|no lleg[oó]|demora|estafador|estafa|roto|dañado|fallado|garant[ií]a|devoluci[oó]n|quiero mi dinero|reembolso|enviaron mal|pedido no llega|mi pedido retrasado)/ui';

    private const REGEX_PETICION_VISUAL = '/(?<![\p{L}\p{N}])(no (los? )?veo|mu[eé]strame|ver(los)?|ens[eé][ñn]ame|d[oó]nde est[aá]n|sigo esperando|p[aá]samelo|a ver)(?![\p{L}\p{N}])/ui';

    private PDO $pdo;
    private DeepSeekClient $client;
    private DomainProfile $profile;
    private int $catalogMaxItems;
    private string $storeUrl;

    private array $infoNegocio = [];
    private array $productos = [];
    private array $mapaProductosPorNombre = [];
    private array $categoriasLista = [];
    private array $mapaCategoriasId = [];
    /** @var array<string, int> término (minúsculas) => id de categoría */
    private array $terminosCategoria = [];
    private ?string $regexPrefijos = null;
    private float $tasaCambio = 1.0;
    private string $simboloMoneda = 'Bs';
    private string $nombreAsesor = 'Valeria';
    private string $nombreTienda = 'Nuestra Tienda';
    private string $telefono = '';
    private string $email = '';
    private string $dueno = '';

    /**
     * @param array{profile?: DomainProfile, catalog_max_items?: int, store_url?: string} $config
     */
    public function __construct(PDO $pdo, ?DeepSeekClient $client = null, array $config = [])
    {
        $this->pdo    = $pdo;
        $this->client = $client ?? new DeepSeekClient();
        $this->profile = $config['profile'] ?? DomainProfile::default();
        $this->catalogMaxItems = max(1, (int)($config['catalog_max_items']
            ?? (defined('CHAT_CATALOG_MAX_ITEMS') ? CHAT_CATALOG_MAX_ITEMS : 60)));
        $this->storeUrl = trim((string)($config['store_url']
            ?? (defined('STORE_PUBLIC_URL') ? STORE_PUBLIC_URL : '')));

        $prefijos = $this->profile->prefijosIgnorables();
        if (!empty($prefijos)) {
            $this->regexPrefijos = '/^(?:' . implode('|', array_map(fn($p) => preg_quote($p, '/'), $prefijos)) . ')\s+/ui';
        }

        $this->cargarConfiguracionYCatalogo();
    }

    // =====================================================================================
    // CARGA DE CONFIGURACIÓN Y CATÁLOGO
    // =====================================================================================

    /**
     * Carga en memoria la información de la tienda, categorías y productos oficiales en Bs.
     */
    private function cargarConfiguracionYCatalogo(): void
    {
        // 1. Configuración de la tienda
        $stmtInfo = $this->pdo->query("SELECT clave, valor FROM configuracion WHERE clave IN ('nombre_tienda', 'contacto_telefono', 'contacto_email', 'nombre_dueño', 'nombre_asesor', 'moneda_simbolo', 'tasa_cambio_usd')");
        if ($stmtInfo) {
            while ($row = $stmtInfo->fetch(PDO::FETCH_ASSOC)) {
                $this->infoNegocio[$row['clave']] = (string)$row['valor'];
            }
        }
        $this->nombreTienda  = $this->infoNegocio['nombre_tienda'] ?? 'Nuestra Tienda';
        $this->telefono      = $this->infoNegocio['contacto_telefono'] ?? 'No disponible';
        $this->email         = $this->infoNegocio['contacto_email'] ?? 'No disponible';
        $this->dueno         = $this->infoNegocio['nombre_dueño'] ?? 'No definido';
        $this->nombreAsesor  = HumanPersona::nombreAsesor($this->infoNegocio);
        $this->simboloMoneda = $this->infoNegocio['moneda_simbolo'] ?? 'Bs';
        $this->tasaCambio    = (float)($this->infoNegocio['tasa_cambio_usd'] ?? 1.0);
        if ($this->tasaCambio <= 0) {
            $this->tasaCambio = 1.0;
        }

        // 2. Categorías dinámicas
        $categoriasRaw = $this->pdo->query("SELECT id, nombre FROM categorias ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($categoriasRaw as $c) {
            $cid = (int)$c['id'];
            $cnom = trim((string)$c['nombre']);
            $this->categoriasLista[] = ['id' => $cid, 'nombre' => $cnom];
            $this->mapaCategoriasId[mb_strtolower($cnom)] = $cid;
        }

        // 3. Catálogo completo con precios oficiales en Bs
        $stmtProd = $this->pdo->query("
            SELECT p.id, p.nombre,
                   SUBSTRING(p.descripcion, 1, 95) AS descripcion_corta,
                   p.precio AS precio_base_usd, p.stock, p.imagen_url, p.categoria_id,
                   c.nombre AS categoria_nombre
            FROM productos p
            LEFT JOIN categorias c ON p.categoria_id = c.id
            WHERE p.activo = 1 AND p.stock > 0
        ");
        $rawProductos = $stmtProd ? $stmtProd->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($rawProductos as $p) {
            $precioOficial = round(((float)$p['precio_base_usd']) * $this->tasaCambio, 2);
            $catNom = $p['categoria_nombre'] ?? 'General';
            $prodObj = [
                'id'           => (int)$p['id'],
                'nombre'       => (string)$p['nombre'],
                'categoria_id' => (int)$p['categoria_id'],
                'categoria'    => $catNom,
                'precio'       => $precioOficial,
                'moneda'       => $this->simboloMoneda,
                'stock'        => (int)$p['stock'],
                'descripcion'  => (string)($p['descripcion_corta'] ?? ''),
                'imagen'       => (string)($p['imagen_url'] ?? ''),
            ];
            $this->productos[] = $prodObj;
            $this->mapaProductosPorNombre[mb_strtolower($p['nombre'])] = $prodObj;
        }

        $this->construirTerminosCategoria();
    }

    /**
     * Términos que identifican cada categoría: los del perfil (solo de categorías que existen en la BD)
     * más los derivados automáticamente del nombre de cada categoría.
     */
    private function construirTerminosCategoria(): void
    {
        $idsValidos = array_column($this->categoriasLista, 'id');
        $mapa = [];

        foreach ($this->profile->terminosCategoria() as $id => $terminos) {
            if (!in_array($id, $idsValidos, true)) {
                continue;
            }
            foreach ($terminos as $t) {
                $mapa[mb_strtolower($t)] ??= $id;
            }
        }
        foreach ($this->categoriasLista as $c) {
            $tokens = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($c['nombre']), -1, PREG_SPLIT_NO_EMPTY) ?: [];
            foreach ($tokens as $tok) {
                if (mb_strlen($tok) >= 4) {
                    $mapa[$tok] ??= $c['id'];
                }
            }
        }
        $this->terminosCategoria = $mapa;
    }

    // =====================================================================================
    // SESIONES EXTERNAS (WhatsApp / Messenger / API)
    // =====================================================================================

    /**
     * Recupera el historial previo de mensajes para un remitente en un canal dado.
     * Solo considera mensajes recientes: una conversación retomada semanas después no debe
     * arrastrar referencias ("agrégalo") a productos de otra charla.
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function obtenerHistorialSesion(string $canal, string $remitente, int $limite = 8, int $maxHoras = 72): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT rol, mensaje
                FROM chat_externo_sesiones
                WHERE canal = :canal AND remitente = :remitente
                  AND creado_en >= (NOW() - INTERVAL :horas HOUR)
                ORDER BY id DESC
                LIMIT :limite
            ");
            $stmt->bindValue(':canal', $canal);
            $stmt->bindValue(':remitente', $remitente);
            $stmt->bindValue(':horas', max(1, $maxHoras), PDO::PARAM_INT);
            $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $historial = [];
            foreach (array_reverse($rows) as $r) {
                $historial[] = [
                    'role'    => $r['rol'] === 'assistant' ? 'assistant' : 'user',
                    'content' => (string)$r['mensaje']
                ];
            }
            return $historial;
        } catch (Throwable $e) {
            error_log('[ChatEngine] Error obteniendo historial de sesión: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Guarda un mensaje (usuario o asistente) en la tabla de sesiones externas.
     */
    public function guardarMensajeSesion(string $canal, string $remitente, string $rol, string $mensaje, ?array $metadata = null): void
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO chat_externo_sesiones (canal, remitente, rol, mensaje, metadata)
                VALUES (:canal, :remitente, :rol, :mensaje, :meta)
            ");
            $stmt->execute([
                ':canal'     => $canal,
                ':remitente' => $remitente,
                ':rol'       => $rol,
                ':mensaje'   => $mensaje,
                ':meta'      => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
            ]);
        } catch (Throwable $e) {
            error_log('[ChatEngine] Error guardando mensaje de sesión: ' . $e->getMessage());
        }
    }

    // =====================================================================================
    // PROCESAMIENTO PRINCIPAL
    // =====================================================================================

    /**
     * Alias de procesarMensaje para conveniencia y compatibilidad de firmas.
     */
    public function procesarPregunta(string $pregunta, array $historial = [], bool|array $useRag = true, array $opciones = []): array
    {
        if (is_array($useRag)) {
            $opciones = $useRag;
            $useRag = (bool)($opciones['use_rag'] ?? true);
        }
        return $this->procesarMensaje($pregunta, $historial, (bool)$useRag, $opciones);
    }

    /**
     * Procesa una consulta de usuario y genera una respuesta completa y validada.
     *
     * @param string $pregunta Texto del mensaje enviado por el cliente
     * @param array $historial Array con roles y contenidos previos
     * @param bool $useRag Indica si se recupera contexto de la base de conocimiento
     * @param array $opciones Opciones adicionales (canal, remitente, sessionId)
     * @return array Estructura estandarizada de respuesta
     */
    public function procesarMensaje(string $pregunta, array $historial = [], bool $useRag = true, array $opciones = []): array
    {
        $canal     = (string)($opciones['canal'] ?? 'web');
        $remitente = (string)($opciones['remitente'] ?? 'anonimo');
        $sessionLogId = (string)($opciones['sessionId'] ?? "{$canal}:{$remitente}");

        // 0. Higiene de entrada: mensaje e historial son datos no confiables
        $entradaTruncada  = InputSanitizer::exceedsLimit(
            InputSanitizer::cleanText($pregunta, PHP_INT_MAX)
        );
        $preguntaOriginal = InputSanitizer::cleanText($pregunta, InputSanitizer::MAX_MESSAGE_CHARS);
        $historial = ContextWindow::trimHistory(InputSanitizer::sanitizeHistory($historial));

        if ($preguntaOriginal === '') {
            return $this->respuestaSinLLM($canal, $remitente, 'Uy, no me llegó bien tu mensaje. ¿Me lo escribes de nuevo?', $entradaTruncada);
        }

        // 1-2. Sinónimos y detección de presupuesto
        $preguntaTratada = $this->aplicarPresupuesto($this->aplicarSinonimos($preguntaOriginal));

        // 3. Último producto conversado
        $ultimoProducto = $this->detectarUltimoProducto($historial);

        // 4. RAG contextual
        $rag = $this->recuperarRag($useRag, $preguntaTratada, $historial);

        // 5-6. Catálogo + System Prompt
        $systemPrompt = $this->construirSystemPrompt($canal, $rag['contexto'], $preguntaTratada, $ultimoProducto);

        $mensajes = [['role' => 'system', 'content' => $systemPrompt]];
        foreach ($historial as $msg) {
            $mensajes[] = ['role' => $msg['role'], 'content' => $msg['content']];
        }
        $mensajes[] = ['role' => 'user', 'content' => $preguntaTratada];

        // 7. Llamada al LLM
        $aiResult = $this->client->chat($mensajes, [
            'json_mode'   => false,
            'temperature' => 0.65,
            'max_tokens'  => 650,
        ]);
        $degraded = !($aiResult['ok'] ?? false);

        $jsonRespuesta = $this->parsearRespuestaLLM($aiResult);
        $acciones = is_array($jsonRespuesta['acciones'] ?? null) ? $jsonRespuesta['acciones'] : [];

        // Rescate de JSON incrustado en el texto
        if (preg_match_all('/\{[^{}]*"tipo"\s*:\s*"[^"]+"[^{}]*\}/s', (string)$jsonRespuesta['respuesta'], $embMatches)) {
            foreach ($embMatches[0] as $embStr) {
                $parsedEmb = json_decode($embStr, true);
                if ($parsedEmb && isset($parsedEmb['tipo'])) {
                    $acciones[] = $parsedEmb;
                }
            }
        }

        $respuestaTexto = HumanPersona::limpiarRespuesta((string)$jsonRespuesta['respuesta']);

        // 8-10. Validación determinista de acciones (precios y productos siempre desde la BD)
        $accionesNormalizadas = $this->resolverAcciones($acciones, $preguntaTratada, $respuestaTexto, $historial, $ultimoProducto);

        // 10. Formato para el canal
        $textoCanal = $this->formatearParaCanal($respuestaTexto, $accionesNormalizadas, $canal);

        // 11. Observabilidad
        $tokensUsage = $aiResult['usage'] ?? [];
        $this->registrarTelemetria($sessionLogId, $preguntaOriginal, $respuestaTexto, $aiResult, $rag, $accionesNormalizadas);

        return [
            'ok'               => true,
            'canal'            => $canal,
            'remitente'        => $remitente,
            'respuesta'        => $respuestaTexto,
            'whatsapp_texto'   => $textoCanal,
            'acciones'         => $accionesNormalizadas,
            'rag_applied'      => $rag['aplicado'],
            'rag_fuentes'      => $rag['fuentes'],
            'rag_score_max'    => $rag['score_max'],
            'tokens_usage'     => $tokensUsage,
            'latencia_ms'      => (int)($aiResult['latencia_ms'] ?? 0),
            'degraded'         => $degraded,
            'entrada_truncada' => $entradaTruncada,
            'whatsapp_payload' => [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $remitente,
                'type'              => 'text',
                'text'              => [
                    'preview_url' => false,
                    'body'        => $textoCanal
                ]
            ]
        ];
    }

    /**
     * Respuesta inmediata sin gastar tokens (mensaje vacío tras la limpieza).
     */
    private function respuestaSinLLM(string $canal, string $remitente, string $texto, bool $truncada): array
    {
        return [
            'ok'               => true,
            'canal'            => $canal,
            'remitente'        => $remitente,
            'respuesta'        => $texto,
            'whatsapp_texto'   => $texto,
            'acciones'         => [],
            'rag_applied'      => false,
            'rag_fuentes'      => [],
            'rag_score_max'    => null,
            'tokens_usage'     => [],
            'latencia_ms'      => 0,
            'degraded'         => false,
            'entrada_truncada' => $truncada,
            'whatsapp_payload' => [
                'messaging_product' => 'whatsapp',
                'recipient_type'    => 'individual',
                'to'                => $remitente,
                'type'              => 'text',
                'text'              => ['preview_url' => false, 'body' => $texto],
            ],
        ];
    }

    // =====================================================================================
    // PRE-PROCESAMIENTO
    // =====================================================================================

    /**
     * Sustituye sinónimos por palabra completa y de la clave más larga a la más corta
     * ("celulares" se evalúa antes que "celular"; antes se producían reemplazos parciales).
     */
    private function aplicarSinonimos(string $texto): string
    {
        foreach ($this->profile->sinonimos() as $clave => $valor) {
            $patron = '/(?<![\p{L}\p{N}])' . preg_quote($clave, '/') . '(?![\p{L}\p{N}])/iu';
            $texto  = (string)preg_replace($patron, str_replace(['\\', '$'], ['\\\\', '\\$'], $valor), $texto);
        }
        return $texto;
    }

    /**
     * Un mensaje que es solo un monto se interpreta como presupuesto. Los números pequeños sin
     * moneda (ej. "42" como respuesta a "¿qué talla?") NO son presupuesto.
     */
    private function aplicarPresupuesto(string $texto): string
    {
        if (preg_match('/^(\d+(?:[.,]\d+)?)\s*(\$|bs\.?|bolivianos)?$/iu', trim($texto), $m)) {
            $monto = (float)str_replace(',', '.', $m[1]);
            $tieneMoneda = !empty($m[2]);
            if ($tieneMoneda || $monto >= 100) {
                return "Mi presupuesto es de aproximadamente {$this->simboloMoneda} {$monto}. ¿Qué me recomiendas?";
            }
        }
        return $texto;
    }

    private function nombreSimplificado(string $nombre): string
    {
        if ($this->regexPrefijos === null) {
            return trim($nombre);
        }
        return trim((string)preg_replace($this->regexPrefijos, '', $nombre));
    }

    /**
     * Busca un alias del perfil (como palabra completa, el más largo primero) y devuelve el producto oficial.
     */
    private function buscarPorAlias(string $textoLower): ?array
    {
        $aliases = $this->profile->aliasesModelos();
        uksort($aliases, fn($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        foreach ($aliases as $alias => $oficial) {
            $patron = '/(?<![\p{L}\p{N}])' . preg_quote($alias, '/') . '(?![\p{L}\p{N}])/u';
            if (preg_match($patron, $textoLower) === 1) {
                $prod = $this->mapaProductosPorNombre[mb_strtolower($oficial)] ?? null;
                if ($prod !== null) {
                    return $prod;
                }
            }
        }
        return null;
    }

    /**
     * Último producto mencionado en la conversación (más reciente primero).
     */
    private function detectarUltimoProducto(array $historial): string
    {
        foreach (array_reverse($historial) as $msg) {
            $msgContent = mb_strtolower((string)($msg['content'] ?? ''));
            if ($msgContent === '') {
                continue;
            }

            // 1. Nombre oficial completo
            foreach ($this->productos as $p) {
                if (mb_stripos($msgContent, mb_strtolower($p['nombre'])) !== false) {
                    return $p['nombre'];
                }
            }
            // 2. Alias o términos distintivos del modelo (perfil de dominio)
            $porAlias = $this->buscarPorAlias($msgContent);
            if ($porAlias !== null) {
                return $porAlias['nombre'];
            }
            // 3. Nombre simplificado (sin prefijo de categoría)
            foreach ($this->productos as $p) {
                $simple = mb_strtolower($this->nombreSimplificado($p['nombre']));
                if (mb_strlen($simple) >= 4 && mb_stripos($msgContent, $simple) !== false) {
                    return $p['nombre'];
                }
            }
        }
        return '';
    }

    /**
     * @return array{contexto: string, aplicado: bool, fuentes: array, score_max: ?float}
     */
    private function recuperarRag(bool $useRag, string $consulta, array $historial): array
    {
        $out = ['contexto' => '', 'aplicado' => false, 'fuentes' => [], 'score_max' => null, 'chunks' => []];
        if (!$useRag) {
            return $out;
        }
        try {
            require_once __DIR__ . '/../rag/RagService.php';
            $ragService = new RagService($this->pdo);
            $ragResult  = $ragService->retrieveContext($consulta, 3, RagService::DEFAULT_MIN_SCORE, $historial);
            if ($ragResult['found']) {
                $out['contexto'] = "\n\n" . $ragResult['context'];
                $out['aplicado'] = true;
                if (!empty($ragResult['chunks'])) {
                    $out['score_max'] = (float)($ragResult['chunks'][0]['score'] ?? 0.0);
                    $out['fuentes']   = array_slice(array_column($ragResult['chunks'], 'title'), 0, 3);
                }
            }
        } catch (Throwable $e) {
            error_log('[ChatEngine RAG Error] ' . $e->getMessage());
        }
        return $out;
    }

    // =====================================================================================
    // SYSTEM PROMPT Y CATÁLOGO
    // =====================================================================================

    private function esCanalSinCarrito(string $canal): bool
    {
        return in_array(mb_strtolower($canal), self::CANALES_SIN_CARRITO, true);
    }

    /**
     * Catálogo en texto. Si hay más de catalog_max_items productos activos, el prompt NO crece sin
     * límite: se envía un resumen por categoría + los artículos más relevantes para la consulta.
     * (Las acciones se validan siempre contra el catálogo completo en memoria.)
     *
     * @return array{texto: string, parcial: bool}
     */
    private function construirCatalogoTexto(string $consulta, string $ultimoProducto): array
    {
        $parcial = count($this->productos) > $this->catalogMaxItems;
        $lista   = $parcial
            ? $this->seleccionarProductosRelevantes($consulta, $ultimoProducto, $this->catalogMaxItems)
            : $this->productos;

        $texto = '';
        if ($parcial) {
            $resumen = [];
            foreach ($this->productos as $p) {
                $c = $p['categoria'];
                $resumen[$c]['n']   = ($resumen[$c]['n'] ?? 0) + 1;
                $resumen[$c]['min'] = min($resumen[$c]['min'] ?? $p['precio'], $p['precio']);
                $resumen[$c]['max'] = max($resumen[$c]['max'] ?? $p['precio'], $p['precio']);
            }
            $texto .= "RESUMEN DE CATEGORÍAS (catálogo total: " . count($this->productos) . " artículos):\n";
            foreach ($resumen as $c => $r) {
                $texto .= sprintf("• %s: %d artículos, precios de %s %.2f a %s %.2f\n", $c, $r['n'], $this->simboloMoneda, $r['min'], $this->simboloMoneda, $r['max']);
            }
            $texto .= "\nARTÍCULOS MÁS RELEVANTES PARA ESTA CONSULTA:\n";
        }

        $porCategoria = [];
        foreach ($lista as $p) {
            $porCategoria[$p['categoria']][] = $p;
        }
        foreach ($porCategoria as $catNom => $prods) {
            $catId = $prods[0]['categoria_id'] ?? 0;
            $texto .= "• CATEGORÍA [ID: {$catId}] {$catNom} (" . count($prods) . " artículos" . ($parcial ? ' mostrados' : ' disponibles') . "):\n";
            foreach ($prods as $p) {
                $texto .= sprintf(
                    "   - [ID: %d] %s | %s %.2f | Stock: %d | Info: %s\n",
                    $p['id'], $p['nombre'], $this->simboloMoneda, $p['precio'], $p['stock'], $p['descripcion']
                );
            }
            $texto .= "\n";
        }
        return ['texto' => $texto, 'parcial' => $parcial];
    }

    /**
     * Puntúa productos por coincidencia de términos de la consulta (nombre, categoría, descripción).
     */
    private function seleccionarProductosRelevantes(string $consulta, string $ultimoProducto, int $max): array
    {
        $tokens = array_unique(array_filter(
            preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($consulta), -1, PREG_SPLIT_NO_EMPTY) ?: [],
            fn($t) => mb_strlen($t) >= 3
        ));
        $scored = [];
        foreach ($this->productos as $i => $p) {
            $haystack = mb_strtolower($p['nombre'] . ' ' . $p['categoria'] . ' ' . $p['descripcion']);
            $score = 0;
            foreach ($tokens as $t) {
                if (mb_strpos($haystack, $t) !== false) {
                    $score++;
                }
            }
            if ($ultimoProducto !== '' && mb_strtolower($p['nombre']) === mb_strtolower($ultimoProducto)) {
                $score += 100;
            }
            $scored[] = ['score' => $score, 'idx' => $i];
        }
        usort($scored, fn($a, $b) => $b['score'] <=> $a['score'] ?: $a['idx'] <=> $b['idx']);
        return array_map(fn($s) => $this->productos[$s['idx']], array_slice($scored, 0, $max));
    }

    private function construirSystemPrompt(string $canal, string $ragContexto, string $consulta, string $ultimoProducto): string
    {
        $catalogo        = $this->construirCatalogoTexto($consulta, $ultimoProducto);
        $catalogoTexto   = $catalogo['texto'];
        $categoriasJSON  = json_encode($this->categoriasLista, JSON_UNESCAPED_UNICODE);
        $reglasPersona   = HumanPersona::reglas($this->nombreAsesor, $this->nombreTienda, $this->telefono, $this->profile);

        $reglaCatalogo = $catalogo['parcial']
            ? "   - Arriba ves el resumen de categorías y los artículos más relevantes. Si el cliente pide un modelo que no aparece, NO lo inventes: dile que lo confirmas por WhatsApp {$this->telefono}."
            : "   - Conoces todos los productos agrupados arriba.";

        $bloqueCanal = '';
        if ($this->esCanalSinCarrito($canal)) {
            $cierre = $this->storeUrl !== ''
                ? "entrando a {$this->storeUrl} o escribiendo al WhatsApp {$this->telefono}"
                : "escribiendo al WhatsApp {$this->telefono} para que el equipo confirme su pedido";
            $bloqueCanal = "\nCANAL ACTUAL: {$canal} (mensajería). En este canal NO existe carrito de compras ni tarjetas con fotos: "
                . "nunca digas que algo \"ya está en tu carrito\". Si el cliente decide comprar, confirma producto y precio y explícale cómo cerrar el pedido: {$cierre}.\n";
        }

        return <<<PROMPT
{$reglasPersona}

INFORMACIÓN DE LA TIENDA:
- Tienda: {$this->nombreTienda} | Dueño: {$this->dueno} | WhatsApp: {$this->telefono} | Email: {$this->email}
- Moneda oficial de venta: {$this->simboloMoneda} (Bolivianos).
{$bloqueCanal}{$ragContexto}

CATÁLOGO DE LA TIENDA (Precios 100% oficiales en {$this->simboloMoneda}):
{$catalogoTexto}

CATEGORÍAS DE LA TIENDA (con sus IDs para filtrar):
{$categoriasJSON}

Último producto conversado: "{$ultimoProducto}"

REGLAS CRÍTICAS DE PRECIOS Y MONEDA:
1. Todos los precios de venta son los indicados en el catálogo en {$this->simboloMoneda}.
   Cita siempre la cifra exacta en {$this->simboloMoneda} (ej. "Bs 4200.00"). JAMÁS inventes precios ni digas montos sin convertir.
2. CONOCIMIENTO INTEGRAL DEL CATÁLOGO (CERO AMNESIA):
{$reglaCatalogo}
   - Si el cliente pregunta qué tienes en una categoría, menciónale las opciones reales.
   - Si el cliente pregunta "¿solo tienes eso?" o "¿tienes más?", NUNCA digas que solo hay uno si la categoría tiene varios.

GESTIÓN DE ACCIONES Y TARJETAS EN EL CHAT ("acciones"):
1. Filtrar catálogo ("filtrar_catalogo"): {"tipo": "filtrar_catalogo", "categoria_id": 4, "mensaje_filtro": "Mostrando Zapatillas"}
2. Mostrar tarjeta de producto ("mostrar_producto"): {"tipo": "mostrar_producto", "nombre": "Nike Air Zoom Pegasus 40", "precio": 1560.00}
3. Agregar al carrito ("agregar_carrito"): {"tipo": "agregar_carrito", "nombre": "Nombre exacto", "precio": 660.00}

FORMATO DE SALIDA (OBLIGATORIO JSON ESTRICTO):
Responde ÚNICAMENTE con un objeto JSON válido, sin texto antes ni después:
{
  "respuesta": "Tu mensaje natural como asesor aquí.",
  "acciones": []
}
PROMPT;
    }

    // =====================================================================================
    // RESPUESTA DEL LLM
    // =====================================================================================

    /**
     * Extrae {respuesta, acciones} de la salida del modelo, tolerando fences markdown y texto extra.
     *
     * @return array{respuesta: string, acciones: array}
     */
    private function parsearRespuestaLLM(array $aiResult): array
    {
        $jsonRespuesta = $aiResult['json'] ?? null;
        $rawRespuesta  = $aiResult['content'] ?? null;

        if (!$jsonRespuesta && $rawRespuesta !== null && trim($rawRespuesta) !== '') {
            $cleaned = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($rawRespuesta));
            $jsonRespuesta = json_decode((string)$cleaned, true);
            if (!$jsonRespuesta && preg_match('/\{[\s\S]*"respuesta"[\s\S]*\}/', $rawRespuesta, $matches)) {
                $jsonRespuesta = json_decode($matches[0], true);
            }
        }

        if (!is_array($jsonRespuesta) || !isset($jsonRespuesta['respuesta'])) {
            $textoRespaldo = ($rawRespuesta !== null && trim($rawRespuesta) !== '')
                ? trim($rawRespuesta)
                : HumanPersona::mensajeFallo($this->telefono);
            return ['respuesta' => $textoRespaldo, 'acciones' => []];
        }
        return $jsonRespuesta;
    }

    // =====================================================================================
    // ACCIONES: VALIDACIÓN DETERMINISTA
    // =====================================================================================

    /**
     * Convierte las acciones propuestas por el LLM (más las inferidas por reglas deterministas)
     * en acciones validadas. Invariante: id, nombre, precio, stock e imagen provienen del catálogo.
     */
    private function resolverAcciones(array $acciones, string $preguntaTratada, string $respuesta, array $historial, string $ultimoProducto): array
    {
        $preguntaActual = mb_strtolower($preguntaTratada);
        $esReclamo = (bool)preg_match(self::REGEX_RECLAMO, $preguntaActual);

        // A. Peticiones visuales ("muéstrame", "no los veo") -> tarjetas de la categoría
        $esPeticionVisual = (bool)preg_match(self::REGEX_PETICION_VISUAL, $preguntaActual);
        $categoriaId = $this->detectarCategoria($preguntaActual);

        if ($esPeticionVisual && !$categoriaId) {
            foreach (array_reverse($historial) as $h) {
                $categoriaId = $this->detectarCategoria(mb_strtolower((string)($h['content'] ?? '')));
                if ($categoriaId) {
                    break;
                }
            }
            if (!$categoriaId && $ultimoProducto !== '' && isset($this->mapaProductosPorNombre[mb_strtolower($ultimoProducto)])) {
                $categoriaId = (int)$this->mapaProductosPorNombre[mb_strtolower($ultimoProducto)]['categoria_id'];
            }
        }

        $tieneMostrar = false;
        foreach ($acciones as $a) {
            if (is_array($a) && ($a['tipo'] ?? '') === 'mostrar_producto') {
                $tieneMostrar = true;
                break;
            }
        }
        if ($esPeticionVisual && !$tieneMostrar && $categoriaId) {
            $deCategoria = array_values(array_filter($this->productos, fn($p) => (int)$p['categoria_id'] === (int)$categoriaId));
            foreach (array_slice($deCategoria, 0, 3) as $pm) {
                $acciones[] = ['tipo' => 'mostrar_producto', 'nombre' => $pm['nombre']];
            }
        }

        // Fallback determinista si no hay ninguna acción
        if (empty($acciones)) {
            if ($categoriaId) {
                $nomCat = '';
                foreach ($this->categoriasLista as $cl) {
                    if ($cl['id'] === $categoriaId) {
                        $nomCat = $cl['nombre'];
                        break;
                    }
                }
                $acciones[] = ['tipo' => 'filtrar_catalogo', 'categoria_id' => $categoriaId, 'mensaje_filtro' => "Mostrando {$nomCat} en el catálogo"];
            } else {
                foreach ($this->productos as $prod) {
                    if (mb_stripos($preguntaActual, mb_strtolower($prod['nombre'])) !== false) {
                        $acciones[] = ['tipo' => 'mostrar_producto', 'nombre' => $prod['nombre']];
                        break;
                    }
                }
            }
        }

        // B. Modo soporte ante reclamos: se desactivan las ventas
        if ($esReclamo) {
            $acciones = [['tipo' => 'contacto_soporte']];
        }

        $ordinal    = $this->resolverOrdinal($preguntaActual, $historial);
        $mencionado = $this->productoMencionadoEn($respuesta);
        $variante   = $this->detectarVariante($preguntaTratada);

        // C. Normalización: precios y datos SIEMPRE desde el catálogo
        $normalizadas = [];
        foreach ($acciones as $acc) {
            if (!is_array($acc) || !isset($acc['tipo'])) {
                continue;
            }
            switch ($acc['tipo']) {
                case 'contacto_soporte':
                    $normalizadas[] = [
                        'tipo'     => 'contacto_soporte',
                        'telefono' => $this->telefono,
                        'mensaje'  => "Atención prioritaria por WhatsApp: {$this->telefono}",
                    ];
                    break;

                case 'filtrar_catalogo':
                    $catId = isset($acc['categoria_id']) ? (int)$acc['categoria_id'] : null;
                    if ($catId !== null && !in_array($catId, array_column($this->categoriasLista, 'id'), true)) {
                        $catId = null; // categoría inventada por el LLM
                    }
                    $normalizadas[] = [
                        'tipo'           => 'filtrar_catalogo',
                        'categoria_id'   => $catId,
                        'texto'          => isset($acc['texto']) ? InputSanitizer::cleanText((string)$acc['texto'], 120) : null,
                        'mensaje_filtro' => isset($acc['mensaje_filtro']) ? InputSanitizer::cleanText((string)$acc['mensaje_filtro'], 160) : '✨ Catálogo actualizado',
                    ];
                    break;

                case 'mostrar_producto':
                    $matched = $this->buscarProducto((string)($acc['nombre'] ?? ''));
                    if ($matched) {
                        $normalizadas[] = [
                            'tipo'        => 'mostrar_producto',
                            'id'          => $matched['id'],
                            'nombre'      => $matched['nombre'],
                            'precio'      => $matched['precio'],
                            'moneda'      => $this->simboloMoneda,
                            'descripcion' => $matched['descripcion'],
                            'imagen'      => $matched['imagen'],
                        ];
                    }
                    break;

                case 'agregar_carrito':
                    $nombre  = trim((string)($acc['nombre'] ?? ''));
                    $matched = $ordinal
                        ?? $this->buscarProducto($nombre)
                        ?? ($ultimoProducto !== '' ? $this->buscarProducto($ultimoProducto) : null)
                        ?? $mencionado;
                    if ($matched === null) {
                        // Sin producto real no hay acción: JAMÁS se usa id/precio propuestos por el LLM
                        error_log('[ChatEngine] agregar_carrito descartado: producto no identificado ("' . $nombre . '")');
                        break;
                    }
                    $normalizadas[] = $this->accionCarrito($matched, $variante ?: $this->varianteSegura($acc['variante'] ?? null));
                    break;
            }
        }

        // D. Detección determinista de intención de compra (con protección de falsos positivos)
        $tieneCarrito = (bool)array_filter($normalizadas, fn($a) => $a['tipo'] === 'agregar_carrito');
        if (!$tieneCarrito && !$esReclamo) {
            $detectado = $this->detectarIntencionAgregar($preguntaActual, $preguntaTratada, $respuesta, $historial, $ultimoProducto, $ordinal, $mencionado);
            if ($detectado !== null) {
                $normalizadas[] = $this->accionCarrito($detectado, $variante);
            }
        }

        return $this->deduplicarAcciones($normalizadas);
    }

    private function accionCarrito(array $p, ?string $variante): array
    {
        return [
            'tipo'     => 'agregar_carrito',
            'id'       => $p['id'],
            'nombre'   => $p['nombre'],
            'precio'   => $p['precio'],
            'stock'    => $p['stock'],
            'imagen'   => $p['imagen'],
            'moneda'   => $this->simboloMoneda,
            'variante' => $variante,
        ];
    }

    /** La variante propuesta por el LLM solo se acepta si es texto corto y sin caracteres raros. */
    private function varianteSegura(mixed $v): ?string
    {
        if (!is_string($v)) {
            return null;
        }
        $v = InputSanitizer::cleanText($v, 40);
        return $v !== '' && preg_match('/^[\p{L}\p{N} .\-\/]+$/u', $v) === 1 ? $v : null;
    }

    private function deduplicarAcciones(array $acciones): array
    {
        $vistas = [];
        $out = [];
        foreach ($acciones as $a) {
            $clave = ($a['tipo'] ?? '') . '|' . ($a['id'] ?? '') . '|' . ($a['variante'] ?? '') . '|' . ($a['categoria_id'] ?? '');
            if (in_array($a['tipo'], ['agregar_carrito', 'mostrar_producto'], true) && isset($vistas[$clave])) {
                continue;
            }
            $vistas[$clave] = true;
            $out[] = $a;
        }
        return $out;
    }

    /**
     * Categoría cuyo término aparece primero en el texto (palabra completa).
     */
    private function detectarCategoria(string $textoLower): ?int
    {
        if ($textoLower === '') {
            return null;
        }
        $mejorPos = null;
        $mejorId  = null;
        foreach ($this->terminosCategoria as $term => $catId) {
            if (preg_match('/(?<![\p{L}\p{N}])' . preg_quote((string)$term, '/') . '(?![\p{L}\p{N}])/u', $textoLower, $m, PREG_OFFSET_CAPTURE) === 1) {
                if ($mejorPos === null || $m[0][1] < $mejorPos) {
                    $mejorPos = $m[0][1];
                    $mejorId  = $catId;
                }
            }
        }
        return $mejorId;
    }

    /**
     * "el segundo", "el último", "el más barato"... resueltos contra los productos que el
     * asistente listó en su último mensaje.
     */
    private function resolverOrdinal(string $preguntaActual, array $historial): ?array
    {
        $mencionados = [];
        foreach (array_reverse($historial) as $h) {
            if (($h['role'] ?? '') !== 'assistant') {
                continue;
            }
            $contenido = (string)($h['content'] ?? '');
            foreach ($this->productos as $p) {
                $pos = mb_stripos($contenido, $p['nombre']);
                if ($pos === false) {
                    $simple = $this->nombreSimplificado($p['nombre']);
                    $pos = $simple !== '' ? mb_stripos($contenido, $simple) : false;
                }
                if ($pos !== false) {
                    $mencionados[$pos] = $p;
                }
            }
            if (!empty($mencionados)) {
                ksort($mencionados);
                $mencionados = array_values($mencionados);
                break;
            }
        }
        if (empty($mencionados)) {
            return null;
        }

        if (preg_match('/(el|la)\s+(primer[oa]?|1er?o?)\b/ui', $preguntaActual)) {
            return $mencionados[0] ?? null;
        }
        if (preg_match('/(el|la)\s+(segund[oa]|2d[oa])\b/ui', $preguntaActual)) {
            return $mencionados[1] ?? null;
        }
        if (preg_match('/(el|la)\s+(tercer[oa]?|3er?o?)\b/ui', $preguntaActual)) {
            return $mencionados[2] ?? null;
        }
        if (preg_match('/(el|la)\s+([uú]ltim[oa])\b/ui', $preguntaActual)) {
            return end($mencionados) ?: null;
        }
        if (preg_match('/(el|la)\s+m[aá]s\s+(barat[oa]|econ[oó]mic[oa])\b/ui', $preguntaActual)) {
            usort($mencionados, fn($a, $b) => $a['precio'] <=> $b['precio']);
            return $mencionados[0] ?? null;
        }
        if (preg_match('/(el|la)\s+m[aá]s\s+(car[oa]|costos[oa]|tope de gama)\b/ui', $preguntaActual)) {
            usort($mencionados, fn($a, $b) => $b['precio'] <=> $a['precio']);
            return $mencionados[0] ?? null;
        }
        return null;
    }

    private function productoMencionadoEn(string $texto): ?array
    {
        foreach ($this->productos as $p) {
            if (mb_stripos($texto, $p['nombre']) !== false) {
                return $p;
            }
            $simple = $this->nombreSimplificado($p['nombre']);
            if ($simple !== '' && mb_stripos($texto, $simple) !== false) {
                return $p;
            }
        }
        return null;
    }

    /**
     * Variante (talla, medida...) según las reglas del perfil de dominio.
     */
    private function detectarVariante(string $texto): ?string
    {
        foreach ($this->profile->variantes() as $regla) {
            if (@preg_match('/' . $regla['regex'] . '/ui', $texto, $m) === 1 && isset($m[1])) {
                $valor = trim($m[1]);
                return $regla['prefijo'] . ($regla['mayusculas'] ? mb_strtoupper($valor) : $valor);
            }
        }
        return null;
    }

    /**
     * ¿El cliente quiere comprar? Devuelve el producto a agregar o null.
     * Si hay varias coincidencias ambiguas por palabra clave, no adivina: prefiere el último
     * producto conversado y, si no aplica, no agrega nada (mejor preguntar que vender lo equivocado).
     */
    private function detectarIntencionAgregar(
        string $preguntaActual,
        string $preguntaTratada,
        string $respuesta,
        array $historial,
        string $ultimoProducto,
        ?array $ordinal,
        ?array $mencionado
    ): ?array {
        $esAfirmacion = (bool)preg_match('/^(s[ií]|dale|claro|bueno|ok|por\s*favor|porfa|por\s*fa|de\s*una|ya|obvio|perfecto|va|me\s*parece|anota|an[oó]talo|agr[ée]galo|agrega|comprar|lo\s*quiero|la\s*quiero|me\s*lo\s*llevo|me\s*la\s*llevo|adelante|an[oó]tame)\b/ui', trim($preguntaActual));

        $asistentePreguntoAgregar = false;
        foreach (array_reverse($historial) as $h) {
            if (($h['role'] ?? '') === 'assistant') {
                if (preg_match('/(agr[ée]go|a[ñn]ad|carrito|bolsa|lo llevas|te lo preparo|te lo anoto|lo cerramos|lo agregamos|la agregamos)/ui', (string)($h['content'] ?? ''))) {
                    $asistentePreguntoAgregar = true;
                }
                break;
            }
        }

        $asistenteAfirmaQueAgrego = (bool)preg_match('/(ya est[aá] en tu carrito|ya te (lo|la)?\s*(agregu[eé]|añad[ií])|agregad[oa] al carrito|añadid[oa] al carrito|te (lo|la)?\s*dejo en el carrito|lo tienes en tu carrito|la tienes en tu carrito)/ui', $respuesta);

        // "quiero que...", "quiero saber..." no es comprar
        $esConsultaNoCompra = (bool)preg_match('/^quiero\s+(?:que|saber|ver|consultar|preguntar|informaci[oó]n|averiguar|cotizar|mirar)\b/ui', trim($preguntaActual));

        $esIntencion = (
                preg_match('/(agr[ée]g|aña[de]|pon(me)?|m[ée]te(lo)?|cargar|comprar|quiero comprar|anota|an[oó]tame|llevar).*(carrito|bolsa|cesta)/ui', $preguntaTratada)
                || preg_match('/(al carrito|a mi carrito|a la bolsa)/ui', $preguntaTratada)
                || preg_match('/^(agrega|agr[ée]galo|a[ñn]ade|me llevo|lo quiero|la quiero|compro)\b/ui', $preguntaTratada)
                || (preg_match('/^quiero\s+(?:el|la|los|las|un[oa]?|est[ea])\s+/ui', $preguntaTratada) && !$esConsultaNoCompra)
                || ($esAfirmacion && $asistentePreguntoAgregar)
                || $asistenteAfirmaQueAgrego
            ) && !$esConsultaNoCompra;

        if (!$esIntencion) {
            return null;
        }

        // 1. Referencia ordinal explícita
        if ($ordinal) {
            return $ordinal;
        }
        // 2. Nombre oficial completo en el mensaje
        foreach ($this->productos as $p) {
            if (mb_stripos($preguntaTratada, $p['nombre']) !== false) {
                return $p;
            }
        }
        // 3. Alias del perfil de dominio
        $porAlias = $this->buscarPorAlias($preguntaActual);
        if ($porAlias !== null) {
            return $porAlias;
        }
        // 4. Palabras clave del nombre (solo si son inequívocas)
        $candidatos = [];
        foreach ($this->productos as $p) {
            foreach (explode(' ', mb_strtolower($p['nombre'])) as $pal) {
                if (mb_strlen($pal) >= 4 && mb_stripos($preguntaTratada, $pal) !== false) {
                    $candidatos[$p['id']] = $p;
                    break;
                }
            }
        }
        if (count($candidatos) === 1) {
            return reset($candidatos);
        }
        if (count($candidatos) > 1) {
            foreach ($candidatos as $c) {
                if ($ultimoProducto !== '' && mb_strtolower($c['nombre']) === mb_strtolower($ultimoProducto)) {
                    return $c;
                }
            }
            return null; // ambiguo: mejor no agregar nada
        }
        // 5. Último producto conversado
        if ($ultimoProducto !== '' && isset($this->mapaProductosPorNombre[mb_strtolower($ultimoProducto)])) {
            return $this->mapaProductosPorNombre[mb_strtolower($ultimoProducto)];
        }
        // 6. Producto mencionado en la respuesta actual
        return $mencionado;
    }

    /**
     * Busca un producto por nombre exacto o parcial en el catálogo en memoria.
     */
    private function buscarProducto(string $nombre): ?array
    {
        $nomLower = mb_strtolower(trim($nombre));
        if ($nomLower === '') {
            return null;
        }
        if (isset($this->mapaProductosPorNombre[$nomLower])) {
            return $this->mapaProductosPorNombre[$nomLower];
        }
        if (mb_strlen($nomLower) < 3) {
            return null; // demasiado corto para una coincidencia parcial confiable
        }
        foreach ($this->productos as $p) {
            $pNomLower = mb_strtolower($p['nombre']);
            if (mb_stripos($pNomLower, $nomLower) !== false || mb_stripos($nomLower, $pNomLower) !== false) {
                return $p;
            }
        }
        return null;
    }

    // =====================================================================================
    // FORMATO Y TELEMETRÍA
    // =====================================================================================

    /**
     * Da formato a la respuesta para canales de mensajería (negritas con *).
     * En canales sin carrito NO se afirma que algo "quedó añadido": se indica cómo cerrar el pedido.
     */
    private function formatearParaCanal(string $texto, array $acciones, string $canal): string
    {
        $salida = trim($texto);
        $sinCarrito = $this->esCanalSinCarrito($canal);
        $hayCompra = false;
        $detallesExtra = [];

        foreach ($acciones as $acc) {
            if ($acc['tipo'] === 'mostrar_producto' && !empty($acc['nombre'])) {
                $detallesExtra[] = "📌 *{$acc['nombre']}* — {$acc['moneda']} " . number_format((float)$acc['precio'], 2);
            } elseif ($acc['tipo'] === 'agregar_carrito' && !empty($acc['nombre'])) {
                $hayCompra = true;
                $varTexto = !empty($acc['variante']) ? " ({$acc['variante']})" : '';
                $etiqueta = $sinCarrito ? 'Producto elegido' : 'Añadido al pedido';
                $detallesExtra[] = "🛒 *{$etiqueta}:* {$acc['nombre']}{$varTexto} — {$acc['moneda']} " . number_format((float)$acc['precio'], 2);
            } elseif ($acc['tipo'] === 'contacto_soporte' && !empty($acc['telefono'])) {
                $detallesExtra[] = "📞 *Soporte Directo WhatsApp:* {$acc['telefono']}";
            }
        }

        if ($sinCarrito && $hayCompra) {
            $detallesExtra[] = $this->storeUrl !== ''
                ? "👉 Finaliza tu compra aquí: {$this->storeUrl}"
                : "📞 Para confirmar tu pedido escríbenos al WhatsApp {$this->telefono}";
        }

        if (!empty($detallesExtra)) {
            $salida .= "\n\n" . implode("\n", array_unique($detallesExtra));
        }

        // Límite de WhatsApp Cloud API: 4096 caracteres por mensaje de texto
        return mb_strlen($salida) > 4000 ? mb_substr($salida, 0, 3990) . '…' : $salida;
    }

    private function registrarTelemetria(string $sessionLogId, string $pregunta, string $respuesta, array $aiResult, array $rag, array $acciones): void
    {
        $tokensUsage = $aiResult['usage'] ?? [];
        try {
            $stmtLog = $this->pdo->prepare("
                INSERT INTO chat_logs
                (session_id, pregunta, respuesta, tokens_usados, latencia_ms, tokens_prompt, tokens_completado, tokens_cache_hit, rag_score_max, rag_fuentes, acciones_json)
                VALUES
                (:sid, :preg, :resp, :tok_tot, :lat, :tok_p, :tok_c, :tok_ch, :r_score, :r_fuentes, :acc)
            ");
            $stmtLog->execute([
                ':sid'        => mb_substr($sessionLogId, 0, 190),
                ':preg'       => $pregunta,
                ':resp'       => $respuesta,
                ':tok_tot'    => (int)($tokensUsage['total_tokens'] ?? 0),
                ':lat'        => (int)($aiResult['latencia_ms'] ?? 0),
                ':tok_p'      => (int)($tokensUsage['prompt_tokens'] ?? 0),
                ':tok_c'      => (int)($tokensUsage['completion_tokens'] ?? 0),
                ':tok_ch'     => (int)($tokensUsage['cache_hit_tokens'] ?? 0),
                ':r_score'    => $rag['score_max'],
                ':r_fuentes'  => !empty($rag['fuentes']) ? implode(', ', $rag['fuentes']) : null,
                ':acc'        => json_encode($acciones, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (Throwable $e) {
            error_log('[ChatEngine Telemetry Error] ' . $e->getMessage());
        }
    }
}
