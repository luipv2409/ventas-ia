<?php
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getConnection();

// Obtener valores actuales
$stmt = $pdo->query("SELECT clave, valor FROM configuracion");
$config = [];
while ($row = $stmt->fetch()) {
    $config[$row['clave']] = $row['valor'];
}

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $simbolo      = trim($_POST['moneda_simbolo'] ?? '');
    $codigo       = trim($_POST['moneda_codigo'] ?? '');
    $tasa         = (float)($_POST['tasa_cambio_usd'] ?? 1);
    $umbral       = (int)($_POST['umbral_stock'] ?? 5);
    $nombreTienda = trim($_POST['nombre_tienda'] ?? 'Tienda IA');

    $stmt = $pdo->prepare("REPLACE INTO configuracion (clave, valor) VALUES 
        ('moneda_simbolo', :simbolo),
        ('moneda_codigo', :codigo),
        ('tasa_cambio_usd', :tasa),
        ('umbral_stock', :umbral),
        ('nombre_tienda', :nombre_tienda)
    ");
    $stmt->execute([
        ':simbolo'      => $simbolo,
        ':codigo'       => $codigo,
        ':tasa'         => $tasa,
        ':umbral'       => $umbral,
        ':nombre_tienda' => $nombreTienda,
    ]);

    header('Location: configuracion.php?msg=ok');
    exit;
}

ob_start();
?>
<h2>⚙️ Configuración General</h2>

<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-success">✅ Configuración guardada correctamente.</div>
<?php endif; ?>

<form method="POST" class="row g-3">
    <div class="col-md-3">
        <label class="form-label">Símbolo de moneda</label>
        <input type="text" name="moneda_simbolo" class="form-control" 
               value="<?= htmlspecialchars($config['moneda_simbolo'] ?? 'Bs') ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Código ISO</label>
        <input type="text" name="moneda_codigo" class="form-control" 
               value="<?= htmlspecialchars($config['moneda_codigo'] ?? 'BOB') ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Tasa de cambio (1 USD = ?)</label>
        <input type="number" step="0.01" name="tasa_cambio_usd" class="form-control" 
               value="<?= htmlspecialchars($config['tasa_cambio_usd'] ?? '6.96') ?>">
    </div>
    <div class="col-md-3">
        <label class="form-label">Umbral de stock bajo</label>
        <input type="number" name="umbral_stock" class="form-control" 
               value="<?= htmlspecialchars($config['umbral_stock'] ?? '5') ?>">
    </div>
    <div class="col-md-4">
        <label class="form-label">Nombre de la tienda</label>
        <input type="text" name="nombre_tienda" class="form-control" 
            value="<?= htmlspecialchars($config['nombre_tienda'] ?? 'Tienda IA') ?>">
    </div>
    <div class="col-12">
        <button type="submit" class="btn btn-success">💾 Guardar</button>
    </div>
</form>
<?php
$contenido = ob_get_clean();
$titulo = 'Configuración';
require 'layout.php';