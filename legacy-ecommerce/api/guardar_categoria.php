<?php
declare(strict_types=1);

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Método no permitido. Use POST.']);
    exit;
}

try {
    $pdo = Database::getConnection();

    // Obtener datos (soporta tanto JSON como multipart/x-www-form-urlencoded)
    $input = json_decode(file_get_contents('php://input'), true);
    $nombre = trim($input['nombre'] ?? $_POST['nombre'] ?? '');
    $descripcion = trim($input['descripcion'] ?? $_POST['descripcion'] ?? '');
    $slug = trim($input['slug'] ?? $_POST['slug'] ?? '');
    $icono = trim($input['icono'] ?? $_POST['icono'] ?? 'fas fa-tag');
    $activa = isset($input['activa']) ? (int)$input['activa'] : (isset($_POST['activa']) ? 1 : 1);

    if ($nombre === '') {
        echo json_encode(['success' => false, 'error' => 'El nombre de la categoría es obligatorio.']);
        exit;
    }

    // Generar slug si no se proveyó
    if ($slug === '') {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $nombre), '-'));
        if ($slug === '') {
            $slug = 'cat-' . substr(md5(uniqid()), 0, 6);
        }
    }

    // Verificar si ya existe una categoría con ese nombre o slug
    $stmtCheck = $pdo->prepare("SELECT id FROM categorias WHERE nombre = :nom OR slug = :slug");
    $stmtCheck->execute([':nom' => $nombre, ':slug' => $slug]);
    $existente = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if ($existente) {
        echo json_encode([
            'success' => false, 
            'error' => "Ya existe una categoría con el nombre '{$nombre}' o slug '{$slug}' (ID: {$existente['id']})."
        ]);
        exit;
    }

    // Insertar nueva categoría
    $stmtIns = $pdo->prepare("
        INSERT INTO categorias (nombre, slug, descripcion, icono, activa) 
        VALUES (:nom, :slug, :des, :ico, :act)
    ");
    $stmtIns->execute([
        ':nom'  => $nombre,
        ':slug' => $slug,
        ':des'  => $descripcion ?: null,
        ':ico'  => $icono ?: 'fas fa-tag',
        ':act'  => $activa
    ]);

    $nuevoId = (int)$pdo->lastInsertId();

    echo json_encode([
        'success' => true,
        'mensaje' => "Categoría '{$nombre}' creada exitosamente.",
        'categoria' => [
            'id' => $nuevoId,
            'nombre' => $nombre,
            'slug' => $slug,
            'descripcion' => $descripcion,
            'icono' => $icono,
            'activa' => $activa
        ]
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Error interno al guardar categoría: ' . $e->getMessage()
    ]);
}
