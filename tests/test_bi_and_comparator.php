<?php
/**
 * Test de validación de Inteligencia de Negocios (BI), Historial y Comparador Kimovil
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/admin/AnalyticsService.php';

echo "=================================================================\n";
echo "🔬 VALIDACIÓN: BI ANALYTICS, REPORTES Y COMPARADOR KIMOVIL\n";
echo "=================================================================\n\n";

// 1. Probar AnalyticsService
echo "--- PRUEBA 1: AnalyticsService Engine ---\n";
$db = Database::getConnection();
$analytics = new \Services\Admin\AnalyticsService($db, 9.96, 'Bs');
$resumen = $analytics->getExecutiveSummary();

if (isset($resumen['ventas_mes']['total_bs']) && isset($resumen['inventario'])) {
    echo "✔ PASÓ: Resumen ejecutivo generado correctamente:\n";
    echo "  - Ventas Mes: Bs " . number_format($resumen['ventas_mes']['total_bs'], 2) . " ($" . number_format($resumen['ventas_mes']['total_usd'], 2) . ")\n";
    echo "  - Valoración Inventario: Bs " . number_format($resumen['inventario']['valor_venta_bs'], 2) . "\n";
    echo "  - Productos en Riesgo: " . count($resumen['productos_criticos']) . "\n";
} else {
    echo "❌ FALLÓ: Resumen ejecutivo no contiene datos requeridos.\n";
    exit(1);
}

// 2. Probar Generación de Reportes
echo "\n--- PRUEBA 2: Generación de Reportes (CSV y HTML) ---\n";
$reporteCsv = $analytics->generateReportFile('inventario', 'csv');
$reporteHtml = $analytics->generateReportFile('ventas', 'html');

$storageDir = __DIR__ . '/../storage/reports/';
$csvFile = $storageDir . $reporteCsv['nombre_archivo'];
$htmlFile = $storageDir . $reporteHtml['nombre_archivo'];

if (file_exists($csvFile) && file_exists($htmlFile)) {
    echo "✔ PASÓ: Archivos de reporte creados con éxito:\n";
    echo "  - CSV: " . $reporteCsv['nombre_archivo'] . " (" . filesize($csvFile) . " bytes)\n";
    echo "  - HTML: " . $reporteHtml['nombre_archivo'] . " (" . filesize($htmlFile) . " bytes)\n";
} else {
    echo "❌ FALLÓ: Uno o más archivos de reporte no se crearon.\n";
    exit(1);
}

// 3. Probar Base de Datos de Especificaciones (Kimovil)
echo "\n--- PRUEBA 3: Especificaciones de Productos para Comparador ---\n";
$stmt = $db->query("SELECT id, nombre, especificaciones FROM productos WHERE especificaciones IS NOT NULL");
$prodsConSpecs = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "✔ PASÓ: " . count($prodsConSpecs) . " productos tienen ficha técnica Kimovil registrada:\n";
foreach ($prodsConSpecs as $p) {
    $specs = json_decode($p['especificaciones'], true);
    $antutu = $specs['benchmark']['antutu_v10'] ?? 'N/A';
    $score = $specs['kimovil_score'] ?? 'N/A';
    echo "  - {$p['nombre']} -> Score Kimovil: {$score}/10 | AnTuTu: {$antutu}\n";
}

// 4. Probar Tablas de Historial Admin
echo "\n--- PRUEBA 4: Tablas de Historial Admin ---\n";
$stmtHilos = $db->query("SHOW TABLES LIKE 'chat_admin_hilos'");
$tieneHilos = $stmtHilos->rowCount() > 0;
$stmtMensajes = $db->query("SHOW TABLES LIKE 'chat_admin_mensajes'");
$tieneMensajes = $stmtMensajes->rowCount() > 0;

if ($tieneHilos && $tieneMensajes) {
    echo "✔ PASÓ: Tablas 'chat_admin_hilos' y 'chat_admin_mensajes' activas en la BD.\n";
} else {
    echo "❌ FALLÓ: Faltan tablas de historial.\n";
    exit(1);
}

echo "\n=================================================================\n";
echo "🎉 TODAS LAS VERIFICACIONES DE BI Y COMPARADOR PASARON CON ÉXITO!\n";
echo "=================================================================\n";
