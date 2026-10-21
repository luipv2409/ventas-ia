<?php
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getConnection();

// Obtener umbral configurable
$umbral = 5;
$stmtUmbral = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'umbral_stock'");
if ($row = $stmtUmbral->fetch()) {
    $umbral = (int)$row['valor'];
}

// Productos con stock bajo (por debajo del umbral)
$stmt = $pdo->prepare("
    SELECT p.nombre, p.stock, p.id, c.nombre AS categoria, 
           (CASE WHEN p.stock = 0 THEN 'agotado' 
                 WHEN p.stock < :critico THEN 'critico' 
                 ELSE 'bajo' END) AS nivel
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    WHERE p.stock < :umbral
    ORDER BY p.stock ASC
");
$stmt->execute([':umbral' => $umbral, ':critico' => max(1, intval($umbral/2))]);
$alertas = $stmt->fetchAll();

// Resumen para tarjetas
$totalBajos = count($alertas);
$totalAgotados = count(array_filter($alertas, fn($a) => $a['stock'] == 0));
$totalCriticos = count(array_filter($alertas, fn($a) => $a['nivel'] === 'critico'));

ob_start();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0 d-flex align-items-center gap-2">
        <i data-lucide="alert-triangle" class="text-warning"></i>
        <span>Alertas de Stock</span>
    </h2>
    <div>
        <a href="configuracion.php" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1">
            <i data-lucide="settings" style="width:16px;height:16px;"></i>
            <span>Umbral actual: <?= $umbral ?> unidades</span>
        </a>
    </div>
</div>

<!-- Tarjetas de resumen -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-left-warning shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title text-warning"><?= $totalBajos ?></h5>
                        <p class="card-text text-muted mb-0">Productos bajos</p>
                    </div>
                    <i data-lucide="alert-triangle" class="text-warning" style="width: 2.2rem; height: 2.2rem;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-left-danger shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title text-danger"><?= $totalAgotados ?></h5>
                        <p class="card-text text-muted mb-0">Agotados</p>
                    </div>
                    <i data-lucide="x-circle" class="text-danger" style="width: 2.2rem; height: 2.2rem;"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-left-danger shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="card-title text-danger"><?= $totalCriticos ?></h5>
                        <p class="card-text text-muted mb-0">Críticos (< <?= max(1, intval($umbral/2)) ?>)</p>
                    </div>
                    <i data-lucide="alert-circle" class="text-danger" style="width: 2.2rem; height: 2.2rem;"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla interactiva -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Listado de productos en alerta</h5>
        <div>
            <button type="button" class="btn btn-outline-success btn-sm d-inline-flex align-items-center gap-1" id="btn-exportar">
                <i data-lucide="download" style="width:16px;height:16px;"></i>
                <span>Exportar Excel</span>
            </button>
        </div>
    </div>
    <div class="card-body p-0">
        <table id="tabla-alertas" class="table table-striped table-hover mb-0" style="width:100%">
            <thead class="table-light">
                <tr>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th>Stock</th>
                    <th>Nivel</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alertas as $prod): ?>
                <tr>
                    <td><?= htmlspecialchars($prod['nombre']) ?></td>
                    <td><?= htmlspecialchars($prod['categoria'] ?? 'Sin categoría') ?></td>
                    <td><?= $prod['stock'] ?></td>
                    <td>
                        <?php
                        $badgeClass = [
                            'bajo' => 'warning',
                            'critico' => 'danger',
                            'agotado' => 'dark'
                        ];
                        $nivelTexto = [
                            'bajo' => 'Bajo',
                            'critico' => 'Crítico',
                            'agotado' => 'Agotado'
                        ];
                        ?>
                        <span class="badge bg-<?= $badgeClass[$prod['nivel']] ?>">
                            <?= $nivelTexto[$prod['nivel']] ?>
                        </span>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="producto_form.php?id=<?= $prod['id'] ?>" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" title="Editar producto">
                                <i data-lucide="edit-3" style="width:14px;height:14px;"></i>
                                <span>Editar</span>
                            </a>
                            <button class="btn btn-sm btn-outline-info notificar-proveedor d-inline-flex align-items-center justify-content-center" 
                                    data-producto="<?= htmlspecialchars($prod['nombre']) ?>"
                                    title="Notificar a proveedor por chat">
                                <i data-lucide="message-square" style="width:14px;height:14px;"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- DataTables CSS/JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="../assets/js/datatables-es.js"></script>
<script>
$(document).ready(function() {
    $('#tabla-alertas').DataTable({
        language: typeof DATATABLES_ESPANOL !== 'undefined' ? DATATABLES_ESPANOL : {},
        order: [[2, 'asc']], // ordenar por stock ascendente
        pageLength: 10,
        responsive: true,
        dom: '<"row p-3"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>rt<"row p-3"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6"p>>',
        drawCallback: function() {
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                lucide.createIcons();
            }
        }
    });

    // Botón exportar
    $('#btn-exportar').on('click', function() {
        window.location.href = '../api/reportes.php?tipo=inventario&formato=csv';
    });

    // Notificar a proveedor (envía al chat administrativo)
    $('#tabla-alertas').on('click', '.notificar-proveedor', function() {
        const producto = $(this).data('producto');
        const mensaje = `Notificar a proveedor sobre bajo stock de: ${producto}`;
        window.open(`chat_admin.php?mensaje=${encodeURIComponent(mensaje)}`, '_self');
    });
});
</script>

<?php
$contenido = ob_get_clean();
$titulo = 'Alertas de Stock';
require 'layout.php';