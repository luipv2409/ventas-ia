<?php
declare(strict_types=1);

if (!isset($pageTitle)) {
    $pageTitle = 'Universidad Latinoamericana - ULAT | Formación Profesional de Excelencia';
}
$activeNav = $activeNav ?? 'inicio';

// Cargar catálogo de carreras para los menús
$catalogFile = __DIR__ . '/../data/ulat_catalog.json';
$carrerasCatalog = file_exists($catalogFile) ? (json_decode(file_get_contents($catalogFile), true) ?: []) : [];

$carrerasSalud = array_filter($carrerasCatalog, fn($c) => str_contains($c['facultad'], 'Salud'));
$carrerasTecno = array_filter($carrerasCatalog, fn($c) => str_contains($c['facultad'], 'Tecnolog'));
$carrerasEcono = array_filter($carrerasCatalog, fn($c) => str_contains($c['facultad'], 'Económ'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- Estilos Institucionales ULAT -->
    <style>
        :root {
            --ulat-blue-dark: #0f2744;
            --ulat-blue-primary: #153e75;
            --ulat-blue-accent: #1e5bb0;
            --ulat-gold: #e5a93c;
            --ulat-gold-hover: #c98e29;
            --ulat-light: #f8fafc;
            --ulat-gray: #64748b;
        }

        body {
            font-family: 'Inter', sans-serif;
            color: #334155;
            background-color: var(--ulat-light);
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6, .font-heading {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
        }

        /* Topbar */
        .ulat-topbar {
            background-color: var(--ulat-blue-dark);
            color: #cbd5e1;
            font-size: 0.82rem;
            padding: 7px 0;
            border-bottom: 2px solid var(--ulat-gold);
        }
        .ulat-topbar a {
            color: #cbd5e1;
            text-decoration: none;
            transition: color 0.2s;
        }
        .ulat-topbar a:hover {
            color: var(--ulat-gold);
        }

        /* Navbar Principal */
        .ulat-navbar {
            background-color: #ffffff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            padding: 12px 0;
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        .ulat-navbar .navbar-brand img {
            max-height: 48px;
            width: auto;
        }
        .ulat-navbar .nav-link {
            font-weight: 600;
            font-size: 0.92rem;
            color: var(--ulat-blue-dark);
            padding: 8px 14px !important;
            transition: all 0.2s ease;
            border-radius: 6px;
        }
        .ulat-navbar .nav-link:hover, .ulat-navbar .nav-link.active {
            color: var(--ulat-blue-accent);
            background-color: rgba(30, 91, 176, 0.06);
        }
        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 30px rgba(15, 39, 68, 0.12);
            border-radius: 10px;
            padding: 10px 0;
        }
        .dropdown-item {
            font-size: 0.88rem;
            font-weight: 500;
            padding: 8px 20px;
            color: #334155;
            transition: all 0.2s;
        }
        .dropdown-item:hover {
            background-color: #eff6ff;
            color: var(--ulat-blue-primary);
            padding-left: 24px;
        }

        .btn-ulat-gold {
            background-color: var(--ulat-gold);
            color: #ffffff;
            font-weight: 700;
            border-radius: 30px;
            padding: 9px 22px;
            border: none;
            box-shadow: 0 4px 14px rgba(229, 169, 60, 0.35);
            transition: all 0.2s;
        }
        .btn-ulat-gold:hover {
            background-color: var(--ulat-gold-hover);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(229, 169, 60, 0.45);
        }

        .btn-ulat-primary {
            background-color: var(--ulat-blue-primary);
            color: #ffffff;
            font-weight: 600;
            border-radius: 30px;
            padding: 9px 22px;
            border: none;
            transition: all 0.2s;
        }
        .btn-ulat-primary:hover {
            background-color: var(--ulat-blue-dark);
            color: #ffffff;
            transform: translateY(-2px);
        }

        /* Banner Hero */
        .ulat-hero {
            position: relative;
            background: linear-gradient(135deg, rgba(15, 39, 68, 0.92) 0%, rgba(21, 62, 117, 0.88) 100%), url('../assets/ulat/banner_1_1920x600.jpg') center/cover no-repeat;
            color: #ffffff;
            padding: 90px 0 100px;
        }

        /* Cards y Componentes */
        .card-ulat {
            border: none;
            border-radius: 14px;
            box-shadow: 0 6px 24px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            background: #ffffff;
            overflow: hidden;
        }
        .card-ulat:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 32px rgba(15, 39, 68, 0.12);
        }

        .badge-facultad {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 20px;
        }
        .badge-salud { background-color: #fee2e2; color: #b91c1c; }
        .badge-tecno { background-color: #e0f2fe; color: #0369a1; }
        .badge-econo { background-color: #fef3c7; color: #b45309; }

        /* Footer */
        .ulat-footer {
            background-color: var(--ulat-blue-dark);
            color: #94a3b8;
            padding: 70px 0 30px;
            border-top: 4px solid var(--ulat-gold);
        }
        .ulat-footer h5 {
            color: #ffffff;
            font-size: 1.15rem;
            margin-bottom: 22px;
            position: relative;
            padding-bottom: 10px;
        }
        .ulat-footer h5::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 2px;
            background-color: var(--ulat-gold);
        }
        .ulat-footer a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.2s;
            display: inline-block;
            margin-bottom: 9px;
            font-size: 0.9rem;
        }
        .ulat-footer a:hover {
            color: var(--ulat-gold);
            padding-left: 4px;
        }

        /* Floating Chat Widget Override */
        .chat-widget {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1050;
        }
        .chat-toggle-btn {
            width: 62px;
            height: 62px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--ulat-gold) 0%, var(--ulat-gold-hover) 100%);
            color: white;
            border: none;
            box-shadow: 0 8px 24px rgba(229, 169, 60, 0.45);
            cursor: pointer;
            position: relative;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .chat-toggle-btn:hover {
            transform: scale(1.06);
            box-shadow: 0 10px 28px rgba(229, 169, 60, 0.55);
        }
        .online-aura {
            position: absolute;
            top: 4px;
            right: 4px;
            width: 14px;
            height: 14px;
            background-color: #22c55e;
            border: 2px solid white;
            border-radius: 50%;
        }
        .chat-window {
            position: fixed;
            bottom: 96px;
            right: 24px;
            width: 380px;
            max-width: calc(100vw - 32px);
            height: 540px;
            max-height: calc(100vh - 120px);
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 16px 48px rgba(15, 39, 68, 0.22);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid rgba(15, 39, 68, 0.08);
            z-index: 1050;
        }
        .chat-header {
            background: linear-gradient(135deg, var(--ulat-blue-dark) 0%, var(--ulat-blue-primary) 100%);
            color: white;
            padding: 15px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chat-avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background-color: var(--ulat-gold);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        .chat-messages {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            background-color: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .message {
            max-width: 82%;
            padding: 11px 15px;
            border-radius: 14px;
            font-size: 0.88rem;
            line-height: 1.45;
            word-wrap: break-word;
        }
        .bot-message {
            align-self: flex-start;
            background: #ffffff;
            color: #1e293b;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 4px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.03);
        }
        .user-message {
            align-self: flex-end;
            background: var(--ulat-blue-primary);
            color: #ffffff;
            border-bottom-right-radius: 4px;
        }
    </style>
</head>
<body>

    <!-- TOPBAR INFORMATIVO -->
    <div class="ulat-topbar">
        <div class="container d-flex flex-wrap justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <span><i class="fa-solid fa-location-dot text-warning me-1"></i> Calle Uruguay #E-836 entre 16 de Julio y Oquendo, Cochabamba</span>
                <span class="d-none d-md-inline"><i class="fa-solid fa-phone text-warning me-1"></i> (4) 4509488 - 4221004</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="mailto:latinoamericana@ulat.edu.bo"><i class="fa-solid fa-envelope text-warning me-1"></i> latinoamericana@ulat.edu.bo</a>
                <span class="text-white-50">|</span>
                <a href="https://wa.me/5914509488" target="_blank"><i class="fa-brands fa-whatsapp text-success me-1"></i> WhatsApp Admisiones</a>
            </div>
        </div>
    </div>

    <!-- NAVBAR PRINCIPAL -->
    <nav class="navbar navbar-expand-lg ulat-navbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="index.php">
                <img src="../assets/ulat/logo.png" alt="Universidad Latinoamericana" onerror="this.src='https://ulat.edu.bo/wp-content/uploads/2021/10/logo-ulat-1.png';">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarUlat" aria-controls="navbarUlat" aria-expanded="false" aria-label="Navegación">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarUlat">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-1">
                    <li class="nav-link-item">
                        <a class="nav-link <?= $activeNav === 'inicio' ? 'active' : '' ?>" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= $activeNav === 'facultades' ? 'active' : '' ?>" href="#" id="dropFacultades" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Facultades
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="dropFacultades">
                            <li><a class="dropdown-item fw-bold text-primary" href="facultades.php#salud"><i class="fa-solid fa-heart-pulse me-2 text-danger"></i> Ciencias de la Salud</a></li>
                            <?php foreach ($carrerasSalud as $s => $cs): ?>
                                <li><a class="dropdown-item ps-4 py-1" href="carrera.php?id=<?= $s ?>"><?= htmlspecialchars($cs['nombre']) ?></a></li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item fw-bold text-primary" href="facultades.php#tecnologia"><i class="fa-solid fa-microchip me-2 text-info"></i> Ciencia y Tecnología</a></li>
                            <?php foreach ($carrerasTecno as $s => $ct): ?>
                                <li><a class="dropdown-item ps-4 py-1" href="carrera.php?id=<?= $s ?>"><?= htmlspecialchars($ct['nombre']) ?></a></li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item fw-bold text-primary" href="facultades.php#economicas"><i class="fa-solid fa-scale-balanced me-2 text-warning"></i> Ciencias Económicas y Jurídicas</a></li>
                            <?php foreach ($carrerasEcono as $s => $ce): ?>
                                <li><a class="dropdown-item ps-4 py-1" href="carrera.php?id=<?= $s ?>"><?= htmlspecialchars($ce['nombre']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeNav === 'carreras' ? 'active' : '' ?>" href="carreras.php">Carreras (15)</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeNav === 'admisiones' ? 'active' : '' ?>" href="admisiones.php">Admisiones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeNav === 'becas' ? 'active' : '' ?>" href="becas.php">Becas & Plan 1 a 1</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeNav === 'reglamentos' ? 'active' : '' ?>" href="reglamentos.php">Reglamentos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeNav === 'historia' ? 'active' : '' ?>" href="historia.php">Nosotros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeNav === 'contacto' ? 'active' : '' ?>" href="contacto.php">Contacto</a>
                    </li>
                    <li class="nav-item ms-lg-2 mt-2 mt-lg-0">
                        <a class="btn btn-ulat-gold btn-sm px-3" href="admisiones.php#preinscripcion">
                            <i class="fa-solid fa-graduation-cap me-1"></i> Preinscríbete
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
