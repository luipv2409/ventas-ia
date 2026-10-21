<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$pdo = Database::getConnection();

$draw = $_POST['draw'] ?? 1;
$start = (int)($_POST['start'] ?? 0);
$length = (int)($_POST['length'] ?? 10);
$search = $_POST['search']['value'] ?? '';

$filtros = $_POST['filtros'] ?? [];
$fechaDesde = $filtros['fecha_desde'] ?? '';
$fechaHasta = $filtros['fecha_hasta'] ?? '';
$estado = $filtros['estado'] ?? '';
$clienteId = $filtros['cliente_id'] ?? '';

$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(c.nombre LIKE :busqueda OR c.email LIKE :busqueda2 OR p.id LIKE :busqueda3)";
    $params[':busqueda'] = "%$search%";
    $params[':busqueda2'] = "%$search%";
    $params[':busqueda3'] = "%$search%";
}
if ($fechaDesde !== '') {
    $where[] = "p.creado_en >= :fechaDesde";
    $params[':fechaDesde'] = $fechaDesde . ' 00:00:00';
}
if ($fechaHasta !== '') {
    $where[] = "p.creado_en <= :fechaHasta";
    $params[':fechaHasta'] = $fechaHasta . ' 23:59:59';
}
if ($estado !== '') {
    $where[] = "p.estado = :estado";
    $params[':estado'] = $estado;
}
if ($clienteId !== '') {
    $where[] = "p.cliente_id = :clienteId";
    $params[':clienteId'] = (int)$clienteId;
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$totalRegistros = $pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();

$stmtCount = $pdo->prepare("SELECT COUNT(*) FROM pedidos p JOIN clientes c ON p.cliente_id = c.id $whereSQL");
$stmtCount->execute($params);
$totalFiltrados = $stmtCount->fetchColumn();

$sql = "SELECT p.id, c.nombre AS cliente, c.email, p.total, p.estado, p.creado_en
        FROM pedidos p
        JOIN clientes c ON p.cliente_id = c.id
        $whereSQL
        ORDER BY p.creado_en DESC
        LIMIT $start, $length";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pedidos = $stmt->fetchAll();

$data = [];
foreach ($pedidos as $ped) {
    $data[] = [
        'id' => $ped['id'],
        'cliente' => $ped['cliente'],
        'email' => $ped['email'],
        'total' => $ped['total'],
        'estado' => $ped['estado'],
        'creado_en' => $ped['creado_en'],
    ];
}

echo json_encode([
    'draw' => intval($draw),
    'recordsTotal' => $totalRegistros,
    'recordsFiltered' => $totalFiltrados,
    'data' => $data,
]);