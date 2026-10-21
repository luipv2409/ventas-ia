<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getConnection();
$editando = false;
$id = 0;

// Obtener tasa de cambio oficial
$stmtTasa = $pdo->query("SELECT valor FROM configuracion WHERE clave = 'tasa_cambio_usd'");
$tasaCambio = (float)($stmtTasa ? $stmtTasa->fetchColumn() : 9.96);
if ($tasaCambio <= 0) $tasaCambio = 9.96;

$producto = [
    'id' => '',
    'nombre' => '',
    'sku' => '',
    'descripcion' => '',
    'precio' => '',
    'stock' => 10,
    'categoria_id' => 1,
    'proveedor_id' => '',
    'imagen_url' => '',
    'especificaciones' => '',
    'activo' => 1
];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $prodDb = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$prodDb) {
        die('Producto no encontrado');
    }
    $producto = $prodDb;
    $editando = true;
}

$error = null;
$exito = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre        = trim($_POST['nombre'] ?? '');
    $sku           = trim($_POST['sku'] ?? '');
    $descripcion   = trim($_POST['descripcion'] ?? '');
    $precioUsd     = (float)($_POST['precio'] ?? 0);
    $stock         = (int)($_POST['stock'] ?? 0);
    $categoriaId   = (int)($_POST['categoria_id'] ?? 0);
    $proveedorId   = !empty($_POST['proveedor_id']) ? (int)$_POST['proveedor_id'] : null;
    $imagenUrl     = trim($_POST['imagen_url'] ?? '');
    $especificacionesRaw = trim($_POST['especificaciones'] ?? '');
    $activo        = isset($_POST['activo']) ? 1 : 0;

    // Validación de campos requeridos
    if ($nombre === '') {
        $error = 'El nombre del producto es obligatorio.';
    } elseif ($precioUsd <= 0) {
        $error = 'El precio debe ser un número positivo mayor a 0 (USD base).';
    } elseif ($categoriaId <= 0) {
        $error = 'Debes seleccionar una categoría válida.';
    } else {
        // Generar SKU automático si viene vacío
        if ($sku === '') {
            $catPrefixes = [
                1 => 'ELEC-CEL',
                2 => 'ELEC-TAB',
                3 => 'ELEC-AUD',
                4 => 'CALZ-ZAP',
                5 => 'ROPA-POL',
                6 => 'HOGA-ELEC',
                7 => 'HOGA-CAMA',
                8 => 'ALIM-CAF'
            ];
            $pref = $catPrefixes[$categoriaId] ?? 'PROD';
            $sku = $pref . '-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 4));
        }

        // Validar que especificaciones sea un JSON válido si se proporcionó
        $especificacionesJson = null;
        if ($especificacionesRaw !== '') {
            $decoded = json_decode($especificacionesRaw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $error = 'El formato de Especificaciones Técnicas debe ser un JSON válido. Error: ' . json_last_error_msg();
            } else {
                $especificacionesJson = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }
        }

        if (!$error) {
            try {
                if ($editando) {
                    $stmt = $pdo->prepare("
                        UPDATE productos 
                        SET categoria_id = :cid,
                            nombre = :nom,
                            sku = :sku,
                            descripcion = :des,
                            precio = :pre,
                            stock = :stk,
                            imagen_url = :img,
                            especificaciones = :spe,
                            proveedor_id = :prv,
                            activo = :act
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':cid' => $categoriaId,
                        ':nom' => $nombre,
                        ':sku' => $sku,
                        ':des' => $descripcion,
                        ':pre' => $precioUsd,
                        ':stk' => $stock,
                        ':img' => $imagenUrl ?: null,
                        ':spe' => $especificacionesJson,
                        ':prv' => $proveedorId,
                        ':act' => $activo,
                        ':id'  => $id
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO productos 
                        (categoria_id, nombre, sku, descripcion, precio, stock, imagen_url, especificaciones, proveedor_id, activo) 
                        VALUES (:cid, :nom, :sku, :des, :pre, :stk, :img, :spe, :prv, :act)
                    ");
                    $stmt->execute([
                        ':cid' => $categoriaId,
                        ':nom' => $nombre,
                        ':sku' => $sku,
                        ':des' => $descripcion,
                        ':pre' => $precioUsd,
                        ':stk' => $stock,
                        ':img' => $imagenUrl ?: null,
                        ':spe' => $especificacionesJson,
                        ':prv' => $proveedorId,
                        ':act' => $activo
                    ]);
                    $id = (int)$pdo->lastInsertId();
                }

                header('Location: productos.php?msg=' . urlencode('Producto guardado y sincronizado con éxito.') . '&tipo=success');
                exit;
            } catch (PDOException $e) {
                if (str_contains($e->getMessage(), 'Duplicate entry') && str_contains($e->getMessage(), 'sku')) {
                    $error = "El SKU '{$sku}' ya existe para otro producto. Por favor ingresa un SKU único.";
                } else {
                    $error = 'Error de base de datos: ' . $e->getMessage();
                }
            }
        }
    }
}

// Cargar categorías y proveedores
$categorias = $pdo->query("SELECT id, nombre, slug FROM categorias ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$proveedores = $pdo->query("SELECT id, nombre FROM proveedores ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);

