<?php
require_once __DIR__ . '/../config/database.php';
$pdo = Database::getConnection();

// Ventas por mes (últimos 6 meses)
$stmt = $pdo->query("
    SELECT DATE_FORMAT(creado_en, '%Y-%m') AS mes, SUM(total) AS total, COUNT(*) AS num_pedidos
    FROM pedidos
    WHERE creado_en >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
    GROUP BY mes
    ORDER BY mes ASC
");
$ventasMensuales = $stmt->fetchAll();

// Productos más vendidos (top 5)
$stmt = $pdo->query("
    SELECT p.nombre, SUM(dp.cantidad) AS total_vendido
    FROM detalle_pedidos dp
    JOIN productos p ON dp.producto_id = p.id
    GROUP BY p.id
    ORDER BY total_vendido DESC
    LIMIT 5
");
$productosTop = $stmt->fetchAll();

// Pedidos por estado
$stmt = $pdo->query("
    SELECT estado, COUNT(*) AS total
    FROM pedidos
    GROUP BY estado
");
$pedidosEstado = $stmt->fetchAll();

// Ingresos diarios últimos 7 días
$stmt = $pdo->query("
    SELECT DATE(creado_en) AS fecha, SUM(total) AS total
    FROM pedidos
    WHERE creado_en >= DATE_SUB(CURRENT_DATE(), INTERVAL 7 DAY)
    GROUP BY fecha
    ORDER BY fecha ASC
");
$ingresosDiarios = $stmt->fetchAll();

// Pasamos los datos a JavaScript
$datosJSON = json_encode([
    'ventasMensuales' => $ventasMensuales,
    'productosTop' => $productosTop,
    'pedidosEstado' => $pedidosEstado,
    'ingresosDiarios' => $ingresosDiarios,
], JSON_UNESCAPED_UNICODE);

ob_start();
?>
<h2>📈 Métricas de Ventas</h2>
<div class="row mt-4">
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h5>Ventas Totales por Mes (últimos 6 meses)</h5></div>
            <div class="card-body"><canvas id="ventasMensualesChart" height="300"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h5>Productos Más Vendidos (Top 5)</h5></div>
            <div class="card-body"><canvas id="productosTopChart" height="300"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h5>Pedidos por Estado</h5></div>
            <div class="card-body"><canvas id="pedidosEstadoChart" height="300"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><h5>Ingresos Diarios (últimos 7 días)</h5></div>
            <div class="card-body"><canvas id="ingresosDiariosChart" height="300"></canvas></div>
        </div>
    </div>
</div>
<?php
$contenido = ob_get_clean();
$titulo = 'Métricas';
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>';
$extraScripts = "
<script>
const datos = $datosJSON;
const colores = ['#007bff','#28a745','#ffc107','#dc3545','#17a2b8','#6f42c1'];

new Chart(document.getElementById('ventasMensualesChart'), {
    type: 'bar',
    data: {
        labels: datos.ventasMensuales.map(v => v.mes),
        datasets: [{
            label: 'Total vendido (\$)',
            data: datos.ventasMensuales.map(v => v.total),
            backgroundColor: '#007bff',
            borderRadius: 8
        }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('productosTopChart'), {
    type: 'pie',
    data: {
        labels: datos.productosTop.map(p => p.nombre),
        datasets: [{
            data: datos.productosTop.map(p => p.total_vendido),
            backgroundColor: colores.slice(0, datos.productosTop.length)
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('pedidosEstadoChart'), {
    type: 'doughnut',
    data: {
        labels: datos.pedidosEstado.map(p => p.estado),
        datasets: [{
            data: datos.pedidosEstado.map(p => p.total),
            backgroundColor: colores.slice(0, datos.pedidosEstado.length)
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('ingresosDiariosChart'), {
    type: 'line',
    data: {
        labels: datos.ingresosDiarios.map(i => i.fecha),
        datasets: [{
            label: 'Ingresos (\$)',
            data: datos.ingresosDiarios.map(i => i.total),
            borderColor: '#28a745',
            backgroundColor: 'rgba(40,167,69,0.1)',
            fill: true,
            tension: 0.3,
            pointRadius: 5
        }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});
</script>";
require 'layout.php';