<?php
// ============================================================
// ARCHIVO: test_conexion.php
// PROPÓSITO: Verificar que la conexión PDO funciona correctamente.
// ⚠️ IMPORTANTE: Eliminar este archivo antes de subir a producción.
// ============================================================

require_once __DIR__ . '/config/database.php';

echo '<h2>🔍 Test de Conexión — Fase 2</h2>';

try {
    // Obtener la instancia Singleton de la BD
    $pdo = Database::getConnection();
    echo '<p style="color:green">✅ Conexión a MySQL exitosa usando PDO.</p>';

    // Verificar que las tablas existen
    $tablas = ['categorias', 'productos', 'clientes', 'pedidos', 'detalle_pedidos', 'chat_logs'];
    echo '<h3>📋 Tablas encontradas en `ventas_ia`:</h3><ul>';
    foreach ($tablas as $tabla) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$tabla'");
        if ($stmt->rowCount() > 0) {
            echo "<li style='color:green'>✅ $tabla</li>";
        } else {
            echo "<li style='color:red'>❌ $tabla — NO encontrada</li>";
        }
    }
    echo '</ul>';

    // Verificar datos semilla
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM productos");
    $row  = $stmt->fetch();
    echo "<p>🛒 Productos en catálogo: <strong>{$row['total']}</strong></p>";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categorias");
    $row  = $stmt->fetch();
    echo "<p>📂 Categorías: <strong>{$row['total']}</strong></p>";

    // Prueba de Singleton: ambas variables deben ser el mismo objeto
    $pdo2 = Database::getConnection();
    echo ($pdo === $pdo2)
        ? '<p style="color:green">✅ Patrón Singleton funcionando: misma instancia reutilizada.</p>'
        : '<p style="color:red">❌ Error: Singleton devolvió instancias distintas.</p>';

} catch (RuntimeException $e) {
    echo '<p style="color:red">❌ ' . htmlspecialchars($e->getMessage()) . '</p>';
}
