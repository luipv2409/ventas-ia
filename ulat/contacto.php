<?php
declare(strict_types=1);
$pageTitle = 'Contacto y Sedes | Universidad Latinoamericana (ULAT)';
$activeNav = 'contacto';
require_once __DIR__ . '/header.php';
?>

<div class="ulat-page-header">
    <div class="container">
        <div class="row align-items-end g-4">
            <div class="col-lg-8">
                <span class="ulat-eyebrow-light">Directorio Institucional</span>
                <h1 class="font-editorial display-4 text-white mb-0">Sedes y Contacto</h1>
            </div>
            <div class="col-lg-4 text-lg-end">
                <p class="small text-white-50 mb-0">
                    Cochabamba, Bolivia · Atención continua de Lunes a Sábado
                </p>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-5">
            <span class="ulat-eyebrow">Ubicaciones Oficiales</span>
            <h2 class="font-editorial h2 mb-4">Nuestros Campus</h2>

            <div class="swiss-grid-row">
                <div class="font-mono-code text-muted mb-1">01 · Sede Central</div>
                <div class="fw-medium text-dark">Calle Uruguay #E-836 entre 16 de Julio y Oquendo</div>
                <div class="small text-muted">Cochabamba, Bolivia</div>
            </div>

            <div class="swiss-grid-row">
                <div class="font-mono-code text-muted mb-1">02 · Campus Norte (Automotriz y Electrónica)</div>
                <div class="fw-medium text-dark">Av. Uyuni Nº 1889 casi Av. Circunvalación</div>
                <div class="small text-muted">Cochabamba, Bolivia</div>
            </div>

            <div class="swiss-grid-row">
                <div class="font-mono-code text-muted mb-1">03 · Teléfonos y WhatsApp</div>
                <div class="fw-medium text-dark">Central: (4) 4509488 – (4) 4221004</div>
                <div class="small text-muted mb-2">WhatsApp Inscripciones: +591 79795778 · Postgrado: 79787897</div>
                <a href="https://wa.me/59179795778" target="_blank" rel="noopener noreferrer" class="font-mono-code text-dark text-decoration-none">Escribir por WhatsApp ↗</a>
            </div>

            <div class="swiss-grid-row">
                <div class="font-mono-code text-muted mb-1">04 · Horario de Atención</div>
                <div class="small text-muted">Lunes a Viernes: 08:00 a 18:30 · Sábados: 08:30 a 12:30</div>
                <div class="small text-muted">Correo: latinoamericana@ulat.edu.bo</div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card card-ulat p-4 p-md-5 bg-white">
                <span class="ulat-eyebrow">Mensaje Directo</span>
                <h3 class="font-editorial h3 mb-2">Comunicarse con Admisiones</h3>
                <p class="small text-muted mb-4">
                    Déjanos tus datos y un asesor se pondrá en contacto contigo a la brevedad.
                </p>

                <form id="form-contacto-page" action="api_lead.php" method="POST">
                    <?= \Services\Security\SecurityKernel::csrfInput() ?>
                    <input type="text" name="website_url" class="d-none" tabindex="-1" autocomplete="off">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="font-mono-code text-muted d-block mb-1">Nombre Completo</label>
                            <input type="text" name="nombre" class="form-control" maxlength="120" placeholder="Andrea Rojas" required>
                        </div>
                        <div class="col-md-6">
                            <label class="font-mono-code text-muted d-block mb-1">Teléfono / WhatsApp</label>
                            <input type="tel" name="telefono" class="form-control" maxlength="20" placeholder="79795778" required>
                        </div>
                        <div class="col-md-6">
                            <label class="font-mono-code text-muted d-block mb-1">Correo Electrónico</label>
                            <input type="email" name="email" class="form-control" maxlength="150" placeholder="andrea@ejemplo.com">
                        </div>
                        <div class="col-md-6">
                            <label class="font-mono-code text-muted d-block mb-1">Carrera o Área</label>
                            <select name="carrera" class="form-select" required>
                                <option value="">Seleccionar carrera...</option>
                                <?php foreach ($carrerasCatalog as $slug => $c): ?>
                                    <option value="<?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                                <option value="Diplomados y Postgrado">Diplomados y Postgrado</option>
                                <option value="Consulta General">Consulta General</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="font-mono-code text-muted d-block mb-1">Consulta</label>
                            <textarea name="notas" class="form-control" rows="4" maxlength="500" placeholder="Escribe tu consulta sobre aranceles, horarios o convalidaciones..." required></textarea>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="consentimiento" value="1" id="consent-contacto" required>
                                <label class="form-check-label small text-muted" for="consent-contacto" style="font-size: 0.75rem;">
                                    Autorizo a la ULAT el uso de mis datos de contacto para responder a mi solicitud académica.
                                </label>
                            </div>
                        </div>
                        <div class="col-12 pt-2">
                            <button type="submit" class="btn btn-ulat-primary px-4 py-2.5">
                                Enviar Consulta →
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
    status.textContent = 'Enviando...';

    try {
        const resp = await fetch('api_lead.php', { method: 'POST', body: data, credentials: 'same-origin' });
        const res = await resp.json();
        if (res.ok) {
            status.className = 'small mt-3 alert alert-success p-3 text-start';
            status.textContent = res.mensaje || 'Solicitud enviada con éxito.';
            form.reset();
        } else {
            status.className = 'small mt-3 alert alert-danger p-2 text-center';
            status.textContent = res.error || 'Ocurrió un error al enviar.';
        }
    } catch(err) {
        status.className = 'small mt-3 alert alert-danger p-2 text-center';
        status.textContent = 'Error de conexión.';
    }
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
