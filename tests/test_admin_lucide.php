<?php
declare(strict_types=1);

session_start();
$_SESSION['admin_user'] = 'Administrador';
$_SESSION['admin_id'] = 1;

chdir(__DIR__ . '/../admin');
ob_start();
require __DIR__ . '/../admin/dashboard.php';
$html = ob_get_clean();

echo "=== TEST: ADMIN DASHBOARD LUCIDE ICONS & HEADER ===" . PHP_EOL;
echo " [+] Dashboard renderizado: " . strlen($html) . " bytes." . PHP_EOL;

$iconosEsperados = [
    'layout-dashboard' => 'Sidebar Dashboard',
    'boxes'            => 'Sidebar Catálogo / KPI Inventario',
    'plus-circle'      => 'Sidebar Nuevo Producto',
    'folder-tree'      => 'Sidebar Categorías',
    'receipt'          => 'Sidebar Pedidos',
    'truck'            => 'Sidebar Proveedores',
    'brain'            => 'Sidebar Jarvis BI',
    'line-chart'       => 'Sidebar Métricas',
    'history'          => 'Sidebar Histórico',
    'alert-triangle'   => 'Sidebar Alertas / KPI Stock',
    'settings'         => 'Sidebar Configuración',
    'wallet'           => 'KPI Ventas del Mes',
    'clock'            => 'Tabla Últimos Pedidos',
    'eye'              => 'Acción Ver Pedido',
    'lucide.min.js'    => 'Script Lucide cargado',
];

$errores = 0;
foreach ($iconosEsperados as $needle => $desc) {
    if (strpos($html, $needle) !== false) {
        echo "  ✔ Icono/Recurso '{$needle}' presente ({$desc})." . PHP_EOL;
    } else {
        echo "  ❌ FALTA: '{$needle}' ({$desc})." . PHP_EOL;
        $errores++;
    }
}

if ($errores === 0) {
    echo "✅ TEST ADMIN LUCIDE PASÓ EXITOSAMENTE." . PHP_EOL;
    exit(0);
} else {
    echo "❌ TEST ADMIN LUCIDE FALLÓ CON {$errores} ERRORES." . PHP_EOL;
    exit(1);
}
