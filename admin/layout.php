<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

use Services\Security\SecurityKernel;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= SecurityKernel::e(SecurityKernel::csrfToken()) ?>">
    <title><?= SecurityKernel::e($titulo ?? 'Panel Institucional') ?> — ULAT Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          integrity="sha384-t1nt8BQoYMLFN5p42tRAtuAAFQaCQODekUVeKKZrEnEyp4H2R0RHFz0KWpmj7i8g"
          crossorigin="anonymous">

    <link href="../assets/css/admin.css" rel="stylesheet">
    <?= $extraHead ?? '' ?>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky">
                    <?php include __DIR__ . '/nav.php'; ?>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between align-items-center pb-3 mb-4 border-bottom">
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-outline-secondary d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                            <i data-lucide="menu"></i>
                        </button>
                        <span class="text-muted small">Universidad Latinoamericana · Gestión de Admisiones e IA</span>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <a href="chat_admin.php" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1">
                            <i data-lucide="brain" class="text-info"></i> <span>Asistente BI</span>
                        </a>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light border rounded-pill px-3 py-1 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                                <i data-lucide="user" class="text-primary"></i>
                                <span class="fw-semibold small"><?= SecurityKernel::e($_SESSION['admin_user'] ?? 'Administrador') ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 rounded-3">
                                <li><a class="dropdown-item small d-flex align-items-center gap-2" href="configuracion.php"><i data-lucide="settings" class="text-muted"></i> Configuración</a></li>
                                <li><a class="dropdown-item small d-flex align-items-center gap-2" href="../ulat/index.php" target="_blank" rel="noopener noreferrer"><i data-lucide="external-link" class="text-muted"></i> Ir al Portal ULAT</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="POST" action="logout.php" class="m-0">
                                        <?= SecurityKernel::csrfInput() ?>
                                        <button type="submit" class="dropdown-item small text-danger d-flex align-items-center gap-2">
                                            <i data-lucide="log-out"></i> Cerrar sesión
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <?= $contenido ?? '' ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous"></script>
    <script src="../assets/js/lucide.min.js"></script>
    <script>
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
            lucide.createIcons();
        }
    </script>
    <?= $extraScripts ?? '' ?>
</body>
</html>
