<?php
declare(strict_types=1);
$pageTitle = 'Contacto y Ubicación | Universidad Latinoamericana (ULAT)';
$activeNav = 'contacto';
require_once __DIR__ . '/header.php';
?>

<div class="bg-primary text-white py-5" style="background: linear-gradient(135deg, var(--ulat-blue-dark) 0%, var(--ulat-blue-primary) 100%);">
    <div class="container text-center py-3">
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-2">Atención Personalizada</span>
        <h1 class="display-5 fw-bold text-white mb-2">Canales de Contacto</h1>
        <p class="lead text-light text-opacity-75 max-w-700 mx-auto">
            Visítanos en nuestro campus central en Cochabamba o comunícate directamente con nuestro equipo de admisiones.
        </p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <!-- Tarjetas de Información -->
        <div class="col-lg-5">
            <div class="card card-ulat p-4 p-md-5 mb-4 border-top border-4 border-warning">
                <h4 class="fw-bold text-primary mb-4">Información Institucional</h4>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-circle">
                        <i class="fa-solid fa-location-dot fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Campus Central Cochabamba</h6>
                        <p class="text-secondary small mb-0">Calle Uruguay #E-836 entre 16 de Julio y Oquendo, Cochabamba - Bolivia.</p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="p-3 bg-success bg-opacity-10 text-success rounded-circle">
                        <i class="fa-brands fa-whatsapp fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">WhatsApp de Admisiones</h6>
                        <p class="text-secondary small mb-1">+591 4509488</p>
                        <a href="https://wa.me/5914509488" target="_blank" class="btn btn-outline-success btn-sm rounded-pill">Chatear por WhatsApp</a>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="p-3 bg-info bg-opacity-10 text-info rounded-circle">
                        <i class="fa-solid fa-phone fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Teléfonos Fijos</h6>
                        <p class="text-secondary small mb-0">(4) 4509488 - (4) 4221004</p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3 mb-4">
                    <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-circle">
                        <i class="fa-solid fa-envelope fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Correo Electrónico</h6>
                        <p class="text-secondary small mb-0">latinoamericana@ulat.edu.bo</p>
                    </div>
                </div>

                <div class="d-flex align-items-start gap-3">
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-circle">
                        <i class="fa-solid fa-clock fs-5"></i>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-1">Horario de Atención</h6>
                        <p class="text-secondary small mb-0">
                            Lunes a Viernes: 08:00 a 18:30 (Continuo)<br>
                            Sábados: 08:30 a 12:30
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formulario de Mensaje Directo -->
        <div class="col-lg-7">
            <div class="card card-ulat p-4 p-md-5 bg-white shadow-sm">
                <h4 class="fw-bold text-dark mb-2">Envíanos un Mensaje</h4>
                <p class="text-muted small mb-4">
                    Déjanos tu consulta y te responderemos a la brevedad con la información de la carrera o trámite que necesitas:
                </p>

                <form id="form-contacto-page" action="api_lead.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Tu Nombre</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej: Andrea Rojas" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Tu Teléfono / WhatsApp</label>
                            <input type="tel" name="telefono" class="form-control" placeholder="Ej: 71234567" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Correo Electrónico</label>
                            <input type="email" name="email" class="form-control" placeholder="andrea@ejemplo.com">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-secondary">Carrera o Asunto</label>
                            <select name="carrera" class="form-select" required>
                                <option value="">Seleccionar carrera...</option>
                                <?php foreach ($carrerasCatalog as $slug => $c): ?>
                                    <option value="<?= htmlspecialchars($c['nombre']) ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                                <?php endforeach; ?>
                                <option value="Consulta General">Consulta General / Trámite</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold text-secondary">Mensaje o Consulta</label>
                            <textarea name="notas" class="form-control" rows="4" placeholder="Escribe aquí tu consulta..." required></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-ulat-gold py-3 px-5 fw-bold">
                                <i class="fa-solid fa-paper-plane me-2"></i> Enviar Mensaje
                            </button>
                        </div>
                    </div>
                    <div id="contacto-status" class="small mt-3 d-none"></div>
                </form>
            </div>

            <!-- Mapa / Ubicación -->
            <div class="card card-ulat p-3 mt-4 border-0 shadow-sm text-center">
                <div class="p-4 bg-light rounded-3">
                    <i class="fa-solid fa-map-location-dot text-primary fs-1 mb-2"></i>
                    <h5 class="fw-bold text-dark mb-1">Campus Central ULAT</h5>
                    <p class="text-secondary small mb-0">Calle Uruguay #E-836 entre 16 de Julio y Oquendo, Cochabamba, Bolivia.</p>
                </div>
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
    status.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin me-1"></i> Enviando mensaje...';

    try {
        const resp = await fetch('api_lead.php', { method: 'POST', body: data });
        const res = await resp.json();
        if (res.ok) {
            status.className = 'small mt-3 alert alert-success p-3 text-start';
            status.innerHTML = `✔ ${res.mensaje}`;
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
