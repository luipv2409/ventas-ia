<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$pedidoId = (int)($_GET['id'] ?? 0);
if ($pedidoId <= 0) {
    echo json_encode([]);
    exit;
}

$pdo = Database::getConnection();
$stmt = $pdo->prepare("
    SELECT pr.nombre, dp.cantidad, dp.subtotal
    FROM detalle_pedidos dp
    JOIN productos pr ON dp.producto_id = pr.id
    WHERE dp.pedido_id = :pid
");
$stmt->execute([':pid' => $pedidoId]);
$productos = $stmt->fetchAll();

echo json_encode($productos);