<?php
declare(strict_types=1);

session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    die('Acceso denegado');
}

$archivo = basename($_GET['archivo'] ?? '');
if ($archivo === '') {
    http_response_code(400);
    die('Archivo no especificado');
}

$reportsDir = __DIR__ . '/../storage/reports';
$filepath = $reportsDir . '/' . $archivo;

if (!file_exists($filepath)) {
    http_response_code(404);
    die('Reporte no encontrado');
}

$ext = strtolower(pathinfo($filepath, PATHINFO_EXTENSION));
if ($ext === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
} else {
    header('Content-Type: text/html; charset=utf-8');
}
header('Content-Disposition: attachment; filename="' . $archivo . '"');
header('Content-Length: ' . filesize($filepath));
readfile($filepath);
exit;
