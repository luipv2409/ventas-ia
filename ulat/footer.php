<?php
// ulat/footer.php
?>
    <!-- FOOTER INSTITUCIONAL -->
    <footer class="ulat-footer">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <img src="../assets/ulat/logo.png" alt="Universidad Latinoamericana ULAT" class="mb-3" style="max-height: 46px;">
                    <p class="small mb-3" style="color: #94a3b8; line-height: 1.6;">
                        La Universidad Latinoamericana (ULAT) es una institución privada de educación superior fundada el 27 de enero de 1995 en Cochabamba, dedicada a la formación integral en ciencia, tecnología y humanismo.
                    </p>
                    <div class="small mb-1" style="color: #e2e8f0;">
                        <strong>Resoluciones Ministeriales:</strong> Nº 241/02 · 590/07 · 597/07 · 157/07 · 0140/25
                    </div>
                    <div class="small" style="color: #64748b;">
                        Personería Jurídica 191/97 – 214/97 · Títulos en Provisión Nacional
                    </div>
                </div>

                <div class="col-lg-2 col-md-6">
                    <h5>Portal Institucional</h5>
                    <ul class="list-unstyled mb-0">
                        <li><a href="index.php">Inicio</a></li>
                        <li><a href="facultades.php">Facultades</a></li>
                        <li><a href="carreras.php">Oferta Académica (15)</a></li>
                        <li><a href="admisiones.php">Requisitos de Admisión</a></li>
                        <li><a href="becas.php">Becas y Plan Uno a Uno</a></li>
                        <li><a href="reglamentos.php">Reglamentos Oficiales</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h5>Áreas Académicas</h5>
                    <ul class="list-unstyled mb-0">
                        <li><a href="facultades.php#salud">Facultad de Ciencias de la Salud</a></li>
                        <li><a href="facultades.php#tecnologia">Facultad de Ciencia y Tecnología</a></li>
                        <li><a href="facultades.php#economicas">Ciencias Económicas y Jurídicas</a></li>
                        <li><a href="becas.php#plan1a1">Plan Uno a Uno (50% en Cuotas)</a></li>
                        <li><a href="historia.php">Historia, Misión y Visión</a></li>
                    </ul>
                </div>

                <div class="col-lg-3 col-md-6">
                    <h5>Sedes y Admisiones</h5>
                    <ul class="list-unstyled small mb-3" style="color: #cbd5e1;">
                        <li class="mb-2 d-flex align-items-start gap-2">
                            <i data-lucide="map-pin" class="lucide-sm mt-1" style="color: var(--ulat-gold);"></i>
                            <span><strong>Sede Central:</strong> Calle Uruguay #E-836 entre 16 de Julio y Oquendo.</span>
                        </li>
                        <li class="mb-2 d-flex align-items-start gap-2">
                            <i data-lucide="building-2" class="lucide-sm mt-1" style="color: var(--ulat-gold);"></i>
                            <span><strong>Campus Norte:</strong> Av. Uyuni Nº 1889 casi Av. Circunvalación.</span>
                        </li>
                        <li class="mb-2 d-flex align-items-center gap-2">
                            <i data-lucide="phone" class="lucide-sm" style="color: var(--ulat-gold);"></i>
                            <span>(4) 4509488 – (4) 4221004</span>
                        </li>
                        <li class="mb-2 d-flex align-items-center gap-2">
                            <i data-lucide="message-square" class="lucide-sm" style="color: #4ade80;"></i>
                            <span>WhatsApp: +591 79795778</span>
                        </li>
                        <li class="d-flex align-items-center gap-2">
                            <i data-lucide="mail" class="lucide-sm" style="color: var(--ulat-gold);"></i>
                            <span>latinoamericana@ulat.edu.bo</span>
                        </li>
                    </ul>
                    <a href="https://wa.me/59179795778" target="_blank" class="btn btn-ulat-gold btn-sm w-100 d-inline-flex align-items-center justify-content-center gap-2">
                        <i data-lucide="message-square" class="lucide-sm"></i>
                        <span>Contactar Admisiones</span>
                    </a>
                </div>
            </div>

            <hr class="border-secondary border-opacity-25 mt-5 mb-4">

            <div class="d-flex flex-wrap justify-content-between align-items-center small" style="color: #64748b;">
                <div>
                    &copy; <?= date('Y') ?> Universidad Latinoamericana (ULAT) — Cochabamba, Bolivia.
                </div>
                <div class="d-flex gap-4 mt-2 mt-md-0">
                    <a href="reglamentos.php" class="mb-0" style="color: #64748b;">Reglamento Estudiantil</a>
                    <a href="reglamentos.php" class="mb-0" style="color: #64748b;">Reglamento de Becas</a>
                    <a href="reglamentos.php" class="mb-0" style="color: #64748b;">Graduación</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Widget de Chat Flotante Guía ULAT -->
    <?php require_once __DIR__ . '/chat_widget_ulat.php'; ?>

    <!-- Scripts Bootstrap y Lucide Icons -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        if (window.lucide) {
            lucide.createIcons({ attrs: { 'stroke-width': 1.6 } });
        }
    </script>
</body>
</html>
