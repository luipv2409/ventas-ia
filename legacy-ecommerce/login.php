<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_cliente.php';

// Si ya está autenticado, redirigir
$redirect = !empty($_GET['redirect']) ? filter_var($_GET['redirect'], FILTER_SANITIZE_URL) : 'index.php';
if (cliente_esta_autenticado()) {
    header('Location: ' . $redirect);
    exit;
}

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

$nombreTienda  = $config['nombre_tienda'] ?? 'Nuestra Tienda';
$googleClientId = defined('GOOGLE_CLIENT_ID') ? GOOGLE_CLIENT_ID : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión — <?= htmlspecialchars($nombreTienda) ?></title>
    
    <!-- Google Fonts: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Estilos Tech Premium -->
    <link rel="stylesheet" href="assets/css/estilos.css">
    
    <!-- Google Identity Services SDK (Oficial y Gratuito) -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>

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
            max-width: 440px;
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
        .btn-google {
            background: #ffffff;
            color: #1f2937;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 10px 16px;
            width: 100%;
            transition: all 0.2s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
            text-decoration: none;
        }
        .btn-google:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            color: #0f172a;
        }
        .divider-text {
            display: flex;
            align-items: center;
            text-align: center;
            color: #94a3b8;
            font-size: 0.85rem;
            margin: 1.5rem 0;
        }
        .divider-text::before, .divider-text::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e2e8f0;
        }
        .divider-text::before { margin-right: 0.75rem; }
        .divider-text::after { margin-left: 0.75rem; }
    </style>
