<?php
// admin/layout.php
require_once 'auth.php'; // asegura que haya sesión
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? 'Panel Ejecutivo' ?> — Admin Hub</title>
    
    <!-- Google Fonts: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Estilos SaaS Admin -->
    <link href="../assets/css/admin.css" rel="stylesheet">
    <?= $extraHead ?? '' ?>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar SaaS -->
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky">
                    <?php include 'nav.php'; ?>
                </div>
            </nav>

            <!-- Contenido principal -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <!-- Topbar administrativa rápida -->
                <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-secondary d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                            <i data-lucide="menu"></i>
                        </button>
                        <span class="text-muted small">Panel de Administración</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <a href="chat_admin.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1">
                            <i data-lucide="brain" class="text-info"></i> <span>Asistente BI</span>
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light border rounded-pill px-3 py-1 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                                <i data-lucide="user" class="text-primary"></i>
                                <span class="fw-semibold small"><?= htmlspecialchars($_SESSION['admin_user'] ?? 'Administrador') ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                <li><a class="dropdown-item small d-flex align-items-center gap-2" href="configuracion.php"><i data-lucide="settings" class="text-muted"></i> Configuración</a></li>
                                <li><a class="dropdown-item small d-flex align-items-center gap-2" href="../index.php" target="_blank"><i data-lucide="store" class="text-muted"></i> Ir a la Tienda</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item small text-danger d-flex align-items-center gap-2" href="logout.php"><i data-lucide="log-out"></i> Cerrar sesión</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <?= $contenido ?>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/lucide.min.js"></script>
    <script>
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            lucide.createIcons();
        }
    </script>
    <?= $extraScripts ?? '' ?>
</body>
</html>