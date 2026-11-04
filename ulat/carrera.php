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

<!-- CABECERA EDITORIAL DE LA CARRERA -->
<div class="ulat-page-header">
    <div class="container">
        <div class="mb-3">
            <a href="carreras.php" class="font-mono-code text-white-50 text-decoration-none">← Catálogo de Carreras</a>
        </div>
        <div class="row align-items-end g-4">
            <div class="col-lg-8">
                <span class="ulat-eyebrow-light"><?= htmlspecialchars($c['facultad']) ?> · <?= htmlspecialchars($c['resolucion'] ?? 'R.M. Oficial') ?></span>
                <h1 class="font-editorial display-4 text-white mb-4">
                    <?= htmlspecialchars($c['nombre']) ?>
                </h1>
                <div class="d-flex flex-wrap gap-4 text-white-50 small">
                    <span>Grado: <strong class="text-white fw-normal"><?= htmlspecialchars($c['grado']) ?></strong></span>
                    <span>·</span>
                    <span>Duración: <strong class="text-white fw-normal"><?= htmlspecialchars($c['duracion']) ?></strong></span>
                    <span>·</span>
                    <span>Sede: <strong class="text-white fw-normal"><?= htmlspecialchars($c['sede'] ?? 'Campus Central') ?></strong></span>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                    <button type="button" class="btn btn-ulat-gold" onclick="window.preguntarAlChat('<?= addslashes($c['nombre']) ?>')">
                        Consultar con Guía ULAT
                    </button>
                    <a href="https://wa.me/59179795778?text=<?= urlencode("Hola ULAT, me interesa recibir información y aranceles de la carrera de {$c['nombre']}") ?>" target="_blank" class="btn btn-ulat-ghost-light">
                        WhatsApp ↗
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <!-- Columna Principal: Plan de Estudios y Perfil -->
        <div class="col-lg-8">

            <!-- Malla Curricular por Semestre con Selector de Vista Minimalista -->
            <?php if (!empty($c['semestres']) && is_array($c['semestres'])): ?>
            <div class="mb-5 pb-4" id="plan-estudios">
                <div class="d-flex justify-content-between align-items-end mb-4 pb-3 flex-wrap gap-3" style="border-bottom: 1px solid var(--ulat-line);">
                    <div>
                        <span class="ulat-eyebrow">Estructura Curricular</span>
                        <h2 class="font-editorial h2 mb-0">Plan de Estudios por Semestre</h2>
                    </div>
                    <?php if (!empty($c['malla_imagen']) && file_exists(__DIR__ . '/../' . $c['malla_imagen'])): ?>
                        <button type="button" class="btn btn-ulat-outline btn-sm" data-bs-toggle="modal" data-bs-target="#modalMalla">
                            Ver Diagrama Oficial HD ↗
                        </button>
                    <?php endif; ?>
                </div>

                <div class="row g-4">
                    <?php foreach ($c['semestres'] as $semNombre => $materias): ?>
                        <div class="col-md-6">
                            <div class="semester-card">
                                <div class="semester-header">
                                    <span><?= htmlspecialchars($semNombre) ?></span>
                                    <span class="text-muted"><?= str_pad((string)count($materias), 2, '0', STR_PAD_LEFT) ?> MAT</span>
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

            <!-- Diagrama Gráfico Oficial -->
            <?php if (!empty($c['malla_imagen']) && file_exists(__DIR__ . '/../' . $c['malla_imagen'])): ?>
            <div class="card card-ulat p-4 mb-5" id="malla-curricular">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 flex-wrap gap-2" style="border-bottom: 1px solid var(--ulat-line);">
                    <span class="font-mono-code text-muted">Flujograma Oficial de Asignaturas</span>
                    <button type="button" class="btn btn-link btn-sm p-0 text-dark text-decoration-none font-mono-code" data-bs-toggle="modal" data-bs-target="#modalMalla">
                        Pantalla Completa ↗
                    </button>
                </div>
                <div class="text-center">
                    <img src="../<?= htmlspecialchars($c['malla_imagen']) ?>" alt="Malla Curricular <?= htmlspecialchars($c['nombre']) ?>" class="img-fluid" style="max-height: 540px; width: auto; cursor: zoom-in;" data-bs-toggle="modal" data-bs-target="#modalMalla">
                </div>
            </div>
            <?php endif; ?>

            <!-- Perfil Profesional -->
            <div class="pt-3" style="border-top: 1px solid var(--ulat-line);">
                <span class="ulat-eyebrow">Alcance Académico y Campo Ocupacional</span>
                <h2 class="font-editorial h2 mb-4">Perfil Profesional</h2>
                <div class="text-secondary" style="line-height: 1.8; font-size: 0.93rem;">
                    <?= nl2br(htmlspecialchars($c['descripcion'])) ?>
                </div>
            </div>
        </div>

        <!-- Sidebar Minimalista -->
        <div class="col-lg-4">
            <div class="sticky-top" style="top: 90px;">
                <div class="card card-ulat p-4 p-md-4 bg-white mb-4" id="preinscripcion-carrera">
                    <span class="ulat-eyebrow">Admisiones II–2026</span>
                    <h4 class="h5 mb-2">Postular a <?= htmlspecialchars($c['nombre']) ?></h4>
                    <p class="small text-muted mb-4">
                        Solicita el detalle de aranceles, horarios y requisitos de inscripción.
                    </p>
                    
                    <form id="form-carrera-lead" action="api_lead.php" method="POST">
                        <input type="hidden" name="carrera" value="<?= htmlspecialchars($c['nombre']) ?>">
                        <div class="mb-3">
                            <label class="font-mono-code text-muted d-block mb-1">Nombre Completo</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Carlos Mendoza" required>
                        </div>
                        <div class="mb-3">
                            <label class="font-mono-code text-muted d-block mb-1">Teléfono / WhatsApp</label>
                            <input type="tel" name="telefono" class="form-control" placeholder="79795778" required>
                        </div>
                        <div class="mb-3">
                            <label class="font-mono-code text-muted d-block mb-1">Correo Electrónico (Opcional)</label>
                            <input type="email" name="email" class="form-control" placeholder="correo@ejemplo.com">
                        </div>
                        <button type="submit" class="btn btn-ulat-primary w-100 py-2.5">
                            Solicitar Aranceles y Cupo →
                        </button>
                        <div id="carrera-lead-status" class="small mt-2 text-center d-none"></div>
                    </form>
                </div>

                <div class="p-4" style="border-top: 1px solid var(--ulat-line);">
                    <div class="font-mono-code text-muted mb-3">Beneficios Aplicables</div>
                    <ul class="semester-list small">
                        <li><strong>Plan Uno a Uno:</strong> 50% de descuento en tu cuota mensual por recomendación.</li>
                        <li><strong>Beca Excelencia:</strong> 100% libre de cuotas para promedios mayores a 95%.</li>
                        <li><strong>Traspasos:</strong> Convalidación ágil de materias cursadas en otras universidades.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Lightbox Modal para Malla en Alta Resolución -->
<?php if (!empty($c['malla_imagen']) && file_exists(__DIR__ . '/../' . $c['malla_imagen'])): ?>
<div class="modal fade" id="modalMalla" tabindex="-1" aria-labelledby="modalMallaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content border-0" style="border-radius: 6px;">
            <div class="modal-header border-bottom px-4 py-3" style="background-color: var(--ulat-ink); color: #fff;">
                <h5 class="modal-title font-mono-code text-white mb-0" id="modalMallaLabel"><?= htmlspecialchars($c['nombre']) ?> · Malla Curricular</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body text-center p-4" style="background-color: var(--ulat-bg);">
                <img src="../<?= htmlspecialchars($c['malla_imagen']) ?>" alt="Malla Completa <?= htmlspecialchars($c['nombre']) ?>" class="img-fluid">
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
            status.innerHTML = 'Solicitud registrada. Admisiones te contactará en breve.';
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
