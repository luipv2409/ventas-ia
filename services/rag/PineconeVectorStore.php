<?php
declare(strict_types=1);

namespace Services\Rag;

use PDO;
use Throwable;

require_once __DIR__ . '/VectorStoreInterface.php';
require_once __DIR__ . '/VectorStore.php';

/**
 * Driver para base de datos vectorial Pinecone (API REST v2025).
 * Implementa VectorStoreInterface y cuenta con fallback automático transparente a MySQL.
 */
class PineconeVectorStore implements VectorStoreInterface
{
    private string $apiKey;
    private string $host;
    private VectorStore $fallbackStore;
    private bool $isAvailable;

    private PDO $pdo;

    public function __construct(PDO $pdo, ?string $apiKey = null, ?string $host = null)
    {
        $this->pdo    = $pdo;
        $this->apiKey = trim((string)($apiKey ?? (defined('PINECONE_API_KEY') ? PINECONE_API_KEY : (getenv('PINECONE_API_KEY') ?: ''))));
        $rawHost      = trim((string)($host ?? (defined('PINECONE_INDEX_HOST') ? PINECONE_INDEX_HOST : (getenv('PINECONE_INDEX_HOST') ?: ''))));
        $rawHost      = (string)preg_replace('#^https?://#i', '', $rawHost);
        $this->host   = rtrim($rawHost, '/');
        $this->fallbackStore = new VectorStore($pdo);
        $this->isAvailable = ($this->apiKey !== '' && $this->host !== '');
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function getFallbackStore(): VectorStore
    {
        return $this->fallbackStore;
    }

    public function ensureSchema(): void
    {
        // Pinecone gestiona sus índices en la nube; aseguramos siempre la base MySQL local
        $this->fallbackStore->ensureSchema();
    }

    public function saveDocument(
        string $collection,
        string $docId,
        string $title,
        string $content,
        array $metadata,
        array $embedding
    ): bool {
        // Guardar siempre en MySQL como almacenamiento principal de respaldo
        $localOk = $this->fallbackStore->saveDocument($collection, $docId, $title, $content, $metadata, $embedding);

        if (!$this->isAvailable) {
            return $localOk;
        }

        // Si el vector es disperso (asociativo de PHP), no enviamos a Pinecone denso salvo que venga como lista
        if (!array_is_list($embedding)) {
            return $localOk;
        }

        try {
            $payload = [
                'vectors' => [
                    [
                        'id'       => "{$collection}_{$docId}",
                        'values'   => array_values($embedding),
                        'metadata' => array_merge($metadata, [
                            'collection' => $collection,
                            'doc_id'     => $docId,
                            'title'      => $title,
                            'content'    => mb_substr($content, 0, 1000),
                        ]),
                    ]
                ],
                'namespace' => $collection,
            ];

            $url = 'https://' . $this->host . '/vectors/upsert';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Api-Key: ' . $this->apiKey,
                ],
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $resp = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return ($httpCode === 200);
        } catch (Throwable $e) {
            error_log('[PINECONE DRIVER ERROR] Fallo en upsert: ' . $e->getMessage());
            return $localOk;
        }
    }

    public function searchSimilar(
        array $queryEmbedding,
        int $topK = 4,
        float $minScore = 0.25,
        string|array|null $collection = null,
        array $queryUnits = []
    ): array {
        // Si Pinecone no está activo, la consulta es dispersa o filtra múltiples namespaces, usar MySQL
        if (!$this->isAvailable || !array_is_list($queryEmbedding) || is_array($collection)) {
            return $this->fallbackStore->searchSimilar($queryEmbedding, $topK, $minScore, $collection, $queryUnits);
        }

        try {
            $payload = [
                'vector'          => array_values($queryEmbedding),
                'topK'            => $topK,
                'includeMetadata' => true,
            ];
            if (is_string($collection) && $collection !== '') {
                $payload['namespace'] = $collection;
            }

            $url = 'https://' . $this->host . '/query';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Api-Key: ' . $this->apiKey,
                ],
                CURLOPT_TIMEOUT        => 6,
                CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $resp = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && is_string($resp)) {
                $data = json_decode($resp, true);
                $matches = $data['matches'] ?? [];
                $results = [];

                foreach ($matches as $m) {
                    $score = (float)($m['score'] ?? 0);
                    if ($score >= $minScore) {
                        $meta = $m['metadata'] ?? [];
                        $results[] = [
                            'id'         => $m['id'],
                            'collection' => $meta['collection'] ?? ($collection ?? 'general'),
                            'doc_id'     => $meta['doc_id'] ?? $m['id'],
                            'title'      => $meta['title'] ?? '',
                            'content'    => $meta['content'] ?? '',
                            'score'      => round($score, 4),
                            'metadata'   => $meta,
                        ];
                    }
                }

                if (!empty($results)) {
                    return $results;
                }
            }

            // Fallback a MySQL si Pinecone no retornó resultados o dio error
            return $this->fallbackStore->searchSimilar($queryEmbedding, $topK, $minScore, $collection, $queryUnits);
        } catch (Throwable $e) {
            error_log('[PINECONE DRIVER ERROR] Fallo en query, aplicando fallback: ' . $e->getMessage());
            return $this->fallbackStore->searchSimilar($queryEmbedding, $topK, $minScore, $collection, $queryUnits);
        }
    }

    public function deleteStale(string $collection, array $keepDocIds): int
    {
        if (empty($keepDocIds)) {
            return 0;
        }

        if ($this->isAvailable) {
            try {
                $placeholders = implode(',', array_fill(0, count($keepDocIds), '?'));
                $stmt = $this->pdo->prepare(
                    "SELECT doc_id FROM rag_documents WHERE collection = ? AND doc_id NOT IN ({$placeholders})"
                );
                $stmt->execute(array_merge([$collection], array_values($keepDocIds)));
                $staleIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

                if (!empty($staleIds)) {
                    $pineconeIds = array_map(fn($id) => "{$collection}_{$id}", $staleIds);
                    $ch = curl_init('https://' . $this->host . '/vectors/delete');
                    curl_setopt_array($ch, [
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_POST           => true,
                        CURLOPT_POSTFIELDS     => json_encode([
                            'ids'       => array_values($pineconeIds),
                            'namespace' => $collection,
                        ], JSON_UNESCAPED_UNICODE),
                        CURLOPT_HTTPHEADER     => [
                            'Content-Type: application/json',
                            'Api-Key: ' . $this->apiKey,
                        ],
                        CURLOPT_TIMEOUT        => 6,
                        CURLOPT_CONNECTTIMEOUT => 3,
                        CURLOPT_SSL_VERIFYPEER => true,
                        CURLOPT_SSL_VERIFYHOST => 2,
                    ]);
                    curl_exec($ch);
                    curl_close($ch);
                }
            } catch (Throwable $e) {
                error_log('[PINECONE DRIVER WARN] Fallo al sincronizar deleteStale: ' . $e->getMessage());
            }
        }

        return $this->fallbackStore->deleteStale($collection, $keepDocIds);
    }
}
