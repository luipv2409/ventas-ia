<?php
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getConnection();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Pedido no especificado");
}
$pedidoId = (int)$_GET['id'];

// Obtener cabecera del pedido
$stmt = $pdo->prepare("SELECT p.*, c.nombre AS cliente, c.email, c.telefono FROM pedidos p JOIN clientes c ON p.cliente_id = c.id WHERE p.id = :id");
$stmt->execute([':id' => $pedidoId]);
$pedido = $stmt->fetch();
if (!$pedido) die("Pedido no encontrado");

// Obtener detalle
$stmtDet = $pdo->prepare("SELECT dp.*, pr.nombre AS producto FROM detalle_pedidos dp JOIN productos pr ON dp.producto_id = pr.id WHERE dp.pedido_id = :id");
$stmtDet->execute([':id' => $pedidoId]);
$detalles = $stmtDet->fetchAll();

ob_start();
?>

<h2>📄 Detalle del Pedido #<?= $pedido['id'] ?></h2>

<div class="card mb-3">
    <div class="card-body">
        <h5>Cliente: <?= htmlspecialchars($pedido['cliente']) ?></h5>
        <p>Email: <?= htmlspecialchars($pedido['email'] ?: 'N/A') ?><br>
        Teléfono: <?= htmlspecialchars($pedido['telefono'] ?: 'N/A') ?><br>
        Fecha: <?= $pedido['creado_en'] ?><br>
        Estado: <span class="badge bg-<?= $pedido['estado'] == 'pendiente' ? 'warning' : ($pedido['estado'] == 'entregado' ? 'success' : 'info') ?>"><?= $pedido['estado'] ?></span></p>
    </div>
</div>

<h5>Productos</h5>
<table class="table table-bordered">
    <thead><tr><th>Producto</th><th>Cantidad</th><th>Precio Unit.</th><th>Subtotal</th></tr></thead>
    <tbody>
    <?php foreach ($detalles as $d): ?>
        <tr>
            <td><?= htmlspecialchars($d['producto']) ?></td>
            <td><?= $d['cantidad'] ?></td>
            <td>Bs <?= number_format((float)$d['precio_unitario'], 2) ?></td>
            <td>Bs <?= number_format((float)$d['subtotal'], 2) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot><tr><th colspan="3" class="text-end">Total:</th><th>Bs <?= number_format((float)$pedido['total'], 2) ?></th></tr></tfoot>
</table>

<a href="pedidos.php" class="btn btn-secondary">Volver a pedidos</a>

<?php
$contenido = ob_get_clean();
$titulo = 'Detalle Pedido';
require 'layout.php';