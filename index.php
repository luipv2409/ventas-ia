<?php
declare(strict_types=1);
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/icons.php';

// Conexión a la base de datos
$pdo = Database::getConnection();

// Obtener configuraciones
$config = [];
$stmtConf = $pdo->query("SELECT clave, valor FROM configuracion");
if ($stmtConf) {
    while ($row = $stmtConf->fetch(PDO::FETCH_ASSOC)) {
        $config[$row['clave']] = $row['valor'];
    }
}

// Valores con fallback
$nombreTienda  = $config['nombre_tienda'] ?? 'Nuestra Tienda';
$simboloMoneda = $config['moneda_simbolo'] ?? 'Bs';
$nombreAsesor  = !empty($config['nombre_asesor']) ? $config['nombre_asesor'] : 'Valeria';

// Obtener categorías para el filtro
$categorias = $pdo->query("SELECT id, nombre, icono FROM categorias WHERE activa = 1 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

$iconosCats = [
    1 => 'fas fa-mobile-screen-button',
    2 => 'fas fa-tablet-screen-button',
    3 => 'fas fa-headphones',
    4 => 'fas fa-shoe-prints',
    5 => 'fas fa-shirt',
    6 => 'fas fa-blender',
    7 => 'fas fa-bed',
    8 => 'fas fa-mug-hot'
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($nombreTienda) ?> — Tienda Oficial Tech & Moda</title>
    
    <!-- Google Fonts: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 & Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Estilos Tech Premium -->
    <link rel="stylesheet" href="assets/css/estilos.css">
</head>
<body>

    <!-- Navbar Sticky Tech Unificado -->
    <?php 
    $paginaActiva = 'catalogo';
    include __DIR__ . '/includes/navbar_store.php'; 
    ?>

    <!-- Hero Section Tech Premium -->
    <div class="hero-tech py-5 mb-4">
        <div class="container text-center">
            <div class="hero-chip mb-3">
                <i data-lucide="shield-check" class="text-info me-1"></i>
                <span>Garantía oficial y envíos a toda Bolivia</span>
            </div>
            <h1 class="display-4 fw-bold gradient-title mb-3">
                Tecnología, Moda y Hogar
            </h1>
            <p class="lead text-light text-opacity-75 mx-auto mb-4" style="max-width: 620px; font-size: 1.05rem;">
                Precios oficiales en Bolivianos (Bs), garantía de fábrica y envíos rápidos a todo el país.
            </p>

            <!-- Trust Pills -->
            <div class="d-flex flex-wrap justify-content-center gap-2 pt-1">
                <span class="trust-badge-pill"><i data-lucide="truck" class="text-info"></i> Envíos a toda Bolivia</span>
                <span class="trust-badge-pill"><i data-lucide="shield-check" class="text-success"></i> Garantía Oficial</span>
                <span class="trust-badge-pill"><i data-lucide="message-square" class="text-warning"></i> Atención por Chat</span>
                <span class="trust-badge-pill"><i data-lucide="banknote" class="text-primary"></i> Precios en Bs</span>
            </div>
        </div>
    </div>

    <!-- Contenido Principal: Catálogo Organizado por Departamentos -->
    <div class="container my-4" id="catalogo-seccion">
        
        <!-- Elementos de compatibilidad con tests anteriores -->
        <div id="col-filtros" class="d-none"></div>
        <button type="button" id="btn-toggle-filtros" class="d-none"></button>
        
        <div id="col-productos" class="col-12">
            <!-- Barra de Control y Pestañas de Departamentos Minimalista -->
        <div class="bg-white rounded-4 border p-3 p-md-4 shadow-sm mb-4">
            
            <!-- Fila 1: Pestañas de Departamentos (Category Pills Scroller) -->
            <div class="mb-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-bold small text-muted text-uppercase tracking-wider d-flex align-items-center gap-1">
                        <i data-lucide="layers" class="text-primary"></i> <span>Departamentos Disponibles</span>
                    </div>
                    <span id="contador-resultados" class="badge bg-slate-100 text-secondary border rounded-pill px-3 py-1 font-monospace">
                        Cargando...
                    </span>
                </div>
                
                <div class="category-pills-scroller">
                    <button type="button" class="category-pill-tab active" data-categoria="todas">
                        <i data-lucide="layout-grid"></i>
                        <span>Todos los Departamentos</span>
                    </button>
                    <?php foreach ($categorias as $cat): ?>
                        <?php $lucideIcon = resolverIconoLucide($cat['icono'] ?? '', (int)$cat['id']); ?>
                        <button type="button" class="category-pill-tab" data-categoria="<?= $cat['id'] ?>">
                            <i data-lucide="<?= $lucideIcon ?>"></i>
                            <span><?= htmlspecialchars($cat['nombre']) ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Fila 2: Barra de Control (Buscador en vivo, Orden y Botón de Precio) -->
            <form id="form-filtros" class="row g-2 align-items-center">
                <input type="hidden" name="categoria_id" id="filtro-categoria-hidden" value="todas">

                <!-- Buscador en vivo -->
                <div class="col-md-5 col-lg-6">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i data-lucide="search"></i>
                        </span>
                        <input type="text" name="texto" id="filtro-texto" class="form-control bg-light border-start-0 ps-0" placeholder="Buscar producto, marca o modelo (ej. iPhone, Nike, Café)..." autocomplete="off">
                    </div>
                </div>

                <!-- Ordenador -->
                <div class="col-6 col-md-3 col-lg-3">
                    <select name="orden" id="filtro-orden" class="form-select bg-light text-secondary">
                        <option value="nombre" selected>Ordenar: Nombre (A-Z)</option>
                        <option value="precio_asc">Precio: Menor a Mayor</option>
                        <option value="precio_desc">Precio: Mayor a Menor</option>
                    </select>
                </div>

                <!-- Botones de Acción / Drawer de Precio -->
                <div class="col-6 col-md-4 col-lg-3 d-flex gap-2 justify-content-end">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3 py-2 btn-sm flex-grow-1 d-flex align-items-center justify-content-center gap-1" data-bs-toggle="collapse" data-bs-target="#collapsePrecio">
                        <i data-lucide="sliders-horizontal"></i> <span>Rango Bs</span>
                    </button>
                    <a href="comparador.php" class="btn btn-outline-warning text-dark rounded-pill px-3 py-2 btn-sm fw-semibold d-flex align-items-center gap-1" title="Comparador Kimovil">
                        <i data-lucide="git-compare"></i> <span class="d-none d-sm-inline">Kimovil</span>
                    </a>
                    <button type="button" id="btn-limpiar-filtros" class="btn btn-light border rounded-circle text-muted p-0 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;" title="Limpiar filtros">
                        <i data-lucide="rotate-ccw" class="small"></i>
                    </button>
                </div>

                <!-- Drawer colapsable para rango de precio -->
                <div class="col-12 collapse mt-2" id="collapsePrecio">
                    <div class="p-3 bg-light rounded-3 border">
                        <div class="row g-2 align-items-center">
                            <div class="col-sm-5">
                                <label class="form-label small fw-semibold text-secondary mb-1">Precio Mínimo (<?= $simboloMoneda ?>)</label>
                                <input type="number" step="0.01" name="precio_min" class="form-control form-control-sm" placeholder="Ej. 100">
                            </div>
                            <div class="col-sm-5">
                                <label class="form-label small fw-semibold text-secondary mb-1">Precio Máximo (<?= $simboloMoneda ?>)</label>
                                <input type="number" step="0.01" name="precio_max" class="form-control form-control-sm" placeholder="Ej. 2500">
                            </div>
                            <div class="col-sm-2 text-end pt-sm-4">
                                <button type="submit" class="btn btn-primary btn-sm rounded-pill w-100 fw-bold">
                                    Aplicar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

        </div>

        <!-- Skeleton Loader -->
        <div id="skeleton-container" class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-4">
            <?php for ($i = 0; $i < 8; $i++): ?>
                <div class="col">
                    <div class="card minimal-product-card p-3" style="min-height: 380px;">
                        <div class="bg-secondary-subtle rounded-3 mb-3" style="height: 200px;"></div>
                        <div class="bg-secondary-subtle rounded-pill w-50 mb-2" style="height: 16px;"></div>
                        <div class="bg-secondary-subtle rounded-pill w-75 mb-3" style="height: 20px;"></div>
                        <div class="bg-secondary-subtle rounded-pill w-25 mt-auto" style="height: 24px;"></div>
                    </div>
                </div>
            <?php endfor; ?>
        </div>

        <!-- Contenedor Real de Productos (Agrupado por Departamentos o Vista Filtrada) -->
        <div id="productos-container" style="display: none;"></div>

        <!-- Mensaje sin resultados -->
        <div id="sin-resultados" class="text-center py-5" style="display: none;">
            <div class="card border-0 shadow-sm p-5 rounded-4 mx-auto" style="max-width: 480px; background: #ffffff;">
                <i data-lucide="package-open" class="text-muted mb-3 opacity-25 mx-auto" style="width: 48px; height: 48px;"></i>
                <h4 class="fw-bold text-dark">No se encontraron productos</h4>
                <p class="text-muted small mb-3">Intenta con otros términos o consulta directamente con Valeria en el chat en vivo.</p>
                <div>
                    <button type="button" class="btn btn-outline-primary rounded-pill px-4 btn-sm fw-semibold d-inline-flex align-items-center gap-2" onclick="window.limpiarTodosFiltros()">
                        <i data-lucide="rotate-ccw"></i> <span>Ver todos los departamentos</span>
                    </button>
                </div>
            </div>
        </div>

        </div> <!-- Fin #col-productos -->
    </div> <!-- Fin #catalogo-seccion -->

    <!-- Footer Tech -->
    <footer class="bg-dark text-white mt-5 py-5 border-top border-secondary border-opacity-25">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <h5 class="brand-font fw-bold text-white mb-3 d-flex align-items-center gap-2">
                        <i data-lucide="cpu" class="text-warning"></i> <?= htmlspecialchars($nombreTienda) ?>
                    </h5>
                    <p class="text-secondary small mb-3">Tecnología, moda y hogar con garantía y entregas oficiales en toda Bolivia.</p>
                    <span class="badge bg-secondary-subtle text-secondary border px-3 py-2 rounded-pill">1 USD = <?= $config['tasa_cambio_usd'] ?? '9.96' ?> Bs</span>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold text-uppercase text-secondary small mb-3">Enlaces Rápidos</h6>
                    <ul class="list-unstyled small d-flex flex-column gap-2 text-secondary">
                        <li><a href="index.php" class="text-decoration-none text-secondary">Catálogo General</a></li>
                        <li><a href="comparador.php" class="text-decoration-none text-warning fw-semibold d-inline-flex align-items-center gap-1"><i data-lucide="git-compare"></i> <span>Comparador</span></a></li>
                        <li><a href="carrito.php" class="text-decoration-none text-secondary">Carrito de Compras</a></li>
                        <li><a href="admin/dashboard.php" class="text-decoration-none text-secondary">Panel Administrativo</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold text-uppercase text-secondary small mb-3">Atención al Cliente</h6>
                    <p class="text-secondary small mb-2 d-inline-flex align-items-center gap-2"><i data-lucide="phone-call" style="width:15px;height:15px;" class="text-success"></i> WhatsApp: <?= htmlspecialchars($config['contacto_telefono'] ?? 'Disponible en chat') ?></p>
                    <p class="text-secondary small mb-0 d-inline-flex align-items-center gap-1"><i data-lucide="shield-check" class="text-primary me-1"></i> <span>Envíos y entregas garantizadas en Bolivia.</span></p>
                </div>
            </div>
            <div class="border-top border-secondary border-opacity-25 mt-4 pt-3 text-center text-secondary small">
                © <?= date('Y') ?> <?= htmlspecialchars($nombreTienda) ?>. Todos los derechos reservados.
            </div>
        </div>
    </footer>

    <?php include __DIR__ . '/includes/chat_widget.php'; ?>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/lucide.min.js"></script>
    <script src="assets/js/carrito.js"></script>
    <script src="assets/js/chat.js"></script>
    <script src="assets/js/catalogo.js"></script>
    <script>
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            lucide.createIcons();
        }
    </script>
</body>
</html>