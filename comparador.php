<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/icons.php';

$pdo = Database::getConnection();

// Configuración de moneda y tienda
$stmtConf = $pdo->query("SELECT clave, valor FROM configuracion WHERE clave IN ('moneda_simbolo', 'tasa_cambio_usd', 'nombre_tienda', 'contacto_telefono')");
$config = [];
if ($stmtConf) {
    while ($row = $stmtConf->fetch(PDO::FETCH_ASSOC)) {
        $config[$row['clave']] = $row['valor'];
    }
}
$simboloMoneda = $config['moneda_simbolo'] ?? 'Bs';
$tasaCambio    = (float)($config['tasa_cambio_usd'] ?? 1.0);
if ($tasaCambio <= 0) $tasaCambio = 1.0;
$nombreTienda  = $config['nombre_tienda'] ?? 'Nuestra Tienda';
$telefono      = $config['contacto_telefono'] ?? '';

// 1. Obtener todas las categorías con al menos 2 productos activos para permitir comparativas
$stmtCats = $pdo->query("
    SELECT c.id, c.nombre, c.icono, COUNT(p.id) as total_prods 
    FROM categorias c 
    JOIN productos p ON c.id = p.categoria_id 
    WHERE p.activo = 1 
    GROUP BY c.id 
    HAVING total_prods >= 2 
    ORDER BY (c.id = 1) DESC, c.nombre ASC
");
$categoriasDisponibles = $stmtCats->fetchAll(PDO::FETCH_ASSOC);
$categoriasIds = array_column($categoriasDisponibles, 'id');

// 2. Obtener todos los productos activos
$stmtAll = $pdo->query("
    SELECT p.id, p.categoria_id, p.nombre, p.descripcion, p.especificaciones, p.precio, p.stock, p.imagen_url, c.nombre as categoria_nombre
    FROM productos p
    LEFT JOIN categorias c ON p.categoria_id = c.id
    WHERE p.activo = 1
    ORDER BY p.nombre ASC
");
$todosProductos = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

// Mapeo general de productos por ID
$todosMap = [];
foreach ($todosProductos as $p) {
    $p['precio_bs'] = round(((float)$p['precio']) * $tasaCambio, 2);
    $p['specs'] = !empty($p['especificaciones']) ? json_decode($p['especificaciones'], true) : [];
    $todosMap[(int)$p['id']] = $p;
}

// 3. Determinar categoría activa y validar coherencia
$catIdReq = !empty($_GET['categoria']) ? (int)$_GET['categoria'] : 0;
$p1_req = !empty($_GET['p1']) ? (int)$_GET['p1'] : 0;
$p2_req = !empty($_GET['p2']) ? (int)$_GET['p2'] : 0;
$p3_req = !empty($_GET['p3']) ? (int)$_GET['p3'] : 0;

$categoriaIncompatibleDetectada = false;

// Si se pide p1, su categoría define el comparador
if ($p1_req && isset($todosMap[$p1_req])) {
    $catIdActiva = (int)$todosMap[$p1_req]['categoria_id'];
} elseif ($catIdReq && in_array($catIdReq, $categoriasIds)) {
    $catIdActiva = $catIdReq;
} else {
    $catIdActiva = !empty($categoriasIds[0]) ? (int)$categoriasIds[0] : 1;
}

// Nombre de la categoría activa
$catActivaInfo = array_values(array_filter($categoriasDisponibles, fn($c) => (int)$c['id'] === $catIdActiva))[0] ?? [
    'id' => 1,
    'nombre' => 'Electrónica',
    'total_prods' => 0
];
$nombreCategoriaActiva = $catActivaInfo['nombre'];

// 4. Filtrar productos ÚNICAMENTE de la categoría activa
$productosCategoria = array_filter($todosProductos, fn($p) => (int)$p['categoria_id'] === $catIdActiva);
$productosCatMap = [];
foreach ($productosCategoria as $p) {
    $p['precio_bs'] = round(((float)$p['precio']) * $tasaCambio, 2);
    $p['specs'] = !empty($p['especificaciones']) ? json_decode($p['especificaciones'], true) : [];
    $productosCatMap[(int)$p['id']] = $p;
}
$catProductIds = array_keys($productosCatMap);

// Validar p1
if ($p1_req && isset($productosCatMap[$p1_req])) {
    $p1_id = $p1_req;
} else {
    $p1_id = $catProductIds[0] ?? 0;
}

// Validar p2 (Debe ser de la misma categoría y diferente de p1)
if ($p2_req) {
    if (isset($productosCatMap[$p2_req]) && $p2_req !== $p1_id) {
        $p2_id = $p2_req;
    } else {
        $categoriaIncompatibleDetectada = true;
        // Buscar producto alternativo en la misma categoría
        $p2_id = null;
        foreach ($catProductIds as $id) {
            if ($id !== $p1_id) {
                $p2_id = $id;
                break;
            }
        }
        $p2_id = $p2_id ?? $p1_id;
    }
} else {
    // Asignar por defecto el segundo producto de la misma categoría
    $p2_id = null;
    foreach ($catProductIds as $id) {
        if ($id !== $p1_id) {
            $p2_id = $id;
            break;
        }
    }
    $p2_id = $p2_id ?? $p1_id;
}

// Validar p3 (Opcional, debe ser de la misma categoría y distinto a p1 y p2)
$p3_id = null;
if ($p3_req) {
    if (isset($productosCatMap[$p3_req]) && $p3_req !== $p1_id && $p3_req !== $p2_id) {
        $p3_id = $p3_req;
    } else {
        $categoriaIncompatibleDetectada = true;
    }
}

$prod1 = $productosCatMap[$p1_id] ?? null;
$prod2 = $productosCatMap[$p2_id] ?? null;
$prod3 = ($p3_id && isset($productosCatMap[$p3_id])) ? $productosCatMap[$p3_id] : null;

$comparados = array_filter([$prod1, $prod2, $prod3]);

// Iconos por categoría
$iconosCats = [
    1 => 'fas fa-mobile-screen-button', // Celulares & Smartphones
    2 => 'fas fa-tablet-screen-button', // Tablets
    3 => 'fas fa-headphones',           // Audio & Auriculares
    4 => 'fas fa-shoe-prints',          // Zapatillas & Calzado
    5 => 'fas fa-shirt',                // Poleras & Camisetas
    6 => 'fas fa-blender',              // Electrodomésticos de Cocina
    7 => 'fas fa-bed',                  // Ropa de Cama & Hogar
    8 => 'fas fa-mug-hot'               // Café & Alimentos Gourmet
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comparador de <?= htmlspecialchars($nombreCategoriaActiva) ?> — <?= htmlspecialchars($nombreTienda) ?></title>
    <!-- Google Fonts: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/estilos.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background: #f8fafc;
            color: #1e293b;
        }
        h1, h2, h3, h4, .brand-font {
            font-family: 'Outfit', sans-serif;
        }
        .kimovil-hero {
            background: radial-gradient(circle at top right, rgba(99, 102, 241, 0.15), transparent 40%),
                        radial-gradient(circle at top left, rgba(6, 182, 212, 0.15), transparent 40%),
                        linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
            color: white;
            padding: 3.5rem 0 2.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .category-tab-btn {
            background: rgba(255, 255, 255, 0.08);
            border: 1.5px solid rgba(255, 255, 255, 0.15);
            color: #e2e8f0;
            padding: 8px 18px;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
            text-decoration: none;
        }
        .category-tab-btn:hover {
            background: rgba(255, 255, 255, 0.18);
            color: #ffffff;
            transform: translateY(-2px);
        }
        .category-tab-btn.active {
            background: linear-gradient(135deg, #4f46e5, #06b6d4);
            border-color: #818cf8;
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
        }
        .kimovil-badge {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            box-shadow: 0 2px 6px rgba(245, 158, 11, 0.3);
        }
        .score-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 6px;
        }
        .score-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #06b6d4);
            border-radius: 4px;
        }
        .compare-table {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid #cbd5e1;
        }
        .compare-table th {
            background: #f1f5f9;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #334155;
            padding: 14px 18px;
            border-bottom: 2px solid #cbd5e1;
        }
        .compare-table td {
            padding: 14px 18px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.92rem;
            color: #1e293b;
        }
        .feature-label {
            width: 230px;
            background: #f8fafc;
            font-weight: 700;
            color: #0f172a;
            border-right: 1.5px solid #e2e8f0;
        }
        .device-card-header {
            background: #ffffff;
            padding: 20px;
            text-align: center;
            border-bottom: 2px solid #e2e8f0;
        }
        .device-img {
            height: 180px;
            object-fit: contain;
            transition: transform 0.3s ease;
        }
        .device-img:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body>

    <!-- Navbar Sticky Tech Unificado -->
    <?php 
    $paginaActiva = 'comparador';
    include __DIR__ . '/includes/navbar_store.php'; 
    ?>

    <!-- Header Hero Comparador -->
    <div class="kimovil-hero text-center">
        <div class="container">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill bg-white bg-opacity-10 mb-3 border border-white border-opacity-10">
                <i data-lucide="<?= resolverIconoLucide($catActivaInfo['icono'] ?? '', (int)$catIdActiva) ?>" class="text-info"></i>
                <span class="small fw-semibold text-light"><?= htmlspecialchars($nombreCategoriaActiva) ?></span>
            </div>
            <h1 class="display-5 fw-bold mb-2">Comparador de Productos</h1>
            <p class="lead text-light text-opacity-75 max-w-700 mx-auto mb-4" style="font-size: 1.05rem;">
                Compara especificaciones técnicas, características y precios oficiales lado a lado.
            </p>

            <!-- Pestañas de Selección de Categoría (Filtro Estricto de Productos Similares) -->
            <div class="d-flex justify-content-center gap-2 flex-wrap mb-4">
                <?php foreach ($categoriasDisponibles as $cat): ?>
                    <a href="comparador.php?categoria=<?= $cat['id'] ?>" 
                       class="category-tab-btn <?= $cat['id'] == $catIdActiva ? 'active' : '' ?>">
                        <i data-lucide="<?= resolverIconoLucide($cat['icono'] ?? '', (int)$cat['id']) ?>"></i>
                        <span><?= htmlspecialchars($cat['nombre']) ?></span>
                        <span class="badge rounded-pill bg-white bg-opacity-25 ms-1"><?= $cat['total_prods'] ?></span>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Aviso si se intentó mezclar productos no comparables -->
            <?php if ($categoriaIncompatibleDetectada): ?>
                <div class="alert alert-warning border-0 shadow d-flex align-items-center justify-content-center gap-2 max-w-700 mx-auto mb-3 text-start" style="border-radius: 14px; background: #fffbeb; color: #b45309; max-width: 780px;">
                    <i data-lucide="shield-check" class="fs-4 text-warning"></i>
                    <div>
                        <strong>Comparación por categoría:</strong> Solo se comparan productos de un mismo departamento. Se ajustó la selección a <strong><?= htmlspecialchars($nombreCategoriaActiva) ?></strong>.
                    </div>
                </div>
            <?php endif; ?>

            <!-- Selectores dinámicos exclusivos de la categoría activa -->
            <form method="GET" class="row g-2 justify-content-center bg-white bg-opacity-10 p-3 rounded-4 border border-white border-opacity-10 shadow-lg" style="backdrop-filter: blur(10px); max-width: 950px; margin: 0 auto;">
                <input type="hidden" name="categoria" value="<?= $catIdActiva ?>">
                
                <div class="col-md-4">
                    <label class="small text-light text-opacity-75 fw-semibold d-block text-start mb-1">
                        <span class="badge bg-secondary-subtle text-primary border rounded-circle px-2 py-1 me-1 small">1</span> Artículo 1 (<?= htmlspecialchars($nombreCategoriaActiva) ?>):
                    </label>
                    <select name="p1" class="form-select rounded-3 fw-medium" onchange="this.form.submit()">
                        <?php foreach ($productosCatMap as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $p['id'] == $p1_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nombre']) ?> (<?= $simboloMoneda ?> <?= number_format($p['precio_bs'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="small text-light text-opacity-75 fw-semibold d-block text-start mb-1">
                        <span class="badge bg-secondary-subtle text-primary border rounded-circle px-2 py-1 me-1 small">2</span> Artículo 2 (<?= htmlspecialchars($nombreCategoriaActiva) ?>):
                    </label>
                    <select name="p2" class="form-select rounded-3 fw-medium" onchange="this.form.submit()">
                        <?php foreach ($productosCatMap as $p): ?>
                            <option value="<?= $p['id'] ?>" <?= $p['id'] == $p2_id ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['nombre']) ?> (<?= $simboloMoneda ?> <?= number_format($p['precio_bs'], 2) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="small text-light text-opacity-75 fw-semibold d-block text-start mb-1">
                        <span class="badge bg-secondary-subtle text-primary border rounded-circle px-2 py-1 me-1 small">3</span> Artículo 3 (Opcional):
                    </label>
                    <select name="p3" class="form-select rounded-3 fw-medium" onchange="this.form.submit()">
                        <option value="">-- Ninguno (Comparar 2) --</option>
                        <?php foreach ($productosCatMap as $p): ?>
                            <?php if ($p['id'] !== $p1_id && $p['id'] !== $p2_id): ?>
                                <option value="<?= $p['id'] ?>" <?= $p['id'] == $p3_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['nombre']) ?> (<?= $simboloMoneda ?> <?= number_format($p['precio_bs'], 2) ?>)
                                </option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla Comparativa Adaptativa -->
    <div class="container my-5">
        <div class="table-responsive compare-table">
            <table class="table table-bordered mb-0">
                <thead>
                    <tr>
                        <th class="feature-label">
                            <span class="d-block text-muted small fw-normal">Categoría:</span>
                            <?= htmlspecialchars($nombreCategoriaActiva) ?>
                        </th>
                        <?php foreach ($comparados as $p): ?>
                            <th class="text-center" style="width: <?= 100 / (count($comparados) + 1) ?>%;">
                                <div class="device-card-header p-0">
                                    <img src="<?= htmlspecialchars($p['imagen_url'] ?: 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=300') ?>" alt="<?= htmlspecialchars($p['nombre']) ?>" class="device-img mb-2">
                                    <h5 class="fw-bold mb-1 text-dark"><?= htmlspecialchars($p['nombre']) ?></h5>
                                    <div class="fs-4 fw-bold text-primary mb-2">
                                        <?= $simboloMoneda ?> <?= number_format($p['precio_bs'], 2) ?>
                                        <small class="text-muted fs-6 fw-normal">($<?= number_format((float)$p['precio'], 2) ?> USD)</small>
                                    </div>
                                    <button class="btn btn-primary btn-sm rounded-pill px-4 fw-bold add-to-cart-comp shadow-sm d-inline-flex align-items-center justify-content-center gap-1" 
                                            data-id="<?= $p['id'] ?>" 
                                            data-nombre="<?= htmlspecialchars($p['nombre']) ?>" 
                                            data-precio="<?= $p['precio_bs'] ?>" 
                                            data-stock="<?= $p['stock'] ?>">
                                        <i data-lucide="shopping-bag" style="width:15px;height:15px;"></i> <span>Añadir a la Bolsa</span>
                                    </button>
                                </div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>

                <?php if ($catIdActiva == 1): ?>
                    <!-- ========================================== -->
                    <!-- MATRIZ TÉCNICA: ELECTRÓNICA & SMARTPHONES  -->
                    <!-- ========================================== -->
                    <tr>
                        <td class="feature-label"><i data-lucide="award" style="width:16px;height:16px;" class="text-warning me-2"></i> Puntuación Kimovil</td>
                        <?php foreach ($comparados as $p): 
                            $score = $p['specs']['kimovil_score'] ?? '8.5 / 10';
                            $num = (float)str_replace('/ 10', '', $score) * 10;
                        ?>
                            <td class="text-center">
                                <span class="kimovil-badge d-inline-flex align-items-center gap-1"><i data-lucide="star" style="width:13px;height:13px;"></i> <?= htmlspecialchars($score) ?></span>
                                <div class="score-bar mx-auto" style="max-width: 160px;">
                                    <div class="score-bar-fill" style="width: <?= min(100, $num) ?>%;"></div>
                                </div>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <tr>
                        <td class="feature-label"><i data-lucide="gauge" style="width:16px;height:16px;" class="text-danger me-2"></i> Benchmark AnTuTu</td>
                        <?php foreach ($comparados as $p): ?>
                            <td class="text-center fw-bold text-dark fs-6">
                                <?= htmlspecialchars($p['specs']['antutu_score'] ?? 'N/A') ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <tr>
                        <td class="feature-label"><i data-lucide="smartphone" style="width:16px;height:16px;" class="text-primary me-2"></i> Pantalla & Panel</td>
                        <?php foreach ($comparados as $p): ?>
                            <td><?= htmlspecialchars($p['specs']['pantalla'] ?? 'N/A') ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <tr>
                        <td class="feature-label"><i data-lucide="cpu" style="width:16px;height:16px;" class="text-info me-2"></i> Procesador & Chipset</td>
                        <?php foreach ($comparados as $p): ?>
                            <td><?= htmlspecialchars($p['specs']['procesador'] ?? 'N/A') ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <tr>
                        <td class="feature-label"><i data-lucide="hard-drive" style="width:16px;height:16px;" class="text-secondary me-2"></i> RAM & Almacenamiento</td>
                        <?php foreach ($comparados as $p): ?>
                            <td>
                                <div><strong>RAM:</strong> <?= htmlspecialchars($p['specs']['ram'] ?? 'N/A') ?></div>
                                <div><strong>Almacenamiento:</strong> <?= htmlspecialchars($p['specs']['almacenamiento'] ?? 'N/A') ?></div>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <tr>
                        <td class="feature-label"><i data-lucide="camera" style="width:16px;height:16px;" class="text-success me-2"></i> Cámaras & Video</td>
                        <?php foreach ($comparados as $p): ?>
                            <td>
                                <div><strong>Principal:</strong> <?= htmlspecialchars($p['specs']['camara_principal'] ?? 'N/A') ?></div>
                                <div><strong>Selfie:</strong> <?= htmlspecialchars($p['specs']['camara_frontal'] ?? 'N/A') ?></div>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <tr>
                        <td class="feature-label"><i data-lucide="battery" style="width:16px;height:16px;" class="text-warning me-2"></i> Batería & Carga</td>
                        <?php foreach ($comparados as $p): ?>
                            <td><?= htmlspecialchars($p['specs']['bateria'] ?? 'Batería de larga duración') ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <tr>
                        <td class="feature-label"><i data-lucide="shield-check" style="width:16px;height:16px;" class="text-dark me-2"></i> Conectividad & Resistencia</td>
                        <?php foreach ($comparados as $p): ?>
                            <td>
                                <div><strong>Red:</strong> <?= htmlspecialchars($p['specs']['conectividad'] ?? 'N/A') ?></div>
                                <div><strong>Protección:</strong> <?= htmlspecialchars($p['specs']['resistencia'] ?? 'N/A') ?></div>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                <?php elseif ($catIdActiva == 2): ?>
                    <!-- ========================================== -->
                    <!-- MATRIZ TÉCNICA: ROPA & CALZADO             -->
                    <!-- ========================================== -->
                    <tr>
                        <td class="feature-label"><i data-lucide="star" style="width:16px;height:16px;" class="text-warning me-2"></i> Rating de Confort</td>
                        <?php foreach ($comparados as $p): 
                            $score = $p['specs']['score_categoria'] ?? '9.0 / 10';
                            $num = (float)str_replace('/ 10', '', $score) * 10;
                        ?>
                            <td class="text-center">
                                <span class="kimovil-badge bg-primary text-white d-inline-flex align-items-center gap-1"><i data-lucide="heart" style="width:13px;height:13px;"></i> <?= htmlspecialchars($score) ?></span>
                                <div class="score-bar mx-auto" style="max-width: 160px;">
                                    <div class="score-bar-fill" style="width: <?= min(100, $num) ?>%;"></div>
                                </div>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <tr>
                        <td class="feature-label"><i data-lucide="shirt" style="width:16px;height:16px;" class="text-info me-2"></i> Tipo de Artículo</td>
                        <?php foreach ($comparados as $p): ?>
                            <td class="fw-bold text-dark"><?= htmlspecialchars($p['specs']['tipo_dispositivo'] ?? 'Prenda de vestir') ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <tr>
                        <td class="feature-label"><i data-lucide="leaf" style="width:16px;height:16px;" class="text-success me-2"></i> Composición & Material</td>
                        <?php foreach ($comparados as $p): ?>
                            <td><?= htmlspecialchars($p['specs']['material'] ?? 'Tejido de alta calidad') ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <tr>
                        <td class="feature-label"><i data-lucide="ruler" style="width:16px;height:16px;" class="text-primary me-2"></i> Tallas Disponibles</td>
                        <?php foreach ($comparados as $p): ?>
                            <td><span class="badge bg-light text-dark border px-2 py-1"><?= htmlspecialchars($p['specs']['tallas'] ?? 'Consultar') ?></span></td>
                        <?php endforeach; ?>
                    </tr>

                    <tr>
                        <td class="feature-label"><i data-lucide="footprints" style="width:16px;height:16px;" class="text-secondary me-2"></i> Suela / Corte / Ajuste</td>
                        <?php foreach ($comparados as $p): ?>
                            <td><?= htmlspecialchars($p['specs']['suela'] ?? ($p['specs']['corte'] ?? 'Corte estándar ergonómico')) ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <tr>
                        <td class="feature-label"><i data-lucide="calendar" style="width:16px;height:16px;" class="text-warning me-2"></i> Uso & Temporada</td>
                        <?php foreach ($comparados as $p): ?>
                            <td><?= htmlspecialchars($p['specs']['temporada'] ?? 'Todo el año') ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <tr>
                        <td class="feature-label"><i data-lucide="droplet" style="width:16px;height:16px;" class="text-primary me-2"></i> Cuidado & Lavado</td>
                        <?php foreach ($comparados as $p): ?>
                            <td class="small text-muted"><?= htmlspecialchars($p['specs']['cuidado'] ?? 'Lavado regular') ?></td>
                        <?php endforeach; ?>
                    </tr>

                <?php elseif ($catIdActiva == 3): ?>
                    <!-- ========================================== -->
                    <!-- MATRIZ TÉCNICA: HOGAR & DECORACIÓN         -->
                    <!-- ========================================== -->
                    <tr>
                        <td class="feature-label"><i data-lucide="award" style="width:16px;height:16px;" class="text-warning me-2"></i> Calidad Hogar</td>
                        <?php foreach ($comparados as $p): 
                            $score = $p['specs']['score_categoria'] ?? '8.8 / 10';
                            $num = (float)str_replace('/ 10', '', $score) * 10;
                        ?>
                            <td class="text-center">
                                <span class="kimovil-badge d-inline-flex align-items-center gap-1"><i data-lucide="star" style="width:13px;height:13px;"></i> <?= htmlspecialchars($score) ?></span>
                                <div class="score-bar mx-auto" style="max-width: 160px;">
                                    <div class="score-bar-fill" style="width: <?= min(100, $num) ?>%;"></div>
                                </div>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <tr>
                        <td class="feature-label"><i data-lucide="zap" style="width:16px;height:16px;" class="text-danger me-2"></i> Potencia / Capacidad</td>
                        <?php foreach ($comparados as $p): ?>
                            <td><?= htmlspecialchars($p['specs']['potencia'] ?? ($p['specs']['capacidad'] ?? ($p['specs']['medidas'] ?? 'N/A'))) ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <tr>
                        <td class="feature-label"><i data-lucide="box" style="width:16px;height:16px;" class="text-info me-2"></i> Material / Vaso / Tejido</td>
                        <?php foreach ($comparados as $p): ?>
                            <td><?= htmlspecialchars($p['specs']['capacidad'] ?? ($p['specs']['composicion'] ?? 'Material de alta durabilidad')) ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <tr>
                        <td class="feature-label"><i data-lucide="sliders" style="width:16px;height:16px;" class="text-secondary me-2"></i> Funciones / Velocidades</td>
                        <?php foreach ($comparados as $p): ?>
                            <td><?= htmlspecialchars($p['specs']['velocidades'] ?? ($p['specs']['ajuste'] ?? 'Diseñado para uso doméstico diario')) ?></td>
                        <?php endforeach; ?>
                    </tr>

                <?php else: ?>
                    <!-- ========================================== -->
                    <!-- MATRIZ GENERAL / ALIMENTOS                 -->
                    <!-- ========================================== -->
                    <tr>
                        <td class="feature-label"><i data-lucide="info" style="width:16px;height:16px;" class="text-primary me-2"></i> Descripción General</td>
                        <?php foreach ($comparados as $p): ?>
                            <td><?= htmlspecialchars($p['descripcion']) ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endif; ?>

                    <!-- Puntos Fuertes Comunes -->
                    <tr>
                        <td class="feature-label"><i data-lucide="thumbs-up" style="width:16px;height:16px;" class="text-success me-2"></i> Puntos Fuertes</td>
                        <?php foreach ($comparados as $p): ?>
                            <td>
                                <?php if (!empty($p['specs']['puntos_fuertes'])): ?>
                                    <ul class="list-unstyled mb-0 text-success small">
                                        <?php foreach ($p['specs']['puntos_fuertes'] as $pf): ?>
                                            <li class="mb-1 d-flex align-items-center gap-1"><i data-lucide="check" style="width:14px;height:14px;" class="text-success"></i> <?= htmlspecialchars($pf) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span class="text-muted small">Excelente relación calidad-precio en su categoría.</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Puntos Débiles Comunes -->
                    <tr>
                        <td class="feature-label"><i data-lucide="alert-circle" style="width:16px;height:16px;" class="text-secondary me-2"></i> A Considerar</td>
                        <?php foreach ($comparados as $p): ?>
                            <td>
                                <?php if (!empty($p['specs']['puntos_debiles'])): ?>
                                    <ul class="list-unstyled mb-0 text-secondary small">
                                        <?php foreach ($p['specs']['puntos_debiles'] as $pd): ?>
                                            <li class="mb-1 d-flex align-items-center gap-1"><i data-lucide="minus-circle" style="width:14px;height:14px;" class="text-muted"></i> <?= htmlspecialchars($pd) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span class="text-muted small">Sin desventajas notables reportadas.</span>
                                <?php endif; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>

                    <!-- Veredicto de Compra -->
                    <tr class="table-light">
                        <td class="feature-label"><i data-lucide="lightbulb" style="width:16px;height:16px;" class="text-warning me-2"></i> Veredicto de Compra</td>
                        <?php foreach ($comparados as $p): ?>
                            <td class="small text-secondary fst-italic">
                                "<?= htmlspecialchars($p['specs']['veredicto'] ?? 'Gran opción disponible en nuestro catálogo oficial.') ?>"
                            </td>
                        <?php endforeach; ?>
                    </tr>

                </tbody>
            </table>
        </div>

        <!-- Banner de Asistencia -->
        <div class="card mt-4 border-0 shadow-sm p-4 text-center" style="border-radius: 16px; background: linear-gradient(135deg, #1e1b4b, #312e81); color: white;">
            <h4 class="fw-bold mb-2 d-inline-flex align-items-center justify-content-center gap-2"><i data-lucide="message-square" style="width:22px;height:22px;" class="text-warning"></i> ¿Tienes dudas sobre cuál elegir?</h4>
            <p class="text-light text-opacity-75 mb-3">Consulta con nuestro soporte por chat para resolver dudas técnicas sobre compatibilidad o rendimiento.</p>
            <div>
                <button type="button" class="btn btn-warning rounded-pill px-4 fw-bold d-inline-flex align-items-center justify-content-center gap-2" onclick="document.getElementById('chat-toggle')?.click()">
                    <i data-lucide="message-square" style="width:16px;height:16px;"></i> <span>Abrir Chat de Consulta</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Script del carrito para añadir desde la comparativa -->
    <script src="assets/js/carrito.js"></script>
    <script>
        document.querySelectorAll('.add-to-cart-comp').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const nombre = btn.dataset.nombre;
                const precio = parseFloat(btn.dataset.precio);
                const stock = parseInt(btn.dataset.stock) || 0;
                if (typeof carrito !== 'undefined') {
                    carrito.agregar({ id, nombre, precio, stock });
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-dark');
                    btn.innerHTML = '<i data-lucide="check" style="width:15px;height:15px;"></i> <span>¡Añadido!</span>';
                    if (window.lucide) lucide.createIcons();
                    setTimeout(() => {
                        btn.classList.remove('btn-dark');
                        btn.classList.add('btn-primary');
                        btn.innerHTML = '<i data-lucide="shopping-bag" style="width:15px;height:15px;"></i> <span>Añadir a la Bolsa</span>';
                        if (window.lucide) lucide.createIcons();
                    }, 2000);
                }
            });
        });
    </script>
    <?php include __DIR__ . '/includes/chat_widget.php'; ?>
    <script src="assets/js/lucide.min.js"></script>
    <script src="assets/js/chat.js"></script>
    <script>
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            lucide.createIcons();
        }
    </script>
</body>
</html>
