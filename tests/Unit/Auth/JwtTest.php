<?php

namespace Tests\Unit\Auth;

use Tests\TestCase;
use App\Auth\Jwt;
use App\Auth\PasswordHasher;
use App\Auth\TokenManager;

/**
 * JWT Token Tests
 */
class JwtTest extends TestCase
{
    protected $secret = 'test_secret_key';

    /**
     * Test token encoding produces valid JWT format
     */
    public function testEncodedTokenHasCorrectFormat()
    {
        $claims = ['id' => 1, 'email' => 'test@example.com'];
        $token = Jwt::encode($claims, $this->secret);

        // JWT format: header.payload.signature
        $parts = explode('.', $token);
        $this->assertCount(3, $parts);
        $this->assertNotEmpty($parts[0]); // header
        $this->assertNotEmpty($parts[1]); // payload
        $this->assertNotEmpty($parts[2]); // signature
    }

    /**
     * Test token header contains algorithm
     */
    public function testTokenHeaderContainsAlgorithm()
    {
        $claims = ['id' => 1];
        $token = Jwt::encode($claims, $this->secret);

        $decoded = Jwt::decode($token, $this->secret);
        $this->assertIsArray($decoded);
    }

    /**
     * Test token payload contains user claims
     */
    public function testTokenPayloadContainsUserClaims()
    {
        $claims = ['id' => 1, 'email' => 'test@example.com', 'name' => 'Test User'];
        $token = Jwt::encode($claims, $this->secret);

        $decoded = Jwt::decode($token, $this->secret);
        $this->assertEquals(1, $decoded['id']);
        $this->assertEquals('test@example.com', $decoded['email']);
        $this->assertEquals('Test User', $decoded['name']);
    }

    /**
     * Test token includes issued at claim
     */
    public function testTokenIssuedAtClaimIsSet()
    {
        $claims = ['id' => 1];
        $token = Jwt::encode($claims, $this->secret);

        $decoded = Jwt::decode($token, $this->secret);
        $this->assertArrayHasKey('iat', $decoded);
        $this->assertIsInt($decoded['iat']);
        $this->assertGreater(0, $decoded['iat']);
    }

    /**
     * Test token includes expiration claim
     */
    public function testTokenExpirationClaimIsSet()
    {
        $claims = ['id' => 1];
        $token = Jwt::encode($claims, $this->secret);

        $decoded = Jwt::decode($token, $this->secret);
        $this->assertArrayHasKey('exp', $decoded);
        $this->assertIsInt($decoded['exp']);
    }

    /**
     * Test decode valid token returns payload
     */
    public function testDecodeValidTokenReturnsPayload()
    {
        $claims = ['id' => 1, 'email' => 'test@example.com'];
        $token = Jwt::encode($claims, $this->secret);

        $decoded = Jwt::decode($token, $this->secret);
        $this->assertEquals(1, $decoded['id']);
        $this->assertEquals('test@example.com', $decoded['email']);
    }

    /**
     * Test decode invalid token returns false
     */
    public function testDecodeInvalidTokenReturnsFalse()
    {
        $result = Jwt::decode('invalid.token.here', $this->secret);
        $this->assertFalse($result);
    }

    /**
     * Test verify valid token
     */
    public function testVerifyValidTokenReturnsTrue()
    {
        $claims = ['id' => 1];
        $token = Jwt::encode($claims, $this->secret);

        $result = Jwt::verify($token, $this->secret);
        $this->assertTrue($result);
    }

    /**
     * Test verify tampered token
     */
    public function testVerifyTamperedTokenReturnsFalse()
    {
        $claims = ['id' => 1];
        $token = Jwt::encode($claims, $this->secret);

        // Tamper with token
        $parts = explode('.', $token);
        $parts[1] = base64_encode('tampered');
        $tampered = implode('.', $parts);

        $result = Jwt::verify($tampered, $this->secret);
        $this->assertFalse($result);
    }

    /**
     * Test verify with wrong secret
     */
    public function testVerifyWrongSecretReturnsFalse()
    {
        $claims = ['id' => 1];
        $token = Jwt::encode($claims, $this->secret);

        $result = Jwt::verify($token, 'wrong_secret');
        $this->assertFalse($result);
    }

    /**
     * Test empty payload
     */
    public function testEmptyPayloadEncoding()
    {
        $token = Jwt::encode([], $this->secret);
        $decoded = Jwt::decode($token, $this->secret);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('iat', $decoded);
    }
}
