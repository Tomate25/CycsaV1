<?php

namespace Cycsa\App\Helpers;

/**
 * Helper para codificar y decodificar IDs numéricos
 * de forma reversible y segura para URLs (tipo Hashids).
 */
class HashHelper {
    private static string $key = 'CycsaSecretKeyLims2026!';
    private static string $method = 'AES-128-ECB';

    public static function codificar(int $id): string {
        $raw_encrypted = openssl_encrypt((string)$id, self::$method, self::$key, OPENSSL_RAW_DATA);
        return rtrim(strtr(base64_encode($raw_encrypted), '+/', '-_'), '=');
    }

    /**
     * Decodifica un hash alfanumérico al ID entero original.
     */
    public static function decodificar(string $hash): ?int {
        $padded = $hash;
        $remainder = strlen($hash) % 4;
        if ($remainder) {
            $padded .= str_repeat('=', 4 - $remainder);
        }
        $decoded = base64_decode(strtr($padded, '-_', '+/'));
        if ($decoded === false) return null;
        $decrypted = openssl_decrypt($decoded, self::$method, self::$key, OPENSSL_RAW_DATA);
        return $decrypted !== false ? (int)$decrypted : null;
    }
}
