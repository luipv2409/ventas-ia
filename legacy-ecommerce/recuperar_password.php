<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_cliente.php';

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña — <?= htmlspecialchars($nombreTienda) ?></title>
    
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
                    <i data-lucide="arrow-left" style="width:14px;height:14px;"></i> Volver a Iniciar Sesión
                </a>
            </div>
        </div>
    </nav>

    <!-- Contenedor -->
    <div class="auth-container">
        <div class="auth-card">
            
            <div class="auth-header">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-10 mb-2" style="width: 52px; height: 52px;">
                    <i data-lucide="key" style="width:26px;height:26px;" class="text-warning"></i>
                </div>
                <h4 class="fw-bold mb-1">Recuperar Contraseña</h4>
                <p class="text-light text-opacity-75 small mb-0">Te enviaremos un enlace seguro para restablecer tu acceso</p>
            </div>

            <div class="auth-body">
                
                <div id="alerta-recovery" class="d-none"></div>

                <div id="pantalla-formulario">
                    <p class="small text-secondary mb-3">
                        Ingresa el correo electrónico asociado a tu cuenta. Te enviaremos un token seguro válido por 60 minutos.
                    </p>

                    <form id="form-recuperar">
                        <div class="mb-3">
                            <label for="email" class="form-label small fw-semibold text-secondary">Correo Electrónico Registrado</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted border-end-0"><i data-lucide="mail" style="width:16px;height:16px;"></i></span>
                                <input type="email" class="form-control tech-form-control border-start-0 ps-0" id="email" required placeholder="tu@email.com">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm d-inline-flex align-items-center justify-content-center gap-2" id="btn-submit">
                            <i data-lucide="send" style="width:16px;height:16px;"></i> <span>Enviar Enlace de Restablecimiento</span>
                        </button>
                    </form>
                </div>

                <div id="pantalla-resultado" class="d-none text-center">
                    <div class="mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success" style="width: 60px; height: 60px;">
                            <i data-lucide="check" style="width:30px;height:30px;"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold text-dark">¡Enlace Generado!</h5>
                    <p class="text-muted small mb-3" id="texto-confirmacion">
                        Revisa tu bandeja de entrada o spam.
                    </p>

                    <!-- Enlace de desarrollo/local para pruebas sin requerir servidor SMTP activo -->
                    <div id="caja-enlace-directo" class="d-none alert alert-info border-0 text-start small rounded-3 p-3 mb-3">
                        <div class="fw-bold text-dark mb-1 d-inline-flex align-items-center gap-1"><i data-lucide="flask-conical" style="width:16px;height:16px;"></i> Modo de Prueba / Entorno Local:</div>
                        <p class="mb-2 text-secondary">Puedes hacer clic directamente en el siguiente enlace generado para restablecer tu clave ahora mismo:</p>
                        <a href="#" id="enlace-restablecer" class="fw-bold text-primary text-break"></a>
                    </div>

                    <a href="login.php" class="btn btn-outline-secondary w-100 rounded-pill py-2 fw-semibold d-inline-flex align-items-center justify-content-center gap-1">
                        <i data-lucide="arrow-left" style="width:16px;height:16px;"></i> Regresar al Inicio de Sesión
                    </a>
                </div>

                <div class="text-center mt-4 pt-2 border-top">
                    <p class="small text-muted mb-0">
                        ¿Recordaste tu contraseña? 
                        <a href="login.php" class="text-primary fw-bold text-decoration-none">Inicia sesión</a>
                    </p>
                </div>

            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/lucide.min.js"></script>
    <script>
        const formRecuperar = document.getElementById('form-recuperar');
        const alerta = document.getElementById('alerta-recovery');
        const btnSubmit = document.getElementById('btn-submit');
        const pantallaForm = document.getElementById('pantalla-formulario');
        const pantallaRes = document.getElementById('pantalla-resultado');
        const cajaEnlace = document.getElementById('caja-enlace-directo');
        const enlaceRestablecer = document.getElementById('enlace-restablecer');

        if (window.lucide) {
            lucide.createIcons();
        }

        function mostrarAlerta(msg, tipo = 'danger') {
            alerta.className = `alert alert-${tipo} border-0 small rounded-3 p-2 mb-3`;
            alerta.innerHTML = msg;
            alerta.classList.remove('d-none');
        }

        formRecuperar.addEventListener('submit', async (e) => {
            e.preventDefault();
            alerta.classList.add('d-none');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Generando enlace...';

            const email = document.getElementById('email').value.trim();

            try {
                const res = await fetch('api/auth_cliente.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'recuperar',
                        email: email,
                        base_url: window.location.origin
                    })
                });
                const data = await res.json();

                if (data.ok) {
                    pantallaForm.classList.add('d-none');
                    pantallaRes.classList.remove('d-none');
                    document.getElementById('texto-confirmacion').innerText = 
                        `Hemos enviado las instrucciones a ${email}.`;

                    // Mostrar enlace para testing directo
                    if (data.enlace) {
                        enlaceRestablecer.href = data.enlace;
                        enlaceRestablecer.innerText = data.enlace;
                        cajaEnlace.classList.remove('d-none');
                    }
                    if (window.lucide) lucide.createIcons();
                } else {
                    mostrarAlerta('⚠️ ' + data.error, 'danger');
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<i data-lucide="send" style="width:16px;height:16px;"></i> <span>Enviar Enlace</span>';
                    if (window.lucide) lucide.createIcons();
                }
            } catch (err) {
                mostrarAlerta('Error de conexión con el servidor.', 'danger');
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i data-lucide="send" style="width:16px;height:16px;"></i> <span>Enviar Enlace</span>';
                if (window.lucide) lucide.createIcons();
            }
        });
    </script>
</body>
</html>
