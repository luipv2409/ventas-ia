<?php
declare(strict_types=1);
$pageTitle = 'Facultades y Áreas Académicas | Universidad Latinoamericana (ULAT)';
$activeNav = 'facultades';
require_once __DIR__ . '/header.php';
?>

<div class="ulat-page-header">
    <div class="container">
        <div class="row align-items-end g-4">
            <div class="col-lg-8">
                <span class="ulat-eyebrow-light">Estructura Universitaria</span>
                <h1 class="font-editorial display-4 text-white mb-0">Nuestras Tres Facultades</h1>
            </div>
            <div class="col-lg-4 text-lg-end">
                <p class="small text-white-50 mb-0">
                    Docencia especializada, laboratorios propios y práctica profesional
                </p>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">

    <!-- 01 SALUD -->
    <div class="py-5" id="salud" style="border-bottom: 1px solid var(--ulat-line);">
        <div class="row g-5 align-items-start">
            <div class="col-lg-5">
                <span class="ulat-eyebrow">Facultad 01 · 06 Carreras</span>
                <h2 class="font-editorial display-6 mb-3">Ciencias de la Salud</h2>
                <p class="small text-muted mb-4" style="line-height: 1.75;">
                    Forma profesionales con rigor clínico, sentido ético y vocación de servicio orientados a la preservación de la vida y la salud comunitaria. Cuenta con anfiteatro anatómico, laboratorios de histología, bioquímica, microbiología, clínicas odontológicas y convenios hospitalarios para el Internado Rotatorio.
                </p>
                <img src="../assets/ulat/banner_10_768x768.jpg" alt="Ciencias de la Salud ULAT" class="img-fluid rounded-1" style="max-height: 280px; width: 100%; object-fit: cover;">
            </div>
            <div class="col-lg-7">
                <div class="bg-white p-4 p-md-5" style="border: 1px solid var(--ulat-line);">
                    <div class="font-mono-code text-muted mb-3">Programas Académicos de Salud</div>
                    <?php foreach ($carrerasSalud as $slug => $c): ?>
                        <a href="carrera.php?id=<?= $slug ?>" class="career-link-row py-3">
                            <div>
                                <div class="fw-medium text-dark"><?= htmlspecialchars($c['nombre']) ?></div>
                                <div class="font-mono-code text-muted" style="font-size: 0.67rem;"><?= htmlspecialchars($c['duracion']) ?> · <?= htmlspecialchars($c['grado']) ?></div>
                            </div>
                            <span class="academic-index">Ver Malla →</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 02 TECNOLOGÍA -->
    <div class="py-5" id="tecnologia" style="border-bottom: 1px solid var(--ulat-line);">
        <div class="row g-5 align-items-start">
            <div class="col-lg-5">
                <span class="ulat-eyebrow">Facultad 02 · 05 Ingenierías</span>
                <h2 class="font-editorial display-6 mb-3">Ciencia y Tecnología</h2>
                <p class="small text-muted mb-4" style="line-height: 1.75;">
                    Impulsa la formación científica y tecnológica aplicada al desarrollo de software e inteligencia artificial, obras civiles con cinco menciones de especialidad, geodesia satelital, autotrónica en nuestro Campus Norte (Av. Uyuni #1889) y transformación digital empresarial.
                </p>
                <img src="../assets/ulat/banner_6_768x1155.jpg" alt="Ciencia y Tecnología ULAT" class="img-fluid rounded-1" style="max-height: 280px; width: 100%; object-fit: cover; object-position: center 25%;">
            </div>
            <div class="col-lg-7">
                <div class="bg-white p-4 p-md-5" style="border: 1px solid var(--ulat-line);">
                    <div class="font-mono-code text-muted mb-3">Programas de Ingeniería</div>
                    <?php foreach ($carrerasTecno as $slug => $c): ?>
                        <a href="carrera.php?id=<?= $slug ?>" class="career-link-row py-3">
                            <div>
                                <div class="fw-medium text-dark"><?= htmlspecialchars($c['nombre']) ?></div>
                                <div class="font-mono-code text-muted" style="font-size: 0.67rem;"><?= htmlspecialchars($c['duracion']) ?> · <?= htmlspecialchars($c['sede'] ?? 'Campus Central') ?></div>
                            </div>
                            <span class="academic-index">Ver Malla →</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- 03 ECONÓMICAS Y JURÍDICAS -->
    <div class="py-5" id="economicas">
        <div class="row g-5 align-items-start">
            <div class="col-lg-5">
                <span class="ulat-eyebrow">Facultad 03 · 04 Programas</span>
                <h2 class="font-editorial display-6 mb-3">Ciencias Económicas y Jurídicas</h2>
                <p class="small text-muted mb-4" style="line-height: 1.75;">
                    Desarrolla capacidades de liderazgo jurídico, práctica forense en salas de juicio oral, auditoría financiera, tributaria y gubernamental, contaduría general y diseño curricular psicopedagógico con alto compromiso social.
                </p>
                <img src="../assets/ulat/banner_12_768x768.jpg" alt="Ciencias Económicas y Jurídicas ULAT" class="img-fluid rounded-1" style="max-height: 280px; width: 100%; object-fit: cover;">
            </div>
            <div class="col-lg-7">
                <div class="bg-white p-4 p-md-5" style="border: 1px solid var(--ulat-line);">
                    <div class="font-mono-code text-muted mb-3">Programas Jurídicos, Financieros y Educativos</div>
                    <?php foreach ($carrerasEcono as $slug => $c): ?>
                        <a href="carrera.php?id=<?= $slug ?>" class="career-link-row py-3">
                            <div>
                                <div class="fw-medium text-dark"><?= htmlspecialchars($c['nombre']) ?></div>
                                <div class="font-mono-code text-muted" style="font-size: 0.67rem;"><?= htmlspecialchars($c['duracion']) ?> · <?= htmlspecialchars($c['grado']) ?></div>
                            </div>
                            <span class="academic-index">Ver Malla →</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>
