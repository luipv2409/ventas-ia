<?php
declare(strict_types=1);

namespace Services\Rag;

use Throwable;

/**
 * Expansión de consultas con DeepSeek (Query Expansion).
 *
 * Convierte preguntas coloquiales o de seguimiento ("¿y cuánto tarda?", "¿cómo les pago si no
 * tengo efectivo?") en palabras clave de búsqueda. Solo se usa cuando la búsqueda local no
 * encontró nada claro (ver RagService), así que la mayoría de mensajes NO generan esta llamada.
 *
 * Costo: una llamada corta a deepseek-chat (~150 tokens de entrada, ≤40 de salida).
 * Si falla o tarda más del timeout, devuelve null y el chat sigue sin expansión.
 */
class QueryExpander
{
    private string $apiKey;
    private string $apiUrl;
    private string $model;
    private int $timeout;

    public function __construct(?string $apiKey = null, ?string $apiUrl = null, ?string $model = null, int $timeout = 4)
    {
        $this->apiKey  = $apiKey ?? (defined('DEEPSEEK_API_KEY') ? DEEPSEEK_API_KEY : '');
        $this->apiUrl  = $apiUrl ?? (defined('DEEPSEEK_API_URL') ? DEEPSEEK_API_URL : 'https://api.deepseek.com/v1/chat/completions');
        $this->model   = $model ?? (defined('DEEPSEEK_MODEL') ? DEEPSEEK_MODEL : 'deepseek-chat');
        $this->timeout = $timeout;
    }

    public function isAvailable(): bool
    {
        return $this->apiKey !== '';
    }

    /**
     * @param array<int, array{role?: string, content?: string}> $history
     * @return string|null Palabras clave separadas por espacio, o null si no aplica/falla
     */
    public function expand(string $query, array $history = []): ?string
    {
        if (!$this->isAvailable()) {
            return null;
        }

        $contexto = '';
        foreach (array_slice($history, -4) as $msg) {
            $role = ($msg['role'] ?? '') === 'user' ? 'Cliente' : 'Tienda';
            $contexto .= $role . ': ' . mb_substr((string)($msg['content'] ?? ''), 0, 200) . "\n";
        }

        $system = 'Generas palabras clave de búsqueda para la base de información de una tienda online en Bolivia '
            . '(productos, envíos, métodos de pago, garantía, devoluciones, horarios, facturación). '
            . 'Devuelve SOLO entre 4 y 12 palabras clave en español, separadas por espacios, sin explicaciones '
            . 'ni signos. Incluye sinónimos útiles. Usa la conversación previa para entender preguntas de '
            . 'seguimiento. Si el mensaje es solo saludo, agradecimiento o charla sin consulta, devuelve: NINGUNA';

        $user = ($contexto !== '' ? "Conversación previa:\n{$contexto}\n" : '') . "Mensaje actual del cliente: {$query}";

        try {
            $ch = curl_init($this->apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'model'       => $this->model,
                    'messages'    => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                    'temperature' => 0,
                    'max_tokens'  => 40,
                ]),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey,
                ],
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 2,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode !== 200 || !$response) {
                return null;
            }

            $data = json_decode((string)$response, true);
            $text = trim((string)($data['choices'][0]['message']['content'] ?? ''));
            if ($text === '' || stripos($text, 'NINGUNA') !== false) {
                return null;
            }

            // Saneamiento: solo letras, números y espacios; longitud acotada
            $text = (string)preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $text);
            $text = trim((string)preg_replace('/\s+/u', ' ', $text));
            return $text !== '' ? mb_substr($text, 0, 200) : null;
        } catch (Throwable $e) {
            error_log('[RAG QUERY EXPANSION] ' . $e->getMessage());
            return null;
        }
    }
}
