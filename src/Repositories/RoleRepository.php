<?php

namespace App\Repositories;

use App\Foundation\Database;
use App\Models\Role;

/**
 * RoleRepository - Role data access layer
 */
class RoleRepository extends Repository
{
    protected $modelClass = Role::class;
    protected $table = 'role';

    public function __construct(Database $database = null)
    {
        parent::__construct($database, new Role());
    }

    /**
     * Find role by name
     */
    public function findByName(string $name)
    {
        return $this->findBy('name', $name);
    }

    /**
     * Get role with permissions
     */
    public function findWithPermissions(int $roleId)
    {
        $role = $this->find($roleId);

        if (!$role) {
            return null;
        }

        // Load permissions
        $stmt = $this->db->prepare(
            "SELECT p.* FROM permission p
             INNER JOIN role_permission rp ON p.id = rp.permission_id
             WHERE rp.role_id = ?
             ORDER BY p.resource, p.action"
        );
        $stmt->execute([$roleId]);
        $permissions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $role->permissions = $permissions;
        return $role;
    }

    /**
     * Assign permission to role
     */
    public function assignPermission(int $roleId, int $permissionId)
    {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO role_permission (role_id, permission_id) VALUES (?, ?)"
        );
        return $stmt->execute([$roleId, $permissionId]);
    }

    /**
     * Remove permission from role
     */
    public function removePermission(int $roleId, int $permissionId)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM role_permission WHERE role_id = ? AND permission_id = ?"
        );
        return $stmt->execute([$roleId, $permissionId]);
    }

    /**
     * Get all permissions for role
     */
    public function getPermissions(int $roleId)
    {
        $stmt = $this->db->prepare(
            "SELECT p.* FROM permission p
             INNER JOIN role_permission rp ON p.id = rp.permission_id
             WHERE rp.role_id = ?
             ORDER BY p.name"
        );
        $stmt->execute([$roleId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
