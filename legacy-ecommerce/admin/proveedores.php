<?php
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getConnection();

if (isset($_GET['eliminar']) && is_numeric($_GET['eliminar'])) {
    $stmt = $pdo->prepare("DELETE FROM proveedores WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['eliminar']]);
    header('Location: proveedores.php?msg=eliminado');
    exit;
}
$proveedores = $pdo->query("SELECT * FROM proveedores ORDER BY nombre")->fetchAll();

ob_start();
?>
<h2>🚚 Proveedores</h2>
<a href="proveedor_form.php" class="btn btn-primary mb-3">➕ Nuevo Proveedor</a>
<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">Operación realizada correctamente.</div>
<?php endif; ?>
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr><th>ID</th><th>Nombre</th><th>Contacto</th><th>Teléfono</th><th>Email</th><th>Acciones</th></tr>
        </thead>
        <tbody>
        <?php foreach ($proveedores as $prov): ?>
            <tr>
                <td><?= $prov['id'] ?></td>
                <td><?= htmlspecialchars($prov['nombre']) ?></td>
                <td><?= htmlspecialchars($prov['contacto'] ?? '') ?></td>
                <td><?= htmlspecialchars($prov['telefono'] ?? '') ?></td>
                <td><?= htmlspecialchars($prov['email'] ?? '') ?></td>
                <td>
                    <a href="proveedor_form.php?id=<?= $prov['id'] ?>" class="btn btn-sm btn-outline-primary">✏️</a>
                    <a href="proveedores.php?eliminar=<?= $prov['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Eliminar proveedor?')">🗑️</a>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php
$contenido = ob_get_clean();
$titulo = 'Gestión de Proveedores';
require 'layout.php';