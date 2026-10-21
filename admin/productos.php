<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getConnection();

// Obtener tasa de cambio oficial
$stmtTasa = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'tasa_cambio_usd'");
$tasaCambio = (float)($stmtTasa ? $stmtTasa->fetchColumn() : 9.96);
if ($tasaCambio <= 0) $tasaCambio = 9.96;

$mensaje = '';
$tipoMensaje = '';

// Soft delete: cambiar estado activo/inactivo
if (isset($_GET['toggle_activo']) && is_numeric($_GET['toggle_activo'])) {
    $idToggle = (int)$_GET['toggle_activo'];

    // Obtener estado actual
    $stmt = $pdo->prepare("SELECT activo, nombre FROM productos WHERE id = :id");
    $stmt->execute([':id' => $idToggle]);
    $producto = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($producto) {
        $nuevoEstado = $producto['activo'] ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE productos SET activo = :activo WHERE id = :id");
        $stmt->execute([':activo' => $nuevoEstado, ':id' => $idToggle]);

        $mensaje = $nuevoEstado ? "Producto '{$producto['nombre']}' reactivado correctamente." : "Producto '{$producto['nombre']}' desactivado (oculto en tienda).";
        $tipoMensaje = 'success';
    } else {
        $mensaje = 'Producto no encontrado.';
        $tipoMensaje = 'danger';
    }

    header("Location: productos.php?msg=" . urlencode($mensaje) . "&tipo=" . $tipoMensaje);
    exit;
}

// Mostrar mensaje de redirección
if (isset($_GET['msg'])) {
    $mensaje = $_GET['msg'];
    $tipoMensaje = $_GET['tipo'] ?? 'info';
}

// Filtro opcional por categoría
$filtroCat = isset($_GET['cat']) && is_numeric($_GET['cat']) ? (int)$_GET['cat'] : 0;
$sqlWhere = $filtroCat > 0 ? "WHERE p.categoria_id = {$filtroCat}" : "";

