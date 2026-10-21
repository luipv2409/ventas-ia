<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Inicializa la sesión si no ha sido iniciada.
 */
function cliente_session_init(): void {
    if (session_status() === PHP_SESSION_NONE) {
        if (!headers_sent()) {
            session_start();
        } else {
            @session_start();
        }
    }
}

/**
 * Verifica si hay un cliente autenticado actualmente.
 */
function cliente_esta_autenticado(): bool {
    cliente_session_init();
    return !empty($_SESSION['cliente_id']);
}

/**
 * Retorna los datos del cliente autenticado o null.
 */
function cliente_actual(): ?array {
    cliente_session_init();
    if (!cliente_esta_autenticado()) {
        return null;
    }

    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT id, nombre, email, telefono, direccion, avatar_url, google_id, creado_en FROM clientes WHERE id = ? AND activo = 1 LIMIT 1");
        $stmt->execute([(int)$_SESSION['cliente_id']]);
        $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cliente) {
            // Sincronizar sesión con datos actualizados
            $_SESSION['cliente_nombre'] = $cliente['nombre'];
            $_SESSION['cliente_email'] = $cliente['email'];
            $_SESSION['cliente_telefono'] = $cliente['telefono'] ?? '';
            $_SESSION['cliente_direccion'] = $cliente['direccion'] ?? '';
            $_SESSION['cliente_avatar'] = $cliente['avatar_url'] ?? '';
            return $cliente;
        } else {
            // Usuario inactivo o eliminado
            cliente_cerrar_sesion();
            return null;
        }
    } catch (Exception $e) {
        return [
            'id' => $_SESSION['cliente_id'],
            'nombre' => $_SESSION['cliente_nombre'] ?? 'Cliente',
            'email' => $_SESSION['cliente_email'] ?? '',
            'telefono' => $_SESSION['cliente_telefono'] ?? '',
            'direccion' => $_SESSION['cliente_direccion'] ?? '',
            'avatar_url' => $_SESSION['cliente_avatar'] ?? '',
        ];
    }
}

/**
 * Establece los datos del cliente en la sesión.
 */
function cliente_iniciar_sesion(array $cliente): void {
    cliente_session_init();
    $_SESSION['cliente_id'] = (int)$cliente['id'];
    $_SESSION['cliente_nombre'] = $cliente['nombre'];
    $_SESSION['cliente_email'] = $cliente['email'] ?? '';
    $_SESSION['cliente_telefono'] = $cliente['telefono'] ?? '';
    $_SESSION['cliente_direccion'] = $cliente['direccion'] ?? '';
    $_SESSION['cliente_avatar'] = $cliente['avatar_url'] ?? '';

    // Actualizar último login
    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE clientes SET ultimo_login = NOW() WHERE id = ?");
        $stmt->execute([(int)$cliente['id']]);
    } catch (Exception $e) {
        // Silencioso
    }
}

/**
 * Cierra la sesión del cliente sin afectar la del admin si existiera.
 */
function cliente_cerrar_sesion(): void {
    cliente_session_init();
    unset(
        $_SESSION['cliente_id'],
        $_SESSION['cliente_nombre'],
        $_SESSION['cliente_email'],
        $_SESSION['cliente_telefono'],
        $_SESSION['cliente_direccion'],
        $_SESSION['cliente_avatar']
    );
}

/**
 * Registra un nuevo cliente con correo y contraseña.
 */
