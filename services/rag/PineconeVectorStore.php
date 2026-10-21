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

    public function __construct(PDO $pdo, ?string $apiKey = null, ?string $host = null)
    {
        $this->apiKey = $apiKey ?? (defined('PINECONE_API_KEY') ? (string)PINECONE_API_KEY : (getenv('PINECONE_API_KEY') ?: ''));
        $this->host   = $host ?? (defined('PINECONE_INDEX_HOST') ? (string)PINECONE_INDEX_HOST : (getenv('PINECONE_INDEX_HOST') ?: 'ventas-ia-rag-9jc19q6.svc.aped-4627-b74a.pinecone.io'));
        $this->fallbackStore = new VectorStore($pdo);
        $this->isAvailable = (!empty($this->apiKey) && !empty($this->host));
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

            $url = 'https://' . rtrim($this->host, '/') . '/vectors/upsert';
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
        ?string $collection = null,
        array $queryUnits = []
    ): array {
        // Si Pinecone no está activo o la consulta es dispersa, usar el fallback optimizado de MySQL
        if (!$this->isAvailable || !array_is_list($queryEmbedding)) {
            return $this->fallbackStore->searchSimilar($queryEmbedding, $topK, $minScore, $collection, $queryUnits);
        }

        try {
            $payload = [
                'vector'          => array_values($queryEmbedding),
                'topK'            => $topK,
                'includeMetadata' => true,
            ];
            if ($collection !== null) {
                $payload['namespace'] = $collection;
            }

            $url = 'https://' . rtrim($this->host, '/') . '/query';
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
        return $this->fallbackStore->deleteStale($collection, $keepDocIds);
    }
}
