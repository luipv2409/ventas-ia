<?php
declare(strict_types=1);

$catalogFile = __DIR__ . '/../data/ulat_catalog.json';
$carrerasCatalog = file_exists($catalogFile) ? (json_decode(file_get_contents($catalogFile), true) ?: []) : [];

$slug = trim((string)($_GET['id'] ?? ''));
if ($slug === '' || !isset($carrerasCatalog[$slug])) {
    header('Location: carreras.php');
    exit;
}

$c = $carrerasCatalog[$slug];
$pageTitle = "{$c['nombre']} | Universidad Latinoamericana (ULAT)";
$activeNav = 'carreras';

require_once __DIR__ . '/header.php';
?>

<!-- ENCABEZADO DE LA CARRERA -->
<div class="ulat-page-header">
    <div class="container py-3">
        <nav aria-label="breadcrumb" class="mb-2">
            <ol class="breadcrumb small mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white-50 text-decoration-none">Inicio</a></li>
                <li class="breadcrumb-item"><a href="carreras.php" class="text-white-50 text-decoration-none">Carreras</a></li>
                <li class="breadcrumb-item active text-white" aria-current="page"><?= htmlspecialchars($c['nombre']) ?></li>
            </ol>
        </nav>
        <div class="row align-items-end g-4">
            <div class="col-lg-8">
                <span class="ulat-eyebrow-light"><?= htmlspecialchars($c['facultad']) ?></span>
                <h1 class="display-5 fw-bold text-white mb-3">
                    <?= htmlspecialchars($c['nombre']) ?>
                </h1>
                <div class="d-flex flex-wrap gap-4 text-white-50 small mb-4">
                    <span class="d-inline-flex align-items-center gap-1">
                        <i data-lucide="award" class="lucide-sm" style="color: var(--ulat-gold);"></i>
                        Grado: <strong class="text-white"><?= htmlspecialchars($c['grado']) ?></strong>
                    </span>
                    <span class="d-inline-flex align-items-center gap-1">
                        <i data-lucide="calendar" class="lucide-sm" style="color: var(--ulat-gold);"></i>
                        Duración: <strong class="text-white"><?= htmlspecialchars($c['duracion']) ?></strong>
                    </span>
                    <span class="d-inline-flex align-items-center gap-1">
                        <i data-lucide="map-pin" class="lucide-sm" style="color: var(--ulat-gold);"></i>
                        Sede: <strong class="text-white"><?= htmlspecialchars($c['sede'] ?? 'Campus Central') ?></strong>
                    </span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-ulat-gold d-inline-flex align-items-center gap-2" onclick="window.preguntarAlChat('<?= addslashes($c['nombre']) ?>')">
                        <i data-lucide="message-square" class="lucide-sm"></i>
                        <span>Consultar sobre esta Carrera</span>
                    </button>
                    <a href="https://wa.me/59179795778?text=<?= urlencode("Hola ULAT, me interesa recibir información y aranceles de la carrera de {$c['nombre']}") ?>" target="_blank" class="btn btn-outline-light px-3 py-2 d-inline-flex align-items-center gap-2" style="border-radius: 6px; font-size: 0.875rem; font-weight: 600;">
                        <span>WhatsApp Admisiones</span>
                        <i data-lucide="arrow-up-right" class="lucide-sm"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <!-- Contenido Principal -->
        <div class="col-lg-8">

            <!-- Malla Curricular Estructurada Semestre por Semestre -->
            <?php if (!empty($c['semestres']) && is_array($c['semestres'])): ?>
            <div class="card card-ulat p-4 p-md-5 mb-5" id="plan-estudios">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom flex-wrap gap-2">
                    <div>
                        <span class="ulat-eyebrow mb-1">Estructura Curricular Oficial</span>
                        <h3 class="h4 fw-bold mb-0">Plan de Estudios por Semestre</h3>
                    </div>
                    <?php if (!empty($c['malla_imagen']) && file_exists(__DIR__ . '/../' . $c['malla_imagen'])): ?>
                        <button type="button" class="btn btn-ulat-outline btn-sm d-inline-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#modalMalla">
                            <i data-lucide="maximize-2" class="lucide-sm"></i>
                            <span>Ver Diagrama Oficial Completo</span>
                        </button>
                    <?php endif; ?>
                </div>

                <div class="row g-3">
                    <?php foreach ($c['semestres'] as $semNombre => $materias): ?>
                        <div class="col-md-6">
                            <div class="semester-card">
                                <div class="semester-header">
                                    <span><?= htmlspecialchars($semNombre) ?></span>
                                    <span class="badge bg-white text-secondary border fw-normal"><?= count($materias) ?> asignaturas</span>
                                </div>
                                <ul class="semester-list">
                                    <?php foreach ($materias as $mat): ?>
                                        <li><?= htmlspecialchars($mat) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Imagen de la Malla Curricular Oficial -->
            <?php if (!empty($c['malla_imagen']) && file_exists(__DIR__ . '/../' . $c['malla_imagen'])): ?>
            <div class="card card-ulat p-4 p-md-5 mb-5" id="malla-curricular">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom flex-wrap gap-2">
                    <div>
                        <span class="ulat-eyebrow mb-1">Flujograma de Correlatividades</span>
                        <h3 class="h5 fw-bold mb-0">Malla Curricular Gráfica Oficial</h3>
                    </div>
                    <button type="button" class="btn btn-ulat-outline btn-sm d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalMalla">
                        <i data-lucide="zoom-in" class="lucide-sm"></i>
                        <span>Ampliar Imagen</span>
                    </button>
                </div>
                <div class="text-center p-3 rounded border" style="background-color: #f8fafc;">
                    <img src="../<?= htmlspecialchars($c['malla_imagen']) ?>" alt="Malla Curricular <?= htmlspecialchars($c['nombre']) ?>" class="img-fluid rounded" style="max-height: 580px; width: auto; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#modalMalla">
                    <div class="small text-muted mt-2">
                        Haz clic sobre la malla curricular para inspeccionar en alta resolución
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Perfil Profesional y Campo Ocupacional -->
            <div class="card card-ulat p-4 p-md-5">
                <span class="ulat-eyebrow mb-1">Alcance Académico y Laboral</span>
                <h3 class="h4 fw-bold mb-4 pb-3 border-bottom">
                    Perfil Profesional y Objetivos de la Carrera
                </h3>
                <div class="text-secondary" style="line-height: 1.75; font-size: 0.94rem;">
                    <?= nl2br(htmlspecialchars($c['descripcion'])) ?>
                </div>
            </div>
        </div>

        <!-- Sidebar Lateral de Preinscripción -->
        <div class="col-lg-4">
            <div class="sticky-top" style="top: 90px;">
                <div class="card card-ulat p-4 bg-white mb-4" id="preinscripcion-carrera" style="border-top: 3px solid var(--ulat-gold);">
                    <span class="ulat-eyebrow mb-1">Admisiones II-2026</span>
                    <h5 class="fw-bold mb-2">Postular a <?= htmlspecialchars($c['nombre']) ?></h5>
                    <p class="small text-muted mb-3">
                        Registra tus datos para recibir el detalle de aranceles, horarios y requisitos de inscripción:
                    </p>
                    
                    <form id="form-carrera-lead" action="api_lead.php" method="POST">
                        <input type="hidden" name="carrera" value="<?= htmlspecialchars($c['nombre']) ?>">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-secondary mb-1">Nombre Completo</label>
                            <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Ej. Carlos Mendoza" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-secondary mb-1">Teléfono / WhatsApp</label>
                            <input type="tel" name="telefono" class="form-control form-control-sm" placeholder="Ej. 79795778" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Correo Electrónico (opcional)</label>
                            <input type="email" name="email" class="form-control form-control-sm" placeholder="correo@ejemplo.com">
                        </div>
                        <button type="submit" class="btn btn-ulat-primary w-100 py-2 d-inline-flex align-items-center justify-content-center gap-2">
                            <span>Solicitar Información Oficial</span>
                            <i data-lucide="arrow-up-right" class="lucide-sm"></i>
                        </button>
                        <div id="carrera-lead-status" class="small mt-2 text-center d-none"></div>
                    </form>
                </div>

                <!-- Resumen de Beneficios -->
                <div class="card card-ulat p-4">
                    <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom">Beneficios Estudiantiles ULAT</h6>
                    <ul class="semester-list px-0 mb-0">
                        <li><strong>Plan Uno a Uno:</strong> 50% de descuento en tu cuota mensual por cada alumno nuevo recomendado.</li>
                        <li><strong>Beca Excelencia:</strong> Cobertura del 100% para promedios superiores al 95%.</li>
                        <li><strong>Planes de Estudio:</strong> Modalidad Regular y Plan S.O.S.</li>
                        <li><strong>Convalidaciones:</strong> Trámite ágil para traspasos de otras universidades.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Malla en Pantalla Completa -->
