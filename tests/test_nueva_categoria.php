<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=================================================================\n";
echo "🔬 PRUEBA: CREACIÓN Y GESTIÓN DE NUEVAS CATEGORÍAS (ADMIN & API)\n";
echo "=================================================================\n\n";

$pdo = Database::getConnection();

// 1. Probar api/guardar_categoria.php simulando POST
echo "--- 1. Prueba de Creación de Nueva Categoría ---\n";
$catTestNombre = 'Laptops & Ultrabooks Test ' . rand(100, 999);
$catTestIcono  = 'fas fa-laptop';
$catTestDesc   = 'Categoría creada para portátiles de alta gama.';

// Simular petición
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['nombre'] = $catTestNombre;
$_POST['icono'] = $catTestIcono;
$_POST['descripcion'] = $catTestDesc;

ob_start();
require __DIR__ . '/../api/guardar_categoria.php';
$jsonOutput = ob_get_clean();

$resp = json_decode($jsonOutput, true);

if (!empty($resp['success']) && !empty($resp['categoria']['id'])) {
    $nuevoCatId = (int)$resp['categoria']['id'];
    echo "  ✔ API respondió éxito. Nueva categoría ID: {$nuevoCatId}, Nombre: '{$resp['categoria']['nombre']}', Slug: '{$resp['categoria']['slug']}'\n";
} else {
    echo "❌ ERROR: No se pudo crear la categoría vía API. Respuesta:\n" . $jsonOutput . "\n";
    exit(1);
}

// 2. Verificar en la base de datos
echo "\n--- 2. Verificación en Base de Datos ---\n";
$stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = :id");
$stmt->execute([':id' => $nuevoCatId]);
$catDb = $stmt->fetch(PDO::FETCH_ASSOC);

if ($catDb && $catDb['nombre'] === $catTestNombre && $catDb['icono'] === $catTestIcono) {
    echo "  ✔ Registro verificado en BD con icono '{$catDb['icono']}' y slug '{$catDb['slug']}'.\n";
} else {
    echo "❌ ERROR: Los datos en BD no coinciden.\n";
    exit(1);
}

// 3. Probar asignación de un producto a la nueva categoría
echo "\n--- 3. Asignación de Producto a la Nueva Categoría ---\n";
$stmtProd = $pdo->prepare("
    INSERT INTO productos (categoria_id, nombre, sku, descripcion, precio, stock, imagen_url, activo)
    VALUES (:cid, 'Laptop ZenBook 14 Test', :sku, 'Laptop ultraligera OLED', 899.00, 10, 'https://example.com/laptop.jpg', 1)
");
$skuTest = 'ELEC-LAP-' . rand(1000, 9999);
$stmtProd->execute([':cid' => $nuevoCatId, ':sku' => $skuTest]);
$prodId = (int)$pdo->lastInsertId();

if ($prodId > 0) {
    echo "  ✔ Producto creado exitosamente con ID {$prodId} en la nueva categoría #{$nuevoCatId}.\n";
} else {
    echo "❌ ERROR: Falló inserción de producto en la nueva categoría.\n";
    exit(1);
}

// 4. Limpieza limpia de datos de prueba
echo "\n--- 4. Limpieza de Prueba ---\n";
$pdo->prepare("DELETE FROM productos WHERE id = :id")->execute([':id' => $prodId]);
$pdo->prepare("DELETE FROM categorias WHERE id = :id")->execute([':id' => $nuevoCatId]);
echo "  ✔ Producto y Categoría de prueba eliminados correctamente.\n";

echo "\n=================================================================\n";
echo "🎉 TODAS LAS PRUEBAS DE NUEVAS CATEGORÍAS PASARON EXITOSAMENTE!\n";
echo "=================================================================\n";
