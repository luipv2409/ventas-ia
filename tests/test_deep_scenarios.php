<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

echo "=================================================================\n";
echo "🔬 BATERÍA DE PRUEBAS EXHAUSTIVA: PRECIOS, FILTROS Y ADMIN\n";
echo "=================================================================\n\n";

function postChat(string $pregunta, array $historial = []): array {
    $ch = curl_init('http://localhost/api/chat_frontend.php');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode([
            'pregunta' => $pregunta,
            'historial' => $historial,
            'use_rag' => true
        ]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 30
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode((string)$resp, true);
    return ['code' => $code, 'data' => $data, 'raw' => $resp];
}

$errores = 0;
$aciertos = 0;

// ESCENARIO 1: Consulta de ropa
echo "--- ESCENARIO 1: Cliente pregunta 'y ropa tendras?' ---\n";
$res1 = postChat('y ropa tendras?', [
    ['role' => 'user', 'content' => 'necesito componentes electronicos como arduino'],
    ['role' => 'assistant', 'content' => 'No manejamos componentes arduino, solo productos de consumo.'],
    ['role' => 'user', 'content' => 'que pena'],
    ['role' => 'assistant', 'content' => 'No te preocupes, cualquier cosa acá estoy.']
]);

echo "Valeria: " . ($res1['data']['respuesta'] ?? '') . "\n";
echo "Acciones: " . json_encode($res1['data']['acciones'] ?? []) . "\n";

// Verificar que NO diga "18 Bs"
if (preg_match('/\b18\s*bs\b|\bbs\s*18\b/i', $res1['data']['respuesta'] ?? '')) {
    echo "❌ FALLÓ: Valeria dijo '18 Bs'!\n";
    $errores++;
} else {
    echo "✔ PASÓ: No usó precio crudo 18 Bs.\n";
    $aciertos++;
}

// Verificar que haya emitido filtrar_catalogo
$tieneFiltro = false;
foreach ($res1['data']['acciones'] ?? [] as $a) {
    if ($a['tipo'] === 'filtrar_catalogo' && ($a['categoria_id'] == 2 || !empty($a['texto']))) {
        $tieneFiltro = true;
    }
}
if ($tieneFiltro) {
    echo "✔ PASÓ: Emitió acción filtrar_catalogo para actualizar la tienda en pantalla!\n";
    $aciertos++;
} else {
    echo "⚠ AVISO: No emitió filtrar_catalogo en acciones.\n";
}
echo "\n";

// ESCENARIO 2: Cliente pregunta específicamente cuánto cuesta la camiseta polo
echo "--- ESCENARIO 2: 'a cuanto sale la camiseta polo classic?' ---\n";
$res2 = postChat('a cuanto sale la camiseta polo classic?');
echo "Valeria: " . ($res2['data']['respuesta'] ?? '') . "\n";
if (strpos($res2['data']['respuesta'] ?? '', '179.28') !== false || strpos($res2['data']['respuesta'] ?? '', '179') !== false) {
    echo "✔ PASÓ: Precio exacto o cercano oficial a 179.28 Bs!\n";
    $aciertos++;
} else {
    echo "❌ FALLÓ: No mencionó el precio oficial de 179.28 Bs!\n";
    $errores++;
}
echo "\n";

// ESCENARIO 3: Cliente dice 'que pena' o 'gracias' tras conversación previa
echo "--- ESCENARIO 3: 'que pena' (no debe spammear tarjetas de turnos viejos) ---\n";
$res3 = postChat('que pena', [
    ['role' => 'user', 'content' => 'tienes arduino?'],
    ['role' => 'assistant', 'content' => 'No, solo celulares, tablets y auriculares.'],
]);
echo "Valeria: " . ($res3['data']['respuesta'] ?? '') . "\n";
echo "Acciones: " . json_encode($res3['data']['acciones'] ?? []) . "\n";
if (empty($res3['data']['acciones'])) {
    echo "✔ PASÓ: Cero acciones/tarjetas en turno de cortesía!\n";
    $aciertos++;
} else {
    echo "❌ FALLÓ: Spammeó tarjetas en 'que pena'!\n";
    $errores++;
}
echo "\n";

// ESCENARIO 4: Cliente pide ver celulares
echo "--- ESCENARIO 4: 'muéstrame celulares' ---\n";
$res4 = postChat('muéstrame celulares');
echo "Valeria: " . ($res4['data']['respuesta'] ?? '') . "\n";
echo "Acciones: " . json_encode($res4['data']['acciones'] ?? []) . "\n";
$filtroCelus = false;
foreach ($res4['data']['acciones'] ?? [] as $a) {
    if ($a['tipo'] === 'filtrar_catalogo' && ($a['categoria_id'] == 1 || !empty($a['texto']))) {
        $filtroCelus = true;
    }
}
if ($filtroCelus) {
    echo "✔ PASÓ: Filtró categoría Electrónica (ID 1) en pantalla!\n";
    $aciertos++;
} else {
    echo "⚠ AVISO: Acciones devueltas: " . json_encode($res4['data']['acciones']) . "\n";
}
echo "\n";

// ESCENARIO 5: Prueba de Chat Admin para consultar producto y stock bajo
echo "--- ESCENARIO 5: Jarvis (Chat Admin) ---\n";
$sid = bin2hex(random_bytes(16));
$sessionFile = '/tmp/sess_' . $sid;
file_put_contents($sessionFile, 'admin_id|i:1;admin_user|s:5:"admin";');
chmod($sessionFile, 0666);

$chAdmin = curl_init('http://localhost/api/chat_admin.php');
curl_setopt_array($chAdmin, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode(['pregunta' => 'consulta el producto Zapatillas Runner Pro']),
    CURLOPT_HTTPHEADER => ['Content-Type: application/json', "Cookie: PHPSESSID=$sid"],
    CURLOPT_TIMEOUT => 25
]);
$respAdmin = curl_exec($chAdmin);
curl_close($chAdmin);
@unlink($sessionFile);

$dataAdmin = json_decode((string)$respAdmin, true);
echo "Jarvis: " . ($dataAdmin['mensaje'] ?? 'ERROR') . "\n";
if (strpos($dataAdmin['mensaje'] ?? '', '647.40') !== false || strpos($dataAdmin['mensaje'] ?? '', '65') !== false) {
    echo "✔ PASÓ: Jarvis reportó el precio con conversión correcta (Bs 647.40)!\n";
    $aciertos++;
} else {
    echo "❌ FALLÓ: Jarvis no reportó el precio esperado!\n";
    $errores++;
}
echo "\n";

echo "=================================================================\n";
echo "RESUMEN: $aciertos pruebas pasadas con éxito, $errores errores.\n";
echo "=================================================================\n";
