<?php
declare(strict_types=1);

namespace Services\Admin;

use PDO;

/**
 * Servicio de Inteligencia de Negocios y Analítica Ejecutiva (BI).
 * Provee métricas de rotación, márgenes, segmentación de clientes,
 * alertas estratégicas y generación de reportes descargables para el Admin.
 */
class AnalyticsService
{
    private PDO $pdo;
    private float $tasaCambio;
    private string $simbolo;

    public function __construct(PDO $pdo, float $tasaCambio = 1.0, string $simbolo = 'Bs')
    {
        $this->pdo = $pdo;
        $this->tasaCambio = $tasaCambio > 0 ? $tasaCambio : 1.0;
        $this->simbolo = $simbolo;
    }

    /**
     * Resumen ejecutivo integral de KPIs
     */
    public function getExecutiveSummary(): array
    {
        // 1. Ventas del día, semana y mes
        $hoy = $this->pdo->query("
            SELECT COUNT(*) as pedidos, COALESCE(SUM(total), 0) as total 
            FROM pedidos WHERE DATE(creado_en) = CURDATE()
        ")->fetch(PDO::FETCH_ASSOC) ?: ['pedidos' => 0, 'total' => 0];

        $mes = $this->pdo->query("
            SELECT COUNT(*) as pedidos, COALESCE(SUM(total), 0) as total,
                   COALESCE(AVG(total), 0) as ticket_promedio
            FROM pedidos 
            WHERE MONTH(creado_en) = MONTH(CURDATE()) AND YEAR(creado_en) = YEAR(CURDATE())
        ")->fetch(PDO::FETCH_ASSOC) ?: ['pedidos' => 0, 'total' => 0, 'ticket_promedio' => 0];

        // 2. Inventario valorizado
        $inv = $this->pdo->query("
            SELECT COUNT(*) as total_skus, 
                   SUM(stock) as unidades_totales,
                   SUM(stock * precio) as valor_usd_base
            FROM productos WHERE activo = 1
        ")->fetch(PDO::FETCH_ASSOC) ?: ['total_skus' => 0, 'unidades_totales' => 0, 'valor_usd_base' => 0];

        $valorBaseUsd = (float)$inv['valor_usd_base'];
        $valorVentaBs = round($valorBaseUsd * $this->tasaCambio, 2);

        // 3. Productos en riesgo de agotamiento (stock < 5)
        $stockCritico = $this->pdo->query("
            SELECT id, nombre, stock, precio 
            FROM productos 
            WHERE activo = 1 AND stock < 5 
            ORDER BY stock ASC
        ")->fetchAll(PDO::FETCH_ASSOC);

        // 4. Velocidad de rotación estimada (unidades vendidas en últimos 30 días)
        $rotacion = $this->pdo->query("
            SELECT p.id, p.nombre, p.stock, COALESCE(SUM(dp.cantidad), 0) as vendidas_mes
            FROM productos p
            LEFT JOIN detalle_pedidos dp ON p.id = dp.producto_id
            LEFT JOIN pedidos ped ON dp.pedido_id = ped.id AND ped.creado_en >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            WHERE p.activo = 1
            GROUP BY p.id
            ORDER BY vendidas_mes DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        return [
            'ventas_hoy' => [
                'pedidos'    => (int)$hoy['pedidos'],
                'total_usd'  => (float)$hoy['total'],
                'total_bs'   => round(((float)$hoy['total']) * $this->tasaCambio, 2),
            ],
            'ventas_mes' => [
                'pedidos'         => (int)$mes['pedidos'],
                'total_usd'       => (float)$mes['total'],
                'total_bs'        => round(((float)$mes['total']) * $this->tasaCambio, 2),
                'ticket_promedio_bs' => round(((float)$mes['ticket_promedio']) * $this->tasaCambio, 2),
            ],
            'inventario' => [
                'skus_activos'     => (int)$inv['total_skus'],
                'unidades_totales' => (int)$inv['unidades_totales'],
                'valor_base_usd'   => $valorBaseUsd,
                'valor_venta_bs'   => $valorVentaBs,
            ],
            'productos_criticos' => $stockCritico,
            'top_rotacion'       => $rotacion,
            'tasa_cambio'        => $this->tasaCambio,
            'moneda'             => $this->simbolo,
        ];
    }

    /**
     * Genera sugerencias y recomendaciones estratégicas automáticas para el Administrador
     */
    public function getStrategicAdvice(): array
    {
        $consejos = [];
        $resumen = $this->getExecutiveSummary();

        // Regla 1: Alerta de reposición inmediata
        if (!empty($resumen['productos_criticos'])) {
            $nombres = array_map(fn($p) => "{$p['nombre']} ({$p['stock']} u.)", array_slice($resumen['productos_criticos'], 0, 3));
            $consejos[] = [
                'prioridad' => 'ALTA',
                'icono'     => '⚠️',
                'titulo'    => 'Reposición de Stock Urgente',
                'detalle'   => 'Los siguientes productos están a punto de quebrar stock: ' . implode(', ', $nombres) . '. Se recomienda emitir orden de compra a proveedores.'
            ];
        }

        // Regla 2: Oportunidad de Upselling / Promoción
        if (!empty($resumen['top_rotacion'])) {
            $estrella = $resumen['top_rotacion'][0];
            if ((int)$estrella['vendidas_mes'] > 0) {
                $consejos[] = [
                    'prioridad' => 'MEDIA',
                    'icono'     => '🚀',
                    'titulo'    => 'Producto Estrella Identificado',
                    'detalle'   => "El producto '{$estrella['nombre']}' lidera las ventas del mes con {$estrella['vendidas_mes']} unidades vendidas. Sugerencia: Crear combos con accesorios para elevar el ticket promedio."
                ];
            }
        }

        // Regla 3: Tasa de cambio y salud del inventario
        $valorBs = number_format($resumen['inventario']['valor_venta_bs'], 2);
        $consejos[] = [
            'prioridad' => 'INFO',
            'icono'     => '💡',
            'titulo'    => 'Valorización del Inventario',
            'detalle'   => "Tu inventario actual está valorizado en {$this->simbolo} {$valorBs} ({$resumen['inventario']['unidades_totales']} unidades totales). Mantén un margen de seguridad frente a fluctuaciones de divisas."
        ];

        return $consejos;
    }

    /**
     * Genera un reporte formateado y lo guarda en storage/reports/ para descarga
     * Retorna la ruta y URL para que Jarvis le entregue el botón de descarga al admin.
     */
    public function generateReportFile(string $tipo = 'inventario', string $formato = 'csv'): array
    {
        $tipo = !empty($tipo) ? strtolower(trim($tipo)) : 'inventario';
        $formato = !empty($formato) ? strtolower(trim($formato)) : 'csv';
        if ($formato !== 'csv' && $formato !== 'html') {
            $formato = 'csv';
        }

        $reportsDir = __DIR__ . '/../../storage/reports';
        if (!is_dir($reportsDir)) {
            @mkdir($reportsDir, 0777, true);
        }
        @chmod($reportsDir, 0777);

        $timestamp = date('Ymd_His');
        $filename = "reporte_{$tipo}_{$timestamp}." . ($formato === 'csv' ? 'csv' : 'html');
        $filepath = $reportsDir . '/' . $filename;
        $urlDescarga = "../api/descargar_reporte.php?archivo=" . urlencode($filename);

        if ($formato === 'csv') {
            $fp = @fopen($filepath, 'w');
            if ($fp === false) {
                // Reintento tras chmod
                @chmod($reportsDir, 0777);
                $fp = @fopen($filepath, 'w');
            }
            if ($fp === false) {
                throw new \RuntimeException("No se pudo escribir el archivo en el servidor. Verifica permisos de 'storage/reports'.");
            }
            // BOM UTF-8 para Excel
            fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));

            if ($tipo === 'inventario') {
                fputcsv($fp, ['ID', 'Producto', 'Categoría', 'Precio USD Base', "Precio Público ({$this->simbolo})", 'Stock', 'SKU', "Valorizado ({$this->simbolo})"]);
                $stmt = $this->pdo->query("
                    SELECT p.id, p.nombre, c.nombre as categoria, p.precio, p.stock, p.sku
                    FROM productos p
                    LEFT JOIN categorias c ON p.categoria_id = c.id
                    WHERE p.activo = 1
                    ORDER BY p.stock ASC
                ");
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $pBs = round(((float)$r['precio']) * $this->tasaCambio, 2);
                    $valTotalBs = round($pBs * (int)$r['stock'], 2);
                    fputcsv($fp, [$r['id'], $r['nombre'], $r['categoria'], $r['precio'], $pBs, $r['stock'], $r['sku'], $valTotalBs]);
                }
            } else {
                // Reporte Ejecutivo de Ventas y Pedidos
                fputcsv($fp, ['ID Pedido', 'Cliente', 'Estado', 'Fecha', 'Total USD', "Total ({$this->simbolo})"]);
                $stmt = $this->pdo->query("
                    SELECT p.id, c.nombre as cliente, p.estado, p.creado_en, p.total
                    FROM pedidos p
                    JOIN clientes c ON p.cliente_id = c.id
                    ORDER BY p.creado_en DESC
                ");
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $tBs = round(((float)$r['total']) * $this->tasaCambio, 2);
                    fputcsv($fp, [$r['id'], $r['cliente'], $r['estado'], $r['creado_en'], $r['total'], $tBs]);
                }
            }
            fclose($fp);
        } else {
            // Formato HTML imprimible ejecutivo
            $resumen = $this->getExecutiveSummary();
            $html = "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Reporte Ejecutivo</title>";
            $html .= "<style>body{font-family:sans-serif;padding:30px;color:#1e293b;}table{width:100%;border-collapse:collapse;margin-top:20px;}th,td{border:1px solid #cbd5e1;padding:10px;text-align:left;}th{background:#f1f5f9;}</style></head><body>";
            $html .= "<h2>📊 Reporte Ejecutivo de Gestión — " . date('d/m/Y H:i') . "</h2>";
            $html .= "<p><strong>Tasa de cambio:</strong> 1 USD = {$this->tasaCambio} {$this->simbolo}</p>";
            $html .= "<p><strong>Ventas del mes:</strong> {$this->simbolo} {$resumen['ventas_mes']['total_bs']} ({$resumen['ventas_mes']['pedidos']} pedidos)</p>";
            $html .= "<p><strong>Inventario total valorizado:</strong> {$this->simbolo} " . number_format($resumen['inventario']['valor_venta_bs'], 2) . "</p>";
            $html .= "<h3>Inventario Actual</h3><table><tr><th>Producto</th><th>Stock</th><th>Precio Base USD</th><th>Precio Oficial ({$this->simbolo})</th></tr>";
            
            $stmt = $this->pdo->query("SELECT nombre, stock, precio FROM productos WHERE activo = 1 ORDER BY stock ASC");
            while ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $bs = round(((float)$p['precio']) * $this->tasaCambio, 2);
                $html .= "<tr><td>{$p['nombre']}</td><td>{$p['stock']}</td><td>\${$p['precio']}</td><td>{$this->simbolo} {$bs}</td></tr>";
            }
            $html .= "</table></body></html>";
            file_put_contents($filepath, $html);
        }

        return [
            'nombre_archivo' => $filename,
            'url'            => $urlDescarga,
            'formato'        => strtoupper($formato),
            'tipo'           => $tipo,
            'fecha'          => date('d/m/Y H:i')
        ];
    }
}
