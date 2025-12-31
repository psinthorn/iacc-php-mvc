<?php

namespace App\Http\Middleware;

use App\Auth\TokenManager;
use App\Foundation\Request;
use App\Foundation\Response;

/**
 * AuthMiddleware - Token authentication middleware
 * Validates JWT tokens and attaches user to request
 */
class AuthMiddleware
{
    protected $tokenManager;

    public function __construct(TokenManager $tokenManager)
    {
        $this->tokenManager = $tokenManager;
    }

    /**
     * Handle request authentication
     */
    public function handle(Request $request, callable $next)
    {
        $token = $this->getBearerToken($request);

        if (!$token) {
            return new Response(
                json_encode(['error' => 'Unauthorized', 'message' => 'Token required']),
                401,
                ['Content-Type' => 'application/json']
            );
        }

        try {
            $claims = $this->tokenManager->validateToken($token);

            if (!$claims) {
                return new Response(
                    json_encode(['error' => 'Unauthorized', 'message' => 'Invalid token']),
                    401,
                    ['Content-Type' => 'application/json']
                );
            }

            // Attach user to request
            $request->setUser($claims);

            return $next($request);
        } catch (\Exception $e) {
            return new Response(
                json_encode(['error' => 'Unauthorized', 'message' => $e->getMessage()]),
                401,
                ['Content-Type' => 'application/json']
            );
        }
    }

    /**
     * Extract Bearer token from Authorization header
     */
    protected function getBearerToken(Request $request): ?string
    {
        $header = $request->getHeader('Authorization') ?? '';

        if (empty($header) || !preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return null;
        }

        return trim($matches[1]);
    }
}
