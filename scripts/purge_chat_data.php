<?php
declare(strict_types=1);

/**
 * Script de Purga y Cumplimiento de Retención de Datos (GDPR/Privacidad/Optimización).
 *
 * Limpia registros antiguos de sesiones de mensajería, telemetría y ventanas de rate limit expiradas.
 * Uso CLI:
 *   php scripts/purge_chat_data.php [--days=90] [--dry-run]
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$options = getopt('', ['days::', 'dry-run']);
$diasRetencion = isset($options['days'])
    ? max(1, (int)$options['days'])
    : (defined('CHAT_RETENTION_DAYS') ? (int)CHAT_RETENTION_DAYS : 90);
$dryRun = isset($options['dry-run']);

echo "=== PURGA Y RETENCIÓN DE DATOS DEL CHATBOT ===" . PHP_EOL;
echo "Política de retención: {$diasRetencion} días" . ($dryRun ? " [MODO SIMULACIÓN - DRY RUN]" : "") . PHP_EOL;

try {
    $pdo = Database::getConnection();

    // 1. chat_externo_sesiones
    $stmtCountSesiones = $pdo->prepare("SELECT COUNT(*) FROM chat_externo_sesiones WHERE creado_en < NOW() - INTERVAL :dias DAY");
    $stmtCountSesiones->execute([':dias' => $diasRetencion]);
    $totalSesiones = (int)$stmtCountSesiones->fetchColumn();

    if (!$dryRun && $totalSesiones > 0) {
        $stmtDel = $pdo->prepare("DELETE FROM chat_externo_sesiones WHERE creado_en < NOW() - INTERVAL :dias DAY");
        $stmtDel->execute([':dias' => $diasRetencion]);
    }
    echo " [-] chat_externo_sesiones: {$totalSesiones} filas " . ($dryRun ? "por purgar" : "purgadas") . PHP_EOL;

    // 2. chat_logs
    $stmtCountLogs = $pdo->prepare("SELECT COUNT(*) FROM chat_logs WHERE creado_en < NOW() - INTERVAL :dias DAY");
    $stmtCountLogs->execute([':dias' => $diasRetencion]);
    $totalLogs = (int)$stmtCountLogs->fetchColumn();

    if (!$dryRun && $totalLogs > 0) {
        $stmtDel = $pdo->prepare("DELETE FROM chat_logs WHERE creado_en < NOW() - INTERVAL :dias DAY");
        $stmtDel->execute([':dias' => $diasRetencion]);
    }
    echo " [-] chat_logs: {$totalLogs} filas " . ($dryRun ? "por purgar" : "purgadas") . PHP_EOL;

    // 3. chat_rate_limits (ventanas expiradas de más de 2 horas)
    $cutoffWindow = time() - 7200;
    $stmtCountRL = $pdo->prepare("SELECT COUNT(*) FROM chat_rate_limits WHERE window_start < :cutoff");
    $stmtCountRL->execute([':cutoff' => $cutoffWindow]);
    $totalRL = (int)$stmtCountRL->fetchColumn();

    if (!$dryRun && $totalRL > 0) {
        $stmtDel = $pdo->prepare("DELETE FROM chat_rate_limits WHERE window_start < :cutoff");
        $stmtDel->execute([':cutoff' => $cutoffWindow]);
    }
    echo " [-] chat_rate_limits: {$totalRL} ventanas antiguas " . ($dryRun ? "por purgar" : "purgadas") . PHP_EOL;

    // 4. rag_query_cache (entradas vencidas)
    $stmtCountCache = $pdo->query("SELECT COUNT(*) FROM rag_query_cache WHERE expira_en < NOW()");
    $totalCache = (int)($stmtCountCache ? $stmtCountCache->fetchColumn() : 0);

    if (!$dryRun && $totalCache > 0) {
        $pdo->exec("DELETE FROM rag_query_cache WHERE expira_en < NOW()");
    }
    echo " [-] rag_query_cache: {$totalCache} entradas vencidas " . ($dryRun ? "por purgar" : "purgadas") . PHP_EOL;

    echo "✅ Purga completada exitosamente." . PHP_EOL;
} catch (Throwable $e) {
    echo "❌ Error ejecutando purga: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
