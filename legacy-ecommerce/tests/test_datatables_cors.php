<?php
declare(strict_types=1);

echo "=== TEST: VERIFICACIÓN LOCALIZACIÓN DATATABLES Y CERO BLOQUEO CORS ===" . PHP_EOL;

$errores = 0;

// 1. Verificar archivos estáticos locales
$jsFile = __DIR__ . '/../assets/js/datatables-es.js';
$jsonFile = __DIR__ . '/../assets/js/datatables-es.json';

if (file_exists($jsFile)) {
    $jsContenido = file_get_contents($jsFile);
    if (strpos($jsContenido, 'DATATABLES_ESPANOL') !== false && strpos($jsContenido, 'Buscar:') !== false) {
        echo "  ✔ datatables-es.js existe con diccionario en español válido." . PHP_EOL;
    } else {
        echo "  ❌ datatables-es.js no contiene la estructura esperada." . PHP_EOL;
        $errores++;
    }
} else {
    echo "  ❌ No se encontró assets/js/datatables-es.js" . PHP_EOL;
    $errores++;
}

if (file_exists($jsonFile)) {
    $json = json_decode(file_get_contents($jsonFile), true);
    if (is_array($json) && isset($json['search']) && isset($json['paginate'])) {
        echo "  ✔ datatables-es.json existe y es un JSON válido." . PHP_EOL;
    } else {
        echo "  ❌ datatables-es.json tiene formato JSON inválido." . PHP_EOL;
        $errores++;
    }
} else {
    echo "  ❌ No se encontró assets/js/datatables-es.json" . PHP_EOL;
    $errores++;
}

// 2. Verificar admin/alertas.php
$alertasPhp = file_get_contents(__DIR__ . '/../admin/alertas.php');
if (strpos($alertasPhp, 'cdn.datatables.net/plug-ins') !== false) {
    echo "  ❌ admin/alertas.php aún contiene llamada externa a cdn.datatables.net/plug-ins!" . PHP_EOL;
    $errores++;
} else {
    echo "  ✔ admin/alertas.php libre de URLs externas a CDN de idioma." . PHP_EOL;
}
if (strpos($alertasPhp, 'datatables-es.js') !== false && strpos($alertasPhp, 'DATATABLES_ESPANOL') !== false) {
    echo "  ✔ admin/alertas.php utiliza el diccionario nativo DATATABLES_ESPANOL." . PHP_EOL;
} else {
    echo "  ❌ admin/alertas.php no incluye datatables-es.js o DATATABLES_ESPANOL." . PHP_EOL;
    $errores++;
}

// 3. Verificar admin/historico_ventas.php
$historicoPhp = file_get_contents(__DIR__ . '/../admin/historico_ventas.php');
if (strpos($historicoPhp, 'cdn.datatables.net/plug-ins') !== false) {
    echo "  ❌ admin/historico_ventas.php aún contiene llamada externa a cdn.datatables.net/plug-ins!" . PHP_EOL;
    $errores++;
} else {
    echo "  ✔ admin/historico_ventas.php libre de URLs externas a CDN de idioma." . PHP_EOL;
}
if (strpos($historicoPhp, 'datatables-es.js') !== false && strpos($historicoPhp, 'DATATABLES_ESPANOL') !== false) {
    echo "  ✔ admin/historico_ventas.php utiliza el diccionario nativo DATATABLES_ESPANOL." . PHP_EOL;
} else {
    echo "  ❌ admin/historico_ventas.php no incluye datatables-es.js o DATATABLES_ESPANOL." . PHP_EOL;
    $errores++;
}

if ($errores === 0) {
    echo "✅ TEST LOCALIZACIÓN DATATABLES PASÓ EXITOSAMENTE (CERO CORS)." . PHP_EOL;
    exit(0);
} else {
    echo "❌ TEST LOCALIZACIÓN DATATABLES FALLÓ CON {$errores} ERRORES." . PHP_EOL;
    exit(1);
}
