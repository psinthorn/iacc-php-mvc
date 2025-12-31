<?php

namespace App\Models;

use App\Foundation\Model;

/**
 * User Model - Authentication and authorization
 */
class User extends Model
{
    protected $table = 'user';

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'last_login_at',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
    ];

    /**
     * Get user's roles
     */
    public function roles()
    {
        // Relationship to roles through user_role pivot table
        return $this->belongsToMany('App\Models\Role', 'user_role');
    }

    /**
     * Get user's permissions (through roles)
     */
    public function permissions()
    {
        // Get permissions through roles
        return $this->belongsToMany('App\Models\Permission', 'user_permission');
    }

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        $roles = $this->roles()->get();
        return $roles->pluck('name')->contains($role);
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        $userRoles = $this->roles()->get()->pluck('name')->toArray();
        return !empty(array_intersect($roles, $userRoles));
    }

    /**
     * Check if user has all of the given roles
     */
    public function hasAllRoles(array $roles): bool
    {
        $userRoles = $this->roles()->get()->pluck('name')->toArray();
        return count(array_intersect($roles, $userRoles)) === count($roles);
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions()->get();
        return $permissions->pluck('name')->contains($permission);
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        $userPermissions = $this->permissions()->get()->pluck('name')->toArray();
        return !empty(array_intersect($permissions, $userPermissions));
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        $userPermissions = $this->permissions()->get()->pluck('name')->toArray();
        return count(array_intersect($permissions, $userPermissions)) === count($permissions);
    }

    /**
     * Get user data for token claims
     */
    public function getTokenData(): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'name' => $this->name,
            'roles' => $this->roles()->get()->pluck('name')->toArray(),
            'permissions' => $this->permissions()->get()->pluck('name')->toArray(),
        ];
    }

    /**
     * Format user data for JSON response
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
