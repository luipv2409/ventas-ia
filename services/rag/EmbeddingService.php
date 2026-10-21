<?php
declare(strict_types=1);

namespace Services\Rag;

/**
 * Servicio de Generación de Embeddings / Vectores Semánticos.
 * Soporta dos modos automáticos:
 * 1. Modo Local / Zero-Cost (por defecto): Genera vectores semánticos TF-IDF/n-gramas
 *    normalizados en L2 directamente en PHP. Cero costo, cero dependencias externas.
 * 2. Modo OpenAI (opcional): Si se define OPENAI_API_KEY en .env, usa text-embedding-3-small.
 *
 * Mejoras del modo local (v2):
 * - Diccionario de conceptos de e-commerce: "despacho", "flete", "encomienda" => #envio, etc.
 *   El concepto se AGREGA como término extra; la palabra original se conserva.
 * - Ponderación IDF calculada sobre todo el corpus al indexar (términos raros pesan más).
 * - TF sublineal (1 + ln tf) para que los textos largos no dominen por repetición.
 * - Stopwords de cortesía ("hola", "gracias", "quiero"...) para evitar falsos positivos.
 */
class EmbeddingService
{
    private ?string $openAiKey;
    private string $openAiModel;
    private array $stopWords;

    /** @var array<string, string[]> palabra normalizada (o su raíz) => lista de conceptos */
    private array $conceptMap;

    /** @var array<string, float>|null IDF por término; null = índice antiguo sin IDF */
    private ?array $idf = null;

    private const WEIGHT_UNIGRAM = 1.0;
    private const WEIGHT_CONCEPT = 0.8;
    private const WEIGHT_BIGRAM  = 1.5;

    public function __construct(?string $openAiKey = null, string $openAiModel = 'text-embedding-3-small')
    {
        $this->openAiKey = $openAiKey ?? (getenv('OPENAI_API_KEY') ?: null);
        $this->openAiModel = $openAiModel;

        // Stop words comunes en español para depurar términos no informativos
        $this->stopWords = array_flip([
            'de', 'la', 'que', 'el', 'en', 'y', 'a', 'los', 'del', 'se', 'las', 'por', 'un',
            'para', 'con', 'no', 'una', 'su', 'al', 'lo', 'como', 'mas', 'pero', 'sus', 'le',
            'ya', 'o', 'este', 'si', 'porque', 'esta', 'son', 'entre', 'esta', 'cuando', 'muy',
            'sin', 'sobre', 'ser', 'tiene', 'tambien', 'me', 'hasta', 'hay', 'donde', 'quien',
            'desde', 'todo', 'nos', 'durante', 'todos', 'uno', 'les', 'ni', 'contra', 'otros',
            'ese', 'eso', 'ante', 'ellos', 'e', 'esto', 'mi', 'antes', 'algunos', 'unos', 'yo',
            'otro', 'otras', 'otra', 'el', 'ella', 'cual', 'cuanto', 'cuantos', 'que', 'cual',
            // Cortesía, saludos y verbos de relleno (no aportan intención de búsqueda)
            'hola', 'holi', 'buenas', 'buenos', 'buen', 'bueno', 'dia', 'dias', 'tarde', 'tardes',
            'noche', 'noches', 'gracias', 'favor', 'porfa', 'porfavor', 'saludos', 'ok', 'okey',
            'quiero', 'quisiera', 'queria', 'necesito', 'saber', 'puedo', 'puede', 'pueden',
            'podria', 'podrian', 'tienen', 'tienes', 'tengo', 'hacen', 'hace', 'haces', 'ver',
            'dame', 'dime', 'decir', 'algun', 'alguna', 'alguno', 'usted', 'ustedes', 'tu', 'te',
            'es', 'estan', 'estoy', 'eres', 'soy', 'fue', 'era', 'sea', 'ah', 'eh', 'mmm', 'jaja',
            'cuantas', 'cuanta', 'como', 'cosa', 'cosas', 'info', 'informacion', 'consulta',
            'hago', 'hacer', 'hacemos', 'haria', 'cuentas', 'cuentame', 'cuenta',
        ]);

        $this->conceptMap = $this->buildConceptMap();
    }

    /**
     * true si se usa el vectorizador local (sin OpenAI).
     */
    public function isLocalMode(): bool
    {
        return empty($this->openAiKey);
    }

