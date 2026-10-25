<?php
// ============================================================
// ARCHIVO: config/config.php
// PROPÓSITO: Constantes globales del sistema.
//            ⚠️ NUNCA subas este archivo a un repositorio público.
//            Agrega config.php a tu .gitignore.
// ============================================================

// ------------------------------------------------------------
// CARGA LIGERA DE VARIABLES DE ENTORNO (.env)
// ------------------------------------------------------------
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
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
// ENTORNO
// Cambia a false cuando estés en producción (InfinityFree)
// ------------------------------------------------------------
define('APP_DEBUG', true);
define('APP_NAME', 'Tienda Virtual con IA');
define('APP_URL',  'http://localhost/simulacion/ventas'); // Cambiar en producción

// ------------------------------------------------------------
// BASE DE DATOS — MySQL / Docker / XAMPP
// En Docker o InfinityFree se leen variables de entorno si existen
// ------------------------------------------------------------
define('DB_HOST',    getenv('DB_HOST') ?: 'localhost');
define('DB_PORT',    getenv('DB_PORT') ?: '3306');
define('DB_NAME',    getenv('DB_NAME') ?: 'ventas_ia');
define('DB_USER',    getenv('DB_USER') ?: 'root');
define('DB_PASS',    getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// ------------------------------------------------------------
// API DE INTELIGENCIA ARTIFICIAL
// Opción A: Groq (Llama 3) — https://console.groq.com
// Opción B: Gemini Flash  — https://aistudio.google.com
// Activa solo uno a la vez con APP_AI_PROVIDER
// ------------------------------------------------------------
define('APP_AI_PROVIDER', 'groq'); // 'groq' o 'gemini'

// Groq API
define('GROQ_API_KEY', getenv('GROQ_API_KEY') ?: 'TU_GROQ_API_KEY_AQUI');
define('GROQ_MODEL',   getenv('GROQ_MODEL') ?: 'llama-3.1-8b-instant');
define('GROQ_API_URL', getenv('GROQ_API_URL') ?: 'https://api.groq.com/openai/v1/chat/completions');

// Gemini Flash API
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: 'TU_GEMINI_API_KEY_AQUI');
define('GEMINI_MODEL',   getenv('GEMINI_MODEL') ?: 'gemini-1.5-flash');
define('GEMINI_API_URL', getenv('GEMINI_API_URL') ?: 'https://generativelanguage.googleapis.com/v1beta/models/' . GEMINI_MODEL . ':generateContent');


// DeepSeek API
define('DEEPSEEK_API_KEY', (!empty(getenv('DEEPSEEK_API_KEY')) ? getenv('DEEPSEEK_API_KEY') : 'TU_DEEPSEEK_API_KEY_AQUI'));
define('DEEPSEEK_API_URL', (!empty(getenv('DEEPSEEK_API_URL')) ? getenv('DEEPSEEK_API_URL') : 'https://api.deepseek.com/v1/chat/completions'));
define('DEEPSEEK_MODEL',   (!empty(getenv('DEEPSEEK_MODEL')) ? getenv('DEEPSEEK_MODEL') : 'deepseek-chat'));

// Clave API de acceso para el endpoint multicanal (WhatsApp / n8n / Messenger)
// En producción, DEBE configurarse CHAT_EXTERNAL_API_KEY en .env. Sin fallback inseguro.
define('CHAT_EXTERNAL_API_KEY', (string)(getenv('CHAT_EXTERNAL_API_KEY') ?: ''));
define('WHATSAPP_VERIFY_TOKEN', (string)(getenv('WHATSAPP_VERIFY_TOKEN') ?: CHAT_EXTERNAL_API_KEY));
define('STORE_PUBLIC_URL',      (string)(getenv('STORE_PUBLIC_URL') ?: ''));
define('CHAT_CATALOG_MAX_ITEMS', (int)(getenv('CHAT_CATALOG_MAX_ITEMS') ?: 60));
define('CHAT_RATE_LIMIT_PER_MIN', (int)(getenv('CHAT_RATE_LIMIT_PER_MIN') ?: 12));
define('CHAT_RATE_LIMIT_IP_PER_MIN', (int)(getenv('CHAT_RATE_LIMIT_IP_PER_MIN') ?: 600));
define('CHAT_RATE_LIMIT_FRONTEND_PER_MIN', (int)(getenv('CHAT_RATE_LIMIT_FRONTEND_PER_MIN') ?: 60));
define('CHAT_RETENTION_DAYS',    (int)(getenv('CHAT_RETENTION_DAYS') ?: 90));

// ------------------------------------------------------------
// CONFIGURACIÓN VECTORIAL RAG — MySQL / Pinecone
// ------------------------------------------------------------
define('RAG_VECTOR_DRIVER', getenv('RAG_VECTOR_DRIVER') ?: 'mysql'); // 'mysql' o 'pinecone'
define('PINECONE_API_KEY',   getenv('PINECONE_API_KEY') ?: '');
define('PINECONE_INDEX_HOST', getenv('PINECONE_INDEX_HOST') ?: 'ventas-ia-rag-9jc19q6.svc.aped-4627-b74a.pinecone.io');

// Guía: https://core.telegram.org/bots/tutorial
//   1. Habla con @BotFather → /newbot → copia el token
//   2. Habla con @userinfobot → copia tu chat_id
// ------------------------------------------------------------
define('TELEGRAM_BOT_TOKEN', 'TU_TELEGRAM_BOT_TOKEN_AQUI');
define('TELEGRAM_CHAT_ID',   'TU_CHAT_ID_AQUI');
define('TELEGRAM_API_URL',   'https://api.telegram.org/bot' . TELEGRAM_BOT_TOKEN . '/sendMessage');

// ------------------------------------------------------------
// AUTENTICACIÓN GOOGLE SIGN-IN (GIS / OAUTH2) - 100% GRATUITO
// Obtén tu Client ID gratis en: https://console.cloud.google.com/apis/credentials
// Si se deja vacío, el sistema incluye simulación 1-click para desarrollo local
// ------------------------------------------------------------
define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: '');

// ------------------------------------------------------------
// CONFIGURACIÓN DE SESIÓN Y SEGURIDAD
// ------------------------------------------------------------
define('SESSION_NAME',     'ventas_session');
define('CSRF_TOKEN_NAME',  'csrf_token');

// ------------------------------------------------------------
// ZONA HORARIA
// ------------------------------------------------------------
date_default_timezone_set('America/Bogota'); // Ajusta a tu país

// ------------------------------------------------------------
// MANEJO DE ERRORES
// En producción: APP_DEBUG = false → los errores no se muestran
// ------------------------------------------------------------
if (APP_DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
