<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getConnection();

// 1. Conversaciones diarias últimos 14 días
$conversacionesDiarias = $pdo->query("
    SELECT DATE(creado_en) AS fecha, COUNT(*) AS total
    FROM chat_logs
    WHERE creado_en >= DATE_SUB(CURRENT_DATE(), INTERVAL 14 DAY)
    GROUP BY DATE(creado_en)
    ORDER BY fecha ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 2. Top carreras solicitadas por prospectos
$carrerasTop = $pdo->query("
    SELECT carrera_interes AS carrera, COUNT(*) AS total
    FROM admisiones_prospectos
    GROUP BY carrera_interes
    ORDER BY total DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// 3. Prospectos por estado de seguimiento
$prospectosEstado = $pdo->query("
    SELECT estado, COUNT(*) AS total
    FROM admisiones_prospectos
    GROUP BY estado
")->fetchAll(PDO::FETCH_ASSOC);

// 4. Prospectos por canal de captación
$prospectosOrigen = $pdo->query("
    SELECT origen, COUNT(*) AS total
    FROM admisiones_prospectos
    GROUP BY origen
")->fetchAll(PDO::FETCH_ASSOC);

$datosJSON = json_encode([
    'conversacionesDiarias' => $conversacionesDiarias,
    'carrerasTop'           => $carrerasTop,
    'prospectosEstado'      => $prospectosEstado,
    'prospectosOrigen'      => $prospectosOrigen,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

ob_start();
?>
<h2 class="fw-bold brand-font mb-1">Métricas de Admisiones y Chatbot ULAT</h2>
<p class="text-muted small mb-4">Analítica consolidada de interacción estudiantil y demanda por carrera.</p>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-3"><h6 class="fw-bold mb-0">Consultas Diarias en Guía ULAT (14 días)</h6></div>
            <div class="card-body"><canvas id="conversacionesChart" height="260"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-3"><h6 class="fw-bold mb-0">Carreras con Mayor Demanda</h6></div>
            <div class="card-body"><canvas id="carrerasTopChart" height="260"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-3"><h6 class="fw-bold mb-0">Prospectos por Estado</h6></div>
            <div class="card-body"><canvas id="estadoChart" height="260"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 pt-3"><h6 class="fw-bold mb-0">Canales de Captación</h6></div>
            <div class="card-body"><canvas id="origenChart" height="260"></canvas></div>
        </div>
    </div>
</div>
<?php
$contenido = ob_get_clean();
$titulo = 'Métricas ULAT';
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" integrity="sha384-9nhczxUqK87bcKHh20fSQcTGD4qq5GhayNYSYWqwBkINBhOfQLg/P5HG5lF1urn4" crossorigin="anonymous"></script>';
$extraScripts = <<<HTML
<script>
const datos = {$datosJSON};
const paleta = ['#0D1E36', '#C59B27', '#1E3A8A', '#0F766E', '#64748B', '#B45309'];

new Chart(document.getElementById('conversacionesChart'), {
    type: 'line',
    data: {
        labels: datos.conversacionesDiarias.map(d => d.fecha),
        datasets: [{
            label: 'Consultas atendidas',
            data: datos.conversacionesDiarias.map(d => Number(d.total)),
            borderColor: '#0D1E36',
            backgroundColor: 'rgba(13, 30, 54, 0.08)',
            fill: true,
            tension: 0.3
        }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('carrerasTopChart'), {
    type: 'bar',
    data: {
        labels: datos.carrerasTop.map(c => c.carrera),
        datasets: [{
            label: 'Postulantes',
            data: datos.carrerasTop.map(c => Number(c.total)),
            backgroundColor: '#C59B27',
            borderRadius: 6
        }]
    },
    options: { responsive: true, scales: { y: { beginAtZero: true } } }
});

new Chart(document.getElementById('estadoChart'), {
    type: 'doughnut',
    data: {
        labels: datos.prospectosEstado.map(e => e.estado),
        datasets: [{
            data: datos.prospectosEstado.map(e => Number(e.total)),
            backgroundColor: paleta
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});

new Chart(document.getElementById('origenChart'), {
    type: 'pie',
    data: {
        labels: datos.prospectosOrigen.map(o => o.origen),
        datasets: [{
            data: datos.prospectosOrigen.map(o => Number(o.total)),
            backgroundColor: paleta
        }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
});
</script>
HTML;
require __DIR__ . '/layout.php';
