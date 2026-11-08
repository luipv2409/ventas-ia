<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/icons.php';

$pdo = Database::getConnection();

// Configuración de moneda
$stmtConf = $pdo->query("SELECT clave, valor FROM configuracion WHERE clave IN ('moneda_simbolo', 'tasa_cambio_usd')");
$config = [];
if ($stmtConf) {
    while ($row = $stmtConf->fetch()) {
        $config[$row['clave']] = $row['valor'];
    }
}
$simbolo = $config['moneda_simbolo'] ?? 'Bs';
$tasa = (float)($config['tasa_cambio_usd'] ?? 9.96);
if ($tasa <= 0) $tasa = 1.0;

// Parámetros
$texto = trim($_GET['texto'] ?? '');
$categoria_id = $_GET['categoria_id'] ?? null;
$precio_min = $_GET['precio_min'] ?? null;
$precio_max = $_GET['precio_max'] ?? null;
$orden = $_GET['orden'] ?? 'nombre';

// Iconos por defecto por categoría
$iconosCats = [
    1 => 'fas fa-mobile-screen-button', // Celulares
    2 => 'fas fa-tablet-screen-button', // Tablets
    3 => 'fas fa-headphones',           // Audio
    4 => 'fas fa-shoe-prints',          // Calzado
    5 => 'fas fa-shirt',                // Ropa
    6 => 'fas fa-blender',              // Cocina
    7 => 'fas fa-bed',                  // Cama / Hogar
    8 => 'fas fa-mug-hot'               // Café
];

// Consulta base
$sql = "SELECT p.*, c.nombre AS categoria, c.icono AS categoria_icono 
        FROM productos p
        INNER JOIN categorias c ON p.categoria_id = c.id
        WHERE p.activo = 1 AND p.stock > 0";
$params = [];

if ($texto !== '') {
    $sql .= " AND (p.nombre LIKE :texto OR p.descripcion LIKE :texto2)";
    $params[':texto'] = "%$texto%";
    $params[':texto2'] = "%$texto%";
}

$filtroCategoriaEspecifica = ($categoria_id && $categoria_id !== 'todas');
if ($filtroCategoriaEspecifica) {
    $sql .= " AND p.categoria_id = :cat_id";
    $params[':cat_id'] = (int)$categoria_id;
}

if ($precio_min !== null && $precio_min !== '') {
    $sql .= " AND p.precio >= :precio_min";
    $params[':precio_min'] = round(((float)$precio_min) / $tasa, 4);
}
if ($precio_max !== null && $precio_max !== '') {
    $sql .= " AND p.precio <= :precio_max";
    $params[':precio_max'] = round(((float)$precio_max) / $tasa, 4);
}

