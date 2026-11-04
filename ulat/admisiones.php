<?php
declare(strict_types=1);
$pageTitle = 'Requisitos de Admisión y Matriculación | Universidad Latinoamericana (ULAT)';
$activeNav = 'admisiones';
require_once __DIR__ . '/header.php';
?>

<div class="bg-primary text-white py-5" style="background: linear-gradient(135deg, var(--ulat-blue-dark) 0%, var(--ulat-blue-primary) 100%);">
    <div class="container text-center py-3">
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-2">Admisiones II-2026</span>
        <h1 class="display-5 fw-bold text-white mb-2">Requisitos de Admisión</h1>
        <p class="lead text-light text-opacity-75 max-w-700 mx-auto">
            Guía oficial de matriculación para bachilleres bolivianos, convalidaciones y estudiantes extranjeros.
        </p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-7">
            <!-- Requisitos Bachilleres Nacionales -->
            <div class="card card-ulat p-4 p-md-5 mb-4">
                <h3 class="fw-bold text-primary mb-3">
                    <i class="fa-solid fa-folder-open text-warning me-2"></i> Estudiantes Nuevos (Nacionales)
                </h3>
                <p class="text-secondary mb-4">
                    Todo interesado en matricularse en cualquiera de las carreras de la Universidad Latinoamericana debe presentar la siguiente documentación en la Dirección de Admisiones:
                </p>
                
                <div class="d-flex align-items-start gap-3 mb-3 p-3 bg-light rounded-3">
                    <div class="bg-primary text-white p-2 rounded-circle fs-6">1</div>
                    <div>
                        <h6 class="fw-bold mb-1">Certificado de Nacimiento Original</h6>
                        <small class="text-muted">Documento original actualizado computarizado.</small>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-3 p-3 bg-light rounded-3">
                    <div class="bg-primary text-white p-2 rounded-circle fs-6">2</div>
                    <div>
                        <h6 class="fw-bold mb-1">Fotocopia de Cédula de Identidad</h6>
                        <small class="text-muted">Fotocopia simple a color y legible de la cédula vigente.</small>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-3 p-3 bg-light rounded-3">
                    <div class="bg-primary text-white p-2 rounded-circle fs-6">3</div>
                    <div>
                        <h6 class="fw-bold mb-1">Fotocopia Legalizada del Diploma de Bachiller</h6>
                        <small class="text-muted">Legalizada ante la Dirección Departamental de Educación (DDE).</small>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-3 p-3 bg-light rounded-3">
                    <div class="bg-primary text-white p-2 rounded-circle fs-6">4</div>
                    <div>
                        <h6 class="fw-bold mb-1">4 Fotografías tamaño 3×4</h6>
                        <small class="text-muted">A color, con fondo azul y vistiendo traje formal.</small>
                    </div>
                </div>

                <div class="alert alert-warning d-flex align-items-center gap-3 mt-4 mb-0">
                    <i class="fa-solid fa-triangle-exclamation fs-3 text-warning"></i>
                    <div class="small">
                        <strong>Presentación obligatoria:</strong> Toda la documentación debe ser presentada en un <strong>folder tamaño oficio color amarillo</strong> en Secretaría General de la universidad.
                    </div>
                </div>
            </div>

            <!-- Estudiantes Extranjeros -->
            <div class="card card-ulat p-4 p-md-5 mb-4 border-start border-4 border-info">
                <h4 class="fw-bold text-dark mb-3">
                    <i class="fa-solid fa-earth-americas text-info me-2"></i> Estudiantes Extranjeros
                </h4>
                <p class="text-secondary small mb-3">
                    Para postulantes internacionales que deseen cursar estudios en Medicina u otras carreras de la ULAT:
                </p>
                <ul class="text-secondary small mb-0">
                    <li class="mb-2">Certificado de Nacimiento legalizado por Cancillería o apostilla de La Haya.</li>
                    <li class="mb-2">Fotocopia de Pasaporte o Documento de Identidad de su país con visa de estudiante correspondiente.</li>
                    <li class="mb-2">Diploma o Certificado de Estudios Secundarios convalidado ante el Ministerio de Educación de Bolivia.</li>
                    <li>4 fotografías 3×4 con fondo azul de traje.</li>
                </ul>
            </div>
        </div>

        <!-- Formulario de Preinscripción Formal -->
        <div class="col-lg-5" id="preinscripcion">
            <div class="card card-ulat p-4 p-md-5 bg-white border-top border-4 border-warning shadow">
                <h4 class="fw-bold text-primary mb-2">Formulario de Admisión</h4>
                <p class="small text-muted mb-4">
                    Inicia tu proceso de matriculación online. Un asesor te responderá con la cotización de aranceles y guía personalizada:
                </p>

                <form id="form-admision-full" action="api_lead.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Nombre y Apellidos</label>
                        <input type="text" name="nombre" class="form-control" placeholder="Ej: Fernando Quiroga" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Número de Celular o WhatsApp</label>
                        <input type="tel" name="telefono" class="form-control" placeholder="Ej: 71234567" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Correo Electrónico</label>
                        <input type="email" name="email" class="form-control" placeholder="tu-correo@ejemplo.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">Carrera a la que Postulas</label>
                        <select name="carrera" class="form-select" required>
                            <option value="">Selecciona una carrera...</option>
                            <?php foreach ($carrerasCatalog as $slug => $c): ?>
                                <option value="<?= htmlspecialchars($c['nombre']) ?>"><?= htmlspecialchars($c['nombre']) ?> (<?= $c['grado'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-secondary">¿Tienes alguna consulta o beneficio previo?</label>
                        <textarea name="notas" class="form-control" rows="3" placeholder="Ej: Me interesa saber más sobre el Plan Uno a Uno o la Beca Excelencia..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-ulat-gold w-100 py-3 fw-bold">
                        <i class="fa-solid fa-paper-plane me-2"></i> Enviar Solicitud de Admisión
                    </button>
                    <div id="admision-status" class="small mt-3 text-center d-none"></div>
                </form>

                <div class="mt-4 pt-3 border-top text-center">
                    <small class="text-muted d-block mb-2">¿Prefieres atención telefónica o presencial?</small>
                    <div class="fw-bold text-primary"><i class="fa-solid fa-phone me-1"></i> (4) 4509488 - 4221004</div>
                    <div class="small text-muted">Calle Uruguay #E-836 entre 16 de Julio y Oquendo</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('form-admision-full')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const status = document.getElementById('admision-status');
    const data = new FormData(form);

    status.classList.remove('d-none', 'alert-danger', 'alert-success');
    status.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-1"></i> Procesando tu registro...';

    try {
        const resp = await fetch('api_lead.php', { method: 'POST', body: data });
        const res = await resp.json();
        if (res.ok) {
            status.className = 'small mt-3 alert alert-success p-3 text-start';
            status.innerHTML = `
                <div class="fw-bold mb-1">✔ ¡Preinscripción registrada con éxito!</div>
                <div>${res.mensaje}</div>
                <a href="${res.whatsapp_directo}" target="_blank" class="btn btn-success btn-sm rounded-pill mt-2 w-100">
                    <i class="fa-brands fa-whatsapp me-1"></i> Contactar ahora por WhatsApp
                </a>
            `;
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

