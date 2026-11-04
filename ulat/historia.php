<?php
declare(strict_types=1);
$pageTitle = 'Historia, Misión y Visión | Universidad Latinoamericana (ULAT)';
$activeNav = 'historia';
require_once __DIR__ . '/header.php';
?>

<div class="ulat-page-header">
    <div class="container">
        <div class="row align-items-end g-4">
            <div class="col-lg-8">
                <span class="ulat-eyebrow-light">Identidad Institucional · Fundada en 1995</span>
                <h1 class="font-editorial display-4 text-white mb-0">Historia, Misión y Visión</h1>
            </div>
            <div class="col-lg-4 text-lg-end">
                <p class="small text-white-50 mb-0">
                    R.M. Nº 241/02 · 590/07 · 597/07 · 157/07 · 0140/25
                </p>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row g-5">
        <div class="col-lg-8">
            <div class="mb-5 pb-4" style="border-bottom: 1px solid var(--ulat-line);">
                <span class="ulat-eyebrow">Origen Legal</span>
                <h2 class="font-editorial h2 mb-3">Tres décadas de educación superior en Bolivia</h2>
                <p class="text-secondary small" style="line-height: 1.8;">
                    La Universidad Latinoamericana (ULAT) fue fundada el <strong>27 de enero de 1995</strong> en la ciudad de Cochabamba al amparo del Artículo 188 de la Constitución Política del Estado. Es una institución de Educación Superior legalmente establecida y respaldada por las Resoluciones Ministeriales <strong>Nº 241/02, 590/07, 597/07, 157/07 y 0140/25</strong> y su Personería Jurídica <strong>191/97 – 214/97</strong>.
                </p>
                <p class="text-secondary small mb-0" style="line-height: 1.8;">
                    Todos los diplomas académicos otorgados por la ULAT facultan la emisión del Título en Provisión Nacional por el Ministerio de Educación del Estado Plurinacional de Bolivia.
                </p>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="semester-card">
                        <div class="semester-header"><span>01 · Misión Institucional</span></div>
                        <p class="small text-muted mb-0" style="line-height: 1.75;">
                            Formar profesionales idóneos, con sólida preparación científica, técnica y valores éticos, capaces de responder creativamente a los desafíos del desarrollo regional y nacional, contribuyendo a la transformación productiva con equidad y justicia social.
                        </p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="semester-card">
                        <div class="semester-header"><span>02 · Visión Institucional</span></div>
                        <p class="small text-muted mb-0" style="line-height: 1.75;">
                            Ser una institución de educación superior líder, reconocida por su excelencia académica, investigación aplicada, vinculación con el entorno socioeconómico y por la calidad humana de sus graduados a nivel nacional e internacional.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-ulat overflow-hidden">
                <img src="../assets/ulat/banner_2_2560x1707.jpg" alt="Graduación ULAT" class="w-100" style="height: 210px; object-fit: cover;">
                <div class="p-4">
                    <span class="ulat-eyebrow">Excelencia Académica</span>
                    <h4 class="h5 mb-2">Títulos con Validez Nacional</h4>
                    <p class="small text-muted mb-3">
                        Formación respaldada por convenios interinstitucionales, hospitales escuela y talleres propios.
                    </p>
                    <a href="carreras.php" class="btn btn-ulat-primary btn-sm w-100">Explorar las 15 Carreras →</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
