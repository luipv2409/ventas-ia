<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getConnection();
$editando = false;
$id = 0;

$categoria = [
    'id' => '',
    'nombre' => '',
    'slug' => '',
    'descripcion' => '',
    'icono' => 'fas fa-tag',
    'activa' => 1
];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $catDb = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$catDb) {
        die('Categoría no encontrada');
    }
    $categoria = $catDb;
    $editando = true;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre      = trim($_POST['nombre'] ?? '');
    $slug        = trim($_POST['slug'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $icono       = trim($_POST['icono'] ?? 'fas fa-tag');
    $activa      = isset($_POST['activa']) ? 1 : 0;

    if ($nombre === '') {
        $error = 'El nombre de la categoría es obligatorio.';
    } else {
        if ($slug === '') {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nombre), '-'));
            if ($slug === '') {
                $slug = 'cat-' . substr(md5(uniqid()), 0, 6);
            }
        }

        try {
            if ($editando) {
                // Verificar que no choque con otra categoría
                $stmtCheck = $pdo->prepare("SELECT id FROM categorias WHERE (nombre = :nom OR slug = :slug) AND id != :id");
                $stmtCheck->execute([':nom' => $nombre, ':slug' => $slug, ':id' => $id]);
                if ($stmtCheck->fetch()) {
                    $error = "Ya existe otra categoría con el nombre '{$nombre}' o slug '{$slug}'.";
                } else {
                    $stmtUp = $pdo->prepare("
                        UPDATE categorias 
                        SET nombre = :nom, slug = :slug, descripcion = :des, icono = :ico, activa = :act 
                        WHERE id = :id
                    ");
                    $stmtUp->execute([
                        ':nom'  => $nombre,
                        ':slug' => $slug,
                        ':des'  => $descripcion ?: null,
                        ':ico'  => $icono ?: 'fas fa-tag',
                        ':act'  => $activa,
                        ':id'   => $id
                    ]);
                    header('Location: categorias.php?msg=' . urlencode("Categoría '{$nombre}' actualizada con éxito.") . '&tipo=success');
                    exit;
                }
            } else {
                $stmtCheck = $pdo->prepare("SELECT id FROM categorias WHERE nombre = :nom OR slug = :slug");
                $stmtCheck->execute([':nom' => $nombre, ':slug' => $slug]);
                if ($stmtCheck->fetch()) {
                    $error = "Ya existe una categoría con el nombre '{$nombre}' o slug '{$slug}'.";
                } else {
                    $stmtIns = $pdo->prepare("
                        INSERT INTO categorias (nombre, slug, descripcion, icono, activa) 
                        VALUES (:nom, :slug, :des, :ico, :act)
                    ");
                    $stmtIns->execute([
                        ':nom'  => $nombre,
                        ':slug' => $slug,
                        ':des'  => $descripcion ?: null,
                        ':ico'  => $icono ?: 'fas fa-tag',
                        ':act'  => $activa
                    ]);
                    header('Location: categorias.php?msg=' . urlencode("Categoría '{$nombre}' creada exitosamente.") . '&tipo=success');
                    exit;
                }
            }
        } catch (PDOException $e) {
            $error = 'Error de base de datos: ' . $e->getMessage();
        }
    }
}

// Catálogo de iconos comunes sugeridos
$iconosSugeridos = [
    'fas fa-mobile-screen-button' => 'Smartphone',
    'fas fa-laptop'               => 'Laptops',
    'fas fa-tablet-screen-button' => 'Tablets',
    'fas fa-headphones'           => 'Audio',
    'fas fa-tv'                   => 'Televisores',
    'fas fa-gamepad'              => 'Gaming',
    'fas fa-camera'               => 'Cámaras',
    'fas fa-shoe-prints'          => 'Calzado',
    'fas fa-shirt'                => 'Moda / Poleras',
    'fas fa-gem'                  => 'Joyería',
    'fas fa-glasses'              => 'Lentes',
    'fas fa-clock'                => 'Relojes',
    'fas fa-blender'              => 'Electro Cocina',
    'fas fa-bed'                  => 'Hogar / Cama',
    'fas fa-couch'                => 'Muebles',
    'fas fa-mug-hot'              => 'Café / Bebidas',
    'fas fa-spray-can-sparkles'   => 'Perfumería',
    'fas fa-bicycle'              => 'Bicicletas',
    'fas fa-dumbbell'             => 'Deportes / Fitness',
    'fas fa-guitar'               => 'Instrumentos',
    'fas fa-book'                 => 'Libros',
    'fas fa-paw'                  => 'Mascotas',
    'fas fa-toolbox'              => 'Herramientas',
    'fas fa-baby'                 => 'Bebés & Niños',
    'fas fa-car'                  => 'Autos / Repuestos'
];

$extraHead = '<link href="../assets/css/estilos.css" rel="stylesheet">';

ob_start();
?>

