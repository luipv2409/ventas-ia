<?php
declare(strict_types=1);
$pageTitle = 'Requisitos de Admisión y Cronograma | Universidad Latinoamericana (ULAT)';
$activeNav = 'admisiones';
require_once __DIR__ . '/header.php';
?>

<div class="ulat-page-header">
    <div class="container">
        <div class="row align-items-end g-4">
            <div class="col-lg-8">
                <span class="ulat-eyebrow-light">Matriculación II–2026</span>
                <h1 class="font-editorial display-4 text-white mb-0">Admisiones y Cronograma</h1>
            </div>
            <div class="col-lg-4 text-lg-end">
                <p class="small text-white-50 mb-0">
                    Inicio de Clases: Lunes 03 de Agosto · Plan Regular y Plan S.O.S.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-7">

            <!-- Cronograma Académico -->
            <div class="mb-5 pb-4" style="border-bottom: 1px solid var(--ulat-line);">
                <span class="ulat-eyebrow">Calendario Oficial II–2026</span>
                <h2 class="font-editorial h2 mb-4">Fechas de Inscripción</h2>
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="semester-card">
                            <div class="semester-header"><span>1ra Opción</span></div>
                            <div class="fw-medium text-dark mb-1">06 Jul – 01 Ago</div>
                            <div class="small text-muted">Estudiantes nuevos y regulares</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="semester-card">
                            <div class="semester-header"><span>2da Opción</span></div>
                            <div class="fw-medium text-dark mb-1">03 Ago – 08 Ago</div>
                            <div class="small text-muted">Rezagados de primera opción</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="semester-card">
                            <div class="semester-header"><span>3ra Opción</span></div>
                            <div class="fw-medium text-dark mb-1">10 Ago – 15 Ago</div>
                            <div class="small text-muted">Casos especiales y cierre</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Requisitos Estudiantes Nacionales -->
            <div class="mb-5">
                <span class="ulat-eyebrow">Documentación de Ingreso</span>
                <h2 class="font-editorial h2 mb-4">Estudiantes Nacionales Nuevos</h2>

                <div class="swiss-grid-row">
                    <div class="d-flex justify-content-between align-items-baseline">
                        <div>
                            <span class="academic-index me-3">01</span>
                            <strong class="text-dark fw-medium">Certificado de Nacimiento Original</strong>
                        </div>
                        <span class="small text-muted">Computarizado SERECI</span>
                    </div>
                </div>
                <div class="swiss-grid-row">
                    <div class="d-flex justify-content-between align-items-baseline">
                        <div>
                            <span class="academic-index me-3">02</span>
                            <strong class="text-dark fw-medium">Fotocopia de Cédula de Identidad</strong>
                        </div>
                        <span class="small text-muted">Vigente y legible</span>
                    </div>
                </div>
                <div class="swiss-grid-row">
                    <div class="d-flex justify-content-between align-items-baseline">
                        <div>
                            <span class="academic-index me-3">03</span>
                            <strong class="text-dark fw-medium">Fotocopia Legalizada del Diploma de Bachiller</strong>
                        </div>
                        <span class="small text-muted">Legalizada por DDE / SEDUCA</span>
                    </div>
                </div>
                <div class="swiss-grid-row">
                    <div class="d-flex justify-content-between align-items-baseline">
                        <div>
                            <span class="academic-index me-3">04</span>
                            <strong class="text-dark fw-medium">4 Fotografías tamaño 3×4</strong>
                        </div>
                        <span class="small text-muted">Fondo azul · Traje formal</span>
                    </div>
                </div>

                <div class="mt-3 small text-muted">
                    Nota: Presentar toda la documentación en Secretaría General en un <strong>folder tamaño oficio color amarillo</strong>.
                </div>
            </div>

            <!-- Requisitos Estudiantes Extranjeros -->
            <div class="pt-3" style="border-top: 1px solid var(--ulat-line);">
                <span class="ulat-eyebrow">Admisión Internacional</span>
                <h3 class="font-editorial h3 mb-3">Postulantes del Exterior</h3>
                <ul class="semester-list">
                    <li>Certificado de Nacimiento original apostillado (Convenio de La Haya) o legalizado por Cancillería.</li>
                    <li>Fotocopia de Pasaporte vigente con Visa de Estudiante o residencia legal en Bolivia.</li>
                    <li>Diploma de Bachiller o equivalente apostillado y homologado ante la Dirección Departamental de Educación.</li>
                    <li>4 fotografías 3×4 con traje formal en folder tamaño oficio color amarillo.</li>
                </ul>
            </div>
        </div>

        <!-- Formulario de Preinscripción -->
        <div class="col-lg-5" id="preinscripcion">
            <div class="sticky-top" style="top: 90px;">
                <div class="card card-ulat p-4 p-md-5 bg-white">
                    <span class="ulat-eyebrow">Registro en Línea</span>
                    <h3 class="font-editorial h3 mb-2">Formulario de Admisión</h3>
                    <p class="small text-muted mb-4">
                        Inicia tu proceso en línea y recibe la cotización de aranceles y horarios por WhatsApp.
                    </p>

                    <form id="form-admision-full" action="api_lead.php" method="POST">
                        <div class="mb-3">
                            <label class="font-mono-code text-muted d-block mb-1">Nombre y Apellidos</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Fernando Quiroga" required>
                        </div>
                        <div class="mb-3">
                            <label class="font-mono-code text-muted d-block mb-1">Celular o WhatsApp</label>
                            <input type="tel" name="telefono" class="form-control" placeholder="79795778" required>
                        </div>
                        <div class="mb-3">
                            <label class="font-mono-code text-muted d-block mb-1">Correo Electrónico</label>
                            <input type="email" name="email" class="form-control" placeholder="correo@ejemplo.com">
                        </div>
                        <div class="mb-3">
                            <label class="font-mono-code text-muted d-block mb-1">Carrera a la que Postulas</label>
                            <select name="carrera" class="form-select" required>
                                <option value="">Seleccionar carrera...</option>
                                <?php foreach ($carrerasCatalog as $slug => $c): ?>
                                    <option value="<?= htmlspecialchars($c['nombre']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-4">
                            <label class="font-mono-code text-muted d-block mb-1">Consulta o Modalidad (Opcional)</label>
                            <textarea name="notas" class="form-control" rows="3" placeholder="Consulta sobre Plan Uno a Uno, turno nocturno o convalidación..."></textarea>
                        </div>

                        <button type="submit" class="btn btn-ulat-primary w-100 py-2.5">
                            Registrar Solicitud de Admisión →
                        </button>
                        <div id="admision-status" class="small mt-3 text-center d-none"></div>
                    </form>
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
    status.innerHTML = 'Procesando registro...';

    try {
        const resp = await fetch('api_lead.php', { method: 'POST', body: data });
        const res = await resp.json();
        if (res.ok) {
            status.className = 'small mt-3 alert alert-success p-3 text-start';
            status.innerHTML = `
                <div class="fw-medium mb-1">Solicitud registrada con éxito</div>
                <div class="mb-2">${res.mensaje}</div>
                <a href="${res.whatsapp_directo}" target="_blank" class="btn btn-ulat-primary btn-sm w-100">
                    Abrir WhatsApp Admisiones ↗
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
