<?php
declare(strict_types=1);

namespace Services\Security;

/**
 * Núcleo centralizado de seguridad HTTP, sesiones, CSRF y sanitización de salida.
 */
final class SecurityKernel
{
    public static function isHttps(array $server = null): bool
    {
        $server ??= $_SERVER;
        if (!empty($server['HTTPS']) && strtolower((string)$server['HTTPS']) !== 'off') {
            return true;
        }
        if ((string)($server['SERVER_PORT'] ?? '') === '443') {
            return true;
        }
        // Solo confiar en X-Forwarded-Proto cuando proviene de un proxy configurado en TRUSTED_PROXY_CIDRS
        $trusted = trim((string)(getenv('TRUSTED_PROXY_CIDRS') ?: ''));
        if ($trusted !== '' && class_exists(\Services\Chat\ClientIpResolver::class)) {
            $remoteAddr = (string)($server['REMOTE_ADDR'] ?? '');
            if (\Services\Chat\ClientIpResolver::ipMatchesAnyCidr($remoteAddr, $trusted)) {
                $proto = strtolower(trim(explode(',', (string)($server['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
                if ($proto === 'https') {
                    return true;
                }
            }
        }
        return false;
    }

    public static function startSession(bool $adminContext = false): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if (!headers_sent()) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');

            $secure = self::isHttps();
            $sameSite = $adminContext ? 'Strict' : 'Lax';

            session_name($adminContext ? 'ulat_admin_sid' : 'ulat_web_sid');
            session_set_cookie_params([
                'lifetime' => 0,
                'path'     => '/',
                'domain'   => '',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => $sameSite,
            ]);
        }

        session_start();
    }

    public static function regenerateSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && !headers_sent()) {
            session_regenerate_id(true);
        }
    }

    public static function destroySession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::startSession(true);
        }
        $_SESSION = [];
        if (ini_get('session.use_cookies') && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?? '/',
                $params['domain'] ?? '',
                (bool)($params['secure'] ?? false),
                (bool)($params['httponly'] ?? true)
            );
        }
        session_destroy();
    }

    public static function csrfToken(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::startSession();
        }
        if (empty($_SESSION['_csrf_token']) || !is_string($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }

    public static function csrfInput(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::e(self::csrfToken()) . '">';
    }

    public static function verifyCsrf(?string $provided = null): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            self::startSession();
        }
        $expected = $_SESSION['_csrf_token'] ?? '';
        if (!is_string($expected) || strlen($expected) < 32) {
            return false;
        }
        $candidate = $provided
            ?? ($_POST['csrf_token'] ?? null)
            ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (!is_string($candidate) || $candidate === '') {
            return false;
        }
        return hash_equals($expected, $candidate);
    }

    public static function requireCsrfPost(bool $jsonResponse = false): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            if ($jsonResponse) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
            } else {
                echo 'Método no permitido';
            }
            exit;
        }

        if (!self::verifyCsrf()) {
            http_response_code(403);
            if ($jsonResponse) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'error' => 'Token de seguridad (CSRF) inválido o expirado. Recarga la página.']);
            } else {
                echo 'Token de seguridad (CSRF) inválido o expirado.';
            }
            exit;
        }
    }

    public static function applySecurityHeaders(string $context = 'ulat'): void
    {
        if (headers_sent()) {
            return;
        }

        header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');

        if (self::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }

        if ($context === 'api') {
            header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");
            return;
        }

        // CSP para vistas HTML (ulat / admin) con soporte a Bootstrap CDN, Google Fonts y Chart.js
        $csp = implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdnjs.cloudflare.com",
            "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: https:",
            "connect-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "frame-ancestors 'self'",
            "form-action 'self'",
        ]);
        header('Content-Security-Policy: ' . $csp);

        if ($context === 'admin') {
            header('Cache-Control: private, no-store, no-cache, must-revalidate');
        }
    }

    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Previene CSV Formula Injection en Excel / LibreOffice / Google Sheets.
     */
    public static function sanitizeCsvCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }
        $str = (string)$value;
        $trimmed = ltrim($str, " ");
        if ($trimmed !== '' && in_array($trimmed[0], ['=', '+', '-', '@', "\t", "\r", "\n"], true)) {
            return "'" . $str;
        }
        return $str;
    }

    /**
     * Cifra un secreto en reposo con AES-256-GCM autenticado para almacenamiento seguro en BD.
     */
    public static function encryptSecret(string $plaintext): string
    {
        $plaintext = trim($plaintext);
        if ($plaintext === '') {
            return '';
        }
        $secretKey = defined('APP_SECRET_KEY') ? (string)APP_SECRET_KEY : 'ulat-default-kdf';
        $key = hash('sha256', $secretKey . '|aes-gcm-v1', true);
        $iv  = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($ciphertext === false || strlen($tag) !== 16) {
            return '';
        }
        return 'enc:v1:' . base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Descifra un secreto almacenado con encryptSecret() verificando la etiqueta de autenticidad GCM.
     */
    public static function decryptSecret(string $stored): string
    {
        $stored = trim($stored);
        if ($stored === '') {
            return '';
        }
        if (!str_starts_with($stored, 'enc:v1:')) {
            return $stored;
        }
        $raw = base64_decode(substr($stored, 7), true);
        if (!is_string($raw) || strlen($raw) <= 28) {
            return '';
        }
        $iv         = substr($raw, 0, 12);
        $tag        = substr($raw, 12, 16);
        $ciphertext = substr($raw, 28);
        $secretKey  = defined('APP_SECRET_KEY') ? (string)APP_SECRET_KEY : 'ulat-default-kdf';
        $key        = hash('sha256', $secretKey . '|aes-gcm-v1', true);
        $plain      = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return is_string($plain) ? $plain : '';
    }

    /**
     * Enmascara una clave secreta para mostrarla en la interfaz sin exponer su valor completo.
     */
    public static function maskSecret(string $secret): string
    {
        $secret = trim($secret);
        if ($secret === '') {
            return '';
        }
        if (strlen($secret) <= 10) {
            return '••••••••';
        }
        return substr($secret, 0, 5) . '••••••••' . substr($secret, -4);
    }
}
