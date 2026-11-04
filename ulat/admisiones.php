<?php
declare(strict_types=1);
$pageTitle = 'Requisitos de Admisión y Cronograma | Universidad Latinoamericana (ULAT)';
$activeNav = 'admisiones';
require_once __DIR__ . '/header.php';
?>

<div class="ulat-page-header">
    <div class="container py-2">
        <span class="ulat-eyebrow-light">Proceso de Matriculación II-2026</span>
        <h1 class="display-6 fw-bold text-white mb-2">Requisitos de Admisión y Cronograma</h1>
        <p class="mb-0 text-white-50" style="max-width: 680px; font-size: 0.98rem;">
            Documentación oficial para bachilleres nacionales, estudiantes de traspaso, postulantes extranjeros y calendario académico vigente.
        </p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-7">

            <!-- Cronograma Gestión II-2026 -->
            <div class="card card-ulat p-4 p-md-5 mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <div>
                        <span class="ulat-eyebrow mb-1">Calendario Académico Oficial</span>
                        <h3 class="h4 fw-bold mb-0">Cronograma de Inscripciones II-2026</h3>
                    </div>
                    <div class="icon-frame-gold">
                        <i data-lucide="calendar" class="lucide"></i>
                    </div>
                </div>
                <div class="row g-3 small">
                    <div class="col-md-4">
                        <div class="p-3 rounded border h-100" style="background-color: #f8fafc;">
                            <div class="fw-bold text-dark mb-1">1ra Opción</div>
                            <div class="text-primary fw-semibold mb-1">06 Jul – 01 Ago</div>
                            <div class="text-muted">Estudiantes nuevos y regulares</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded border h-100" style="background-color: #f8fafc;">
                            <div class="fw-bold text-dark mb-1">2da Opción</div>
                            <div class="text-primary fw-semibold mb-1">03 Ago – 08 Ago</div>
                            <div class="text-muted">Rezagados de primera opción</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="p-3 rounded border h-100" style="background-color: #f8fafc;">
                            <div class="fw-bold text-dark mb-1">3ra Opción</div>
                            <div class="text-primary fw-semibold mb-1">10 Ago – 15 Ago</div>
                            <div class="text-muted">Casos especiales y toma de materias</div>
                        </div>
                    </div>
                </div>
                <div class="mt-3 pt-3 border-top d-flex flex-wrap justify-content-between small text-secondary">
                    <span><strong>Inicio de Clases:</strong> Lunes 03 de Agosto</span>
                    <span><strong>Planes Disponibles:</strong> Plan Regular y Plan S.O.S.</span>
                </div>
            </div>

            <!-- Requisitos Bachilleres Nacionales -->
            <div class="card card-ulat p-4 p-md-5 mb-4">
                <span class="ulat-eyebrow mb-1">Documentación Obligatoria</span>
                <h3 class="h4 fw-bold mb-3 pb-3 border-bottom">Estudiantes Nuevos (Nacionales)</h3>
                <p class="text-secondary small mb-4">
                    Presentar los siguientes documentos en la Dirección de Admisiones de la ULAT:
                </p>

                <div class="d-flex align-items-start gap-3 mb-3 pb-3 border-bottom">
                    <span class="academic-index">01</span>
                    <div>
                        <h6 class="fw-bold mb-1">Certificado de Nacimiento Original</h6>
                        <small class="text-muted">Documento original actualizado computarizado emitido por el SERECI.</small>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-3 pb-3 border-bottom">
                    <span class="academic-index">02</span>
                    <div>
                        <h6 class="fw-bold mb-1">Fotocopia de Cédula de Identidad</h6>
                        <small class="text-muted">Fotocopia simple a color y legible de la cédula de identidad vigente.</small>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-3 pb-3 border-bottom">
                    <span class="academic-index">03</span>
                    <div>
                        <h6 class="fw-bold mb-1">Fotocopia Legalizada del Diploma de Bachiller</h6>
                        <small class="text-muted">Legalizada ante la Dirección Departamental de Educación (DDE) o SEDUCA.</small>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <span class="academic-index">04</span>
                    <div>
                        <h6 class="fw-bold mb-1">4 Fotografías tamaño 3×4</h6>
                        <small class="text-muted">A color, con fondo azul y vistiendo traje formal.</small>
                    </div>
                </div>

                <div class="p-3 rounded small" style="background-color: var(--ulat-gold-soft); border: 1px solid rgba(212, 154, 53, 0.4);">
                    <strong>Presentación en Secretaría General:</strong> Toda la documentación debe entregarse en un <strong>folder tamaño oficio color amarillo</strong>.
                </div>
            </div>

            <!-- Estudiantes Extranjeros -->
            <div class="card card-ulat p-4 p-md-5">
                <span class="ulat-eyebrow mb-1">Admisión Internacional</span>
                <h4 class="h5 fw-bold mb-3 pb-2 border-bottom">Requisitos para Estudiantes Extranjeros</h4>
                <ul class="semester-list px-0 mb-0">
                    <li>Certificado de Nacimiento original apostillado (Convenio de La Haya) o legalizado por Cancillería.</li>
                    <li>Fotocopia de Pasaporte o Cédula de Identidad de Extranjero con visa de estudiante vigente.</li>
                    <li>Diploma de Bachiller o Certificado de Estudios Secundarios apostillado y homologado por la DDE.</li>
                    <li>4 fotografías tamaño 3×4 a color con fondo azul y traje formal (en folder oficio amarillo).</li>
                </ul>
            </div>
        </div>

        <!-- Formulario de Preinscripción Formal -->
        <div class="col-lg-5" id="preinscripcion">
            <div class="sticky-top" style="top: 90px;">
                <div class="card card-ulat p-4 p-md-5 bg-white" style="border-top: 3px solid var(--ulat-gold);">
                    <span class="ulat-eyebrow mb-1">Registro en Línea</span>
                    <h4 class="fw-bold mb-2">Formulario de Admisión</h4>
                    <p class="small text-muted mb-4">
                        Completa tus datos para iniciar tu preinscripción y recibir asesoría directa de Admisiones con el detalle de aranceles:
                    </p>

                    <form id="form-admision-full" action="api_lead.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Nombre y Apellidos</label>
                            <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Ej. Fernando Quiroga" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Celular o WhatsApp</label>
                            <input type="tel" name="telefono" class="form-control form-control-sm" placeholder="Ej. 79795778" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Correo Electrónico</label>
                            <input type="email" name="email" class="form-control form-control-sm" placeholder="correo@ejemplo.com">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Carrera a la que Postulas</label>
                            <select name="carrera" class="form-select form-select-sm" required>
                                <option value="">Selecciona una carrera...</option>
                                <?php foreach ($carrerasCatalog as $slug => $c): ?>
                                    <option value="<?= htmlspecialchars($c['nombre']) ?>"><?= htmlspecialchars($c['nombre']) ?> (<?= $c['grado'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-secondary mb-1">Consulta o Modalidad de Interés (opcional)</label>
                            <textarea name="notas" class="form-control form-control-sm" rows="3" placeholder="Ej. Consulta sobre Plan Uno a Uno, horario nocturno o convalidación de materias..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-ulat-primary w-100 py-2.5 d-inline-flex align-items-center justify-content-center gap-2">
                            <span>Registrar Solicitud de Admisión</span>
                            <i data-lucide="arrow-up-right" class="lucide-sm"></i>
                        </button>
                        <div id="admision-status" class="small mt-3 text-center d-none"></div>
                    </form>

                    <div class="mt-4 pt-3 border-top small text-secondary">
                        <div class="fw-bold text-dark mb-1">Atención Presencial y Telefónica</div>
                        <div>Sede Central: Calle Uruguay #E-836 · Telf. (4) 4509488 – 4221004</div>
                        <div>Campus Norte: Av. Uyuni #1889 · WhatsApp: 79795778</div>
                    </div>
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
    status.innerHTML = 'Procesando tu registro...';

    try {
        const resp = await fetch('api_lead.php', { method: 'POST', body: data });
        const res = await resp.json();
        if (res.ok) {
            status.className = 'small mt-3 alert alert-success p-3 text-start';
            status.innerHTML = `
                <div class="fw-bold mb-1">Preinscripción registrada con éxito</div>
                <div class="mb-2">${res.mensaje}</div>
                <a href="${res.whatsapp_directo}" target="_blank" class="btn btn-success btn-sm w-100">
                    Continuar en WhatsApp Admisiones
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
