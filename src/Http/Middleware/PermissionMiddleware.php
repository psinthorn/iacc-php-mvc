<?php

namespace App\Http\Middleware;

use App\Foundation\Request;
use App\Foundation\Response;

/**
 * PermissionMiddleware - Permission-based access control middleware
 * Checks if user has required permission(s)
 */
class PermissionMiddleware
{
    /**
     * Handle permission authorization
     * Usage: PermissionMiddleware:company:view or PermissionMiddleware:product:view,product:edit
     */
    public function handle(Request $request, callable $next, string ...$permissions)
    {
        if (!$request->isAuthenticated()) {
            return new Response(
                json_encode(['error' => 'Unauthorized', 'message' => 'Authentication required']),
                401,
                ['Content-Type' => 'application/json']
            );
        }

        $user = $request->user();
        $userPermissions = $user['permissions'] ?? [];

        // Normalize requested permissions
        $requiredPermissions = [];
        foreach ($permissions as $permission) {
            $requiredPermissions[] = trim($permission);
        }

        // Check if user has any of the required permissions
        $hasPermission = false;
        foreach ($requiredPermissions as $required) {
            if ($this->matchesPermission($required, $userPermissions)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            return new Response(
                json_encode([
                    'error' => 'Forbidden',
                    'message' => 'Required permission(s): ' . implode(', ', $requiredPermissions),
                ]),
                403,
                ['Content-Type' => 'application/json']
            );
        }

        return $next($request);
    }

    /**
     * Check if required permission matches user permissions
     * Supports wildcards: "company:view", "company:*", "*:*"
     */
    protected function matchesPermission(string $required, array $userPermissions): bool
    {
        foreach ($userPermissions as $userPermission) {
            if ($this->permissionMatches($userPermission, $required)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if permission string matches required pattern
     */
    protected function permissionMatches(string $permission, string $required): bool
    {
        // Exact match
        if ($permission === $required) {
            return true;
        }

        // User has wildcard permissions
        if ($permission === '*:*') {
            return true;
        }

        // Parse resource:action pattern
        [$userResource, $userAction] = array_pad(explode(':', $permission), 2, '');
        [$reqResource, $reqAction] = array_pad(explode(':', $required), 2, '');

        // User has all actions on resource
        if ($userResource === $reqResource && $userAction === '*') {
            return true;
        }

        // User has all resources with specific action
        if ($userResource === '*' && $userAction === $reqAction) {
            return true;
        }

        return false;
    }
}
