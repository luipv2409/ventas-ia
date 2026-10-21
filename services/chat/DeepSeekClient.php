<?php
declare(strict_types=1);

namespace Services\Chat;

use RuntimeException;
use Throwable;

/**
 * Cliente HTTP nativo para la API de DeepSeek (compatible con OpenAI Spec).
 * 
 * Características:
 * - PHP 8.1+ tipado estricto con extensiones nativas (ext-curl, ext-json).
 * - Soporte nativo de 'response_format' => ['type' => 'json_object'].
 * - Reintentos exponenciales automáticos ante códigos temporales (429, 502, 503).
 * - Telemetría detallada: latencia_ms, tokens usados y cache-hit tokens.
 * - Soporte para streaming SSE mediante callable callback.
 */
class DeepSeekClient
{
    private string $apiKey;
    private string $apiUrl;
    private string $model;
    private int $timeout;
    private int $maxRetries;

    public function __construct(
        ?string $apiKey = null,
        ?string $apiUrl = null,
        ?string $model = null,
        int $timeout = 25,
        int $maxRetries = 2
    ) {
        $this->apiKey = $apiKey ?? (defined('DEEPSEEK_API_KEY') ? (string)DEEPSEEK_API_KEY : '');
        $this->apiUrl = $apiUrl ?? (defined('DEEPSEEK_API_URL') ? (string)DEEPSEEK_API_URL : 'https://api.deepseek.com/v1/chat/completions');
        $this->model  = $model ?? (defined('DEEPSEEK_MODEL') ? (string)DEEPSEEK_MODEL : 'deepseek-chat');
        $this->timeout = $timeout;
        $this->maxRetries = max(0, $maxRetries);
    }

    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && $this->apiKey !== 'TU_DEEPSEEK_API_KEY_AQUI';
    }

    public function getModel(): string
    {
        return $this->model;
    }

    /**
     * Realiza una llamada síncrona a DeepSeek Chat Completions.
     *
     * @param array<int, array{role: string, content: string}> $messages
     * @param array<string, mixed> $options Opciones como temperature, max_tokens, json_mode
     * @return array{
     *     ok: bool,
     *     content: ?string,
     *     json: ?array,
     *     usage: array{prompt_tokens: int, completion_tokens: int, total_tokens: int, cache_hit_tokens: int},
     *     latencia_ms: int,
     *     http_code: int,
     *     error: ?string
     * }
     */
    public function chat(array $messages, array $options = []): array
    {
        $start = microtime(true);
        $emptyUsage = [
            'prompt_tokens'     => 0,
            'completion_tokens' => 0,
            'total_tokens'      => 0,
            'cache_hit_tokens'  => 0,
        ];

        if (!$this->isConfigured()) {
            return [
                'ok'          => false,
                'content'     => null,
                'json'        => null,
                'usage'       => $emptyUsage,
                'latencia_ms' => 0,
                'http_code'   => 0,
                'error'       => 'DEEPSEEK_API_KEY no está configurada o es inválida.',
            ];
        }

        $payload = [
            'model'       => $options['model'] ?? $this->model,
            'messages'    => $messages,
            'temperature' => (float)($options['temperature'] ?? 0.6),
            'max_tokens'  => (int)($options['max_tokens'] ?? 600),
        ];

        // Activar modo JSON si se solicita explícitamente
        if (!empty($options['json_mode'])) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $attempt = 0;
        $lastError = null;
        $lastHttpCode = 0;
        $responseBody = '';

        while ($attempt <= $this->maxRetries) {
            $attempt++;

            $ch = curl_init($this->apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey,
                ],
                CURLOPT_TIMEOUT        => $this->timeout,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);

            $responseBody = (string)curl_exec($ch);
            $lastHttpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($curlError !== '') {
                $lastError = 'cURL Error: ' . $curlError;
            } elseif ($lastHttpCode === 200 && $responseBody !== '') {
                // Éxito
                $lastError = null;
                break;
            } else {
                $lastError = "HTTP {$lastHttpCode}: " . mb_substr($responseBody, 0, 300);
            }

            // Reintentar solo si es error transitorio (429, 502, 503) y aún quedan intentos
            if (in_array($lastHttpCode, [429, 502, 503, 504], true) && $attempt <= $this->maxRetries) {
                usleep(500000 * $attempt); // Backoff: 500ms, 1000ms
                continue;
            }

            // Otros errores HTTP (400, 401) no se reintentan
            break;
        }

        $latenciaMs = (int)round((microtime(true) - $start) * 1000);

        if ($lastError !== null || $lastHttpCode !== 200) {
            error_log(sprintf('[DEEPSEEK CLIENT ERROR] Código %d | %s | Intento %d', $lastHttpCode, $lastError, $attempt));
            return [
                'ok'          => false,
                'content'     => null,
                'json'        => null,
                'usage'       => $emptyUsage,
                'latencia_ms' => $latenciaMs,
                'http_code'   => $lastHttpCode,
                'error'       => $lastError,
            ];
        }

        $decoded = json_decode($responseBody, true);
        if (!is_array($decoded)) {
            return [
                'ok'          => false,
                'content'     => null,
                'json'        => null,
                'usage'       => $emptyUsage,
                'latencia_ms' => $latenciaMs,
                'http_code'   => $lastHttpCode,
                'error'       => 'Respuesta HTTP 200 de DeepSeek pero JSON malformado.',
            ];
        }

        $content = $decoded['choices'][0]['message']['content'] ?? '';
        $rawUsage = $decoded['usage'] ?? [];

        $usage = [
            'prompt_tokens'     => (int)($rawUsage['prompt_tokens'] ?? 0),
            'completion_tokens' => (int)($rawUsage['completion_tokens'] ?? 0),
            'total_tokens'      => (int)($rawUsage['total_tokens'] ?? 0),
            'cache_hit_tokens'  => (int)($rawUsage['prompt_cache_hit_tokens'] ?? ($rawUsage['prompt_tokens_details']['cached_tokens'] ?? 0)),
        ];

        // Intentar parsear JSON del contenido si se espera estructura
        $parsedJson = null;
        if (!empty($options['json_mode']) || str_contains($content, '{')) {
            $cleaned = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($content));
            $jsonAttempt = json_decode((string)$cleaned, true);
            if (is_array($jsonAttempt)) {
                $parsedJson = $jsonAttempt;
            } elseif (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
                $parsedJson = json_decode($matches[0], true);
            }
        }

        return [
            'ok'          => true,
            'content'     => $content,
            'json'        => is_array($parsedJson) ? $parsedJson : null,
            'usage'       => $usage,
            'latencia_ms' => $latenciaMs,
            'http_code'   => 200,
            'error'       => null,
        ];
    }

    /**
     * Transmite la respuesta progresiva de DeepSeek vía Server-Sent Events (SSE).
     *
     * @param array<int, array{role: string, content: string}> $messages
     * @param callable(string $chunkDelta): void $onChunk
     * @param array<string, mixed> $options
     * @return array{ok: bool, full_content: string, error: ?string}
     */
    public function stream(array $messages, callable $onChunk, array $options = []): array
    {
        if (!$this->isConfigured()) {
            return ['ok' => false, 'full_content' => '', 'error' => 'API Key no configurada'];
        }

        $payload = [
            'model'       => $options['model'] ?? $this->model,
            'messages'    => $messages,
            'temperature' => (float)($options['temperature'] ?? 0.6),
            'max_tokens'  => (int)($options['max_tokens'] ?? 600),
            'stream'      => true,
        ];

        $fullContent = '';
        $buffer = '';

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_WRITEFUNCTION  => function ($ch, $rawChunk) use (&$fullContent, &$buffer, $onChunk) {
                $buffer .= $rawChunk;
                $lines = explode("\n", $buffer);
                // Guardar la última línea incompleta en el buffer
                $buffer = array_pop($lines) ?? '';

                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, ':')) {
                        continue;
                    }
                    if ($line === 'data: [DONE]') {
                        continue;
                    }
                    if (str_starts_with($line, 'data: ')) {
                        $jsonStr = substr($line, 6);
                        $data = json_decode($jsonStr, true);
                        if (is_array($data)) {
                            $delta = $data['choices'][0]['delta']['content'] ?? '';
                            if ($delta !== '') {
                                $fullContent .= $delta;
                                $onChunk($delta);
                            }
                        }
                    }
                }
                return strlen($rawChunk);
            },
        ]);

        $success = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!$success || $httpCode !== 200) {
            return [
                'ok'           => false,
                'full_content' => $fullContent,
                'error'        => $curlError ?: "HTTP {$httpCode}",
            ];
        }

        return [
            'ok'           => true,
            'full_content' => $fullContent,
            'error'        => null,
        ];
    }
}
