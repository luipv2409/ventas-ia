<?php
declare(strict_types=1);

namespace Services\Chat;

/**
 * Resolución segura de IP cliente resistente a spoofing de cabeceras HTTP.
 *
 * Comportamiento por defecto (TRUSTED_PROXY_CIDRS vacío):
 *  - Usa SIEMPRE $_SERVER['REMOTE_ADDR'] e ignora CF-Connecting-IP / X-Forwarded-For.
 *
 * Cuando TRUSTED_PROXY_CIDRS contiene rangos CIDR (separados por comas):
 *  - Solo inspecciona cabeceras reenviadas si la conexión TCP real (REMOTE_ADDR)
 *    proviene de uno de esos rangos confiables.
 */
final class ClientIpResolver
{
    public static function resolve(?array $server = null, ?string $trustedCidrsOverride = null): string
    {
        $server ??= $_SERVER;
        $remoteAddr = trim((string)($server['REMOTE_ADDR'] ?? ''));
        if (!filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
            return '0.0.0.0';
        }

        $trustedCidrs = $trustedCidrsOverride
            ?? (defined('TRUSTED_PROXY_CIDRS') ? (string)TRUSTED_PROXY_CIDRS : (string)(getenv('TRUSTED_PROXY_CIDRS') ?: ''));
        $trustedCidrs = trim($trustedCidrs);

        if ($trustedCidrs === '' || !self::ipMatchesAnyCidr($remoteAddr, $trustedCidrs)) {
            return $remoteAddr;
        }

        // 1. Cabecera canónica inyectada por Cloudflare en su borde
        if (!empty($server['HTTP_CF_CONNECTING_IP'])) {
            $cfIp = trim((string)$server['HTTP_CF_CONNECTING_IP']);
            if (filter_var($cfIp, FILTER_VALIDATE_IP)) {
                return $cfIp;
            }
        }

        // 2. X-Forwarded-For recorrido de derecha a izquierda descartando saltos confiables
        if (!empty($server['HTTP_X_FORWARDED_FOR'])) {
            $hops = array_reverse(array_map('trim', explode(',', (string)$server['HTTP_X_FORWARDED_FOR'])));
            foreach ($hops as $hop) {
                if (!filter_var($hop, FILTER_VALIDATE_IP)) {
                    continue;
                }
                if (!self::ipMatchesAnyCidr($hop, $trustedCidrs)) {
                    return $hop;
                }
            }
        }

        return $remoteAddr;
    }

    public static function ipMatchesAnyCidr(string $ip, string $cidrList): bool
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }
        $entries = preg_split('/[\s,;]+/', trim($cidrList), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($entries as $cidr) {
            if (self::ipInCidr($ip, $cidr)) {
                return true;
            }
        }
        return false;
    }

    public static function ipInCidr(string $ip, string $cidr): bool
    {
        $cidr = trim($cidr);
        if ($cidr === '') {
            return false;
        }

        if (!str_contains($cidr, '/')) {
            $ipBin  = @inet_pton($ip);
            $netBin = @inet_pton($cidr);
            return $ipBin !== false && $netBin !== false && $ipBin === $netBin;
        }

        [$subnet, $bitsRaw] = explode('/', $cidr, 2);
        if (!ctype_digit($bitsRaw)) {
            return false;
        }
        $bits = (int)$bitsRaw;

        $ipBin  = @inet_pton($ip);
        $netBin = @inet_pton($subnet);
        if ($ipBin === false || $netBin === false || strlen($ipBin) !== strlen($netBin)) {
            return false;
        }

        $maxBits = strlen($ipBin) * 8; // 32 IPv4, 128 IPv6
        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }
        if ($bits === 0) {
            return true;
        }

        $fullBytes = intdiv($bits, 8);
        $remBits   = $bits % 8;

        if ($fullBytes > 0 && substr($ipBin, 0, $fullBytes) !== substr($netBin, 0, $fullBytes)) {
            return false;
        }

        if ($remBits > 0) {
            $mask = (0xFF << (8 - $remBits)) & 0xFF;
            if ((ord($ipBin[$fullBytes]) & $mask) !== (ord($netBin[$fullBytes]) & $mask)) {
                return false;
            }
        }

        return true;
    }
}
