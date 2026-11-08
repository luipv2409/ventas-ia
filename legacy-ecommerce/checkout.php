<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_cliente.php';

// Cargar cliente autenticado si existe
$clienteActual = cliente_actual();
$estaLogueado  = ($clienteActual !== null);

// Cargar configuración de la tienda
$config = [];
try {
    $pdo = Database::getConnection();
    $stmtConfig = $pdo->query("SELECT clave, valor FROM configuracion");
    while ($row = $stmtConfig->fetch(PDO::FETCH_ASSOC)) {
        $config[$row['clave']] = $row['valor'];
    }
} catch (Exception $e) {
    // Fallback silencioso
}

$nombreTienda  = $config['nombre_tienda'] ?? 'Nuestra Tienda';
$simboloMoneda = $config['moneda_simbolo'] ?? 'Bs';
$tasaCambio    = !empty($config['tasa_cambio_usd']) ? (float)$config['tasa_cambio_usd'] : 9.96;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finalizar Pedido — <?= htmlspecialchars($nombreTienda) ?></title>
    
    <!-- Google Fonts: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Estilos Tech Premium -->
    <link rel="stylesheet" href="assets/css/estilos.css">
    <script>
        const TASA_CAMBIO = <?= json_encode($tasaCambio) ?>;
        const SIMBOLO_MONEDA = <?= json_encode($simboloMoneda) ?>;
    </script>