<?php if (!empty($c['malla_imagen']) && file_exists(__DIR__ . '/../' . $c['malla_imagen'])): ?>
<div class="modal fade" id="modalMalla" tabindex="-1" aria-labelledby="modalMallaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title h6" id="modalMallaLabel">Malla Curricular Oficial — <?= htmlspecialchars($c['nombre']) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center p-3 bg-light">
                <img src="../<?= htmlspecialchars($c['malla_imagen']) ?>" alt="Malla Completa <?= htmlspecialchars($c['nombre']) ?>" class="img-fluid rounded border">
            </div>
            <div class="modal-footer justify-content-between">
                <span class="small text-muted">Universidad Latinoamericana (ULAT) · Título en Provisión Nacional</span>
                <button type="button" class="btn btn-ulat-outline btn-sm" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.getElementById('form-carrera-lead')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const status = document.getElementById('carrera-lead-status');
    const data = new FormData(form);

    status.classList.remove('d-none', 'alert-danger', 'alert-success');
    status.innerHTML = 'Registrando solicitud...';

    try {
        const resp = await fetch('api_lead.php', { method: 'POST', body: data });
        const res = await resp.json();
        if (res.ok) {
            status.className = 'small mt-2 alert alert-success p-2 text-center';
            status.innerHTML = 'Solicitud registrada con éxito. Admisiones te contactará en breve.';
            form.reset();
        } else {
            status.className = 'small mt-2 alert alert-danger p-2 text-center';
            status.innerHTML = res.error || 'Ocurrió un error al enviar.';
        }
    } catch(err) {
        status.className = 'small mt-2 alert alert-danger p-2 text-center';
        status.innerHTML = 'Error de conexión.';
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
