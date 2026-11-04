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

$facClass = str_contains($c['facultad'], 'Salud') ? 'salud' : (str_contains($c['facultad'], 'Tecnolog') ? 'tecnologia' : 'economicas');
$badgeColor = str_contains($c['facultad'], 'Salud') ? 'badge-salud' : (str_contains($c['facultad'], 'Tecnolog') ? 'badge-tecno' : 'badge-econo');

require_once __DIR__ . '/header.php';
?>

<!-- BANNER DE LA CARRERA -->
<div class="bg-primary text-white py-5" style="background: linear-gradient(135deg, var(--ulat-blue-dark) 0%, var(--ulat-blue-primary) 100%);">
    <div class="container py-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="index.php" class="text-light text-opacity-75">Inicio</a></li>
                <li class="breadcrumb-item"><a href="carreras.php" class="text-light text-opacity-75">Carreras</a></li>
                <li class="breadcrumb-item active text-warning" aria-current="page"><?= htmlspecialchars($c['nombre']) ?></li>
            </ol>
        </nav>
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge-facultad <?= $badgeColor ?> mb-2 d-inline-block">
                    <?= htmlspecialchars($c['facultad']) ?>
                </span>
                <h1 class="display-4 fw-bold text-white mb-3">
                    <?= htmlspecialchars($c['nombre']) ?>
                </h1>
                <div class="d-flex flex-wrap gap-4 text-light text-opacity-90 small mb-4">
                    <span><i class="fa-solid fa-graduation-cap text-warning me-1"></i> Grado: <strong><?= htmlspecialchars($c['grado']) ?></strong></span>
                    <span><i class="fa-solid fa-clock text-warning me-1"></i> Duración: <strong><?= htmlspecialchars($c['duracion']) ?></strong></span>
                    <span><i class="fa-solid fa-building text-warning me-1"></i> Modalidad: <strong><?= htmlspecialchars($c['modalidad']) ?></strong></span>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-ulat-gold" onclick="window.preguntarAlChat('<?= addslashes($c['nombre']) ?>')">
                        <i class="fa-solid fa-comments me-1"></i> Consultar con Guía ULAT
                    </button>
                    <a href="https://wa.me/5914509488?text=<?= urlencode("Hola ULAT, me interesa recibir información y costos de la carrera de {$c['nombre']}") ?>" target="_blank" class="btn btn-success rounded-pill px-4">
                        <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp Admisiones
                    </a>
                    <a href="#preinscripcion-carrera" class="btn btn-outline-light rounded-pill px-4">
                        <i class="fa-solid fa-file-pen me-1"></i> Preinscribirme
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <!-- Contenido principal -->
        <div class="col-lg-8">
            <!-- Perfil y Objetivos -->
            <div class="card card-ulat p-4 p-md-5 mb-5">
                <h3 class="fw-bold text-primary mb-4 pb-2 border-bottom">
                    <i class="fa-solid fa-id-card-clip me-2 text-warning"></i> Perfil Profesional y Objetivos
                </h3>
                <div class="lh-lg text-secondary">
                    <?= nl2br(htmlspecialchars($c['descripcion'])) ?>
                </div>
            </div>

            <!-- Malla Curricular Visual -->
            <div class="card card-ulat p-4 p-md-5 mb-5" id="malla-curricular">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom flex-wrap gap-2">
                    <h3 class="fw-bold text-primary mb-0">
                        <i class="fa-solid fa-network-wired me-2 text-warning"></i> Malla Curricular Oficial
                    </h3>
                    <?php if (!empty($c['malla_imagen'])): ?>
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#modalMalla">
                            <i class="fa-solid fa-magnifying-glass-plus me-1"></i> Ver en Pantalla Completa
                        </button>
                    <?php endif; ?>
                </div>

                <?php if (!empty($c['malla_imagen']) && file_exists(__DIR__ . '/../' . $c['malla_imagen'])): ?>
                    <div class="text-center bg-light p-3 rounded-3 border">
                        <img src="../<?= htmlspecialchars($c['malla_imagen']) ?>" alt="Malla Curricular <?= htmlspecialchars($c['nombre']) ?>" class="img-fluid rounded shadow-sm" style="max-height: 600px; width: auto; cursor: pointer;" data-bs-toggle="modal" data-bs-target="#modalMalla">
                        <p class="small text-muted mt-2 mb-0">
                            <i class="fa-solid fa-hand-pointer me-1"></i> Haz clic en la imagen para ampliar en alta definición
                        </p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info d-flex align-items-center gap-3">
                        <i class="fa-solid fa-info-circle fs-4"></i>
                        <div>
                            La visualización digital de la malla curricular está siendo actualizada. Puedes solicitar el pensum analítico completo al asesor de admisiones.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Sidebar Lateral de Preinscripción -->
        <div class="col-lg-4">
            <div class="sticky-top" style="top: 100px;">
                <!-- Card de Preinscripción -->
                <div class="card card-ulat p-4 bg-white border-top border-4 border-warning mb-4" id="preinscripcion-carrera">
                    <h5 class="fw-bold text-primary mb-2">Preinscripción a <?= htmlspecialchars($c['nombre']) ?></h5>
                    <p class="small text-muted mb-3">Completa tus datos y asegura tu cupo con los beneficios vigentes:</p>
                    
                    <form id="form-carrera-lead" action="api_lead.php" method="POST">
                        <input type="hidden" name="carrera" value="<?= htmlspecialchars($c['nombre']) ?>">
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary">Nombre Completo</label>
                            <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Ej: Pedro Morales" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary">Teléfono / WhatsApp</label>
                            <input type="tel" name="telefono" class="form-control form-control-sm" placeholder="Ej: 71234567" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary">Correo Electrónico (opcional)</label>
                            <input type="email" name="email" class="form-control form-control-sm" placeholder="correo@ejemplo.com">
                        </div>
                        <button type="submit" class="btn btn-ulat-gold w-100 btn-sm py-2 mt-2">
                            <i class="fa-solid fa-paper-plane me-1"></i> Enviar Preinscripción
                        </button>
                        <div id="carrera-lead-status" class="small mt-2 text-center d-none"></div>
                    </form>
                </div>

                <!-- Card de Beneficios -->
                <div class="card card-ulat p-4 bg-light border-0">
                    <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-gift text-warning me-2"></i> Beneficios para esta carrera</h6>
                    <ul class="list-unstyled small text-secondary mb-0">
                        <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> <strong>Plan Uno a Uno:</strong> 50% de descuento en tu cuota si recomiendas a un compañero.</li>
                        <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> <strong>Beca Excelencia:</strong> 100% libre de cuotas si tu promedio supera 95%.</li>
                        <li class="mb-2"><i class="fa-solid fa-check text-success me-2"></i> Prácticas y laboratorios especializados propios.</li>
                        <li><i class="fa-solid fa-check text-success me-2"></i> Bolsa de trabajo y convalidaciones.</li>
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
                <h5 class="modal-title" id="modalMallaLabel">Malla Curricular: <?= htmlspecialchars($c['nombre']) ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center p-2 bg-secondary bg-opacity-10">
                <img src="../<?= htmlspecialchars($c['malla_imagen']) ?>" alt="Malla Completa <?= htmlspecialchars($c['nombre']) ?>" class="img-fluid rounded">
            </div>
            <div class="modal-footer justify-content-between">
                <span class="small text-muted">Universidad Latinoamericana (ULAT) • Acreditación Ministerial</span>
                <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Cerrar</button>
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
    status.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-1"></i> Registrando...';

    try {
        const resp = await fetch('api_lead.php', { method: 'POST', body: data });
        const res = await resp.json();
        if (res.ok) {
            status.className = 'small mt-2 alert alert-success p-2 text-center';
            status.innerHTML = '✔ ¡Preinscripción registrada con éxito!';
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