</head>
<body class="bg-slate-50">

    <!-- Navbar Sticky Tech Unificado -->
    <?php 
    $paginaActiva = 'checkout';
    include __DIR__ . '/includes/navbar_store.php'; 
    ?>

    <!-- Contenido Principal -->
    <main class="container my-5">
        
        <!-- Stepper de Progreso -->
        <div class="stepper-tech">
            <a href="carrito.php" class="step-item completed">
                <div class="step-number"><i data-lucide="check" class="lucide-sm"></i></div>
                <span>1. Tu Carrito</span>
            </a>
            <div class="step-separator completed"></div>
            <div class="step-item active">
                <div class="step-number">2</div>
                <span>2. Datos de Envío</span>
            </div>
            <div class="step-separator"></div>
            <div class="step-item">
                <div class="step-number">3</div>
                <span>3. Confirmación</span>
            </div>
        </div>

        <div class="mb-4">
            <h2 class="fw-bold mb-1 d-flex align-items-center gap-2">
                <i data-lucide="check-circle" class="text-primary"></i> <span>Finalizar tu Pedido</span>
            </h2>
            <p class="text-muted small mb-0">Completa tus datos para coordinar el despacho seguro de tu compra.</p>
        </div>

        <div class="row g-4" id="checkout-container-row">
            
            <!-- Columna Izquierda: Formulario de Contacto y Envío -->
            <div class="col-lg-7">
                <div class="tech-card p-4 p-md-5">
                    
                    <form id="form-checkout">
                        
                        <?php if ($estaLogueado): ?>
                            <div class="alert alert-info border-0 rounded-4 p-3 mb-4 d-flex align-items-center gap-3 shadow-sm" style="background: #eff6ff; color: #1e40af;">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center flex-shrink-0" style="width: 44px; height: 44px; font-size: 1.1rem;">
                                    <?php if (!empty($clienteActual['avatar_url'])): ?>
                                        <img src="<?= htmlspecialchars($clienteActual['avatar_url']) ?>" alt="Avatar" class="rounded-circle w-100 h-100" style="object-fit: cover;">
                                    <?php else: ?>
                                        <i data-lucide="user-check" style="width:20px;height:20px;"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold d-flex align-items-center gap-2">
                                        <span>Comprando como <?= htmlspecialchars($clienteActual['nombre']) ?></span>
                                        <span class="badge bg-primary text-white small">Cuenta Activa</span>
                                    </div>
                                    <div class="small text-secondary">
                                        <?= htmlspecialchars($clienteActual['email']) ?> — Si modificas tus datos de envío, se guardarán automáticamente en tu cuenta.
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-light border rounded-4 p-3 mb-4 d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 shadow-sm">
                                <div class="d-flex align-items-center gap-2 small text-secondary">
                                    <i data-lucide="info" style="width:20px;height:20px;" class="text-primary"></i>
                                    <span>¿Tienes cuenta registrada? Inicia sesión para usar tus datos guardados o continúa como invitado.</span>
                                </div>
                                <a href="login.php?redirect=checkout.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold flex-shrink-0 d-inline-flex align-items-center gap-1">
                                    <i data-lucide="log-in" style="width:14px;height:14px;"></i> <span>Iniciar Sesión</span>
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- Sección 1: Datos Personales -->
                        <div class="mb-4">
                            <h5 class="fw-bold text-dark d-flex align-items-center gap-2 mb-3">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 28px; height: 28px; font-size: 0.85rem;">1</span>
                                Datos Personales
                            </h5>
                            
                            <div class="row g-3">
                                <div class="col-12">
                                    <label for="nombre" class="form-label small fw-semibold text-secondary">Nombre y Apellido *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i data-lucide="user" style="width:16px;height:16px;"></i></span>
                                        <input type="text" class="form-control tech-form-control border-start-0 ps-0" id="nombre" name="nombre" placeholder="Ej. Carlos Mendoza" value="<?= htmlspecialchars($clienteActual['nombre'] ?? '') ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="telefono" class="form-label small fw-semibold text-secondary">Teléfono / WhatsApp *</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i data-lucide="phone-call" style="width:16px;height:16px;" class="text-success"></i></span>
                                        <input type="tel" class="form-control tech-form-control border-start-0 ps-0" id="telefono" name="telefono" placeholder="Ej. 70012345" value="<?= htmlspecialchars($clienteActual['telefono'] ?? '') ?>" required>
                                    </div>
                                    <div class="form-text small text-muted">Te contactaremos para coordinar la entrega.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label small fw-semibold text-secondary">Correo Electrónico (Opcional)</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i data-lucide="mail" style="width:16px;height:16px;"></i></span>
                                        <input type="email" class="form-control tech-form-control border-start-0 ps-0" id="email" name="email" placeholder="carlos@ejemplo.com" value="<?= htmlspecialchars($clienteActual['email'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 text-secondary opacity-25">

                        <!-- Sección 2: Dirección de Despacho -->
                        <div class="mb-4">
                            <h5 class="fw-bold text-dark d-flex align-items-center gap-2 mb-3">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 28px; height: 28px; font-size: 0.85rem;">2</span>
                                Lugar de Entrega
                            </h5>

                            <div class="row g-3">
                                <div class="col-md-5">
                                    <label for="ciudad" class="form-label small fw-semibold text-secondary">Ciudad / Departamento *</label>
                                    <select class="form-select tech-form-control" id="ciudad" name="ciudad" required>
                                        <option value="La Paz" selected>La Paz</option>
                                        <option value="Santa Cruz">Santa Cruz</option>
                                        <option value="Cochabamba">Cochabamba</option>
                                        <option value="El Alto">El Alto</option>
                                        <option value="Oruro">Oruro</option>
                                        <option value="Potosí">Potosí</option>
                                        <option value="Sucre">Sucre</option>
                                        <option value="Tarija">Tarija</option>
                                        <option value="Beni">Beni</option>
                                        <option value="Pando">Pando</option>
                                    </select>
                                </div>
                                <div class="col-md-7">
                                    <label for="direccion" class="form-label small fw-semibold text-secondary">Dirección Exacta o Zona</label>
                                    <input type="text" class="form-control tech-form-control" id="direccion" name="direccion" placeholder="Ej. Av. Arce #2140, Zona San Jorge" value="<?= htmlspecialchars($clienteActual['direccion'] ?? '') ?>">
                                </div>
                                <div class="col-12">
                                    <label for="notas" class="form-label small fw-semibold text-secondary">Instrucciones o Referencias Adicionales</label>
                                    <textarea class="form-control tech-form-control" id="notas" name="notas" rows="2" placeholder="Ej. Casa de rejas blancas, timbre 2B o dejar en recepción"></textarea>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4 text-secondary opacity-25">

                        <!-- Sección 3: Método de Pago -->
                        <div class="mb-4">
                            <h5 class="fw-bold text-dark d-flex align-items-center gap-2 mb-3">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary" style="width: 28px; height: 28px; font-size: 0.85rem;">3</span>
                                Método de Pago
                            </h5>

                            <input type="hidden" id="metodo_pago" name="metodo_pago" value="QR Simple / Transferencia Bancaria">

                            <div class="option-select-card selected" data-value="QR Simple / Transferencia Bancaria">
                                <div class="option-icon"><i data-lucide="qr-code"></i></div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark d-flex align-items-center gap-2">
                                        QR Simple / Transferencia Bancaria
                                        <span class="badge bg-success-subtle text-success small">Recomendado</span>
                                    </div>
                                    <div class="text-muted small">Generamos tu código QR para pagar desde cualquier banco móvil boliviano.</div>
                                </div>
                                <div class="text-primary fs-5 check-icon"><i data-lucide="check-circle-2"></i></div>
                            </div>

                            <div class="option-select-card" data-value="Pago contra Entrega (Efectivo)">
                                <div class="option-icon"><i data-lucide="banknote"></i></div>
                                <div class="flex-grow-1">
                                    <div class="fw-bold text-dark">Pago contra Entrega (Efectivo)</div>
                                    <div class="text-muted small">Pagas en efectivo al mensajero en mano al momento de recibir tus productos.</div>
                                </div>
                                <div class="text-secondary fs-5 check-icon"><i data-lucide="circle"></i></div>
                            </div>
                        </div>

                        <!-- Botón de Confirmación -->
                        <div class="mt-4 pt-2">
                            <button type="submit" class="btn-tech-checkout" id="btn-confirmar">
                                <i data-lucide="shield-check"></i> Confirmar y Registrar Pedido
                            </button>
                            <p class="text-center text-muted small mt-2 mb-0 d-flex align-items-center justify-content-center gap-1">
                                <i data-lucide="shield-check" class="text-success me-1"></i> <span>Transacción segura y protegida.</span>
                            </p>
                        </div>
                    </form>

                    <div id="mensaje-checkout" class="mt-3"></div>
                </div>
            </div>

            <!-- Columna Derecha: Resumen del Pedido Sticky -->
            <div class="col-lg-5">
                <div class="cart-summary-box tech-card p-4">
                    <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                        <h5 class="fw-bold mb-0 text-dark">Tu Pedido</h5>
                        <a href="carrito.php" class="small text-primary text-decoration-none fw-medium d-inline-flex align-items-center gap-1">
                            <i data-lucide="edit-3"></i> <span>Editar bolsa</span>
                        </a>
                    </div>
                    
                    <div id="resumen-carrito">
                        <!-- Renderizado por checkout.js -->
                    </div>

                    <div class="trust-badge-row mt-4">
                        <div class="trust-badge-item">
                            <i data-lucide="truck" class="text-success"></i>
                            <span>Envío a domicilio en toda Bolivia</span>
                        </div>
                        <div class="trust-badge-item">
                            <i data-lucide="shield-check" class="text-success"></i>
                            <span>Garantía oficial y compra protegida</span>
                        </div>
                        <div class="trust-badge-item">
                            <i data-lucide="message-square" class="text-success"></i>
                            <span>Coordinación directa de entrega por WhatsApp</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Pantalla de Éxito / Confirmación (Oculta por defecto) -->
        <div id="pantalla-exito" class="tech-card p-5 text-center d-none my-4">
            <div class="mb-4">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success text-white shadow" style="width: 84px; height: 84px;">
                    <i data-lucide="check" style="width: 44px; height: 44px;"></i>
                </div>
            </div>
            <h2 class="fw-bold mb-2 text-dark">¡Muchas gracias por tu compra!</h2>
            <p class="text-muted fs-5 mb-4" id="texto-exito-pedido">Tu pedido ha sido registrado con éxito.</p>
            
            <div class="card bg-light border-0 p-4 mx-auto mb-4" style="max-width: 520px; border-radius: 14px;">
                <h6 class="fw-bold text-dark mb-2 d-inline-flex align-items-center gap-1"><i data-lucide="phone-call" style="width:16px;height:16px;" class="text-success"></i> ¿Qué sucederá a continuación?</h6>
                <p class="text-secondary small mb-0">
                    Nuestra asesora de ventas se comunicará contigo vía WhatsApp o llamada telefónica para confirmar los detalles de despacho y enviarte el comprobante de seguimiento.
                </p>
            </div>

            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="mis_pedidos.php" class="btn btn-primary rounded-pill px-4 py-2 fw-bold text-decoration-none shadow-sm d-inline-flex align-items-center gap-2">
                    <i data-lucide="package"></i> <span>Ver en Mis Pedidos</span>
                </a>
                <a href="index.php" class="btn btn-outline-secondary rounded-pill px-4 py-2 fw-semibold text-decoration-none d-inline-flex align-items-center gap-2">
                    <i data-lucide="home"></i> <span>Volver a la Tienda</span>
                </a>
                <a href="comparador.php" class="btn btn-outline-warning rounded-pill px-4 py-2 fw-semibold text-decoration-none text-dark d-inline-flex align-items-center gap-2">
                    <i data-lucide="git-compare"></i> <span>Seguir Viendo Comparativas</span>
                </a>
            </div>
        </div>

    </main>

    <!-- Footer Tech -->
    <footer class="bg-dark text-white py-4 mt-5 border-top border-secondary border-opacity-25">
        <div class="container text-center text-md-between d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div class="text-white-50 small">
                © <?= date('Y') ?> <?= htmlspecialchars($nombreTienda) ?>. Precios oficiales en <?= htmlspecialchars($simboloMoneda) ?>.
            </div>
            <div class="d-flex gap-3 small">
                <a href="index.php" class="text-white-50 text-decoration-none">Inicio</a>
                <a href="comparador.php" class="text-white-50 text-decoration-none">Comparador</a>
                <a href="carrito.php" class="text-white-50 text-decoration-none">Carrito</a>
                <a href="admin/login.php" class="text-white-50 text-decoration-none">Acceso Admin</a>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="assets/js/lucide.min.js"></script>
    <script src="assets/js/carrito.js"></script>
    <script src="assets/js/checkout.js"></script>
    <script>
        // Manejador interactivo para selección de método de pago
        document.querySelectorAll('.option-select-card').forEach(card => {
            card.addEventListener('click', () => {
                document.querySelectorAll('.option-select-card').forEach(c => {
                    c.classList.remove('selected');
                    const icon = c.querySelector('.check-icon');
                    if (icon) icon.innerHTML = '<i data-lucide="circle" class="text-secondary"></i>';
                });

                card.classList.add('selected');
                const activeIcon = card.querySelector('.check-icon');
                if (activeIcon) activeIcon.innerHTML = '<i data-lucide="check-circle-2" class="text-primary"></i>';

                const valor = card.dataset.value;
                document.getElementById('metodo_pago').value = valor;
                if (window.lucide) lucide.createIcons();
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
    <?php include __DIR__ . '/includes/chat_widget.php'; ?>
    <script src="assets/js/chat.js"></script>
</body>
</html>