// Formatear especificaciones para el textarea
$specsDisplay = '';
if (!empty($producto['especificaciones'])) {
    $decoded = json_decode($producto['especificaciones'], true);
    if ($decoded) {
        $specsDisplay = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        $specsDisplay = $producto['especificaciones'];
    }
}

// Precio en Bolivianos calculado
$precioUsdActual = (float)($producto['precio'] ?: 0);
$precioBsActual = round($precioUsdActual * $tasaCambio, 2);

// Enlazar estilos de la tienda pública para renderizar el prototipo con fidelidad 1:1
$extraHead = '<link href="../assets/css/estilos.css" rel="stylesheet">';

ob_start();
?>

<!-- Header y Acciones de Navegación -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary text-white rounded-pill px-3 py-1" style="font-size: 0.78rem;">
                <i class="fas <?= $editando ? 'fa-pen-to-square' : 'fa-sparkles' ?> me-1"></i>
                <?= $editando ? 'Modo Edición' : 'Nuevo Lanzamiento' ?>
            </span>
            <span class="text-muted small">ID: <?= $editando ? '#' . $producto['id'] : 'Autogenerado' ?></span>
        </div>
        <h2 class="fw-bold mb-1 brand-font">
            <?= $editando ? '✏️ Editar Producto: ' . htmlspecialchars((string)$producto['nombre']) : '➕ Crear Nuevo Producto en Catálogo' ?>
        </h2>
        <p class="text-muted small mb-0">
            Completa los datos técnicos del producto. Al lado derecho verás en tiempo real cómo se mostrará la tarjeta al cliente.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2 mt-3 mt-md-0">
        <span class="badge bg-light text-dark border px-3 py-2 rounded-pill shadow-sm">
            <i class="fas fa-coins text-warning me-1"></i> Tasa oficial: <strong>1 USD = <?= number_format($tasaCambio, 2) ?> Bs</strong>
        </span>
        <a href="productos.php" class="btn btn-outline-secondary rounded-pill px-3 py-2">
            <i class="fas fa-arrow-left me-1"></i> Volver al Catálogo
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center gap-3 mb-4">
        <i class="fas fa-circle-exclamation fa-2x"></i>
        <div>
            <strong>Error al procesar el producto:</strong>
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    </div>
<?php endif; ?>

