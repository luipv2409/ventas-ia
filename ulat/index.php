<?php
declare(strict_types=1);
$pageTitle = 'Universidad Latinoamericana (ULAT) | Educación Superior en Cochabamba';
$activeNav = 'inicio';
require_once __DIR__ . '/header.php';
?>

<!-- HERO BANNER PRINCIPAL -->
<section class="ulat-hero">
    <div class="container py-3">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 mb-3 rounded" style="background-color: rgba(212, 154, 53, 0.18); border: 1px solid rgba(212, 154, 53, 0.45); color: #f8d386; font-size: 0.78rem; font-weight: 600; letter-spacing: 0.05em;">
                    <span>ADMISIONES GESTIÓN II-2026 · INICIO DE CLASES 3 DE AGOSTO</span>
                </div>
                <h1 class="display-5 fw-bold mb-3 text-white" style="line-height: 1.15;">
                    Formación profesional con rigor científico y compromiso humano
                </h1>
                <p class="lead mb-4 text-white-50" style="font-size: 1.06rem; line-height: 1.65;">
                    Desde 1995 en el corazón de Cochabamba. Quince carreras a nivel Licenciatura y Técnico Superior con títulos en Provisión Nacional, laboratorios propios y convenios institucionales.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="carreras.php" class="btn btn-ulat-gold px-4 py-2 d-inline-flex align-items-center gap-2">
                        <span>Explorar las 15 Carreras</span>
                        <i data-lucide="arrow-right" class="lucide-sm"></i>
                    </a>
                    <a href="admisiones.php" class="btn btn-outline-light px-4 py-2" style="border-radius: 6px; font-size: 0.875rem; font-weight: 600;">
                        Requisitos de Admisión
                    </a>
                    <button type="button" class="btn btn-light px-4 py-2 d-inline-flex align-items-center gap-2" style="border-radius: 6px; font-size: 0.875rem; font-weight: 600; color: var(--ulat-blue-dark);" onclick="window.preguntarAlChat('Hola, quisiera orientación sobre las carreras y becas disponibles')">
                        <i data-lucide="message-square" class="lucide-sm" style="color: var(--ulat-gold-dark);"></i>
                        <span>Hablar con Guía ULAT</span>
                    </button>
                </div>

                <!-- Indicadores Institucionales Sobrios -->
                <div class="row g-3 mt-4 pt-4 border-top border-light border-opacity-10">
                    <div class="col-4">
                        <div class="h4 fw-bold text-white mb-0">1995</div>
                        <div class="small text-white-50">Año de Fundación</div>
                    </div>
                    <div class="col-4">
                        <div class="h4 fw-bold text-white mb-0">15 Carreras</div>
                        <div class="small text-white-50">3 Facultades Oficiales</div>
                    </div>
                    <div class="col-4">
                        <div class="h4 fw-bold text-white mb-0">2 Campus</div>
                        <div class="small text-white-50">Sede Central y Norte</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card card-ulat p-4 p-md-4 bg-white text-dark shadow-lg" style="border-top: 3px solid var(--ulat-gold);">
                    <div class="d-flex justify-content-between align-items-start mb-3 pb-2 border-bottom">
                        <div>
                            <span class="ulat-eyebrow mb-0">Solicitud de Información</span>
                            <h5 class="fw-bold mb-0">Preinscripción en Línea</h5>
                        </div>
                        <div class="icon-frame-gold">
                            <i data-lucide="book-open" class="lucide"></i>
                        </div>
                    </div>
                    <form id="form-lead-hero" action="api_lead.php" method="POST">
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-secondary mb-1">Nombre Completo</label>
                            <input type="text" name="nombre" class="form-control form-control-sm" style="border-radius: 5px;" placeholder="Ej. María Fernanda Rojas" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold text-secondary mb-1">Teléfono o WhatsApp</label>
                            <input type="tel" name="telefono" class="form-control form-control-sm" style="border-radius: 5px;" placeholder="Ej. 79795778" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Carrera de Interés</label>
                            <select name="carrera" class="form-select form-select-sm" style="border-radius: 5px;" required>
                                <option value="">Selecciona una carrera...</option>
                                <?php foreach ($carrerasCatalog as $slug => $c): ?>
                                    <option value="<?= htmlspecialchars($c['nombre']) ?>"><?= htmlspecialchars($c['nombre']) ?> (<?= $c['duracion'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-ulat-primary w-100 py-2 d-inline-flex align-items-center justify-content-center gap-2">
                            <span>Solicitar Aranceles y Plan de Estudios</span>
                            <i data-lucide="arrow-up-right" class="lucide-sm"></i>
                        </button>
                        <div id="lead-hero-status" class="small mt-2 text-center d-none"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECCIÓN: LAS 3 FACULTADES (DISEÑO EDITORIAL ARQUITECTÓNICO) -->
<section class="py-5 bg-white border-bottom">
    <div class="container py-4">
        <div class="row align-items-end mb-5">
            <div class="col-lg-7">
                <span class="ulat-eyebrow">Estructura Académica</span>
                <h2 class="display-6 fw-bold mb-0">Tres Facultades, Quince Programas de Pregrado</h2>
            </div>
            <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                <a href="carreras.php" class="btn btn-ulat-outline d-inline-flex align-items-center gap-2">
                    <span>Ver Catálogo Completo de Carreras</span>
                    <i data-lucide="arrow-right" class="lucide-sm"></i>
                </a>
            </div>
        </div>

        <div class="row g-4">
            <!-- Facultad I: Salud -->
            <div class="col-lg-4 col-md-6">
                <div class="card card-ulat h-100 p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <span class="academic-index">FACULTAD I</span>
                        <span class="small fw-semibold text-muted">6 Carreras</span>
                    </div>
                    <h3 class="h4 fw-bold mb-2">Ciencias de la Salud</h3>
                    <p class="text-secondary small mb-3" style="line-height: 1.6;">
                        Formación clínica y biomédica con laboratorios especializados, anfiteatro anatómico y convenios hospitalarios para prácticas e internado rotatorio.
                    </p>
                    <ul class="semester-list mb-4 flex-grow-1 px-0">
                        <li><a href="carrera.php?id=medicina" class="text-decoration-none text-dark fw-medium">Licenciatura en Medicina (12 sem.)</a></li>
                        <li><a href="carrera.php?id=odontologia" class="text-decoration-none text-dark fw-medium">Licenciatura en Odontología (10 sem.)</a></li>
                        <li><a href="carrera.php?id=enfermeria" class="text-decoration-none text-dark fw-medium">Licenciatura en Enfermería (10 sem.)</a></li>
                        <li><a href="carrera.php?id=bioquimica-farmacia" class="text-decoration-none text-dark fw-medium">Bioquímica y Farmacia (9 sem.)</a></li>
                        <li><a href="carrera.php?id=optica-optometria" class="text-decoration-none text-dark fw-medium">Óptica y Optometría (9 sem.)</a></li>
                        <li><a href="carrera.php?id=protesis-dental" class="text-decoration-none text-dark fw-medium">T.S. en Prótesis Dental (5 sem.)</a></li>
                    </ul>
                    <a href="facultades.php#salud" class="btn btn-ulat-outline btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2">
                        <span>Explorar Facultad de Salud</span>
                        <i data-lucide="arrow-right" class="lucide-sm"></i>
                    </a>
                </div>
            </div>

            <!-- Facultad II: Tecnología -->
            <div class="col-lg-4 col-md-6">
                <div class="card card-ulat h-100 p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <span class="academic-index">FACULTAD II</span>
                        <span class="small fw-semibold text-muted">5 Ingenierías</span>
                    </div>
                    <h3 class="h4 fw-bold mb-2">Ciencia y Tecnología</h3>
                    <p class="text-secondary small mb-3" style="line-height: 1.6;">
                        Ingenierías orientadas al desarrollo de software e inteligencia artificial, infraestructura civil, geodesia satelital, autotrónica y comercio digital.
                    </p>
                    <ul class="semester-list mb-4 flex-grow-1 px-0">
                        <li><a href="carrera.php?id=ingenieria-de-sistemas" class="text-decoration-none text-dark fw-medium">Ingeniería de Sistemas (9 sem.)</a></li>
                        <li><a href="carrera.php?id=ingenieria-civil" class="text-decoration-none text-dark fw-medium">Ingeniería Civil · 5 Menciones (10 sem.)</a></li>
                        <li><a href="carrera.php?id=ingenieria-geodesia-topografia" class="text-decoration-none text-dark fw-medium">Ing. en Geodesia y Topografía (10 sem.)</a></li>
                        <li><a href="carrera.php?id=ingenieria-mecanica-automotriz" class="text-decoration-none text-dark fw-medium">Ing. Mecánica Automotriz y Electrónica (8 sem.)</a></li>
                        <li><a href="carrera.php?id=ingenieria-comercio-marketing" class="text-decoration-none text-dark fw-medium">Ing. en Comercio, Marketing y Diseño Digital (9 sem.)</a></li>
                    </ul>
                    <a href="facultades.php#tecnologia" class="btn btn-ulat-outline btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2">
                        <span>Explorar Facultad de Tecnología</span>
                        <i data-lucide="arrow-right" class="lucide-sm"></i>
                    </a>
                </div>
            </div>

            <!-- Facultad III: Económicas y Jurídicas -->
            <div class="col-lg-4 col-md-6">
                <div class="card card-ulat h-100 p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <span class="academic-index">FACULTAD III</span>
                        <span class="small fw-semibold text-muted">4 Carreras</span>
                    </div>
                    <h3 class="h4 fw-bold mb-2">Ciencias Económicas y Jurídicas</h3>
                    <p class="text-secondary small mb-3" style="line-height: 1.6;">
                        Preparación rigurosa en ciencias jurídicas, práctica forense, auditoría financiera y tributaria, contaduría general y gestión educativa.
                    </p>
                    <ul class="semester-list mb-4 flex-grow-1 px-0">
                        <li><a href="carrera.php?id=derecho" class="text-decoration-none text-dark fw-medium">Licenciatura en Derecho (9 sem.)</a></li>
                        <li><a href="carrera.php?id=auditoria" class="text-decoration-none text-dark fw-medium">Licenciatura en Auditoría / CPA (9 sem.)</a></li>
                        <li><a href="carrera.php?id=ciencias-de-la-educacion" class="text-decoration-none text-dark fw-medium">Licenciatura en Ciencias de la Educación (9 sem.)</a></li>
                        <li><a href="carrera.php?id=contador-general" class="text-decoration-none text-dark fw-medium">T.S. Contador General (6 sem.)</a></li>
                    </ul>
                    <a href="facultades.php#economicas" class="btn btn-ulat-outline btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2">
                        <span>Explorar Ciencias Económicas</span>
                        <i data-lucide="arrow-right" class="lucide-sm"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECCIÓN: BENEFICIOS ECONÓMICOS Y VIDA PRÁCTICA -->
<section class="py-5">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <span class="ulat-eyebrow">Apoyo Económico al Estudiante</span>
                <h2 class="display-6 fw-bold mb-3">
                    Plan Uno a Uno y Becas de Excelencia
                </h2>
                <p class="text-secondary mb-4" style="line-height: 1.65;">
                    La Universidad Latinoamericana y el ILAT impulsan programas de incentivo directo para respaldar la continuidad académica de sus estudiantes regulares y nuevos postulantes.
                </p>

                <div class="card card-ulat p-4 mb-3">
                    <div class="d-flex align-items-start gap-3">
                        <div class="icon-frame-gold">
                            <i data-lucide="users" class="lucide"></i>
                        </div>
                        <div>
                            <h5 class="h6 fw-bold mb-1">Plan Uno a Uno (50% de Descuento en tu Cuota)</h5>
                            <p class="text-secondary small mb-0" style="line-height: 1.6;">
                                Por cada estudiante nuevo que recomiendes y se matricule en cualquiera de nuestras carreras o diplomados, cancelas únicamente el 50% de tu cuota mensual.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="card card-ulat p-4 mb-4">
                    <div class="d-flex align-items-start gap-3">
                        <div class="icon-frame">
                            <i data-lucide="award" class="lucide"></i>
                        </div>
                        <div>
                            <h5 class="h6 fw-bold mb-1">Beca por Excelencia Académica (Cobertura del 100%)</h5>
                            <p class="text-secondary small mb-0" style="line-height: 1.6;">
                                Estudiantes con promedio general igual o superior al 95% acceden a la liberación total de matrícula y cuotas semestrales conforme al Reglamento de Becas.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-3">
                    <a href="becas.php" class="btn btn-ulat-primary d-inline-flex align-items-center gap-2">
                        <span>Ver Modalidades de Beca</span>
                        <i data-lucide="arrow-right" class="lucide-sm"></i>
                    </a>
                    <a href="admisiones.php" class="btn btn-ulat-outline">
                        Requisitos de Inscripción
                    </a>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card card-ulat overflow-hidden">
                    <div class="row g-0 align-items-stretch">
                        <div class="col-md-5">
                            <img src="../assets/ulat/banner_6_768x1155.jpg" alt="Prácticas de campo ULAT" class="w-100 h-100" style="object-fit: cover; min-height: 280px;">
                        </div>
                        <div class="col-md-7 p-4 d-flex flex-column justify-content-between">
                            <div>
                                <span class="ulat-eyebrow">Documentación de Ingreso</span>
                                <h4 class="h5 fw-bold mb-3">Requisitos de Admisión</h4>
                                <p class="text-secondary small mb-3">
                                    Presentar en Secretaría General en un <strong>folder tamaño oficio color amarillo</strong>:
                                </p>
                                <ul class="semester-list px-0 mb-3">
                                    <li>Certificado de Nacimiento Original</li>
                                    <li>Fotocopia de Cédula de Identidad vigente</li>
                                    <li>Fotocopia Legalizada del Diploma de Bachiller</li>
                                    <li>4 fotografías 3×4 fondo azul (traje formal)</li>
                                </ul>
                            </div>
                            <a href="admisiones.php" class="btn btn-ulat-outline btn-sm w-100">
                                Ver Convalidaciones y Postulantes Extranjeros
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECCIÓN: MENSAJE DEL RECTORADO (ESTILO EDITORIAL SERIF) -->
<section class="py-5 bg-white border-top">
    <div class="container py-3 text-center">
        <div class="mx-auto" style="max-width: 740px;">
            <span class="ulat-eyebrow">Filosofía Institucional</span>
            <blockquote class="font-editorial fs-4 text-dark my-3" style="line-height: 1.55; font-style: italic;">
                “Nuestra misión fundamental es la formación profesional de excelencia en las distintas áreas del conocimiento científico y tecnológico, preparando mujeres y hombres comprometidos con el desarrollo integral de Bolivia y Latinoamérica.”
            </blockquote>
            <div class="fw-bold text-dark small text-uppercase mt-3" style="letter-spacing: 0.06em;">Rectorado · Universidad Latinoamericana</div>
            <div class="small text-muted">Fundada el 27 de enero de 1995 · Cochabamba, Bolivia</div>
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
    status.innerHTML = 'Registrando solicitud...';

    try {
        const resp = await fetch('api_lead.php', { method: 'POST', body: data });
        const res = await resp.json();
        if (res.ok) {
            status.className = 'small mt-2 alert alert-success p-2 text-center';
            status.innerHTML = 'Solicitud registrada. Nuestro equipo de admisiones se comunicará contigo por WhatsApp.';
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
