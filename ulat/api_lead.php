<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$nombre   = trim((string)($_POST['nombre'] ?? ''));
$telefono = trim((string)($_POST['telefono'] ?? ''));
$carrera  = trim((string)($_POST['carrera'] ?? ''));
$email    = trim((string)($_POST['email'] ?? ''));
$notas    = trim((string)($_POST['notas'] ?? ''));

if ($nombre === '' || $telefono === '' || $carrera === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Por favor completa tu nombre, teléfono y carrera de interés.']);
    exit;
}

try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("
        INSERT INTO admisiones_prospectos (nombre, telefono, email, carrera_interes, origen, notas)
        VALUES (?, ?, ?, ?, 'formulario_web', ?)
    ");
    $stmt->execute([
        $nombre,
        $telefono,
        $email !== '' ? $email : null,
        $carrera,
        $notas !== '' ? $notas : 'Preinscripción desde el portal web ULAT'
    ]);

    echo json_encode([
        'ok' => true,
        'mensaje' => 'Solicitud registrada correctamente. Un asesor de admisiones te contactará en breve.',
        'whatsapp_directo' => 'https://wa.me/5914509488?text=' . urlencode("Hola ULAT, me llamo {$nombre} y solicito información sobre la carrera de {$carrera}")
    ]);
} catch (\Throwable $e) {
    error_log('[api_lead] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error interno al registrar tu solicitud.']);
}
