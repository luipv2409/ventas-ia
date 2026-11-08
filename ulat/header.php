<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

use Services\Security\SecurityKernel;

SecurityKernel::startSession(false);
SecurityKernel::applySecurityHeaders('ulat');

if (!isset($pageTitle)) {
    $pageTitle = 'Universidad Latinoamericana — ULAT | Educación Superior en Cochabamba';
}
$activeNav = $activeNav ?? 'inicio';

// Cargar catálogo de carreras para los menús
$catalogFile = __DIR__ . '/../data/ulat_catalog.json';
$carrerasCatalog = file_exists($catalogFile) ? (json_decode((string)file_get_contents($catalogFile), true) ?: []) : [];

$carrerasSalud = array_filter($carrerasCatalog, fn($c) => str_contains($c['facultad'], 'Salud'));
$carrerasTecno = array_filter($carrerasCatalog, fn($c) => str_contains($c['facultad'], 'Tecnolog'));
$carrerasEcono = array_filter($carrerasCatalog, fn($c) => str_contains($c['facultad'], 'Económ'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SecurityKernel::e($pageTitle) ?></title>
    
    <!-- Tipografía Editorial Suiza: Inter + Newsreader (Serif) + JetBrains Mono -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;500&family=Newsreader:ital,opsz,wght@0,6..72,400;0,6..72,500;1,6..72,400&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 Grid & Utilities (con SRI) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <!-- Lucide Monoline Icons -->
    <script src="../assets/js/lucide.min.js"></script>
    
    <!-- Sistema de Diseño Minimalista Editorial ULAT -->
    <style>
        :root {
            --ulat-bg: #FAF9F5;
            --ulat-surface: #FFFFFF;
            --ulat-subtle: #F3F1EC;
            --ulat-ink: #0A1526;
            --ulat-navy: #0D1E36;
            --ulat-text: #2C3748;
            --ulat-muted: #647082;
            --ulat-bronze: #C59B27;
            --ulat-bronze-soft: rgba(197, 155, 39, 0.12);
            --ulat-line: rgba(10, 21, 38, 0.09);
            --ulat-line-strong: rgba(10, 21, 38, 0.18);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--ulat-text);
            background-color: var(--ulat-bg);
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
            letter-spacing: -0.008em;
            line-height: 1.65;
        }

        h1, h2, h3, h4, h5, h6 {
            color: var(--ulat-ink);
            font-weight: 500;
            letter-spacing: -0.025em;
            line-height: 1.18;
        }

        .font-editorial {
            font-family: 'Newsreader', Georgia, serif;
            font-weight: 400;
            letter-spacing: -0.02em;
        }

        .font-mono-code {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.73rem;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }

        /* Iconos Monolínea Ultraligeros */
        .lucide {
            width: 16px;
            height: 16px;
            stroke-width: 1.4;
            vertical-align: -2px;
            flex-shrink: 0;
        }
        .lucide-sm {
            width: 14px;
            height: 14px;
            stroke-width: 1.4;
            vertical-align: -1px;
        }

        /* Etiquetas e Índices Minimalistas */
        .ulat-eyebrow {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: var(--ulat-muted);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 0.85rem;
        }
        .ulat-eyebrow::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: var(--ulat-bronze);
        }
        .ulat-eyebrow-light {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.7rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            color: rgba(255, 255, 255, 0.65);
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 0.85rem;
        }
        .ulat-eyebrow-light::before {
            content: '';
            display: inline-block;
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background-color: var(--ulat-bronze);
        }

        .academic-index {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.72rem;
            color: var(--ulat-muted);
            letter-spacing: 0.08em;
        }

        /* Topbar Minimalista */
        .ulat-topbar {
            background-color: #060D18;
            color: rgba(255, 255, 255, 0.55);
            font-size: 0.74rem;
            padding: 6px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }
        .ulat-topbar a {
            color: rgba(255, 255, 255, 0.72);
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .ulat-topbar a:hover {
            color: #ffffff;
        }

        /* Navbar Principal Minimalista (1 sola línea garantizada) */
        .ulat-navbar {
            background-color: rgba(10, 21, 38, 0.96);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 13px 0;
            position: sticky;
            top: 0;
            z-index: 1020;
        }
        .ulat-navbar .navbar-brand {
            padding: 0;
            margin-right: 2rem;
            flex-shrink: 0;
        }
        .ulat-navbar .navbar-brand img {
            height: 40px;
            width: auto;
            display: block;
        }
        .ulat-navbar .navbar-nav {
            flex-wrap: nowrap;
        }
        .ulat-navbar .nav-link {
            font-weight: 400;
            font-size: 0.84rem;
            color: rgba(255, 255, 255, 0.72) !important;
            padding: 6px 12px !important;
            white-space: nowrap;
            transition: color 0.2s ease;
            position: relative;
        }
        .ulat-navbar .nav-link:hover {
            color: #ffffff !important;
        }
        .ulat-navbar .nav-link.active {
            color: #ffffff !important;
            font-weight: 500;
        }
        .ulat-navbar .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 12px;
            right: 12px;
            height: 1.5px;
            background-color: var(--ulat-bronze);
        }

        .dropdown-menu {
            background-color: #ffffff;
            border: 1px solid var(--ulat-line-strong);
            box-shadow: 0 20px 40px -15px rgba(10, 21, 38, 0.14);
            border-radius: 6px;
            padding: 10px 0;
            min-width: 270px;
            margin-top: 10px !important;
        }
        .dropdown-header-ulat {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.66rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--ulat-muted);
            padding: 8px 20px 4px;
            display: block;
            text-decoration: none;
        }
        .dropdown-item {
            font-size: 0.83rem;
            font-weight: 400;
            padding: 6px 20px;
            color: var(--ulat-text);
            transition: all 0.15s ease;
        }
        .dropdown-item:hover {
            background-color: var(--ulat-bg);
            color: var(--ulat-ink);
            padding-left: 23px;
        }

        /* Botones Minimalistas */
        .btn-ulat-gold {
            background-color: var(--ulat-bronze);
            color: #ffffff;
            font-weight: 500;
            font-size: 0.83rem;
            border-radius: 4px;
            padding: 8px 18px;
            border: 1px solid var(--ulat-bronze);
            white-space: nowrap;
            transition: all 0.2s ease;
        }
        .btn-ulat-gold:hover {
            background-color: #b0891e;
            border-color: #b0891e;
            color: #ffffff;
        }

        .btn-ulat-primary {
            background-color: var(--ulat-ink);
            color: #ffffff;
            font-weight: 500;
            font-size: 0.83rem;
            border-radius: 4px;
            padding: 9px 20px;
            border: 1px solid var(--ulat-ink);
            white-space: nowrap;
            transition: all 0.2s ease;
        }
        .btn-ulat-primary:hover {
            background-color: #1a2c47;
            border-color: #1a2c47;
            color: #ffffff;
        }

        .btn-ulat-outline {
            background-color: transparent;
            color: var(--ulat-ink);
            font-weight: 500;
            font-size: 0.83rem;
            border-radius: 4px;
            padding: 8px 18px;
            border: 1px solid var(--ulat-line-strong);
            white-space: nowrap;
            transition: all 0.2s ease;
        }
        .btn-ulat-outline:hover {
            background-color: var(--ulat-ink);
            border-color: var(--ulat-ink);
            color: #ffffff;
        }

        .btn-ulat-ghost-light {
            background-color: rgba(255, 255, 255, 0.06);
            color: #ffffff;
            font-weight: 400;
            font-size: 0.83rem;
            border-radius: 4px;
            padding: 8px 18px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            white-space: nowrap;
            transition: all 0.2s ease;
        }
        .btn-ulat-ghost-light:hover {
            background-color: #ffffff;
            color: var(--ulat-ink);
        }

        /* Hero y Cabeceras Editoriales */
        .ulat-hero {
            position: relative;
            background: linear-gradient(110deg, rgba(6, 13, 24, 0.95) 0%, rgba(10, 21, 38, 0.90) 58%, rgba(13, 30, 54, 0.82) 100%), url('../assets/ulat/banner_1_1920x600.jpg') center/cover no-repeat;
            color: #ffffff;
            padding: 92px 0 96px;
        }

        .ulat-page-header {
            background-color: var(--ulat-ink);
            color: #ffffff;
            padding: 64px 0 56px;
            border-bottom: 1px solid var(--ulat-line);
        }

        /* Superficies y Líneas Divisorias Suizas (Sin cajas recargadas) */
        .card-ulat {
            background: var(--ulat-surface);
            border: 1px solid var(--ulat-line);
            border-radius: 4px;
            box-shadow: none;
            transition: border-color 0.25s ease, transform 0.25s ease;
        }
        .card-ulat:hover {
            border-color: var(--ulat-line-strong);
        }

        .swiss-grid-row {
            border-top: 1px solid var(--ulat-line);
            padding: 28px 0;
            transition: background-color 0.2s ease;
        }
        .swiss-grid-row:last-child {
            border-bottom: 1px solid var(--ulat-line);
        }

        .career-link-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 11px 0;
            border-bottom: 1px solid var(--ulat-line);
            color: var(--ulat-ink);
            text-decoration: none;
            font-size: 0.88rem;
            transition: padding-left 0.2s ease, color 0.2s ease;
        }
        .career-link-row:last-child {
            border-bottom: none;
        }
        .career-link-row:hover {
            padding-left: 6px;
            color: var(--ulat-bronze);
        }

        /* Inputs Minimalistas */
        .form-control, .form-select {
            background-color: var(--ulat-bg);
            border: 1px solid var(--ulat-line-strong);
            border-radius: 4px;
            font-size: 0.85rem;
            padding: 9px 12px;
            color: var(--ulat-ink);
            box-shadow: none !important;
        }
        .form-control:focus, .form-select:focus {
            background-color: #ffffff;
            border-color: var(--ulat-ink);
        }

        /* Semestres Minimalistas */
        .semester-card {
            border-top: 1.5px solid var(--ulat-ink);
            background: transparent;
            padding-top: 14px;
            height: 100%;
        }
        .semester-header {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--ulat-ink);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .semester-list {
            list-style: none;
            padding: 0;
            margin: 0;
            font-size: 0.84rem;
            color: var(--ulat-text);
        }
        .semester-list li {
            padding: 6px 0;
            border-bottom: 1px solid var(--ulat-line);
            display: flex;
            align-items: baseline;
            gap: 8px;
        }
        .semester-list li:last-child {
            border-bottom: none;
        }

        /* Footer Minimalista */
        .ulat-footer {
            background-color: var(--ulat-ink);
            color: rgba(255, 255, 255, 0.55);
            padding: 72px 0 36px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
        }
        .ulat-footer h5 {
            font-family: 'JetBrains Mono', monospace;
            color: rgba(255, 255, 255, 0.45);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.14em;
            margin-bottom: 18px;
        }
        .ulat-footer a {
            color: rgba(255, 255, 255, 0.68);
            text-decoration: none;
            transition: color 0.15s;
            display: inline-block;
            margin-bottom: 8px;
            font-size: 0.84rem;
        }
        .ulat-footer a:hover {
            color: #ffffff;
        }

        /* Floating Chat Widget (Glassmorphism Editorial) */
        .chat-widget {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 1050;
        }
        .chat-toggle-btn {
            height: 46px;
            padding: 0 18px;
            border-radius: 23px;
            background-color: var(--ulat-ink);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.14);
            box-shadow: 0 12px 32px rgba(6, 13, 24, 0.22);
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 0.82rem;
            letter-spacing: -0.01em;
            gap: 10px;
        }
        .chat-toggle-btn:hover {
            transform: translateY(-2px);
            background-color: #162844;
        }
        .online-aura {
            width: 7px;
            height: 7px;
            background-color: #34d399;
            border-radius: 50%;
            display: inline-block;
        }
        .chat-window {
            position: fixed;
            bottom: 82px;
            right: 24px;
            width: 380px;
            max-width: calc(100vw - 32px);
            height: 540px;
            max-height: calc(100vh - 110px);
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0 24px 60px rgba(6, 13, 24, 0.22);
            display: flex;
            flex-direction: column;
            overflow: hidden;
            border: 1px solid var(--ulat-line-strong);
            z-index: 1050;
        }
        .chat-header {
            background-color: var(--ulat-ink);
            color: white;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .chat-avatar {
            width: 32px;
            height: 32px;
            border-radius: 4px;
            background-color: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: var(--ulat-bronze);
            font-family: 'JetBrains Mono', monospace;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
        }
        .chat-messages {
            flex: 1;
            padding: 18px;
            overflow-y: auto;
            background-color: var(--ulat-bg);
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .message {
            max-width: 86%;
            padding: 11px 14px;
            border-radius: 6px;
            font-size: 0.84rem;
            line-height: 1.52;
            word-wrap: break-word;
        }
        .bot-message {
            align-self: flex-start;
            background: #ffffff;
            color: var(--ulat-text);
            border: 1px solid var(--ulat-line);
        }
        .user-message {
            align-self: flex-end;
            background: var(--ulat-ink);
            color: #ffffff;
        }
        .chat-chip {
            font-size: 0.73rem;
            padding: 4px 10px;
            border-radius: 14px;
            border: 1px solid var(--ulat-line-strong);
            background: #ffffff;
            color: var(--ulat-text);
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.15s ease;
        }
        .chat-chip:hover {
            background: var(--ulat-ink);
            color: #ffffff;
            border-color: var(--ulat-ink);
        }
    </style>
</head>
<body>

    <!-- TOPBAR MINIMALISTA -->
    <div class="ulat-topbar">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="d-flex align-items-center flex-wrap gap-3">
                <span>Cochabamba, Bolivia · C. Uruguay #E-836 &amp; Campus Norte Av. Uyuni #1889</span>
                <span class="d-none d-md-inline text-white-50">·</span>
                <span class="d-none d-md-inline">(4) 4509488 – 4221004</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="mailto:latinoamericana@ulat.edu.bo" class="d-none d-sm-inline">latinoamericana@ulat.edu.bo</a>
                <span class="d-none d-sm-inline text-white-50">·</span>
                <a href="https://wa.me/59179795778" target="_blank" rel="noopener noreferrer">WhatsApp Admisiones 79795778 ↗</a>
            </div>
        </div>
    </div>

    <!-- NAVBAR PRINCIPAL MINIMALISTA -->
    <nav class="navbar navbar-expand-lg navbar-dark ulat-navbar">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="../assets/ulat/logo.png" alt="Universidad Latinoamericana ULAT">
            </a>
            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarUlat" aria-controls="navbarUlat" aria-expanded="false" aria-label="Navegación">
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
                            <li><a class="dropdown-header-ulat" href="facultades.php#salud">01 · Ciencias de la Salud</a></li>
                            <?php foreach ($carrerasSalud as $s => $cs): ?>
                                <li><a class="dropdown-item" href="carrera.php?id=<?= $s ?>"><?= htmlspecialchars($cs['nombre']) ?></a></li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider my-1 opacity-10"></li>
                            <li><a class="dropdown-header-ulat" href="facultades.php#tecnologia">02 · Ciencia y Tecnología</a></li>
                            <?php foreach ($carrerasTecno as $s => $ct): ?>
                                <li><a class="dropdown-item" href="carrera.php?id=<?= $s ?>"><?= htmlspecialchars($ct['nombre']) ?></a></li>
                            <?php endforeach; ?>
                            <li><hr class="dropdown-divider my-1 opacity-10"></li>
                            <li><a class="dropdown-header-ulat" href="facultades.php#economicas">03 · Económicas y Jurídicas</a></li>
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
                    <li class="nav-item ms-lg-3 mt-2 mt-lg-0">
                        <a class="btn btn-ulat-gold btn-sm px-3 py-1.5" href="admisiones.php#preinscripcion">
                            Preinscripción ↗
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
