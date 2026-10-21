<?php
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getConnection();
$editando = false;
$proveedor = ['nombre' => '', 'contacto' => '', 'telefono' => '', 'email' => '', 'direccion' => ''];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id = :id");
    $stmt->execute([':id' => (int)$_GET['id']]);
    $proveedor = $stmt->fetch();
    if (!$proveedor) die('Proveedor no encontrado');
    $editando = true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $contacto = trim($_POST['contacto'] ?? '');
    $telefono = trim($_POST['telefono'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $direccion = trim($_POST['direccion'] ?? '');

    if ($nombre === '') {
        $error = 'El nombre es obligatorio.';
    } else {
        try {
            if ($editando) {
                $stmt = $pdo->prepare("UPDATE proveedores SET nombre=:n, contacto=:c, telefono=:t, email=:e, direccion=:d WHERE id=:id");
                $stmt->execute([
                    ':n' => $nombre,
                    ':c' => $contacto,
                    ':t' => $telefono,
                    ':e' => $email,
                    ':d' => $direccion,
                    ':id' => (int)$_GET['id']
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO proveedores (nombre, contacto, telefono, email, direccion) VALUES (:n, :c, :t, :e, :d)");
                $stmt->execute([
                    ':n' => $nombre,
                    ':c' => $contacto,
                    ':t' => $telefono,
                    ':e' => $email,
                    ':d' => $direccion
                ]);
            }
            header('Location: proveedores.php?msg=guardado');
            exit;
        } catch (PDOException $e) {
            $error = 'Error al guardar: ' . $e->getMessage();
        }
    }
}

ob_start();
?>
<h2><?= $editando ? '✏️ Editar Proveedor' : '➕ Nuevo Proveedor' ?></h2>
<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>
<form method="POST" class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nombre *</label>
        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($proveedor['nombre']) ?>" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Persona de contacto</label>
        <input type="text" name="contacto" class="form-control" value="<?= htmlspecialchars($proveedor['contacto']) ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label">Teléfono</label>
        <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($proveedor['telefono']) ?>">
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($proveedor['email']) ?>">
    </div>
    <div class="col-12">
        <label class="form-label">Dirección</label>
        <textarea name="direccion" class="form-control"><?= htmlspecialchars($proveedor['direccion']) ?></textarea>
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-success">💾 Guardar</button>
        <a href="proveedores.php" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
<?php
$contenido = ob_get_clean();
$titulo = $editando ? 'Editar Proveedor' : 'Nuevo Proveedor';
require 'layout.php';