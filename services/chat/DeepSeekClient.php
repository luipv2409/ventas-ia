<?php
declare(strict_types=1);

namespace Services\Chat;

use Throwable;

/**
 * Cliente HTTP nativo endurecido para DeepSeek Chat Completions (OpenAI Spec).
 *
 * Controles de seguridad y resiliencia (Hallazgo Alto #16):
 * - Timeouts acotados (lectura 15s, conexión 4s, máx. 1 reintento) para no agotar workers PHP/Apache.
 * - Reintentos con backoff exponencial + jitter ante fallos de red (HTTP 0) y códigos 429/502/503/504.
 * - Respeto de cabecera Retry-After en HTTP 429 (fail-fast si supera 4s para no bloquear workers).
 * - Circuit Breaker atómico respaldado en archivo (flock): abre durante 30s tras 5 fallos consecutivos.
 * - Verificación estricta de certificados TLS (SSL_VERIFYPEER=true, SSL_VERIFYHOST=2) en chat() y stream().
 */
class DeepSeekClient
{
    public const CB_FAILURE_THRESHOLD = 5;
    public const CB_OPEN_SECONDS      = 30;
    public const MAX_RETRY_AFTER_WAIT = 4;

    private string $apiKey;
    private string $apiUrl;
    private string $model;
    private int $timeout;
    private int $connectTimeout;
    private int $maxRetries;
    private string $circuitFile;
    /** @var callable|null */
    private $transport;

