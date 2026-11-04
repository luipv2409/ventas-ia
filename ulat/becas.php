<?php
declare(strict_types=1);
$pageTitle = 'Programa de Becas y Plan Uno a Uno | Universidad Latinoamericana (ULAT)';
$activeNav = 'becas';
require_once __DIR__ . '/header.php';
?>

<div class="ulat-page-header">
    <div class="container">
        <div class="row align-items-end g-4">
            <div class="col-lg-8">
                <span class="ulat-eyebrow-light">Apoyo Económico e Incentivo al Mérito</span>
                <h1 class="font-editorial display-4 text-white mb-0">Becas y Plan Uno a Uno</h1>
            </div>
            <div class="col-lg-4 text-lg-end">
                <p class="small text-white-50 mb-0">
                    Programa Solidario ULAT–ILAT &amp; Reglamento de Becas D.S. 1433
                </p>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">

    <!-- PLAN UNO A UNO (EDITORIAL ASIMÉTRICO) -->
    <div class="p-4 p-md-5 bg-white mb-5" id="plan1a1" style="border: 1px solid var(--ulat-line);">
        <div class="row align-items-center g-5">
            <div class="col-lg-4 text-lg-center" style="border-right: 1px solid var(--ulat-line);">
                <span class="ulat-eyebrow">Beneficio ULAT – ILAT</span>
                <div class="font-editorial display-1" style="color: var(--ulat-ink); line-height: 1;">-50%</div>
                <div class="font-mono-code text-muted mt-2">En tu Cuota Mensual</div>
            </div>
            <div class="col-lg-8">
                <h2 class="font-editorial h2 mb-3">Plan Uno a Uno: Estudia a mitad de cuota recomendando a un compañero</h2>
                <p class="small text-muted mb-3" style="line-height: 1.75;">
                    La Universidad Latinoamericana y el Instituto Latinoamericano (ILAT) diseñaron el <strong>Plan Uno a Uno</strong> para apoyar económicamente a nuestros estudiantes regulares.
                </p>
                <p class="small text-muted mb-0" style="line-height: 1.75;">
                    Todo estudiante regular que invite a un alumno nuevo a matricularse en cualquiera de nuestras carreras o programas ofertados (Diplomados, Licenciatura, Técnico Superior o Técnico Medio) cancelará únicamente el <strong>50% de la cuota correspondiente al mes</strong> por cada alumno nuevo matriculado.
                </p>
            </div>
        </div>
    </div>

    <!-- MODALIDADES DE BECA -->
    <div class="mb-4">
        <span class="ulat-eyebrow">Modalidades Reglamentarias</span>
        <h2 class="font-editorial h2">Programa Institucional de Becas</h2>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-4">
            <div class="card card-ulat h-100 p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom: 1px solid var(--ulat-line);">
                    <span class="academic-index">BECA 01</span>
                    <span class="academic-index">COBERTURA 100%</span>
                </div>
                <h4 class="h5 mb-2">Excelencia Académica</h4>
                <p class="small text-muted mb-3" style="line-height: 1.7;">
                    El estudiante que alcance un promedio general superior al <strong>95%</strong> en el semestre anterior sin materias reprobadas queda liberado del 100% del pago de matrícula y cuotas.
                </p>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-ulat h-100 p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom: 1px solid var(--ulat-line);">
                    <span class="academic-index">BECA 02</span>
                    <span class="academic-index">SELECCIÓN ULAT</span>
                </div>
                <h4 class="h5 mb-2">Beca Deportiva y Cultural</h4>
                <p class="small text-muted mb-3" style="line-height: 1.7;">
                    Otorga descuentos en las cuotas mensuales a estudiantes con destacadas cualidades deportivas o artísticas que representen oficialmente a la Universidad Latinoamericana.
                </p>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card card-ulat h-100 p-4">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom: 1px solid var(--ulat-line);">
                    <span class="academic-index">BECA 03</span>
                    <span class="academic-index">INTERINSTITUCIONAL</span>
                </div>
                <h4 class="h5 mb-2">Convenio, Familiar y Trabajo</h4>
                <p class="small text-muted mb-3" style="line-height: 1.7;">
                    Descuentos para personal de instituciones en convenio, familiares en primer grado de consanguinidad (Beca Familiar) y apoyo de medio tiempo dentro de la universidad (Beca Trabajo).
                </p>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 pt-4" style="border-top: 1px solid var(--ulat-line);">
        <div class="small text-muted">
            Las escalas de porcentaje y requisitos de renovación están normados en el Reglamento Oficial de Becas.
        </div>
        <a href="reglamentos.php" class="btn btn-ulat-outline btn-sm">
            Descargar Reglamento de Becas PDF →
        </a>
    </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
