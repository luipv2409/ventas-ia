<?php
declare(strict_types=1);

if (!isset($pageTitle)) {
    $pageTitle = 'Universidad Latinoamericana — ULAT | Educación Superior en Cochabamba';
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
    
    <!-- Tipografía Editorial e Institucional -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=Newsreader:ital,opsz,wght@0,6..72,500;0,6..72,600;1,6..72,400&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Lucide Monoline Architectural Icons (Local) -->
    <script src="../assets/js/lucide.min.js"></script>
    
    <!-- Sistema de Diseño Institucional ULAT -->
    <style>
        :root {
            --ulat-navy-deep: #081629;
            --ulat-blue-dark: #0c213d;
            --ulat-blue-primary: #143866;
            --ulat-blue-accent: #1d4ed8;
            --ulat-gold: #d49a35;
            --ulat-gold-hover: #b88224;
            --ulat-gold-dark: #9a6b18;
            --ulat-gold-soft: #fbf6ec;
            --ulat-surface: #f8f9fb;
            --ulat-border: #e2e8f0;
            --ulat-text: #1e293b;
            --ulat-muted: #64748b;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--ulat-text);
            background-color: var(--ulat-surface);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }

        h1, h2, h3, h4, h5, h6, .font-heading {
            font-family: 'Plus Jakarta Sans', sans-serif;
            letter-spacing: -0.02em;
            color: var(--ulat-blue-dark);
        }

        .font-editorial {
            font-family: 'Newsreader', Georgia, serif;
        }

        /* Iconografía Monolínea Arquitectónica (Lucide 1.5px) */
        .lucide {
            width: 18px;
            height: 18px;
            stroke-width: 1.6;
            vertical-align: -3px;
            flex-shrink: 0;
        }
        .lucide-sm {
            width: 15px;
            height: 15px;
            stroke-width: 1.6;
            vertical-align: -2px;
        }
        .lucide-lg {
            width: 22px;
            height: 22px;
            stroke-width: 1.5;
        }

        /* Marco sobrio para iconos (Cero cajas pastel de IA) */
        .icon-frame {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            border: 1px solid var(--ulat-border);
            background-color: #ffffff;
            color: var(--ulat-blue-dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .icon-frame-gold {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            border: 1px solid rgba(212, 154, 53, 0.35);
            background-color: var(--ulat-gold-soft);
            color: var(--ulat-gold-dark);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Índice tipográfico editorial (01, 02, 03) */
        .academic-index {
            font-family: 'Newsreader', Georgia, serif;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--ulat-gold-dark);
            letter-spacing: 0.04em;
            padding: 4px 10px;
            border: 1px solid rgba(212, 154, 53, 0.35);
            background-color: var(--ulat-gold-soft);
            border-radius: 4px;
            display: inline-block;
        }

        .ulat-eyebrow {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--ulat-gold-dark);
            display: inline-block;
            margin-bottom: 0.5rem;
        }
        .ulat-eyebrow-light {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--ulat-gold);
            display: inline-block;
            margin-bottom: 0.5rem;
        }

        /* Topbar Institucional */
        .ulat-topbar {
            background-color: var(--ulat-navy-deep);
            color: #94a3b8;
            font-size: 0.78rem;
            padding: 7px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.07);
        }
        .ulat-topbar a {
            color: #cbd5e1;
            text-decoration: none;
            transition: color 0.15s;
        }
        .ulat-topbar a:hover {
            color: var(--ulat-gold);
        }

        /* Navbar Principal (Fondo Azul Marino Institucional para contraste perfecto del logo blanco y enlaces en 1 sola línea) */
        .ulat-navbar {
            background-color: var(--ulat-blue-dark);
            border-bottom: 2px solid var(--ulat-gold);
            padding: 11px 0;
            position: sticky;
            top: 0;
            z-index: 1020;
            box-shadow: 0 8px 24px rgba(8, 22, 41, 0.18);
        }
        .ulat-navbar .navbar-brand {
            padding: 0;
            margin-right: 1.5rem;
            flex-shrink: 0;
        }
        .ulat-navbar .navbar-brand img {
            height: 44px;
            width: auto;
            display: block;
        }
        .ulat-navbar .navbar-nav {
            flex-wrap: nowrap;
        }
        .ulat-navbar .nav-link {
            font-weight: 500;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.86) !important;
            padding: 7px 11px !important;
            white-space: nowrap;
            transition: all 0.15s ease;
            border-radius: 5px;
            position: relative;
        }
        .ulat-navbar .nav-link:hover {
            color: #ffffff !important;
            background-color: rgba(255, 255, 255, 0.07);
        }
        .ulat-navbar .nav-link.active {
            color: #ffffff !important;
            font-weight: 600;
            background-color: rgba(212, 154, 53, 0.16);
        }
        .ulat-navbar .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 2px;
            left: 11px;
            right: 11px;
            height: 2px;
            background-color: var(--ulat-gold);
            border-radius: 1px;
        }

        .dropdown-menu {
            border: 1px solid var(--ulat-border);
            box-shadow: 0 14px 34px rgba(8, 22, 41, 0.16);
            border-radius: 8px;
            padding: 8px 0;
            min-width: 280px;
            margin-top: 8px !important;
        }
        .dropdown-header-ulat {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--ulat-blue-primary);
            padding: 8px 18px 4px;
            display: block;
            text-decoration: none;
        }
        .dropdown-item {
            font-size: 0.84rem;
            font-weight: 500;
            padding: 6px 18px;
            color: #334155;
            transition: background-color 0.15s, color 0.15s;
        }
        .dropdown-item:hover {
            background-color: #f1f5f9;
            color: var(--ulat-blue-dark);
        }

        /* Botones Institucionales (Geometría arquitectónica 6px, sin abuso de píldoras) */
        .btn-ulat-gold {
            background-color: var(--ulat-gold);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.875rem;
            border-radius: 6px;
            padding: 8px 18px;
            border: 1px solid var(--ulat-gold);
            white-space: nowrap;
            transition: all 0.18s ease;
        }
        .btn-ulat-gold:hover {
            background-color: var(--ulat-gold-hover);
            border-color: var(--ulat-gold-hover);
            color: #ffffff;
        }

        .btn-ulat-primary {
            background-color: var(--ulat-blue-dark);
            color: #ffffff;
            font-weight: 600;
            font-size: 0.875rem;
            border-radius: 6px;
            padding: 9px 20px;
            border: 1px solid var(--ulat-blue-dark);
            white-space: nowrap;
            transition: all 0.18s ease;
        }
        .btn-ulat-primary:hover {
            background-color: var(--ulat-blue-primary);
            border-color: var(--ulat-blue-primary);
            color: #ffffff;
        }

        .btn-ulat-outline {
            background-color: transparent;
            color: var(--ulat-blue-dark);
            font-weight: 600;
            font-size: 0.875rem;
            border-radius: 6px;
            padding: 8px 18px;
            border: 1px solid #cbd5e1;
            white-space: nowrap;
            transition: all 0.18s ease;
        }
        .btn-ulat-outline:hover {
            background-color: var(--ulat-blue-dark);
            border-color: var(--ulat-blue-dark);
            color: #ffffff;
        }

        /* Banner Hero & Encabezados de Página */
        .ulat-hero {
            position: relative;
            background: linear-gradient(115deg, rgba(8, 22, 41, 0.94) 0%, rgba(12, 33, 61, 0.88) 60%, rgba(20, 56, 102, 0.82) 100%), url('../assets/ulat/banner_1_1920x600.jpg') center/cover no-repeat;
            color: #ffffff;
            padding: 76px 0 84px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .ulat-page-header {
            background: linear-gradient(120deg, var(--ulat-navy-deep) 0%, var(--ulat-blue-dark) 65%, var(--ulat-blue-primary) 100%);
            color: #ffffff;
            padding: 52px 0;
            border-bottom: 1px solid var(--ulat-border);
        }

        /* Tarjetas Editoriales (Sin bordes arcoíris de IA) */
        .card-ulat {
            border: 1px solid var(--ulat-border);
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
            transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
            background: #ffffff;
            overflow: hidden;
        }
        .card-ulat:hover {
            border-color: #cbd5e1;
            box-shadow: 0 12px 28px -8px rgba(12, 33, 61, 0.1);
        }

        /* Etiquetas sobrias de facultad */
        .badge-facultad {
            font-size: 0.71rem;
            font-weight: 600;
            letter-spacing: 0.03em;
            padding: 4px 10px;
            border-radius: 4px;
            border: 1px solid transparent;
        }
        .badge-salud { background-color: #f8fafc; color: #0f2744; border-color: #cbd5e1; }
        .badge-tecno { background-color: #f8fafc; color: #0f2744; border-color: #cbd5e1; }
        .badge-econo { background-color: #f8fafc; color: #0f2744; border-color: #cbd5e1; }

        /* Tabla de Malla Curricular Semestral */
        .semester-card {
            border: 1px solid var(--ulat-border);
            border-radius: 6px;
            background: #ffffff;
            height: 100%;
        }
        .semester-header {
            background-color: #f8fafc;
            border-bottom: 1px solid var(--ulat-border);
            padding: 10px 14px;
            font-weight: 700;
            font-size: 0.84rem;
            color: var(--ulat-blue-dark);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .semester-list {
            list-style: none;
            padding: 10px 14px;
            margin: 0;
            font-size: 0.83rem;
            color: #334155;
        }
        .semester-list li {
            padding: 5px 0;
            border-bottom: 1px dashed #e2e8f0;
            display: flex;
            align-items: baseline;
            gap: 8px;
        }
        .semester-list li:last-child {
            border-bottom: none;
        }
        .semester-list li::before {
            content: '·';
            color: var(--ulat-gold-dark);
            font-weight: 700;
        }

        /* Footer Institucional */
        .ulat-footer {
            background-color: var(--ulat-navy-deep);
            color: #94a3b8;
            padding: 64px 0 28px;
            border-top: 3px solid var(--ulat-gold);
        }
        .ulat-footer h5 {
            color: #ffffff;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .ulat-footer a {
            color: #94a3b8;
            text-decoration: none;
            transition: color 0.15s;
            display: inline-block;
            margin-bottom: 8px;
            font-size: 0.86rem;
        }
        .ulat-footer a:hover {
            color: #ffffff;
        }

        /* Floating Chat Widget */
        .chat-widget {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1050;
        }
        .chat-toggle-btn {
            height: 52px;
            padding: 0 20px;
            border-radius: 26px;
            background-color: var(--ulat-blue-dark);
            color: #ffffff;
            border: 1px solid rgba(212, 154, 53, 0.5);
            box-shadow: 0 10px 28px rgba(8, 22, 41, 0.28);
            cursor: pointer;
            position: relative;
            transition: all 0.2s ease;
            font-weight: 600;
            font-size: 0.86rem;
            gap: 10px;
        }
        .chat-toggle-btn:hover {
            background-color: var(--ulat-blue-primary);
            transform: translateY(-2px);
        }
        .online-aura {
            width: 9px;
            height: 9px;
            background-color: #22c55e;
            border-radius: 50%;
            display: inline-block;
            box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.22);
        }
        .chat-window {
            position: fixed;
            bottom: 88px;
            right: 24px;
            width: 385px;
            max-width: calc(100vw - 32px);
            height: 550px;
            max-height: calc(100vh - 110px);
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 20px 50px rgba(8, 22, 41, 0.25);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid var(--ulat-border);
            z-index: 1050;
        }
        .chat-header {
            background-color: var(--ulat-blue-dark);
            color: white;
            padding: 14px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid var(--ulat-gold);
        }
        .chat-avatar {
            width: 36px;
            height: 36px;
            border-radius: 6px;
            background-color: rgba(212, 154, 53, 0.2);
            border: 1px solid var(--ulat-gold);
            color: var(--ulat-gold);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 0.85rem;
        }
        .chat-messages {
            flex: 1;
            padding: 16px;
            overflow-y: auto;
            background-color: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .message {
            max-width: 85%;
            padding: 10px 14px;
            border-radius: 10px;
            font-size: 0.875rem;
            line-height: 1.48;
            word-wrap: break-word;
        }
        .bot-message {
            align-self: flex-start;
            background: #ffffff;
            color: #1e293b;
            border: 1px solid #e2e8f0;
            border-bottom-left-radius: 2px;
        }
        .user-message {
            align-self: flex-end;
            background: var(--ulat-blue-dark);
            color: #ffffff;
            border-bottom-right-radius: 2px;
        }
    </style>
</head>
<body>

    <!-- TOPBAR INSTITUCIONAL -->
    <div class="ulat-topbar">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center flex-wrap gap-3">
                <span class="d-inline-flex align-items-center gap-1">
                    <i data-lucide="map-pin" class="lucide-sm" style="color: var(--ulat-gold);"></i>
                    Sede Central: C. Uruguay #E-836 · Campus Norte: Av. Uyuni #1889
                </span>
                <span class="d-none d-md-inline-flex align-items-center gap-1">
                    <i data-lucide="phone" class="lucide-sm" style="color: var(--ulat-gold);"></i>
                    (4) 4509488 – 4221004
                </span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="mailto:latinoamericana@ulat.edu.bo" class="d-none d-sm-inline-flex align-items-center gap-1">
                    <i data-lucide="mail" class="lucide-sm" style="color: var(--ulat-gold);"></i>
                    latinoamericana@ulat.edu.bo
                </a>
                <span class="text-white-50 d-none d-sm-inline">|</span>
                <a href="https://wa.me/59179795778" target="_blank" class="d-inline-flex align-items-center gap-1 fw-medium text-white">
                    <i data-lucide="message-Square" class="lucide-sm" style="color: #4ade80;"></i>
                    WhatsApp Inscripciones: 79795778
                </a>
            </div>
        </div>
    </div>

    <!-- NAVBAR PRINCIPAL (1 SOLA LÍNEA, CONTRASTE OFICIAL) -->
    <nav class="navbar navbar-expand-lg navbar-dark ulat-navbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="../assets/ulat/logo.png" alt="Universidad Latinoamericana ULAT">
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarUlat" aria-controls="navbarUlat" aria-expanded="false" aria-label="Navegación">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarUlat">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center gap-1">
                    <li class="nav-item">
                        <a class="nav-link <?= $activeNav === 'inicio' ? 'active' : '' ?>" href="index.php">Inicio</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?= $activeNav === 'facultades' ? 'active' : '' ?>" href="#" id="dropFacultades" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Facultades
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropFacultades">
                            <li><a class="dropdown-header-ulat" href="facultades.php#salud">Facultad de Ciencias de la Salud</a></li>
                            <?php foreach ($carrerasSalud as $s => $cs): ?>
                                <li><a class="dropdown-item" href="carrera.php?id=<?= $s ?>"><?= htmlspecialchars($cs['nombre']) ?></a></li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><a class="dropdown-header-ulat" href="facultades.php#tecnologia">Facultad de Ciencia y Tecnología</a></li>
                            <?php foreach ($carrerasTecno as $s => $ct): ?>
                                <li><a class="dropdown-item" href="carrera.php?id=<?= $s ?>"><?= htmlspecialchars($ct['nombre']) ?></a></li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li><a class="dropdown-header-ulat" href="facultades.php#economicas">Ciencias Económicas y Jurídicas</a></li>
                            <?php foreach ($carrerasEcono as $s => $ce): ?>
                                <li><a class="dropdown-item" href="carrera.php?id=<?= $s ?>"><?= htmlspecialchars($ce['nombre']) ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeNav === 'carreras' ? 'active' : '' ?>" href="carreras.php">Carreras</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeNav === 'admisiones' ? 'active' : '' ?>" href="admisiones.php">Admisiones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $activeNav === 'becas' ? 'active' : '' ?>" href="becas.php">Becas</a>
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
                        <a class="btn btn-ulat-gold btn-sm px-3 py-2 d-inline-flex align-items-center gap-1" href="admisiones.php#preinscripcion">
                            <span>Preinscripción</span>
                            <i data-lucide="arrow-up-right" class="lucide-sm"></i>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
