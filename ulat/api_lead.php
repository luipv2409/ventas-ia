<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

use Services\Chat\ClientIpResolver;
use Services\Chat\InputSanitizer;
use Services\Chat\RateLimiter;
use Services\Security\SecurityKernel;

SecurityKernel::startSession(false);
SecurityKernel::applySecurityHeaders('api');
header('Content-Type: application/json; charset=utf-8');

SecurityKernel::requireCsrfPost(true);

// Honeypot silencioso contra bots de spam
if (!empty($_POST['website_url'])) {
    echo json_encode([
        'ok'               => true,
        'mensaje'          => 'Solicitud registrada correctamente.',
        'whatsapp_directo' => 'https://wa.me/59179795778',
    ]);
    exit;
}

try {
    $pdo = Database::getConnection();
    $limiter = new RateLimiter($pdo);
    $clientIp = ClientIpResolver::resolve();
    $limit10m = defined('LEAD_RATE_LIMIT_PER_10MIN') ? (int)LEAD_RATE_LIMIT_PER_10MIN : 5;

    $rate = $limiter->hit("ulat_lead:{$clientIp}", $limit10m, 600);
    if (!$rate['allowed']) {
        http_response_code(429);
        header('Retry-After: ' . $rate['retry_after']);
        echo json_encode([
            'ok'    => false,
            'error' => 'Recibimos varias solicitudes desde tu conexión. Intenta de nuevo en unos minutos o escríbenos al WhatsApp 79795778.',
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $nombre         = InputSanitizer::cleanText((string)($_POST['nombre'] ?? ''), 120);
    $telefono       = trim((string)($_POST['telefono'] ?? ''));
    $carrera        = InputSanitizer::cleanText((string)($_POST['carrera'] ?? ''), 140);
    $email          = trim((string)($_POST['email'] ?? ''));
    $notas          = InputSanitizer::cleanText((string)($_POST['notas'] ?? ''), 500);
    $consentimiento = (string)($_POST['consentimiento'] ?? '');

    if ($consentimiento !== '1') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Debes aceptar el tratamiento de datos para contacto de admisiones.']);
        exit;
    }

    if (mb_strlen($nombre) < 3 || $telefono === '' || $carrera === '') {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Por favor completa tu nombre completo, teléfono y carrera de interés.']);
        exit;
    }

    $soloDigitos = preg_replace('/\D/', '', $telefono) ?? '';
    if (!preg_match('/^[0-9+\-\s()]{7,20}$/', $telefono) || strlen($soloDigitos) < 7 || strlen($soloDigitos) > 15) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Ingresa un número telefónico o WhatsApp válido (7 a 15 dígitos).']);
        exit;
    }

    if ($email !== '' && (mb_strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'El correo electrónico proporcionado no es válido.']);
        exit;
    }

    // Validar carrera contra el catálogo oficial ULAT + opciones generales válidas
    $carrerasPermitidas = ['Diplomados y Postgrado', 'Consulta General', 'Orientación Vocacional'];
    $catalogPath = __DIR__ . '/../data/ulat_catalog.json';
    if (is_file($catalogPath)) {
        $catData = json_decode((string)file_get_contents($catalogPath), true) ?: [];
        foreach ($catData as $item) {
            if (!empty($item['nombre'])) {
                $carrerasPermitidas[] = (string)$item['nombre'];
            }
        }
    }

    if (!in_array($carrera, $carrerasPermitidas, true)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Selecciona una carrera o área válida del listado oficial ULAT.']);
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO admisiones_prospectos (nombre, telefono, email, carrera_interes, origen, notas)
        VALUES (:n, :t, :e, :c, 'formulario_web', :notas)
    ");
    $stmt->execute([
        ':n'     => $nombre,
        ':t'     => $telefono,
        ':e'     => $email !== '' ? $email : null,
        ':c'     => $carrera,
        ':notas' => $notas !== '' ? $notas : 'Preinscripción desde el portal web ULAT',
    ]);

    echo json_encode([
        'ok'               => true,
        'mensaje'          => 'Solicitud registrada correctamente. Un asesor de admisiones de la ULAT se comunicará contigo.',
        'whatsapp_directo' => 'https://wa.me/59179795778?text=' . rawurlencode("Hola ULAT, me llamo {$nombre} y solicito información sobre {$carrera}"),
    ], JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    error_log('[api_lead] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno al registrar tu solicitud.']);
}
