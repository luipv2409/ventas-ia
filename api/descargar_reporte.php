<?php
declare(strict_types=1);

require_once __DIR__ . '/../admin/auth.php';

$archivo = (string)($_GET['archivo'] ?? '');
if (!preg_match('/^reporte_[a-z0-9_]{1,40}\.(csv|html)$/', $archivo)) {
    http_response_code(400);
    exit('Nombre de archivo inválido');
}

$reportsDir = realpath(__DIR__ . '/../storage/reports');
if ($reportsDir === false) {
    http_response_code(404);
    exit('Directorio de reportes no disponible');
}

$filepath = realpath($reportsDir . DIRECTORY_SEPARATOR . $archivo);
if ($filepath === false || !str_starts_with($filepath, $reportsDir . DIRECTORY_SEPARATOR) || !is_file($filepath)) {
    http_response_code(404);
    exit('Reporte no encontrado');
}

$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: private, no-store, max-age=0');

if ($ext === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
} else {
    header("Content-Security-Policy: sandbox; default-src 'none'; style-src 'unsafe-inline'");
    header('Content-Type: text/html; charset=utf-8');
}

header('Content-Disposition: attachment; filename="' . $archivo . '"');
header('Content-Length: ' . (string)filesize($filepath));
readfile($filepath);
exit;
