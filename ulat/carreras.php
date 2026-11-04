<?php
declare(strict_types=1);
$pageTitle = 'Oferta Académica (15 Carreras) | Universidad Latinoamericana (ULAT)';
$activeNav = 'carreras';
require_once __DIR__ . '/header.php';
?>

<div class="ulat-page-header">
    <div class="container py-2">
        <span class="ulat-eyebrow-light">Programas de Pregrado Acreditados</span>
        <h1 class="display-6 fw-bold text-white mb-2">Oferta Académica Oficial (15 Carreras)</h1>
        <p class="mb-0 text-white-50" style="max-width: 680px; font-size: 0.98rem;">
            Planes de estudio semestrales actualizados, mallas curriculares completas y titulación en Provisión Nacional avalada por el Ministerio de Educación.
        </p>
    </div>
</div>

<div class="container py-5">
    <!-- Filtros Sobrios por Facultad -->
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4 pb-3 border-bottom">
        <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-ulat-primary btn-sm btn-filtro" data-filter="all">Todas las Carreras (15)</button>
            <button class="btn btn-ulat-outline btn-sm btn-filtro" data-filter="salud">Ciencias de la Salud (6)</button>
            <button class="btn btn-ulat-outline btn-sm btn-filtro" data-filter="tecnologia">Ciencia y Tecnología (5)</button>
            <button class="btn btn-ulat-outline btn-sm btn-filtro" data-filter="economicas">Económicas y Jurídicas (4)</button>
        </div>
        <div class="small text-muted">
            Modalidad Presencial · Régimen Semestral
        </div>
    </div>

    <!-- Grilla de Carreras -->
    <div class="row g-4" id="carreras-grid">
        <?php foreach ($carrerasCatalog as $slug => $c): 
            $facClass = str_contains($c['facultad'], 'Salud') ? 'salud' : (str_contains($c['facultad'], 'Tecnolog') ? 'tecnologia' : 'economicas');
            $numSemestres = isset($c['semestres']) && is_array($c['semestres']) ? count($c['semestres']) : 0;
        ?>
            <div class="col-lg-4 col-md-6 carrera-item" data-category="<?= $facClass ?>">
                <div class="card card-ulat h-100 d-flex flex-column">
                    <div class="p-4 flex-grow-1">
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                            <span class="ulat-eyebrow mb-0"><?= htmlspecialchars($c['facultad']) ?></span>
                            <span class="badge bg-light text-secondary border fw-medium"><?= htmlspecialchars($c['grado']) ?></span>
                        </div>
                        <h3 class="h5 fw-bold text-dark mb-2">
                            <a href="carrera.php?id=<?= $slug ?>" class="text-decoration-none text-dark"><?= htmlspecialchars($c['nombre']) ?></a>
                        </h3>
                        <div class="small text-muted mb-3 d-flex flex-wrap gap-3">
                            <span class="d-inline-flex align-items-center gap-1">
                                <i data-lucide="calendar" class="lucide-sm"></i>
                                <?= htmlspecialchars($c['duracion']) ?>
                            </span>
                        </div>
                        <p class="text-secondary small mb-0" style="line-height: 1.6;">
                            <?= htmlspecialchars(mb_substr(strip_tags($c['descripcion']), 0, 155)) ?>...
                        </p>
                    </div>
                    <div class="px-4 py-3 bg-light border-top d-flex justify-content-between align-items-center gap-2">
                        <a href="carrera.php?id=<?= $slug ?>" class="btn btn-ulat-primary btn-sm flex-grow-1 d-inline-flex align-items-center justify-content-center gap-2">
                            <span>Malla Curricular y Perfil</span>
                            <i data-lucide="arrow-right" class="lucide-sm"></i>
                        </a>
                        <button type="button" class="btn btn-ulat-outline btn-sm px-2.5" title="Consultar con Guía ULAT" onclick="window.preguntarAlChat('<?= addslashes($c['nombre']) ?>')">
                            <i data-lucide="message-square" class="lucide-sm"></i>
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
        document.querySelectorAll('.btn-filtro').forEach(b => {
            b.classList.remove('btn-ulat-primary');
            b.classList.add('btn-ulat-outline');
        });
        btn.classList.remove('btn-ulat-outline');
        btn.classList.add('btn-ulat-primary');
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
