<?php
// ulat/footer.php
?>
    <!-- FOOTER EDITORIAL MINIMALISTA -->
    <footer class="ulat-footer">
        <div class="container">
            <div class="row g-5">
                <div class="col-lg-4 col-md-6">
                    <img src="../assets/ulat/logo.png" alt="Universidad Latinoamericana ULAT" class="mb-4" style="max-height: 40px;">
                    <p class="small mb-3" style="line-height: 1.7;">
                        Institución privada de Educación Superior fundada el 27 de enero de 1995 en Cochabamba, Bolivia. Formación profesional en ciencia, tecnología y humanismo.
                    </p>
                    <div class="font-mono-code" style="font-size: 0.66rem; color: rgba(255,255,255,0.4);">
                        R.M. Nº 241/02 · 590/07 · 597/07 · 157/07 · 0140/25
                    </div>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h5>Navegación</h5>
                    <ul class="list-unstyled mb-0">
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="facultades.php">Facultades</a></li>
                        <li><a href="carreras.php">Carreras (15)</a></li>
                        <li><a href="admisiones.php">Admisiones</a></li>
                        <li><a href="becas.php">Becas y Plan 1 a 1</a></li>
                        <li><a href="reglamentos.php">Reglamentos PDF</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h5>Facultades</h5>
                    <ul class="list-unstyled mb-0">
                        <li><a href="facultades.php#salud">01 · Ciencias de la Salud</a></li>
                        <li><a href="facultades.php#tecnologia">02 · Ciencia y Tecnología</a></li>
                        <li><a href="facultades.php#economicas">03 · Económicas y Jurídicas</a></li>
                        <li><a href="historia.php">Historia y Filosofía</a></li>
                        <li><a href="contacto.php">Directorio de Sedes</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h5>Campus Cochabamba</h5>
                    <div class="small mb-2">
                        <strong class="text-white fw-normal">Sede Central:</strong> C. Uruguay #E-836 entre 16 de Julio y Oquendo.
                    </div>
                    <div class="small mb-2">
                        <strong class="text-white fw-normal">Campus Norte:</strong> Av. Uyuni #1889 casi Circunvalación.
                    </div>
                    <div class="small mb-3">
                        Telf. (4) 4509488 – 4221004 · WhatsApp 79795778
                    </div>
                    <a href="https://wa.me/59179795778" target="_blank" class="btn btn-ulat-ghost-light btn-sm w-100">
                        WhatsApp Admisiones ↗
                    </a>
                </div>
            </div>

            <div class="d-flex flex-wrap justify-content-between align-items-center pt-4 mt-5" style="border-top: 1px solid rgba(255,255,255,0.08); font-size: 0.76rem;">
                <div>
                    &copy; <?= date('Y') ?> Universidad Latinoamericana (ULAT) · Todos los derechos reservados.
                </div>
                <div class="d-flex gap-4 mt-2 mt-md-0">
                    <a href="reglamentos.php" class="mb-0">Reglamento Estudiantil</a>
                    <a href="reglamentos.php" class="mb-0">Reglamento de Becas</a>
                    <a href="reglamentos.php" class="mb-0">Graduación</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Widget Flotante Guía ULAT -->
    <?php require_once __DIR__ . '/chat_widget_ulat.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if (window.lucide) {
            lucide.createIcons({ attrs: { 'stroke-width': 1.4 } });
        }
    </script>
</body>
</html>