function cliente_registrar(
    string $nombre,
    string $email,
    string $password,
    ?string $telefono = null,
    ?string $direccion = null
): array {
    $nombre = trim($nombre);
    $email = strtolower(trim($email));

    if (empty($nombre)) {
        return ['ok' => false, 'error' => 'Por favor, ingresa tu nombre completo.'];
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Por favor, ingresa un correo electrónico válido.'];
    }

    if (strlen($password) < 6) {
        return ['ok' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres.'];
    }

    $pdo = Database::getConnection();

    // Comprobar si ya existe un cliente con este email
    $stmt = $pdo->prepare("SELECT id, nombre, email, password_hash, activo FROM clientes WHERE LOWER(email) = ? LIMIT 1");
    $stmt->execute([$email]);
    $existente = $stmt->fetch(PDO::FETCH_ASSOC);

    $hash = password_hash($password, PASSWORD_DEFAULT);

    if ($existente) {
        if (!empty($existente['password_hash'])) {
            return ['ok' => false, 'error' => 'Ya existe una cuenta con este correo electrónico. Por favor, inicia sesión.'];
        }

        // Si ya existía por una compra previa como invitado sin contraseña, activamos su cuenta
        $update = $pdo->prepare("UPDATE clientes SET nombre = ?, password_hash = ?, telefono = COALESCE(NULLIF(?, ''), telefono), direccion = COALESCE(NULLIF(?, ''), direccion), activo = 1, ultimo_login = NOW() WHERE id = ?");
        $update->execute([$nombre, $hash, $telefono, $direccion, (int)$existente['id']]);

        $clienteId = (int)$existente['id'];
    } else {
        $insert = $pdo->prepare("INSERT INTO clientes (nombre, email, password_hash, telefono, direccion, activo, ultimo_login) VALUES (?, ?, ?, ?, ?, 1, NOW())");
        $insert->execute([$nombre, $email, $hash, $telefono, $direccion]);
        $clienteId = (int)$pdo->lastInsertId();
    }

    // Cargar y loguear cliente
    $stmt = $pdo->prepare("SELECT id, nombre, email, telefono, direccion, avatar_url FROM clientes WHERE id = ?");
    $stmt->execute([$clienteId]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    cliente_iniciar_sesion($cliente);

    return ['ok' => true, 'mensaje' => '¡Cuenta creada con éxito!', 'cliente' => $cliente];
}

/**
 * Autentica un cliente con email y contraseña.
 */
function cliente_login(string $email, string $password): array {
    $email = strtolower(trim($email));

    if (empty($email) || empty($password)) {
        return ['ok' => false, 'error' => 'Ingresa tu correo y contraseña.'];
    }

    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE LOWER(email) = ? LIMIT 1");
    $stmt->execute([$email]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        return ['ok' => false, 'error' => 'No encontramos una cuenta con ese correo electrónico.'];
    }

    if ((int)($cliente['activo'] ?? 1) === 0) {
        return ['ok' => false, 'error' => 'Tu cuenta ha sido desactivada. Comunícate con atención al cliente.'];
    }

    if (empty($cliente['password_hash'])) {
        if (!empty($cliente['google_id'])) {
            return ['ok' => false, 'error' => 'Esta cuenta se creó con Google. Utiliza el botón "Continuar con Google".'];
        }
        return ['ok' => false, 'error' => 'Tu cuenta no tiene contraseña asignada (compra previa como invitado). Utiliza "Recuperar Contraseña" para crear una.'];
    }

    if (!password_verify($password, $cliente['password_hash'])) {
        return ['ok' => false, 'error' => 'Contraseña incorrecta. Por favor, verifica tus datos.'];
    }

    cliente_iniciar_sesion($cliente);

    return ['ok' => true, 'mensaje' => '¡Bienvenido de nuevo, ' . htmlspecialchars($cliente['nombre']) . '!', 'cliente' => $cliente];
}

/**
 * Autenticación o registro con Google OAuth / GIS.
 */
function cliente_google_auth(string $googleId, string $email, string $nombre, ?string $avatarUrl = null): array {
    $googleId = trim($googleId);
    $email = strtolower(trim($email));
    $nombre = trim($nombre);

    if (empty($email) || empty($nombre)) {
        return ['ok' => false, 'error' => 'Datos de Google incompletos.'];
    }

    $pdo = Database::getConnection();

    // 1. Buscar por google_id o por email
    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE google_id = ? OR LOWER(email) = ? LIMIT 1");
    $stmt->execute([$googleId, $email]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente) {
        // Actualizar datos de Google si cambiaron
        $stmtUpdate = $pdo->prepare("UPDATE clientes SET google_id = COALESCE(google_id, ?), avatar_url = COALESCE(?, avatar_url), ultimo_login = NOW() WHERE id = ?");
        $stmtUpdate->execute([$googleId, $avatarUrl, (int)$cliente['id']]);
        $clienteId = (int)$cliente['id'];
    } else {
        // Crear nuevo cliente desde Google
        $stmtInsert = $pdo->prepare("INSERT INTO clientes (nombre, email, google_id, avatar_url, activo, ultimo_login) VALUES (?, ?, ?, ?, 1, NOW())");
        $stmtInsert->execute([$nombre, $email, $googleId, $avatarUrl]);
        $clienteId = (int)$pdo->lastInsertId();
    }

    $stmt = $pdo->prepare("SELECT id, nombre, email, telefono, direccion, avatar_url FROM clientes WHERE id = ?");
    $stmt->execute([$clienteId]);
    $clienteData = $stmt->fetch(PDO::FETCH_ASSOC);

    cliente_iniciar_sesion($clienteData);

    return [
        'ok' => true,
        'mensaje' => '¡Sesión iniciada con Google con éxito!',
        'cliente' => $clienteData,
        'falta_datos' => empty($clienteData['telefono']) || empty($clienteData['direccion'])
    ];
}

/**
 * Solicita restablecimiento de contraseña generando un token seguro.
 */
function cliente_solicitar_recuperacion(string $email, string $baseUrl = ''): array {
    $email = strtolower(trim($email));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Ingresa un correo electrónico válido.'];
    }

    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT id, nombre, email FROM clientes WHERE LOWER(email) = ? AND activo = 1 LIMIT 1");
    $stmt->execute([$email]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        return ['ok' => false, 'error' => 'No encontramos ninguna cuenta registrada con este correo.'];
    }

    // Generar token criptográficamente seguro
    $token = bin2hex(random_bytes(32));

    $stmtUpdate = $pdo->prepare("UPDATE clientes SET token_recuperacion = ?, token_expira = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?");
    $stmtUpdate->execute([$token, (int)$cliente['id']]);

    // Construir enlace
    if (empty($baseUrl)) {
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8085';
        $baseUrl = "{$scheme}://{$host}";
    }
    // Determinar la ruta relativa base
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
    // Si scriptDir termina en /api, quitarlo
    if (preg_match('#/api$#i', $scriptDir)) {
        $scriptDir = substr($scriptDir, 0, -4);
    }
    $enlace = "{$baseUrl}{$scriptDir}/restablecer_password.php?token={$token}";

    // Intentar envío de correo HTML
    $asunto = "Recuperación de contraseña — " . APP_NAME;
    $mensajeHtml = "
    <html>
    <head><title>Recuperar Contraseña</title></head>
    <body style='font-family: Arial, sans-serif; background: #f8fafc; padding: 20px;'>
        <div style='max-width: 500px; margin: 0 auto; background: #ffffff; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0;'>
            <h2 style='color: #4f46e5; margin-top: 0;'>Recuperación de Contraseña</h2>
            <p>Hola <strong>" . htmlspecialchars($cliente['nombre']) . "</strong>,</p>
            <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en <strong>" . htmlspecialchars(APP_NAME) . "</strong>.</p>
            <p style='text-align: center; margin: 30px 0;'>
                <a href='{$enlace}' style='background: #4f46e5; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold; display: inline-block;'>
                    Restablecer mi Contraseña
                </a>
            </p>
            <p style='font-size: 13px; color: #64748b;'>Este enlace es válido durante los próximos 60 minutos. Si no solicitaste este cambio, puedes ignorar este correo de forma segura.</p>
            <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
            <p style='font-size: 11px; color: #94a3b8;'>Si tienes problemas con el botón, copia y pega este enlace en tu navegador:<br><a href='{$enlace}'>{$enlace}</a></p>
        </div>
    </body>
    </html>";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . APP_NAME . " <no-reply@" . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ">\r\n";

    $mailEnviado = false;
    try {
        // En Windows / Docker local sendmail puede no estar configurado, usamos @mail para no romper
        $mailEnviado = @mail($email, $asunto, $mensajeHtml, $headers);
    } catch (Throwable $t) {
        $mailEnviado = false;
    }

    return [
        'ok' => true,
        'mensaje' => 'Hemos generado las instrucciones para restablecer tu contraseña.',
        'email' => $email,
        'token' => $token,
        'enlace' => $enlace,
        'mail_enviado' => $mailEnviado
    ];
}

/**
 * Restablece la contraseña usando el token generado.
 */
function cliente_restablecer_password(string $token, string $nuevaPassword): array {
    $token = trim($token);

    if (empty($token)) {
        return ['ok' => false, 'error' => 'Token de recuperación inválido o vacío.'];
    }

    if (strlen($nuevaPassword) < 6) {
        return ['ok' => false, 'error' => 'La contraseña debe tener al menos 6 caracteres.'];
    }

    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT id, nombre, email FROM clientes WHERE token_recuperacion = ? AND token_expira > NOW() AND activo = 1 LIMIT 1");
    $stmt->execute([$token]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$cliente) {
        return ['ok' => false, 'error' => 'El enlace de restablecimiento ha expirado o ya fue utilizado. Solicita uno nuevo.'];
    }

    $hash = password_hash($nuevaPassword, PASSWORD_DEFAULT);

    $stmtUpdate = $pdo->prepare("UPDATE clientes SET password_hash = ?, token_recuperacion = NULL, token_expira = NULL WHERE id = ?");
    $stmtUpdate->execute([$hash, (int)$cliente['id']]);

    return [
        'ok' => true,
        'mensaje' => '¡Tu contraseña ha sido actualizada con éxito! Ya puedes iniciar sesión.'
    ];
}