    public function __construct(
        ?string $apiKey = null,
        ?string $apiUrl = null,
        ?string $model = null,
        int $timeout = 15,
        int $maxRetries = 1,
        int $connectTimeout = 4,
        ?callable $transport = null,
        ?string $circuitFile = null
    ) {
        $this->apiKey         = $apiKey ?? (defined('DEEPSEEK_API_KEY') ? (string)DEEPSEEK_API_KEY : '');
        $this->apiUrl         = $apiUrl ?? (defined('DEEPSEEK_API_URL') ? (string)DEEPSEEK_API_URL : 'https://api.deepseek.com/v1/chat/completions');
        $this->model          = $model ?? (defined('DEEPSEEK_MODEL') ? (string)DEEPSEEK_MODEL : 'deepseek-chat');
        $this->timeout        = max(2, $timeout);
        $this->connectTimeout = max(1, $connectTimeout);
        $this->maxRetries     = max(0, $maxRetries);
        $this->transport      = $transport;
        $this->circuitFile    = $circuitFile ?? (sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ulat_deepseek_cb_' . substr(hash('sha256', $this->apiUrl), 0, 12) . '.json');
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
     * Reinicia el estado del Circuit Breaker (útil para pruebas deterministas y recuperación administrativa).
     */
    public function resetCircuitBreaker(): void
    {
        $this->writeCircuitState(['failures' => 0, 'open_until' => 0, 'updated_at' => time()]);
    }

    /**
     * @return array{failures: int, open_until: int, is_open: bool}
     */
    public function getCircuitState(): array
    {
        $state = $this->readCircuitState();
        return [
            'failures'   => $state['failures'],
            'open_until' => $state['open_until'],
            'is_open'    => $state['open_until'] > time(),
        ];
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

        // Fast-fail inmediato si el Circuit Breaker está abierto
        $cb = $this->getCircuitState();
        if ($cb['is_open']) {
            return [
                'ok'          => false,
                'content'     => null,
                'json'        => null,
                'usage'       => $emptyUsage,
                'latencia_ms' => (int)round((microtime(true) - $start) * 1000),
                'http_code'   => 503,
                'error'       => 'Circuit breaker abierto temporalmente para proteger workers.',
            ];
        }

        $payload = [
            'model'       => $options['model'] ?? $this->model,
            'messages'    => $messages,
            'temperature' => (float)($options['temperature'] ?? 0.6),
            'max_tokens'  => (int)($options['max_tokens'] ?? 600),
        ];

        if (!empty($options['json_mode'])) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $attempt      = 0;
        $lastError    = null;
        $lastHttpCode = 0;
        $responseBody = '';

        while ($attempt <= $this->maxRetries) {
            $attempt++;

            $res = $this->executeHttpPost($payload);
            $responseBody = $res['body'];
            $lastHttpCode = $res['http_code'];
            $curlError    = $res['curl_error'];
            $retryAfter   = $res['retry_after'];

            if ($curlError === '' && $lastHttpCode === 200 && $responseBody !== '') {
                $lastError = null;
                $this->recordCircuitSuccess();
                break;
            }

            $lastError = $curlError !== ''
                ? 'cURL Error: ' . $curlError
                : "HTTP {$lastHttpCode}: " . mb_substr($responseBody, 0, 300);

            $isTransient = in_array($lastHttpCode, [0, 429, 500, 502, 503, 504], true);
            if ($isTransient) {
                $tripped = $this->recordCircuitFailure();
                if ($tripped) {
                    break;
                }
            }

            if ($isTransient && $attempt <= $this->maxRetries) {
                if ($lastHttpCode === 429 && $retryAfter !== null) {
                    // Si el proveedor exige esperar demasiado, liberar el worker de inmediato (fail-fast)
                    if ($retryAfter > self::MAX_RETRY_AFTER_WAIT) {
                        break;
                    }
                    $sleepUs = max(100_000, ($retryAfter * 1_000_000) + random_int(50_000, 200_000));
                } else {
                    $sleepUs = (400_000 * $attempt) + random_int(50_000, 200_000);
                }
                if (empty($options['no_sleep'])) {
                    usleep($sleepUs);
                }
                continue;
            }

            // Errores permanentes (400, 401, 403, 422) no se reintentan
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

        $content  = (string)($decoded['choices'][0]['message']['content'] ?? '');
        $rawUsage = $decoded['usage'] ?? [];

        $usage = [
            'prompt_tokens'     => (int)($rawUsage['prompt_tokens'] ?? 0),
            'completion_tokens' => (int)($rawUsage['completion_tokens'] ?? 0),
            'total_tokens'      => (int)($rawUsage['total_tokens'] ?? 0),
            'cache_hit_tokens'  => (int)($rawUsage['prompt_cache_hit_tokens'] ?? ($rawUsage['prompt_tokens_details']['cached_tokens'] ?? 0)),
        ];

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
     * Transmite la respuesta progresiva de DeepSeek vía Server-Sent Events (SSE) con TLS estricto.
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

        if ($this->getCircuitState()['is_open']) {
            return ['ok' => false, 'full_content' => '', 'error' => 'Circuit breaker abierto temporalmente'];
        }

        $payload = [
            'model'       => $options['model'] ?? $this->model,
            'messages'    => $messages,
            'temperature' => (float)($options['temperature'] ?? 0.6),
            'max_tokens'  => (int)($options['max_tokens'] ?? 600),
            'stream'      => true,
        ];

        $fullContent = '';
        $buffer      = '';

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_WRITEFUNCTION  => function ($ch, $rawChunk) use (&$fullContent, &$buffer, $onChunk) {
                $buffer .= $rawChunk;
                $lines  = explode("\n", $buffer);
                $buffer = array_pop($lines) ?? '';

                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, ':') || $line === 'data: [DONE]') {
                        continue;
                    }
                    if (str_starts_with($line, 'data: ')) {
                        $jsonStr = substr($line, 6);
                        $data    = json_decode($jsonStr, true);
                        if (is_array($data)) {
                            $delta = (string)($data['choices'][0]['delta']['content'] ?? '');
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

        $caBundle = getenv('CURL_CA_BUNDLE');
        if (is_string($caBundle) && $caBundle !== '' && is_file($caBundle)) {
            curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
        }

        $success   = curl_exec($ch);
        $httpCode  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!$success || $httpCode !== 200) {
            if (in_array($httpCode, [0, 429, 500, 502, 503, 504], true)) {
                $this->recordCircuitFailure();
            }
            return [
                'ok'           => false,
                'full_content' => $fullContent,
                'error'        => $curlError ?: "HTTP {$httpCode}",
            ];
        }

        $this->recordCircuitSuccess();
        return [
            'ok'           => true,
            'full_content' => $fullContent,
            'error'        => null,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{http_code: int, body: string, curl_error: string, retry_after: ?int}
     */
    private function executeHttpPost(array $payload): array
    {
        if ($this->transport !== null) {
            $mock = ($this->transport)($payload, $this->apiUrl);
            return [
                'http_code'   => (int)($mock['http_code'] ?? 0),
                'body'        => (string)($mock['body'] ?? ''),
                'curl_error'  => (string)($mock['curl_error'] ?? ''),
                'retry_after' => isset($mock['retry_after']) ? (int)$mock['retry_after'] : null,
            ];
        }

        $retryAfter = null;
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
            CURLOPT_CONNECTTIMEOUT => $this->connectTimeout,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HEADERFUNCTION => static function ($ch, string $headerLine) use (&$retryAfter): int {
                if (stripos($headerLine, 'Retry-After:') === 0) {
                    $val = trim(substr($headerLine, 12));
                    if (ctype_digit($val)) {
                        $retryAfter = (int)$val;
                    }
                }
                return strlen($headerLine);
            },
        ]);

        $caBundle = getenv('CURL_CA_BUNDLE');
        if (is_string($caBundle) && $caBundle !== '' && is_file($caBundle)) {
            curl_setopt($ch, CURLOPT_CAINFO, $caBundle);
        }

        $body      = (string)curl_exec($ch);
        $httpCode  = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        return [
            'http_code'   => $httpCode,
            'body'        => $body,
            'curl_error'  => $curlError,
            'retry_after' => $retryAfter,
        ];
    }

    /**
     * @return array{failures: int, open_until: int}
     */
    private function readCircuitState(): array
    {
        if (!is_file($this->circuitFile)) {
            return ['failures' => 0, 'open_until' => 0];
        }
        $fp = @fopen($this->circuitFile, 'rb');
        if ($fp === false) {
            return ['failures' => 0, 'open_until' => 0];
        }
        try {
            if (@flock($fp, LOCK_SH)) {
                $raw = stream_get_contents($fp);
                @flock($fp, LOCK_UN);
                $data = is_string($raw) ? json_decode($raw, true) : null;
                if (is_array($data)) {
                    return [
                        'failures'   => max(0, (int)($data['failures'] ?? 0)),
                        'open_until' => max(0, (int)($data['open_until'] ?? 0)),
                    ];
                }
            }
        } finally {
            @fclose($fp);
        }
        return ['failures' => 0, 'open_until' => 0];
    }

    /**
     * @param array<string, int> $state
     */
    private function writeCircuitState(array $state): void
    {
        $fp = @fopen($this->circuitFile, 'c+b');
        if ($fp === false) {
            return;
        }
        try {
            if (@flock($fp, LOCK_EX)) {
                ftruncate($fp, 0);
                rewind($fp);
                fwrite($fp, (string)json_encode($state));
                fflush($fp);
                @flock($fp, LOCK_UN);
            }
        } finally {
            @fclose($fp);
        }
    }

    private function recordCircuitSuccess(): void
    {
        $state = $this->readCircuitState();
        if ($state['failures'] > 0 || $state['open_until'] > 0) {
            $this->writeCircuitState(['failures' => 0, 'open_until' => 0, 'updated_at' => time()]);
        }
    }

    /**
     * Incrementa fallos consecutivos y devuelve true si el circuito acaba de dispararse a ABIERTO.
     */
    private function recordCircuitFailure(): bool
    {
        $fp = @fopen($this->circuitFile, 'c+b');
        if ($fp === false) {
            return false;
        }
        try {
            if (!@flock($fp, LOCK_EX)) {
                return false;
            }
            $raw  = stream_get_contents($fp);
            $data = is_string($raw) ? json_decode($raw, true) : null;
            $failures = is_array($data) ? max(0, (int)($data['failures'] ?? 0)) + 1 : 1;
            $openUntil = is_array($data) ? max(0, (int)($data['open_until'] ?? 0)) : 0;

            $tripped = false;
            if ($failures >= self::CB_FAILURE_THRESHOLD) {
                $openUntil = time() + self::CB_OPEN_SECONDS;
                $tripped   = true;
            }

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, (string)json_encode([
                'failures'   => $failures,
                'open_until' => $openUntil,
                'updated_at' => time(),
            ]));
            fflush($fp);
            @flock($fp, LOCK_UN);
            return $tripped;
        } catch (Throwable $e) {
            return false;
        } finally {
            @fclose($fp);
        }
    }
}
