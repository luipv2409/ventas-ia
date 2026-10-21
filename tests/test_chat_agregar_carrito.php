<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=================================================================\n";
echo "🔬 PRUEBA: AGREGAR AL CARRITO DESDE EL CHAT Y PERSISTENCIA\n";
echo "=================================================================\n\n";

// 1. Simular petición al endpoint chat_frontend.php pidiendo agregar al carrito
$casos = [
    'agrega al carrito el iPhone 17 Pro Max',
    'porfa añade la camiseta polo classic al carrito',
    'agrega una zapatillas runner pro al carrito'
];

foreach ($casos as $idx => $pregunta) {
    echo "--- Caso " . ($idx + 1) . ": '{$pregunta}' ---\n";
    
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $payload = json_encode([
        'pregunta' => $pregunta,
        'historial' => [],
        'use_rag' => true
    ]);
    
    // Determinar URL del endpoint (puerto 80 dentro del contenedor, 8085 en host)
    $url = 'http://localhost/api/chat_frontend.php';
    $fp = @fsockopen('localhost', 8085, $errno, $errstr, 0.3);
    if ($fp) {
        fclose($fp);
        $url = 'http://localhost:8085/api/chat_frontend.php';
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT        => 20
    ]);
    
    $respRaw = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$respRaw) {
        echo "❌ ERROR: Código HTTP {$httpCode} o respuesta vacía.\n";
        exit(1);
    }
    
    $data = json_decode($respRaw, true);
    if (empty($data['ok'])) {
        echo "❌ ERROR: Respuesta no contiene ok=true.\n";
        exit(1);
    }
    
    $acciones = $data['acciones'] ?? [];
    $tieneAgregarCarrito = false;
    $productoAgregado = null;
    
    foreach ($acciones as $acc) {
        if ($acc['tipo'] === 'agregar_carrito') {
            $tieneAgregarCarrito = true;
            $productoAgregado = $acc;
            break;
        }
    }
    
    if ($tieneAgregarCarrito && $productoAgregado) {
        echo "  ✔ PASÓ: Acción 'agregar_carrito' generada con éxito!\n";
        echo "    - ID: {$productoAgregado['id']}\n";
        echo "    - Nombre: {$productoAgregado['nombre']}\n";
        echo "    - Precio Oficial: Bs " . number_format((float)$productoAgregado['precio'], 2) . "\n";
        echo "    - Stock: {$productoAgregado['stock']}\n";
    } else {
        echo "❌ FALLÓ: No se emitió la acción 'agregar_carrito' para '{$pregunta}'.\n";
        echo "  Acciones recibidas: " . json_encode($acciones, JSON_UNESCAPED_UNICODE) . "\n";
        echo "  Respuesta IA: " . ($data['respuesta'] ?? '') . "\n";
        exit(1);
    }
    echo "\n";
}

// 2. Verificar que chat_widget.php está incluido en las 4 vistas del cliente
echo "--- 2. Verificación de Presencia del Chat Widget en Vistas del Cliente ---\n";
$archivos = [
    'index.php'      => __DIR__ . '/../index.php',
    'carrito.php'    => __DIR__ . '/../carrito.php',
    'comparador.php' => __DIR__ . '/../comparador.php',
    'checkout.php'   => __DIR__ . '/../checkout.php'
];

foreach ($archivos as $nombre => $ruta) {
    $contenido = file_get_contents($ruta);
    if (str_contains($contenido, 'includes/chat_widget.php') && str_contains($contenido, 'assets/js/chat.js')) {
        echo "  ✔ {$nombre}: Contiene widget de chat y script chat.js.\n";
    } else {
        echo "❌ ERROR: {$nombre} NO contiene includes/chat_widget.php o chat.js!\n";
        exit(1);
    }
}

// 3. Verificar persistencia localStorage en assets/js/chat.js
echo "\n--- 3. Verificación de Persistencia LocalStorage en chat.js ---\n";
$chatJs = file_get_contents(__DIR__ . '/../assets/js/chat.js');
if (str_contains($chatJs, 'valeria_chat_historial_v2') &&
    str_contains($chatJs, 'valeria_chat_abierto_v2') &&
    str_contains($chatJs, 'cargarHistorialGuardado') &&
    str_contains($chatJs, 'guardarMensajeEnStorage')) {
    echo "  ✔ chat.js contiene persistencia completa de historial y estado de ventana.\n";
} else {
    echo "❌ ERROR: Falta lógica de persistencia en chat.js.\n";
    exit(1);
}

// 4. Verificar sincronización de vista en carrito.php
if (str_contains($chatJs, 'renderizarVistaCarrito')) {
    echo "  ✔ chat.js sincroniza en vivo renderizarVistaCarrito al agregar desde cualquier turno.\n";
} else {
    echo "❌ ERROR: chat.js no llama a renderizarVistaCarrito.\n";
    exit(1);
}

echo "\n=================================================================\n";
echo "🎉 TODAS LAS VERIFICACIONES DE CARRITO Y CHAT PASARON CON ÉXITO!\n";
echo "=================================================================\n";
