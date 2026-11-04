<?php
declare(strict_types=1);
$pageTitle = 'Universidad Latinoamericana (ULAT) | Educación Superior en Cochabamba';
$activeNav = 'inicio';
require_once __DIR__ . '/header.php';
?>

<!-- HERO EDITORIAL ASIMÉTRICO -->
<section class="ulat-hero">
    <div class="container py-4">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="ulat-eyebrow-light">Admisiones II–2026 · Inicio de Clases 03.08</span>
                <h1 class="font-editorial display-4 text-white mb-4" style="line-height: 1.08;">
                    Formación universitaria con rigor científico y visión humana.
                </h1>
                <p class="mb-5" style="color: rgba(255,255,255,0.68); font-size: 1.02rem; max-width: 560px; line-height: 1.7;">
                    Fundada en 1995 en Cochabamba, Bolivia. Quince programas de pregrado acreditados en Salud, Ingeniería y Ciencias Jurídicas y Económicas.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="carreras.php" class="btn btn-ulat-gold px-4 py-2.5">
                        Explorar las 15 Carreras →
                    </a>
                    <a href="admisiones.php" class="btn btn-ulat-ghost-light px-4 py-2.5">
                        Requisitos de Admisión
                    </a>
                    <button type="button" class="btn btn-ulat-ghost-light px-4 py-2.5" onclick="window.enviarChipChat('Hola, quisiera orientación sobre las carreras y becas disponibles')">
                        Orientación en Línea
                    </button>
                </div>

                <!-- Métricas Minimalistas -->
                <div class="row g-4 mt-5 pt-4" style="border-top: 1px solid rgba(255,255,255,0.12); max-width: 540px;">
                    <div class="col-4">
                        <div class="font-editorial h2 text-white mb-0">1995</div>
                        <div class="font-mono-code text-white-50" style="font-size: 0.65rem;">Fundación</div>
                    </div>
                    <div class="col-4">
                        <div class="font-editorial h2 text-white mb-0">15</div>
                        <div class="font-mono-code text-white-50" style="font-size: 0.65rem;">Carreras Oficiales</div>
                    </div>
                    <div class="col-4">
                        <div class="font-editorial h2 text-white mb-0">02</div>
                        <div class="font-mono-code text-white-50" style="font-size: 0.65rem;">Campus en Cbba</div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="p-4 p-md-5 bg-white text-dark" style="border-radius: 4px; border: 1px solid var(--ulat-line);">
                    <span class="ulat-eyebrow">Registro Directo</span>
                    <h3 class="h4 mb-2">Solicitud de Admisión</h3>
                    <p class="small text-muted mb-4">
                        Recibe el plan de estudios, aranceles semestrales y beneficios vigentes en tu WhatsApp.
                    </p>
                    <form id="form-lead-hero" action="api_lead.php" method="POST">
                        <div class="mb-3">
                            <label class="font-mono-code text-muted d-block mb-1">01 · Nombre Completo</label>
                            <input type="text" name="nombre" class="form-control" placeholder=" María Fernanda Rojas" required>
                        </div>
                        <div class="mb-3">
                            <label class="font-mono-code text-muted d-block mb-1">02 · Teléfono / WhatsApp</label>
                            <input type="tel" name="telefono" class="form-control" placeholder="79795778" required>
                        </div>
                        <div class="mb-4">
                            <label class="font-mono-code text-muted d-block mb-1">03 · Carrera de Interés</label>
                            <select name="carrera" class="form-select" required>
                                <option value="">Seleccionar programa académico...</option>
                                <?php foreach ($carrerasCatalog as $slug => $c): ?>
                                    <option value="<?= htmlspecialchars($c['nombre']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-ulat-primary w-100 py-2.5">
                            Solicitar Información Oficial →
                        </button>
                        <div id="lead-hero-status" class="small mt-3 text-center d-none"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECCIÓN: GRILLA EDITORIAL SUIZA DE LAS 3 FACULTADES -->
