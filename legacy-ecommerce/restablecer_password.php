<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_cliente.php';

$token = trim($_GET['token'] ?? '');

// Cargar configuración de la tienda
$config = [];
try {
    $pdo = Database::getConnection();
    $stmtConfig = $pdo->query("SELECT clave, valor FROM configuracion");
    while ($row = $stmtConfig->fetch(PDO::FETCH_ASSOC)) {
        $config[$row['clave']] = $row['valor'];
    }
} catch (Exception $e) {
    // Silencioso
}

$nombreTienda = $config['nombre_tienda'] ?? 'Nuestra Tienda';

// Validar token en la base de datos
$tokenValido = false;
$cliente = null;

if (!empty($token)) {
    try {
        $stmt = $pdo->prepare("SELECT id, nombre, email FROM clientes WHERE token_recuperacion = ? AND token_expira > NOW() AND activo = 1 LIMIT 1");
        $stmt->execute([$token]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($cliente) {
            $tokenValido = true;
        }
    } catch (Exception $e) {
        $tokenValido = false;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Contraseña — <?= htmlspecialchars($nombreTienda) ?></title>
    
    <!-- Google Fonts: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Estilos Tech Premium -->
    <link rel="stylesheet" href="assets/css/estilos.css">

    <style>
        .auth-container {
            min-height: calc(100vh - 180px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2.5rem 1rem;
        }
        .auth-card {
            background: #ffffff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
            width: 100%;
            max-width: 460px;
            overflow: hidden;
        }
        .auth-header {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            padding: 2rem;
            color: #fff;
            text-align: center;
        }
        .auth-body {
            padding: 2rem;
        }
    </style>
</head>
<body class="bg-slate-50">

    <!-- Navbar Sticky Tech -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-tech sticky-top py-2">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
                <span class="brand-icon d-inline-flex align-items-center justify-content-center"><i data-lucide="cpu" style="width:20px;height:20px;"></i></span>
                <span class="brand-font fs-4"><?= htmlspecialchars($nombreTienda) ?></span>
            </a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <a href="login.php" class="text-white-50 text-decoration-none small d-inline-flex align-items-center gap-1">
                    <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Ir a Iniciar Sesión
                </a>
            </div>
        </div>
    </nav>

    <!-- Contenedor -->
    <div class="auth-container">
        <div class="auth-card">
            
            <div class="auth-header">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-10 mb-2" style="width: 52px; height: 52px;">
                    <i data-lucide="shield-check" style="width:26px;height:26px;" class="text-info"></i>
                </div>
                <h4 class="fw-bold mb-1">Restablecer Contraseña</h4>
                <p class="text-light text-opacity-75 small mb-0">Crea una nueva contraseña segura para tu cuenta</p>
            </div>

            <div class="auth-body">
                
                <div id="alerta-reset" class="d-none"></div>

                <?php if (!$tokenValido): ?>
                    <div class="text-center py-3">
                        <div class="mb-3 text-warning d-flex justify-content-center">
                            <i data-lucide="alert-triangle" style="width:40px;height:40px;"></i>
                        </div>
                        <h5 class="fw-bold text-dark">Enlace no válido o expirado</h5>
                        <p class="text-muted small mb-4">
                            El enlace de recuperación es incorrecto, ya fue utilizado o superó los 60 minutos de vigencia.
                        </p>
                        <a href="recuperar_password.php" class="btn btn-primary rounded-pill w-100 py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-2">
                            <i data-lucide="rotate-ccw" style="width:16px;height:16px;"></i> <span>Solicitar Nuevo Enlace</span>
                        </a>
                    </div>
                <?php else: ?>
                    <div id="pantalla-formulario">
                        <div class="alert alert-light border small text-muted p-2 rounded-3 mb-3">
                            Restableciendo acceso para: <strong><?= htmlspecialchars($cliente['email']) ?></strong>
                        </div>

                        <form id="form-restablecer">
                            <div class="mb-3">
                                <label for="password" class="form-label small fw-semibold text-secondary">Nueva Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted border-end-0"><i data-lucide="lock" style="width:16px;height:16px;"></i></span>
                                    <input type="password" class="form-control tech-form-control border-start-0 ps-0" id="password" required minlength="6" placeholder="Mínimo 6 caracteres">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="password_confirm" class="form-label small fw-semibold text-secondary">Confirmar Nueva Contraseña</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-white text-muted border-end-0"><i data-lucide="lock" style="width:16px;height:16px;"></i></span>
                                    <input type="password" class="form-control tech-form-control border-start-0 ps-0" id="password_confirm" required minlength="6" placeholder="Repite tu nueva contraseña">
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm d-inline-flex align-items-center justify-content-center gap-2" id="btn-submit">
                                <i data-lucide="check" style="width:16px;height:16px;"></i> <span>Guardar Nueva Contraseña</span>
                            </button>
                        </form>
                    </div>

                    <div id="pantalla-exito" class="d-none text-center py-3">
                        <div class="mb-3 text-success d-flex justify-content-center">
                            <i data-lucide="check-circle-2" style="width:44px;height:44px;"></i>
                        </div>
                        <h5 class="fw-bold text-dark">¡Contraseña Actualizada!</h5>
                        <p class="text-muted small mb-4">
                            Tu contraseña ha sido modificada con éxito. Ya puedes iniciar sesión con tus nuevas credenciales.
                        </p>
                        <a href="login.php" class="btn btn-primary rounded-pill w-100 py-2 fw-semibold shadow-sm d-inline-flex align-items-center justify-content-center gap-2">
                            <i data-lucide="log-in" style="width:16px;height:16px;"></i> <span>Iniciar Sesión Ahora</span>
                        </a>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/lucide.min.js"></script>
    <script>
        const TOKEN = <?= json_encode($token) ?>;
        const form = document.getElementById('form-restablecer');
        const alerta = document.getElementById('alerta-reset');
        const btnSubmit = document.getElementById('btn-submit');
        const pantallaForm = document.getElementById('pantalla-formulario');
        const pantallaExito = document.getElementById('pantalla-exito');

        if (window.lucide) {
            lucide.createIcons();
        }

        function mostrarAlerta(msg, tipo = 'danger') {
            alerta.className = `alert alert-${tipo} border-0 small rounded-3 p-2 mb-3`;
            alerta.innerHTML = msg;
            alerta.classList.remove('d-none');
        }

        if (form) {
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                alerta.classList.add('d-none');

                const password = document.getElementById('password').value;
                const confirm = document.getElementById('password_confirm').value;

                if (password !== confirm) {
                    mostrarAlerta('Las contraseñas no coinciden.', 'warning');
                    return;
                }

                if (password.length < 6) {
                    mostrarAlerta('La contraseña debe tener al menos 6 caracteres.', 'warning');
                    return;
                }

                btnSubmit.disabled = true;
                btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Actualizando...';

                try {
                    const res = await fetch('api/auth_cliente.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({
                            action: 'restablecer',
                            token: TOKEN,
                            password: password,
                            password_confirm: confirm
                        })
                    });
                    const data = await res.json();

                    if (data.ok) {
                        pantallaForm.classList.add('d-none');
                        pantallaExito.classList.remove('d-none');
                        if (window.lucide) lucide.createIcons();
                    } else {
                        mostrarAlerta('⚠️ ' + data.error, 'danger');
                        btnSubmit.disabled = false;
                        btnSubmit.innerHTML = '<i data-lucide="check" style="width:16px;height:16px;"></i> <span>Guardar Nueva Contraseña</span>';
                        if (window.lucide) lucide.createIcons();
                    }
                } catch (err) {
                    mostrarAlerta('Error de conexión con el servidor.', 'danger');
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<i data-lucide="check" style="width:16px;height:16px;"></i> <span>Guardar Nueva Contraseña</span>';
                    if (window.lucide) lucide.createIcons();
                }
            });
        }
    </script>
</body>
</html>
