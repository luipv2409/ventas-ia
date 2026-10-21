<?php
declare(strict_types=1);

namespace Services\Rag;

use PDO;
use Throwable;

require_once __DIR__ . '/EmbeddingService.php';
require_once __DIR__ . '/VectorStore.php';
require_once __DIR__ . '/QueryExpander.php';

class RagService
{
    /** Umbral mínimo de relevancia (calibrado con tests/test_rag_offline.php) */
    public const DEFAULT_MIN_SCORE = 0.22;
    /** Por encima de este score la búsqueda se considera clara y no se expande la consulta */
    public const STRONG_SCORE = 0.40;
    /** Bonus máximo que aporta la coincidencia léxica FULLTEXT de MariaDB */
    public const LEXICAL_BOOST = 0.08;
    /** Se descartan fragmentos con menos de este % del mejor score (evita "relleno" débil) */
    public const RELATIVE_CUTOFF = 0.45;

    private EmbeddingService $embeddingService;
    private VectorStore $vectorStore;
    private ?QueryExpander $queryExpander;

    public function __construct(PDO $pdo, ?EmbeddingService $embeddingService = null, ?QueryExpander $queryExpander = null)
    {
        $this->embeddingService = $embeddingService ?? new EmbeddingService();
        $this->vectorStore = new VectorStore($pdo);

        // Cargar la tabla IDF generada por scripts/index_rag.php (si existe)
        if ($this->embeddingService->isLocalMode() && !$this->embeddingService->hasIdf()) {
            $meta = $this->vectorStore->loadMeta('idf');
            if (!empty($meta['idf']) && is_array($meta['idf'])) {
                $this->embeddingService->setIdf($meta['idf']);
            }
        }

        if ($queryExpander !== null) {
            $this->queryExpander = $queryExpander;
        } else {
            $enabled = defined('RAG_QUERY_EXPANSION') ? (bool)RAG_QUERY_EXPANSION : true;
            $this->queryExpander = $enabled ? new QueryExpander() : null;
        }
    }

    /**
     * Recupera fragmentos de conocimiento relevantes con fallback seguro.
     *
     * @param string $query Pregunta o mensaje del usuario
     * @param int $topK Número máximo de fragmentos relevantes a retornar
     * @param float $minScore Umbral mínimo de similitud para considerar relevante
     * @param array $history Historial reciente [{role, content}] para entender preguntas de seguimiento
     * @return array{found: bool, context: string, chunks: array, expanded_query: ?string}
     */
    public function retrieveContext(string $query, int $topK = 3, float $minScore = self::DEFAULT_MIN_SCORE, array $history = []): array
    {
        $empty = ['found' => false, 'context' => '', 'chunks' => [], 'expanded_query' => null];

        try {
            $matches = $this->search($query, $topK, $minScore);
            $expandedWith = null;

            // Expansión con DeepSeek solo si la búsqueda local fue débil
            $topScore = $matches[0]['score'] ?? 0.0;
            if ($this->queryExpander !== null && $topScore < self::STRONG_SCORE && $this->worthExpanding($query, $history)) {
                $extra = $this->queryExpander->expand($query, $history);
                if ($extra !== null) {
                    $expandedWith = $extra;
                    $more = $this->search($query . ' ' . $extra, $topK, $minScore);
                    $matches = $this->merge($matches, $more, $topK);
                }
            }

            if (empty($matches)) {
                return array_merge($empty, ['expanded_query' => $expandedWith]);
            }

            // Bloque de contexto para el prompt. Sin porcentajes ni jerga técnica, para que el
            // modelo lo use como conocimiento propio y no lo "cite" ante el cliente.
            $contextText = "### DATOS OFICIALES DE LA TIENDA PARA ESTA CONSULTA:\n";
            $contextText .= "Úsalos como conocimiento propio (no menciones que te los dieron):\n\n";

            foreach ($matches as $m) {
                $contextText .= sprintf("[%s]\n%s\n\n", $m['title'], trim($m['content']));
            }

            return [
                'found'          => true,
                'context'        => trim($contextText),
                'chunks'         => $matches,
                'expanded_query' => $expandedWith,
            ];
        } catch (Throwable $e) {
            // Fallback seguro: loguear y continuar sin romper la llamada a la IA
            error_log('[RAG SAFE FALLBACK] Excepción capturada en retrieveContext: ' . $e->getMessage());
            return $empty;
        }
    }

    /**
     * Búsqueda híbrida: vector local (conceptos + IDF) como base y FULLTEXT de MariaDB
     * como re-ranking léxico (premia coincidencias exactas de nombres, modelos y SKU).
     */
    private function search(string $query, int $topK, float $minScore): array
    {
        $q = $this->embeddingService->buildQuery($query);
        if (empty($q['vector'])) {
            return [];
        }

        // Pool amplio con umbral relajado, para que el bonus léxico pueda rescatar candidatos cercanos
        $candidates = $this->vectorStore->searchSimilar($q['vector'], max(12, $topK * 4), $minScore * 0.6, null, $q['units']);
        if (empty($candidates)) {
            return [];
        }

        $lexical = $this->vectorStore->fulltextScores($query, 20);
        if (!empty($lexical)) {
            $maxLex = max($lexical);
            foreach ($candidates as &$c) {
                if (isset($lexical[$c['id']])) {
                    $c['score'] = round(min(1.0, $c['score'] + self::LEXICAL_BOOST * ($lexical[$c['id']] / $maxLex)), 4);
                    $c['lexical_match'] = true;
                }
            }
            unset($c);
        }

        return self::selectTop($candidates, $topK, $minScore);
    }

    /**
     * Ordena, aplica umbral absoluto y relativo, y recorta a topK.
     */
    public static function selectTop(array $candidates, int $topK, float $minScore): array
    {
        usort($candidates, fn($a, $b) => $b['score'] <=> $a['score']);
        $best = $candidates[0]['score'] ?? 0.0;
        $floor = max($minScore, $best * self::RELATIVE_CUTOFF);

        $selected = array_filter($candidates, fn($c) => $c['score'] >= $floor);
        return array_slice(array_values($selected), 0, $topK);
    }

    private function merge(array $a, array $b, int $topK): array
    {
        $byId = [];
        foreach (array_merge($a, $b) as $m) {
            if (!isset($byId[$m['id']]) || $m['score'] > $byId[$m['id']]['score']) {
                $byId[$m['id']] = $m;
            }
        }
        return self::selectTop(array_values($byId), $topK, 0.0);
    }

    /**
     * Evita gastar una llamada a DeepSeek en saludos, agradecimientos o confirmaciones.
     */
    private function worthExpanding(string $query, array $history): bool
    {
        $q = trim(mb_strtolower($query, 'UTF-8'));
        if (mb_strlen($q) < 4) {
            return false;
        }
        if (preg_match('/^(hola|holi|gracias|muchas gracias|ok|okey|okay|vale|listo|dale|s[ií]|no|perfecto|genial|chau|chao|adi[oó]s|buen[oa]s?( d[ií]as| tardes| noches)?)[\s!.,?¡¿😊🙌👍]*$/u', $q)) {
            return false;
        }
        $units = $this->embeddingService->buildQuery($query)['units'];
        return !empty($units) || !empty($history);
    }
}
