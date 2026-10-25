<?php
declare(strict_types=1);

namespace Services\Chat;

/**
 * Higiene de entrada para cualquier canal (web, WhatsApp, Messenger, API).
 *
 * Defiende contra: UTF-8 inválido, caracteres de formato invisibles (zero-width, BOM, bidi,
 * "tag characters" usados para ASCII smuggling), caracteres de control, mensajes gigantes que
 * encarecen tokens, e identificadores de sesión sin acotar.
 */
final class InputSanitizer
{
    public const MAX_MESSAGE_CHARS      = 2000;
    public const MAX_HISTORY_ITEM_CHARS = 1200;
    public const MAX_HISTORY_ITEMS      = 30;
    public const MAX_IDENTIFIER_CHARS   = 128;

    /**
     * Limpia un texto de usuario y lo acota a $maxChars caracteres.
     */
    public static function cleanText(string $text, int $maxChars = self::MAX_MESSAGE_CHARS): string
    {
        // 1. UTF-8 válido
        $text = function_exists('mb_scrub') ? mb_scrub($text, 'UTF-8') : $text;

        // 2. Normalización Unicode (NFC) si intl está disponible: unifica "é" compuesta/descompuesta
        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($text, \Normalizer::FORM_C);
            if (is_string($normalized)) {
                $text = $normalized;
            }
        }

        // 3. Fuera caracteres de formato invisibles (Cf: ZWSP, ZWNJ, ZWJ, BOM, bidi, tags U+E0000...)
        $text = (string)preg_replace('/\p{Cf}+/u', '', $text);

        // 4. Fuera caracteres de control, salvo salto de línea y tabulador
        $text = (string)preg_replace('/[^\P{Cc}\n\t]/u', '', $text);

        // 5. Espacios: tabuladores/espacios repetidos y más de 2 saltos de línea seguidos
        $text = (string)preg_replace('/[ \t]{2,}/u', ' ', $text);
        $text = (string)preg_replace("/\n{3,}/", "\n\n", $text);
        $text = trim($text);

        // 6. Límite de longitud
        if ($maxChars > 0 && mb_strlen($text) > $maxChars) {
            $text = rtrim(mb_substr($text, 0, $maxChars));
        }
        return $text;
    }

    /**
     * ¿El texto limpio perdió contenido por exceder el límite? (para telemetría/avisos)
     */
    public static function exceedsLimit(string $text, int $maxChars = self::MAX_MESSAGE_CHARS): bool
    {
        return mb_strlen(trim($text)) > $maxChars;
    }

    /**
     * Identificador de remitente (teléfono, PSID, session id). Acotado y sin caracteres raros.
     */
    public static function cleanIdentifier(string $value, int $maxChars = self::MAX_IDENTIFIER_CHARS): string
    {
        $value = self::cleanText($value, $maxChars);
        return (string)preg_replace('/[^\p{L}\p{N}_\-+.:@]/u', '', $value);
    }

    /**
     * Canal permitido: 1 a 32 caracteres [a-z0-9_-]. Devuelve null si no es válido.
     */
    public static function normalizeChannel(string $channel): ?string
    {
        $channel = mb_strtolower(trim($channel));
        return preg_match('/^[a-z0-9_\-]{1,32}$/', $channel) === 1 ? $channel : null;
    }

    /**
     * Sanea un historial recibido de un cliente no confiable: solo roles user/assistant,
     * contenido de tipo string, longitud acotada y cantidad máxima de turnos (los más recientes).
     *
     * @param array<int, mixed> $history
     * @return array<int, array{role: string, content: string}>
     */
    public static function sanitizeHistory(array $history, int $maxItems = self::MAX_HISTORY_ITEMS): array
    {
        $clean = [];
        foreach ($history as $item) {
            if (!is_array($item) || !isset($item['role'], $item['content']) || !is_string($item['content'])) {
                continue;
            }
            $role = $item['role'] === 'assistant' ? 'assistant' : ($item['role'] === 'user' ? 'user' : null);
            if ($role === null) {
                continue;
            }
            $content = self::cleanText($item['content'], self::MAX_HISTORY_ITEM_CHARS);
            if ($content === '') {
                continue;
            }
            $clean[] = ['role' => $role, 'content' => $content];
        }
        return count($clean) > $maxItems ? array_slice($clean, -$maxItems) : $clean;
    }
}