<!-- Header -->
<div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-primary text-white rounded-pill px-3 py-1" style="font-size: 0.78rem;">
                <i class="fas <?= $editando ? 'fa-pen-to-square' : 'fa-layer-group' ?> me-1"></i>
                <?= $editando ? 'Modo Edición' : 'Nueva Categoría' ?>
            </span>
            <span class="text-muted small">ID: <?= $editando ? '#' . $categoria['id'] : 'Autogenerado' ?></span>
        </div>
        <h2 class="fw-bold mb-1 brand-font">
            <?= $editando ? '✏️ Editar Categoría: ' . htmlspecialchars((string)$categoria['nombre']) : '➕ Crear Nueva Categoría Segmentada' ?>
        </h2>
        <p class="text-muted small mb-0">
            Define un nuevo segmento para tus productos. Esto creará automáticamente su espacio en el catálogo, filtros y comparador.
        </p>
    </div>
    <div class="d-flex align-items-center gap-2 mt-3 mt-md-0">
        <a href="categorias.php" class="btn btn-outline-secondary rounded-pill px-3 py-2">
            <i class="fas fa-arrow-left me-1"></i> Volver a Categorías
        </a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-4 d-flex align-items-center gap-3 mb-4">
        <i class="fas fa-circle-exclamation fa-2x"></i>
        <div>
            <strong>Error al guardar:</strong>
            <div><?= htmlspecialchars($error) ?></div>
        </div>
    </div>
<?php endif; ?>

