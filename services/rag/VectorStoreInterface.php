<?php
declare(strict_types=1);

namespace Services\Rag;

/**
 * Interfaz unificada para motores de almacenamiento y búsqueda vectorial RAG.
 * Permite alternar limpiamente entre MySQL/MariaDB local y Pinecone sin alterar RagService.
 */
interface VectorStoreInterface
{
    /**
     * Asegura la existencia de tablas, índices o esquemas necesarios.
     */
    public function ensureSchema(): void;

    /**
     * Guarda o actualiza un documento con su vector en la base de datos.
     *
     * @param string $collection Colección lógica ('catalogo', 'politicas', 'faqs', 'documentos')
     * @param string $docId Identificador único del fragmento
     * @param string $title Título del documento
     * @param string $content Texto del contenido
     * @param array<string, mixed> $metadata Metadatos arbitrarios
     * @param array $embedding Vector de características (disperso asociativo o denso numérico)
     * @return bool
     */
    public function saveDocument(
        string $collection,
        string $docId,
        string $title,
        string $content,
        array $metadata,
        array $embedding
    ): bool;

    /**
     * Busca los documentos más similares a un vector de consulta.
     *
     * @param array $queryEmbedding Vector de la consulta
     * @param int $topK Número máximo de resultados
     * @param float $minScore Umbral mínimo de similitud
     * @param string|null $collection Colección por la que filtrar (opcional)
     * @param array $queryUnits Unidades de token para evaluar cobertura
     * @return array<int, array{id: int|string, collection: string, doc_id: string, title: string, content: string, score: float, metadata: array}>
     */
    public function searchSimilar(
        array $queryEmbedding,
        int $topK = 4,
        float $minScore = 0.25,
        ?string $collection = null,
        array $queryUnits = []
    ): array;

    /**
     * Elimina documentos obsoletos que ya no existen en el origen.
     *
     * @param string $collection
     * @param string[] $keepDocIds
     * @return int Cantidad de documentos eliminados
     */
    public function deleteStale(string $collection, array $keepDocIds): int;
}