// Ordenamiento estructurado
if ($filtroCategoriaEspecifica) {
    switch ($orden) {
        case 'precio_asc': $sql .= " ORDER BY p.precio ASC"; break;
        case 'precio_desc': $sql .= " ORDER BY p.precio DESC"; break;
        case 'nombre': default: $sql .= " ORDER BY p.nombre ASC"; break;
    }
} else {
    // Si vemos todas las categorías, agrupamos primero por categoría para mantener el orden
    switch ($orden) {
        case 'precio_asc': $sql .= " ORDER BY p.categoria_id ASC, p.precio ASC"; break;
        case 'precio_desc': $sql .= " ORDER BY p.categoria_id ASC, p.precio DESC"; break;
        case 'nombre': default: $sql .= " ORDER BY p.categoria_id ASC, p.nombre ASC"; break;
    }
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

/**
 * Renderiza una tarjeta de producto minimalista y elegante
 */
if (!function_exists('renderizarTarjetaMinimalista')) {
    function renderizarTarjetaMinimalista(array $prod, float $tasa, string $simbolo, array $iconosCats): string {
        $precioConvertido = $prod['precio'] * $tasa;
        $specs = !empty($prod['especificaciones']) ? json_decode($prod['especificaciones'], true) : [];
        $kimovilScore = $specs['kimovil_score'] ?? null;
        $stock = (int)$prod['stock'];
        $catId = (int)$prod['categoria_id'];
        $lucideCat = resolverIconoLucide($prod['categoria_icono'] ?? '', $catId);
        $img = !empty($prod['imagen_url']) ? $prod['imagen_url'] : 'https://via.placeholder.com/300x300?text=Sin+imagen';

        // Extracción de especificación destacada concisa
        $specHighlight = '';
        if (!empty($specs['procesador'])) {
            $specHighlight = explode(' ', $specs['procesador'])[0];
        } elseif (!empty($specs['material'])) {
            $specHighlight = $specs['material'];
        } elseif (!empty($specs['potencia'])) {
            $specHighlight = $specs['potencia'];
        } elseif (!empty($specs['tostado'])) {
            $specHighlight = 'Tostado ' . $specs['tostado'];
        } elseif (!empty($specs['tipo'])) {
            $specHighlight = $specs['tipo'];
        }

        ob_start();
        ?>
        <div class="col">
            <div class="minimal-product-card h-100">
                <!-- Contenedor de Imagen con fondo suave y padding para evitar distorsiones -->
                <div class="product-media-box">
                    <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($prod['nombre']) ?>" loading="lazy">
                    
                    <span class="minimal-cat-tag">
                        <i data-lucide="<?= htmlspecialchars($lucideCat) ?>" class="me-1"></i> <?= htmlspecialchars($prod['categoria']) ?>
                    </span>

                    <?php if ($kimovilScore): ?>
                        <span class="minimal-score-tag" title="Rating de rendimiento Kimovil">
                            <i data-lucide="gauge" class="text-warning me-1"></i> <?= htmlspecialchars($kimovilScore) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="product-info-box d-flex flex-column flex-grow-1 p-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="minimal-stock-badge <?= $stock < 5 ? 'low' : '' ?>">
                            <span class="stock-dot"></span> <?= $stock ?> en stock
                        </span>
                        <?php if (!empty($specHighlight)): ?>
                            <span class="minimal-spec-badge text-truncate" style="max-width: 125px;" title="<?= htmlspecialchars($specHighlight) ?>">
                                <?= htmlspecialchars($specHighlight) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <h5 class="product-card-title fw-bold text-dark mb-1" title="<?= htmlspecialchars($prod['nombre']) ?>">
                        <?= htmlspecialchars($prod['nombre']) ?>
                    </h5>
                    
                    <p class="product-card-desc text-muted small mb-3 flex-grow-1">
                        <?= htmlspecialchars(mb_substr($prod['descripcion'] ?? '', 0, 85)) ?>...
                    </p>

                    <div class="product-card-bottom pt-2 border-top mt-auto">
                        <div class="d-flex justify-content-between align-items-baseline mb-2">
                            <div>
                                <span class="price-official fw-bold brand-font text-dark fs-5">
                                    <?= $simbolo ?> <?= number_format($precioConvertido, 2) ?>
                                </span>
                                <small class="price-ref text-muted d-block">
                                    ≈ $<?= number_format((float)$prod['precio'], 2) ?> USD base
                                </small>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button class="btn btn-primary btn-sm rounded-pill flex-grow-1 fw-bold add-to-cart py-2 d-flex align-items-center justify-content-center gap-1 shadow-sm"
                                    data-id="<?= $prod['id'] ?>" 
                                    data-nombre="<?= htmlspecialchars($prod['nombre']) ?>" 
                                    data-precio="<?= $precioConvertido ?>" 
                                    data-stock="<?= $prod['stock'] ?>">
                                <i data-lucide="shopping-bag" class="me-1"></i> <span>Añadir</span>
                            </button>
                            <?php if ($catId == 1 || $catId == 2 || $catId == 3 || !empty($prod['especificaciones'])): ?>
                                <a href="comparador.php?p1=<?= $prod['id'] ?>&categoria=<?= $catId ?>" 
                                   class="btn btn-outline-warning text-dark btn-sm rounded-pill px-3 py-2 fw-semibold d-flex align-items-center justify-content-center" 
                                   title="Comparar ficha técnica Kimovil">
                                    <i data-lucide="git-compare"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Generación del HTML estructurado
ob_start();

if (empty($productos)) {
    ?>
    <div class="col-12 text-center py-5">
        <div class="card border-0 shadow-sm p-5 rounded-4 mx-auto" style="max-width: 480px; background: #ffffff;">
            <i data-lucide="package-search" class="text-muted mb-3 d-block opacity-50 mx-auto" style="width: 48px; height: 48px;"></i>
            <h4 class="fw-bold text-dark">No se encontraron productos</h4>
            <p class="text-muted small mb-3">No hay artículos que coincidan con tus criterios de búsqueda en esta sección.</p>
            <div>
                <button type="button" class="btn btn-outline-primary rounded-pill px-4 btn-sm fw-semibold d-inline-flex align-items-center gap-2" onclick="window.cambiarCategoriaTab('todas')">
                    <i data-lucide="rotate-ccw"></i> <span>Ver todos los departamentos</span>
                </button>
            </div>
        </div>
    </div>
    <?php
} else {
    if ($filtroCategoriaEspecifica) {
        // Vista de categoría individual seleccionada
        $primero = $productos[0];
        $nombreCat = $primero['categoria'];
        $lucideCat = resolverIconoLucide($primero['categoria_icono'] ?? '', (int)$categoria_id);
        ?>
        <div class="col-12 mb-3">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 bg-white rounded-4 border shadow-sm">
                <div class="d-flex align-items-center gap-3">
                    <div class="shelf-icon-badge" style="width: 42px; height: 42px;">
                        <i data-lucide="<?= htmlspecialchars($lucideCat) ?>"></i>
                    </div>
                    <div>
                        <h4 class="fw-bold text-dark mb-0"><?= htmlspecialchars($nombreCat) ?></h4>
                        <span class="text-muted small"><?= count($productos) ?> producto(s) en este departamento</span>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1" onclick="window.cambiarCategoriaTab('todas')">
                    <i data-lucide="layout-grid"></i> <span>Ver todos los departamentos</span>
                </button>
            </div>
        </div>

        <div class="col-12">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-4">
                <?php foreach ($productos as $prod): ?>
                    <?= renderizarTarjetaMinimalista($prod, $tasa, $simbolo, $iconosCats) ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    } else {
        // Vista de todos los departamentos: Organizada en estantes/secciones limpias
        $agrupados = [];
        foreach ($productos as $prod) {
            $catId = (int)$prod['categoria_id'];
            if (!isset($agrupados[$catId])) {
                $agrupados[$catId] = [
                    'id' => $catId,
                    'nombre' => $prod['categoria'],
                    'icono' => resolverIconoLucide($prod['categoria_icono'] ?? '', $catId),
                    'items' => []
                ];
            }
            $agrupados[$catId]['items'][] = $prod;
        }

        // Si es una búsqueda libre, mostramos un banner de resultados
        if ($texto !== '' || $precio_min !== null || $precio_max !== null) {
            ?>
            <div class="col-12 mb-3">
                <div class="alert alert-light border rounded-4 p-3 d-flex justify-content-between align-items-center gap-2">
                    <div class="small text-muted d-flex align-items-center gap-2">
                        <i data-lucide="sliders-horizontal" class="text-primary"></i>
                        <span>Resultados encontrados: <strong><?= count($productos) ?></strong> artículo(s) organizado(s) por departamento</span>
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-decoration-none text-secondary p-0" onclick="window.limpiarTodosFiltros()">
                        Limpiar filtros
                    </button>
                </div>
            </div>
            <?php
        }

        // Renderizado por Departamento / Estantería
        foreach ($agrupados as $catGrupo) {
            ?>
            <div class="col-12 department-shelf mb-4" id="seccion-cat-<?= $catGrupo['id'] ?>">
                <div class="shelf-header d-flex justify-content-between align-items-center pb-2 mb-3 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <span class="shelf-icon-badge">
                            <i data-lucide="<?= htmlspecialchars($catGrupo['icono']) ?>"></i>
                        </span>
                        <div>
                            <h4 class="fw-bold mb-0 text-dark fs-5"><?= htmlspecialchars($catGrupo['nombre']) ?></h4>
                            <span class="text-muted small" style="font-size: 0.78rem;"><?= count($catGrupo['items']) ?> producto(s)</span>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-link text-primary text-decoration-none fw-semibold d-flex align-items-center gap-1 p-0" onclick="window.cambiarCategoriaTab(<?= $catGrupo['id'] ?>)">
                        <span>Ver sólo esta categoría</span> <i data-lucide="arrow-right" class="small"></i>
                    </button>
                </div>

                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-xl-4 g-4">
                    <?php foreach ($catGrupo['items'] as $prod): ?>
                        <?= renderizarTarjetaMinimalista($prod, $tasa, $simbolo, $iconosCats) ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php
        }
    }
}

$html = ob_get_clean();

echo json_encode([
    'html' => $html,
    'total' => count($productos),
    'categoria_activa' => $categoria_id ?? 'todas'
]);