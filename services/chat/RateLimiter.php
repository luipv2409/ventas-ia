<?php
declare(strict_types=1);

namespace Services\Chat;

use PDO;
use Throwable;

/**
 * Limitador de tasa de ventana fija respaldado en MySQL/MariaDB con fallback
 * atómico en disco (flock) y modo fail-closed ante fallos críticos.
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
     * @return array{allowed: bool, count: int, limit: int, retry_after: int}
     */
    public function hit(string $bucket, int $limit, int $windowSeconds = 60, ?int $now = null): array
    {
        $now           = $now ?? time();
        $windowSeconds = max(1, $windowSeconds);
        $windowStart   = intdiv($now, $windowSeconds) * $windowSeconds;
        $bucket        = mb_substr($bucket, 0, 190);
        $retryAfter    = max(1, ($windowStart + $windowSeconds) - $now);

        try {
            try {
                $count = $this->increment($bucket, $windowStart);
            } catch (Throwable $e) {
                if (str_contains($e->getMessage(), '42S02') || stripos($e->getMessage(), "doesn't exist") !== false) {
                    $this->ensureSchema();
                    $count = $this->increment($bucket, $windowStart);
                } else {
                    throw $e;
                }
            }

            if (random_int(1, 100) === 1) {
                $this->pdo->prepare('DELETE FROM chat_rate_limits WHERE window_start < :t')
                    ->execute([':t' => $now - 86400]);
            }

            return [
                'allowed'     => $count <= $limit,
                'count'       => $count,
                'limit'       => $limit,
                'retry_after' => $retryAfter,
            ];
        } catch (Throwable $e) {
            error_log('[RateLimiter] Error de BD, activando respaldo local: ' . $e->getMessage());
            return $this->hitFallbackFile($bucket, $windowStart, $limit, $retryAfter);
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

    /**
     * Respaldo local con bloqueo exclusivo flock() y cierre seguro (fail-closed) si el FS falla.
     *
     * @return array{allowed: bool, count: int, limit: int, retry_after: int}
     */
    private function hitFallbackFile(string $bucket, int $windowStart, int $limit, int $retryAfter): array
    {
        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ulat_ratelimits';
        if (!is_dir($dir) && !@mkdir($dir, 0700, true) && !is_dir($dir)) {
            return ['allowed' => false, 'count' => $limit + 1, 'limit' => $limit, 'retry_after' => $retryAfter];
        }

        $file = $dir . DIRECTORY_SEPARATOR . sha1($bucket . '|' . $windowStart) . '.cnt';
        $fp = @fopen($file, 'c+b');
        if ($fp === false) {
            return ['allowed' => false, 'count' => $limit + 1, 'limit' => $limit, 'retry_after' => $retryAfter];
        }

        if (!@flock($fp, LOCK_EX)) {
            fclose($fp);
            return ['allowed' => false, 'count' => $limit + 1, 'limit' => $limit, 'retry_after' => $retryAfter];
        }

        $raw = stream_get_contents($fp);
        $count = ($raw !== false && ctype_digit(trim($raw))) ? ((int)trim($raw) + 1) : 1;

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, (string)$count);
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        return [
            'allowed'     => $count <= $limit,
            'count'       => $count,
            'limit'       => $limit,
            'retry_after' => $retryAfter,
        ];
    }
}
