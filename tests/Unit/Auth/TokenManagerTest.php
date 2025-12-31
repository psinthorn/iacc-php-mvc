<?php

namespace Tests\Unit\Auth;

use Tests\TestCase;
use App\Auth\TokenManager;
use App\Auth\Jwt;

/**
 * Token Manager Tests
 */
class TokenManagerTest extends TestCase
{
    protected $secret = 'test_secret_key';
    protected $tokenManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenManager = new TokenManager($this->secret, 3600);
    }

    /**
     * Test generate token creates valid JWT
     */
    public function testGenerateTokenCreatesValidJwt()
    {
        $user = ['id' => 1, 'email' => 'test@example.com'];
        $token = $this->tokenManager->generateToken($user);

        $this->assertIsString($token);
        $this->assertStringContainsString('.', $token);
    }

    /**
     * Test generate token includes user data
     */
    public function testGenerateTokenIncludesUserData()
    {
        $user = ['id' => 1, 'email' => 'test@example.com', 'name' => 'Test User'];
        $token = $this->tokenManager->generateToken($user);

        $claims = $this->tokenManager->getClaims($token);
        $this->assertEquals(1, $claims['id']);
        $this->assertEquals('test@example.com', $claims['email']);
    }

    /**
     * Test validate token returns claims
     */
    public function testValidateTokenReturnsClaimsForValidToken()
    {
        $user = ['id' => 1, 'email' => 'test@example.com'];
        $token = $this->tokenManager->generateToken($user);

        $claims = $this->tokenManager->validateToken($token);
        $this->assertIsArray($claims);
        $this->assertEquals(1, $claims['id']);
    }

    /**
     * Test validate invalid token returns false
     */
    public function testValidateTokenReturnsFalseForInvalidToken()
    {
        $result = $this->tokenManager->validateToken('invalid.token.here');
        $this->assertFalse($result);
    }

    /**
     * Test refresh token creates new token
     */
    public function testRefreshTokenCreatesNewToken()
    {
        $user = ['id' => 1, 'email' => 'test@example.com'];
        $oldToken = $this->tokenManager->generateToken($user);
        $newToken = $this->tokenManager->refreshToken($oldToken);

        $this->assertNotEquals($oldToken, $newToken);
        $this->assertIsString($newToken);
    }

    /**
     * Test refresh token revokes old token
     */
    public function testRefreshTokenRevokesOldToken()
    {
        $user = ['id' => 1, 'email' => 'test@example.com'];
        $oldToken = $this->tokenManager->generateToken($user);
        $newToken = $this->tokenManager->refreshToken($oldToken);

        $this->assertTrue($this->tokenManager->isTokenBlacklisted($oldToken));
    }

    /**
     * Test revoke token adds to blacklist
     */
    public function testRevokeTokenAddsToBlacklist()
    {
        $user = ['id' => 1];
        $token = $this->tokenManager->generateToken($user);

        $this->tokenManager->revokeToken($token);
        $this->assertTrue($this->tokenManager->isTokenBlacklisted($token));
    }

    /**
     * Test validate blacklisted token returns false
     */
    public function testValidateBlacklistedTokenReturnsFalse()
    {
        $user = ['id' => 1];
        $token = $this->tokenManager->generateToken($user);

        $this->tokenManager->revokeToken($token);
        $result = $this->tokenManager->validateToken($token);

        $this->assertFalse($result);
    }

    /**
     * Test get token expiration
     */
    public function testGetTokenExpirationReturnsCorrectTime()
    {
        $user = ['id' => 1];
        $token = $this->tokenManager->generateToken($user);

        $expiration = $this->tokenManager->getTokenExpiration($token);
        $this->assertIsInt($expiration);
        $this->assertGreater(time(), $expiration);
    }
}
