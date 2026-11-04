<?php
declare(strict_types=1);
$pageTitle = 'Programa de Becas y Plan Uno a Uno | Universidad Latinoamericana (ULAT)';
$activeNav = 'becas';
require_once __DIR__ . '/header.php';
?>

<div class="ulat-page-header">
    <div class="container py-2">
        <span class="ulat-eyebrow-light">Incentivos Académicos y Apoyo Económico</span>
        <h1 class="display-6 fw-bold text-white mb-2">Programa de Becas y Plan Uno a Uno</h1>
        <p class="mb-0 text-white-50" style="max-width: 680px; font-size: 0.98rem;">
            Beneficios estudiantiles regidos por el Reglamento General de Universidades Privadas (D.S. Nº 1433) y el programa solidario ULAT – ILAT.
        </p>
    </div>
</div>

<div class="container py-5">

    <!-- PLAN UNO A UNO -->
    <div class="card card-ulat p-4 p-md-5 mb-5" id="plan1a1">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="ulat-eyebrow">Beneficio Solidario ULAT – ILAT</span>
                <h2 class="h3 fw-bold mb-3">Plan Uno a Uno: 50% de Descuento en Cuotas Mensuales</h2>
                <p class="text-secondary small mb-3" style="line-height: 1.7;">
                    La Universidad Latinoamericana y el Instituto Latinoamericano (ILAT) establecieron el <strong>Plan Uno a Uno</strong> con el propósito de respaldar la economía familiar de nuestros estudiantes regulares.
                </p>
                <div class="p-3 rounded border mb-3" style="background-color: #f8fafc;">
                    <h6 class="fw-bold text-dark mb-1">¿Cómo se aplica el beneficio?</h6>
                    <p class="small text-secondary mb-0" style="line-height: 1.6;">
                        Todo estudiante regular que invite a un alumno nuevo a matricularse en cualquiera de nuestras carreras o programas ofertados (Licenciaturas, Técnico Superior o Diplomados) cancela únicamente el <strong>50% de su cuota mensual</strong> por cada estudiante nuevo inscrito.
                    </p>
                </div>
                <div class="small text-muted">
                    El formulario oficial del Plan Uno a Uno se solicita en Dirección Administrativa o a través de Admisiones.
                </div>
            </div>
            <div class="col-lg-4">
                <div class="p-4 rounded text-center" style="background-color: var(--ulat-gold-soft); border: 1px solid rgba(212, 154, 53, 0.4);">
                    <span class="ulat-eyebrow mb-1">Descuento Directo</span>
                    <div class="display-4 fw-bold mb-1" style="color: var(--ulat-blue-dark);">-50%</div>
                    <div class="fw-bold text-dark small">En tu Cuota Mensual</div>
                    <p class="small text-muted mt-2 mb-0">Por cada estudiante nuevo recomendado y matriculado en la gestión vigente.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALIDADES DE BECA -->
    <div class="mb-4">
        <span class="ulat-eyebrow">Normativa Ministerial D.S. 1433</span>
        <h3 class="h4 fw-bold">Modalidades de Beca Institucional</h3>
    </div>

    <div class="row g-4">
        <!-- Beca Excelencia -->
        <div class="col-lg-4 col-md-6">
            <div class="card card-ulat h-100 p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <span class="academic-index">BECA 01</span>
                    <div class="icon-frame-gold">
                        <i data-lucide="award" class="lucide"></i>
                    </div>
                </div>
                <h4 class="h5 fw-bold mb-2">Beca por Excelencia Académica</h4>
                <p class="small text-secondary mb-3 flex-grow-1" style="line-height: 1.65;">
                    Reconoce el mérito de los estudiantes regulares que alcanzan el más alto rendimiento académico durante el semestre anterior sin asignaturas reprobadas.
                </p>
                <div class="p-3 rounded small" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                    <div><strong>Requisito:</strong> Promedio igual o superior a <strong>95 puntos</strong>.</div>
                    <div class="mt-1"><strong>Cobertura:</strong> Liberación del <strong>100%</strong> en cuotas.</div>
                </div>
            </div>
        </div>

        <!-- Beca Deportiva y Cultural -->
        <div class="col-lg-4 col-md-6">
            <div class="card card-ulat h-100 p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <span class="academic-index">BECA 02</span>
                    <div class="icon-frame">
                        <i data-lucide="trophy" class="lucide"></i>
                    </div>
                </div>
                <h4 class="h5 fw-bold mb-2">Beca Cultura y Deporte</h4>
                <p class="small text-secondary mb-3 flex-grow-1" style="line-height: 1.65;">
                    Destinada a estudiantes destacados que integran las selecciones deportivas o elencos culturales oficiales de la Universidad Latinoamericana en certámenes departamentales y nacionales.
                </p>
                <div class="p-3 rounded small" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                    <div><strong>Requisito:</strong> Evaluación técnica y récord académico regular.</div>
                    <div class="mt-1"><strong>Beneficio:</strong> Descuento porcentual semestral.</div>
                </div>
            </div>
        </div>

        <!-- Beca Convenio e Interinstitucional -->
        <div class="col-lg-4 col-md-6">
            <div class="card card-ulat h-100 p-4 d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <span class="academic-index">BECA 03</span>
                    <div class="icon-frame">
                        <i data-lucide="building" class="lucide"></i>
                    </div>
                </div>
                <h4 class="h5 fw-bold mb-2">Beca Convenio Institucional</h4>
                <p class="small text-secondary mb-3 flex-grow-1" style="line-height: 1.65;">
                    Aranceles preferenciales para miembros, trabajadores y familiares directos de instituciones públicas, colegios profesionales y empresas con convenio vigente con la ULAT.
                </p>
                <div class="p-3 rounded small" style="background-color: #f8fafc; border: 1px solid #e2e8f0;">
                    <div><strong>Requisito:</strong> Certificación vigente de la entidad en convenio.</div>
                    <div class="mt-1"><strong>Normativa:</strong> Reglamento Interno de Becas.</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Descarga de Reglamento -->
    <div class="card card-ulat p-4 p-md-5 mt-5">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="ulat-eyebrow">Transparencia Normativa</span>
                <h4 class="fw-bold mb-2">Consulta el Reglamento Oficial de Becas</h4>
                <p class="text-secondary small mb-0">
                    Revisa en detalle los plazos de convocatoria, requisitos documentales y condiciones de permanencia de cada modalidad de beca.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="reglamentos.php" class="btn btn-ulat-primary d-inline-flex align-items-center gap-2">
                    <i data-lucide="file-text" class="lucide-sm"></i>
                    <span>Ver Reglamentos en PDF</span>
                </a>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
