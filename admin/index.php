<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/autoload.php';

use Services\Chat\ClientIpResolver;
use Services\Chat\RateLimiter;
use Services\Security\SecurityKernel;

SecurityKernel::startSession(true);
SecurityKernel::applySecurityHeaders('admin');

if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!SecurityKernel::verifyCsrf()) {
        http_response_code(403);
        $error = 'La sesión del formulario expiró. Vuelve a intentarlo.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $pdo = Database::getConnection();
        $limiter = new RateLimiter($pdo);
        $clientIp = ClientIpResolver::resolve();

        // Protección anti-fuerza bruta: máx 5 intentos cada 15 minutos por IP y por cuenta
        $ipCheck = $limiter->hit('admin_login_ip:' . $clientIp, 5, 900);
        $usrCheck = $limiter->hit('admin_login_usr:' . mb_strtolower(mb_substr($username, 0, 50)), 5, 900);

        if (!$ipCheck['allowed'] || !$usrCheck['allowed']) {
            http_response_code(429);
            $error = 'Demasiados intentos de acceso. Espera unos minutos antes de reintentar.';
        } else {
            // Rechazar explícitamente contraseñas por defecto conocidas comprometidas
            $prohibidas = ['admin123', 'admin', 'password', '123456', '12345678'];
            if ($username !== '' && $password !== '' && !in_array(strtolower($password), $prohibidas, true)) {
                $stmt = $pdo->prepare('SELECT id, username, password_hash FROM usuarios_admin WHERE username = :u LIMIT 1');
                $stmt->execute([':u' => $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, (string)$user['password_hash'])) {
                    SecurityKernel::regenerateSession();
                    $_SESSION['admin_id'] = (int)$user['id'];
                    $_SESSION['admin_user'] = (string)$user['username'];
                    $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
                    header('Location: dashboard.php');
                    exit;
                }
            }
            $error = 'Credenciales inválidas.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Administrativo — Portal ULAT</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow-sm border-0" style="width: 23rem; border-radius: 12px;">
        <div class="card-body p-4">
            <div class="text-center mb-4">
                <span class="badge bg-dark mb-2">ULAT · Admisiones &amp; IA</span>
                <h4 class="fw-bold mb-0">Panel Administrativo</h4>
            </div>
            <?php if ($error !== null): ?>
                <div class="alert alert-danger small py-2"><?= SecurityKernel::e($error) ?></div>
            <?php endif; ?>
            <form method="POST" autocomplete="off">
                <?= SecurityKernel::csrfInput() ?>
                <div class="mb-3">
                    <label for="username" class="form-label small fw-semibold">Usuario</label>
                    <input type="text" class="form-control" id="username" name="username" maxlength="50" required autofocus>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label small fw-semibold">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" maxlength="128" required>
                </div>
                <button type="submit" class="btn btn-dark w-100 fw-semibold">Ingresar</button>
            </form>
        </div>
    </div>
</body>
</html>
