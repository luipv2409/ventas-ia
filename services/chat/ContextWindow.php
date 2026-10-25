<?php
declare(strict_types=1);

namespace Services\Chat;

/**
 * Control de la ventana de contexto por presupuesto de tokens (no por cantidad fija de turnos).
 *
 * Estimación sin tokenizer: en español ~3.2-4 caracteres por token; se usa 3.2 (conservador)
 * para no quedarse corto. El objetivo no es ser exacto sino acotar costo y evitar desbordes.
 */
final class ContextWindow
{
    public const DEFAULT_HISTORY_TOKENS   = 2500;
    public const DEFAULT_HISTORY_MESSAGES = 12;
    private const CHARS_PER_TOKEN         = 3.2;

    public static function estimateTokens(string $text): int
    {
        return (int)ceil(mb_strlen($text) / self::CHARS_PER_TOKEN);
    }

    /**
     * Devuelve los turnos MÁS RECIENTES que caben en el presupuesto (orden cronológico).
     * El último turno siempre se conserva (recortado si hace falta).
     *
     * @param array<int, array{role: string, content: string}> $history
     * @return array<int, array{role: string, content: string}>
     */
    public static function trimHistory(
        array $history,
        int $maxTokens = self::DEFAULT_HISTORY_TOKENS,
        int $maxMessages = self::DEFAULT_HISTORY_MESSAGES
    ): array {
        $kept = [];
        $used = 0;

        foreach (array_reverse(array_values($history)) as $msg) {
            if (!isset($msg['role'], $msg['content'])) {
                continue;
            }
            $content = (string)$msg['content'];
            $tokens  = self::estimateTokens($content);

            if (count($kept) >= $maxMessages) {
                break;
            }
            if ($used + $tokens > $maxTokens) {
                if (empty($kept)) {
                    // El turno más reciente nunca se descarta: se recorta al presupuesto
                    $maxChars = (int)floor($maxTokens * self::CHARS_PER_TOKEN);
                    $kept[]   = ['role' => (string)$msg['role'], 'content' => mb_substr($content, -$maxChars)];
                }
                break;
            }
            $kept[] = ['role' => (string)$msg['role'], 'content' => $content];
            $used  += $tokens;
        }
        return array_reverse($kept);
    }
}
