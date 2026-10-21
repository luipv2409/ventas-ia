<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/rag/RagService.php';

use Services\Rag\RagService;

$pdo = Database::getConnection();
$rag = new RagService($pdo);

$queries = [
    "¿Cuánto cuesta el Galaxy A55 y tienen stock?",
    "¿Cuáles son los métodos de pago y aceptan QR?",
    "¿Hacen envíos a otras ciudades y cuánto tardan?",
    "Hola, buenos días" // Consulta genérica que debería filtrar o dar score bajo
];

foreach ($queries as $q) {
    echo "\n🔎 Consulta: \"$q\"\n";
    $res = $rag->retrieveContext($q, 2);
    echo "   Found: " . ($res['found'] ? 'SÍ' : 'NO') . "\n";
    if ($res['found']) {
        foreach ($res['chunks'] as $c) {
            echo "   - [" . round($c['score'] * 100, 1) . "%] {$c['title']}\n";
        }
    }
}
