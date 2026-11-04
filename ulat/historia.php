<?php
declare(strict_types=1);
$pageTitle = 'Historia, Misión y Visión | Universidad Latinoamericana (ULAT)';
$activeNav = 'historia';
require_once __DIR__ . '/header.php';
?>

<div class="ulat-page-header">
    <div class="container py-2">
        <span class="ulat-eyebrow-light">Identidad y Trayectoria</span>
        <h1 class="display-6 fw-bold text-white mb-2">Historia, Misión y Visión Institucional</h1>
        <p class="mb-0 text-white-50" style="max-width: 680px; font-size: 0.98rem;">
            Más de tres décadas formando profesionales íntegros al servicio del desarrollo científico, tecnológico y social de Bolivia.
        </p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-8">
            <!-- Historia -->
            <div class="card card-ulat p-4 p-md-5 mb-4">
                <span class="ulat-eyebrow mb-1">Reseña Histórica</span>
                <h3 class="h4 fw-bold mb-3 pb-3 border-bottom">Origen y Fundación Legal</h3>
                <p class="text-secondary small" style="line-height: 1.75;">
                    La Universidad Latinoamericana (ULAT) es una institución privada de Educación Superior cuya misión primordial es la formación académica e investigativa en las diversas ramas del saber científico, tecnológico y humanístico.
                </p>
                <p class="text-secondary small mb-4" style="line-height: 1.75;">
                    Fue fundada el <strong>27 de enero de 1995</strong> al amparo del Artículo 188 de la Constitución Política del Estado. Su funcionamiento institucional y académico está plenamente respaldado por las Resoluciones Ministeriales <strong>Nº 241/02, 590/07, 597/07, 157/07 y 0140/25</strong>, contando con Personería Jurídica reconocida mediante resoluciones <strong>191/97 y 214/97</strong>.
                </p>
                <div class="p-3 rounded small" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                    <strong>Títulos en Provisión Nacional:</strong> Todos los diplomas académicos emitidos por la ULAT habilitan la tramitación del Título en Provisión Nacional ante el Ministerio de Educación del Estado Plurinacional de Bolivia.
                </div>
            </div>

            <!-- Misión y Visión -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card card-ulat p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                            <span class="academic-index">MISIÓN</span>
                            <i data-lucide="compass" class="lucide text-secondary"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-2">Misión Institucional</h4>
                        <p class="text-secondary small mb-0" style="line-height: 1.7;">
                            Formar profesionales idóneos, con sólida preparación científica, técnica y valores éticos, capaces de responder creativamente a los desafíos del desarrollo regional y nacional, contribuyendo a la transformación productiva con equidad y justicia social.
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card card-ulat p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                            <span class="academic-index">VISIÓN</span>
                            <i data-lucide="eye" class="lucide text-secondary"></i>
                        </div>
                        <h4 class="h5 fw-bold mb-2">Visión Institucional</h4>
                        <p class="text-secondary small mb-0" style="line-height: 1.7;">
                            Ser una institución de educación superior líder, reconocida por su excelencia académica, investigación aplicada, vinculación con el entorno socioeconómico y por la calidad humana de sus graduados a nivel nacional e internacional.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Principios y Valores -->
            <div class="card card-ulat p-4 p-md-5">
                <span class="ulat-eyebrow mb-1">Pilares Éticos</span>
                <h4 class="h5 fw-bold mb-3 pb-2 border-bottom">Valores Universitarios</h4>
                <div class="row g-3 small text-secondary">
                    <div class="col-md-6">
                        <div class="p-3 rounded border h-100" style="background-color: #f8fafc;">
                            <div class="fw-bold text-dark mb-1">Excelencia Académica y Científica</div>
                            <div>Compromiso permanente con la actualización curricular y la práctica en laboratorio.</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 rounded border h-100" style="background-color: #f8fafc;">
                            <div class="fw-bold text-dark mb-1">Ética y Responsabilidad Social</div>
                            <div>Formación de ciudadanos íntegros al servicio de la comunidad boliviana.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <div class="sticky-top" style="top: 90px;">
                <div class="card card-ulat overflow-hidden mb-4">
                    <img src="../assets/ulat/banner_2_2560x1707.jpg" alt="Graduación ULAT" class="w-100" style="height: 200px; object-fit: cover;">
                    <div class="p-4">
                        <span class="ulat-eyebrow mb-1">Desde 1995</span>
                        <h5 class="fw-bold mb-2">Trayectoria Acreditada</h5>
                        <p class="small text-secondary mb-3">
                            Más de 30 años formando médicos, ingenieros, abogados, auditores y educadores en Cochabamba.
                        </p>
                        <button type="button" class="btn btn-ulat-primary btn-sm w-100" onclick="window.preguntarAlChat('Cuéntame sobre la historia, sedes y resoluciones ministeriales de la ULAT')">
                            Consultar a Guía ULAT
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
