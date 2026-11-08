<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

use Services\Security\SecurityKernel;

$pdo = Database::getConnection();
$estadosValidos = ['nuevo', 'contactado', 'en_seguimiento', 'matriculado', 'descartado'];
$origenesValidos = ['chatbot', 'formulario_web', 'whatsapp_directo'];

$mensajeOk = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    SecurityKernel::requireCsrfPost();

    $accion = (string)($_POST['accion'] ?? '');
    if ($accion === 'cambiar_estado') {
        $id = (int)($_POST['prospecto_id'] ?? 0);
        $nuevoEstado = (string)($_POST['estado'] ?? '');
        if ($id > 0 && in_array($nuevoEstado, $estadosValidos, true)) {
            $stmtUpd = $pdo->prepare('UPDATE admisiones_prospectos SET estado = :e WHERE id = :id');
            $stmtUpd->execute([':e' => $nuevoEstado, ':id' => $id]);
            header('Location: prospectos.php?updated=1');
            exit;
        }
    }
}

if (isset($_GET['updated'])) {
    $mensajeOk = 'Estado del prospecto actualizado correctamente.';
}

$filtroEstado = (string)($_GET['estado'] ?? '');
$filtroOrigen = (string)($_GET['origen'] ?? '');

$where = [];
$params = [];

if (in_array($filtroEstado, $estadosValidos, true)) {
    $where[] = 'estado = :estado';
    $params[':estado'] = $filtroEstado;
}
if (in_array($filtroOrigen, $origenesValidos, true)) {
    $where[] = 'origen = :origen';
    $params[':origen'] = $filtroOrigen;
}

$sql = 'SELECT id, nombre, telefono, email, carrera_interes, origen, notas, estado, created_at FROM admisiones_prospectos';
if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY created_at DESC LIMIT 200';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$prospectos = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="fw-bold brand-font mb-1">Prospectos de Admisiones ULAT</h2>
        <p class="text-muted small mb-0">Postulantes registrados vía Guía ULAT y formularios web institucionales.</p>
    </div>
    <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
        <select name="estado" class="form-select form-select-sm" style="width: auto;">
            <option value="">Todos los estados</option>
            <?php foreach ($estadosValidos as $est): ?>
                <option value="<?= SecurityKernel::e($est) ?>" <?= $filtroEstado === $est ? 'selected' : '' ?>>
                    <?= SecurityKernel::e(ucfirst(str_replace('_', ' ', $est))) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="origen" class="form-select form-select-sm" style="width: auto;">
            <option value="">Todos los canales</option>
            <?php foreach ($origenesValidos as $org): ?>
                <option value="<?= SecurityKernel::e($org) ?>" <?= $filtroOrigen === $org ? 'selected' : '' ?>>
                    <?= SecurityKernel::e($org) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-primary">Filtrar</button>
        <a href="prospectos.php" class="btn btn-sm btn-outline-secondary">Limpiar</a>
    </form>
</div>

<?php if ($mensajeOk !== null): ?>
    <div class="alert alert-success small py-2"><?= SecurityKernel::e($mensajeOk) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-saas align-middle mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Postulante</th>
                    <th>Contacto</th>
                    <th>Carrera</th>
                    <th>Canal</th>
                    <th>Notas</th>
                    <th>Fecha</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($prospectos)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No se encontraron prospectos con los filtros seleccionados.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($prospectos as $p): ?>
                        <tr>
                            <td class="fw-bold">#<?= (int)$p['id'] ?></td>
                            <td class="fw-semibold"><?= SecurityKernel::e($p['nombre']) ?></td>
                            <td class="small">
                                <div><?= SecurityKernel::e($p['telefono']) ?></div>
                                <?php if (!empty($p['email'])): ?>
                                    <div class="text-muted"><?= SecurityKernel::e($p['email']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= SecurityKernel::e($p['carrera_interes']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= SecurityKernel::e($p['origen']) ?></span></td>
                            <td class="small text-muted" style="max-width: 240px;"><?= SecurityKernel::e(mb_substr((string)($p['notas'] ?? ''), 0, 120)) ?></td>
                            <td class="small text-muted"><?= SecurityKernel::e(date('d/m/Y H:i', strtotime((string)$p['created_at']))) ?></td>
                            <td>
                                <form method="POST" class="d-flex gap-1 align-items-center m-0">
                                    <?= SecurityKernel::csrfInput() ?>
                                    <input type="hidden" name="accion" value="cambiar_estado">
                                    <input type="hidden" name="prospecto_id" value="<?= (int)$p['id'] ?>">
                                    <select name="estado" class="form-select form-select-sm" style="width: 145px;">
                                        <?php foreach ($estadosValidos as $est): ?>
                                            <option value="<?= SecurityKernel::e($est) ?>" <?= $p['estado'] === $est ? 'selected' : '' ?>>
                                                <?= SecurityKernel::e(ucfirst(str_replace('_', ' ', $est))) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-outline-primary">OK</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$contenido = ob_get_clean();
$titulo = 'Prospectos de Admisiones';
require __DIR__ . '/layout.php';
