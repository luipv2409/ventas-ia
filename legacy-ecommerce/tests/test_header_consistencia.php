<?php
declare(strict_types=1);

session_start();
$_SESSION['cliente_id'] = 1;
$_SESSION['cliente_nombre'] = 'Carlos Test';
$_SESSION['cliente_email'] = 'carlos@test.com';

echo "=== TEST: CONSISTENCIA DE HEADER GLOBAL EN TODAS LAS VISTAS ===" . PHP_EOL;

$vistas = [
    'catalogo'   => __DIR__ . '/../index.php',
    'comparador' => __DIR__ . '/../comparador.php',
    'carrito'    => __DIR__ . '/../carrito.php',
    'checkout'   => __DIR__ . '/../checkout.php',
    'pedidos'    => __DIR__ . '/../mis_pedidos.php',
];

$errores = 0;

foreach ($vistas as $clave => $archivo) {
    ob_start();
    include $archivo;
    $html = ob_get_clean();

    echo " [+] Verificando header en '{$clave}' (" . basename($archivo) . "):" . PHP_EOL;

    // 1. Logo consistente
    if (strpos($html, 'data-lucide="cpu"') !== false) {
        echo "    ✔ Logo con icono cpu presente" . PHP_EOL;
    } else {
        echo "    ❌ FALTA logo con cpu" . PHP_EOL;
        $errores++;
    }

    // 2. Enlace Catálogo
    if (strpos($html, 'data-lucide="layout-grid"') !== false && strpos($html, 'Catálogo') !== false) {
        echo "    ✔ Enlace 'Catálogo' con layout-grid presente" . PHP_EOL;
    } else {
        echo "    ❌ FALTA enlace 'Catálogo'" . PHP_EOL;
        $errores++;
    }

    // 3. Enlace Comparador unificado (no 'Comparador Celulares')
    if (strpos($html, 'data-lucide="git-compare"') !== false && preg_match('/<span>Comparador<\/span>/', $html)) {
        echo "    ✔ Enlace 'Comparador' unificado con git-compare presente" . PHP_EOL;
    } else {
        echo "    ❌ FALTA enlace 'Comparador' unificado" . PHP_EOL;
        $errores++;
    }

    // 4. Carrito con badge
    if (strpos($html, 'id="carrito-contador"') !== false) {
        echo "    ✔ Botón Carrito con badge reactivo #carrito-contador presente" . PHP_EOL;
    } else {
        echo "    ❌ FALTA botón Carrito con #carrito-contador" . PHP_EOL;
        $errores++;
    }

    // 5. Admin acceso
    if (strpos($html, 'href="admin/dashboard.php"') !== false && strpos($html, 'data-lucide="shield"') !== false) {
        echo "    ✔ Acceso Admin unificado con icono shield presente" . PHP_EOL;
    } else {
        echo "    ❌ FALTA acceso Admin unificado" . PHP_EOL;
        $errores++;
    }

    // 6. Verificar que el archivo fuente inicie con <?php
    $primerasLineas = file_get_contents($archivo, false, null, 0, 10);
    if (str_starts_with(ltrim($primerasLineas), '<?php')) {
        echo "    ✔ Archivo fuente inicia correctamente con <?php" . PHP_EOL;
    } else {
        echo "    ❌ ERROR: Archivo {$archivo} NO inicia con <?php" . PHP_EOL;
        $errores++;
    }

    // 7. Verificar que no haya advertencias PHP en el HTML
    if (preg_match('/<b>(Warning|Fatal error|Parse error|Deprecated)<\/b>/i', $html, $m)) {
        echo "    ❌ ERROR: Advertencia/Error PHP detectado en HTML: {$m[1]}" . PHP_EOL;
        $errores++;
    } else {
        echo "    ✔ Renderizado limpio sin advertencias ni errores PHP" . PHP_EOL;
    }
}

if ($errores === 0) {
    echo "✅ TEST CONSISTENCIA DE HEADER PASÓ 100% EXITOSAMENTE." . PHP_EOL;
    exit(0);
} else {
    echo "❌ TEST CONSISTENCIA DE HEADER FALLÓ CON {$errores} ERRORES." . PHP_EOL;
    exit(1);
}
