<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\AuthService;
use App\Auth\TokenManager;
use App\Repositories\UserRepository;
use App\Exceptions\ValidationException;
use App\Exceptions\BusinessException;

/**
 * Auth Service Tests
 */
class AuthServiceTest extends TestCase
{
    protected $authService;
    protected $tokenManager;
    protected $userRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tokenManager = new TokenManager('test_secret', 3600);
        $this->userRepository = $this->createMock(UserRepository::class);

        $this->authService = new AuthService(
            $this->tokenManager,
            $this->userRepository,
            $this->db,
            new \App\Foundation\Logger(),
            new \App\Validation\Validator(),
            new \App\Events\EventBus()
        );
    }

    /**
     * Test create token returns valid structure
     */
    public function testCreateTokenReturnsValidToken()
    {
        $user = (object)[
            'id' => 1,
            'email' => 'test@example.com',
            'name' => 'Test User',
        ];

        $result = $this->authService->createToken($user);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertEquals('Bearer', $result['token_type']);
    }

    /**
     * Test create token includes user data
     */
    public function testCreateTokenIncludesUserData()
    {
        $user = (object)[
            'id' => 1,
            'email' => 'test@example.com',
            'name' => 'Test User',
        ];

        $result = $this->authService->createToken($user);
        $token = $result['token'];

        $claims = $this->tokenManager->getClaims($token);
        $this->assertEquals(1, $claims['id']);
        $this->assertEquals('test@example.com', $claims['email']);
    }

    /**
     * Test validate token returns claims
     */
    public function testValidateTokenReturnsClaims()
    {
        $user = (object)[
            'id' => 1,
            'email' => 'test@example.com',
        ];

        $result = $this->authService->createToken($user);
        $token = $result['token'];

        $claims = $this->authService->validateToken($token);
        $this->assertIsArray($claims);
        $this->assertEquals(1, $claims['id']);
    }

    /**
     * Test validate invalid token throws exception
     */
    public function testValidateTokenThrowsExceptionForInvalidToken()
    {
        $this->expectException(BusinessException::class);
        $this->authService->validateToken('invalid.token');
    }

    /**
     * Test logout revokes token
     */
    public function testLogoutRevokesToken()
    {
        $user = (object)['id' => 1, 'email' => 'test@example.com'];
        $result = $this->authService->createToken($user);
        $token = $result['token'];

        $this->authService->logout($token);

        $this->assertTrue($this->tokenManager->isTokenBlacklisted($token));
    }

    /**
     * Test refresh token creates new token
     */
    public function testRefreshTokenCreatesNewToken()
    {
        $user = (object)['id' => 1, 'email' => 'test@example.com'];
        $oldTokenResult = $this->authService->createToken($user);
        $oldToken = $oldTokenResult['token'];

        $newResult = $this->authService->refreshToken($oldToken);

        $this->assertNotEquals($oldToken, $newResult['token']);
        $this->assertArrayHasKey('token', $newResult);
        $this->assertArrayHasKey('expires_in', $newResult);
    }
}
