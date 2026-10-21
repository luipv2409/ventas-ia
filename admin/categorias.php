<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/icons.php';

$pdo = Database::getConnection();

$mensaje = '';
$tipoMensaje = '';

// Soft toggle activo/inactivo
if (isset($_GET['toggle_activa']) && is_numeric($_GET['toggle_activa'])) {
    $idToggle = (int)$_GET['toggle_activa'];

    $stmt = $pdo->prepare("SELECT activa, nombre FROM categorias WHERE id = :id");
    $stmt->execute([':id' => $idToggle]);
    $cat = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cat) {
        $nuevoEstado = $cat['activa'] ? 0 : 1;
        $stmtUp = $pdo->prepare("UPDATE categorias SET activa = :act WHERE id = :id");
        $stmtUp->execute([':act' => $nuevoEstado, ':id' => $idToggle]);

        $mensaje = $nuevoEstado ? "Categoría '{$cat['nombre']}' activada correctamente." : "Categoría '{$cat['nombre']}' desactivada (oculta en tienda).";
        $tipoMensaje = 'success';
    } else {
        $mensaje = 'Categoría no encontrada.';
        $tipoMensaje = 'danger';
    }

    header("Location: categorias.php?msg=" . urlencode($mensaje) . "&tipo=" . $tipoMensaje);
    exit;
}

// Mensajes de redirección
if (isset($_GET['msg'])) {
    $mensaje = $_GET['msg'];
    $tipoMensaje = $_GET['tipo'] ?? 'info';
}

