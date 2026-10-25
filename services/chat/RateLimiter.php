<?php
declare(strict_types=1);

namespace Services\Chat;

use PDO;
use Throwable;

/**
 * Limitador de tasa de ventana fija, respaldado en MySQL/MariaDB (sin Redis).
 *
 * Protege contra "denegación de servicio económica": cada mensaje llama a un LLM de pago, así
 * que un script malicioso (o un bucle en n8n) podría gastar la cuota en minutos.
 *
 * El incremento es atómico (INSERT ... ON DUPLICATE KEY UPDATE). Si la BD falla, el limitador
 * "falla abierto" (permite) y lo registra: preferimos disponibilidad a bloquear clientes reales.
 */
final class RateLimiter
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function ensureSchema(): void
    {
        $this->pdo->exec("
            CREATE TABLE IF NOT EXISTS chat_rate_limits (
                bucket       VARCHAR(190)  NOT NULL,
                window_start INT UNSIGNED  NOT NULL,
                hits         INT UNSIGNED  NOT NULL DEFAULT 0,
                PRIMARY KEY (bucket, window_start),
                INDEX idx_window (window_start)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    /**
     * Registra un intento y dice si está permitido.
     *
     * @return array{allowed: bool, count: int, limit: int, retry_after: int}
     */
    public function hit(string $bucket, int $limit, int $windowSeconds = 60, ?int $now = null): array
    {
        $now         = $now ?? time();
        $windowSeconds = max(1, $windowSeconds);
        $windowStart = intdiv($now, $windowSeconds) * $windowSeconds;
        $bucket      = mb_substr($bucket, 0, 190);

        try {
            try {
                $count = $this->increment($bucket, $windowStart);
            } catch (Throwable $e) {
                // Tabla inexistente (SQLSTATE 42S02): se crea una vez y se reintenta
                if (str_contains($e->getMessage(), '42S02') || stripos($e->getMessage(), "doesn't exist") !== false) {
                    $this->ensureSchema();
                    $count = $this->increment($bucket, $windowStart);
                } else {
                    throw $e;
                }
            }

            // Limpieza oportunista (~1% de las llamadas): ventanas de más de 1 hora
            if (random_int(1, 100) === 1) {
                $this->pdo->prepare('DELETE FROM chat_rate_limits WHERE window_start < :t')
                    ->execute([':t' => $now - 3600]);
            }

            return [
                'allowed'     => $count <= $limit,
                'count'       => $count,
                'limit'       => $limit,
                'retry_after' => max(1, ($windowStart + $windowSeconds) - $now),
            ];
        } catch (Throwable $e) {
            error_log('[RateLimiter] Falla abierta por error de BD: ' . $e->getMessage());
            return ['allowed' => true, 'count' => 0, 'limit' => $limit, 'retry_after' => 0];
        }
    }

    private function increment(string $bucket, int $windowStart): int
    {
        $this->pdo->prepare('
            INSERT INTO chat_rate_limits (bucket, window_start, hits) VALUES (:b, :w, 1)
            ON DUPLICATE KEY UPDATE hits = hits + 1
        ')->execute([':b' => $bucket, ':w' => $windowStart]);

        $stmt = $this->pdo->prepare('SELECT hits FROM chat_rate_limits WHERE bucket = :b AND window_start = :w');
        $stmt->execute([':b' => $bucket, ':w' => $windowStart]);
        return (int)$stmt->fetchColumn();
    }
}
