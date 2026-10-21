<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

require_once __DIR__ . '/../includes/auth_cliente.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$cliente = $body['cliente'] ?? [];
$carrito = $body['carrito'] ?? [];

if (empty($cliente['nombre']) || empty($carrito)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Faltan datos del cliente o el carrito está vacío.']);
    exit;
}

try {
    $pdo = Database::getConnection();
    $pdo->beginTransaction();

    // 1. Cliente: si está autenticado, reutilizar su ID y actualizar datos faltantes
    $direccion = !empty($cliente['direccion']) ? trim($cliente['direccion']) : null;
    if (!empty($cliente['ciudad'])) {
        $direccion = ($direccion ? $direccion . ' | ' : '') . 'Ciudad: ' . trim($cliente['ciudad']);
    }

    $telefono = !empty($cliente['telefono']) ? trim($cliente['telefono']) : null;
    $email = !empty($cliente['email']) ? strtolower(trim($cliente['email'])) : null;
    $nombre = trim($cliente['nombre']);

    if (cliente_esta_autenticado()) {
        $clienteId = (int)$_SESSION['cliente_id'];
        
        // Actualizar datos del cliente autenticado si faltaban (ej. registro con Google que no tenía teléfono o dirección)
        $stmtUpdateCliente = $pdo->prepare("
            UPDATE clientes SET 
                telefono = COALESCE(NULLIF(?, ''), telefono),
                direccion = COALESCE(NULLIF(?, ''), direccion),
                nombre = COALESCE(NULLIF(?, ''), nombre)
            WHERE id = ?
        ");
        $stmtUpdateCliente->execute([$telefono, $direccion, $nombre, $clienteId]);

        // Actualizar datos en sesión activa
        if (!empty($telefono)) $_SESSION['cliente_telefono'] = $telefono;
        if (!empty($direccion)) $_SESSION['cliente_direccion'] = $direccion;
        if (!empty($nombre)) $_SESSION['cliente_nombre'] = $nombre;
    } else {
        // Modo Invitado (Guest checkout clásico)
        $stmt = $pdo->prepare("INSERT INTO clientes (nombre, email, telefono, direccion) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $nombre,
            $email,
            $telefono,
            $direccion,
        ]);
        $clienteId = (int)$pdo->lastInsertId();
    }

    // 2. Pedido
    $total = 0;
    foreach ($carrito as $item) {
        $total += $item['precio'] * $item['cantidad'];
    }
    $notas = !empty($cliente['notas']) ? trim($cliente['notas']) : null;
    if (!empty($cliente['metodo_pago'])) {
        $notas = ($notas ? $notas . ' | ' : '') . 'Método de pago: ' . trim($cliente['metodo_pago']);
    }
    $stmt = $pdo->prepare("INSERT INTO pedidos (cliente_id, total, estado, notas) VALUES (?, ?, 'pendiente', ?)");
    $stmt->execute([$clienteId, $total, $notas]);
    $pedidoId = (int)$pdo->lastInsertId();

    // 3. Detalle y stock
    $stmtDetalle = $pdo->prepare(
        "INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)"
    );
    $stmtStock = $pdo->prepare(
        "UPDATE productos SET stock = stock - ? WHERE id = ? AND stock >= ?"
    );

    foreach ($carrito as $item) {
        if (!isset($item['id'])) {
            throw new RuntimeException("El producto '{$item['nombre']}' no tiene ID.");
        }
        $subtotal = $item['precio'] * $item['cantidad'];

        $stmtStock->execute([$item['cantidad'], $item['id'], $item['cantidad']]);
        if ($stmtStock->rowCount() === 0) {
            throw new RuntimeException("Stock insuficiente para: " . $item['nombre']);
        }

        $stmtDetalle->execute([
            $pedidoId,
            $item['id'],
            $item['cantidad'],
            $item['precio'],
            $subtotal,
        ]);
    }

    $pdo->commit();
    echo json_encode([
        'ok' => true,
        'mensaje' => "¡Pedido #{$pedidoId} registrado con éxito!",
        'pedido_id' => $pedidoId,
        'total' => $total
    ]);
} catch (RuntimeException $e) {
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => APP_DEBUG ? $e->getMessage() : 'Error interno.']);
}