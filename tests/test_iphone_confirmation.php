<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=================================================================\n";
echo "🔬 PRUEBA DE REGRESIÓN: CONFIRMACIÓN DE COMPRA IPHONE ('SI')\n";
echo "=================================================================\n\n";

$url = 'http://localhost/api/chat_frontend.php';
$fp = @fsockopen('localhost', 8085, $errno, $errstr, 0.3);
if ($fp) {
    fclose($fp);
    $url = 'http://localhost:8085/api/chat_frontend.php';
}

// Historial idéntico al de la conversación real del usuario donde falló la adición del iPhone
$historial = [
    ['role' => 'user', 'content' => 'hola'],
    ['role' => 'assistant', 'content' => '¡Hola! ¿Cómo estás? Cuéntame, ¿qué andas buscando hoy?'],
    ['role' => 'user', 'content' => 'iphones'],
    ['role' => 'assistant', 'content' => 'Mira, de iPhone tenemos el 17 Pro Max, el buque insignia de Apple, a Bs 18000.00. ¿Te lo muestro?'],
    ['role' => 'user', 'content' => 'no tendras el iphone plegable?'],
    ['role' => 'assistant', 'content' => 'Mira, iPhone plegable como tal no manejamos, no existe en el catálogo. Lo más top que tenemos es el 17 Pro Max. ¿Te interesa que te lo muestre?'],
    ['role' => 'user', 'content' => 'si, me indicas las caracteristicas?'],
    ['role' => 'assistant', 'content' => 'Claro, te cuento. Tiene pantalla OLED de 6.9" con 120Hz, chip A19 Pro con 12 GB de RAM, cámara triple de 48 MP con teleobjetivo 5x, batería de 4850 mAh con carga rápida 35W y chasis de titanio. Viene en 256 GB, 512 GB o 1 TB. ¿Te lo agrego al carrito?']
];

$pregunta = 'si';

$payload = json_encode([
    'pregunta'  => $pregunta,
    'historial' => $historial,
    'use_rag'   => true
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $payload,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 25
]);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200 || !$resp) {
    echo "❌ Error HTTP $httpCode\n";
    exit(1);
}

$data = json_decode((string)$resp, true);
$respuesta = trim($data['respuesta'] ?? '');
$acciones = $data['acciones'] ?? [];

echo "👤 Cliente: '$pregunta'\n";
echo "🤖 Valeria: \"$respuesta\"\n";
echo "📦 Acciones: " . json_encode($acciones, JSON_UNESCAPED_UNICODE) . "\n\n";

$encontroIphoneEnCarrito = false;
foreach ($acciones as $acc) {
    if (($acc['tipo'] ?? '') === 'agregar_carrito' && mb_stripos($acc['nombre'] ?? '', 'iPhone 17 Pro Max') !== false) {
        $encontroIphoneEnCarrito = true;
        echo "✅ ÉXITO TOTAL: Acción 'agregar_carrito' generada para: '{$acc['nombre']}' con precio Bs " . number_format((float)$acc['precio'], 2) . "\n";
        break;
    }
}

if (!$encontroIphoneEnCarrito) {
    echo "❌ ERROR: No se emitió la acción 'agregar_carrito' para el iPhone 17 Pro Max tras confirmar 'si'.\n";
    exit(1);
}

echo "=================================================================\n";
echo "🎉 PRUEBA DE CONFIRMACIÓN IPHONE PASÓ AL 100%!\n";
echo "=================================================================\n";
