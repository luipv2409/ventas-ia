<?php
declare(strict_types=1);
$pageTitle = 'Programa de Becas y Plan Uno a Uno | Universidad Latinoamericana (ULAT)';
$activeNav = 'becas';
require_once __DIR__ . '/header.php';
?>

<div class="bg-primary text-white py-5" style="background: linear-gradient(135deg, var(--ulat-blue-dark) 0%, var(--ulat-blue-primary) 100%);">
    <div class="container text-center py-3">
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-2">Apoyo Estudiantil</span>
        <h1 class="display-5 fw-bold text-white mb-2">Programa de Becas y Beneficios ULAT</h1>
        <p class="lead text-light text-opacity-75 max-w-700 mx-auto">
            Garantizamos oportunidades de acceso a la educación superior mediante incentivos académicos, deportivos e institucionales.
        </p>
    </div>
</div>

<div class="container py-5">

    <!-- PLAN UNO A UNO -->
    <div class="card card-ulat p-4 p-md-5 mb-5 border-start border-5 border-success" id="plan1a1">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <span class="badge bg-success mb-2 px-3 py-2">Beneficio Solidario ULAT - ILAT</span>
                <h2 class="h3 fw-bold text-dark mb-3">Plan Uno a Uno: 50% de Descuento en Cuotas</h2>
                <p class="text-secondary lh-lg mb-3">
                    La Universidad Latinoamericana y el Instituto Latinoamericano han diseñado el <strong>Plan Uno a Uno</strong> para apoyar económicamente a nuestros estudiantes regulares.
                </p>
                <div class="p-3 bg-light rounded-3 border mb-3">
                    <h6 class="fw-bold text-primary mb-1"><i class="fa-solid fa-users me-2"></i> ¿Cómo funciona?</h6>
                    <p class="small text-muted mb-0">
                        El estudiante regular debe invitar a un alumno nuevo a matricularse en cualquiera de nuestras carreras o programas ofertados (Diplomados, Licenciaturas, Técnico Superior o Técnico Medio). El estudiante regular cancelará únicamente el <strong>50% de la cuota del mes</strong> por cada alumno nuevo matriculado.
                    </p>
                </div>
                <p class="small text-muted mb-0">
                    <i class="fa-solid fa-file-pdf text-danger me-1"></i> El formulario del Plan Uno a Uno puede tramitarse directamente en Dirección Administrativa o consultando en nuestro chat de admisiones.
                </p>
            </div>
            <div class="col-lg-4 text-center">
                <div class="p-4 bg-success bg-opacity-10 rounded-4">
                    <div class="display-4 fw-bold text-success mb-1">-50%</div>
                    <div class="fw-bold text-dark">En tu Cuota Mensual</div>
                    <small class="text-muted d-block mt-2">Por cada alumno nuevo recomendado que se matricule</small>
                </div>
            </div>
        </div>
    </div>

    <!-- PROGRAMA DE BECAS REGULARES -->
    <div class="text-center mb-5">
        <h3 class="display-6 fw-bold">Modalidades de Beca</h3>
        <p class="text-muted max-w-700 mx-auto">
            Regidas bajo el Reglamento General de Universidades Privadas (D.S. Nº 1433) y el Reglamento Interno de Becas de la ULAT.
        </p>
    </div>

    <div class="row g-4">
        <!-- Beca Excelencia -->
        <div class="col-lg-4 col-md-6">
            <div class="card card-ulat h-100 p-4 border-top border-4 border-warning">
                <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 fs-3 d-inline-block mb-3">
                    <i class="fa-solid fa-award"></i>
                </div>
                <h4 class="h5 fw-bold text-dark mb-2">Beca por Excelencia Académica</h4>
                <p class="small text-muted mb-3">
                    Dirigida a estudiantes destacados que demuestren un alto rendimiento continuo en sus semestres universitarios.
                </p>
                <div class="p-3 bg-light rounded small mb-3">
                    <strong>Requisito:</strong> Promedio general superior al <strong>95%</strong> tras el primer semestre de estudio.
                </div>
                <p class="small text-secondary mb-0">
                    Libera al estudiante del <strong>100% del pago de matrícula y cuotas</strong>, debiendo mantener el promedio para conservar el beneficio semestral.
                </p>
            </div>
        </div>

        <!-- Beca Deportiva -->
        <div class="col-lg-4 col-md-6">
            <div class="card card-ulat h-100 p-4 border-top border-4 border-primary">
                <div class="p-3 bg-primary bg-opacity-10 text-primary rounded-3 fs-3 d-inline-block mb-3">
                    <i class="fa-solid fa-futbol"></i>
                </div>
                <h4 class="h5 fw-bold text-dark mb-2">Beca Deportiva</h4>
                <p class="small text-muted mb-3">
                    Para jóvenes talentos que destaquen en disciplinas deportivas y representen a la universidad en torneos oficiales.
                </p>
                <div class="p-3 bg-light rounded small mb-3">
                    <strong>Requisito:</strong> Evaluación técnica y compromiso de participación en selecciones de la ULAT.
                </div>
                <p class="small text-secondary mb-0">
                    Otorga descuentos significativos en las cuotas mensuales de acuerdo con la evaluación y rendimiento del estudiante atleta.
                </p>
            </div>
        </div>

        <!-- Beca Convenio -->
        <div class="col-lg-4 col-md-6">
            <div class="card card-ulat h-100 p-4 border-top border-4 border-info">
                <div class="p-3 bg-info bg-opacity-10 text-info rounded-3 fs-3 d-inline-block mb-3">
                    <i class="fa-solid fa-handshake"></i>
                </div>
                <h4 class="h5 fw-bold text-dark mb-2">Beca Convenio Institucional</h4>
                <p class="small text-muted mb-3">
                    Descuentos preferenciales para funcionarios y familiares de instituciones públicas y privadas con convenio vigente.
                </p>
                <div class="p-3 bg-light rounded small mb-3">
                    <strong>Requisito:</strong> Acreditación laboral o certificación institucional de la entidad en convenio.
                </div>
                <p class="small text-secondary mb-0">
                    Los porcentajes de rebaja y condiciones específicas se encuentran estipulados en el reglamento de becas interinstitucionales.
                </p>
            </div>
        </div>
    </div>

    <!-- Banner de descarga de reglamento -->
    <div class="card card-ulat bg-primary text-white p-4 p-md-5 mt-5">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h4 class="fw-bold mb-2 text-white">¿Deseas leer el Reglamento de Becas completo?</h4>
                <p class="text-light text-opacity-75 mb-0">
                    Consulta los artículos, escalas de puntuación y causales de mantenimiento de becas en el documento oficial normativo de la ULAT.
                </p>
            </div>
            <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                <a href="reglamentos.php" class="btn btn-ulat-gold">
                    <i class="fa-solid fa-file-pdf me-1"></i> Ver Reglamentos en PDF
                </a>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>

