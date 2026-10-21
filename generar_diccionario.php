<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Incluir database.php que está en la subcarpeta config/
require_once __DIR__ . '/config/database.php';
$pdo = Database::getConnection();

$tablas = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$diccionario = [];

foreach ($tablas as $tabla) {
    $columnas = $pdo->query("DESCRIBE `$tabla`")->fetchAll(PDO::FETCH_ASSOC);
    $diccionario[$tabla] = [];
    foreach ($columnas as $col) {
        $diccionario[$tabla][] = "{$col['Field']} ({$col['Type']})" . ($col['Key'] ? ' ['.$col['Key'].']' : '');
    }
}

$json = json_encode($diccionario, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
// Guardar en la misma carpeta del script (ventas/)
file_put_contents(__DIR__ . '/diccionario_bd.json', $json);
echo "✅ Diccionario generado correctamente en " . __DIR__ . "\\diccionario_bd.json";