<?php
declare(strict_types=1);

/**
 * Prueba OFFLINE del motor RAG local (no necesita Docker, BD ni API).
 * Construye el corpus con los archivos reales de knowledge/ y los productos semilla
 * de database/ventas_ia.sql, calcula IDF y evalúa consultas con resultado esperado.
 *
 * Uso: php tests/test_rag_offline.php
 * Nota: no incluye el bonus FULLTEXT (requiere MariaDB) ni la expansión con DeepSeek.
 */

require_once __DIR__ . '/../services/rag/EmbeddingService.php';
require_once __DIR__ . '/../services/rag/VectorStore.php';
require_once __DIR__ . '/../services/rag/DocumentChunker.php';
require_once __DIR__ . '/../services/rag/QueryExpander.php';
require_once __DIR__ . '/../services/rag/RagService.php';

use Services\Rag\DocumentChunker;
use Services\Rag\EmbeddingService;
use Services\Rag\RagService;
use Services\Rag\VectorStore;

// Productos semilla (copia de database/ventas_ia.sql)
$productos = [
    ['id' => 1, 'nombre' => 'Smartphone Galaxy A55', 'descripcion' => 'Pantalla AMOLED 6.6", 128GB, cámara 50MP, batería 5000mAh. Ideal para fotos y redes sociales.', 'precio' => 350, 'stock' => 20, 'sku' => 'ELEC-001', 'categoria' => 'Electrónica'],
    ['id' => 2, 'nombre' => 'Auriculares Bluetooth X3', 'descripcion' => 'Sonido HD inalámbrico, 30h de batería, cancelación de ruido activa, plegables.', 'precio' => 45, 'stock' => 49, 'sku' => 'ELEC-002', 'categoria' => 'Electrónica'],
    ['id' => 3, 'nombre' => 'Tablet Pro 10"', 'descripcion' => 'Pantalla IPS 10 pulgadas, 64GB, WiFi, ideal para estudio y entretenimiento.', 'precio' => 1000, 'stock' => 15, 'sku' => 'ELEC-003', 'categoria' => 'Electrónica'],
    ['id' => 4, 'nombre' => 'Camiseta Polo Classic', 'descripcion' => 'Polo 100% algodón peinado, disponible en tallas S, M, L, XL. Colores: blanco, negro, azul.', 'precio' => 18, 'stock' => 100, 'sku' => 'ROPA-001', 'categoria' => 'Ropa'],
    ['id' => 5, 'nombre' => 'Zapatillas Runner Pro', 'descripcion' => 'Zapatillas deportivas ultralivianas, suela de goma antideslizante, tallas 36-44.', 'precio' => 65, 'stock' => 40, 'sku' => 'ROPA-002', 'categoria' => 'Ropa'],
    ['id' => 6, 'nombre' => 'Licuadora 600W', 'descripcion' => 'Licuadora de 600W, vaso de vidrio 1.5L, 5 velocidades + pulso, cuchillas de acero inoxidable.', 'precio' => 55, 'stock' => 30, 'sku' => 'HOGA-001', 'categoria' => 'Hogar'],
    ['id' => 7, 'nombre' => 'Juego de Sábanas Queen', 'descripcion' => 'Juego de sábanas 100% microfibra, tamaño Queen, 4 piezas, varios colores disponibles.', 'precio' => 35, 'stock' => 60, 'sku' => 'HOGA-002', 'categoria' => 'Hogar'],
    ['id' => 8, 'nombre' => 'Café Molido Premium 500g', 'descripcion' => 'Café arábica molido, tostado medio, origen colombiano, 500 gramos.', 'precio' => 12, 'stock' => 200, 'sku' => 'ALIM-001', 'categoria' => 'Alimentos'],
    ['id' => 9, 'nombre' => 'iPhone 17 Pro Max', 'descripcion' => 'El iPhone 17 Pro Max es el buque insignia de Apple, destacando por su enorme pantalla OLED de 6.9 pulgadas (120 Hz), su procesador A19 Pro con 12 GB de RAM, y su gran batería', 'precio' => 1500, 'stock' => 1, 'sku' => null, 'categoria' => 'Electrónica'],
];

$docs = [];
foreach ($productos as $p) {
    $docs[] = ['collection' => 'catalogo'] + DocumentChunker::productChunk($p);
}
foreach (glob(__DIR__ . '/../knowledge/*.md') as $file) {
    foreach (DocumentChunker::chunkMarkdown(file_get_contents($file), basename($file)) as $c) {
        $docs[] = ['collection' => 'politicas'] + $c;
    }
}

