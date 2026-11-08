<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

use Services\Security\SecurityKernel;

$pdo = Database::getConnection();

// 1. Resumen de prospectos de admisiones
$leadsStats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        COALESCE(SUM(CASE WHEN estado = 'nuevo' THEN 1 ELSE 0 END), 0) AS nuevos,
        COALESCE(SUM(CASE WHEN estado = 'matriculado' THEN 1 ELSE 0 END), 0) AS matriculados,
        COALESCE(SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END), 0) AS ultimo_mes
    FROM admisiones_prospectos
")->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'nuevos' => 0, 'matriculados' => 0, 'ultimo_mes' => 0];

// 2. Telemetría del Chatbot Guía ULAT (últimos 30 días)
$chatStats = $pdo->query("
    SELECT
        COUNT(*) AS interacciones,
        ROUND(COALESCE(AVG(latencia_ms), 0)) AS latencia_media_ms,
        COALESCE(SUM(tokens_usados), 0) AS tokens_totales,
        COALESCE(SUM(CASE WHEN rag_score_max >= 0.25 THEN 1 ELSE 0 END), 0) AS rag_hits
    FROM chat_logs
    WHERE creado_en >= DATE_SUB(NOW(), INTERVAL 30 DAY)
")->fetch(PDO::FETCH_ASSOC) ?: ['interacciones' => 0, 'latencia_media_ms' => 0, 'tokens_totales' => 0, 'rag_hits' => 0];

$ragCobertura = (int)$chatStats['interacciones'] > 0
    ? round(((int)$chatStats['rag_hits'] / (int)$chatStats['interacciones']) * 100, 1)
    : 0.0;

// 3. Estado de la base documental RAG ULAT
$ragDocsCount = (int)($pdo->query("
    SELECT COUNT(*) FROM rag_documents
    WHERE collection IN ('admisiones', 'becas', 'carreras', 'institucional', 'reglamentos')
")->fetchColumn() ?: 0);

// 4. Últimos prospectos registrados
$ultimosProspectos = $pdo->query("
    SELECT id, nombre, telefono, carrera_interes, origen, estado, created_at
    FROM admisiones_prospectos
    ORDER BY created_at DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold brand-font mb-1">Dashboard de Admisiones e IA — ULAT</h2>
        <p class="text-muted small mb-0">Seguimiento en tiempo real de postulantes captados y desempeño de Guía ULAT.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="prospectos.php" class="btn btn-outline-dark rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2">
            <i data-lucide="users"></i> <span>Gestionar Prospectos</span>
        </a>
        <a href="chat_admin.php" class="btn btn-primary rounded-pill px-3 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
            <i data-lucide="brain"></i> <span>Asistente BI</span>
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-xl-4 col-md-6">
        <div class="kpi-card h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Prospectos Captados</span>
                    <h3 class="fw-bold brand-font text-dark mb-0 mt-1"><?= (int)$leadsStats['total'] ?></h3>
                    <small class="text-muted"><?= (int)$leadsStats['ultimo_mes'] ?> en los últimos 30 días</small>
                </div>
                <div class="kpi-icon-box kpi-icon-emerald">
                    <i data-lucide="user-plus"></i>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-3 border-top small text-muted">
                <span><strong class="text-warning"><?= (int)$leadsStats['nuevos'] ?></strong> pendientes de contacto</span>
                <span><strong class="text-success"><?= (int)$leadsStats['matriculados'] ?></strong> matriculados</span>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-6">
        <div class="kpi-card h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Consultas Guía ULAT (30d)</span>
                    <h3 class="fw-bold brand-font text-dark mb-0 mt-1"><?= number_format((int)$chatStats['interacciones']) ?></h3>
                    <small class="text-muted">Latencia media: <?= number_format((int)$chatStats['latencia_media_ms']) ?> ms</small>
                </div>
                <div class="kpi-icon-box kpi-icon-indigo">
                    <i data-lucide="message-square"></i>
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-3 border-top small text-muted">
                <span>Cobertura RAG: <strong><?= SecurityKernel::e($ragCobertura) ?>%</strong></span>
                <span><?= number_format((int)$chatStats['tokens_totales']) ?> tokens</span>
            </div>
        </div>
    </div>

    <div class="col-xl-4 col-md-12">
        <div class="kpi-card h-100 d-flex flex-column justify-content-between">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <span class="text-muted small text-uppercase fw-bold">Índice Académico RAG</span>
                    <h3 class="fw-bold brand-font text-dark mb-0 mt-1"><?= number_format($ragDocsCount) ?> chunks</h3>
                    <small class="text-muted">Carreras, mallas, becas y reglamentos</small>
                </div>
                <div class="kpi-icon-box kpi-icon-amber">
                    <i data-lucide="book-open"></i>
                </div>
            </div>
            <div class="pt-3 border-top small text-muted">
                <span>15 carreras oficiales + 3 reglamentos ministeriales activos</span>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
    <div class="card-header bg-white border-0 pt-3 pb-2 d-flex justify-content-between align-items-center">
        <h5 class="fw-bold brand-font mb-0"><i data-lucide="clock" class="text-primary me-2"></i> Últimos Prospectos de Admisiones</h5>
        <a href="prospectos.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Ver todos</a>
    </div>
    <div class="table-responsive">
        <table class="table table-saas mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Postulante</th>
                    <th>Teléfono</th>
                    <th>Carrera de Interés</th>
                    <th>Origen</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($ultimosProspectos)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Aún no se registran prospectos en admisiones.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($ultimosProspectos as $p): ?>
                        <tr>
                            <td class="fw-bold text-dark">#<?= (int)$p['id'] ?></td>
                            <td><?= SecurityKernel::e($p['nombre']) ?></td>
                            <td><?= SecurityKernel::e($p['telefono']) ?></td>
                            <td><?= SecurityKernel::e($p['carrera_interes']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= SecurityKernel::e($p['origen']) ?></span></td>
                            <td><span class="badge bg-secondary"><?= SecurityKernel::e($p['estado']) ?></span></td>
                            <td class="text-muted small"><?= SecurityKernel::e(date('d/m/Y H:i', strtotime((string)$p['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$contenido = ob_get_clean();
$titulo = 'Dashboard Admisiones ULAT';
require __DIR__ . '/layout.php';
