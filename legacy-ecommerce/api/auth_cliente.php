<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth_cliente.php';

$metodo = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Si es GET y action es logout
if ($metodo === 'GET' && $action === 'logout') {
    cliente_cerrar_sesion();
    $redirect = $_GET['redirect'] ?? '../index.php';
    header('Location: ' . $redirect);
    exit;
}

// Si es GET y action es me
if ($metodo === 'GET' && $action === 'me') {
    $cliente = cliente_actual();
    echo json_encode([
        'autenticado' => $cliente !== null,
        'cliente' => $cliente
    ]);
    exit;
}

if ($metodo !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;
$action = $input['action'] ?? $action;

try {
    switch ($action) {
        case 'registro':
            $nombre    = $input['nombre'] ?? '';
            $email     = $input['email'] ?? '';
            $password  = $input['password'] ?? '';
            $telefono  = !empty($input['telefono']) ? trim($input['telefono']) : null;
            $direccion = !empty($input['direccion']) ? trim($input['direccion']) : null;

            $res = cliente_registrar($nombre, $email, $password, $telefono, $direccion);
            if (!$res['ok']) {
                http_response_code(400);
            }
            echo json_encode($res);
            break;

        case 'login':
            $email    = $input['email'] ?? '';
            $password = $input['password'] ?? '';

            $res = cliente_login($email, $password);
            if (!$res['ok']) {
                http_response_code(400);
            }
            echo json_encode($res);
            break;

        case 'google_login':
            $credential = $input['credential'] ?? '';
            $googleId   = $input['google_id'] ?? '';
            $email      = $input['email'] ?? '';
            $nombre     = $input['nombre'] ?? '';
            $avatarUrl  = $input['avatar_url'] ?? '';

            // Si se envió un token JWT oficial de Google Identity Services
            if (!empty($credential)) {
                $partes = explode('.', $credential);
                if (count($partes) === 3) {
                    $payloadJson = base64_decode(strtr($partes[1], '-_', '+/'));
                    $payload = json_decode($payloadJson, true);
                    if ($payload && !empty($payload['sub']) && !empty($payload['email'])) {
                        $googleId  = $payload['sub'];
                        $email     = $payload['email'];
                        $nombre    = $payload['name'] ?? explode('@', $email)[0];
                        $avatarUrl = $payload['picture'] ?? null;
                    }
                }
            }

            if (empty($email) || empty($nombre)) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'No se recibieron datos válidos de Google.']);
                exit;
            }

            if (empty($googleId)) {
                $googleId = 'goog_' . md5($email);
            }

            $res = cliente_google_auth($googleId, $email, $nombre, $avatarUrl);
            if (!$res['ok']) {
                http_response_code(400);
            }
            echo json_encode($res);
            break;

        case 'recuperar':
            $email = $input['email'] ?? '';
            $baseUrl = rtrim($input['base_url'] ?? '', '/');

            $res = cliente_solicitar_recuperacion($email, $baseUrl);
            if (!$res['ok']) {
                http_response_code(400);
            }
            echo json_encode($res);
            break;

        case 'restablecer':
            $token    = $input['token'] ?? '';
            $password = $input['password'] ?? '';
            $confirm  = $input['password_confirm'] ?? '';

            if (empty($password) || $password !== $confirm) {
                http_response_code(400);
                echo json_encode(['ok' => false, 'error' => 'Las contraseñas no coinciden o están vacías.']);
                exit;
            }

            $res = cliente_restablecer_password($token, $password);
            if (!$res['ok']) {
                http_response_code(400);
            }
            echo json_encode($res);
            break;

        case 'logout':
            cliente_cerrar_sesion();
            echo json_encode(['ok' => true, 'mensaje' => 'Sesión cerrada correctamente.']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Acción no válida.']);
            break;
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => APP_DEBUG ? $e->getMessage() : 'Error interno en el servidor.'
    ]);
}
