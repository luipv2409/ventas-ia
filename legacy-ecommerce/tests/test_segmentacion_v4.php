<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=================================================================\n";
echo "🔬 PRUEBA: SEGMENTACIÓN V4, COMPARADOR HOMOGÉNEO Y ADMIN PRODUCTO\n";
echo "=================================================================\n\n";

$pdo = Database::getConnection();

// 1. Validar las 8 categorías segmentadas
echo "--- 1. Verificación de Categorías Segmentadas ---\n";
$cats = $pdo->query("SELECT id, nombre, slug FROM categorias ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$esperadas = [
    1 => 'Celulares & Smartphones',
    2 => 'Tablets',
    3 => 'Audio & Auriculares',
    4 => 'Zapatillas & Calzado',
    5 => 'Poleras & Camisetas',
    6 => 'Electrodomésticos de Cocina',
    7 => 'Ropa de Cama & Hogar',
    8 => 'Café & Alimentos Gourmet'
];

foreach ($esperadas as $id => $nomEsperado) {
    $encontrada = false;
    foreach ($cats as $c) {
        if ((int)$c['id'] === $id && str_contains($c['nombre'], explode(' ', $nomEsperado)[0])) {
            $encontrada = true;
            break;
        }
    }
    if (!$encontrada) {
        echo "❌ ERROR: Categoría ID {$id} ('{$nomEsperado}') no encontrada.\n";
        exit(1);
    }
    echo "  ✔ Categoría #{$id}: {$nomEsperado} verificada.\n";
}

// 2. Validar que cada categoría tiene al menos 2 productos para comparativa
echo "\n--- 2. Verificación de Productos por Categoría (Comparables) ---\n";
foreach ($esperadas as $id => $nom) {
    $stmtP = $pdo->prepare("SELECT id, nombre FROM productos WHERE categoria_id = :cid AND activo = 1");
    $stmtP->execute([':cid' => $id]);
    $prods = $stmtP->fetchAll(PDO::FETCH_ASSOC);
    $cant = count($prods);
    if ($cant < 2) {
        echo "❌ ERROR: Categoría #{$id} tiene menos de 2 productos ({$cant}).\n";
        exit(1);
    }
    echo "  ✔ Categoría #{$id} ({$nom}): {$cant} productos disponibles (Ej: {$prods[0]['nombre']}, {$prods[1]['nombre']})\n";
}

// 3. Simular inserción y edición en admin con SKU y especificaciones JSON
echo "\n--- 3. Prueba de Inserción y Edición de Producto Admin (Esquema Completo) ---\n";
$skuPrueba = 'TEST-CEL-' . rand(1000, 9999);
$specsTest = json_encode([
    'tipo_dispositivo' => 'Smartphone Test',
    'pantalla' => 'OLED 6.8"',
    'procesador' => 'Test Core 3.0GHz',
    'kimovil_score' => '9.5 / 10'
], JSON_UNESCAPED_UNICODE);

$stmtIns = $pdo->prepare("
    INSERT INTO productos (categoria_id, nombre, sku, descripcion, precio, stock, imagen_url, especificaciones, activo)
    VALUES (1, 'Producto Test Segmentado', :sku, 'Descripción de prueba', 299.99, 15, 'https://example.com/test.jpg', :spe, 1)
");
$stmtIns->execute([':sku' => $skuPrueba, ':spe' => $specsTest]);
$nuevoId = (int)$pdo->lastInsertId();

if ($nuevoId > 0) {
    echo "  ✔ Inserción exitosa de producto con SKU '{$skuPrueba}', ID: {$nuevoId}\n";
    
    // Consultar producto insertado
    $stmtCheck = $pdo->prepare("SELECT * FROM productos WHERE id = :id");
    $stmtCheck->execute([':id' => $nuevoId]);
    $pRecuperado = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($pRecuperado['sku'] === $skuPrueba && !empty($pRecuperado['especificaciones'])) {
        echo "  ✔ SKU y especificaciones verificados con fidelidad en la BD.\n";
    } else {
        echo "❌ ERROR: Los datos recuperados no coinciden con los guardados.\n";
        exit(1);
    }

    // Limpieza
    $pdo->prepare("DELETE FROM productos WHERE id = :id")->execute([':id' => $nuevoId]);
    echo "  ✔ Producto de prueba eliminado limpiamente.\n";
} else {
    echo "❌ ERROR: No se pudo insertar el producto de prueba.\n";
    exit(1);
}

// 4. Verificar interfaz de filtros colapsables en index.php
echo "\n--- 4. Verificación de Filtro Colapsable en index.php ---\n";
$indexContent = file_get_contents(__DIR__ . '/../index.php');
if (str_contains($indexContent, 'id="col-filtros"') && 
    str_contains($indexContent, 'id="col-productos"') && 
    str_contains($indexContent, 'id="btn-toggle-filtros"')) {
    echo "  ✔ Elementos del panel de filtros colapsable presentes en index.php.\n";
} else {
    echo "❌ ERROR: Faltan identificadores para filtros colapsables en index.php.\n";
    exit(1);
}

// 5. Verificar lógica en catalogo.js
$catalogoJsContent = file_get_contents(__DIR__ . '/../assets/js/catalogo.js');
if (str_contains($catalogoJsContent, 'setFiltrosVisibles') && 
    str_contains($catalogoJsContent, 'tienda_filtros_ocultos')) {
    echo "  ✔ Lógica de toggle y persistencia localStorage presente en catalogo.js.\n";
} else {
    echo "❌ ERROR: Falta lógica de visibilidad en catalogo.js.\n";
    exit(1);
}

echo "\n=================================================================\n";
echo "🎉 TODAS LAS PRUEBAS DE LA V4 PASARON EXITOSAMENTE!\n";
echo "=================================================================\n";
