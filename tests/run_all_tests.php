<?php
declare(strict_types=1);

$tests = [
    'test_deepseek_client.php',
    'test_pdf_extractor.php',
    'test_rag_offline.php',
    'test_chat_frontend_contract.php',
    'test_catalogo_departamentos.php',
    'test_segmentacion_v4.php',
    'test_auth_clientes.php',
    'test_chat_agregar_carrito.php',
    'test_bi_and_comparator.php',
    'test_nueva_categoria.php',
    'test_admin_lucide.php',
    'test_header_consistencia.php',
    'test_datatables_cors.php',
    'test_deep_jailbreaks.php',
    'test_chat_externo.php',
    'test_hardening_unit.php',
    'test_chat_engine_stub.php',
    'test_chat_concurrency_live.php'
];

echo "======================================================\n";
echo "   EJECUTANDO SUITE COMPLETA DE PRUEBAS DEL SISTEMA   \n";
echo "======================================================\n\n";

$fallos = 0;
foreach ($tests as $t) {
    $cmd = 'php ' . escapeshellarg(__DIR__ . '/' . $t);
    echo ">> [TEST] {$t}...\n";
    passthru($cmd, $ret);
    if ($ret !== 0) {
        echo "❌ FALLÓ: {$t}\n\n";
        $fallos++;
    } else {
        echo "✔ OK: {$t}\n\n";
    }
}

if ($fallos === 0) {
    echo "🎉 TODOS LOS TESTS DEL SISTEMA PASARON AL 100% CON ÉXITO!\n";
    exit(0);
} else {
    echo "❌ HUBO {$fallos} TESTS CON ERRORES.\n";
    exit(1);
}