$emb = new EmbeddingService(''); // forzar modo local
$idf = EmbeddingService::computeIdf(array_map(fn($d) => $emb->extractFeatures($d['embed_text']), $docs));
$emb->setIdf($idf);

$rows = [];
foreach ($docs as $i => $d) {
    $rows[] = [
        'id' => $i + 1, 'collection' => $d['collection'], 'doc_id' => $d['doc_id'],
        'title' => $d['title'], 'content' => $d['content'], 'metadata_json' => [],
        'embedding' => $emb->getEmbedding($d['embed_text']),
    ];
}

// [consulta, fragmento esperado en el top-1 (substring del título) o null si NO debe encontrar nada]
$casos = [
    ['¿Cuánto cuesta el Galaxy A55 y tienen stock?', 'Galaxy A55'],
    ['¿Cuáles son los métodos de pago y aceptan QR?', 'Métodos de Pago'],
    ['¿Hacen envíos a otras ciudades y cuánto tardan?', 'Envíos'],
    ['¿Hacen despachos a Cochabamba?', 'Envíos'],
    ['¿En cuánto tiempo me llega a Santa Cruz?', 'Envíos'],
    ['Cuanto tiempo tengo para devolver un producto si llega mal?', 'Devolución'],
    ['Se me rompió el celular, ¿lo cubre la garantía?', 'Garantía'],
    ['¿Puedo pagar con tarjeta Visa?', 'Métodos de Pago'],
    ['¿Puedo depositar al BCP?', 'Métodos de Pago'],
    ['¿Atienden los domingos?', 'Horarios'],
    ['Necesito factura con NIT de mi empresa', 'Facturación'],
    ['tienen audífonos?', 'Auriculares'],
    ['quiero unas zapatillas para correr', 'Zapatillas'],
    ['¿Qué celulares tienen?', 'Smartphone'],
    ['¿Cómo hago un pedido?', 'Compra'],
    ['¿Tienen tienda física para recoger?', 'Horarios'],
    ['Hola, buenos días', null],
    ['gracias!', null],
    ['me cuentas un chiste?', null],
    ['¿quién ganó el partido ayer?', null],
    ['recomiéndame películas', null],
];

// Consultas que NO se usaron para calibrar (miden generalización; no afectan el exit code)
$casosNoCalibrados = [
    ['¿Cuánto cobran por el envío?', 'Envíos'],
    ['el producto me vino fallado, ¿qué hago?', 'Garantía|Devolución'],
    ['¿puedo pagar cuando me llegue?', 'Métodos de Pago'],
    ['¿hasta qué hora atienden el sábado?', 'Horarios'],
    ['busco algo para escuchar música', 'Auriculares'],
    ['¿la licuadora tiene vaso de vidrio?', 'Licuadora'],
    ['¿tienen poleras talla XL?', 'Polo'],
    ['¿aceptan transferencias del Banco Unión?', 'Métodos de Pago'],
    ['me arrepentí de la compra, ¿me devuelven la plata?', 'Devolución'],
    ['¿venden café?', 'Café'],
    ['¿me das el número de seguimiento?', 'Envíos'],
    ['jajaja ok', null],
    ['¿cuál es la capital de Francia?', null],
];

$min = RagService::DEFAULT_MIN_SCORE;
echo "Corpus: " . count($rows) . " fragmentos | Vocabulario: " . count($idf) . " | minScore: {$min}\n";

$evaluar = function (array $casos, string $titulo) use ($emb, $rows, $min): int {
    echo "\n=== {$titulo} ===\n";
    $ok = 0;
    foreach ($casos as [$q, $esperado]) {
        $query = $emb->buildQuery($q);
        $cands = VectorStore::rankRows($rows, $query['vector'], 12, $min * 0.6, $query['units']);
        $res = RagService::selectTop($cands, 3, $min);

        $top = $res[0]['title'] ?? null;
        $pass = $esperado === null
            ? $top === null
            : ($top !== null && (bool)preg_match('/' . $esperado . '/iu', $top));
        $ok += $pass ? 1 : 0;

        echo ($pass ? '✅' : '❌') . " \"$q\"\n";
        if (empty($res)) {
            $mejor = $cands[0] ?? null;
            echo "     (sin resultados" . ($mejor ? sprintf("; mejor candidato %.3f %s", $mejor['score'], $mejor['title']) : '') . ")\n";
        }
        foreach ($res as $r) {
            printf("     %.3f  %s\n", $r['score'], $r['title']);
        }
    }
    printf("--> %d/%d correctos\n", $ok, count($casos));
    return $ok;
};

$ok = $evaluar($casos, 'Casos de calibración');
$evaluar($casosNoCalibrados, 'Casos NO calibrados (generalización)');

exit($ok === count($casos) ? 0 : 1);