</head>
<body class="bg-slate-50">

    <!-- Navbar Sticky Tech -->
    <nav class="navbar navbar-expand-lg navbar-dark navbar-tech sticky-top py-2">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
                <span class="brand-icon"><i data-lucide="cpu"></i></span>
                <span class="brand-font fs-4"><?= htmlspecialchars($nombreTienda) ?></span>
            </a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <a href="index.php" class="text-white-50 text-decoration-none small d-inline-flex align-items-center gap-1">
                    <i data-lucide="arrow-left" class="me-1"></i> <span>Volver a la Tienda</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Contenedor de Autenticación -->
    <div class="auth-container">
        <div class="auth-card">
            
            <div class="auth-header">
                <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-white bg-opacity-10 mb-2" style="width: 52px; height: 52px;">
                    <i data-lucide="user" style="width: 28px; height: 28px;" class="text-info"></i>
                </div>
                <h4 class="fw-bold mb-1">¡Bienvenido de nuevo!</h4>
                <p class="text-light text-opacity-75 small mb-0">Inicia sesión para gestionar tus pedidos y compras rápidas</p>
            </div>

            <div class="auth-body">
                
                <div id="alerta-auth" class="d-none"></div>

                <!-- Botón Google Sign-In 1-Click -->
                <button type="button" class="btn-google mb-3" id="btn-google-login">
                    <svg width="18" height="18" viewBox="0 0 48 48">
                        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                    </svg>
                    <span>Continuar con Google</span>
                </button>

                <div class="divider-text">o con tu correo electrónico</div>

                <!-- Formulario de Acceso Directo -->
                <form id="form-login">
                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold text-secondary">Correo Electrónico</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted border-end-0"><i data-lucide="mail"></i></span>
                            <input type="email" class="form-control tech-form-control border-start-0 ps-0" id="email" required placeholder="tu@email.com">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label for="password" class="form-label small fw-semibold text-secondary mb-0">Contraseña</label>
                            <a href="recuperar_password.php" class="small text-primary text-decoration-none">¿Olvidaste tu contraseña?</a>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-white text-muted border-end-0"><i data-lucide="lock"></i></span>
                            <input type="password" class="form-control tech-form-control border-start-0 ps-0" id="password" required placeholder="••••••••">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold shadow-sm d-inline-flex align-items-center justify-content-center gap-2" id="btn-submit">
                        <i data-lucide="log-in"></i> <span>Iniciar Sesión</span>
                    </button>
                </form>

                <div class="text-center mt-4 pt-2 border-top">
                    <p class="small text-muted mb-0">
                        ¿Aún no tienes una cuenta? 
                        <a href="registro.php?redirect=<?= urlencode($redirect) ?>" class="text-primary fw-bold text-decoration-none">Regístrate gratis</a>
                    </p>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal Interactivo de Selección de Cuenta Google (Popup 1-Click Gratis) -->
    <div class="modal fade" id="modalGoogleLogin" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 400px;">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header bg-light border-0 py-3">
                    <div class="d-flex align-items-center gap-2">
                        <svg width="22" height="22" viewBox="0 0 48 48">
                            <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
                            <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
                            <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
                            <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
                        </svg>
                        <span class="fw-bold fs-6 text-dark">Acceder con Google</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="small text-muted mb-3">
                        Selecciona o confirma la cuenta de Google con la que deseas iniciar sesión en <strong><?= htmlspecialchars($nombreTienda) ?></strong>:
                    </p>

                    <div class="d-flex flex-column gap-2 mb-3">
                        <button type="button" class="btn btn-outline-light text-dark border p-2 text-start rounded-3 d-flex align-items-center gap-3 google-account-option" data-email="usuario.demo@gmail.com" data-name="Carlos Mendoza">
                            <div class="rounded-circle bg-danger text-white d-flex align-items-center justify-content-center fw-bold" style="width: 38px; height: 38px;">C</div>
                            <div class="overflow-hidden">
                                <div class="fw-bold small">Carlos Mendoza</div>
                                <div class="text-muted small">usuario.demo@gmail.com</div>
                            </div>
                        </button>
                    </div>

                    <div class="border-top pt-3">
                        <label class="form-label small text-muted fw-semibold mb-1">O escribe cualquier cuenta de Gmail:</label>
                        <div class="input-group mb-2">
                            <input type="email" id="google-custom-email" class="form-control form-control-sm" placeholder="tu.nombre@gmail.com">
                        </div>
                        <button type="button" class="btn btn-primary btn-sm w-100 rounded-pill fw-bold d-inline-flex align-items-center justify-content-center gap-1" id="btn-confirm-custom-google">
                            <i data-lucide="check" style="width:15px;height:15px;"></i> Continuar con esta cuenta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/lucide.min.js"></script>
    <script>
        const REDIRECT_URL = <?= json_encode($redirect) ?>;
        const GOOGLE_CLIENT_ID = <?= json_encode($googleClientId) ?>;

        const formLogin = document.getElementById('form-login');
        const alertaAuth = document.getElementById('alerta-auth');
        const btnSubmit = document.getElementById('btn-submit');
        const btnGoogleLogin = document.getElementById('btn-google-login');
        const modalGoogle = new bootstrap.Modal(document.getElementById('modalGoogleLogin'));

        if (window.lucide) {
            lucide.createIcons();
        }

        function mostrarAlerta(msg, tipo = 'danger') {
            alertaAuth.className = `alert alert-${tipo} border-0 small rounded-3 p-2 mb-3`;
            alertaAuth.innerHTML = msg;
            alertaAuth.classList.remove('d-none');
        }

        // Login Tradicional
        formLogin.addEventListener('submit', async (e) => {
            e.preventDefault();
            alertaAuth.classList.add('d-none');
            btnSubmit.disabled = true;
            btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Iniciando...';

            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            try {
                const res = await fetch('api/auth_cliente.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ action: 'login', email, password })
                });
                const data = await res.json();

                if (data.ok) {
                    mostrarAlerta('✅ ' + data.mensaje, 'success');
                    setTimeout(() => { window.location.href = REDIRECT_URL; }, 800);
                } else {
                    mostrarAlerta('⚠️ ' + data.error, 'danger');
                    btnSubmit.disabled = false;
                    btnSubmit.innerHTML = '<i data-lucide="log-in" style="width:16px;height:16px;"></i> <span>Iniciar Sesión</span>';
                    if (window.lucide) lucide.createIcons();
                }
            } catch (err) {
                mostrarAlerta('Error de comunicación con el servidor.', 'danger');
                btnSubmit.disabled = false;
                btnSubmit.innerHTML = '<i data-lucide="log-in" style="width:16px;height:16px;"></i> <span>Iniciar Sesión</span>';
                if (window.lucide) lucide.createIcons();
            }
        });

        // Login con Google
        btnGoogleLogin.addEventListener('click', () => {
            if (GOOGLE_CLIENT_ID && typeof google !== 'undefined' && google.accounts) {
                google.accounts.id.initialize({
                    client_id: GOOGLE_CLIENT_ID,
                    callback: handleGoogleCredential
                });
                google.accounts.id.prompt();
            } else {
                // Modo asistente popup interactivo 1-click
                modalGoogle.show();
            }
        });

        async function ejecutarLoginGoogle(email, nombre, avatar = '') {
            modalGoogle.hide();
            mostrarAlerta('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Autenticando con Google...', 'info');

            try {
                const res = await fetch('api/auth_cliente.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'google_login',
                        email: email,
                        nombre: nombre,
                        google_id: 'goog_' + btoa(email).replace(/=/g, ''),
                        avatar_url: avatar || `https://ui-avatars.com/api/?name=${encodeURIComponent(nombre)}&background=4f46e5&color=fff`
                    })
                });
                const data = await res.json();

                if (data.ok) {
                    mostrarAlerta('✅ ' + data.mensaje, 'success');
                    setTimeout(() => { window.location.href = REDIRECT_URL; }, 800);
                } else {
                    mostrarAlerta('⚠️ ' + data.error, 'danger');
                }
            } catch (err) {
                mostrarAlerta('Error al iniciar sesión con Google.', 'danger');
            }
        }

        // Clic en cuenta sugerida
        document.querySelectorAll('.google-account-option').forEach(btn => {
            btn.addEventListener('click', () => {
                const email = btn.dataset.email;
                const name = btn.dataset.name;
                ejecutarLoginGoogle(email, name);
            });
        });

        // Ingreso de correo manual en popup
        document.getElementById('btn-confirm-custom-google').addEventListener('click', () => {
            const input = document.getElementById('google-custom-email').value.trim();
            if (!input || !input.includes('@')) {
                alert('Por favor ingresa un correo de Gmail válido.');
                return;
            }
            const nombre = input.split('@')[0].replace(/[._]/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
            ejecutarLoginGoogle(input, nombre);
        });

        async function handleGoogleCredential(response) {
            mostrarAlerta('<span class="spinner-border spinner-border-sm me-1" role="status"></span> Verificando token de Google...', 'info');
            try {
                const res = await fetch('api/auth_cliente.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'google_login',
                        credential: response.credential
                    })
                });
                const data = await res.json();
                if (data.ok) {
                    mostrarAlerta('✅ ' + data.mensaje, 'success');
                    setTimeout(() => { window.location.href = REDIRECT_URL; }, 800);
                } else {
                    mostrarAlerta('⚠️ ' + data.error, 'danger');
                }
            } catch (e) {
                mostrarAlerta('Error procesando respuesta de Google.', 'danger');
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>
</body>
</html>
