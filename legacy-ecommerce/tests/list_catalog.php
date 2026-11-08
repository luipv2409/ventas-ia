<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getConnection();
$stmt = $pdo->query("SELECT p.id, p.nombre, c.nombre as categoria, p.precio, p.stock FROM productos p JOIN categorias c ON p.categoria_id = c.id WHERE p.activo = 1 ORDER BY c.id, p.id");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cats = [];
foreach ($rows as $r) {
    $cats[$r['categoria']][] = $r['nombre'] . " (Stock: {$r['stock']})";
}

foreach ($cats as $cat => $prods) {
    echo "=== Categoría: $cat (" . count($prods) . " productos) ===\n";
    foreach ($prods as $p) {
        echo "  - $p\n";
    }
}
