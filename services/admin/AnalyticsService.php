<?php
declare(strict_types=1);

namespace Services\Admin;

use PDO;
use Services\Security\SecurityKernel;

/**
 * Servicio de Inteligencia Institucional y Analítica de Admisiones ULAT.
 */
class AnalyticsService
{
    private const TIPOS_PERMITIDOS = ['prospectos', 'conversaciones', 'ejecutivo'];
    private const FORMATOS_PERMITIDOS = ['csv', 'html'];

    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getExecutiveSummary(): array
    {
        $leads = $this->pdo->query("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END), 0) AS hoy,
                COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END), 0) AS mes,
                COALESCE(SUM(CASE WHEN estado = 'nuevo' THEN 1 ELSE 0 END), 0) AS pendientes,
                COALESCE(SUM(CASE WHEN estado = 'matriculado' THEN 1 ELSE 0 END), 0) AS matriculados
            FROM admisiones_prospectos
        ")->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'hoy' => 0, 'mes' => 0, 'pendientes' => 0, 'matriculados' => 0];

        $chats = $this->pdo->query("
            SELECT
                COUNT(*) AS total_mes,
                ROUND(COALESCE(AVG(latencia_ms), 0)) AS latencia_media_ms,
                COALESCE(SUM(tokens_usados), 0) AS tokens_mes,
                COALESCE(SUM(CASE WHEN rag_score_max >= 0.25 THEN 1 ELSE 0 END), 0) AS rag_hits
            FROM chat_logs
            WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ")->fetch(PDO::FETCH_ASSOC) ?: ['total_mes' => 0, 'latencia_media_ms' => 0, 'tokens_mes' => 0, 'rag_hits' => 0];

        $topCarreras = $this->pdo->query("
            SELECT carrera_interes, COUNT(*) AS cantidad
            FROM admisiones_prospectos
            GROUP BY carrera_interes
            ORDER BY cantidad DESC
            LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);

        $totalChatsMes = (int)$chats['total_mes'];
        $ragRate = $totalChatsMes > 0 ? round(((int)$chats['rag_hits'] / $totalChatsMes) * 100, 1) : 0.0;

        return [
            'prospectos' => [
                'total'        => (int)$leads['total'],
                'hoy'          => (int)$leads['hoy'],
                'mes'          => (int)$leads['mes'],
                'pendientes'   => (int)$leads['pendientes'],
                'matriculados' => (int)$leads['matriculados'],
            ],
            'chatbot' => [
                'consultas_mes'     => $totalChatsMes,
                'latencia_media_ms' => (int)$chats['latencia_media_ms'],
                'tokens_mes'        => (int)$chats['tokens_mes'],
                'cobertura_rag_pct' => $ragRate,
            ],
            'top_carreras' => $topCarreras,
        ];
    }

    public function getStrategicAdvice(): array
    {
        $consejos = [];
        $res = $this->getExecutiveSummary();

        if ($res['prospectos']['pendientes'] > 0) {
            $consejos[] = [
                'prioridad' => 'ALTA',
                'icono'     => '📞',
                'titulo'    => 'Seguimiento de Postulantes Pendientes',
                'detalle'   => "Existen {$res['prospectos']['pendientes']} postulante(s) en estado 'nuevo' esperando contacto de Admisiones.",
            ];
        }

        if (!empty($res['top_carreras'])) {
            $top = $res['top_carreras'][0];
            $consejos[] = [
                'prioridad' => 'MEDIA',
                'icono'     => '🎓',
                'titulo'    => 'Carrera con Mayor Interés Actual',
                'detalle'   => "La carrera '{$top['carrera_interes']}' concentra {$top['cantidad']} solicitud(es). Se recomienda reforzar cupos y material informativo.",
            ];
        }

        $consejos[] = [
            'prioridad' => 'INFO',
            'icono'     => '📚',
            'titulo'    => 'Salud del Asistente Guía ULAT',
            'detalle'   => "Cobertura documental RAG del {$res['chatbot']['cobertura_rag_pct']}% con latencia media de {$res['chatbot']['latencia_media_ms']} ms en los últimos 30 días.",
        ];

        return $consejos;
    }

