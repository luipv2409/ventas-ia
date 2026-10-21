<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// 1. Probar llamada interna a api/filtrar_productos.php con categoria_id=todas
$_GET['categoria_id'] = 'todas';
ob_start();
@include __DIR__ . '/../api/filtrar_productos.php';
$jsonStr = ob_get_clean();

// Extraer sólo el JSON si hubiera algún aviso previo
$pos = strpos($jsonStr, '{');
if ($pos !== false) {
    $jsonStr = substr($jsonStr, $pos);
}

$data = json_decode($jsonStr, true);
if (!$data || !isset($data['html'])) {
    echo "❌ Error al decodificar respuesta JSON de api/filtrar_productos.php" . PHP_EOL;
    exit(1);
}

echo "=== TEST: DEPARTAMENTOS Y CATÁLOGO MINIMALISTA ===" . PHP_EOL;
echo " [+] Total de productos: " . $data['total'] . PHP_EOL;
preg_match_all('/<h4[^>]*class="[^"]*fw-bold[^"]*"[^>]*>(.*?)<\/h4>/i', $data['html'], $matches);

echo " [+] Estanterías/Secciones de Departamentos generadas:" . PHP_EOL;
foreach ($matches[1] as $seccion) {
    echo "    ✔ " . trim(strip_tags($seccion)) . PHP_EOL;
}

// 2. Probar filtro específico por categoría (ej. Celulares = 1)
$_GET['categoria_id'] = '1';
ob_start();
@include __DIR__ . '/../api/filtrar_productos.php';
$jsonCat1 = ob_get_clean();
$pos1 = strpos($jsonCat1, '{');
if ($pos1 !== false) $jsonCat1 = substr($jsonCat1, $pos1);
$dataCat1 = json_decode($jsonCat1, true);

echo " [+] Filtrado específico de Celulares (#1): " . $dataCat1['total'] . " artículos." . PHP_EOL;
if (strpos($dataCat1['html'], 'Celulares') !== false) {
    echo "    ✔ Cabecera de departamento Celulares presente." . PHP_EOL;
} else {
    echo "    ❌ No se encontró la cabecera de Celulares." . PHP_EOL;
    exit(1);
}

// 3. Probar filtro específico de Café (#8)
$_GET['categoria_id'] = '8';
ob_start();
@include __DIR__ . '/../api/filtrar_productos.php';
$jsonCat8 = ob_get_clean();
$pos8 = strpos($jsonCat8, '{');
if ($pos8 !== false) $jsonCat8 = substr($jsonCat8, $pos8);
$dataCat8 = json_decode($jsonCat8, true);

echo " [+] Filtrado específico de Café (#8): " . $dataCat8['total'] . " artículos." . PHP_EOL;
if (strpos($dataCat8['html'], 'Café') !== false) {
    echo "    ✔ Cabecera de departamento Café presente." . PHP_EOL;
} else {
    echo "    ❌ No se encontró la cabecera de Café." . PHP_EOL;
    exit(1);
}

echo "✅ TODAS LAS PRUEBAS DE DEPARTAMENTOS PASARON EXITOSAMENTE." . PHP_EOL;
