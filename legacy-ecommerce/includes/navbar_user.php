<?php
declare(strict_types=1);

require_once __DIR__ . '/auth_cliente.php';

$clienteNav = cliente_actual();
$inicialesNav = '';
if ($clienteNav) {
    $partes = explode(' ', trim($clienteNav['nombre']));
    $inicialesNav = count($partes) >= 2 
        ? mb_strtoupper(mb_substr($partes[0], 0, 1) . mb_substr($partes[1], 0, 1))
        : mb_strtoupper(mb_substr($clienteNav['nombre'], 0, 2));
}
?>
<?php if ($clienteNav): ?>
    <li class="nav-item dropdown ms-lg-2">
        <a class="nav-link dropdown-toggle d-flex align-items-center gap-2 fw-semibold text-white px-2" href="#" id="clienteDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <?php if (!empty($clienteNav['avatar_url'])): ?>
                <img src="<?= htmlspecialchars($clienteNav['avatar_url']) ?>" alt="Avatar" class="rounded-circle shadow-sm" style="width: 28px; height: 28px; object-fit: cover; border: 1.5px solid #818cf8;">
            <?php else: ?>
                <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center shadow-sm" style="width: 28px; height: 28px; font-size: 0.75rem; font-weight: 700;">
                    <?= htmlspecialchars($inicialesNav) ?>
                </span>
            <?php endif; ?>
            <span><?= htmlspecialchars(explode(' ', $clienteNav['nombre'])[0]) ?></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 mt-2" aria-labelledby="clienteDropdown" style="min-width: 200px;">
            <li class="px-3 py-2 border-bottom">
                <div class="fw-bold small text-dark"><?= htmlspecialchars($clienteNav['nombre']) ?></div>
                <div class="text-muted small text-truncate" style="max-width: 170px;"><?= htmlspecialchars($clienteNav['email']) ?></div>
            </li>
            <li>
                <a class="dropdown-item py-2 d-flex align-items-center gap-2 <?= (($paginaActiva ?? '') === 'pedidos') ? 'active' : '' ?>" href="mis_pedidos.php">
                    <i data-lucide="package" class="text-primary"></i> Mis Pedidos
                </a>
            </li>
            <li><hr class="dropdown-divider my-1"></li>
            <li>
                <a class="dropdown-item py-2 text-danger d-flex align-items-center gap-2" href="api/auth_cliente.php?action=logout">
                    <i data-lucide="log-out"></i> Cerrar Sesión
                </a>
            </li>
        </ul>
    </li>
<?php else: ?>
    <li class="nav-item ms-lg-2">
        <a class="btn btn-outline-light rounded-pill px-3 py-1 d-inline-flex align-items-center gap-1" href="login.php">
            <i data-lucide="user"></i>
            <span>Ingresar</span>
        </a>
    </li>
<?php endif; ?>