    public function generateReportFile(string $tipo = 'prospectos', string $formato = 'csv'): array
    {
        $tipo = strtolower(trim($tipo));
        if (!in_array($tipo, self::TIPOS_PERMITIDOS, true)) {
            $tipo = 'prospectos';
        }

        $formato = strtolower(trim($formato));
        if (!in_array($formato, self::FORMATOS_PERMITIDOS, true)) {
            $formato = 'csv';
        }

        $reportsDir = __DIR__ . '/../../storage/reports';
        if (!is_dir($reportsDir)) {
            @mkdir($reportsDir, 0750, true);
        }
        @chmod($reportsDir, 0750);

        $timestamp = date('Ymd_His');
        $suffix = bin2hex(random_bytes(3));
        $filename = "reporte_{$tipo}_{$timestamp}_{$suffix}.{$formato}";
        $filepath = $reportsDir . DIRECTORY_SEPARATOR . $filename;
        $urlDescarga = '../api/descargar_reporte.php?archivo=' . rawurlencode($filename);

        if ($formato === 'csv') {
            $fp = @fopen($filepath, 'wb');
            if ($fp === false) {
                throw new \RuntimeException('No fue posible generar el archivo de reporte.');
            }
            fwrite($fp, "\xEF\xBB\xBF"); // BOM UTF-8

            if ($tipo === 'conversaciones') {
                fputcsv($fp, ['ID', 'Fecha', 'Pregunta', 'Respuesta', 'Latencia (ms)', 'Tokens', 'RAG Score']);
                $stmt = $this->pdo->query("
                    SELECT id, creado_en, pregunta, respuesta, latencia_ms, tokens_usados, rag_score_max
                    FROM chat_logs
                    ORDER BY creado_en DESC
                    LIMIT 500
                ");
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    fputcsv($fp, [
                        (int)$r['id'],
                        SecurityKernel::sanitizeCsvCell($r['creado_en']),
                        SecurityKernel::sanitizeCsvCell($r['pregunta']),
                        SecurityKernel::sanitizeCsvCell($r['respuesta']),
                        (int)($r['latencia_ms'] ?? 0),
                        (int)($r['tokens_usados'] ?? 0),
                        (string)($r['rag_score_max'] ?? '0'),
                    ]);
                }
            } else {
                fputcsv($fp, ['ID', 'Nombre', 'Teléfono', 'Email', 'Carrera de Interés', 'Origen', 'Estado', 'Notas', 'Fecha']);
                $stmt = $this->pdo->query("
                    SELECT id, nombre, telefono, email, carrera_interes, origen, estado, notas, created_at
                    FROM admisiones_prospectos
                    ORDER BY created_at DESC
                    LIMIT 1000
                ");
                while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    fputcsv($fp, [
                        (int)$r['id'],
                        SecurityKernel::sanitizeCsvCell($r['nombre']),
                        SecurityKernel::sanitizeCsvCell($r['telefono']),
                        SecurityKernel::sanitizeCsvCell($r['email'] ?? ''),
                        SecurityKernel::sanitizeCsvCell($r['carrera_interes']),
                        SecurityKernel::sanitizeCsvCell($r['origen']),
                        SecurityKernel::sanitizeCsvCell($r['estado']),
                        SecurityKernel::sanitizeCsvCell($r['notas'] ?? ''),
                        SecurityKernel::sanitizeCsvCell($r['created_at']),
                    ]);
                }
            }
            fclose($fp);
        } else {
            $resumen = $this->getExecutiveSummary();
            $fechaHoy = SecurityKernel::e(date('d/m/Y H:i'));
            $html  = "<!DOCTYPE html><html lang='es'><head><meta charset='utf-8'><title>Reporte Institucional ULAT</title>";
            $html .= "<style>body{font-family:sans-serif;padding:30px;color:#0f172a;}table{width:100%;border-collapse:collapse;margin-top:20px;}th,td{border:1px solid #cbd5e1;padding:8px 10px;text-align:left;font-size:13px;}th{background:#f1f5f9;}</style></head><body>";
            $html .= "<h2>Reporte Institucional de Admisiones e IA — ULAT ({$fechaHoy})</h2>";
            $html .= "<p><strong>Prospectos totales:</strong> " . (int)$resumen['prospectos']['total']
                   . " | <strong>Pendientes:</strong> " . (int)$resumen['prospectos']['pendientes']
                   . " | <strong>Matriculados:</strong> " . (int)$resumen['prospectos']['matriculados'] . "</p>";
            $html .= "<p><strong>Consultas Guía ULAT (30d):</strong> " . (int)$resumen['chatbot']['consultas_mes']
                   . " | <strong>Cobertura RAG:</strong> " . SecurityKernel::e($resumen['chatbot']['cobertura_rag_pct']) . "%</p>";
            $html .= "<h3>Últimos Prospectos Registrados</h3>";
            $html .= "<table><tr><th>ID</th><th>Postulante</th><th>Teléfono</th><th>Carrera</th><th>Origen</th><th>Estado</th><th>Fecha</th></tr>";

            $stmt = $this->pdo->query("
                SELECT id, nombre, telefono, carrera_interes, origen, estado, created_at
                FROM admisiones_prospectos
                ORDER BY created_at DESC
                LIMIT 200
            ");
            while ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $html .= '<tr>'
                    . '<td>#' . (int)$p['id'] . '</td>'
                    . '<td>' . SecurityKernel::e($p['nombre']) . '</td>'
                    . '<td>' . SecurityKernel::e($p['telefono']) . '</td>'
                    . '<td>' . SecurityKernel::e($p['carrera_interes']) . '</td>'
                    . '<td>' . SecurityKernel::e($p['origen']) . '</td>'
                    . '<td>' . SecurityKernel::e($p['estado']) . '</td>'
                    . '<td>' . SecurityKernel::e($p['created_at']) . '</td>'
                    . '</tr>';
            }
            $html .= '</table></body></html>';
            file_put_contents($filepath, $html);
        }

        @chmod($filepath, 0640);

        return [
            'nombre_archivo' => $filename,
            'url'            => $urlDescarga,
            'formato'        => strtoupper($formato),
            'tipo'           => $tipo,
            'fecha'          => date('d/m/Y H:i'),
        ];
    }
}
