<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/rag/EmbeddingService.php';
require_once __DIR__ . '/../services/rag/VectorStore.php';

$pdo = Database::getConnection();
$emb = new Services\Rag\EmbeddingService();
$store = new Services\Rag\VectorStore($pdo);

$q = "Cuanto tiempo tengo para devolver un producto si llega mal?";
$vec = $emb->getEmbedding($q);
echo "Tokens y pesos de la consulta:\n";
print_r($vec);

$res = $store->searchSimilar($vec, 5, 0.01);
echo "\nTop matches con score > 0.01:\n";
print_r($res);
