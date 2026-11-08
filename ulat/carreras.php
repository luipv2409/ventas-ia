<?php
declare(strict_types=1);
$pageTitle = 'Oferta Académica (15 Carreras) | Universidad Latinoamericana (ULAT)';
$activeNav = 'carreras';
require_once __DIR__ . '/header.php';
?>

<div class="ulat-page-header">
    <div class="container">
        <div class="row align-items-end g-4">
            <div class="col-lg-8">
                <span class="ulat-eyebrow-light">Catálogo de Pregrado · 15 Programas</span>
                <h1 class="font-editorial display-4 text-white mb-0">Oferta Académica Oficial</h1>
            </div>
            <div class="col-lg-4 text-lg-end">
                <p class="small text-white-50 mb-0">
                    Títulos en Provisión Nacional · Régimen Semestral Presencial
                </p>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <!-- Barra de Filtro y Buscador Instantáneo Minimalista -->
    <div class="row align-items-center g-3 mb-5 pb-4" style="border-bottom: 1px solid var(--ulat-line);">
        <div class="col-lg-8">
            <div class="d-flex flex-wrap gap-2">
                <button class="btn btn-ulat-primary btn-sm btn-filtro" data-filter="all">Todas · 15</button>
                <button class="btn btn-ulat-outline btn-sm btn-filtro" data-filter="salud">Ciencias de la Salud · 06</button>
                <button class="btn btn-ulat-outline btn-sm btn-filtro" data-filter="tecnologia">Ciencia y Tecnología · 05</button>
                <button class="btn btn-ulat-outline btn-sm btn-filtro" data-filter="economicas">Económicas y Jurídicas · 04</button>
            </div>
        </div>
        <div class="col-lg-4">
            <input type="search" id="buscador-carreras" class="form-control form-control-sm bg-white" placeholder="Filtrar por carrera o área (ej. Sistemas, Salud)...">
        </div>
    </div>

    <!-- Grilla Editorial de Carreras -->
    <div class="row g-4" id="carreras-grid">
        <?php 
        $idx = 1;
        foreach ($carrerasCatalog as $slug => $c): 
            $facClass = str_contains($c['facultad'], 'Salud') ? 'salud' : (str_contains($c['facultad'], 'Tecnolog') ? 'tecnologia' : 'economicas');
            $numStr = str_pad((string)$idx++, 2, '0', STR_PAD_LEFT);
        ?>
            <div class="col-lg-4 col-md-6 carrera-item" data-category="<?= $facClass ?>" data-search="<?= htmlspecialchars(mb_strtolower($c['nombre'] . ' ' . $c['facultad'] . ' ' . $c['grado'])) ?>">
                <div class="card card-ulat h-100 p-4 d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-center mb-3 pb-2" style="border-bottom: 1px solid var(--ulat-line);">
                            <span class="academic-index"><?= $numStr ?> · <?= htmlspecialchars(str_replace('Facultad de ', '', $c['facultad'])) ?></span>
                            <span class="academic-index"><?= htmlspecialchars($c['grado']) ?></span>
                        </div>
                        <h3 class="h5 mb-2">
                            <a href="carrera.php?id=<?= $slug ?>" class="text-decoration-none" style="color: var(--ulat-ink);">
                                <?= htmlspecialchars($c['nombre']) ?>
                            </a>
                        </h3>
                        <div class="font-mono-code text-muted mb-3" style="font-size: 0.68rem;">
                            <?= htmlspecialchars($c['duracion']) ?> · <?= htmlspecialchars($c['modalidad']) ?>
                        </div>
                        <p class="small text-muted mb-4" style="line-height: 1.6;">
                            <?= htmlspecialchars(mb_substr(strip_tags($c['descripcion']), 0, 145)) ?>...
                        </p>
                    </div>
                    <div class="pt-3 d-flex justify-content-between align-items-center" style="border-top: 1px solid var(--ulat-line);">
                        <a href="carrera.php?id=<?= $slug ?>" class="font-mono-code text-decoration-none" style="color: var(--ulat-ink);">
                            Ver Plan de Estudios →
                        </a>
                        <button type="button" class="btn btn-link btn-sm p-0 text-muted text-decoration-none font-mono-code" title="Consultar con Guía ULAT" data-chat-prompt="<?= htmlspecialchars($c['nombre'], ENT_QUOTES, 'UTF-8') ?>">
                            Consultar ↗
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
const btnsFiltro = document.querySelectorAll('.btn-filtro');
const inputSearch = document.getElementById('buscador-carreras');
let currentCategory = 'all';

function aplicarFiltros() {
    const q = (inputSearch?.value || '').trim().toLowerCase();
    document.querySelectorAll('.carrera-item').forEach(item => {
        const matchCat = (currentCategory === 'all' || item.dataset.category === currentCategory);
        const matchText = (q === '' || (item.dataset.search || '').includes(q));
        item.style.display = (matchCat && matchText) ? 'block' : 'none';
    });
}

btnsFiltro.forEach(btn => {
    btn.addEventListener('click', () => {
        btnsFiltro.forEach(b => {
            b.classList.remove('btn-ulat-primary');
            b.classList.add('btn-ulat-outline');
        });
        btn.classList.remove('btn-ulat-outline');
        btn.classList.add('btn-ulat-primary');
        currentCategory = btn.dataset.filter;
        aplicarFiltros();
    });
});

inputSearch?.addEventListener('input', aplicarFiltros);
</script>

<?php require_once __DIR__ . '/footer.php'; ?>
