<?php
// ulat/footer.php
?>
    <!-- FOOTER INSTITUCIONAL -->
    <footer class="ulat-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <img src="../assets/ulat/logo.png" alt="ULAT" class="mb-3" style="max-height: 48px; filter: brightness(0) invert(1);">
                    <p class="text-light text-opacity-75 small">
                        La Universidad Latinoamericana "ULAT" es una Institución privada de Educación Superior legalmente establecida, cuya misión fundamental es la formación profesional integral en ciencia, tecnología y humanismo.
                    </p>
                    <div class="text-warning small mb-2">
                        <i class="fa-solid fa-award me-1"></i> R.M. Nº 241/02 – 597/07 – 157/07
                    </div>
                    <div class="text-light text-opacity-50 small">
                        Personería Jurídica 191/97 – 214/97
                    </div>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h5>Navegación</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="facultades.php">Facultades</a></li>
                        <li><a href="carreras.php">Oferta Académica</a></li>
                        <li><a href="admisiones.php">Requisitos de Admisión</a></li>
                        <li><a href="becas.php">Programa de Becas</a></li>
                        <li><a href="reglamentos.php">Reglamentos Oficiales</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h5>Facultades</h5>
                    <ul class="list-unstyled">
                        <li><a href="facultades.php#salud">Ciencias de la Salud</a></li>
                        <li><a href="facultades.php#tecnologia">Ciencia y Tecnología</a></li>
                        <li><a href="facultades.php#economicas">Ciencias Económicas, Sociales y Jurídicas</a></li>
                        <li><a href="becas.php#plan1a1">Plan Uno a Uno (50% Dcto)</a></li>
                        <li><a href="historia.php">Historia y Valores</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h5>Contacto Directo</h5>
                    <ul class="list-unstyled text-light text-opacity-75 small">
                        <li class="mb-2">
                            <i class="fa-solid fa-location-dot text-warning me-2"></i>
                            Calle Uruguay #E-836 entre 16 de Julio y Oquendo, Cochabamba, Bolivia.
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-phone text-warning me-2"></i>
                            (4) 4509488 - 4221004
                        </li>
                        <li class="mb-2">
                            <i class="fa-brands fa-whatsapp text-success me-2"></i>
                            +591 4509488
                        </li>
                        <li class="mb-2">
                            <i class="fa-solid fa-envelope text-warning me-2"></i>
                            latinoamericana@ulat.edu.bo
                        </li>
                    </ul>
                    <a href="https://wa.me/5914509488" target="_blank" class="btn btn-outline-warning btn-sm w-100 rounded-pill mt-2">
                        <i class="fa-brands fa-whatsapp me-1"></i> Hablar con un Asesor
                    </a>
                </div>
            </div>

            <hr class="border-secondary mt-5 mb-4">

            <div class="d-flex flex-wrap justify-content-between align-items-center text-light text-opacity-50 small">
                <div>
                    &copy; <?= date('Y') ?> Universidad Latinoamericana (ULAT). Todos los derechos reservados.
                </div>
                <div class="d-flex gap-3 mt-2 mt-md-0">
                    <a href="reglamentos.php" class="text-light text-opacity-50">Reglamento Estudiantil</a>
                    <a href="reglamentos.php" class="text-light text-opacity-50">Reglamento de Becas</a>
                    <a href="reglamentos.php" class="text-light text-opacity-50">Graduación</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Widget de Chat Flotante Guía ULAT -->
    <?php require_once __DIR__ . '/chat_widget_ulat.php'; ?>

    <!-- Scripts Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