    /**
     * Carga la tabla IDF calculada en la indexación.
     * @param array<string, float> $idf
     */
    public function setIdf(array $idf): void
    {
        $this->idf = $idf;
    }

    public function hasIdf(): bool
    {
        return $this->idf !== null;
    }

    /**
     * Calcula IDF (variante BM25, siempre positiva) a partir de los términos de cada documento.
     * @param array<int, array<string, float>> $featureSets Un array de features por documento
     * @return array<string, float>
     */
    public static function computeIdf(array $featureSets): array
    {
        $n = count($featureSets);
        $df = [];
        foreach ($featureSets as $features) {
            foreach (array_keys($features) as $term) {
                $df[$term] = ($df[$term] ?? 0) + 1;
            }
        }

        $idf = [];
        foreach ($df as $term => $d) {
            $idf[$term] = round(log(1 + ($n - $d + 0.5) / ($d + 0.5)), 4);
        }
        return $idf;
    }

    /**
     * Genera el vector de representación semántica para el texto dado.
     * Retorna array asociativo [termino => peso] o array denso de floats, o null si falla.
     * @return array|null
     */
    public function getEmbedding(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        // Si hay API key de OpenAI configurada, usar text-embedding-3-small
        if (!empty($this->openAiKey)) {
            $apiEmbedding = $this->callOpenAiEmbedding($text);
            if ($apiEmbedding !== null) {
                return $apiEmbedding;
            }
        }

        // Modo autónomo local (Zero-cost, compatible con DeepSeek como LLM)
        return $this->generateLocalVector($text);
    }

    /**
     * Construye la consulta para búsqueda: vector + "unidades" (una por palabra, con sus conceptos).
     * Las unidades permiten medir qué parte de la pregunta quedó cubierta, aunque la
     * coincidencia haya sido por concepto (ej: "despacho" cubierto por #envio).
     *
     * @return array{vector: array, units: array<int, string[]>}
     */
    public function buildQuery(string $text): array
    {
        if (!$this->isLocalMode()) {
            return ['vector' => $this->getEmbedding($text) ?? [], 'units' => []];
        }

        $units = [];
        foreach ($this->tokenize($text) as $token) {
            $units[] = array_merge([$token], $this->conceptMap[$token] ?? []);
        }

        return ['vector' => $this->generateLocalVector($text), 'units' => $units];
    }

    /**
     * Extrae los términos ponderados por TF (sin IDF ni normalizar).
     * @return array<string, float>
     */
    public function extractFeatures(string $text): array
    {
        $tokens = $this->tokenize($text);
        if (empty($tokens)) {
            return [];
        }

        $counts = [];
        $add = static function (string $term, float $weight) use (&$counts): void {
            if (!isset($counts[$term])) {
                $counts[$term] = ['n' => 0, 'w' => $weight];
            }
            $counts[$term]['n']++;
        };

        $total = count($tokens);
        foreach ($tokens as $i => $token) {
            // 1. Unigramas con peso estándar
            $add($token, self::WEIGHT_UNIGRAM);
            // 2. Conceptos de dominio (sinónimos comerciales)
            foreach ($this->conceptMap[$token] ?? [] as $concept) {
                $add($concept, self::WEIGHT_CONCEPT);
            }
            // 3. Bigramas (pares de palabras consecutivas) para capturar semántica de frases
            if ($i < $total - 1) {
                $add($token . '_' . $tokens[$i + 1], self::WEIGHT_BIGRAM);
            }
        }

        // TF sublineal: 1 + ln(n)
        $features = [];
        foreach ($counts as $term => $c) {
            $features[$term] = (1.0 + log($c['n'])) * $c['w'];
        }
        return $features;
    }

    /**
     * Genera un vector normalizado en norma L2 basado en n-gramas, conceptos, TF e IDF.
     */
    public function generateLocalVector(string $text): array
    {
        $features = $this->extractFeatures($text);
        if (empty($features)) {
            return [];
        }

        // Aplicar IDF. Los términos que no existen en el corpus no pueden coincidir con
        // ningún documento, así que se descartan para no diluir el peso de los que sí.
        if ($this->idf !== null) {
            $weighted = [];
            foreach ($features as $term => $tf) {
                if (isset($this->idf[$term])) {
                    $weighted[$term] = $tf * $this->idf[$term];
                }
            }
            $features = $weighted;
        }

        // Normalización L2 del vector: v = v / sqrt(sum(v_i^2))
        $sumSquares = 0.0;
        foreach ($features as $weight) {
            $sumSquares += $weight * $weight;
        }

        $magnitude = sqrt($sumSquares);
        if ($magnitude <= 0.0) {
            return [];
        }

        $vector = [];
        foreach ($features as $term => $weight) {
            $vector[$term] = round($weight / $magnitude, 6);
        }

        return $vector;
    }

