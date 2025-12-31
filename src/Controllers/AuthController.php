<?php

namespace App\Controllers;

use App\Services\AuthService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;

/**
 * AuthController - User authentication endpoints
 */
class AuthController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * POST /api/v1/auth/register
     */
    public function register()
    {
        try {
            $data = $this->all();

            $user = $this->authService->register($data);
            $tokenData = $this->authService->createToken($user);

            return $this->json([
                'message' => 'User registered successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $tokenData['token'],
                'expires_in' => $tokenData['expires_in'],
            ], 201);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login()
    {
        try {
            $email = $this->get('email');
            $password = $this->get('password');

            if (!$email || !$password) {
                return $this->jsonError('Email and password required', 400);
            }

            $user = $this->authService->login($email, $password);
            $tokenData = $this->authService->createToken($user);

            return $this->json([
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'token' => $tokenData['token'],
                'expires_in' => $tokenData['expires_in'],
            ]);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (BusinessException $e) {
            return $this->jsonError($e->getMessage(), 401);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout()
    {
        try {
            $token = $this->getBearerToken();

            if (!$token) {
                return $this->jsonError('Token required', 400);
            }

            $this->authService->logout($token);

            return $this->json(['message' => 'Logged out successfully']);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/v1/auth/refresh
     */
    public function refresh()
    {
        try {
            $data = $this->all();

            if (!isset($data['token'])) {
                return $this->jsonError('Token required', 400);
            }

            $tokenData = $this->authService->refreshToken($data['token']);

            return $this->json([
                'message' => 'Token refreshed',
                'token' => $tokenData['token'],
                'expires_in' => $tokenData['expires_in'],
            ]);
        } catch (BusinessException $e) {
            return $this->jsonError($e->getMessage(), 401);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /api/v1/auth/profile
     */
    public function profile()
    {
        try {
            if (!$this->isAuthenticated()) {
                return $this->jsonError('Unauthorized', 401);
            }

            $user = $this->user();

            return $this->json([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at ?? null,
                ],
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /api/v1/auth/profile
     */
    public function updateProfile()
    {
        try {
            if (!$this->isAuthenticated()) {
                return $this->jsonError('Unauthorized', 401);
            }

            $user = $this->user();
            $data = $this->all();

            // Only allow name update
            $allowedFields = ['name'];
            $updateData = array_intersect_key($data, array_flip($allowedFields));

            if (empty($updateData)) {
                return $this->jsonError('No valid fields to update', 400);
            }

            $errors = $this->validate($updateData, [
                'name' => 'string|min:2|max:255',
            ]);

            if (!empty($errors)) {
                return $this->jsonError('Validation failed', 422, $errors);
            }

            // TODO: Update user in repository
            // $updatedUser = $this->userRepository->update($user->id, $updateData);

            return $this->json([
                'message' => 'Profile updated',
                'data' => $user,
            ]);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /api/v1/auth/password
     */
    public function updatePassword()
    {
        try {
            if (!$this->isAuthenticated()) {
                return $this->jsonError('Unauthorized', 401);
            }

            $user = $this->user();
            $data = $this->all();

            $errors = $this->validate($data, [
                'old_password' => 'required',
                'password' => 'required|min:8',
                'password_confirmation' => 'required|confirmed',
            ]);

            if (!empty($errors)) {
                return $this->jsonError('Validation failed', 422, $errors);
            }

            $this->authService->updatePassword(
                $user->id,
                $data['old_password'],
                $data['password']
            );

            return $this->json(['message' => 'Password updated successfully']);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (BusinessException $e) {
            return $this->jsonError($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/v1/auth/reset-password
     */
    public function resetPassword()
    {
        try {
            $email = $this->get('email');

            if (!$email) {
                return $this->jsonError('Email required', 400);
            }

            $resetData = $this->authService->resetPassword($email);

            return $this->json([
                'message' => 'Reset token generated',
                'reset_token' => $resetData['reset_token'],
                'expires_in' => $resetData['expires_in'],
            ]);
        } catch (NotFoundException $e) {
            // Don't reveal if user exists
            return $this->json([
                'message' => 'If email exists, reset token will be sent',
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Extract Bearer token from Authorization header
     */
    protected function getBearerToken(): ?string
    {
        $header = $this->request->getHeader('Authorization') ?? '';

        if (!preg_match('/Bearer\s+(.+)/', $header, $matches)) {
            return null;
        }

        return $matches[1];
    }
}