<form method="POST" id="form-producto" class="needs-validation" novalidate>
    <div class="row g-4">
        <!-- ==========================================
             COLUMNA IZQUIERDA: Formulario de Datos
             ========================================== -->
        <div class="col-xl-7 col-lg-7">
            <!-- 1. Información General -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h5 class="fw-bold mb-3 brand-font d-flex align-items-center gap-2 text-primary">
                    <i class="fas fa-info-circle"></i> 1. Información Principal del Producto
                </h5>

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label fw-semibold small text-muted">Nombre Comercial <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="input-nombre" class="form-control rounded-3" value="<?= htmlspecialchars((string)$producto['nombre']) ?>" placeholder="Ej: iPhone 17 Pro Max 256GB" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">SKU (Código Único)</label>
                        <div class="input-group">
                            <input type="text" name="sku" id="input-sku" class="form-control rounded-start-3 font-monospace" value="<?= htmlspecialchars((string)($producto['sku'] ?? '')) ?>" placeholder="ELEC-CEL-001">
                            <button class="btn btn-outline-secondary" type="button" id="btn-generar-sku" title="Generar código automático">
                                <i class="fas fa-magic"></i>
                            </button>
                        </div>
                        <small class="text-muted" style="font-size: 0.72rem;">Código único en almacén.</small>
                    </div>

                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-semibold small text-muted mb-0">Categoría Segmentada <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none small text-primary fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNuevaCategoria" title="Crear nueva categoría sin salir de este formulario">
                                <i class="fas fa-plus-circle me-1"></i> + Nueva Categoría
                            </button>
                        </div>
                        <select name="categoria_id" id="select-categoria" class="form-select rounded-3" required>
                            <option value="">-- Seleccionar Categoría --</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= (int)$cat['id'] === (int)$producto['categoria_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <small class="text-muted" style="font-size: 0.72rem;">Define el segmento comparativo homogéneo.</small>
                            <a href="categorias.php" target="_blank" class="text-muted text-decoration-none" style="font-size: 0.72rem;">
                                <i class="fas fa-gear me-1"></i> Gestionar todas
                            </a>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold small text-muted">Proveedor Oficial</label>
                        <select name="proveedor_id" class="form-select rounded-3">
                            <option value="">-- Sin Proveedor Asociado --</option>
                            <?php foreach ($proveedores as $prv): ?>
                                <option value="<?= $prv['id'] ?>" <?= (int)$prv['id'] === (int)($producto['proveedor_id'] ?? 0) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($prv['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small text-muted">Descripción Comercial & Argumento de Venta</label>
                        <textarea name="descripcion" id="input-descripcion" class="form-control rounded-3" rows="3" placeholder="Descripción detallada que la IA Valeria y el catálogo mostrarán a los compradores..."><?= htmlspecialchars((string)($producto['descripcion'] ?? '')) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- 2. Precios Bimonetarios y Stock -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h5 class="fw-bold mb-3 brand-font d-flex align-items-center gap-2 text-primary">
                    <i class="fas fa-money-bill-wave"></i> 2. Precios Bimonetarios y Stock
                </h5>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">Precio Base en USD ($) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold">$</span>
                            <input type="number" step="0.01" min="0.01" name="precio" id="precio-usd" class="form-control" value="<?= $producto['precio'] ?>" placeholder="0.00" required>
                        </div>
                        <small class="text-muted" style="font-size: 0.72rem;">Guardado en base de datos.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">Precio en Bolivianos (Bs)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-primary text-white fw-bold">Bs</span>
                            <input type="number" step="0.01" min="0.01" id="precio-bs" class="form-control bg-light" value="<?= $precioBsActual > 0 ? $precioBsActual : '' ?>" placeholder="0.00">
                        </div>
                        <small class="text-muted" style="font-size: 0.72rem;">Tasa oficial: 1:<?= $tasaCambio ?>.</small>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold small text-muted">Stock Disponible <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-boxes-stacked"></i></span>
                            <input type="number" name="stock" id="input-stock" min="0" class="form-control" value="<?= $producto['stock'] ?>" required>
                        </div>
                        <small class="text-muted" style="font-size: 0.72rem;">Unidades en depósito.</small>
                    </div>
                </div>
            </div>

            <!-- 3. Ficha Técnica y Especificaciones JSON -->
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="fw-bold mb-0 brand-font text-primary d-flex align-items-center gap-2">
                            <i class="fas fa-microchip"></i> 3. Especificaciones Técnicas (Ficha Kimovil & BI)
                        </h5>
                        <p class="text-muted small mb-0">Estructura JSON técnica que alimenta el comparador Kimovil y la IA.</p>
                    </div>
                    <div id="json-status" class="badge bg-success-subtle text-success border px-3 py-2 rounded-pill">
                        <i class="fas fa-check-circle me-1"></i> JSON Válido
                    </div>
                </div>

                <!-- Botones de Plantillas por Categoría -->
                <div class="mb-3 p-3 bg-light rounded-4 border">
                    <label class="form-label fw-semibold small text-muted d-block mb-2">
                        <i class="fas fa-magic me-1"></i> Cargar Plantilla de Especificaciones con 1 Clic:
                    </label>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill btn-plantilla" data-tipo="celular">
                            📱 Smartphone
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info rounded-pill btn-plantilla" data-tipo="tablet">
                            💻 Tablet
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill btn-plantilla" data-tipo="audio">
                            🎧 Audio
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-dark rounded-pill btn-plantilla" data-tipo="calzado">
                            👟 Calzado
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success rounded-pill btn-plantilla" data-tipo="ropa">
                            👕 Ropa
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-warning rounded-pill btn-plantilla" data-tipo="electro">
                            🍳 Cocina
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill btn-plantilla" data-tipo="cama">
                            🛏️ Cama
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill btn-plantilla" data-tipo="cafe">
                            ☕ Café
                        </button>
                    </div>
                </div>

                <div class="mb-2">
                    <textarea name="especificaciones" id="textarea-especificaciones" class="form-control font-monospace rounded-3" rows="12" style="font-size: 0.88rem; background: #0f172a; color: #38bdf8; border-color: #334155;"><?= htmlspecialchars($specsDisplay) ?></textarea>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted" style="font-size: 0.75rem;">
                        Atributos reconocidos: <code>pantalla</code>, <code>procesador</code>, <code>kimovil_score</code>, <code>puntos_fuertes</code>, etc.
                    </small>
                    <button type="button" id="btn-formatear-json" class="btn btn-sm btn-link text-decoration-none">
                        <i class="fas fa-indent me-1"></i> Auto-Formatear JSON
                    </button>
                </div>
            </div>
        </div>

        <!-- ==========================================
             COLUMNA DERECHA: PROTOTIPO EN TIEMPO REAL
             ========================================== -->
        <div class="col-xl-5 col-lg-5">
            <div class="sticky-top" style="top: 20px; z-index: 10;">
                <!-- Card Encabezado del Prototipo -->
                <div class="card border-0 shadow-sm rounded-4 p-3 mb-3 bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-semibold">
                                <i class="fas fa-desktop me-1"></i> Prototipo en Vivo
                            </span>
                        </div>
                        <span class="badge bg-danger text-white rounded-pill px-2 py-1 shadow-sm d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;">
                            <span class="spinner-grow spinner-grow-sm" style="width: 8px; height: 8px;" role="status"></span>
                            <span>TIEMPO REAL</span>
                        </span>
                    </div>
                    <small class="text-muted mt-2 d-block" style="font-size: 0.75rem;">
                        Exactamente cómo verá este producto el cliente en el catálogo público de la tienda:
                    </small>
                </div>

                <!-- CAJA DE PRODUCTO DEL CLIENTE (100% ESTILO CATÁLOGO) -->
                <div class="position-relative mb-4">
                    <!-- Overlay de Oculto cuando activo = false -->
                    <div id="preview-overlay-oculto" class="position-absolute top-0 start-0 w-100 h-100 rounded-4 d-flex flex-column align-items-center justify-content-center" style="background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); z-index: 20; display: none !important;">
                        <i class="fas fa-eye-slash fa-3x text-warning mb-2"></i>
                        <h5 class="fw-bold text-white mb-1">Producto Oculto</h5>
                        <p class="text-white-50 small mb-0 px-3 text-center">No será visible en la tienda pública.</p>
                    </div>

                    <div class="product-card shadow-sm mx-auto" style="border-radius: 16px; overflow: hidden; background: #ffffff; border: 1px solid #e2e8f0;">
                        <!-- Imagen y Badges -->
                        <div class="product-img-wrapper" style="position: relative; height: 210px; background: #f1f5f9; overflow: hidden;">
                            <img id="preview-box-img" 
                                 src="<?= !empty($producto['imagen_url']) ? htmlspecialchars($producto['imagen_url']) : 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500&auto=format&fit=crop&q=60' ?>" 
                                 alt="Prototipo" 
                                 style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.4s ease;">
                            
                            <span class="cat-badge" id="preview-box-cat" style="position: absolute; top: 12px; left: 12px; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(8px); color: #ffffff; font-size: 0.72rem; font-weight: 600; padding: 4px 10px; border-radius: 9999px; border: 1px solid rgba(255, 255, 255, 0.15);">
                                <i class="fas fa-tag me-1"></i> <span id="preview-box-cat-text">Celulares & Smartphones</span>
                            </span>

                            <span class="kimovil-score-badge" id="preview-box-score" style="position: absolute; top: 12px; right: 12px; background: linear-gradient(135deg, #f59e0b, #d97706); color: #ffffff; font-size: 0.72rem; font-weight: 700; padding: 4px 10px; border-radius: 9999px; box-shadow: 0 4px 8px rgba(245, 158, 11, 0.3); display: none;">
                                <i class="fas fa-star"></i> <span id="preview-box-score-text">9.5 / 10</span>
                            </span>
                        </div>

                        <!-- Cuerpo de la Tarjeta -->
                        <div class="p-3 d-flex flex-column flex-grow-1">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="stock-pill" id="preview-box-stock" style="font-size: 0.78rem; font-weight: 600; color: #10b981; display: inline-flex; align-items: center; gap: 5px;">
                                    <span class="stock-dot" style="width: 6px; height: 6px; border-radius: 50%; background-color: currentColor; display: inline-block;"></span>
                                    <span id="preview-box-stock-num"><?= $producto['stock'] ?></span> disponibles
                                </span>
                                <span class="badge bg-light text-secondary border small text-truncate" id="preview-box-spec-badge" style="max-width: 140px; font-size: 0.7rem; display: none;">
                                    Hardware
                                </span>
                            </div>

                            <h5 class="fw-bold text-dark mb-1" id="preview-box-title" style="font-size: 1.05rem; min-height: 25px;">
                                <?= !empty($producto['nombre']) ? htmlspecialchars($producto['nombre']) : 'Nombre del Producto' ?>
                            </h5>
                            
                            <p class="text-muted small flex-grow-1 mb-3" id="preview-box-desc" style="line-height: 1.4; font-size: 0.83rem; min-height: 42px;">
                                <?= !empty($producto['descripcion']) ? htmlspecialchars(substr($producto['descripcion'], 0, 95)) . '...' : 'Descripción comercial del producto que cautiva al cliente...' ?>
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-baseline mb-3">
                                <div>
                                    <span class="fs-4 fw-bold text-primary brand-font" id="preview-box-price-bs">
                                        Bs <?= number_format($precioBsActual, 2) ?>
                                    </span>
                                    <small class="text-muted d-block" id="preview-box-price-usd" style="font-size: 0.75rem;">
                                        $<?= number_format($precioUsdActual, 2) ?> USD base
                                    </small>
                                </div>
                            </div>

                            <!-- Botones Simulados -->
                            <div class="d-flex gap-2 mt-auto">
                                <button type="button" class="btn btn-primary btn-sm rounded-pill flex-grow-1 fw-semibold py-2 d-flex align-items-center justify-content-center gap-1 shadow-sm" style="pointer-events: none;">
                                    <i class="fas fa-shopping-cart"></i> <span>Añadir</span>
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm rounded-pill px-3 py-2 fw-semibold d-flex align-items-center gap-1" style="pointer-events: none;">
                                    <i class="fas fa-bolt"></i> <span>Comparar</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ajustes de Imagen y Estado -->
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-3 bg-white">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted d-flex justify-content-between">
                            <span>URL de Imagen del Producto</span>
                            <a href="https://unsplash.com" target="_blank" class="text-decoration-none small" style="font-size: 0.75rem;">
                                <i class="fas fa-external-link-alt me-1"></i> Unsplash
                            </a>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fas fa-image"></i></span>
                            <input type="url" name="imagen_url" id="input-imagen-url" class="form-control" value="<?= htmlspecialchars((string)($producto['imagen_url'] ?? '')) ?>" placeholder="https://images.unsplash.com/...">
                        </div>
                    </div>

                    <div class="form-check form-switch fs-5 d-flex align-items-center justify-content-between p-0 mb-3">
                        <label class="form-check-label fs-6 fw-semibold text-dark" for="check-activo">
                            <i class="fas fa-toggle-on text-success me-1"></i> Visible en Tienda Pública
                        </label>
                        <input class="form-check-input ms-0" type="checkbox" role="switch" name="activo" id="check-activo" <?= !empty($producto['activo']) ? 'checked' : '' ?>>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-semibold py-2 d-flex align-items-center justify-content-center gap-2 shadow-sm">
                        <i class="fas fa-floppy-disk"></i>
                        <span><?= $editando ? 'Guardar Cambios' : 'Publicar Producto Ahora' ?></span>
                    </button>
                    
                    <a href="productos.php" class="btn btn-link text-muted w-100 text-decoration-none small mt-2">
                        Cancelar y volver a la lista
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const tasaCambio = <?= json_encode($tasaCambio) ?>;
    
    // Inputs del formulario
    const inputNombre = document.getElementById('input-nombre');
    const inputSku    = document.getElementById('input-sku');
    const selectCat   = document.getElementById('select-categoria');
    const inputDesc   = document.getElementById('input-descripcion');
    const inputUsd    = document.getElementById('precio-usd');
    const inputBs     = document.getElementById('precio-bs');
    const inputStock  = document.getElementById('input-stock');
    const inputImg    = document.getElementById('input-imagen-url');
    const checkActivo = document.getElementById('check-activo');
    const textareaSpecs = document.getElementById('textarea-especificaciones');
    const btnGenSku   = document.getElementById('btn-generar-sku');
    const jsonStatus  = document.getElementById('json-status');
    const btnFormatJson = document.getElementById('btn-formatear-json');

    // Elementos del Prototipo en Vivo
    const pBoxImg     = document.getElementById('preview-box-img');
    const pBoxCatText = document.getElementById('preview-box-cat-text');
    const pBoxScore   = document.getElementById('preview-box-score');
    const pBoxScoreText = document.getElementById('preview-box-score-text');
    const pBoxStockPill = document.getElementById('preview-box-stock');
    const pBoxStockNum  = document.getElementById('preview-box-stock-num');
    const pBoxSpecBadge = document.getElementById('preview-box-spec-badge');
    const pBoxTitle   = document.getElementById('preview-box-title');
    const pBoxDesc    = document.getElementById('preview-box-desc');
    const pBoxPriceBs = document.getElementById('preview-box-price-bs');
    const pBoxPriceUsd= document.getElementById('preview-box-price-usd');
    const pBoxOverlayOculto = document.getElementById('preview-overlay-oculto');

    // Actualizador en Tiempo Real del Prototipo
    function actualizarPrototipo() {
        // 1. Título
        const nom = inputNombre.value.trim();
        pBoxTitle.textContent = nom !== '' ? nom : 'Nombre del Producto';

        // 2. Categoría
        if (selectCat && selectCat.selectedIndex >= 0) {
            const catOption = selectCat.options[selectCat.selectedIndex];
            pBoxCatText.textContent = catOption && catOption.value ? catOption.text.trim() : 'Categoría';
        }

        // 3. Imagen
        const url = inputImg.value.trim();
        if (url) {
            pBoxImg.src = url;
        } else {
            pBoxImg.src = 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=500&auto=format&fit=crop&q=60';
        }

        // 4. Descripción
        const desc = inputDesc.value.trim();
        if (desc) {
            pBoxDesc.textContent = desc.length > 95 ? desc.substring(0, 95) + '...' : desc;
        } else {
            pBoxDesc.textContent = 'Descripción comercial del producto que cautiva al cliente...';
        }

        // 5. Precios
        const usd = parseFloat(inputUsd.value) || 0;
        const bs  = usd * tasaCambio;
        pBoxPriceBs.textContent = `Bs ${bs.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        pBoxPriceUsd.textContent = `$${usd.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} USD base`;

        // 6. Stock
        const st = parseInt(inputStock.value) || 0;
        pBoxStockNum.textContent = st;
        if (st <= 5) {
            pBoxStockPill.style.color = '#f59e0b';
        } else {
            pBoxStockPill.style.color = '#10b981';
        }

        // 7. Visibilidad
        if (pBoxOverlayOculto) {
            if (checkActivo.checked) {
                pBoxOverlayOculto.style.setProperty('display', 'none', 'important');
            } else {
                pBoxOverlayOculto.style.setProperty('display', 'flex', 'important');
            }
        }

        // 8. Especificaciones JSON (Kimovil Score & Spec Badge)
        const rawJson = textareaSpecs.value.trim();
        if (rawJson) {
            try {
                const parsed = JSON.parse(rawJson);
                // Score Kimovil
                const score = parsed.kimovil_score || parsed.score_categoria;
                if (score) {
                    pBoxScore.style.display = 'inline-block';
                    pBoxScoreText.textContent = score;
                } else {
                    pBoxScore.style.display = 'none';
                }

                // Hardware/Atributo Badge
                const attr = parsed.procesador || parsed.material || parsed.potencia || parsed.origen;
                if (attr) {
                    pBoxSpecBadge.style.display = 'inline-block';
                    pBoxSpecBadge.textContent = attr.toString().split(' ')[0] || 'Detalle';
                } else {
                    pBoxSpecBadge.style.display = 'none';
                }
            } catch (e) {
                pBoxScore.style.display = 'none';
                pBoxSpecBadge.style.display = 'none';
            }
        } else {
            pBoxScore.style.display = 'none';
            pBoxSpecBadge.style.display = 'none';
        }
    }

    // Escuchadores de eventos para sincronización en tiempo real
    if (inputNombre) inputNombre.addEventListener('input', actualizarPrototipo);
    if (selectCat)   selectCat.addEventListener('change', actualizarPrototipo);
    if (inputDesc)   inputDesc.addEventListener('input', actualizarPrototipo);
    if (inputStock)  inputStock.addEventListener('input', actualizarPrototipo);
    if (inputImg) {
        inputImg.addEventListener('input', actualizarPrototipo);
        pBoxImg.addEventListener('error', () => {
            pBoxImg.src = 'https://via.placeholder.com/400x300?text=Error+al+cargar+imagen';
        });
    }
    if (checkActivo) checkActivo.addEventListener('change', actualizarPrototipo);

    // Conversión de Precios interactiva
    if (inputUsd && inputBs) {
        inputUsd.addEventListener('input', () => {
            const val = parseFloat(inputUsd.value);
            if (!isNaN(val) && val >= 0) {
                inputBs.value = (val * tasaCambio).toFixed(2);
            } else {
                inputBs.value = '';
            }
            actualizarPrototipo();
        });

        inputBs.addEventListener('input', () => {
            const val = parseFloat(inputBs.value);
            if (!isNaN(val) && val >= 0) {
                inputUsd.value = (val / tasaCambio).toFixed(2);
            } else {
                inputUsd.value = '';
            }
            actualizarPrototipo();
        });
    }

    // Generador de SKU
    const prefijosCat = {
        '1': 'ELEC-CEL',
        '2': 'ELEC-TAB',
        '3': 'ELEC-AUD',
        '4': 'CALZ-ZAP',
        '5': 'ROPA-POL',
        '6': 'HOGA-ELEC',
        '7': 'HOGA-CAMA',
        '8': 'ALIM-CAF'
    };

    if (btnGenSku && inputSku) {
        btnGenSku.addEventListener('click', () => {
            const catId = selectCat ? selectCat.value : '1';
            const pref = prefijosCat[catId] || 'PROD';
            const rand = Math.floor(100 + Math.random() * 900);
            inputSku.value = `${pref}-${rand}`;
        });
    }

    // Validador de JSON
    function validarJson() {
        if (!textareaSpecs || !jsonStatus) return true;
        const texto = textareaSpecs.value.trim();
        if (texto === '') {
            jsonStatus.className = 'badge bg-secondary-subtle text-secondary border px-3 py-2 rounded-pill';
            jsonStatus.innerHTML = '<i class="fas fa-minus-circle me-1"></i> Ficha Opcional';
            return true;
        }
        try {
            JSON.parse(texto);
            jsonStatus.className = 'badge bg-success-subtle text-success border px-3 py-2 rounded-pill';
            jsonStatus.innerHTML = '<i class="fas fa-check-circle me-1"></i> JSON Válido';
            return true;
        } catch (e) {
            jsonStatus.className = 'badge bg-danger-subtle text-danger border px-3 py-2 rounded-pill';
            jsonStatus.innerHTML = '<i class="fas fa-times-circle me-1"></i> Error de Sintaxis';
            return false;
        }
    }

    if (textareaSpecs) {
        textareaSpecs.addEventListener('input', () => {
            validarJson();
            actualizarPrototipo();
        });
        validarJson();
    }

    if (btnFormatJson && textareaSpecs) {
        btnFormatJson.addEventListener('click', () => {
            try {
                const parsed = JSON.parse(textareaSpecs.value.trim());
                textareaSpecs.value = JSON.stringify(parsed, null, 4);
                validarJson();
                actualizarPrototipo();
            } catch (e) {
                alert('El JSON tiene errores de sintaxis y no puede auto-formatearse: ' + e.message);
            }
        });
    }

    // Plantillas por Categoría
    const plantillas = {
        celular: {
            tipo_dispositivo: "Smartphone",
            pantalla: 'AMOLED 6.7" 120Hz Full HD+ HDR10+',
            procesador: "Qualcomm Snapdragon 8 Gen 2 / Exynos 2400",
            ram: "12 GB LPDDR5X",
            almacenamiento: "256 GB UFS 4.0",
            camara_principal: "50 MP OIS + 12 MP Ultra Gran Angular + 10 MP Teleobjetivo",
            camara_frontal: "32 MP HDR",
            bateria: "5.000 mAh con Carga Rápida 45W",
            conectividad: "5G, Wi-Fi 6E, Bluetooth 5.3, NFC",
            resistencia: "IP68 resistente a inmersión",
            antutu_score: "1.250.000 puntos",
            kimovil_score: "9.4 / 10",
            puntos_fuertes: ["Pantalla ultra brillante", "Gran autonomía de batería", "Cámara versátil con OIS"],
            puntos_debiles: ["Cargador no incluido en empaque"],
            veredicto: "Excelente balance calidad-precio dentro de la gama alta."
        },
        tablet: {
            tipo_dispositivo: "Tablet",
            pantalla: 'Liquid Retina 11" 90Hz True Tone',
            procesador: "Octa-Core Alta Eficiencia",
            ram: "8 GB",
            almacenamiento: "128 GB expandible",
            camara_principal: "13 MP con autoenfoque",
            camara_frontal: "12 MP Gran Angular para videollamadas",
            bateria: "8.200 mAh (hasta 12 horas)",
            conectividad: "Wi-Fi 6, Bluetooth 5.3, USB-C",
            accesorios: "Compatible con lápiz óptico y teclado",
            kimovil_score: "9.2 / 10",
            puntos_fuertes: ["Gran pantalla para estudio y multimedia", "Batería de larga duración"],
            veredicto: "Ideal para productividad y entretenimiento."
        },
        audio: {
            tipo_dispositivo: "Auriculares Over-Ear / In-Ear TWS",
            procesador: "Chip de procesamiento de audio digital con ANC",
            bateria: "Hasta 30 horas con estuche de carga rápida",
            conectividad: "Bluetooth 5.3 multipunto con soporte Hi-Res",
            cancelacion_ruido: "Cancelación Activa de Ruido Adaptativa",
            resistencia: "IPX4 resistente a salpicaduras y sudor",
            peso: "250 gramos ergonómicos",
            kimovil_score: "9.3 / 10",
            puntos_fuertes: ["Aislamiento acústico excepcional", "Sonido envolvente espacial"],
            veredicto: "Inmersión total para viajes y trabajo concentrado."
        },
        calzado: {
            tipo_dispositivo: "Calzado Deportivo / Running",
            material: "Malla técnica transpirable de una sola capa",
            tallas: "38, 39, 40, 41, 42, 43, 44 unisex",
            suela: "Goma de alta resistencia con amortiguación React",
            drop: "10 mm con doble cámara de aire",
            peso: "280 gramos",
            temporada: "Todo el año",
            cuidado: "Limpiar en seco o con paño suave. No usar lavadora.",
            score_categoria: "9.4 / 10",
            puntos_fuertes: ["Excelente retorno de energía", "Gran tracción en asfalto"],
            veredicto: "Comodidad suprema para entrenamiento diario."
        },
        ropa: {
            tipo_dispositivo: "Polera Casual / Deportiva",
            material: "100% Algodón Peinado 200g / Dry-Fit",
            tallas: "S, M, L, XL, XXL",
            colores: "Negro, Blanco, Azul Marino",
            corte: "Regular Fit ergonómico",
            temporada: "Todo el año",
            cuidado: "Lavado en frío con colores similares",
            durabilidad: "Costuras reforzadas anti-deformación",
            score_categoria: "9.1 / 10",
            puntos_fuertes: ["Tacto extrasuave", "Transpirabilidad garantizada"],
            veredicto: "Básico imprescindible de alta durabilidad."
        },
        electro: {
            tipo_dispositivo: "Electrodoméstico de Cocina",
            potencia: "1.500 Watts de alto rendimiento",
            capacidad: "5.0 Litros",
            temperatura: "Rango de 80°C a 200°C digital",
            funciones: "8 programas preestablecidos con apagado automático",
            seguridad: "Protección térmica y canasta libre de BPA",
            score_categoria: "9.2 / 10",
            puntos_fuertes: ["Cocción rápida sin aceite", "Fácil limpieza"],
            veredicto: "Cocina saludable y eficiente en minutos."
        },
        cama: {
            tipo_dispositivo: "Blancos y Textiles para el Hogar",
            composicion: "Microfibra cepillada 100% hipoalergénica tacto pluma",
            medidas: "Queen (220 x 240 cm)",
            ajuste: "Acolchado térmico en cuadros",
            colores: "Gris / Blanco Reversible",
            cuidado: "Lavable a máquina en ciclo suave",
            score_categoria: "9.0 / 10",
            puntos_fuertes: ["Calidez sin peso excesivo", "No se apelmaza"],
            veredicto: "Descanso placentero de calidad hotelera."
        },
        cafe: {
            tipo_dispositivo: "Café de Especialidad Gourmet",
            origen: "Caranavi, Los Yungas de La Paz (1.600 msnm)",
            variedad: "100% Arábica de estricta altura",
            tueste: "Medio artesanal recién tostado",
            notas_cata: "Chocolate negro, miel silvestre y notas cítricas",
            presentacion: "Bolsa de 500g con válvula desgasificadora",
            score_categoria: "9.6 / 10",
            puntos_fuertes: ["Origen 100% boliviano premium", "Aroma y crema excepcionales"],
            veredicto: "Sabor supremo para amantes del buen café."
        }
    };

    document.querySelectorAll('.btn-plantilla').forEach(btn => {
        btn.addEventListener('click', () => {
            const tipo = btn.dataset.tipo;
            if (plantillas[tipo] && textareaSpecs) {
                if (textareaSpecs.value.trim() !== '' && !confirm('¿Sobrescribir las especificaciones actuales con esta plantilla?')) {
                    return;
                }
                textareaSpecs.value = JSON.stringify(plantillas[tipo], null, 4);
                validarJson();
                actualizarPrototipo();
            }
        });
    });

    // Validación al enviar formulario
    const form = document.getElementById('form-producto');
    if (form) {
        form.addEventListener('submit', (e) => {
            if (!validarJson()) {
                e.preventDefault();
                alert('Por favor corrige los errores en el campo Especificaciones Técnicas (debe ser un JSON válido o estar vacío) antes de guardar.');
            }
        });
    }

    // Modal: Creación Rápida de Categoría
    const formModalCat = document.getElementById('form-modal-categoria');
    const modalCatError = document.getElementById('modal-cat-error');
    const modalCatNombre = document.getElementById('modal-cat-nombre');
    const modalCatIcono = document.getElementById('modal-cat-icono');
    const modalCatDesc = document.getElementById('modal-cat-desc');
    const btnGuardarModalCat = document.getElementById('btn-guardar-modal-cat');

    document.querySelectorAll('.modal-ico-btn').forEach(b => {
        b.addEventListener('click', () => {
            if (modalCatIcono) modalCatIcono.value = b.dataset.ico;
        });
    });

    if (formModalCat) {
        formModalCat.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (modalCatError) modalCatError.classList.add('d-none');
            const nom = modalCatNombre.value.trim();
            if (!nom) return;

            btnGuardarModalCat.disabled = true;
            btnGuardarModalCat.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Creando...';

            try {
                const resp = await fetch('../api/guardar_categoria.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        nombre: nom,
                        icono: modalCatIcono.value.trim() || 'fas fa-tag',
                        descripcion: modalCatDesc.value.trim()
                    })
                });
                const data = await resp.json();
                if (data.success && data.categoria) {
                    // Agregar nueva opción al select
                    const opt = document.createElement('option');
                    opt.value = data.categoria.id;
                    opt.textContent = data.categoria.nombre;
                    opt.selected = true;
                    selectCat.appendChild(opt);

                    // Actualizar prototipo en vivo
                    actualizarPrototipo();

                    // Cerrar modal
                    const modalEl = document.getElementById('modalNuevaCategoria');
                    const modalInstance = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
                    modalInstance.hide();
                    formModalCat.reset();
                    if (modalCatIcono) modalCatIcono.value = 'fas fa-tag';

                    alert(`¡Categoría "${data.categoria.nombre}" creada y seleccionada con éxito!`);
                } else {
                    if (modalCatError) {
                        modalCatError.textContent = data.error || 'Error al crear la categoría';
                        modalCatError.classList.remove('d-none');
                    }
                }
            } catch (err) {
                if (modalCatError) {
                    modalCatError.textContent = 'Error de conexión con el servidor.';
                    modalCatError.classList.remove('d-none');
                }
            } finally {
                btnGuardarModalCat.disabled = false;
                btnGuardarModalCat.innerHTML = '<i class="fas fa-check me-1"></i> Crear y Seleccionar';
            }
        });
    }

    // Inicializar prototipo al cargar
    actualizarPrototipo();
});
</script>

<!-- Modal Rápido: Crear Nueva Categoría -->
<div class="modal fade" id="modalNuevaCategoria" tabindex="-1" aria-labelledby="modalNuevaCategoriaLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold brand-font text-primary d-flex align-items-center gap-2" id="modalNuevaCategoriaLabel">
                    <i class="fas fa-layer-group"></i> Crear Nueva Categoría
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="text-muted small mb-3">
                    Agrega un nuevo tipo o rubro de producto. Se seleccionará de inmediato sin perder los datos que ya escribiste.
                </p>
                <div id="modal-cat-error" class="alert alert-danger py-2 small d-none"></div>
                <form id="form-modal-categoria">
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Nombre de la Categoría <span class="text-danger">*</span></label>
                        <input type="text" id="modal-cat-nombre" class="form-control rounded-3" placeholder="Ej: Laptops, Joyería, Bicicletas, Perfumería..." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Icono Representativo (FontAwesome)</label>
                        <input type="text" id="modal-cat-icono" class="form-control rounded-3 font-monospace" value="fas fa-tag" placeholder="fas fa-laptop">
                        <div class="d-flex flex-wrap gap-1 mt-2">
                            <button type="button" class="btn btn-sm btn-light border py-0 px-2 small modal-ico-btn" data-ico="fas fa-laptop"><i class="fas fa-laptop"></i> Laptop</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-2 small modal-ico-btn" data-ico="fas fa-gem"><i class="fas fa-gem"></i> Joya</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-2 small modal-ico-btn" data-ico="fas fa-bicycle"><i class="fas fa-bicycle"></i> Bici</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-2 small modal-ico-btn" data-ico="fas fa-spray-can-sparkles"><i class="fas fa-spray-can-sparkles"></i> Perfume</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-2 small modal-ico-btn" data-ico="fas fa-guitar"><i class="fas fa-guitar"></i> Música</button>
                            <button type="button" class="btn btn-sm btn-light border py-0 px-2 small modal-ico-btn" data-ico="fas fa-gamepad"><i class="fas fa-gamepad"></i> Gaming</button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold small text-muted">Descripción Comercial (Opcional)</label>
                        <textarea id="modal-cat-desc" class="form-control rounded-3" rows="2" placeholder="Qué tipo de productos abarcará..."></textarea>
                    </div>
                    <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" id="btn-guardar-modal-cat" class="btn btn-primary rounded-pill px-4 fw-semibold d-inline-flex align-items-center gap-1 shadow-sm">
                            <i class="fas fa-check me-1"></i> Crear y Seleccionar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
$contenido = ob_get_clean();
$titulo = $editando ? 'Editar Producto' : 'Nuevo Producto';
require 'layout.php';