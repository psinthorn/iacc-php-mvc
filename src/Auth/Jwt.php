<?php

namespace App\Auth;

/**
 * JWT - JSON Web Token handler
 * Generates and validates JWT tokens with HS256 algorithm
 */
class Jwt
{
    private $algorithm = 'HS256';
    private $algorithms = [
        'HS256' => 'sha256',
        'HS384' => 'sha384',
        'HS512' => 'sha512',
    ];

    /**
     * Encode claims into JWT token
     */
    public static function encode(array $claims, string $secret, string $algo = 'HS256'): string
    {
        $header = [
            'alg' => $algo,
            'typ' => 'JWT',
        ];

        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $claimsEncoded = self::base64UrlEncode(json_encode($claims));
        $signature = self::createSignature($headerEncoded . '.' . $claimsEncoded, $secret, $algo);

        return $headerEncoded . '.' . $claimsEncoded . '.' . $signature;
    }

    /**
     * Decode JWT token
     */
    public static function decode(string $token, string $secret): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        list($headerEncoded, $claimsEncoded, $signatureProvided) = $parts;

        // Verify signature
        $signatureComputed = self::createSignature(
            $headerEncoded . '.' . $claimsEncoded,
            $secret,
            'HS256'
        );

        if (!self::constantTimeEquals($signatureProvided, $signatureComputed)) {
            return null;
        }

        $claims = json_decode(self::base64UrlDecode($claimsEncoded), true);

        return $claims;
    }

    /**
     * Verify token is valid and not expired
     */
    public static function verify(string $token, string $secret): bool
    {
        $claims = self::decode($token, $secret);

        if (!$claims) {
            return false;
        }

        // Check expiration
        if (isset($claims['exp']) && $claims['exp'] < time()) {
            return false;
        }

        return true;
    }

    /**
     * Check if token is expired
     */
    public static function isExpired(array $claims): bool
    {
        if (!isset($claims['exp'])) {
            return false;
        }

        return $claims['exp'] < time();
    }

    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode(string $data): string
    {
        $padding = 4 - (strlen($data) % 4);
        if ($padding !== 4) {
            $data .= str_repeat('=', $padding);
        }

        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Create signature
     */
    private static function createSignature(string $data, string $secret, string $algo): string
    {
        $algorithm = [
            'HS256' => 'sha256',
            'HS384' => 'sha384',
            'HS512' => 'sha512',
        ][$algo] ?? 'sha256';

        $signature = hash_hmac($algorithm, $data, $secret, true);
        return self::base64UrlEncode($signature);
    }

    /**
     * Constant time string comparison
     */
    private static function constantTimeEquals(string $known, string $user): bool
    {
        if (strlen($known) !== strlen($user)) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < strlen($known); $i++) {
            $result |= ord($known[$i]) ^ ord($user[$i]);
        }

        return $result === 0;
    }
}
