<?php
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getConnection();

// Configuración de moneda
$stmtConf = $pdo->query("SELECT clave, valor FROM configuracion WHERE clave IN ('moneda_simbolo', 'tasa_cambio_usd')");
$config = $stmtConf ? ($stmtConf->fetchAll(PDO::FETCH_KEY_PAIR) ?: []) : [];
$simboloMoneda = $config['moneda_simbolo'] ?? 'Bs';
$tasaCambio    = (float)($config['tasa_cambio_usd'] ?? 1.0);
if ($tasaCambio <= 0) $tasaCambio = 1.0;

// 1. Productos con stock bajo (< 5)
$stmtLowStock = $pdo->query("SELECT p.nombre, p.stock, p.id, p.precio FROM productos p WHERE p.stock < 5 AND p.stock > 0 ORDER BY p.stock ASC");
$lowStock = $stmtLowStock->fetchAll(PDO::FETCH_ASSOC);

// 2. Ventas del mes
$stmtVentas = $pdo->query("
    SELECT COUNT(*) as total_pedidos, COALESCE(SUM(total), 0) as ingresos, COALESCE(AVG(total), 0) as ticket_promedio 
    FROM pedidos 
    WHERE MONTH(creado_en) = MONTH(CURRENT_DATE()) AND YEAR(creado_en) = YEAR(CURRENT_DATE())
");
$ventasMes = $stmtVentas->fetch(PDO::FETCH_ASSOC) ?: ['total_pedidos' => 0, 'ingresos' => 0, 'ticket_promedio' => 0];
$ingresosBs = round(((float)$ventasMes['ingresos']) * $tasaCambio, 2);
$ticketBs = round(((float)$ventasMes['ticket_promedio']) * $tasaCambio, 2);

// 3. Inventario Valorizado
$stmtInv = $pdo->query("SELECT COUNT(*) as total_skus, COALESCE(SUM(stock * precio), 0) as valor_usd, COALESCE(SUM(stock), 0) as unidades FROM productos WHERE activo = 1");
$inv = $stmtInv->fetch(PDO::FETCH_ASSOC) ?: ['total_skus' => 0, 'valor_usd' => 0, 'unidades' => 0];
$invValorBs = round(((float)$inv['valor_usd']) * $tasaCambio, 2);

// 4. Últimos pedidos
$ultimosPedidos = $pdo->query("
    SELECT p.id, c.nombre as cliente, p.total, p.estado, p.creado_en 
    FROM pedidos p 
    JOIN clientes c ON p.cliente_id = c.id 
    ORDER BY p.creado_en DESC 
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold brand-font mb-1">Panel de Control Ejecutivo</h2>
        <p class="text-muted small mb-0">Resumen operativo, métricas financieras e inventario en tiempo real.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="chat_admin.php" class="btn btn-primary rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
            <i class="fas fa-brain"></i> <span>Consultar a Jarvis BI</span>
        </a>
    </div>
</div>

<!-- Grid de KPIs Ejecutivos -->
<div class="row g-3 mb-4">
    <!-- Ventas del Mes -->
    <div class="col-xl-4 col-md-6">
        <div class="kpi-card h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Ventas del Mes</span>
                    <h3 class="fw-bold brand-font text-dark mb-0 mt-1"><?= $simboloMoneda ?> <?= number_format($ingresosBs, 2) ?></h3>
                    <small class="text-muted">$<?= number_format((float)$ventasMes['ingresos'], 2) ?> USD base</small>
                </div>
                <div class="kpi-icon-box kpi-icon-emerald">
                    <i data-lucide="wallet"></i>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-3 border-top small text-muted">
                <span><i data-lucide="shopping-bag" class="me-1 text-success"></i> <?= $ventasMes['total_pedidos'] ?> pedidos</span>
                <span>Ticket prom: <strong><?= $simboloMoneda ?> <?= number_format($ticketBs, 2) ?></strong></span>
            </div>
        </div>
    </div>

    <!-- Inventario Valorizado -->
    <div class="col-xl-4 col-md-6">
        <div class="kpi-card h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Inventario Valorizado</span>
                    <h3 class="fw-bold brand-font text-dark mb-0 mt-1"><?= $simboloMoneda ?> <?= number_format($invValorBs, 2) ?></h3>
                    <small class="text-muted">$<?= number_format((float)$inv['valor_usd'], 2) ?> USD en almacén</small>
                </div>
                <div class="kpi-icon-box kpi-icon-indigo">
                    <i data-lucide="boxes"></i>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-3 border-top small text-muted">
                <span><i data-lucide="package" class="me-1 text-primary"></i> <?= $inv['unidades'] ?> unidades totales</span>
                <span><?= $inv['total_skus'] ?> SKUs activos</span>
            </div>
        </div>
    </div>

    <!-- Stock Crítico -->
    <div class="col-xl-4 col-md-12">
        <div class="kpi-card h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Riesgo de Quiebre de Stock</span>
                    <h3 class="fw-bold brand-font text-<?= count($lowStock) > 0 ? 'warning' : 'success' ?> mb-0 mt-1">
                        <?= count($lowStock) ?> SKUs
                    </h3>
                    <small class="text-muted">Productos con menos de 5 unidades</small>
                </div>
                <div class="kpi-icon-box kpi-icon-amber">
                    <i data-lucide="alert-triangle"></i>
                </div>
            </div>
            <div class="pt-2 border-top small">
                <?php if (empty($lowStock)): ?>
                    <span class="text-success"><i data-lucide="check-circle-2" class="me-1"></i> Stock saludable en todas las líneas.</span>
                <?php else: ?>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-truncate text-secondary" style="max-width: 200px;">
                            <?= htmlspecialchars($lowStock[0]['nombre']) ?> (<?= $lowStock[0]['stock'] ?> u.)
                        </span>
                        <a href="alertas.php" class="text-decoration-none fw-semibold small text-primary">Ver todos &rarr;</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Banner de Métricas & Consultas BI -->
<div class="card border-0 shadow-sm p-4 mb-4" style="border-radius: 16px; background: linear-gradient(135deg, #1e1b4b, #312e81); color: #ffffff;">
    <div class="row align-items-center">
        <div class="col-lg-8 mb-3 mb-lg-0">
            <div class="d-inline-flex align-items-center gap-2 bg-white bg-opacity-10 px-3 py-1 rounded-pill mb-2 border border-white border-opacity-15 small">
                <i data-lucide="bar-chart-2" class="text-warning"></i>
                <span>Métricas & Reportes</span>
            </div>
            <h4 class="fw-bold mb-1">Análisis de ventas e inventario</h4>
            <p class="text-light text-opacity-75 small mb-0">Consulta rotación de inventario, márgenes y pedidos, o exporta reportes en formato CSV/Excel.</p>
        </div>
        <div class="col-lg-4 text-lg-end">
            <a href="chat_admin.php" class="btn btn-warning rounded-pill px-4 py-2 fw-bold shadow-sm d-inline-flex align-items-center gap-2">
                <i data-lucide="message-square"></i> <span>Consultas y Reportes</span>
            </a>
        </div>
    </div>
</div>

<!-- Tabla de Últimos Pedidos -->
<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-white border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold brand-font mb-0"><i data-lucide="clock" class="text-primary me-2"></i> Últimos Pedidos</h5>
        <a href="pedidos.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Ver todos los pedidos</a>
    </div>
    <div class="table-responsive">
        <table class="table table-saas mb-0">
            <thead>
                <tr>
                    <th>ID Pedido</th>
                    <th>Cliente</th>
                    <th>Total Oficial</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th class="text-end">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ultimosPedidos)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Aún no se han registrado pedidos en el sistema.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ultimosPedidos as $ped): 
                        $totalPedBs = round(((float)$ped['total']) * $tasaCambio, 2);
                        $est = strtolower($ped['estado']);
                    ?>
                        <tr>
                            <td class="fw-bold text-dark">#<?= $ped['id'] ?></td>
                            <td><?= htmlspecialchars($ped['cliente']) ?></td>
                            <td>
                                <span class="fw-bold text-primary"><?= $simboloMoneda ?> <?= number_format($totalPedBs, 2) ?></span>
                                <small class="text-muted d-block" style="font-size: 0.72rem;">$<?= number_format((float)$ped['total'], 2) ?> USD</small>
                            </td>
                            <td>
                                <span class="badge-status badge-status-<?= $est ?>">
                                    <?= htmlspecialchars($ped['estado']) ?>
                                </span>
                            </td>
                            <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($ped['creado_en'])) ?></td>
                            <td class="text-end">
                                <a href="detalle_pedido.php?id=<?= $ped['id'] ?>" class="btn btn-sm btn-light border rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1">
                                    <i data-lucide="eye" class="text-muted"></i> Ver
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$contenido = ob_get_clean();
$titulo = 'Dashboard Ejecutivo';
require 'layout.php';