// Obtener todos los productos (activos e inactivos)
$productos = $pdo->query("
    SELECT p.*, c.nombre AS categoria, pr.nombre AS proveedor
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    LEFT JOIN proveedores pr ON p.proveedor_id = pr.id
    {$sqlWhere}
    ORDER BY p.categoria_id ASC, p.nombre ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Métricas de inventario
$totalProds = count($productos);
$totalActivos = count(array_filter($productos, fn($p) => $p['activo'] == 1));
$totalValorizadoUsd = array_reduce($productos, fn($sum, $p) => $sum + ($p['precio'] * $p['stock']), 0.0);
$totalValorizadoBs = $totalValorizadoUsd * $tasaCambio;
$totalConFicha = count(array_filter($productos, fn($p) => !empty($p['especificaciones'])));

// Categorías para el filtro
$categorias = $pdo->query("SELECT id, nombre FROM categorias ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<!-- Header y Acciones -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1 brand-font">📦 Gestión de Productos & Catálogo</h2>
        <p class="text-muted small mb-0">Control integral de inventario, segmentación por categorías y fichas Kimovil.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="producto_form.php" class="btn btn-primary rounded-pill px-4 py-2 d-inline-flex align-items-center gap-2 shadow-sm">
            <i data-lucide="plus"></i>
            <span>Nuevo Producto</span>
        </a>
    </div>
</div>

<?php if ($mensaje): ?>
    <div class="alert alert-<?= $tipoMensaje ?> border-0 shadow-sm rounded-4 d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="<?= $tipoMensaje === 'success' ? 'check-circle-2' : 'info' ?>"></i>
            <span><?= htmlspecialchars($mensaje) ?></span>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Tarjetas KPI Resumen -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Total Productos</span>
                    <h3 class="fw-bold mb-0 mt-1"><?= $totalProds ?></h3>
                    <small class="text-success"><i data-lucide="check-circle-2" class="me-1"></i> <?= $totalActivos ?> activos en tienda</small>
                </div>
                <div class="p-3 bg-primary-subtle text-primary rounded-4">
                    <i data-lucide="boxes" style="width:28px;height:28px;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Fichas Técnicas</span>
                    <h3 class="fw-bold mb-0 mt-1 text-info"><?= $totalConFicha ?></h3>
                    <small class="text-muted">Con especificaciones</small>
                </div>
                <div class="p-3 bg-info-subtle text-info rounded-4">
                    <i data-lucide="cpu" style="width:28px;height:28px;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Inventario Valorizado</span>
                    <h4 class="fw-bold mb-0 mt-1 text-primary">Bs <?= number_format($totalValorizadoBs, 2) ?></h4>
                    <small class="text-muted">$<?= number_format($totalValorizadoUsd, 2) ?> USD base</small>
                </div>
                <div class="p-3 bg-success-subtle text-success rounded-4">
                    <i data-lucide="wallet" style="width:28px;height:28px;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Tasa Oficial</span>
                    <h4 class="fw-bold mb-0 mt-1 text-warning">1 USD = <?= number_format($tasaCambio, 2) ?> Bs</h4>
                    <small class="text-muted"><a href="configuracion.php" class="text-decoration-none">Ajustar Tasa</a></small>
                </div>
                <div class="p-3 bg-warning-subtle text-warning rounded-4">
                    <i data-lucide="banknote" style="width:28px;height:28px;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Barra de Filtro Rápido por Categoría -->
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div class="d-flex align-items-center gap-2">
            <span class="fw-semibold small text-muted"><i data-lucide="filter" class="me-1"></i> Filtrar Categoría:</span>
            <div class="btn-group btn-group-sm flex-wrap" role="group">
                <a href="productos.php" class="btn btn-outline-secondary <?= $filtroCat === 0 ? 'active' : '' ?>">Todas</a>
                <?php foreach ($categorias as $c): ?>
                    <a href="productos.php?cat=<?= $c['id'] ?>" class="btn btn-outline-secondary <?= $filtroCat === (int)$c['id'] ? 'active' : '' ?>">
                        <?= htmlspecialchars($c['nombre']) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <input type="text" id="tabla-buscador" class="form-control form-control-sm rounded-pill px-3" placeholder="Buscar en tabla..." style="width: 200px;">
        </div>
    </div>
</div>

<!-- Tabla de Productos -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tabla-productos">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th style="width: 50px;">Img</th>
                    <th>Producto & SKU</th>
                    <th>Categoría Segmentada</th>
                    <th>Precio (USD / Bs)</th>
                    <th>Stock</th>
                    <th>Ficha Técnica</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($productos as $prod): ?>
                <?php 
                    $precioUsd = (float)$prod['precio'];
                    $precioBs = round($precioUsd * $tasaCambio, 2);
                    $hasSpecs = !empty($prod['especificaciones']);
                    $specsCount = 0;
                    if ($hasSpecs) {
                        $dec = json_decode($prod['especificaciones'], true);
                        $specsCount = is_array($dec) ? count($dec) : 0;
                    }
                ?>
                <tr class="<?= $prod['activo'] ? '' : 'table-light text-muted opacity-75' ?>">
                    <td>
                        <img src="<?= htmlspecialchars($prod['imagen_url'] ?: 'https://via.placeholder.com/60x60?text=No+Img') ?>" 
                             alt="<?= htmlspecialchars($prod['nombre']) ?>" 
                             class="rounded-3 border" 
                             style="width: 48px; height: 48px; object-fit: cover;">
                    </td>
                    <td>
                        <div class="fw-bold text-dark"><?= htmlspecialchars($prod['nombre']) ?></div>
                        <div class="d-flex align-items-center gap-2 mt-1">
                            <span class="badge bg-light text-secondary border font-monospace" style="font-size: 0.72rem;">
                                <?= htmlspecialchars($prod['sku'] ?: 'SIN-SKU') ?>
                            </span>
                            <?php if (!empty($prod['proveedor'])): ?>
                                <small class="text-muted d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                    <i data-lucide="truck" class="text-muted"></i><?= htmlspecialchars($prod['proveedor']) ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill">
                            <?= htmlspecialchars($prod['categoria'] ?? 'Sin categoría') ?>
                        </span>
                    </td>
                    <td>
                        <div class="fw-bold text-primary brand-font">Bs <?= number_format($precioBs, 2) ?></div>
                        <small class="text-muted" style="font-size: 0.75rem;">$<?= number_format($precioUsd, 2) ?> USD</small>
                    </td>
                    <td>
                        <?php if ($prod['stock'] <= 5): ?>
                            <span class="badge bg-warning-subtle text-warning border px-2 py-1 rounded-pill">
                                <i data-lucide="alert-triangle" class="me-1"></i> <?= $prod['stock'] ?> u.
                            </span>
                        <?php else: ?>
                            <span class="badge bg-success-subtle text-success border px-2 py-1 rounded-pill">
                                <?= $prod['stock'] ?> u.
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($hasSpecs): ?>
                            <span class="badge bg-info-subtle text-info border px-2 py-1 rounded-pill" title="Contiene especificaciones técnicas">
                                <i data-lucide="cpu" class="me-1"></i> <?= $specsCount ?> atributos
                            </span>
                        <?php else: ?>
                            <span class="badge bg-light text-muted border px-2 py-1 rounded-pill">
                                Sin ficha
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($prod['activo']): ?>
                            <span class="badge bg-success rounded-pill px-2 py-1">Activo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary rounded-pill px-2 py-1">Oculto</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            <a href="producto_form.php?id=<?= $prod['id'] ?>" class="btn btn-sm btn-outline-primary rounded-circle" title="Editar producto" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                <i data-lucide="edit-3"></i>
                            </a>
                            <a href="productos.php?toggle_activo=<?= $prod['id'] ?>" 
                               class="btn btn-sm btn-outline-<?= $prod['activo'] ? 'danger' : 'success' ?> rounded-circle" 
                               style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                               onclick="return confirm('<?= $prod['activo'] ? '¿Desactivar este producto? Dejará de mostrarse en la tienda.' : '¿Reactivar este producto? Volverá a estar visible.' ?>')"
                               title="<?= $prod['activo'] ? 'Desactivar' : 'Reactivar' ?>">
                                <i data-lucide="<?= $prod['activo'] ? 'eye-off' : 'eye' ?>"></i>
                            </a>
                            <?php if ($prod['activo']): ?>
                                <a href="../comparador.php?p1=<?= $prod['id'] ?>&categoria=<?= $prod['categoria_id'] ?>" target="_blank" class="btn btn-sm btn-outline-warning rounded-circle" title="Ver en comparador" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                    <i data-lucide="git-compare"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const buscador = document.getElementById('tabla-buscador');
    const filas = document.querySelectorAll('#tabla-productos tbody tr');

    if (buscador) {
        buscador.addEventListener('input', () => {
            const term = buscador.value.toLowerCase().trim();
            filas.forEach(fila => {
                const texto = fila.textContent.toLowerCase();
                fila.style.display = texto.includes(term) ? '' : 'none';
            });
        });
    }
});
</script>

<?php
$contenido = ob_get_clean();
$titulo = 'Gestión de Productos';
require 'layout.php';