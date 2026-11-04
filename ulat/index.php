<?php
declare(strict_types=1);
$pageTitle = 'Universidad Latinoamericana (ULAT) | Formación Profesional de Excelencia en Cochabamba';
$activeNav = 'inicio';
require_once __DIR__ . '/header.php';
?>

<!-- HERO BANNER PRINCIPAL -->
<section class="ulat-hero">
    <div class="container py-4">
        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-3">
                    <i class="fa-solid fa-bell me-1"></i> INSCRIPCIONES ABIERTAS II-2026
                </span>
                <h1 class="display-4 fw-bold mb-3 text-white">
                    El lugar perfecto para tu educación superior
                </h1>
                <p class="lead mb-4 text-light text-opacity-90">
                    Formando líderes y profesionales idóneos con sólida preparación científica, técnica y valores éticos desde 1995 en Cochabamba, Bolivia.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="carreras.php" class="btn btn-ulat-gold btn-lg">
                        <i class="fa-solid fa-book-open me-2"></i> Ver las 15 Carreras
                    </a>
                    <a href="admisiones.php" class="btn btn-outline-light btn-lg rounded-pill px-4">
                        <i class="fa-solid fa-file-signature me-2"></i> Requisitos de Admisión
                    </a>
                    <button type="button" class="btn btn-light btn-lg rounded-pill px-4 text-primary fw-bold" onclick="window.preguntarAlChat('carreras y becas disponibles')">
                        <i class="fa-solid fa-comments me-2 text-warning"></i> Chatear con Guía ULAT
                    </button>
                </div>
            </div>
            <div class="col-lg-5 mt-4 mt-lg-0 text-center">
                <div class="card bg-white text-dark p-4 rounded-4 shadow-lg text-start">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bg-primary text-white rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                            <i class="fa-solid fa-graduation-cap fs-4"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 text-primary">Preinscripción Rápida</h5>
                            <small class="text-muted">Asegura tu cupo para esta gestión</small>
                        </div>
                    </div>
                    <form id="form-lead-hero" action="api_lead.php" method="POST">
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary">Nombre Completo</label>
                            <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Ej: María Gonzales" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-bold text-secondary">Teléfono o WhatsApp</label>
                            <input type="tel" name="telefono" class="form-control form-control-sm" placeholder="Ej: 71234567" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-secondary">Carrera de tu Interés</label>
                            <select name="carrera" class="form-select form-select-sm" required>
                                <option value="">Selecciona una carrera...</option>
                                <?php foreach ($carrerasCatalog as $slug => $c): ?>
                                    <option value="<?= htmlspecialchars($c['nombre']) ?>"><?= htmlspecialchars($c['nombre']) ?> (<?= $c['grado'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-ulat-gold w-100 btn-sm py-2">
                            <i class="fa-solid fa-paper-plane me-1"></i> Solicitar Información y Descuentos
                        </button>
                        <div id="lead-hero-status" class="small mt-2 text-center d-none"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECCIÓN: LAS 3 FACULTADES -->
<section class="py-5 bg-white">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="text-primary fw-bold text-uppercase small tracking-wide">Estructura Académica</span>
            <h2 class="display-6 fw-bold">Nuestras 3 Facultades</h2>
            <p class="text-muted max-w-700 mx-auto">
                Programas curriculares modernos avalados por el Ministerio de Educación, con laboratorios equipados y docentes de amplia trayectoria.
            </p>
        </div>

        <div class="row g-4">
            <!-- Facultad Salud -->
            <div class="col-lg-4 col-md-6">
                <div class="card card-ulat h-100 border-top border-4 border-danger p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-3 fs-3">
                            <i class="fa-solid fa-heart-pulse"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 text-dark">Ciencias de la Salud</h4>
                            <span class="badge bg-danger">6 Carreras</span>
                        </div>
                    </div>
                    <p class="text-muted small mb-3">
                        Formación con alto sentido ético y humanístico en medicina, odontología y ciencias biomédicas con prácticas clínicas tempranas.
                    </p>
                    <ul class="list-unstyled small mb-4 flex-grow-1">
                        <li class="py-1"><i class="fa-solid fa-circle-check text-danger me-2"></i> Licenciatura en Medicina</li>
                        <li class="py-1"><i class="fa-solid fa-circle-check text-danger me-2"></i> Licenciatura en Odontología</li>
                        <li class="py-1"><i class="fa-solid fa-circle-check text-danger me-2"></i> Licenciatura en Enfermería</li>
                        <li class="py-1"><i class="fa-solid fa-circle-check text-danger me-2"></i> Licenciatura en Bioquímica y Farmacia</li>
                        <li class="py-1"><i class="fa-solid fa-circle-check text-danger me-2"></i> Licenciatura en Óptica y Optometría</li>
                        <li class="py-1"><i class="fa-solid fa-circle-check text-danger me-2"></i> Técnico Superior en Prótesis Dental</li>
                    </ul>
                    <a href="facultades.php#salud" class="btn btn-outline-danger btn-sm rounded-pill w-100">
                        Ver Detalles de la Facultad <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Facultad Tecnología -->
            <div class="col-lg-4 col-md-6">
                <div class="card card-ulat h-100 border-top border-4 border-info p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="p-3 bg-info bg-opacity-10 text-info rounded-3 fs-3">
                            <i class="fa-solid fa-microchip"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 text-dark">Ciencia y Tecnología</h4>
                            <span class="badge bg-info text-dark">5 Carreras</span>
                        </div>
                    </div>
                    <p class="text-muted small mb-3">
                        Ingenierías aplicadas al desarrollo de infraestructura, sistemas de software, automotriz, topografía y transformación digital.
                    </p>
                    <ul class="list-unstyled small mb-4 flex-grow-1">
                        <li class="py-1"><i class="fa-solid fa-circle-check text-info me-2"></i> Ingeniería de Sistemas</li>
                        <li class="py-1"><i class="fa-solid fa-circle-check text-info me-2"></i> Ingeniería Civil</li>
                        <li class="py-1"><i class="fa-solid fa-circle-check text-info me-2"></i> Ingeniería en Geodesia y Topografía</li>
                        <li class="py-1"><i class="fa-solid fa-circle-check text-info me-2"></i> Ing. Mecánica Automotriz y Electrónica</li>
                        <li class="py-1"><i class="fa-solid fa-circle-check text-info me-2"></i> Ing. Comercio, Marketing y Diseño Digital</li>
                    </ul>
                    <a href="facultades.php#tecnologia" class="btn btn-outline-info btn-sm rounded-pill w-100 text-dark">
                        Ver Detalles de la Facultad <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>

            <!-- Facultad Económicas -->
            <div class="col-lg-4 col-md-6">
                <div class="card card-ulat h-100 border-top border-4 border-warning p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 fs-3">
                            <i class="fa-solid fa-scale-balanced"></i>
                        </div>
                        <div>
                            <h4 class="mb-0 text-dark">Económicas y Jurídicas</h4>
                            <span class="badge bg-warning text-dark">4 Carreras</span>
                        </div>
                    </div>
                    <p class="text-muted small mb-3">
                        Formación de profesionales éticos, críticos e innovadores en derecho, auditoría contable y ciencias de la educación.
                    </p>
                    <ul class="list-unstyled small mb-4 flex-grow-1">
                        <li class="py-1"><i class="fa-solid fa-circle-check text-warning me-2"></i> Licenciatura en Derecho</li>
                        <li class="py-1"><i class="fa-solid fa-circle-check text-warning me-2"></i> Licenciatura en Auditoría (CPA)</li>
                        <li class="py-1"><i class="fa-solid fa-circle-check text-warning me-2"></i> Licenciatura en Ciencias de la Educación</li>
                        <li class="py-1"><i class="fa-solid fa-circle-check text-warning me-2"></i> Técnico Superior Contador General</li>
                    </ul>
                    <a href="facultades.php#economicas" class="btn btn-outline-warning btn-sm rounded-pill w-100 text-dark">
                        Ver Detalles de la Facultad <i class="fa-solid fa-arrow-right ms-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECCIÓN: BENEFICIOS Y PLAN UNO A UNO -->
<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="badge bg-success bg-opacity-10 text-success px-3 py-2 rounded-pill fw-bold mb-3">
                    <i class="fa-solid fa-hand-holding-dollar me-1"></i> Beneficio Exclusivo ULAT
                </span>
                <h2 class="display-6 fw-bold text-dark mb-4">
                    Plan Uno a Uno: ¡Paga la mitad de tu cuota!
                </h2>
                <p class="lead text-secondary mb-4">
                    La Universidad Latinoamericana y el ILAT crearon el <strong>Plan Uno a Uno</strong> para apoyar la economía de nuestros estudiantes regulares.
                </p>
                <div class="card p-3 border-0 bg-white shadow-sm mb-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="bg-primary text-white p-2 rounded-circle">
                            <i class="fa-solid fa-percent fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">50% de Descuento por Alumno Recomendado</h6>
                            <p class="text-muted small mb-0">
                                Por cada estudiante nuevo que traigas y se matricule en cualquiera de nuestras carreras (Licenciatura, Técnico Superior o Diplomados), cancelas únicamente el 50% de tu cuota mensual.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="card p-3 border-0 bg-white shadow-sm mb-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="bg-warning text-dark p-2 rounded-circle">
                            <i class="fa-solid fa-medal fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Beca por Excelencia Académica (100%)</h6>
                            <p class="text-muted small mb-0">
                                Los estudiantes con promedio general superior al 95% quedan completamente liberados del pago de matrícula y cuotas semestrales.
                            </p>
                        </div>
                    </div>
                </div>
                <a href="becas.php" class="btn btn-ulat-primary">
                    <i class="fa-solid fa-award me-2"></i> Conocer Todo el Programa de Becas
                </a>
            </div>
            <div class="col-lg-6">
                <div class="card card-ulat p-4 p-md-5 bg-white border-0 shadow">
                    <h4 class="fw-bold text-primary mb-3"><i class="fa-solid fa-clipboard-list me-2"></i> Requisitos de Admisión</h4>
                    <p class="text-muted small mb-4">
                        Presenta los siguientes documentos oficiales en la oficina de Admisiones de la ULAT (folder tamaño oficio color amarillo):
                    </p>
                    <ul class="list-group list-group-flush small mb-4">
                        <li class="list-group-item px-0 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-check text-success"></i>
                            <span>Certificado de Nacimiento Original.</span>
                        </li>
                        <li class="list-group-item px-0 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-check text-success"></i>
                            <span>Fotocopia de Cédula de Identidad vigente.</span>
                        </li>
                        <li class="list-group-item px-0 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-check text-success"></i>
                            <span>Fotocopia Legalizada del Diploma de Bachiller.</span>
                        </li>
                        <li class="list-group-item px-0 d-flex align-items-center gap-2">
                            <i class="fa-solid fa-check text-success"></i>
                            <span>4 Fotografías tamaño 3×4 a color con fondo azul (traje formal).</span>
                        </li>
                    </ul>
                    <a href="admisiones.php" class="btn btn-outline-primary btn-sm rounded-pill w-100">
                        Más detalles sobre convalidaciones y postulantes extranjeros
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECCIÓN: TESTIMONIO DEL RECTOR Y TRADICIÓN -->
<section class="py-5 bg-white">
    <div class="container py-4 text-center">
        <div class="max-w-700 mx-auto">
            <i class="fa-solid fa-quote-left text-warning fs-1 mb-3"></i>
            <h3 class="fw-bold mb-3">Mensaje del Rectorado</h3>
            <p class="text-muted fs-5 fst-italic mb-4">
                "Nuestra misión fundamental es la formación profesional de excelencia en las distintas áreas del conocimiento científico y tecnológico, preparando mujeres y hombres comprometidos con el desarrollo integral de Bolivia y Latinoamérica."
            </p>
            <div class="fw-bold text-dark">Rectorado de la Universidad Latinoamericana</div>
            <small class="text-muted">Fundada el 27 de enero de 1995 • Cochabamba, Bolivia</small>
        </div>
    </div>
</section>

<script>
document.getElementById('form-lead-hero')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const status = document.getElementById('lead-hero-status');
    const data = new FormData(form);

    status.classList.remove('d-none', 'alert-danger', 'alert-success');
    status.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-1"></i> Registrando preinscripción...';

    try {
        const resp = await fetch('api_lead.php', { method: 'POST', body: data });
        const res = await resp.json();
        if (res.ok) {
            status.className = 'small mt-2 alert alert-success p-2 text-center';
            status.innerHTML = '✔ ¡Preinscripción registrada! Un asesor de admisiones te contactará por WhatsApp.';
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

