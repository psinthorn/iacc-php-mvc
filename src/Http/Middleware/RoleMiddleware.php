<?php

namespace App\Http\Middleware;

use App\Foundation\Request;
use App\Foundation\Response;

/**
 * RoleMiddleware - Role-based access control middleware
 * Checks if user has required role(s)
 */
class RoleMiddleware
{
    /**
     * Handle role authorization
     * Usage: RoleMiddleware:admin or RoleMiddleware:admin,supervisor
     */
    public function handle(Request $request, callable $next, string ...$roles)
    {
        if (!$request->isAuthenticated()) {
            return new Response(
                json_encode(['error' => 'Unauthorized', 'message' => 'Authentication required']),
                401,
                ['Content-Type' => 'application/json']
            );
        }

        $user = $request->user();
        $userRoles = $user['roles'] ?? [];

        // Check if user has any of the required roles
        $hasRole = false;
        foreach ($roles as $role) {
            if (in_array(trim($role), $userRoles, true)) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            return new Response(
                json_encode([
                    'error' => 'Forbidden',
                    'message' => 'Required role(s): ' . implode(', ', $roles),
                ]),
                403,
                ['Content-Type' => 'application/json']
            );
        }

        return $next($request);
    }
}
