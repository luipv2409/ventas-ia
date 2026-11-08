<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

$runLive = in_array('--live', $argv ?? [], true);

$offlineTests = [
    'test_deepseek_client.php',
    'test_pdf_extractor.php',
    'test_rag_offline.php',
    'test_rag_ulat.php',
    'test_hardening_unit.php',
    'test_chat_engine_stub.php',
];

if (is_file(__DIR__ . '/test_security_regression_cli.php')) {
    $offlineTests[] = 'test_security_regression_cli.php';
}

$liveTests = [
    'test_chat_frontend_contract.php',
    'test_api_chat_ulat_frontend.php',
    'test_chat_externo.php',
    'test_deep_ulat_security_and_naturalness.php',
];

$tests = $runLive ? array_merge($offlineTests, $liveTests) : $offlineTests;

echo "======================================================\n";
echo "   SUITE DE PRUEBAS ULAT (" . ($runLive ? "OFFLINE + LIVE IA" : "MODO OFFLINE SEGURO") . ")\n";
echo "======================================================\n\n";

$fallos = 0;
foreach ($tests as $t) {
    $full = __DIR__ . '/' . $t;
    if (!is_file($full)) {
        continue;
    }
    $cmd = 'php ' . escapeshellarg($full);
    echo ">> [TEST] {$t}...\n";
    passthru($cmd, $ret);
    if ($ret !== 0) {
        echo "FALLO: {$t}\n\n";
        $fallos++;
    } else {
        echo "OK: {$t}\n\n";
    }
}

if ($fallos === 0) {
    echo "TODOS LOS TESTS EJECUTADOS PASARON CON EXITO.\n";
    exit(0);
}
echo "HUBO {$fallos} TEST(S) CON ERRORES.\n";
exit(1);
