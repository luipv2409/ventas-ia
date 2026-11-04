<?php
declare(strict_types=1);
$pageTitle = 'Facultades y Áreas Académicas | Universidad Latinoamericana (ULAT)';
$activeNav = 'facultades';
require_once __DIR__ . '/header.php';
?>

<div class="ulat-page-header">
    <div class="container py-2">
        <span class="ulat-eyebrow-light">Organización Académica</span>
        <h1 class="display-6 fw-bold text-white mb-2">Facultades de la Universidad Latinoamericana</h1>
        <p class="mb-0 text-white-50" style="max-width: 680px; font-size: 0.98rem;">
            Tres facultades consolidadas que integran docencia especializada, infraestructura de laboratorio y vinculación práctica con el medio profesional.
        </p>
    </div>
</div>

<div class="container py-5">

    <!-- FACULTAD I: CIENCIAS DE LA SALUD -->
    <div class="card card-ulat overflow-hidden mb-5" id="salud">
        <div class="row g-0 align-items-stretch">
            <div class="col-lg-8 p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <span class="academic-index">FACULTAD I · CIENCIAS BIOMÉDICAS</span>
                    <span class="small fw-semibold text-muted">6 Programas</span>
                </div>
                <h2 class="h3 fw-bold mb-3">Facultad de Ciencias de la Salud</h2>
                <p class="text-secondary small mb-4" style="line-height: 1.7;">
                    La Facultad de Ciencias de la Salud forma profesionales con rigor clínico, sentido ético y vocación de servicio orientados a la preservación de la vida y la salud comunitaria. Cuenta con anfiteatro anatómico, laboratorios de histología, bioquímica, microbiología, clínicas odontológicas y convenios hospitalarios para el Internado Rotatorio.
                </p>
                <div class="row g-2">
                    <?php foreach ($carrerasSalud as $slug => $c): ?>
                        <div class="col-md-6">
                            <div class="p-3 rounded border d-flex justify-content-between align-items-center" style="background-color: #f8fafc;">
                                <div>
                                    <div class="fw-bold small text-dark"><?= htmlspecialchars($c['nombre']) ?></div>
                                    <div class="text-muted" style="font-size: 0.76rem;"><?= htmlspecialchars($c['duracion']) ?></div>
                                </div>
                                <a href="carrera.php?id=<?= $slug ?>" class="btn btn-ulat-outline btn-sm py-1 px-2" style="font-size: 0.76rem;">Malla</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-4 bg-dark">
                <img src="../assets/ulat/banner_10_768x768.jpg" alt="Facultad de Ciencias de la Salud ULAT" class="w-100 h-100" style="object-fit: cover; min-height: 300px;">
            </div>
        </div>
    </div>

    <!-- FACULTAD II: CIENCIA Y TECNOLOGÍA -->
    <div class="card card-ulat overflow-hidden mb-5" id="tecnologia">
        <div class="row g-0 align-items-stretch">
            <div class="col-lg-8 p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <span class="academic-index">FACULTAD II · INGENIERÍA E INNOVACIÓN</span>
                    <span class="small fw-semibold text-muted">5 Ingenierías</span>
                </div>
                <h2 class="h3 fw-bold mb-3">Facultad de Ciencia y Tecnología</h2>
                <p class="text-secondary small mb-4" style="line-height: 1.7;">
                    Impulsa la formación científica y tecnológica aplicada al desarrollo de software e inteligencia artificial, obras civiles con cinco menciones de especialidad, geodesia satelital y fotogrametría, autotrónica en nuestro Campus Norte (Av. Uyuni #1889) y transformación digital empresarial.
                </p>
                <div class="row g-2">
                    <?php foreach ($carrerasTecno as $slug => $c): ?>
                        <div class="col-md-6">
                            <div class="p-3 rounded border d-flex justify-content-between align-items-center" style="background-color: #f8fafc;">
                                <div>
                                    <div class="fw-bold small text-dark"><?= htmlspecialchars($c['nombre']) ?></div>
                                    <div class="text-muted" style="font-size: 0.76rem;"><?= htmlspecialchars($c['duracion']) ?></div>
                                </div>
                                <a href="carrera.php?id=<?= $slug ?>" class="btn btn-ulat-outline btn-sm py-1 px-2" style="font-size: 0.76rem;">Malla</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-4 bg-dark">
                <img src="../assets/ulat/banner_6_768x1155.jpg" alt="Facultad de Ciencia y Tecnología ULAT" class="w-100 h-100" style="object-fit: cover; min-height: 300px;">
            </div>
        </div>
    </div>

    <!-- FACULTAD III: CIENCIAS ECONÓMICAS, SOCIALES Y JURÍDICAS -->
    <div class="card card-ulat overflow-hidden mb-4" id="economicas">
        <div class="row g-0 align-items-stretch">
            <div class="col-lg-8 p-4 p-md-5">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                    <span class="academic-index">FACULTAD III · DERECHO, FINANZAS Y EDUCACIÓN</span>
                    <span class="small fw-semibold text-muted">4 Programas</span>
                </div>
                <h2 class="h3 fw-bold mb-3">Facultad de Ciencias Económicas, Sociales y Jurídicas</h2>
                <p class="text-secondary small mb-4" style="line-height: 1.7;">
                    Desarrolla capacidades de liderazgo jurídico, práctica forense en salas de juicio oral, auditoría financiera, tributaria y gubernamental, contaduría general y diseño curricular psicopedagógico con alto compromiso social.
                </p>
                <div class="row g-2">
                    <?php foreach ($carrerasEcono as $slug => $c): ?>
                        <div class="col-md-6">
                            <div class="p-3 rounded border d-flex justify-content-between align-items-center" style="background-color: #f8fafc;">
                                <div>
                                    <div class="fw-bold small text-dark"><?= htmlspecialchars($c['nombre']) ?></div>
                                    <div class="text-muted" style="font-size: 0.76rem;"><?= htmlspecialchars($c['duracion']) ?></div>
                                </div>
                                <a href="carrera.php?id=<?= $slug ?>" class="btn btn-ulat-outline btn-sm py-1 px-2" style="font-size: 0.76rem;">Malla</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-4 bg-dark">
                <img src="../assets/ulat/banner_12_768x768.jpg" alt="Facultad de Ciencias Económicas y Jurídicas ULAT" class="w-100 h-100" style="object-fit: cover; min-height: 300px;">
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
