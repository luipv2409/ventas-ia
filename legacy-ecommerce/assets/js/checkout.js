// ============================================================
// ARCHIVO: assets/js/checkout.js
// PROPÓSITO: Mostrar el resumen del carrito en checkout y
//            enviar el pedido al backend con estética Tech Premium.
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
    const resumenDiv = document.getElementById('resumen-carrito');
    const form       = document.getElementById('form-checkout');
    const mensajeDiv = document.getElementById('mensaje-checkout');
    const simbolo    = typeof SIMBOLO_MONEDA !== 'undefined' ? SIMBOLO_MONEDA : 'Bs';
    const tasa       = typeof TASA_CAMBIO !== 'undefined' && TASA_CAMBIO > 0 ? TASA_CAMBIO : 9.96;

    // Mostrar productos del carrito con estilo moderno
    function mostrarResumen() {
        const items = carrito.obtener();
        if (items.length === 0) {
            resumenDiv.innerHTML = `
                <div class="alert alert-warning border-0 p-3 mb-0" style="background: #fffbeb; border-radius: 12px; color: #b45309;">
                    <i data-lucide="alert-triangle" class="me-2"></i>
                    Tu bolsa de compras está vacía. 
                    <a href="index.php" class="fw-bold text-decoration-underline text-warning-emphasis">Volver al catálogo</a>
                </div>
            `;
            if (window.lucide) lucide.createIcons();
            const btnConfirmar = document.getElementById('btn-confirmar');
            if (btnConfirmar) btnConfirmar.disabled = true;
            return;
        }

        let html = '<div class="d-flex flex-column gap-3 mb-3">';
        items.forEach(item => {
            const subtotal = item.precio * item.cantidad;
            html += `
                <div class="d-flex justify-content-between align-items-center p-2 rounded" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                    <div class="d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; background: #e0e7ff; color: #4f46e5; font-size: 0.85rem; font-weight: 700;">
                            ${item.cantidad}x
                        </div>
                        <div>
                            <div class="fw-bold text-dark small" style="line-height: 1.2;">${escapeHtml(item.nombre)}</div>
                            <div class="text-muted small">${simbolo} ${item.precio.toFixed(2)} c/u</div>
                        </div>
                    </div>
                    <div class="fw-bold text-dark text-end">
                        ${simbolo} ${subtotal.toFixed(2)}
                    </div>
                </div>
            `;
        });
        html += '</div>';

        const totalBs = carrito.total();
        const totalUsd = totalBs / tasa;

        html += `
            <div class="pt-2 border-top">
                <div class="summary-row mb-1">
                    <span class="small text-muted">Subtotal</span>
                    <span class="fw-medium text-dark">${simbolo} ${totalBs.toFixed(2)}</span>
                </div>
                <div class="summary-row mb-2">
                    <span class="small text-muted">Envío Express a domicilio</span>
                    <span class="badge bg-success-subtle text-success fw-bold px-2 py-1">GRATIS 🇧🇴</span>
                </div>
                <div class="summary-row total pt-2 mt-2 border-top">
                    <div>
                        <div class="fw-bold fs-6 text-dark">Total Oficial</div>
                        <div class="text-muted small fw-normal">≈ $${totalUsd.toFixed(2)} USD</div>
                    </div>
                    <div class="fs-4 fw-bold text-primary">${simbolo} ${totalBs.toFixed(2)}</div>
                </div>
            </div>
        `;
        resumenDiv.innerHTML = html;
        if (window.lucide) lucide.createIcons();
    }

    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    mostrarResumen();

    // Enviar formulario
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('btn-confirmar');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Procesando tu Pedido...';

        const items = carrito.obtener();
        if (items.length === 0) {
            mensajeDiv.innerHTML = '<div class="alert alert-danger border-0 p-3 rounded-3">La bolsa de compras está vacía.</div>';
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="shield-check" style="width:16px;height:16px;" class="me-1"></i> Confirmar y Registrar Pedido';
            if (window.lucide) lucide.createIcons();
            return;
        }

        const totalCalculado = carrito.total();

        const datosCliente = {
            nombre: document.getElementById('nombre').value.trim(),
            email: document.getElementById('email').value.trim(),
            telefono: document.getElementById('telefono').value.trim(),
            ciudad: document.getElementById('ciudad') ? document.getElementById('ciudad').value.trim() : '',
            direccion: document.getElementById('direccion') ? document.getElementById('direccion').value.trim() : '',
            notas: document.getElementById('notas') ? document.getElementById('notas').value.trim() : '',
            metodo_pago: document.getElementById('metodo_pago') ? document.getElementById('metodo_pago').value : 'QR Simple'
        };

        try {
            const response = await fetch('api/procesar_pedido.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    cliente: datosCliente,
                    carrito: items
                })
            });

            const data = await response.json();
            if (data.ok) {
                // Vaciar carrito
                carrito.vaciar();

                // Cambiar a pantalla de éxito
                const containerRow = document.getElementById('checkout-container-row');
                const pantallaExito = document.getElementById('pantalla-exito');
                const textoExito = document.getElementById('texto-exito-pedido');

                if (containerRow && pantallaExito) {
                    containerRow.classList.add('d-none');
                    pantallaExito.classList.remove('d-none');
                    if (textoExito) {
                        textoExito.innerHTML = `Tu pedido <strong>#${data.pedido_id || 'REG'}</strong> por <strong>${simbolo} ${totalCalculado.toFixed(2)}</strong> ha sido registrado con éxito.`;
                    }

                    // Actualizar stepper al paso 3
                    const stepperItems = document.querySelectorAll('.step-item');
                    if (stepperItems.length >= 3) {
                        stepperItems[1].classList.remove('active');
                        stepperItems[1].classList.add('completed');
                        stepperItems[1].querySelector('.step-number').innerHTML = '<i data-lucide="check" style="width:16px;height:16px;"></i>';
                        stepperItems[2].classList.add('active');
                    }

                    const separators = document.querySelectorAll('.step-separator');
                    separators.forEach(s => s.classList.add('completed'));

                    if (window.lucide) {
                        lucide.createIcons();
                    }

                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    mensajeDiv.innerHTML = `<div class="alert alert-success">✅ ${data.mensaje}</div>`;
                    setTimeout(() => { window.location.href = 'index.php'; }, 3000);
                }
            } else {
                mensajeDiv.innerHTML = `<div class="alert alert-danger border-0 p-3 rounded-3">${data.error}</div>`;
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="shield-check" style="width:16px;height:16px;" class="me-1"></i> Confirmar y Registrar Pedido';
                if (window.lucide) lucide.createIcons();
            }
        } catch (error) {
            mensajeDiv.innerHTML = '<div class="alert alert-danger border-0 p-3 rounded-3">Ocurrió un error de conexión al procesar el pedido. Intenta nuevamente.</div>';
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="shield-check" style="width:16px;height:16px;" class="me-1"></i> Confirmar y Registrar Pedido';
            if (window.lucide) lucide.createIcons();
        }
    });
});