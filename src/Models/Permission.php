<?php

namespace App\Models;

use App\Foundation\Model;

/**
 * Permission Model - Fine-grained permission definition
 */
class Permission extends Model
{
    protected $table = 'permission';

    protected $fillable = [
        'name',
        'resource',
        'action',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get roles with this permission
     */
    public function roles()
    {
        return $this->belongsToMany('App\Models\Role', 'role_permission');
    }

    /**
     * Get users with this permission
     */
    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'user_permission');
    }

    /**
     * Check if this permission matches a pattern
     * Supports: "resource:action", "resource:*", "*:*"
     */
    public function matches(string $pattern): bool
    {
        $permissionStr = $this->resource . ':' . $this->action;

        // Exact match
        if ($permissionStr === $pattern) {
            return true;
        }

        // Parse pattern
        [$patResource, $patAction] = array_pad(explode(':', $pattern), 2, '');

        // Pattern has wildcard on action
        if ($patResource === $this->resource && $patAction === '*') {
            return true;
        }

        // Pattern has wildcard on resource
        if ($patResource === '*' && $patAction === $this->action) {
            return true;
        }

        // Pattern is all wildcard
        if ($pattern === '*:*') {
            return true;
        }

        return false;
    }

    /**
     * Format permission for JSON response
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'resource' => $this->resource,
            'action' => $this->action,
            'description' => $this->description,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
