<?php
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getConnection();

// Procesar cambio de estado si se envía por GET (o podríamos usar POST, pero para simplificar usamos GET con confirmación)
if (isset($_GET['cambiar_estado'], $_GET['pedido_id']) && is_numeric($_GET['pedido_id'])) {
    $pedidoId = (int)$_GET['pedido_id'];
    $nuevoEstado = $_GET['cambiar_estado'];
    $estadosPermitidos = ['pendiente', 'confirmado', 'enviado', 'entregado', 'cancelado'];
    
    if (in_array($nuevoEstado, $estadosPermitidos)) {
        try {
            $stmt = $pdo->prepare("UPDATE pedidos SET estado = :est WHERE id = :id");
            $stmt->execute([':est' => $nuevoEstado, ':id' => $pedidoId]);
            header("Location: pedidos.php?msg=Estado actualizado");
            exit;
        } catch (PDOException $e) {
            $error = "Error al actualizar: " . $e->getMessage();
        }
    }
}

// Obtener pedidos con cliente
$pedidos = $pdo->query("
    SELECT p.id, p.estado, p.total, p.creado_en, c.nombre AS cliente
    FROM pedidos p
    JOIN clientes c ON p.cliente_id = c.id
    ORDER BY p.creado_en DESC
")->fetchAll();

ob_start();
?>

<h2>📋 Gestión de Pedidos</h2>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_GET['msg']) ?></div>
<?php elseif (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped">
        <thead class="table-dark">
            <tr><th>ID</th><th>Cliente</th><th>Total</th><th>Estado</th><th>Fecha</th><th>Acción</th></tr>
        </thead>
        <tbody>
        <?php foreach ($pedidos as $ped): ?>
            <tr>
                <td><?= $ped['id'] ?></td>
                <td><?= htmlspecialchars($ped['cliente']) ?></td>
                <td>Bs <?= number_format((float)$ped['total'], 2) ?></td>
                <td>
                    <span class="badge bg-<?= 
                        $ped['estado'] == 'pendiente' ? 'warning' : 
                        ($ped['estado'] == 'entregado' ? 'success' : 
                        ($ped['estado'] == 'cancelado' ? 'danger' : 'info'))
                    ?>"><?= $ped['estado'] ?></span>
                </td>
                <td><?= $ped['creado_en'] ?></td>
                <td>
                    <?php if ($ped['estado'] != 'entregado' && $ped['estado'] != 'cancelado'): ?>
                        <div class="btn-group btn-group-sm">
                            <?php if ($ped['estado'] == 'pendiente'): ?>
                                <a href="?cambiar_estado=confirmado&pedido_id=<?= $ped['id'] ?>" class="btn btn-outline-success">Confirmar</a>
                            <?php endif; ?>
                            <?php if ($ped['estado'] == 'confirmado' || $ped['estado'] == 'pendiente'): ?>
                                <a href="?cambiar_estado=enviado&pedido_id=<?= $ped['id'] ?>" class="btn btn-outline-primary">Enviar</a>
                            <?php endif; ?>
                            <a href="?cambiar_estado=entregado&pedido_id=<?= $ped['id'] ?>" class="btn btn-outline-success">Entregar</a>
                            <a href="?cambiar_estado=cancelado&pedido_id=<?= $ped['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('¿Cancelar pedido?')">Cancelar</a>
                        </div>
                    <?php else: ?>
                        <span class="text-muted">Finalizado</span>
                    <?php endif; ?>
                    <!-- Opción para ver detalle -->
                    <a href="detalle_pedido.php?id=<?= $ped['id'] ?>" class="btn btn-sm btn-outline-secondary ms-1">📄 Detalle</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
$contenido = ob_get_clean();
$titulo = 'Gestión de Pedidos';
require 'layout.php';