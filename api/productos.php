<?php
// ============================================================
// ARCHIVO: api/productos.php
// PROPÓSITO: Endpoint REST que devuelve el catálogo en JSON.
// MÉTODO: GET
// RESPUESTA: { "ok": true, "productos": [...], "categorias": [...] }
// ============================================================
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = Database::getConnection();

    // Filtro opcional por categoría: ?categoria_id=1
    $categoriaId = isset($_GET['categoria_id']) ? (int)$_GET['categoria_id'] : 0;

    $sql = "
        SELECT p.id, p.nombre, p.descripcion, p.precio, p.stock,
               p.imagen_url, p.sku, c.nombre AS categoria, c.id AS categoria_id
        FROM productos p
        INNER JOIN categorias c ON c.id = p.categoria_id
        WHERE p.activo = 1
    ";
    if ($categoriaId > 0) {
        $sql .= " AND p.categoria_id = :categoria_id";
    }
    $sql .= " ORDER BY c.nombre, p.nombre";

    $stmt = $pdo->prepare($sql);
    if ($categoriaId > 0) {
        $stmt->bindValue(':categoria_id', $categoriaId, PDO::PARAM_INT);
    }
    $stmt->execute();
    $productos = $stmt->fetchAll();

    // Categorías para el filtro
    $cats = $pdo->query("SELECT id, nombre FROM categorias WHERE activa = 1 ORDER BY nombre")->fetchAll();

    echo json_encode(['ok' => true, 'productos' => $productos, 'categorias' => $cats]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => APP_DEBUG ? $e->getMessage() : 'Error interno']);
}
