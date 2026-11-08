<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../services/chat/DeepSeekClient.php';

use Services\Chat\DeepSeekClient;

echo "=== TEST: DeepSeekClient Resiliencia, Retry-After y Circuit Breaker (#16) ===\n";

$fallos = 0;
$check = static function (string $label, bool $cond, string $detail = '') use (&$fallos): void {
    if ($cond) {
        echo "  ✔ {$label}\n";
    } else {
        $fallos++;
        echo "  ❌ FALLÓ: {$label}" . ($detail !== '' ? " ({$detail})" : '') . "\n";
    }
};

$cbFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ulat_test_cb_' . bin2hex(random_bytes(4)) . '.json';

try {
    // 1. Cliente sin API key falla rápido sin tocar red
    $noKey = new DeepSeekClient('', null, null, 5, 1, 2, null, $cbFile);
    $resNoKey = $noKey->chat([['role' => 'user', 'content' => 'Hola']]);
    $check('Sin API key retorna ok=false inmediato', !$resNoKey['ok'] && $resNoKey['http_code'] === 0);

    // 2. Reintento exitoso ante error transitorio de red (HTTP 0 -> HTTP 200) + json_mode
    $calls = 0;
    $lastPayload = [];
    $mockRetryOk = new DeepSeekClient(
        'sk-test-dummy',
        'https://api.deepseek.com/v1/chat/completions',
        'deepseek-chat',
        5,
        1,
        2,
        static function (array $payload) use (&$calls, &$lastPayload): array {
            $calls++;
            $lastPayload = $payload;
            if ($calls === 1) {
                return ['http_code' => 0, 'body' => '', 'curl_error' => 'Connection reset by peer'];
            }
            return [
                'http_code'  => 200,
                'body'       => (string)json_encode([
                    'choices' => [['message' => ['content' => '{"respuesta":"Hola desde ULAT","acciones":[]}']]],
                    'usage'   => ['prompt_tokens' => 12, 'completion_tokens' => 8, 'total_tokens' => 20],
                ]),
                'curl_error' => '',
            ];
        },
        $cbFile
    );
    $mockRetryOk->resetCircuitBreaker();
    $resOk = $mockRetryOk->chat([['role' => 'user', 'content' => 'Hola']], ['json_mode' => true, 'no_sleep' => true]);
    $check('Reintenta ante fallo de red HTTP 0 y recupera en el 2º intento', $resOk['ok'] && $calls === 2);
    $check('Activa response_format=json_object cuando json_mode=true (#21)', ($lastPayload['response_format']['type'] ?? '') === 'json_object');
    $check('Parsea JSON estructurado de la respuesta', ($resOk['json']['respuesta'] ?? '') === 'Hola desde ULAT');

    // 3. HTTP 429 con Retry-After alto (>4s) hace fail-fast sin reintentar ni colgar worker
    $calls429 = 0;
    $mock429 = new DeepSeekClient(
        'sk-test-dummy',
        'https://api.deepseek.com/v1/chat/completions',
        'deepseek-chat',
        5,
        2,
        2,
        static function () use (&$calls429): array {
            $calls429++;
            return ['http_code' => 429, 'body' => '{"error":"rate_limit"}', 'curl_error' => '', 'retry_after' => 30];
        },
        $cbFile
    );
    $mock429->resetCircuitBreaker();
    $res429 = $mock429->chat([['role' => 'user', 'content' => 'Hola']], ['no_sleep' => true]);
    $check('HTTP 429 con Retry-After=30s aborta inmediato (1 solo intento, fail-fast)', !$res429['ok'] && $calls429 === 1);

    // 4. Circuit Breaker se abre tras 5 fallos transitorios y bloquea sin tocar el transporte
    $transportHits = 0;
    $mockOutage = new DeepSeekClient(
        'sk-test-dummy',
        'https://api.deepseek.com/v1/chat/completions',
        'deepseek-chat',
        5,
        0,
        2,
        static function () use (&$transportHits): array {
            $transportHits++;
            return ['http_code' => 503, 'body' => 'Service Unavailable', 'curl_error' => ''];
        },
        $cbFile
    );
    $mockOutage->resetCircuitBreaker();
    for ($i = 1; $i <= DeepSeekClient::CB_FAILURE_THRESHOLD; $i++) {
        $mockOutage->chat([['role' => 'user', 'content' => "Intento {$i}" ]], ['no_sleep' => true]);
    }
    $stateOpen = $mockOutage->getCircuitState();
    $check('Circuit Breaker pasa a ABIERTO tras 5 fallos 503 consecutivos', $stateOpen['is_open'] === true);

    $hitsBeforeFastFail = $transportHits;
    $fastFail = $mockOutage->chat([['role' => 'user', 'content' => 'Durante caida']], ['no_sleep' => true]);
    $check('Con Circuit Breaker ABIERTO responde fast-fail 503 sin invocar red', !$fastFail['ok'] && $fastFail['http_code'] === 503 && $transportHits === $hitsBeforeFastFail);

    // 5. Llamada opcional en vivo solo si se solicita --live
    if (in_array('--live', $argv ?? [], true)) {
        $live = new DeepSeekClient();
        if ($live->isConfigured()) {
            $liveRes = $live->chat([
                ['role' => 'system', 'content' => 'Responde en JSON: {"saludo":"hola"}'],
                ['role' => 'user', 'content' => 'Saluda'],
            ], ['json_mode' => true, 'max_tokens' => 40]);
            $check('Llamada live TLS a DeepSeek exitosa', $liveRes['ok'] && is_array($liveRes['json']));
        }
    }
} finally {
    @unlink($cbFile);
}

exit($fallos === 0 ? 0 : 1);