// Obtener todas las categorías con el conteo de productos
$categorias = $pdo->query("
    SELECT c.*, COUNT(p.id) as total_productos,
           SUM(CASE WHEN p.activo = 1 THEN 1 ELSE 0 END) as productos_activos
    FROM categorias c
    LEFT JOIN productos p ON c.id = p.categoria_id
    GROUP BY c.id
    ORDER BY c.id ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Métricas de categorías
$totalCats = count($categorias);
$catsActivas = count(array_filter($categorias, fn($c) => $c['activa'] == 1));
$catsConProductos = count(array_filter($categorias, fn($c) => $c['total_productos'] > 0));
$totalProductosGlobal = array_sum(array_column($categorias, 'total_productos'));

ob_start();
?>

<!-- Header de Gestión -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1 brand-font">🏷️ Gestión de Categorías Segmentadas</h2>
        <p class="text-muted small mb-0">
            Organiza tus líneas de negocio para permitir comparativas coherentes estilo Kimovil y navegación precisa.
           <div class="d-flex align-items-center gap-2">
        <a href="categoria_form.php" class="btn btn-primary rounded-pill px-4 py-2 d-inline-flex align-items-center gap-2 shadow-sm">
            <i data-lucide="plus"></i>
            <span>Nueva Categoría</span>
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

<!-- Tarjetas KPI de Categorías -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Total Categorías</span>
                    <h3 class="fw-bold mb-0 mt-1"><?= $totalCats ?></h3>
                    <small class="text-success"><i data-lucide="check-circle-2" class="me-1"></i> <?= $catsActivas ?> activas en tienda</small>
                </div>
                <div class="p-3 bg-primary-subtle text-primary rounded-4">
                    <i data-lucide="folder-tree" style="width:28px;height:28px;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Con Productos</span>
                    <h3 class="fw-bold mb-0 mt-1 text-info"><?= $catsConProductos ?></h3>
                    <small class="text-muted">Con inventario cargado</small>
                </div>
                <div class="p-3 bg-info-subtle text-info rounded-4">
                    <i data-lucide="boxes" style="width:28px;height:28px;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Total Artículos</span>
                    <h3 class="fw-bold mb-0 mt-1 text-success"><?= $totalProductosGlobal ?></h3>
                    <small class="text-muted">Distribuidos en catálogo</small>
                </div>
                <div class="p-3 bg-success-subtle text-success rounded-4">
                    <i data-lucide="tag" style="width:28px;height:28px;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="card border-0 shadow-sm rounded-4 p-3 bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <span class="text-muted small fw-semibold text-uppercase">Segmentación</span>
                    <h4 class="fw-bold mb-0 mt-1 text-warning">Organizada</h4>
                    <small class="text-muted">Por departamentos</small>
                </div>
                <div class="p-3 bg-warning-subtle text-warning rounded-4">
                    <i data-lucide="check-circle" style="width:28px;height:28px;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Buscador Rápido -->
<div class="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-white">
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i data-lucide="search" class="text-muted"></i>
            <span class="fw-semibold small text-muted">Filtrar categorías por nombre:</span>
        </div>
        <input type="text" id="tabla-buscador-cat" class="form-control form-control-sm rounded-pill px-3" placeholder="Buscar categoría..." style="width: 250px;">
    </div>
</div>

<!-- Tabla de Categorías -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tabla-categorias">
            <thead class="table-light text-muted small text-uppercase">
                <tr>
                    <th style="width: 60px;">Icono</th>
                    <th>Nombre de Categoría</th>
                    <th>Slug URL</th>
                    <th>Descripción Comercial & RAG</th>
                    <th>Productos</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($categorias as $cat): 
                $lucideCat = resolverIconoLucide($cat['icono'] ?? '', (int)$cat['id']);
            ?>
                <tr class="<?= $cat['activa'] ? '' : 'table-light text-muted opacity-75' ?>">
                    <td>
                        <div class="p-2 bg-primary-subtle text-primary rounded-3 text-center" style="width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;">
                            <i data-lucide="<?= htmlspecialchars($lucideCat) ?>"></i>
                        </div>
                    </td>
                    <td>
                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($cat['nombre']) ?></div>
                        <small class="text-muted font-monospace" style="font-size: 0.72rem;">ID: #<?= $cat['id'] ?></small>
                    </td>
                    <td>
                        <span class="badge bg-light text-secondary border font-monospace px-2 py-1">
                            <?= htmlspecialchars($cat['slug']) ?>
                        </span>
                    </td>
                    <td style="max-width: 320px;">
                        <small class="text-muted d-block" style="line-height: 1.4;">
                            <?= htmlspecialchars($cat['descripcion'] ?: 'Sin descripción detallada') ?>
                        </small>
                    </td>
                    <td>
                        <a href="productos.php?cat=<?= $cat['id'] ?>" class="btn btn-sm btn-light border rounded-pill px-3 py-1 text-decoration-none d-inline-flex align-items-center gap-1" title="Ver productos de esta categoría">
                            <i data-lucide="package" class="text-primary"></i>
                            <span class="fw-semibold"><?= (int)$cat['total_productos'] ?></span>
                            <span class="text-muted small">(<?= (int)$cat['productos_activos'] ?> act.)</span>
                        </a>
                    </td>
                    <td>
                        <?php if ($cat['activa']): ?>
                            <span class="badge bg-success rounded-pill px-2 py-1">Activa</span>
                        <?php else: ?>
                            <span class="badge bg-secondary rounded-pill px-2 py-1">Oculta</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-1">
                            <a href="producto_form.php?categoria_id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-success rounded-circle" title="Crear producto en esta categoría" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                <i data-lucide="plus"></i>
                            </a>
                            <a href="categoria_form.php?id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-primary rounded-circle" title="Editar categoría" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                <i data-lucide="edit-3"></i>
                            </a>
                            <a href="categorias.php?toggle_activa=<?= $cat['id'] ?>" 
                               class="btn btn-sm btn-outline-<?= $cat['activa'] ? 'danger' : 'success' ?> rounded-circle" 
                               style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                               onclick="return confirm('<?= $cat['activa'] ? '¿Desactivar esta categoría? No se mostrará en los filtros de la tienda.' : '¿Reactivar esta categoría? Volverá a estar visible.' ?>')"
                               title="<?= $cat['activa'] ? 'Desactivar' : 'Reactivar' ?>">
                                <i data-lucide="<?= $cat['activa'] ? 'eye-off' : 'eye' ?>"></i>
                            </a>
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
    const buscador = document.getElementById('tabla-buscador-cat');
    const filas = document.querySelectorAll('#tabla-categorias tbody tr');

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
$titulo = 'Gestión de Categorías';
require 'layout.php';
