<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}


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
echo "🏛️ INDEXADOR RAG ULAT — Universidad Latinoamericana\n";
echo "====================================================\n\n";

try {
    $pdo = Database::getConnection();
    $embeddingService = new EmbeddingService();
    $vectorStore = new VectorStore($pdo);

    echo "🛠️  [0/4] Verificando esquema RAG...\n";
    $vectorStore->ensureSchema();

    $docs = [];
    $ulatDocsDir = __DIR__ . '/../knowledge/ulat_docs';
    $catalogFile = __DIR__ . '/../data/ulat_catalog.json';

    // ------------------------------------------------------------
    // 1. Catálogo estructurado de carreras
    // ------------------------------------------------------------
    echo "🎓 [1/4] Indexando catálogo de Carreras y Facultades...\n";
    if (file_exists($catalogFile)) {
        $catalog = json_decode(file_get_contents($catalogFile), true) ?: [];
        foreach ($catalog as $slug => $c) {
            $materiasResumen = '';
            if (!empty($c['semestres']) && is_array($c['semestres'])) {
                foreach ($c['semestres'] as $semNom => $mats) {
                    $materiasResumen .= "{$semNom}: " . implode(', ', $mats) . ". ";
                }
            }

            $content = "CARRERA: {$c['nombre']}\n";
            $content .= "FACULTAD: {$c['facultad']}\n";
            $content .= "GRADO: {$c['grado']}\n";
            $content .= "DURACIÓN: {$c['duracion']}\n";
            $content .= "MODALIDAD: {$c['modalidad']}\n";
            $content .= "SEDE: " . ($c['sede'] ?? 'Campus Central') . "\n\n";
            $content .= "MALLA CURRICULAR: {$materiasResumen}\n\n";
            $content .= "DESCRIPCIÓN Y PERFIL:\n" . substr($c['descripcion'], 0, 1200) . "\n";

            $embedText = "{$c['nombre']} {$c['facultad']} {$c['grado']} {$c['duracion']} {$c['modalidad']} malla curricular materias semestres {$materiasResumen} " . substr($c['descripcion'], 0, 600);

            $docs[] = [
                'collection' => 'carreras',
                'doc_id'     => 'carrera_' . $slug,
                'title'      => $c['nombre'],
                'content'    => $content,
                'embed_text' => $embedText,
                'metadata'   => [
                    'tipo'     => 'carrera_universitaria',
                    'slug'     => $slug,
                    'facultad' => $c['facultad'],
                    'grado'    => $c['grado'],
                    'duracion' => $c['duracion'],
                ]
            ];
        }
    }

    // ------------------------------------------------------------
    // 2. Documentos Markdown institucionales y de carreras
    // ------------------------------------------------------------
    echo "📚 [2/4] Indexando documentos institucionales y mallas (.md)...\n";
    foreach (glob($ulatDocsDir . '/*.md') as $filePath) {
        $filename = basename($filePath);
        $raw = file_get_contents($filePath);
        if (!$raw) continue;

        $col = str_starts_with($filename, 'institucional_beca') || str_starts_with($filename, 'institucional_plan') 
             ? 'becas' 
             : (str_starts_with($filename, 'institucional_admi') ? 'admisiones' : 'institucional');

        foreach (DocumentChunker::chunkMarkdown($raw, $filename) as $chunk) {
            $docs[] = [
                'collection' => $col,
                'doc_id'     => $chunk['doc_id'],
                'title'      => $chunk['title'],
                'content'    => $chunk['content'],
                'embed_text' => $chunk['embed_text'],
                'metadata'   => [
                    'tipo'    => 'documento_ulat',
                    'archivo' => $filename,
                    'seccion' => $chunk['section'],
                ]
            ];
        }
    }

    // ------------------------------------------------------------
    // 3. Reglamentos oficiales PDF
    // ------------------------------------------------------------
    echo "📄 [3/4] Indexando reglamentos oficiales en PDF...\n";
    foreach (glob($ulatDocsDir . '/*.pdf') as $filePath) {
        $filename = basename($filePath);
        echo "   ▶ Procesando PDF: {$filename}...\n";
        $extractedText = PdfExtractor::extractText($filePath);
        if (mb_strlen(trim($extractedText)) < 30) {
            echo "   ⚠ PDF vacío: {$filename}\n";
            continue;
        }

        foreach (DocumentChunker::chunkPdf($extractedText, $filename) as $chunk) {
            $docs[] = [
                'collection' => 'reglamentos',
                'doc_id'     => $chunk['doc_id'],
                'title'      => $chunk['title'],
                'content'    => $chunk['content'],
                'embed_text' => $chunk['embed_text'],
                'metadata'   => $chunk['metadata'],
            ];
        }
    }

    // ------------------------------------------------------------
    // 4. Calcular IDF & Guardar vectores en VectorStore
    // ------------------------------------------------------------
    echo "🧮 [4/4] Calculando vectores y guardando " . count($docs) . " fragmentos...\n";
    if ($embeddingService->isLocalMode()) {
        $featureSets = array_map(fn($d) => $embeddingService->extractFeatures($d['embed_text']), $docs);
        $idf = EmbeddingService::computeIdf($featureSets);
        $embeddingService->setIdf($idf);
        $vectorStore->saveMeta('idf', [
            'idf'       => $idf,
            'docs'      => count($docs),
            'generated' => date('c'),
        ]);
        echo "   ✔ Vocabulario IDF calculado: " . count($idf) . " términos.\n";
    }

    $indexados = [];
    $vigentes = [];

    foreach ($docs as $doc) {
        $vector = $embeddingService->getEmbedding($doc['embed_text']);
        if ($vector === null || empty($vector)) continue;

        $ok = $vectorStore->saveDocument(
            collection: $doc['collection'],
            docId: $doc['doc_id'],
            title: $doc['title'],
            content: $doc['content'],
            metadata: $doc['metadata'],
            embedding: $vector
        );

        if ($ok) {
            $col = $doc['collection'];
            $indexados[$col] = ($indexados[$col] ?? 0) + 1;
            $vigentes[$col][] = $doc['doc_id'];
        }
    }

    // Limpiar obsoletos, purgar colecciones heredadas de tienda y vaciar caché de consultas RAG
    $eliminados = 0;
    foreach ($vigentes as $col => $ids) {
        $eliminados += $vectorStore->deleteStale($col, $ids);
    }
    $purgadosTienda = $pdo->exec("DELETE FROM rag_documents WHERE collection NOT IN ('admisiones', 'becas', 'carreras', 'institucional', 'reglamentos')");
    if ($purgadosTienda > 0) {
        echo "   ✔ Purgados {$purgadosTienda} fragmentos contaminados ajenos a ULAT (catalogo/politicas).\n";
    }
    try {
        $pdo->exec("TRUNCATE TABLE rag_query_cache");
        echo "   ✔ Caché de consultas RAG (rag_query_cache) actualizada.\n";
    } catch (Throwable $e) {}

    echo "\n====================================================\n";
    echo "🎉 INDEXACIÓN RAG ULAT COMPLETADA CON ÉXITO!\n";
    foreach ($indexados as $col => $cnt) {
        echo "   - Colección '{$col}': {$cnt} fragmentos indexados.\n";
    }
    echo "   - Total fragmentos activos: " . array_sum($indexados) . "\n";
    echo "====================================================\n";

} catch (Throwable $e) {
    echo "❌ Error en indexación RAG ULAT: " . $e->getMessage() . "\n";
    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo $e->getTraceAsString() . "\n";
    }
}

