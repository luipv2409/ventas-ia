<?php
declare(strict_types=1);
$pageTitle = 'Oferta Académica (15 Carreras) | Universidad Latinoamericana (ULAT)';
$activeNav = 'carreras';
require_once __DIR__ . '/header.php';
?>

<div class="bg-primary text-white py-5" style="background: linear-gradient(135deg, var(--ulat-blue-dark) 0%, var(--ulat-blue-primary) 100%);">
    <div class="container text-center py-3">
        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold mb-2">Oferta Académica Oficial</span>
        <h1 class="display-5 fw-bold text-white mb-2">Nuestras 15 Carreras Profesionales</h1>
        <p class="lead text-light text-opacity-75 max-w-700 mx-auto">
            Elige la carrera que impulsará tu vocación y futuro profesional con planes de estudio actualizados y titulación avalada.
        </p>
    </div>
</div>

<div class="container py-5">
    <!-- Filtros por Facultad -->
    <div class="d-flex flex-wrap justify-content-center gap-2 mb-5">
        <button class="btn btn-outline-primary btn-sm px-3 rounded-pill active btn-filtro" data-filter="all">Todas (15)</button>
        <button class="btn btn-outline-danger btn-sm px-3 rounded-pill btn-filtro" data-filter="salud"><i class="fa-solid fa-heart-pulse me-1"></i> Ciencias de la Salud (6)</button>
        <button class="btn btn-outline-info btn-sm px-3 rounded-pill text-dark btn-filtro" data-filter="tecnologia"><i class="fa-solid fa-microchip me-1"></i> Ciencia y Tecnología (5)</button>
        <button class="btn btn-outline-warning btn-sm px-3 rounded-pill text-dark btn-filtro" data-filter="economicas"><i class="fa-solid fa-scale-balanced me-1"></i> Económicas y Jurídicas (4)</button>
    </div>

    <!-- Grilla de Carreras -->
    <div class="row g-4" id="carreras-grid">
        <?php foreach ($carrerasCatalog as $slug => $c): 
            $facClass = str_contains($c['facultad'], 'Salud') ? 'salud' : (str_contains($c['facultad'], 'Tecnolog') ? 'tecnologia' : 'economicas');
            $badgeColor = str_contains($c['facultad'], 'Salud') ? 'badge-salud' : (str_contains($c['facultad'], 'Tecnolog') ? 'badge-tecno' : 'badge-econo');
            $icon = str_contains($c['facultad'], 'Salud') ? 'fa-user-doctor' : (str_contains($c['facultad'], 'Tecnolog') ? 'fa-laptop-code' : 'fa-briefcase');
        ?>
            <div class="col-lg-4 col-md-6 carrera-item" data-category="<?= $facClass ?>">
                <div class="card card-ulat h-100 d-flex flex-column">
                    <div class="p-4 flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <span class="badge-facultad <?= $badgeColor ?>">
                                <?= htmlspecialchars($c['facultad']) ?>
                            </span>
                            <span class="badge bg-light text-secondary border">
                                <?= htmlspecialchars($c['grado']) ?>
                            </span>
                        </div>
                        <h4 class="h5 fw-bold text-dark mb-2">
                            <?= htmlspecialchars($c['nombre']) ?>
                        </h4>
                        <div class="small text-muted mb-3 d-flex align-items-center gap-3">
                            <span><i class="fa-solid fa-clock text-warning me-1"></i> <?= htmlspecialchars($c['duracion']) ?></span>
                            <span><i class="fa-solid fa-building text-info me-1"></i> <?= htmlspecialchars($c['modalidad']) ?></span>
                        </div>
                        <p class="text-secondary small mb-0 line-clamp-3">
                            <?= htmlspecialchars(mb_substr(strip_tags($c['descripcion']), 0, 160)) ?>...
                        </p>
                    </div>
                    <div class="p-3 bg-light border-top d-flex justify-content-between align-items-center gap-2">
                        <a href="carrera.php?id=<?= $slug ?>" class="btn btn-outline-primary btn-sm rounded-pill flex-grow-1">
                            <i class="fa-solid fa-table-list me-1"></i> Ver Malla y Perfil
                        </a>
                        <button type="button" class="btn btn-warning btn-sm rounded-circle text-dark" title="Consultar con Guía ULAT" onclick="window.preguntarAlChat('<?= addslashes($c['nombre']) ?>')">
                            <i class="fa-solid fa-comment-dots"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
document.querySelectorAll('.btn-filtro').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.btn-filtro').forEach(b => b.classList.remove('active', 'btn-primary', 'btn-danger', 'btn-info', 'btn-warning'));
        btn.classList.add('active');
        const filter = btn.dataset.filter;
        document.querySelectorAll('.carrera-item').forEach(item => {
            if (filter === 'all' || item.dataset.category === filter) {
                item.style.display = 'block';
            } else {
                item.style.display = 'none';
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/footer.php'; ?>

