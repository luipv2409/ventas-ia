<?php
// admin/nav.php — Navegación institucional ULAT (Admisiones & Chatbot IA)
use Services\Security\SecurityKernel;

$paginaActual = basename($_SERVER['PHP_SELF'] ?? 'dashboard.php');
?>
<div class="sidebar-header d-flex align-items-center gap-2">
    <span class="sidebar-brand-icon"><i data-lucide="graduation-cap"></i></span>
    <div>
        <div class="fw-bold text-white brand-font" style="font-size: 1.1rem; line-height: 1;">ULAT Admin</div>
        <small class="text-white-50" style="font-size: 0.72rem;">Admisiones &amp; Guía IA</small>
    </div>
</div>

<div class="sidebar-section-title">Admisiones</div>
<ul class="nav flex-column mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $paginaActual === 'dashboard.php' ? 'active' : '' ?>" href="dashboard.php">
            <i data-lucide="layout-dashboard"></i> <span>Dashboard</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $paginaActual === 'prospectos.php' ? 'active' : '' ?>" href="prospectos.php">
            <i data-lucide="users"></i> <span>Prospectos</span>
        </a>
    </li>
</ul>

<div class="sidebar-section-title">Inteligencia &amp; Chatbot</div>
<ul class="nav flex-column mb-3">
    <li class="nav-item">
        <a class="nav-link <?= $paginaActual === 'chat_admin.php' ? 'active' : '' ?>" href="chat_admin.php">
            <i data-lucide="brain" class="text-info"></i> <span>Asistente BI</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $paginaActual === 'metricas.php' ? 'active' : '' ?>" href="metricas.php">
            <i data-lucide="line-chart"></i> <span>Métricas Chatbot</span>
        </a>
    </li>
</ul>

<div class="sidebar-section-title">Sistema</div>
<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link <?= $paginaActual === 'configuracion.php' ? 'active' : '' ?>" href="configuracion.php">
            <i data-lucide="settings"></i> <span>Configuración</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link text-info" href="../ulat/index.php" target="_blank" rel="noopener noreferrer">
            <i data-lucide="external-link"></i> <span>Ver Portal ULAT</span>
        </a>
    </li>
    <li class="nav-item mt-2 px-2">
        <form method="POST" action="logout.php" class="m-0">
            <?= SecurityKernel::csrfInput() ?>
            <button type="submit" class="nav-link text-danger bg-transparent border-0 w-100 text-start">
                <i data-lucide="log-out"></i> <span>Cerrar sesión</span>
            </button>
        </form>
    </li>
</ul>
