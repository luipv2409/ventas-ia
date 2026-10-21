<?php
declare(strict_types=1);

namespace Services\Rag;

use PDO;
use Throwable;

require_once __DIR__ . '/VectorStoreInterface.php';

class VectorStore implements VectorStoreInterface
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Crea/actualiza el esquema RAG de forma idempotente (seguro de ejecutar muchas veces):
     * - Tabla rag_documents (si no existe)
     * - Índice FULLTEXT sobre (title, content) para la búsqueda léxica híbrida
     * - Tabla rag_meta para guardar la tabla IDF del corpus
     */
    public function ensureSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `rag_documents` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `collection` VARCHAR(50) NOT NULL,
                `doc_id` VARCHAR(100) NOT NULL,
                `title` VARCHAR(255) NOT NULL,
                `content` TEXT NOT NULL,
                `metadata_json` JSON NULL,
                `embedding` JSON NOT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_collection_doc` (`collection`, `doc_id`),
                INDEX `idx_collection` (`collection`),
                FULLTEXT KEY `ft_title_content` (`title`, `content`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $hasFulltext = $this->pdo
            ->query("SHOW INDEX FROM `rag_documents` WHERE Key_name = 'ft_title_content'")
            ->fetch(PDO::FETCH_ASSOC);
        if (!$hasFulltext) {
            $this->pdo->exec("ALTER TABLE `rag_documents` ADD FULLTEXT KEY `ft_title_content` (`title`, `content`)");
        }

        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS `rag_meta` (
                `meta_key` VARCHAR(64) NOT NULL PRIMARY KEY,
                `meta_value` LONGTEXT NOT NULL,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Guarda o actualiza un documento en la tabla rag_documents.
     */
    public function saveDocument(
        string $collection,
        string $docId,
        string $title,
        string $content,
        array $metadata,
        array $embedding
    ): bool {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO rag_documents (collection, doc_id, title, content, metadata_json, embedding)
                VALUES (:col, :doc_id, :title, :content, :meta, :emb)
                ON DUPLICATE KEY UPDATE 
                    title = VALUES(title),
                    content = VALUES(content),
                    metadata_json = VALUES(metadata_json),
                    embedding = VALUES(embedding)
            ");

            return $stmt->execute([
                ':col'     => $collection,
                ':doc_id'  => $docId,
                ':title'   => $title,
                ':content' => $content,
                ':meta'    => json_encode($metadata, JSON_UNESCAPED_UNICODE),
                ':emb'     => json_encode($embedding, JSON_UNESCAPED_UNICODE)
            ]);
        } catch (Throwable $e) {
            error_log('[VECTOR STORE ERROR] Fallo al guardar documento: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Elimina documentos de una colección que ya no existen en el origen
     * (productos desactivados, secciones borradas de knowledge/, etc.).
     * @param string[] $keepDocIds
     * @return int Filas eliminadas
     */
    public function deleteStale(string $collection, array $keepDocIds): int
    {
        if (empty($keepDocIds)) {
            return 0; // Por seguridad: nunca vaciar una colección completa por un origen vacío
        }
        $placeholders = implode(',', array_fill(0, count($keepDocIds), '?'));
        $stmt = $this->pdo->prepare(
            "DELETE FROM rag_documents WHERE collection = ? AND doc_id NOT IN ($placeholders)"
        );
        $stmt->execute(array_merge([$collection], array_values($keepDocIds)));
        return $stmt->rowCount();
    }

    public function saveMeta(string $key, array $value): bool
    {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO rag_meta (meta_key, meta_value) VALUES (:k, :v)
                ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)
            ");
            return $stmt->execute([':k' => $key, ':v' => json_encode($value, JSON_UNESCAPED_UNICODE)]);
        } catch (Throwable $e) {
            error_log('[VECTOR STORE ERROR] Fallo al guardar meta: ' . $e->getMessage());
            return false;
        }
    }

    public function loadMeta(string $key): ?array
    {
        try {
            $stmt = $this->pdo->prepare("SELECT meta_value FROM rag_meta WHERE meta_key = :k");
            $stmt->execute([':k' => $key]);
            $raw = $stmt->fetchColumn();
            if ($raw === false) {
                return null;
            }
            $data = json_decode((string)$raw, true);
            return is_array($data) ? $data : null;
        } catch (Throwable $e) {
            // Tabla inexistente (índice antiguo): se trabaja sin IDF
            return null;
        }
    }

    /**
     * Búsqueda léxica con el índice FULLTEXT de MariaDB.
     * Retorna [id => relevancia]. Si el índice no existe, retorna [] sin romper nada.
     * @return array<int, float>
     */
    public function fulltextScores(string $query, int $limit = 20): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, MATCH(title, content) AGAINST(:q1 IN NATURAL LANGUAGE MODE) AS rel
                FROM rag_documents
                WHERE MATCH(title, content) AGAINST(:q2 IN NATURAL LANGUAGE MODE)
                ORDER BY rel DESC
                LIMIT " . max(1, $limit)
            );
            $stmt->execute([':q1' => $query, ':q2' => $query]);

            $scores = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                if ((float)$row['rel'] > 0) {
                    $scores[(int)$row['id']] = (float)$row['rel'];
                }
            }
            return $scores;
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Búsqueda por similitud de coseno con filtrado por score mínimo.
     * Soporta tanto vectores dispersos asociativos como vectores densos.
     *
     * @param array $queryEmbedding Vector de la consulta
     * @param int $topK Número máximo de resultados a recuperar
     * @param float $minScore Umbral mínimo de similitud (0.0 a 1.0)
     * @param string|null $collection Filtrar por colección ('catalogo', 'politicas', etc.)
     * @param array $queryUnits Unidades de la consulta (ver EmbeddingService::buildQuery) para cobertura
     * @return array
     */
    public function searchSimilar(
        array $queryEmbedding,
        int $topK = 4,
        float $minScore = 0.25,
        ?string $collection = null,
        array $queryUnits = []
    ): array {
        if (empty($queryEmbedding)) {
            return [];
        }

        try {
            // Verificar tamaño de la colección para poda de candidatos
            $countSql = "SELECT COUNT(*) FROM rag_documents" . ($collection !== null ? " WHERE collection = :c" : "");
            $cntStmt = $this->pdo->prepare($countSql);
            if ($collection !== null) {
                $cntStmt->execute([':c' => $collection]);
            } else {
                $cntStmt->execute();
            }
            $totalCount = (int)$cntStmt->fetchColumn();

            // Si hay más de 80 documentos, generar subconjunto de candidatos en SQL (Two-Stage Retrieval)
            if ($totalCount > 80) {
                $termKeys = array_keys(array_filter($queryEmbedding, fn($w) => is_numeric($w) && (float)$w > 0.05));
                $termsText = implode(' ', array_slice($termKeys, 0, 8));

                $sql = "
                    (SELECT id, collection, doc_id, title, content, metadata_json, embedding 
                     FROM rag_documents 
                     WHERE MATCH(title, content) AGAINST(:terms IN NATURAL LANGUAGE MODE) "
                     . ($collection !== null ? "AND collection = :col1 " : "") . "
                     LIMIT 50)
                    UNION
                    (SELECT id, collection, doc_id, title, content, metadata_json, embedding 
                     FROM rag_documents "
                     . ($collection !== null ? "WHERE collection = :col2 " : "") . "
                     ORDER BY updated_at DESC 
                     LIMIT 30)
                ";
                $stmt = $this->pdo->prepare($sql);
                $params = [':terms' => $termsText ?: 'tienda productos'];
                if ($collection !== null) {
                    $params[':col1'] = $collection;
                    $params[':col2'] = $collection;
                }
                $stmt->execute($params);
            } else {
                $sql = "SELECT id, collection, doc_id, title, content, metadata_json, embedding FROM rag_documents";
                if ($collection !== null) {
                    $sql .= " WHERE collection = :col";
                }
                $stmt = $this->pdo->prepare($sql);
                if ($collection !== null) {
                    $stmt->execute([':col' => $collection]);
                } else {
                    $stmt->execute();
                }
            }

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            return self::rankRows($rows, $queryEmbedding, $topK, $minScore, $queryUnits);
        } catch (Throwable $e) {
            error_log('[VECTOR STORE ERROR] Fallo al buscar similitud: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Calcula el score de cada fila y devuelve las mejores. Es una función pura (sin BD)
     * para poder probarla de forma aislada (ver tests/test_rag_offline.php).
     */
    public static function rankRows(
        array $rows,
        array $queryEmbedding,
        int $topK,
        float $minScore,
        array $queryUnits = []
    ): array {
        $isDense = array_is_list($queryEmbedding);
        $queryMag = $isDense ? self::magnitudeDense($queryEmbedding) : 1.0;

        $matches = [];
        foreach ($rows as $row) {
            $docVector = is_array($row['embedding']) ? $row['embedding'] : json_decode($row['embedding'], true);
            if (!is_array($docVector) || empty($docVector)) {
                continue;
            }

            if ($isDense && array_is_list($docVector)) {
                $score = self::cosineSimilarityDense($queryEmbedding, $docVector, $queryMag);
            } else {
                // Vector disperso (producto punto de términos compartidos)
                $score = self::scoreSparse($queryEmbedding, $docVector, $queryUnits);
            }

            if ($score >= $minScore) {
                $matches[] = [
                    'id'         => (int)$row['id'],
                    'collection' => $row['collection'],
                    'doc_id'     => $row['doc_id'],
                    'title'      => $row['title'],
                    'content'    => $row['content'],
                    'score'      => round($score, 4),
                    'metadata'   => is_array($row['metadata_json'] ?? null)
                        ? $row['metadata_json']
                        : json_decode($row['metadata_json'] ?? '{}', true)
                ];
            }
        }

        // Ordenar de mayor a menor similitud
        usort($matches, fn($a, $b) => $b['score'] <=> $a['score']);

        return array_slice($matches, 0, $topK);
    }

    /**
     * Similitud de coseno para vectores dispersos normalizados en L2.
     * Como ambos vectores ya están normalizados en norma L2, dot(A, B) = cos(theta).
     *
     * Score final = 0.7 * coseno + 0.3 * cobertura + bonus de frases (bigramas).
     * - Cobertura: fracción de palabras de la pregunta que coincidieron, ya sea de forma
     *   directa o por concepto (ej. "despacho" cubierta por #envio).
     * - Bonus: +0.05 por cada bigrama (frase de 2 palabras) coincidente, máximo +0.10.
     */
    public static function scoreSparse(array $vecA, array $vecB, array $queryUnits = []): float
    {
        $dot = 0.0;
        $matched = [];
        $matchedBigrams = 0;

        foreach ($vecA as $term => $weightA) {
            if (isset($vecB[$term])) {
                $dot += $weightA * (float)$vecB[$term];
                $matched[$term] = true;
                if (str_contains((string)$term, '_')) {
                    $matchedBigrams++;
                }
            }
        }

        if ($dot <= 0.0) {
            return 0.0;
        }

        if (!empty($queryUnits)) {
            $covered = 0;
            foreach ($queryUnits as $unitTerms) {
                foreach ($unitTerms as $t) {
                    if (isset($matched[$t])) {
                        $covered++;
                        break;
                    }
                }
            }
            $coverage = $covered / count($queryUnits);
        } else {
            // Compatibilidad: proporción de términos del vector que coincidieron
            $coverage = count($matched) / max(1, count($vecA));
        }

        $score = ($dot * 0.7) + ($coverage * 0.3) + min(0.10, $matchedBigrams * 0.05);

        return min(1.0, max(0.0, $score));
    }

    /**
     * Similitud de coseno para vectores densos (ej. OpenAI 1536 dimensiones).
     */
    private static function cosineSimilarityDense(array $vecA, array $vecB, float $magA): float
    {
        $dot = 0.0;
        $magB = 0.0;
        $len = count($vecA);

        for ($i = 0; $i < $len; $i++) {
            $valA = (float)$vecA[$i];
            $valB = (float)($vecB[$i] ?? 0.0);
            $dot += $valA * $valB;
            $magB += $valB * $valB;
        }

        $magB = sqrt($magB);
        if ($magA <= 0.0 || $magB <= 0.0) return 0.0;

        return min(1.0, max(0.0, $dot / ($magA * $magB)));
    }

    private static function magnitudeDense(array $vec): float
    {
        $sum = 0.0;
        foreach ($vec as $v) $sum += ((float)$v) * ((float)$v);
        return sqrt($sum);
    }
}
