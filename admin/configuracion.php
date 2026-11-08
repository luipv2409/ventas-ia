<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/database.php';

use Services\Security\SecurityKernel;

$pdo = Database::getConnection();
$clavesPermitidas = ['nombre_tienda', 'nombre_asesor', 'contacto_telefono', 'contacto_email', 'whatsapp_admisiones'];

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    SecurityKernel::requireCsrfPost();

    $institucion = mb_substr(trim((string)($_POST['nombre_tienda'] ?? '')), 0, 120);
    $asesor      = mb_substr(trim((string)($_POST['nombre_asesor'] ?? '')), 0, 80);
    $telefono    = mb_substr(trim((string)($_POST['contacto_telefono'] ?? '')), 0, 40);
    $email       = mb_substr(trim((string)($_POST['contacto_email'] ?? '')), 0, 120);
    $whatsapp    = preg_replace('/[^0-9]/', '', (string)($_POST['whatsapp_admisiones'] ?? ''));

    if ($institucion === '' || $asesor === '') {
        $error = 'El nombre institucional y el nombre del asistente son obligatorios.';
    } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo institucional ingresado no tiene formato válido.';
    } elseif ($whatsapp !== '' && (strlen($whatsapp) < 8 || strlen($whatsapp) > 15)) {
        $error = 'El número de WhatsApp debe contener entre 8 y 15 dígitos.';
    } else {
        $stmtUpd = $pdo->prepare('REPLACE INTO configuracion (clave, valor) VALUES (:c, :v)');
        $valores = [
            'nombre_tienda'       => $institucion,
            'nombre_asesor'       => $asesor,
            'contacto_telefono'   => $telefono,
            'contacto_email'      => $email,
            'whatsapp_admisiones' => $whatsapp,
        ];
        foreach ($valores as $k => $v) {
            $stmtUpd->execute([':c' => $k, ':v' => $v]);
        }
        header('Location: configuracion.php?msg=ok');
        exit;
    }
}

$inPlaceholders = implode(',', array_fill(0, count($clavesPermitidas), '?'));
$stmt = $pdo->prepare("SELECT clave, valor FROM configuracion WHERE clave IN ({$inPlaceholders})");
$stmt->execute($clavesPermitidas);
$config = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];

ob_start();
?>
<h2 class="fw-bold brand-font mb-1">Configuración Institucional — ULAT</h2>
<p class="text-muted small mb-4">Parámetros globales utilizados por el asistente virtual Guía ULAT y el portal de admisiones.</p>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'ok'): ?>
    <div class="alert alert-success small py-2">Configuración institucional guardada correctamente.</div>
<?php endif; ?>

<?php if ($error !== null): ?>
    <div class="alert alert-danger small py-2"><?= SecurityKernel::e($error) ?></div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 p-4">
    <form method="POST" class="row g-3" autocomplete="off">
        <?= SecurityKernel::csrfInput() ?>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Nombre de la Institución</label>
            <input type="text" name="nombre_tienda" class="form-control" maxlength="120" required
                   value="<?= SecurityKernel::e($config['nombre_tienda'] ?? 'Universidad Latinoamericana (ULAT)') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Nombre del Asistente Virtual</label>
            <input type="text" name="nombre_asesor" class="form-control" maxlength="80" required
                   value="<?= SecurityKernel::e($config['nombre_asesor'] ?? 'Guía ULAT') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Teléfonos Fijos de Contacto</label>
            <input type="text" name="contacto_telefono" class="form-control" maxlength="40"
                   value="<?= SecurityKernel::e($config['contacto_telefono'] ?? '(4) 4509488 - 4221004') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">WhatsApp Admisiones (código país + número)</label>
            <input type="text" name="whatsapp_admisiones" class="form-control" maxlength="15"
                   value="<?= SecurityKernel::e($config['whatsapp_admisiones'] ?? '59179795778') ?>">
        </div>
        <div class="col-md-4">
            <label class="form-label small fw-semibold">Correo Institucional</label>
            <input type="email" name="contacto_email" class="form-control" maxlength="120"
                   value="<?= SecurityKernel::e($config['contacto_email'] ?? 'latinoamericana@ulat.edu.bo') ?>">
        </div>
        <div class="col-12 pt-2">
            <button type="submit" class="btn btn-primary px-4 fw-semibold">Guardar Configuración</button>
        </div>
    </form>
</div>
<?php
$contenido = ob_get_clean();
$titulo = 'Configuración Institucional';
require __DIR__ . '/layout.php';
