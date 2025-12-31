<?php

namespace App\Auth;

/**
 * Role - Role-based access control role definition
 */
class Role
{
    protected $id;
    protected $name;
    protected $description;
    protected $permissions = [];

    public function __construct(int $id = 0, string $name, string $description = '')
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
    }

    /**
     * Get role ID
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Get role name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get role description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Add permission to role
     */
    public function addPermission(Permission $permission): void
    {
        $this->permissions[$permission->getName()] = $permission;
    }

    /**
     * Remove permission from role
     */
    public function removePermission(Permission $permission): void
    {
        unset($this->permissions[$permission->getName()]);
    }

    /**
     * Check if role has permission
     */
    public function hasPermission(string $permission): bool
    {
        return isset($this->permissions[$permission]);
    }

    /**
     * Check if role has all permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if role has any permission
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all permissions
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * Get permission names
     */
    public function getPermissionNames(): array
    {
        return array_keys($this->permissions);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'permissions' => $this->getPermissionNames(),
        ];
    }
}
