<?php

namespace App\Auth;

/**
 * TokenManager - Manage JWT tokens with blacklist support
 */
class TokenManager
{
    private $jwt;
    private $secret;
    private $expirationTime;
    private $blacklist = [];

    public function __construct(string $secret, int $expirationTime = 3600)
    {
        $this->jwt = new Jwt();
        $this->secret = $secret;
        $this->expirationTime = $expirationTime;
    }

    /**
     * Generate token for user
     */
    public function generateToken(array $user, int $customExpiration = null): string
    {
        $expiresAt = time() + ($customExpiration ?? $this->expirationTime);

        $claims = [
            'sub' => $user['id'],
            'email' => $user['email'],
            'name' => $user['name'],
            'iat' => time(),
            'exp' => $expiresAt,
        ];

        return Jwt::encode($claims, $this->secret);
    }

    /**
     * Validate token
     */
    public function validateToken(string $token): ?array
    {
        if ($this->isTokenBlacklisted($token)) {
            return null;
        }

        return Jwt::decode($token, $this->secret);
    }

    /**
     * Verify token is valid
     */
    public function verifyToken(string $token): bool
    {
        return Jwt::verify($token, $this->secret);
    }

    /**
     * Get claims from token
     */
    public function getClaims(string $token): ?array
    {
        return Jwt::decode($token, $this->secret);
    }

    /**
     * Refresh token (generate new token from old one)
     */
    public function refreshToken(string $oldToken, int $customExpiration = null): ?string
    {
        $claims = $this->validateToken($oldToken);

        if (!$claims) {
            return null;
        }

        // Revoke old token
        $this->revokeToken($oldToken);

        // Generate new token
        $user = [
            'id' => $claims['sub'],
            'email' => $claims['email'],
            'name' => $claims['name'],
        ];

        return $this->generateToken($user, $customExpiration);
    }

    /**
     * Revoke token (add to blacklist)
     */
    public function revokeToken(string $token): void
    {
        $claims = Jwt::decode($token, $this->secret);

        if (!$claims || !isset($claims['exp'])) {
            return;
        }

        $this->blacklist[$token] = $claims['exp'];
    }

    /**
     * Check if token is blacklisted
     */
    public function isTokenBlacklisted(string $token): bool
    {
        return isset($this->blacklist[$token]);
    }

    /**
     * Get token expiration time
     */
    public function getTokenExpiration(string $token): ?int
    {
        $claims = Jwt::decode($token, $this->secret);

        if (!$claims || !isset($claims['exp'])) {
            return null;
        }

        return $claims['exp'];
    }

    /**
     * Check if token is expired
     */
    public function isTokenExpired(string $token): bool
    {
        $claims = Jwt::decode($token, $this->secret);

        if (!$claims) {
            return true;
        }

        return Jwt::isExpired($claims);
    }
}
