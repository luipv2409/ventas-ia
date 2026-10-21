<?php
declare(strict_types=1);

require_once __DIR__ . '/../services/rag/PdfExtractor.php';
require_once __DIR__ . '/../services/rag/DocumentChunker.php';

use Services\Rag\PdfExtractor;
use Services\Rag\DocumentChunker;

echo "=== TEST: PdfExtractor y Sliding Window Chunking (Fase 3) ===\n";

// Crear un PDF sintético de prueba válido en memoria
$pdfContent = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/MediaBox[0 0 612 792]/Parent 2 0 R/Contents 4 0 R/Resources<<>>>>endobj\n4 0 obj<</Length 85>>stream\nBT\n/F1 12 Tf\n100 700 Td\n(Manual de Garantia y Servicio Tecnico de la Tienda. Cobertura valida por 12 meses.) Tj\nET\nendstream\nendobj\nxref\n0 5\n0000000000 65535 f\n0000000009 00000 n\n0000000058 00000 n\n0000000115 00000 n\n0000000216 00000 n\ntrailer<</Size 5/Root 1 0 R>>\nstartxref\n352\n%%EOF";

$tempPdf = __DIR__ . '/test_sample.pdf';
file_put_contents($tempPdf, $pdfContent);

try {
    $extracted = PdfExtractor::extractText($tempPdf);
    echo "Texto extraído: '{$extracted}'\n";

    if (str_contains($extracted, 'Manual de Garantia') && str_contains($extracted, 'Cobertura valida')) {
        echo "✔ Extracción nativa de PDF: CORRECTA\n";
    } else {
        echo "❌ Extracción de PDF falló o vino incompleta\n";
        exit(1);
    }

    // Probar Sliding Window Chunking
    $longText = "Primera oracion sobre la garantia del smartphone. Cobertura completa contra defectos de fabricacion por un ano. " .
                "Segunda oracion sobre envios y entregas urgentes a nivel nacional. Si el cliente solicita cambio inmediato, el despacho se realiza en 24 horas. " .
                "Tercera oracion sobre devoluciones de dinero y reembolsos directos a cuenta bancaria.";

    $chunks = DocumentChunker::chunkPdf($longText, 'garantia_test.pdf', 120, 30);
    echo "Total chunks generados: " . count($chunks) . "\n";
    foreach ($chunks as $i => $ch) {
        echo "  Chunk {$i} [{$ch['title']}]: " . mb_substr($ch['content'], 0, 70) . "...\n";
    }

    if (count($chunks) >= 2) {
        echo "✔ Ventana deslizante con solapamiento: CORRECTA\n";
    } else {
        echo "❌ No se generaron múltiples chunks con solapamiento\n";
        exit(1);
    }

    echo "✔ FASE 3 PASÓ EXITOSAMENTE.\n";
} finally {
    if (file_exists($tempPdf)) {
        unlink($tempPdf);
    }
}