    /**
     * Normaliza y divide texto en tokens significativos sin acentos ni puntuación.
     * @return string[]
     */
    public function tokenize(string $text): array
    {
        $text = $this->normalizeText($text);

        $words = preg_split('/\s+/u', trim($text));
        $cleanTokens = [];

        foreach ($words as $w) {
            $w = trim($w);
            if (strlen($w) < 2) continue; // Descartar letras sueltas
            if (ctype_digit($w) && strlen($w) < 3) continue; // Números cortos sueltos ("24", "00")
            if (isset($this->stopWords[$w])) continue; // Descartar stopwords

            $cleanTokens[] = $this->stem($w);
        }

        return $cleanTokens;
    }

    private function normalizeText(string $text): string
    {
        // Minúsculas y reemplazo de acentos
        $text = mb_strtolower($text, 'UTF-8');
        $text = strtr($text, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n'
        ]);

        // Reemplazar signos y caracteres no alfanuméricos por espacios
        return (string)preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);
    }

    private function stem(string $w): string
    {
        // Stemming ligero de plurales en español (ej: envíos -> envio, ciudades -> ciudad)
        $stemmed = $w;
        if (mb_strlen($stemmed) > 4) {
            if (str_ends_with($stemmed, 'es')) {
                $stemmed = substr($stemmed, 0, -2);
            } elseif (str_ends_with($stemmed, 's')) {
                $stemmed = substr($stemmed, 0, -1);
            }
        }

        // Normalización de lemas comerciales comunes
        $lemmas = [
            'devolver' => 'devolucion',
            'devolucion' => 'devolucion',
            'garantizar' => 'garantia',
            'entregar' => 'entrega',
            'pagar' => 'pago',
            'comprar' => 'compra',
            'enviar' => 'envio',
            'cuesta' => 'precio',
            'costar' => 'precio',
            'costo' => 'precio',
            // Formas verbales => sustantivo canónico (v2)
            'despacho' => 'envio',
            'despachar' => 'envio',
            'despachado' => 'envio',
            'despachamo' => 'envio',
            'enviamo' => 'envio',
            'envian' => 'envio',
            'enviado' => 'envio',
            'entregamo' => 'entrega',
            'entregan' => 'entrega',
            'pedido' => 'compra',
            'pedir' => 'compra',
            'compro' => 'compra',
            'comprado' => 'compra',
            'pagado' => 'pago',
            'pague' => 'pago',
            'devuelvo' => 'devolucion',
            'devuelven' => 'devolucion',
            'facturar' => 'factura',
            'facturacion' => 'factura',
        ];

        return $lemmas[$stemmed] ?? $stemmed;
    }

    /**
     * Diccionario de conceptos de e-commerce (Bolivia). Cada palabra se indexa tanto en su
     * forma normalizada como en su raíz, para que coincida con la salida de tokenize().
     * @return array<string, string[]>
     */
    private function buildConceptMap(): array
    {
        $concepts = [
            '#envio' => [
                'envio', 'enviar', 'envian', 'envias', 'envie', 'despacho', 'despachar', 'despachan',
                'delivery', 'entrega', 'entregan', 'entregas', 'llega', 'llegar', 'llegan', 'llegue',
                'tarda', 'tardan', 'tardar', 'demora', 'demoran', 'demorar', 'encomienda', 'flete',
                'domicilio', 'seguimiento', 'mandar', 'mandan', 'paquete', 'transporte', 'courier',
                'ciudad', 'departamento', 'provincia', 'interior', 'nacional', 'local',
                'cochabamba', 'cruz', 'paz', 'sucre', 'oruro', 'potosi', 'tarija',
                'beni', 'pando', 'trinidad', 'cobija',
            ],
            '#pago' => [
                'pago', 'pagar', 'pague', 'pagan', 'qr', 'transferencia', 'transferir', 'deposito',
                'depositar', 'banco', 'bancaria', 'tarjeta', 'visa', 'mastercard', 'efectivo',
                'contraentrega', 'cobro', 'cobran', 'bcp', 'union', 'bnb', 'mercantil',
                'credito', 'debito', 'tigo', 'money', 'cuota', 'cuotas', 'pasarela',
            ],
            '#garantia' => [
                'garantia', 'defecto', 'defectuoso', 'defectuosa', 'falla', 'fallado', 'fallada',
                'falla', 'roto', 'rota', 'malogrado', 'danado', 'danada', 'descompuesto', 'reparar',
                'reparacion', 'cubre', 'mal', 'fabrica',
            ],
            '#devolucion' => [
                'devolucion', 'devuelvo', 'devuelven', 'reembolso', 'reembolsar', 'cambio', 'cambiar',
                'reclamo', 'reclamar', 'retorno', 'arrepenti', 'arrepiento', 'plazo',
            ],
            '#horario' => [
                'horario', 'hora', 'abren', 'abierto', 'cierran', 'cerrado', 'atienden', 'atencion',
                'domingo', 'feriado', 'sabado', 'lunes', 'viernes',
            ],
            '#tienda_fisica' => [
                'direccion', 'ubicacion', 'ubicados', 'sucursal', 'retiro', 'retirar', 'recoger',
                'fisica', 'presencial', 'mostrador',
            ],
            '#factura' => [
                'factura', 'facturar', 'facturacion', 'nit', 'recibo', 'fiscal', 'comprobante',
                'impuesto', 'empresa',
            ],
            '#compra' => [
                'compra', 'pedido', 'pedir', 'ordenar', 'orden', 'carrito', 'checkout', 'finalizar',
                'adquirir',
            ],
            '#precio' => [
                'precio', 'cuestan', 'valen', 'valor', 'barato', 'barata', 'caro', 'cara',
                'oferta', 'descuento', 'presupuesto', 'economico',
            ],
            '#stock' => [
                'stock', 'disponible', 'disponibilidad', 'existencia', 'quedan', 'agotado', 'unidades',
            ],
            '#smartphone' => [
                'celular', 'celu', 'celus', 'telefono', 'movil', 'smartphone', 'galaxy', 'iphone',
                'samsung', 'android', 'xiaomi', 'redmi',
            ],
            '#audio' => [
                'auricular', 'audifono', 'audifonos', 'parlante', 'headphone', 'headset', 'bluetooth',
                'sonido', 'musica',
            ],
            '#tablet' => ['tablet', 'tableta', 'ipad'],
            '#calzado' => ['zapatilla', 'zapato', 'tenis', 'calzado', 'runner', 'deportiva'],
            '#ropa' => ['camiseta', 'polo', 'polera', 'ropa', 'prenda', 'talla', 'algodon'],
            '#hogar' => ['licuadora', 'sabana', 'cocina', 'hogar', 'cama', 'electrodomestico'],
            '#cafe' => ['cafe', 'molido', 'arabica', 'bebida'],
        ];

        $map = [];
        foreach ($concepts as $concept => $words) {
            foreach ($words as $word) {
                $normalized = trim($this->normalizeText($word));
                foreach (array_unique([$normalized, $this->stem($normalized)]) as $key) {
                    if (!in_array($concept, $map[$key] ?? [], true)) {
                        $map[$key][] = $concept;
                    }
                }
            }
        }
        return $map;
    }

    /**
     * Llamada segura a API OpenAI si está configurada (con timeout de 4s).
     */
    private function callOpenAiEmbedding(string $text): ?array
    {
        try {
            $payload = json_encode([
                'model' => $this->openAiModel,
                'input' => mb_substr($text, 0, 4000)
            ]);

            $ch = curl_init('https://api.openai.com/v1/embeddings');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->openAiKey
                ],
                CURLOPT_TIMEOUT        => 4,
                CURLOPT_CONNECTTIMEOUT => 2
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (isset($data['data'][0]['embedding'])) {
                    return $data['data'][0]['embedding'];
                }
            }
        } catch (\Throwable $e) {
            error_log('[EMBEDDING WARN] Fallback a vectorizador local: ' . $e->getMessage());
        }

        return null;
    }
}
