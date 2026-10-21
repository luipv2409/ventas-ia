<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

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
    <title>Tu Carrito de Compras — <?= htmlspecialchars($nombreTienda) ?></title>
    
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
    $paginaActiva = 'carrito';
    include __DIR__ . '/includes/navbar_store.php'; 
    ?>

    <!-- Contenido Principal -->
    <main class="container my-5">
        
        <!-- Stepper de Progreso -->
        <div class="stepper-tech">
            <div class="step-item active">
                <div class="step-number"><i data-lucide="shopping-bag" class="lucide-sm"></i></div>
                <span>1. Tu Carrito</span>
            </div>
            <div class="step-separator"></div>
            <div class="step-item">
                <div class="step-number">2</div>
                <span>2. Datos de Envío</span>
            </div>
            <div class="step-separator"></div>
            <div class="step-item">
                <div class="step-number">3</div>
                <span>3. Confirmación</span>
            </div>
        </div>

        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="fw-bold mb-1 d-flex align-items-center gap-2">
                    <i data-lucide="shopping-bag" class="text-primary"></i> <span>Bolsa de Compras</span>
                </h2>
                <p class="text-muted small mb-0">Revisa tus artículos seleccionados antes de finalizar el pedido.</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary rounded-pill px-3 py-1 text-decoration-none small d-none d-md-inline-flex align-items-center gap-1">
                <i data-lucide="arrow-left" class="me-1"></i> Seguir Comprando
            </a>
        </div>

        <!-- Contenedor Dinámico del Carrito -->
        <div id="carrito-contenedor-principal" class="row g-4">
            <!-- Columna Izquierda: Tabla de Productos -->
            <div class="col-lg-8">
                <div class="tech-card">
                    <div class="tech-card-header">
                        <span class="fw-bold text-dark" id="carrito-items-count">Productos en tu bolsa</span>
                        <button type="button" class="btn btn-sm text-danger text-decoration-none fw-semibold p-0 d-inline-flex align-items-center gap-1" id="btn-vaciar-carrito">
                            <i data-lucide="trash-2"></i> <span>Vaciar Carrito</span>
                        </button>
                    </div>
                    <div class="table-responsive">
                        <div id="carrito-tabla-wrapper"></div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Resumen de Compra -->
            <div class="col-lg-4">
                <div class="cart-summary-box tech-card p-4">
                    <h5 class="fw-bold mb-3 text-dark">Resumen del Pedido</h5>
                    
                    <div class="summary-row">
                        <span>Subtotal de productos</span>
                        <span class="fw-semibold text-dark" id="resumen-subtotal">Bs 0.00</span>
                    </div>
                    <div class="summary-row">
                        <span>Envío a domicilio</span>
                        <span class="badge bg-success-subtle text-success fw-bold px-2 py-1">Gratis</span>
                    </div>
                    <div class="summary-row">
                        <span>Impuestos y tasas</span>
                        <span class="text-muted">Incluidos</span>
                    </div>

                    <div class="summary-row total">
                        <span>Total a Pagar</span>
                        <div class="text-end">
                            <div class="text-primary fs-3 fw-bold" id="resumen-total-bs">Bs 0.00</div>
                            <div class="text-muted small fw-normal" id="resumen-total-usd">≈ $0.00 USD</div>
                        </div>
                    </div>

                    <div class="mt-4">
                        <a href="checkout.php" class="btn-tech-checkout text-decoration-none">
                            <i data-lucide="shield-check"></i> Finalizar Compra
                        </a>
                        <a href="index.php" class="btn btn-outline-secondary rounded-pill w-100 mt-2 py-2 fw-medium text-decoration-none small">
                            ← Continuar Comprando
                        </a>
                    </div>

                    <!-- Badges de Confianza -->
                    <div class="trust-badge-row">
                        <div class="trust-badge-item">
                            <i data-lucide="shield-check" class="text-success"></i>
                            <span>Garantía oficial y factura</span>
                        </div>
                        <div class="trust-badge-item">
                            <i data-lucide="truck" class="text-success"></i>
                            <span>Envíos a toda Bolivia</span>
                        </div>
                        <div class="trust-badge-item">
                            <i data-lucide="message-square" class="text-success"></i>
                            <span>Atención directa por WhatsApp</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estado Vacío (Oculto por defecto) -->
        <div id="carrito-vacio-box" class="tech-card p-5 text-center d-none my-4">
            <div class="mb-3">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 84px; height: 84px; background: #eef2ff; color: #4f46e5;">
                    <i data-lucide="shopping-bag" style="width: 44px; height: 44px;"></i>
                </div>
            </div>
            <h3 class="fw-bold mb-2">Tu bolsa de compras está vacía</h3>
            <p class="text-muted mx-auto mb-4" style="max-width: 480px;">
                Parece que aún no has agregado productos. Explora nuestro catálogo de productos.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="index.php" class="btn btn-primary rounded-pill px-4 py-2 fw-bold text-decoration-none d-inline-flex align-items-center gap-2">
                    <i data-lucide="layout-grid"></i> <span>Explorar Catálogo</span>
                </a>
                <a href="comparador.php" class="btn btn-outline-warning rounded-pill px-4 py-2 fw-bold text-decoration-none text-dark d-inline-flex align-items-center gap-2">
                    <i data-lucide="git-compare"></i> <span>Comparador</span>
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
    <script src="assets/js/carrito.js"></script>
    <script>
        function renderizarVistaCarrito() {
            const items = carrito.obtener();
            const contenedorPrincipal = document.getElementById('carrito-contenedor-principal');
            const vacioBox = document.getElementById('carrito-vacio-box');
            const tablaWrapper = document.getElementById('carrito-tabla-wrapper');
            const itemsCount = document.getElementById('carrito-items-count');

            if (items.length === 0) {
                contenedorPrincipal.classList.add('d-none');
                vacioBox.classList.remove('d-none');
                return;
            }

            contenedorPrincipal.classList.remove('d-none');
            vacioBox.classList.add('d-none');

            const totalArticulos = carrito.cantidadTotal();
            itemsCount.textContent = `${totalArticulos} ${totalArticulos === 1 ? 'artículo' : 'artículos'} en tu bolsa`;

            let tablaHtml = `
                <table class="table-tech-cart">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th class="text-center">Precio</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-center" style="width: 50px;"></th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            items.forEach(item => {
                const subtotal = item.precio * item.cantidad;
                tablaHtml += `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="cart-item-avatar">
                                    <i data-lucide="package"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark mb-1">${escapeHtml(item.nombre)}</div>
                                    <span class="badge bg-light text-secondary border small">SKU #${item.id || 'N/A'}</span>
                                </div>
                            </div>
                        </td>
                        <td class="text-center fw-medium text-dark">
                            ${SIMBOLO_MONEDA} ${item.precio.toFixed(2)}
                        </td>
                        <td class="text-center">
                            <div class="qty-control">
                                <button type="button" class="btn-qty btn-restar" data-nombre="${escapeHtml(item.nombre)}" title="Restar">-</button>
                                <span class="qty-display">${item.cantidad}</span>
                                <button type="button" class="btn-qty btn-sumar" data-nombre="${escapeHtml(item.nombre)}" title="Sumar">+</button>
                            </div>
                        </td>
                        <td class="text-end fw-bold text-dark">
                            ${SIMBOLO_MONEDA} ${subtotal.toFixed(2)}
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn-delete-cart btn-eliminar-item" data-nombre="${escapeHtml(item.nombre)}" title="Eliminar de la bolsa">
                                <i data-lucide="trash-2"></i>
                            </button>
                        </td>
                    </tr>
                `;
            });

            tablaHtml += `
                    </tbody>
                </table>
            `;

            tablaWrapper.innerHTML = tablaHtml;
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }

            // Actualizar resumen
            const totalBs = carrito.total();
            const totalUsd = TASA_CAMBIO > 0 ? (totalBs / TASA_CAMBIO) : totalBs;

            document.getElementById('resumen-subtotal').textContent = `${SIMBOLO_MONEDA} ${totalBs.toFixed(2)}`;
            document.getElementById('resumen-total-bs').textContent = `${SIMBOLO_MONEDA} ${totalBs.toFixed(2)}`;
            document.getElementById('resumen-total-usd').textContent = `≈ $${totalUsd.toFixed(2)} USD`;

            // Event Listeners para Sumar y Restar
            document.querySelectorAll('.btn-sumar').forEach(btn => {
                btn.addEventListener('click', () => {
                    const nombre = btn.dataset.nombre;
                    const itms = carrito.obtener();
                    const itm = itms.find(i => i.nombre === nombre);
                    if (itm) {
                        itm.cantidad++;
                        carrito.guardar(itms);
                        renderizarVistaCarrito();
                        carrito.actualizarContador();
                    }
                });
            });

            document.querySelectorAll('.btn-restar').forEach(btn => {
                btn.addEventListener('click', () => {
                    const nombre = btn.dataset.nombre;
                    let itms = carrito.obtener();
                    const itm = itms.find(i => i.nombre === nombre);
                    if (itm) {
                        if (itm.cantidad > 1) {
                            itm.cantidad--;
                        } else {
                            itms = itms.filter(i => i.nombre !== nombre);
                        }
                        carrito.guardar(itms);
                        renderizarVistaCarrito();
                        carrito.actualizarContador();
                    }
                });
            });

            document.querySelectorAll('.btn-eliminar-item').forEach(btn => {
                btn.addEventListener('click', () => {
                    const nombre = btn.dataset.nombre;
                    carrito.eliminar(nombre);
                    renderizarVistaCarrito();
                    carrito.actualizarContador();
                });
            });
        }

        // Vaciar Carrito
        document.getElementById('btn-vaciar-carrito').addEventListener('click', () => {
            if (confirm('¿Estás seguro de que deseas vaciar todos los artículos de tu bolsa?')) {
                carrito.vaciar();
                renderizarVistaCarrito();
                carrito.actualizarContador();
            }
        });

        function escapeHtml(text) {
            const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderizarVistaCarrito();
            carrito.actualizarContador();
            if (window.lucide && typeof window.lucide.createIcons === 'function') {
                window.lucide.createIcons();
            }
        });

        // Exponer globalmente para que el chat de Valeria pueda actualizar la vista en vivo
        window.renderizarVistaCarrito = renderizarVistaCarrito;
    </script>

    <?php include __DIR__ . '/includes/chat_widget.php'; ?>
    <script src="assets/js/lucide.min.js"></script>
    <script src="assets/js/chat.js"></script>
</body>
</html>