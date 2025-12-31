<?php

namespace App\Services;

use App\Auth\PasswordHasher;
use App\Auth\TokenManager;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;

/**
 * AuthService - User authentication and authorization
 */
class AuthService extends Service
{
    protected $tokenManager;
    protected $userRepository;

    public function __construct(
        TokenManager $tokenManager,
        \App\Repositories\UserRepository $userRepository = null,
        \App\Foundation\Database $database,
        \App\Foundation\Logger $logger,
        \App\Validation\Validator $validator,
        \App\Events\EventBus $eventBus = null
    ) {
        parent::__construct($database, $logger, $validator, $eventBus);
        $this->tokenManager = $tokenManager;
        $this->userRepository = $userRepository;
    }

    /**
     * Register new user
     */
    public function register(array $data)
    {
        $errors = $this->validate($data, [
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|unique:user,email',
            'password' => 'required|min:8',
            'password_confirmation' => 'required|confirmed',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Validate password strength
        $passwordErrors = PasswordHasher::validateStrength($data['password']);
        if (!empty($passwordErrors)) {
            throw new ValidationException(['password' => implode(', ', $passwordErrors)]);
        }

        return $this->transaction(function () use ($data) {
            // Hash password
            $hashedPassword = PasswordHasher::hash($data['password']);

            // Create user
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $hashedPassword,
            ]);

            $this->log('user_registered', [
                'user_id' => $user->id,
                'email' => $data['email'],
            ]);

            return $user;
        });
    }

    /**
     * Login user
     */
    public function login(string $email, string $password)
    {
        $errors = $this->validate(
            compact('email', 'password'),
            [
                'email' => 'required|email',
                'password' => 'required',
            ]
        );

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Find user by email
        $user = $this->userRepository->findByEmail($email);

        if (!$user || !PasswordHasher::verify($password, $user->password)) {
            throw new BusinessException('Invalid email or password');
        }

        // Update last login
        $this->userRepository->update($user->id, [
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);

        $this->log('user_login', [
            'user_id' => $user->id,
            'email' => $email,
        ]);

        return $user;
    }

    /**
     * Create token for user
     */
    public function createToken($user)
    {
        $userData = [
            'id' => $user->id,
            'email' => $user->email,
            'name' => $user->name,
        ];

        $token = $this->tokenManager->generateToken($userData);

        return [
            'token' => $token,
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Validate token
     */
    public function validateToken(string $token)
    {
        $claims = $this->tokenManager->validateToken($token);

        if (!$claims) {
            throw new BusinessException('Invalid or expired token');
        }

        return $claims;
    }

    /**
     * Refresh token
     */
    public function refreshToken(string $oldToken)
    {
        $newToken = $this->tokenManager->refreshToken($oldToken);

        if (!$newToken) {
            throw new BusinessException('Invalid or expired token');
        }

        return [
            'token' => $newToken,
            'expires_in' => 3600,
            'token_type' => 'Bearer',
        ];
    }

    /**
     * Logout user (revoke token)
     */
    public function logout(string $token)
    {
        $this->tokenManager->revokeToken($token);

        $claims = $this->tokenManager->getClaims($token);
        if ($claims) {
            $this->log('user_logout', ['user_id' => $claims['sub']]);
        }
    }

    /**
     * Update user password
     */
    public function updatePassword(int $userId, string $oldPassword, string $newPassword)
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new NotFoundException('User not found');
        }

        if (!PasswordHasher::verify($oldPassword, $user->password)) {
            throw new BusinessException('Current password is incorrect');
        }

        // Validate new password strength
        $passwordErrors = PasswordHasher::validateStrength($newPassword);
        if (!empty($passwordErrors)) {
            throw new ValidationException(['password' => implode(', ', $passwordErrors)]);
        }

        return $this->transaction(function () use ($userId, $newPassword) {
            $this->userRepository->update($userId, [
                'password' => PasswordHasher::hash($newPassword),
            ]);

            $this->log('user_password_changed', ['user_id' => $userId]);

            return true;
        });
    }

    /**
     * Reset password (by email)
     */
    public function resetPassword(string $email)
    {
        $user = $this->userRepository->findByEmail($email);

        if (!$user) {
            throw new NotFoundException('User not found');
        }

        // Generate reset token
        $resetToken = $this->tokenManager->generateToken(
            ['id' => $user->id, 'email' => $user->email, 'name' => $user->name],
            3600 // 1 hour expiration for reset token
        );

        $this->log('password_reset_requested', ['user_id' => $user->id, 'email' => $email]);

        return [
            'reset_token' => $resetToken,
            'expires_in' => 3600,
        ];
    }

    /**
     * Get user by ID
     */
    public function getUserById(int $userId)
    {
        $user = $this->userRepository->find($userId);

        if (!$user) {
            throw new NotFoundException('User not found');
        }

        return $user;
    }
}
