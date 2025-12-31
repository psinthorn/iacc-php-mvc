<?php

namespace App\Repositories;

use App\Foundation\Database;
use App\Models\User;

/**
 * UserRepository - User data access layer with authentication support
 */
class UserRepository extends Repository
{
    protected $modelClass = User::class;
    protected $table = 'user';

    public function __construct(Database $database)
    {
        parent::__construct($database, new User());
    }

    /**
     * Find user by email
     */
    public function findByEmail($email)
    {
        return $this->findBy('email', $email);
    }

    /**
     * Find user by username
     */
    public function findByUsername($username)
    {
        return $this->findBy('username', $username);
    }

    /**
     * Check if email exists
     */
    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    /**
     * Get active users
     */
    public function getActiveUsers()
    {
        return array_filter($this->all(), fn($u) => $u->status == 1);
    }

    /**
     * Get user with roles
     */
    public function findWithRoles(int $userId)
    {
        $user = $this->find($userId);

        if (!$user) {
            return null;
        }

        // Load roles
        $stmt = $this->db->prepare(
            "SELECT r.* FROM role r 
             INNER JOIN user_role ur ON r.id = ur.role_id 
             WHERE ur.user_id = ?"
        );
        $stmt->execute([$userId]);
        $roles = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $user->roles = $roles;
        return $user;
    }

    /**
     * Get user with permissions
     */
    public function findWithPermissions(int $userId)
    {
        $user = $this->find($userId);

        if (!$user) {
            return null;
        }

        // Load permissions through roles and direct assignments
        $stmt = $this->db->prepare(
            "SELECT DISTINCT p.* FROM permission p
             LEFT JOIN role_permission rp ON p.id = rp.permission_id
             LEFT JOIN user_role ur ON rp.role_id = ur.role_id
             LEFT JOIN user_permission up ON p.id = up.permission_id
             WHERE ur.user_id = ? OR up.user_id = ?
             ORDER BY p.resource, p.action"
        );
        $stmt->execute([$userId, $userId]);
        $permissions = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $user->permissions = $permissions;
        return $user;
    }

    /**
     * Assign role to user
     */
    public function assignRole(int $userId, int $roleId)
    {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO user_role (user_id, role_id) VALUES (?, ?)"
        );
        return $stmt->execute([$userId, $roleId]);
    }

    /**
     * Remove role from user
     */
    public function removeRole(int $userId, int $roleId)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM user_role WHERE user_id = ? AND role_id = ?"
        );
        return $stmt->execute([$userId, $roleId]);
    }

    /**
     * Assign permission to user (direct)
     */
    public function assignPermission(int $userId, int $permissionId)
    {
        $stmt = $this->db->prepare(
            "INSERT IGNORE INTO user_permission (user_id, permission_id) VALUES (?, ?)"
        );
        return $stmt->execute([$userId, $permissionId]);
    }

    /**
     * Remove permission from user
     */
    public function removePermission(int $userId, int $permissionId)
    {
        $stmt = $this->db->prepare(
            "DELETE FROM user_permission WHERE user_id = ? AND permission_id = ?"
        );
        return $stmt->execute([$userId, $permissionId]);
    }

    /**
     * Update last login
     */
    public function updateLastLogin(int $userId)
    {
        return $this->update($userId, [
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