<section class="py-5 bg-white" style="border-bottom: 1px solid var(--ulat-line);">
    <div class="container py-5">
        <div class="row align-items-end mb-5 pb-3" style="border-bottom: 1px solid var(--ulat-line);">
            <div class="col-lg-7">
                <span class="ulat-eyebrow">Estructura Académica</span>
                <h2 class="font-editorial display-5 mb-0">Tres Facultades, Quince Carreras</h2>
            </div>
            <div class="col-lg-5 text-lg-end mt-3 mt-lg-0">
                <a href="carreras.php" class="btn btn-ulat-outline">
                    Catálogo Académico Completo (15) →
                </a>
            </div>
        </div>

        <div class="row g-5">
            <!-- 01 Salud -->
            <div class="col-lg-4">
                <div class="pe-lg-3 h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="academic-index">FACULTAD 01</span>
                        <span class="academic-index">06 PROGRAMAS</span>
                    </div>
                    <h3 class="h4 mb-2">Ciencias de la Salud</h3>
                    <p class="small text-muted mb-4">
                        Formación clínica y biomédica con laboratorios propios, anfiteatro anatómico y convenios hospitalarios para Internado Rotatorio.
                    </p>
                    <div class="mb-4 flex-grow-1" style="border-top: 1px solid var(--ulat-line);">
                        <a href="carrera.php?id=medicina" class="career-link-row">
                            <span>Licenciatura en Medicina</span>
                            <span class="academic-index">12 SEM →</span>
                        </a>
                        <a href="carrera.php?id=odontologia" class="career-link-row">
                            <span>Licenciatura en Odontología</span>
                            <span class="academic-index">10 SEM →</span>
                        </a>
                        <a href="carrera.php?id=enfermeria" class="career-link-row">
                            <span>Licenciatura en Enfermería</span>
                            <span class="academic-index">10 SEM →</span>
                        </a>
                        <a href="carrera.php?id=bioquimica-farmacia" class="career-link-row">
                            <span>Bioquímica y Farmacia</span>
                            <span class="academic-index">09 SEM →</span>
                        </a>
                        <a href="carrera.php?id=optica-optometria" class="career-link-row">
                            <span>Óptica y Optometría</span>
                            <span class="academic-index">09 SEM →</span>
                        </a>
                        <a href="carrera.php?id=protesis-dental" class="career-link-row">
                            <span>T.S. en Prótesis Dental</span>
                            <span class="academic-index">05 SEM →</span>
                        </a>
                    </div>
                    <a href="facultades.php#salud" class="font-mono-code text-decoration-none text-dark">
                        Ver Facultad de Salud ↗
                    </a>
                </div>
            </div>

            <!-- 02 Tecnología -->
            <div class="col-lg-4">
                <div class="px-lg-2 h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="academic-index">FACULTAD 02</span>
                        <span class="academic-index">05 INGENIERÍAS</span>
                    </div>
                    <h3 class="h4 mb-2">Ciencia y Tecnología</h3>
                    <p class="small text-muted mb-4">
                        Ingenierías orientadas al desarrollo de software, infraestructura civil, geodesia satelital, autotrónica en Campus Norte y diseño digital.
                    </p>
                    <div class="mb-4 flex-grow-1" style="border-top: 1px solid var(--ulat-line);">
                        <a href="carrera.php?id=ingenieria-de-sistemas" class="career-link-row">
                            <span>Ingeniería de Sistemas</span>
                            <span class="academic-index">09 SEM →</span>
                        </a>
                        <a href="carrera.php?id=ingenieria-civil" class="career-link-row">
                            <span>Ingeniería Civil (5 Menciones)</span>
                            <span class="academic-index">10 SEM →</span>
                        </a>
                        <a href="carrera.php?id=ingenieria-geodesia-topografia" class="career-link-row">
                            <span>Ing. en Geodesia y Topografía</span>
                            <span class="academic-index">10 SEM →</span>
                        </a>
                        <a href="carrera.php?id=ingenieria-mecanica-automotriz" class="career-link-row">
                            <span>Ing. Mecánica Automotriz y Elec.</span>
                            <span class="academic-index">08 SEM →</span>
                        </a>
                        <a href="carrera.php?id=ingenieria-comercio-marketing" class="career-link-row">
                            <span>Ing. Comercio y Diseño Digital</span>
                            <span class="academic-index">09 SEM →</span>
                        </a>
                    </div>
                    <a href="facultades.php#tecnologia" class="font-mono-code text-decoration-none text-dark">
                        Ver Facultad de Tecnología ↗
                    </a>
                </div>
            </div>

            <!-- 03 Económicas y Jurídicas -->
            <div class="col-lg-4">
                <div class="ps-lg-3 h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="academic-index">FACULTAD 03</span>
                        <span class="academic-index">04 PROGRAMAS</span>
                    </div>
                    <h3 class="h4 mb-2">Económicas y Jurídicas</h3>
                    <p class="small text-muted mb-4">
                        Preparación rigurosa en ciencias jurídicas, práctica forense, auditoría financiera y tributaria, contaduría general y ciencias de la educación.
                    </p>
                    <div class="mb-4 flex-grow-1" style="border-top: 1px solid var(--ulat-line);">
                        <a href="carrera.php?id=derecho" class="career-link-row">
                            <span>Licenciatura en Derecho</span>
                            <span class="academic-index">09 SEM →</span>
                        </a>
                        <a href="carrera.php?id=auditoria" class="career-link-row">
                            <span>Licenciatura en Auditoría (CPA)</span>
                            <span class="academic-index">09 SEM →</span>
                        </a>
                        <a href="carrera.php?id=ciencias-de-la-educacion" class="career-link-row">
                            <span>Ciencias de la Educación</span>
                            <span class="academic-index">09 SEM →</span>
                        </a>
                        <a href="carrera.php?id=contador-general" class="career-link-row">
                            <span>T.S. Contador General</span>
                            <span class="academic-index">06 SEM →</span>
                        </a>
                    </div>
                    <a href="facultades.php#economicas" class="font-mono-code text-decoration-none text-dark">
                        Ver Ciencias Económicas ↗
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- SECCIÓN: BECAS Y PLAN UNO A UNO (COMPOSICIÓN ASIMÉTRICA MINIMALISTA) -->
<section class="py-5">
    <div class="container py-5">
        <div class="row g-5 align-items-start">
            <div class="col-lg-5">
                <span class="ulat-eyebrow">Incentivos Estudiantiles</span>
                <h2 class="font-editorial display-5 mb-3">
                    Acceso y continuidad académica.
                </h2>
                <p class="text-muted mb-4" style="font-size: 0.95rem;">
                    Respaldamos el mérito académico y la economía familiar mediante el programa solidario ULAT–ILAT y nuestro reglamento de becas ministeriales.
                </p>
                <div class="d-flex gap-3">
                    <a href="becas.php" class="btn btn-ulat-primary">Programa de Becas →</a>
                    <a href="reglamentos.php" class="btn btn-ulat-outline">Reglamentos PDF</a>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="swiss-grid-row pt-0">
                    <div class="row align-items-center g-3">
                        <div class="col-sm-3">
                            <div class="font-editorial display-4" style="color: var(--ulat-ink);">-50%</div>
                            <span class="academic-index">PLAN UNO A UNO</span>
                        </div>
                        <div class="col-sm-9">
                            <h4 class="h5 mb-2">Reducción del 50% en Cuota Mensual por Recomendación</h4>
                            <p class="small text-muted mb-0">
                                Todo estudiante regular que invite a un alumno nuevo a matricularse en cualquiera de nuestras carreras o diplomados cancela únicamente la mitad de su cuota mensual por cada nuevo inscrito.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="swiss-grid-row">
                    <div class="row align-items-center g-3">
                        <div class="col-sm-3">
                            <div class="font-editorial display-4" style="color: var(--ulat-ink);">100%</div>
                            <span class="academic-index">BECA EXCELENCIA</span>
                        </div>
                        <div class="col-sm-9">
                            <h4 class="h5 mb-2">Liberación Total por Excelencia Académica</h4>
                            <p class="small text-muted mb-0">
                                Estudiantes regulares con promedio general igual o superior a 95 puntos quedan liberados del 100% del pago de cuotas semestrales conforme al Reglamento Oficial de Becas.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="swiss-grid-row">
                    <div class="row align-items-center g-3">
                        <div class="col-sm-3">
                            <div class="font-editorial display-4" style="color: var(--ulat-ink);">04</div>
                            <span class="academic-index">DOCUMENTOS</span>
                        </div>
                        <div class="col-sm-9">
                            <h4 class="h5 mb-2">Requisitos de Admisión Simplificados</h4>
                            <p class="small text-muted mb-0">
                                Certificado de nacimiento original, fotocopia de C.I., fotocopia legalizada del Diploma de Bachiller y 4 fotografías 3×4 fondo azul (traje formal) en folder oficio amarillo.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CITA EDITORIAL DEL RECTORADO -->
<section class="py-5 bg-white" style="border-top: 1px solid var(--ulat-line);">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <span class="ulat-eyebrow">Misión Institucional</span>
                <blockquote class="font-editorial display-6 my-4" style="color: var(--ulat-ink); line-height: 1.38; font-style: italic;">
                    “Formar profesionales idóneos en las distintas áreas del conocimiento científico y tecnológico, comprometidos con el desarrollo integral de Bolivia y Latinoamérica.”
                </blockquote>
                <div class="font-mono-code text-muted mt-3">Rectorado · Universidad Latinoamericana · R.M. Nº 241/02 – 590/07</div>
            </div>
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
            status.className = 'small mt-3 alert alert-success p-2 text-center';
            status.innerHTML = 'Solicitud registrada. Admisiones se comunicará contigo por WhatsApp.';
            form.reset();
        } else {
            status.className = 'small mt-3 alert alert-danger p-2 text-center';
            status.innerHTML = res.error || 'Ocurrió un error al enviar.';
        }
    } catch(err) {
        status.className = 'small mt-3 alert alert-danger p-2 text-center';
        status.innerHTML = 'Error de conexión.';
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
