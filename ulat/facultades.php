<?php
declare(strict_types=1);
$pageTitle = 'Facultades y Áreas del Conocimiento | Universidad Latinoamericana (ULAT)';
$activeNav = 'facultades';
require_once __DIR__ . '/header.php';
?>

<div class="bg-primary text-white py-5" style="background: linear-gradient(135deg, var(--ulat-blue-dark) 0%, var(--ulat-blue-primary) 100%);">
    <div class="container text-center py-3">
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-2">Comunidad Académica</span>
        <h1 class="display-5 fw-bold text-white mb-2">Nuestras 3 Facultades</h1>
        <p class="lead text-light text-opacity-75 max-w-700 mx-auto">
            Formación integral, científica y humanística orientada a responder a las demandas del desarrollo nacional y global.
        </p>
    </div>
</div>

<div class="container py-5">

    <!-- FACULTAD 1: CIENCIAS DE LA SALUD -->
    <div class="card card-ulat p-4 p-md-5 mb-5 border-start border-5 border-danger" id="salud">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="p-3 bg-danger bg-opacity-10 text-danger rounded-3 fs-3">
                        <i class="fa-solid fa-heart-pulse"></i>
                    </div>
                    <div>
                        <h2 class="h3 fw-bold text-dark mb-0">Facultad de Ciencias de la Salud</h2>
                        <span class="badge bg-danger">6 Carreras Ofertadas</span>
                    </div>
                </div>
                <p class="text-secondary lh-lg mb-4">
                    La Facultad de Ciencias de la Salud de la ULAT tiene como misión formar profesionales idóneos, éticos y comprometidos con la preservación de la vida y el bienestar humano. Nuestros estudiantes acceden a laboratorios biomédicos modernos, salas de simulación y convenios clínicos para prácticas tempranas en hospitales del departamento.
                </p>
                <div class="row g-2">
                    <?php foreach ($carrerasSalud as $slug => $c): ?>
                        <div class="col-md-6">
                            <div class="p-2 bg-light rounded border d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold small text-dark"><?= htmlspecialchars($c['nombre']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($c['duracion']) ?></small>
                                </div>
                                <a href="carrera.php?id=<?= $slug ?>" class="btn btn-sm btn-outline-danger rounded-pill">Ver Malla</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="p-4 bg-danger bg-opacity-10 rounded-4">
                    <i class="fa-solid fa-stethoscope text-danger" style="font-size: 5rem;"></i>
                    <h5 class="fw-bold text-danger mt-3">Prácticas Clínicas</h5>
                    <p class="small text-muted mb-0">Formación intensiva en centros de salud y hospitales desde los primeros semestres.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- FACULTAD 2: CIENCIA Y TECNOLOGÍA -->
    <div class="card card-ulat p-4 p-md-5 mb-5 border-start border-5 border-info" id="tecnologia">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="p-3 bg-info bg-opacity-10 text-info rounded-3 fs-3">
                        <i class="fa-solid fa-microchip"></i>
                    </div>
                    <div>
                        <h2 class="h3 fw-bold text-dark mb-0">Facultad de Ciencia y Tecnología</h2>
                        <span class="badge bg-info text-dark">5 Carreras Ofertadas</span>
                    </div>
                </div>
                <p class="text-secondary lh-lg mb-4">
                    Tiene por objetivo primario cumplir con las finalidades esenciales de la Universidad Latinoamericana: apoyar la formación científica y tecnológica de vanguardia. Preparamos ingenieros capacitados para diseñar infraestructura civil, desarrollar software robusto, implementar tecnologías automotrices e innovar en diseño digital y marketing.
                </p>
                <div class="row g-2">
                    <?php foreach ($carrerasTecno as $slug => $c): ?>
                        <div class="col-md-6">
                            <div class="p-2 bg-light rounded border d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold small text-dark"><?= htmlspecialchars($c['nombre']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($c['duracion']) ?></small>
                                </div>
                                <a href="carrera.php?id=<?= $slug ?>" class="btn btn-sm btn-outline-info rounded-pill text-dark">Ver Malla</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="p-4 bg-info bg-opacity-10 rounded-4">
                    <i class="fa-solid fa-laptop-code text-info" style="font-size: 5rem;"></i>
                    <h5 class="fw-bold text-info mt-3">Tecnología de Punta</h5>
                    <p class="small text-muted mb-0">Talleres automotrices, estaciones topográficas y laboratorios de computación de alta potencia.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- FACULTAD 3: CIENCIAS ECONÓMICAS, SOCIALES Y JURÍDICAS -->
    <div class="card card-ulat p-4 p-md-5 mb-5 border-start border-5 border-warning" id="economicas">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="p-3 bg-warning bg-opacity-10 text-warning rounded-3 fs-3">
                        <i class="fa-solid fa-scale-balanced"></i>
                    </div>
                    <div>
                        <h2 class="h3 fw-bold text-dark mb-0">Facultad de Ciencias Económicas, Sociales y Jurídicas</h2>
                        <span class="badge bg-warning text-dark">4 Carreras Ofertadas</span>
                    </div>
                </div>
                <p class="text-secondary lh-lg mb-4">
                    Tiene por tarea la formación integral a través del servicio, la ética y el diálogo continuo. Desarrolla en los estudiantes la capacidad de innovación, autodeterminación, gestión, investigación y compromiso social asumiendo una actitud reflexiva y proactiva para transformar positivamente su entorno.
                </p>
                <div class="row g-2">
                    <?php foreach ($carrerasEcono as $slug => $c): ?>
                        <div class="col-md-6">
                            <div class="p-2 bg-light rounded border d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold small text-dark"><?= htmlspecialchars($c['nombre']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($c['duracion']) ?></small>
                                </div>
                                <a href="carrera.php?id=<?= $slug ?>" class="btn btn-sm btn-outline-warning rounded-pill text-dark">Ver Malla</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-4 text-center">
                <div class="p-4 bg-warning bg-opacity-10 rounded-4">
                    <i class="fa-solid fa-gavel text-warning" style="font-size: 5rem;"></i>
                    <h5 class="fw-bold text-warning mt-3">Liderazgo Ético</h5>
                    <p class="small text-muted mb-0">Salas de juicio oral simuladas, gabinetes contables y consultorio jurídico universitario.</p>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
