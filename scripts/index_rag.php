<?php
declare(strict_types=1);

// Permitir ejecución desde CLI o desde navegador web para administradores
$isCli = (php_sapi_name() === 'cli');
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/rag/EmbeddingService.php';
require_once __DIR__ . '/../services/rag/VectorStore.php';
require_once __DIR__ . '/../services/rag/DocumentChunker.php';
require_once __DIR__ . '/../services/rag/PdfExtractor.php';

use Services\Rag\DocumentChunker;
use Services\Rag\EmbeddingService;
use Services\Rag\PdfExtractor;
use Services\Rag\VectorStore;

echo "====================================================\n";
echo "🚀 INDEXADOR RAG v3 — Base de Conocimiento (MD + PDF) y Catálogo\n";
echo "====================================================\n\n";

try {
    $pdo = Database::getConnection();
    $embeddingService = new EmbeddingService();
    $vectorStore = new VectorStore($pdo);

    // ------------------------------------------------------------
    // 0. Esquema (idempotente): FULLTEXT + tabla rag_meta
    // ------------------------------------------------------------
    echo "🛠️  [0/4] Verificando esquema RAG (FULLTEXT, rag_meta)...\n";
    $vectorStore->ensureSchema();

    /** @var array<int, array{collection: string, doc_id: string, title: string, content: string, embed_text: string, metadata: array}> $docs */
    $docs = [];

    // ------------------------------------------------------------
    // 1. Recolectar Catálogo de Productos (con conversión de moneda oficial)
    // ------------------------------------------------------------
    $tasa = 1.0;
    $simbolo = 'Bs';
    try {
        $cfgStmt = $pdo->query("SELECT clave, valor FROM configuracion WHERE clave IN ('tasa_cambio_usd', 'moneda_simbolo')");
        $cfgs = $cfgStmt ? ($cfgStmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: []) : [];
        if (!empty($cfgs['tasa_cambio_usd'])) {
            $tasa = (float)$cfgs['tasa_cambio_usd'];
        }
        if (!empty($cfgs['moneda_simbolo'])) {
            $simbolo = trim((string)$cfgs['moneda_simbolo']);
        }
    } catch (Throwable $e) {
        // Mantener defaults si no existe la tabla
    }
    echo "📦 [1/4] Leyendo catálogo de productos (Tasa: {$tasa}, Moneda: {$simbolo})...\n";
    $stmt = $pdo->query("
        SELECT p.id, p.nombre, p.descripcion, p.especificaciones, p.precio, p.stock, p.sku, c.nombre AS categoria
        FROM productos p
        LEFT JOIN categorias c ON p.categoria_id = c.id
        WHERE p.activo = 1
    ");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $prod) {
        $chunk = DocumentChunker::productChunk($prod, $tasa, $simbolo);
        $docs[] = ['collection' => 'catalogo'] + $chunk;
    }

    // ------------------------------------------------------------
    // 2. Recolectar Documentos de Políticas, FAQs y PDFs (knowledge/)
    // ------------------------------------------------------------
    echo "📚 [2/4] Leyendo documentos de knowledge/ (Markdown y PDFs)...\n";
    $knowledgeDir = __DIR__ . '/../knowledge';
    if (is_dir($knowledgeDir)) {
        // A. Archivos Markdown (.md)
        foreach (glob($knowledgeDir . '/*.md') as $filePath) {
            $filename = basename($filePath);
            $rawContent = file_get_contents($filePath);
            if (!$rawContent) continue;

            foreach (DocumentChunker::chunkMarkdown($rawContent, $filename) as $chunk) {
                $docs[] = [
                    'collection' => 'politicas',
                    'doc_id'     => $chunk['doc_id'],
                    'title'      => $chunk['title'],
                    'content'    => $chunk['content'],
                    'embed_text' => $chunk['embed_text'],
                    'metadata'   => [
                        'tipo'    => 'politica_faq',
                        'archivo' => $filename,
                        'seccion' => $chunk['section'],
                    ],
                ];
            }
        }

        // B. Archivos PDF (.pdf) con extracción de texto plano
        foreach (glob($knowledgeDir . '/*.pdf') as $filePath) {
            $filename = basename($filePath);
            echo "   📄 Procesando PDF: {$filename}...\n";
            $extractedText = PdfExtractor::extractText($filePath);
            if (mb_strlen(trim($extractedText)) < 30) {
                echo "   ⚠ PDF vacío o sin texto legible: {$filename}\n";
                continue;
            }

            foreach (DocumentChunker::chunkPdf($extractedText, $filename) as $chunk) {
                $docs[] = [
                    'collection' => 'documentos',
                    'doc_id'     => $chunk['doc_id'],
                    'title'      => $chunk['title'],
                    'content'    => $chunk['content'],
                    'embed_text' => $chunk['embed_text'],
                    'metadata'   => $chunk['metadata'],
                ];
            }
        }
    }

    // ------------------------------------------------------------
    // 3. Calcular IDF sobre todo el corpus (solo modo local)
    // ------------------------------------------------------------
    if ($embeddingService->isLocalMode()) {
        echo "🧮 [3/4] Calculando pesos IDF de " . count($docs) . " fragmentos...\n";
        $featureSets = array_map(fn($d) => $embeddingService->extractFeatures($d['embed_text']), $docs);
        $idf = EmbeddingService::computeIdf($featureSets);
        $embeddingService->setIdf($idf);
        $vectorStore->saveMeta('idf', [
            'idf'        => $idf,
            'docs'       => count($docs),
            'generated'  => date('c'),
        ]);
        echo "   ✔ Vocabulario: " . count($idf) . " términos\n";
    } else {
        echo "🧮 [3/4] Modo OpenAI detectado: se omite IDF local.\n";
    }

    // ------------------------------------------------------------
    // 4. Vectorizar y guardar
    // ------------------------------------------------------------
    echo "\n💾 [4/4] Generando vectores y guardando...\n";
    $indexados = ['catalogo' => 0, 'politicas' => 0, 'documentos' => 0];
    $vigentes = ['catalogo' => [], 'politicas' => [], 'documentos' => []];

    foreach ($docs as $doc) {
        $vector = $embeddingService->getEmbedding($doc['embed_text']);
        if ($vector === null || empty($vector)) {
            echo "   ⚠ Sin vector: {$doc['title']}\n";
            continue;
        }

        $ok = $vectorStore->saveDocument(
            collection: $doc['collection'],
            docId: $doc['doc_id'],
            title: $doc['title'],
            content: $doc['content'],
            metadata: $doc['metadata'],
            embedding: $vector
        );

        if ($ok) {
            $indexados[$doc['collection']]++;
            $vigentes[$doc['collection']][] = $doc['doc_id'];
            echo "   ✔ [{$doc['collection']}] {$doc['title']}\n";
        }
    }

    // Limpiar fragmentos obsoletos (productos desactivados, secciones eliminadas o renombradas)
    $eliminados = 0;
    foreach ($vigentes as $collection => $ids) {
        $eliminados += $vectorStore->deleteStale($collection, $ids);
    }

    echo "\n====================================================\n";
    echo "🎉 Indexación RAG finalizada con éxito!\n";
    echo "   - Productos indexados: {$indexados['catalogo']}\n";
    echo "   - Fragmentos de políticas/FAQs indexados: {$indexados['politicas']}\n";
    echo "   - Fragmentos obsoletos eliminados: {$eliminados}\n";
    echo "====================================================\n";

} catch (Throwable $e) {
    echo "❌ Error durante la indexación: " . $e->getMessage() . "\n";
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo $e->getTraceAsString() . "\n";
    }
}
