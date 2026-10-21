<?php
// admin/nav.php
$paginaActual = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar-header d-flex align-items-center gap-2">
    <span class="sidebar-brand-icon"><i data-lucide="zap"></i></span>
    <div>
        <div class="fw-bold text-white brand-font" style="font-size: 1.15rem; line-height: 1;">Admin Hub</div>
        <small class="text-white-50" style="font-size: 0.72rem;">Panel de Control</small>
    </div>
</div>

<div class="sidebar-section-title">Operaciones</div>
<ul class="nav flex-column mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $paginaActual == 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
            <i data-lucide="layout-dashboard"></i> <span>Dashboard</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= in_array($paginaActual, ['productos.php']) ? 'active' : '' ?>" href="productos.php">
            <i data-lucide="boxes"></i> <span>Catálogo de Productos</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= ($paginaActual == 'producto_form.php' && empty($_GET['id'])) ? 'active' : '' ?>" href="producto_form.php">
            <i data-lucide="plus-circle" class="text-info"></i> <span>Nuevo Producto</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= in_array($paginaActual, ['categorias.php', 'categoria_form.php']) ? 'active' : '' ?>" href="categorias.php">
            <i data-lucide="folder-tree"></i> <span>Categorías</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= in_array($paginaActual, ['pedidos.php', 'detalle_pedido.php']) ? 'active' : '' ?>" href="pedidos.php">
            <i data-lucide="receipt"></i> <span>Pedidos</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= in_array($paginaActual, ['proveedores.php', 'proveedor_form.php']) ? 'active' : '' ?>" href="proveedores.php">
            <i data-lucide="truck"></i> <span>Proveedores</span>
        </a>
    </li>
</ul>

<div class="sidebar-section-title">Analítica & Reportes</div>
<ul class="nav flex-column mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $paginaActual == 'chat_admin.php' ? 'active' : '' ?>" href="chat_admin.php">
            <i data-lucide="brain" class="text-info"></i> <span>Consultas BI</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $paginaActual == 'metricas.php' ? 'active' : '' ?>" href="metricas.php">
            <i data-lucide="line-chart"></i> <span>Métricas</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $paginaActual == 'historico_ventas.php' ? 'active' : '' ?>" href="historico_ventas.php">
            <i data-lucide="history"></i> <span>Histórico de Ventas</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $paginaActual == 'alertas.php' ? 'active' : '' ?>" href="alertas.php">
            <i data-lucide="alert-triangle" class="text-warning"></i> <span>Alertas de Stock</span>
        </a>
    </li>
</ul>

<div class="sidebar-section-title">Sistema</div>
<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link <?= $paginaActual == 'configuracion.php' ? 'active' : '' ?>" href="configuracion.php">
            <i data-lucide="settings"></i> <span>Configuración</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link text-info" href="../index.php" target="_blank">
            <i data-lucide="external-link"></i> <span>Ver Tienda</span>
        </a>
    </li>
    <li class="nav-item mt-2">
        <a class="nav-link text-danger" href="logout.php">
            <i data-lucide="log-out"></i> <span>Cerrar sesión</span>
        </a>
    </li>
</ul>