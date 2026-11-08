<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

/**
 * Script de Purga, Anonimización y Cumplimiento de Retención de Datos — ULAT.
 *
 * Uso CLI:
 *   php scripts/purge_chat_data.php [--days=90] [--lead-days=365] [--dry-run]
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

$options = getopt('', ['days::', 'lead-days::', 'dry-run']);
$diasRetencion = isset($options['days'])
    ? max(1, (int)$options['days'])
    : (defined('CHAT_RETENTION_DAYS') ? (int)CHAT_RETENTION_DAYS : 90);
$diasLeads = isset($options['lead-days'])
    ? max(30, (int)$options['lead-days'])
    : (defined('LEAD_RETENTION_DAYS') ? (int)LEAD_RETENTION_DAYS : 365);
$dryRun = isset($options['dry-run']);

echo "=== PURGA Y RETENCIÓN DE DATOS — PORTAL ULAT ===" . PHP_EOL;
echo "Retención logs chat: {$diasRetencion} días | Retención prospectos cerrados: {$diasLeads} días"
    . ($dryRun ? ' [DRY RUN]' : '') . PHP_EOL;

try {
    $pdo = Database::getConnection();

    // 0. Anonimizar session_id antiguos en chat_logs que aún no tengan prefijo HMAC 'sid_'
    $stmtLegacySid = $pdo->query("SELECT id, session_id FROM chat_logs WHERE session_id NOT LIKE 'sid_%' LIMIT 5000");
    $legacyRows = $stmtLegacySid ? $stmtLegacySid->fetchAll(PDO::FETCH_ASSOC) : [];
    if (!$dryRun && !empty($legacyRows)) {
        $stmtAnon = $pdo->prepare('UPDATE chat_logs SET session_id = :h WHERE id = :id');
        foreach ($legacyRows as $r) {
            $hashed = 'sid_' . substr(hash_hmac('sha256', (string)$r['session_id'], APP_SECRET_KEY), 0, 32);
            $stmtAnon->execute([':h' => $hashed, ':id' => (int)$r['id']]);
        }
    }
    echo ' [-] chat_logs session_id sin anonimizar: ' . count($legacyRows) . ' filas ' . ($dryRun ? 'detectadas' : 'anonimizadas') . PHP_EOL;

    // 1. chat_externo_sesiones
    $stmtCountSesiones = $pdo->prepare('SELECT COUNT(*) FROM chat_externo_sesiones WHERE creado_en < NOW() - INTERVAL :dias DAY');
    $stmtCountSesiones->execute([':dias' => $diasRetencion]);
    $totalSesiones = (int)$stmtCountSesiones->fetchColumn();
    if (!$dryRun && $totalSesiones > 0) {
        $pdo->prepare('DELETE FROM chat_externo_sesiones WHERE creado_en < NOW() - INTERVAL :dias DAY')
            ->execute([':dias' => $diasRetencion]);
    }
    echo " [-] chat_externo_sesiones: {$totalSesiones} filas " . ($dryRun ? 'por purgar' : 'purgadas') . PHP_EOL;

    // 2. chat_logs
    $stmtCountLogs = $pdo->prepare('SELECT COUNT(*) FROM chat_logs WHERE creado_en < NOW() - INTERVAL :dias DAY');
    $stmtCountLogs->execute([':dias' => $diasRetencion]);
    $totalLogs = (int)$stmtCountLogs->fetchColumn();
    if (!$dryRun && $totalLogs > 0) {
        $pdo->prepare('DELETE FROM chat_logs WHERE creado_en < NOW() - INTERVAL :dias DAY')
            ->execute([':dias' => $diasRetencion]);
    }
    echo " [-] chat_logs: {$totalLogs} filas " . ($dryRun ? 'por purgar' : 'purgadas') . PHP_EOL;

    // 3. admisiones_prospectos (descartados o antiguos fuera del ciclo de admisión)
    $stmtCountLeads = $pdo->prepare("
        SELECT COUNT(*) FROM admisiones_prospectos
        WHERE created_at < NOW() - INTERVAL :dias DAY
          AND estado IN ('descartado', 'contactado', 'nuevo')
    ");
    $stmtCountLeads->execute([':dias' => $diasLeads]);
    $totalLeads = (int)$stmtCountLeads->fetchColumn();
    if (!$dryRun && $totalLeads > 0) {
        $pdo->prepare("
            DELETE FROM admisiones_prospectos
            WHERE created_at < NOW() - INTERVAL :dias DAY
              AND estado IN ('descartado', 'contactado', 'nuevo')
        ")->execute([':dias' => $diasLeads]);
    }
    echo " [-] admisiones_prospectos expirados: {$totalLeads} filas " . ($dryRun ? 'por purgar' : 'purgadas') . PHP_EOL;

    // 4. chat_rate_limits (> 24h)
    $cutoffWindow = time() - 86400;
    $stmtCountRL = $pdo->prepare('SELECT COUNT(*) FROM chat_rate_limits WHERE window_start < :cutoff');
    $stmtCountRL->execute([':cutoff' => $cutoffWindow]);
    $totalRL = (int)$stmtCountRL->fetchColumn();
    if (!$dryRun && $totalRL > 0) {
        $pdo->prepare('DELETE FROM chat_rate_limits WHERE window_start < :cutoff')
            ->execute([':cutoff' => $cutoffWindow]);
    }
    echo " [-] chat_rate_limits: {$totalRL} ventanas expiradas " . ($dryRun ? 'por purgar' : 'purgadas') . PHP_EOL;

    // 5. rag_query_cache
    $totalCache = (int)($pdo->query('SELECT COUNT(*) FROM rag_query_cache WHERE expira_en < NOW()')->fetchColumn() ?: 0);
    if (!$dryRun && $totalCache > 0) {
        $pdo->exec('DELETE FROM rag_query_cache WHERE expira_en < NOW()');
    }
    echo " [-] rag_query_cache: {$totalCache} entradas vencidas " . ($dryRun ? 'por purgar' : 'purgadas') . PHP_EOL;

    echo 'OK: Purga y anonimización completadas.' . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Error ejecutando purga: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
