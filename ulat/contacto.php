<?php
declare(strict_types=1);
$pageTitle = 'Contacto y Sedes | Universidad Latinoamericana (ULAT)';
$activeNav = 'contacto';
require_once __DIR__ . '/header.php';
?>

<div class="ulat-page-header">
    <div class="container py-2">
        <span class="ulat-eyebrow-light">Atención Institucional</span>
        <h1 class="display-6 fw-bold text-white mb-2">Sedes y Canales de Contacto</h1>
        <p class="mb-0 text-white-50" style="max-width: 680px; font-size: 0.98rem;">
            Visítanos en nuestra Sede Central o en el Campus Norte en la ciudad de Cochabamba, o comunícate con nuestro equipo de Admisiones.
        </p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <!-- Información de Sedes -->
        <div class="col-lg-5">
            <div class="card card-ulat p-4 p-md-5 mb-4">
                <span class="ulat-eyebrow mb-1">Ubicación y Teléfonos</span>
                <h4 class="fw-bold mb-4 pb-2 border-bottom">Directorio Oficial ULAT</h4>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="icon-frame-gold">
                        <i data-lucide="map-pin" class="lucide"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Sede Central Cochabamba</h6>
                        <p class="text-secondary small mb-0">Calle Uruguay #E-836 entre 16 de Julio y Oquendo, Cochabamba – Bolivia.</p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="icon-frame">
                        <i data-lucide="building-2" class="lucide"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Campus Norte (Automotriz y Electrónica)</h6>
                        <p class="text-secondary small mb-0">Av. Uyuni Nº 1889 casi Av. Circunvalación, Cochabamba – Bolivia.</p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="icon-frame">
                        <i data-lucide="message-square" class="lucide"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">WhatsApp Inscripciones en Línea</h6>
                        <p class="text-secondary small mb-2">+591 79795778 · Atención Central: +591 4509488</p>
                        <a href="https://wa.me/59179795778" target="_blank" class="btn btn-ulat-outline btn-sm">Escribir por WhatsApp</a>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="icon-frame">
                        <i data-lucide="phone" class="lucide"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Central Telefónica</h6>
                        <p class="text-secondary small mb-0">(4) 4509488 – (4) 4221004</p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="icon-frame">
                        <i data-lucide="mail" class="lucide"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Correo Institucional</h6>
                        <p class="text-secondary small mb-0">latinoamericana@ulat.edu.bo</p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3">
                    <div class="icon-frame">
                        <i data-lucide="clock" class="lucide"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Horario de Atención</h6>
                        <p class="text-secondary small mb-0">
                            Lunes a Viernes: 08:00 a 18:30 (horario continuo)<br>
                            Sábados: 08:30 a 12:30
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de Contacto -->
        <div class="col-lg-7">
            <div class="card card-ulat p-4 p-md-5 bg-white">
                <span class="ulat-eyebrow mb-1">Atención al Postulante</span>
                <h4 class="fw-bold mb-2">Envíanos tu Consulta</h4>
                <p class="text-muted small mb-4">
                    Completa el formulario y un asesor de la Dirección de Admisiones se comunicará contigo a la brevedad:
                </p>

                <form id="form-contacto-page" action="api_lead.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary mb-1">Nombre Completo</label>
                            <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Ej. Andrea Rojas" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary mb-1">Teléfono / WhatsApp</label>
                            <input type="tel" name="telefono" class="form-control form-control-sm" placeholder="Ej. 79795778" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary mb-1">Correo Electrónico</label>
                            <input type="email" name="email" class="form-control form-control-sm" placeholder="andrea@ejemplo.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-secondary mb-1">Carrera o Área de Interés</label>
                            <select name="carrera" class="form-select form-select-sm" required>
                                <option value="">Seleccionar carrera...</option>
                                <?php foreach ($carrerasCatalog as $slug => $c): ?>
                                    <option value="<?= htmlspecialchars($c['nombre']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endforeach; ?>
                                <option value="Diplomados y Postgrado">Diplomados y Postgrado</option>
                                <option value="Consulta General">Consulta General / Trámite</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold text-secondary mb-1">Mensaje o Consulta</label>
                            <textarea name="notas" class="form-control form-control-sm" rows="4" placeholder="Escribe aquí tu consulta sobre horarios, aranceles, convalidaciones o requisitos..." required></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-ulat-primary px-4 py-2 d-inline-flex align-items-center gap-2">
                                <span>Enviar Consulta a Admisiones</span>
                                <i data-lucide="arrow-up-right" class="lucide-sm"></i>
                            </button>
                        </div>
                    </div>
                    <div id="contacto-status" class="small mt-3 d-none"></div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('form-contacto-page')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const status = document.getElementById('contacto-status');
    const data = new FormData(form);

    status.classList.remove('d-none', 'alert-danger', 'alert-success');
    status.innerHTML = 'Enviando mensaje...';

    try {
        const resp = await fetch('api_lead.php', { method: 'POST', body: data });
        const res = await resp.json();
        if (res.ok) {
            status.className = 'small mt-3 alert alert-success p-3 text-start';
            status.innerHTML = `${res.mensaje}`;
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
