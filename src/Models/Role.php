<?php

namespace App\Models;

use App\Foundation\Model;

/**
 * Role Model - User role definition
 */
class Role extends Model
{
    protected $table = 'role';

    protected $fillable = [
        'name',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get users with this role
     */
    public function users()
    {
        return $this->belongsToMany('App\Models\User', 'user_role');
    }

    /**
     * Get permissions for this role
     */
    public function permissions()
    {
        return $this->belongsToMany('App\Models\Permission', 'role_permission');
    }

    /**
     * Check if role has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        $permissions = $this->permissions()->get();
        return $permissions->pluck('name')->contains($permission);
    }

    /**
     * Get permission names
     */
    public function getPermissionNames(): array
    {
        return $this->permissions()->get()->pluck('name')->toArray();
    }

    /**
     * Format role for JSON response
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'permissions' => $this->getPermissionNames(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
