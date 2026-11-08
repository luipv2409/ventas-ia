<?php
// includes/navbar_store.php
// Componente de navegación global unificado y minimalista para toda la tienda
declare(strict_types=1);

if (!isset($nombreTienda)) {
    $nombreTienda = 'Tienda';
    if (file_exists(__DIR__ . '/../config/database.php')) {
        try {
            require_once __DIR__ . '/../config/database.php';
            $pdoNav = Database::getConnection();
            $stmtNav = $pdoNav->query("SELECT valor FROM configuracion WHERE clave = 'nombre_tienda' LIMIT 1");
            $nombreTienda = $stmtNav ? ($stmtNav->fetchColumn() ?: 'Tienda') : 'Tienda';
        } catch (\Throwable $e) {
            $nombreTienda = 'Tienda';
        }
    }
}

$paginaActiva = $paginaActiva ?? '';
?>
<!-- Navbar Sticky Tech Unificado -->
<nav class="navbar navbar-expand-lg navbar-dark navbar-tech sticky-top py-2">
    <div class="container">
        <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
            <span class="brand-icon d-inline-flex align-items-center justify-content-center">
                <i data-lucide="cpu" style="width:20px;height:20px;"></i>
            </span>
            <span class="brand-font fs-4"><?= htmlspecialchars($nombreTienda) ?></span>
        </a>
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Navegación">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link <?= $paginaActiva === 'catalogo' ? 'active fw-semibold' : '' ?> px-3 d-inline-flex align-items-center gap-1" href="index.php">
                        <i data-lucide="layout-grid"></i>
                        <span>Catálogo</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $paginaActiva === 'comparador' ? 'active fw-semibold text-warning' : 'text-warning' ?> px-3 d-inline-flex align-items-center gap-1" href="comparador.php">
                        <i data-lucide="git-compare"></i>
                        <span>Comparador</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="btn <?= $paginaActiva === 'carrito' ? 'btn-primary' : 'btn-outline-light' ?> rounded-pill px-3 py-1 position-relative d-inline-flex align-items-center gap-1" href="carrito.php">
                        <i data-lucide="shopping-bag"></i>
                        <span>Carrito</span>
                        <span id="carrito-contador" class="badge rounded-pill bg-danger ms-1" style="display:none;">0</span>
                    </a>
                </li>
                <?php include __DIR__ . '/navbar_user.php'; ?>
                <li class="nav-item ms-lg-2">
                    <a class="nav-link text-white-50 small p-2 d-inline-flex align-items-center justify-content-center" href="admin/dashboard.php" title="Panel Administrativo">
                        <i data-lucide="shield"></i>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
