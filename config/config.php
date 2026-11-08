<?php
declare(strict_types=1);

// ============================================================
// ARCHIVO: config/config.php
// PROPÓSITO: Configuración global endurecida — Portal & Chatbot ULAT
// ============================================================

require_once __DIR__ . '/autoload.php';

// ------------------------------------------------------------
// CARGA DE VARIABLES DE ENTORNO (.env)
// ------------------------------------------------------------
$envFile = __DIR__ . '/../.env';
if (is_file($envFile)) {
    $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($envLines !== false) {
        foreach ($envLines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\n\r\0\x0B\"'");
            if (getenv($k) === false) {
                putenv("{$k}={$v}");
                $_ENV[$k] = $v;
                $_SERVER[$k] = $v;
            }
        }
    }
}

// ------------------------------------------------------------
// ENTORNO Y SEGURIDAD (Fail-Safe por defecto: APP_DEBUG = false)
// ------------------------------------------------------------
$rawDebug = getenv('APP_DEBUG');
define('APP_DEBUG', $rawDebug !== false ? filter_var($rawDebug, FILTER_VALIDATE_BOOLEAN) : false);
define('APP_NAME', (string)(getenv('APP_NAME') ?: 'Universidad Latinoamericana — ULAT'));
define('APP_URL',  (string)(getenv('APP_URL') ?: 'http://localhost/ventas-ia-main/ulat'));

// Proxies inversos confiables (vacío por defecto: usa siempre REMOTE_ADDR)
define('TRUSTED_PROXY_CIDRS', trim((string)(getenv('TRUSTED_PROXY_CIDRS') ?: '')));

// Clave interna para HMAC de anonimización de logs
define('APP_SECRET_KEY', (string)(getenv('APP_SECRET_KEY') ?: hash('sha256', __DIR__ . '|' . (getenv('DB_NAME') ?: 'ulat') . '|ulat-hmac-v1')));

// ------------------------------------------------------------
// BASE DE DATOS — MySQL / MariaDB
// ------------------------------------------------------------
define('DB_HOST',    (string)(getenv('DB_HOST') ?: 'localhost'));
define('DB_PORT',    (string)(getenv('DB_PORT') ?: '3306'));
define('DB_NAME',    (string)(getenv('DB_NAME') ?: 'ventas_ia'));
define('DB_USER',    (string)(getenv('DB_USER') ?: 'root'));
define('DB_PASS',    getenv('DB_PASS') !== false ? (string)getenv('DB_PASS') : '');
define('DB_CHARSET', (string)(getenv('DB_CHARSET') ?: 'utf8mb4'));

// ------------------------------------------------------------
// MOTOR CONVERSACIONAL DEEPSEEK E INTEGRACIONES
// ------------------------------------------------------------
define('DEEPSEEK_API_KEY', (string)(getenv('DEEPSEEK_API_KEY') ?: ''));
define('DEEPSEEK_API_URL', (string)(getenv('DEEPSEEK_API_URL') ?: 'https://api.deepseek.com/v1/chat/completions'));
define('DEEPSEEK_MODEL',   (string)(getenv('DEEPSEEK_MODEL') ?: 'deepseek-chat'));

// Integración multicanal (WhatsApp / n8n / Messenger) — sin fallbacks inseguros
define('CHAT_EXTERNAL_API_KEY',          (string)(getenv('CHAT_EXTERNAL_API_KEY') ?: ''));
define('WHATSAPP_VERIFY_TOKEN',          (string)(getenv('WHATSAPP_VERIFY_TOKEN') ?: ''));
define('STORE_PUBLIC_URL',               (string)(getenv('STORE_PUBLIC_URL') ?: ''));
define('CHAT_CATALOG_MAX_ITEMS',         (int)(getenv('CHAT_CATALOG_MAX_ITEMS') ?: 60));
define('CHAT_RATE_LIMIT_PER_MIN',        (int)(getenv('CHAT_RATE_LIMIT_PER_MIN') ?: 12));
define('CHAT_RATE_LIMIT_IP_PER_MIN',     (int)(getenv('CHAT_RATE_LIMIT_IP_PER_MIN') ?: 120));
define('CHAT_RATE_LIMIT_FRONTEND_PER_MIN', (int)(getenv('CHAT_RATE_LIMIT_FRONTEND_PER_MIN') ?: 15));
define('CHAT_RATE_LIMIT_FRONTEND_PER_DAY', (int)(getenv('CHAT_RATE_LIMIT_FRONTEND_PER_DAY') ?: 150));
define('LEAD_RATE_LIMIT_PER_10MIN',      (int)(getenv('LEAD_RATE_LIMIT_PER_10MIN') ?: 5));
define('CHAT_RETENTION_DAYS',            (int)(getenv('CHAT_RETENTION_DAYS') ?: 90));
define('LEAD_RETENTION_DAYS',            (int)(getenv('LEAD_RETENTION_DAYS') ?: 365));

// ------------------------------------------------------------
// CONFIGURACIÓN VECTORIAL RAG — MySQL / Pinecone (sin hostnames hardcodeados)
// ------------------------------------------------------------
define('RAG_VECTOR_DRIVER',   (string)(getenv('RAG_VECTOR_DRIVER') ?: 'mysql'));
define('PINECONE_API_KEY',    (string)(getenv('PINECONE_API_KEY') ?: ''));
define('PINECONE_INDEX_HOST', (string)(getenv('PINECONE_INDEX_HOST') ?: ''));

// ------------------------------------------------------------
// SESIÓN Y CSRF
// ------------------------------------------------------------
define('SESSION_NAME',    'ulat_web_sid');
define('CSRF_TOKEN_NAME', 'csrf_token');

// Zona horaria oficial Bolivia (UTC-4)
date_default_timezone_set((string)(getenv('APP_TIMEZONE') ?: 'America/La_Paz'));

// ------------------------------------------------------------
// MANEJO DE ERRORES (Ocultar detalles internos en producción)
// ------------------------------------------------------------
error_reporting(E_ALL);
ini_set('log_errors', '1');
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}
