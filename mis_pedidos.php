<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_cliente.php';
require_once __DIR__ . '/includes/icons.php';

// Si no está autenticado, redirigir a login
if (!cliente_esta_autenticado()) {
    header('Location: login.php?redirect=mis_pedidos.php');
    exit;
}

$cliente = cliente_actual();
$clienteId = (int)$cliente['id'];

// Cargar configuración de la tienda
$config = [];
try {
    $pdo = Database::getConnection();
    $stmtConfig = $pdo->query("SELECT clave, valor FROM configuracion");
    while ($row = $stmtConfig->fetch(PDO::FETCH_ASSOC)) {
        $config[$row['clave']] = $row['valor'];
    }
} catch (Exception $e) {
    // Silencioso
}

$nombreTienda  = $config['nombre_tienda'] ?? 'Nuestra Tienda';
$simboloMoneda = $config['moneda_simbolo'] ?? 'Bs';
$tasaCambio    = !empty($config['tasa_cambio_usd']) ? (float)$config['tasa_cambio_usd'] : 9.96;

// Obtener pedidos del cliente con sus detalles
$pedidos = [];
$totalInvertido = 0.0;
$pedidosPendientes = 0;
$pedidosEntregados = 0;

try {
    $stmt = $pdo->prepare("
        SELECT id, total, estado, notas, notificado_telegram, creado_en 
        FROM pedidos 
        WHERE cliente_id = ? 
        ORDER BY id DESC
    ");
    $stmt->execute([$clienteId]);
    $pedidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Obtener detalles de cada pedido
    if (!empty($pedidos)) {
        $stmtDetalle = $pdo->prepare("
            SELECT dp.id, dp.pedido_id, dp.producto_id, dp.cantidad, dp.precio_unitario, dp.subtotal,
                   p.nombre, p.imagen_url, p.sku
            FROM detalle_pedidos dp
            LEFT JOIN productos p ON dp.producto_id = p.id
            WHERE dp.pedido_id = ?
        ");

        foreach ($pedidos as &$ped) {
            $totalInvertido += (float)$ped['total'];
            if (in_array($ped['estado'], ['pendiente', 'confirmado', 'enviado'], true)) {
                $pedidosPendientes++;
            } elseif ($ped['estado'] === 'entregado') {
                $pedidosEntregados++;
            }

            $stmtDetalle->execute([(int)$ped['id']]);
            $ped['items'] = $stmtDetalle->fetchAll(PDO::FETCH_ASSOC);
        }
        unset($ped);
    }
} catch (Exception $e) {
    // Silencioso
}

// Iniciales o Avatar
$iniciales = '';
$partesNombre = explode(' ', trim($cliente['nombre']));
if (count($partesNombre) >= 2) {
    $iniciales = mb_strtoupper(mb_substr($partesNombre[0], 0, 1) . mb_substr($partesNombre[1], 0, 1));
} else {
    $iniciales = mb_strtoupper(mb_substr($cliente['nombre'], 0, 2));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos e Historial — <?= htmlspecialchars($nombreTienda) ?></title>
    
    <!-- Google Fonts: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Estilos Tech Premium -->
    <link rel="stylesheet" href="assets/css/estilos.css">
    <style>
        .portal-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #312e81 100%);
            border-radius: 20px;
            padding: 2.2rem;
            color: #fff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .portal-avatar {
            width: 76px;
            height: 76px;
            border-radius: 50%;
            border: 3px solid #6366f1;
            background: #4f46e5;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: 700;
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
            object-fit: cover;
        }
        .stat-card-tech {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 1.3rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
            transition: all 0.2s ease;
        }
        .stat-card-tech:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.07);
        }
        .order-card {
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            margin-bottom: 1.5rem;
            overflow: hidden;
            transition: all 0.2s ease;
        }
        .order-card:hover {
            border-color: #cbd5e1;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.04);
        }
        .order-header {
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
            padding: 1rem 1.4rem;
        }
        .badge-estado {
            font-size: 0.8rem;
            padding: 6px 12px;
            border-radius: 30px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .badge-pendiente { background: #fef3c7; color: #92400e; }
        .badge-confirmado { background: #e0f2fe; color: #0369a1; }
        .badge-enviado { background: #e0e7ff; color: #4338ca; }
        .badge-entregado { background: #dcfce7; color: #15803d; }
        .badge-cancelado { background: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body class="bg-slate-50">

    <!-- Navbar Sticky Tech Unificado -->
    <?php 
    $paginaActiva = 'pedidos';
    include __DIR__ . '/includes/navbar_store.php'; 
    ?>

    <!-- Contenido Principal -->
    <main class="container my-5">
        
        <!-- Header del Portal de Usuario -->
        <div class="portal-header mb-4">
            <div class="row align-items-center g-3">
                <div class="col-auto">
                    <?php if (!empty($cliente['avatar_url'])): ?>
                        <img src="<?= htmlspecialchars($cliente['avatar_url']) ?>" alt="Avatar" class="portal-avatar">
                    <?php else: ?>
                        <div class="portal-avatar"><?= htmlspecialchars($iniciales) ?></div>
                    <?php endif; ?>
                </div>
                <div class="col">
                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                        <h2 class="fw-bold mb-0 text-white"><?= htmlspecialchars($cliente['nombre']) ?></h2>
                        <?php if (!empty($cliente['google_id'])): ?>
                            <span class="badge bg-white text-dark small rounded-pill px-2 py-1 shadow-sm d-inline-flex align-items-center gap-1">
                                <i data-lucide="check-circle" style="width:14px;height:14px;" class="text-primary"></i> Cuenta Google
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="text-white-50 small d-flex flex-wrap gap-3 align-items-center">
                        <span class="d-inline-flex align-items-center gap-1"><i data-lucide="mail" style="width:14px;height:14px;" class="text-info"></i> <?= htmlspecialchars($cliente['email']) ?></span>
                        <?php if (!empty($cliente['telefono'])): ?>
                            <span class="d-inline-flex align-items-center gap-1"><i data-lucide="phone" style="width:14px;height:14px;" class="text-success"></i> <?= htmlspecialchars($cliente['telefono']) ?></span>
                        <?php else: ?>
                            <span class="text-warning-emphasis d-inline-flex align-items-center gap-1"><i data-lucide="alert-circle" style="width:14px;height:14px;"></i> Teléfono pendiente (se solicitará en tu próxima compra)</span>
                        <?php endif; ?>
                        <?php if (!empty($cliente['direccion'])): ?>
                            <span class="d-inline-flex align-items-center gap-1"><i data-lucide="map-pin" style="width:14px;height:14px;" class="text-warning"></i> <?= htmlspecialchars($cliente['direccion']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-auto text-lg-end">
                    <a href="index.php" class="btn btn-outline-light rounded-pill px-3 py-2 fw-semibold btn-sm me-2 d-inline-flex align-items-center gap-1">
                        <i data-lucide="shopping-bag" style="width:15px;height:15px;"></i> Comprar Más
                    </a>
                    <a href="api/auth_cliente.php?action=logout" class="btn btn-danger bg-opacity-75 rounded-pill px-3 py-2 fw-semibold btn-sm d-inline-flex align-items-center gap-1">
                        <i data-lucide="log-out" style="width:15px;height:15px;"></i> Salir
                    </a>
                </div>
            </div>
        </div>

        <!-- Métricas Rápidas del Cliente -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-md-4">
                <div class="stat-card-tech d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-primary-subtle text-primary" style="width: 48px; height: 48px;">
                        <i data-lucide="package" style="width:24px;height:24px;"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Pedidos Totales</div>
                        <div class="fs-4 fw-bold text-dark"><?= count($pedidos) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-md-4">
                <div class="stat-card-tech d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-warning-subtle text-warning" style="width: 48px; height: 48px;">
                        <i data-lucide="truck" style="width:24px;height:24px;"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">En Proceso / Pendientes</div>
                        <div class="fs-4 fw-bold text-dark"><?= $pedidosPendientes ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-4">
                <div class="stat-card-tech d-flex align-items-center gap-3">
                    <div class="rounded-circle d-flex align-items-center justify-content-center bg-success-subtle text-success" style="width: 48px; height: 48px;">
                        <i data-lucide="credit-card" style="width:24px;height:24px;"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-medium">Total Comprado</div>
                        <div class="fs-4 fw-bold text-dark"><?= htmlspecialchars($simboloMoneda) ?> <?= number_format($totalInvertido, 2) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Listado de Pedidos -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="fw-bold mb-0 text-dark d-flex align-items-center gap-2">
                <i data-lucide="clock" style="width:22px;height:22px;" class="text-primary"></i> Historial de Compras
            </h4>
            <span class="badge bg-slate-200 text-secondary fw-semibold"><?= count($pedidos) ?> registro(s)</span>
        </div>

        <?php if (empty($pedidos)): ?>
            <div class="tech-card p-5 text-center my-4">
                <div class="mb-3 text-muted d-flex justify-content-center">
                    <i data-lucide="package-search" style="width:48px;height:48px;" class="opacity-50"></i>
                </div>
                <h4 class="fw-bold text-dark">Aún no tienes pedidos registrados</h4>
                <p class="text-muted max-w-500 mx-auto mb-4">
                    Explora nuestro catálogo con cientos de productos tecnológicos con precios oficiales y agrega tus favoritos al carrito.
                </p>
                <a href="index.php" class="btn btn-primary rounded-pill px-4 py-2 fw-bold text-decoration-none shadow d-inline-flex align-items-center gap-2">
                    <i data-lucide="shopping-bag" style="width:18px;height:18px;"></i> Comenzar a Comprar
                </a>
            </div>
        <?php else: ?>
            <div class="row">
                <div class="col-12">
                    <?php foreach ($pedidos as $p): ?>
                        <?php
                            $estado = strtolower($p['estado']);
                            $badgeClass = 'badge-pendiente';
                            $iconoEstado = 'clock';
                            $textoEstado = 'Pendiente de Confirmación';

                            switch ($estado) {
                                case 'confirmado':
                                    $badgeClass = 'badge-confirmado';
                                    $iconoEstado = 'check';
                                    $textoEstado = 'Confirmado y en Preparación';
                                    break;
                                case 'enviado':
                                    $badgeClass = 'badge-enviado';
                                    $iconoEstado = 'truck';
                                    $textoEstado = 'En Camino / Despachado';
                                    break;
                                case 'entregado':
                                    $badgeClass = 'badge-entregado';
                                    $iconoEstado = 'check-circle-2';
                                    $textoEstado = 'Entregado con Éxito';
                                    break;
                                case 'cancelado':
                                    $badgeClass = 'badge-cancelado';
                                    $iconoEstado = 'ban';
                                    $textoEstado = 'Cancelado';
                                    break;
                            }
                        ?>
                        <div class="order-card shadow-sm">
                            <div class="order-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="fw-bold text-dark fs-5">
                                        Pedido #<?= $p['id'] ?>
                                    </div>
                                    <span class="badge-estado <?= $badgeClass ?>">
                                        <i data-lucide="<?= $iconoEstado ?>" style="width:14px;height:14px;"></i> <?= $textoEstado ?>
                                    </span>
                                </div>
                                <div class="text-muted small d-inline-flex align-items-center gap-1">
                                    <i data-lucide="calendar" style="width:14px;height:14px;"></i> <?= date('d/m/Y - H:i', strtotime($p['creado_en'])) ?>
                                </div>
                            </div>

                            <div class="p-3 p-md-4">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead class="text-muted small">
                                            <tr>
                                                <th>Artículo</th>
                                                <th class="text-center">Cant.</th>
                                                <th class="text-end">Precio Unit.</th>
                                                <th class="text-end">Subtotal</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($p['items'] as $item): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-3">
                                                            <?php if (!empty($item['imagen_url'])): ?>
                                                                <img src="<?= htmlspecialchars($item['imagen_url']) ?>" alt="Producto" class="rounded" style="width: 44px; height: 44px; object-fit: contain; background: #f8fafc; border: 1px solid #e2e8f0;">
                                                            <?php else: ?>
                                                                <div class="rounded bg-light d-flex align-items-center justify-content-center text-secondary" style="width: 44px; height: 44px; border: 1px solid #e2e8f0;">
                                                                    <i data-lucide="package" style="width:20px;height:20px;"></i>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div>
                                                                <div class="fw-bold text-dark small"><?= htmlspecialchars($item['nombre'] ?? 'Producto #' . $item['producto_id']) ?></div>
                                                                <?php if (!empty($item['sku'])): ?>
                                                                    <div class="text-muted small" style="font-size: 0.75rem;">SKU: <?= htmlspecialchars($item['sku']) ?></div>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center fw-semibold text-secondary">
                                                        <?= $item['cantidad'] ?>
                                                    </td>
                                                    <td class="text-end text-muted small">
                                                        <?= htmlspecialchars($simboloMoneda) ?> <?= number_format((float)$item['precio_unitario'], 2) ?>
                                                    </td>
                                                    <td class="text-end fw-bold text-dark">
                                                        <?= htmlspecialchars($simboloMoneda) ?> <?= number_format((float)$item['subtotal'], 2) ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row align-items-center mt-3 pt-3 border-top g-2">
                                    <div class="col-md-7">
                                        <?php if (!empty($p['notas'])): ?>
                                            <div class="small text-muted bg-light p-2 rounded border d-flex align-items-center gap-1">
                                                <i data-lucide="file-text" style="width:14px;height:14px;" class="text-secondary"></i> <strong>Detalle / Método:</strong> <?= htmlspecialchars($p['notas']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-5 text-md-end">
                                        <span class="text-muted me-2 small">Total Pagado:</span>
                                        <span class="fs-4 fw-bold text-primary"><?= htmlspecialchars($simboloMoneda) ?> <?= number_format((float)$p['total'], 2) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

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
                <a href="mis_pedidos.php" class="text-white text-decoration-none fw-semibold">Mis Pedidos</a>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/lucide.min.js"></script>
    <script>
        if (window.lucide) {
            lucide.createIcons();
        }
    </script>
    <script src="assets/js/carrito.js"></script>
    <?php include __DIR__ . '/includes/chat_widget.php'; ?>
    <script src="assets/js/chat.js"></script>
</body>
</html>
