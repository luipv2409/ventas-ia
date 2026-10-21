<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "🚀 Verificando esquema de tabla categorias...\n";

$pdo = Database::getConnection();

// 1. Agregar columna icono si no existe
$cols = $pdo->query("SHOW COLUMNS FROM categorias LIKE 'icono'")->fetchAll();
if (empty($cols)) {
    $pdo->exec("ALTER TABLE categorias ADD COLUMN icono VARCHAR(80) DEFAULT 'fas fa-tag' AFTER slug");
    echo "✔ Columna 'icono' agregada a tabla categorias.\n";
} else {
    echo "ℹ Columna 'icono' ya existe.\n";
}

// 2. Asignar iconos por defecto a las categorías existentes
$iconos = [
    1 => 'fas fa-mobile-screen-button', // Celulares
    2 => 'fas fa-tablet-screen-button', // Tablets
    3 => 'fas fa-headphones',           // Audio
    4 => 'fas fa-shoe-prints',          // Zapatillas
    5 => 'fas fa-shirt',                // Poleras
    6 => 'fas fa-blender',              // Electrodomésticos
    7 => 'fas fa-bed',                  // Cama
    8 => 'fas fa-mug-hot'               // Café
];

$stmt = $pdo->prepare("UPDATE categorias SET icono = :ico WHERE id = :id AND (icono IS NULL OR icono = '' OR icono = 'fas fa-tag')");
foreach ($iconos as $id => $ico) {
    $stmt->execute([':ico' => $ico, ':id' => $id]);
}
echo "✔ Iconos predeterminados sincronizados para las categorías existentes.\n";