<form method="POST" id="form-categoria">
    <div class="row g-4">
        <!-- Columna Izquierda: Campos -->
        <div class="col-xl-7 col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <h5 class="fw-bold mb-3 brand-font d-flex align-items-center gap-2 text-primary">
                    <i class="fas fa-sliders"></i> Datos de la Categoría
                </h5>

                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label fw-semibold small text-muted">Nombre de la Categoría <span class="text-danger">*</span></label>
                        <input type="text" name="nombre" id="input-cat-nombre" class="form-control rounded-3" value="<?= htmlspecialchars((string)$categoria['nombre']) ?>" placeholder="Ej: Laptops & Computadoras" required>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label fw-semibold small text-muted">Slug URL (Amigable)</label>
                        <input type="text" name="slug" id="input-cat-slug" class="form-control rounded-3 font-monospace" value="<?= htmlspecialchars((string)$categoria['slug']) ?>" placeholder="laptops-computadoras">
                        <small class="text-muted" style="font-size: 0.72rem;">Generado automáticamente si se deja vacío.</small>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small text-muted">Icono Representativo (FontAwesome)</label>
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-light" id="preview-addon-icono">
                                <i class="<?= htmlspecialchars((string)($categoria['icono'] ?: 'fas fa-tag')) ?>" id="preview-input-icono"></i>
                            </span>
                            <input type="text" name="icono" id="input-cat-icono" class="form-control font-monospace" value="<?= htmlspecialchars((string)($categoria['icono'] ?: 'fas fa-tag')) ?>" placeholder="fas fa-laptop">
                        </div>
                        
                        <!-- Paleta de Iconos Rápidos -->
                        <div class="p-3 bg-light rounded-4 border">
                            <small class="text-muted fw-semibold d-block mb-2">Selección Rápida de Iconos Frecuentes:</small>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($iconosSugeridos as $claseIco => $lblIco): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1 btn-select-icono d-inline-flex align-items-center gap-1" data-icono="<?= $claseIco ?>" style="font-size: 0.75rem;">
                                        <i class="<?= $claseIco ?>"></i> <span><?= $lblIco ?></span>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-semibold small text-muted">Descripción Comercial & Prompt RAG</label>
                        <textarea name="descripcion" id="input-cat-desc" class="form-control rounded-3" rows="4" placeholder="Describe qué tipo de productos abarcará esta categoría para orientar las respuestas del asistente Valeria y la navegación..."><?= htmlspecialchars((string)($categoria['descripcion'] ?? '')) ?></textarea>
                    </div>

                    <div class="col-12">
                        <div class="form-check form-switch fs-5 d-flex align-items-center justify-content-between p-0">
                            <label class="form-check-label fs-6 fw-semibold text-dark" for="check-cat-activa">
                                <i class="fas fa-toggle-on text-success me-1"></i> Categoría Activa en la Tienda
                            </label>
                            <input class="form-check-input ms-0" type="checkbox" role="switch" name="activa" id="check-cat-activa" <?= !empty($categoria['activa']) ? 'checked' : '' ?>>
                        </div>
                        <small class="text-muted d-block mt-1" style="font-size: 0.75rem;">
                            Al activarse, aparecerá inmediatamente en los filtros del catálogo principal y en el comparador.
                        </small>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold d-inline-flex align-items-center gap-2 shadow-sm">
                        <i class="fas fa-floppy-disk"></i>
                        <span><?= $editando ? 'Guardar Cambios' : 'Crear Categoría' ?></span>
                    </button>
                    <a href="categorias.php" class="btn btn-outline-secondary rounded-pill px-4 py-2">
                        Cancelar
                    </a>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Prototipo en Vivo de la Categoría -->
        <div class="col-xl-5 col-lg-5">
            <div class="sticky-top" style="top: 20px;">
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-semibold">
                            <i class="fas fa-eye me-1"></i> Vista del Cliente
                        </span>
                        <span class="badge bg-danger text-white rounded-pill px-2 py-1 shadow-sm d-inline-flex align-items-center gap-1" style="font-size: 0.72rem;">
                            <span class="spinner-grow spinner-grow-sm" style="width: 8px; height: 8px;" role="status"></span>
                            <span>EN VIVO</span>
                        </span>
                    </div>

                    <h6 class="fw-bold text-muted small text-uppercase mb-2">1. Badge sobre las Tarjetas de Producto:</h6>
                    <div class="p-3 bg-light rounded-4 border mb-4 text-center">
                        <div class="position-relative d-inline-block rounded-3 overflow-hidden shadow-sm" style="width: 240px; height: 130px; background: #0f172a;">
                            <img src="https://images.unsplash.com/photo-1526738549149-8e07eca6c147?w=400&auto=format&fit=crop&q=60" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.85;">
                            <span id="preview-badge-cat" class="cat-badge" style="position: absolute; top: 10px; left: 10px; background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px); color: #ffffff; font-size: 0.75rem; font-weight: 600; padding: 4px 12px; border-radius: 9999px; border: 1px solid rgba(255, 255, 255, 0.2);">
                                <i class="<?= htmlspecialchars((string)($categoria['icono'] ?: 'fas fa-tag')) ?>" id="preview-badge-icono"></i>
                                <span id="preview-badge-texto"><?= !empty($categoria['nombre']) ? htmlspecialchars($categoria['nombre']) : 'Nombre de Categoría' ?></span>
                            </span>
                        </div>
                    </div>

                    <h6 class="fw-bold text-muted small text-uppercase mb-2">2. Pestaña en el Comparador Kimovil:</h6>
                    <div class="p-3 rounded-4 mb-4" style="background: #0f172a; border: 1px solid rgba(255,255,255,0.1);">
                        <button type="button" class="btn btn-sm text-white rounded-pill px-3 py-2 d-inline-flex align-items-center gap-2" style="background: rgba(255,255,255,0.15); border: 1px solid rgba(255,255,255,0.25);">
                            <i class="<?= htmlspecialchars((string)($categoria['icono'] ?: 'fas fa-tag')) ?>" id="preview-tab-icono"></i>
                            <span class="fw-semibold" id="preview-tab-texto"><?= !empty($categoria['nombre']) ? htmlspecialchars($categoria['nombre']) : 'Nombre de Categoría' ?></span>
                            <span class="badge bg-secondary rounded-pill" style="font-size: 0.65rem;">2</span>
                        </button>
                    </div>

                    <h6 class="fw-bold text-muted small text-uppercase mb-2">3. En el Menú Desplegable de Filtros:</h6>
                    <div class="p-3 bg-light rounded-4 border">
                        <select class="form-select rounded-3" disabled>
                            <option selected id="preview-select-opt">
                                📁 <?= !empty($categoria['nombre']) ? htmlspecialchars($categoria['nombre']) : 'Nombre de Categoría' ?>
                            </option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const inputNom   = document.getElementById('input-cat-nombre');
    const inputSlug  = document.getElementById('input-cat-slug');
    const inputIco   = document.getElementById('input-cat-icono');
    const previewIco = document.getElementById('preview-input-icono');
    
    // Prototipo
    const pBadgeIco  = document.getElementById('preview-badge-icono');
    const pBadgeTxt  = document.getElementById('preview-badge-texto');
    const pTabIco    = document.getElementById('preview-tab-icono');
    const pTabTxt    = document.getElementById('preview-tab-texto');
    const pSelectOpt = document.getElementById('preview-select-opt');

    function slugify(text) {
        return text.toString().toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9 -]/g, '')
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }

    function actualizar() {
        const nom = inputNom.value.trim() || 'Nombre de Categoría';
        const ico = inputIco.value.trim() || 'fas fa-tag';

        // Textos
        pBadgeTxt.textContent = nom;
        pTabTxt.textContent = nom;
        pSelectOpt.textContent = '📁 ' + nom;

        // Iconos
        previewIco.className = ico;
        pBadgeIco.className = ico;
        pTabIco.className = ico;
    }

    if (inputNom) {
        inputNom.addEventListener('input', () => {
            if (!<?= json_encode($editando) ?>) {
                inputSlug.value = slugify(inputNom.value);
            }
            actualizar();
        });
    }

    if (inputIco) {
        inputIco.addEventListener('input', actualizar);
    }

    // Botones de iconos rápidos
    document.querySelectorAll('.btn-select-icono').forEach(btn => {
        btn.addEventListener('click', () => {
            const ico = btn.dataset.icono;
            inputIco.value = ico;
            actualizar();
        });
    });

    actualizar();
});
</script>

<?php
$contenido = ob_get_clean();
$titulo = $editando ? 'Editar Categoría' : 'Nueva Categoría';
require 'layout.php';
