<?php

namespace Tests\Unit\Auth;

use Tests\TestCase;
use App\Auth\Role;
use App\Auth\Permission;

/**
 * Role Tests
 */
class RoleTest extends TestCase
{
    /**
     * Test role can be created
     */
    public function testRoleCanBeCreated()
    {
        $role = new Role(1, 'admin', 'Administrator role');

        $this->assertEquals(1, $role->getId());
        $this->assertEquals('admin', $role->getName());
    }

    /**
     * Test add permission to role
     */
    public function testAddPermissionAddsToCollection()
    {
        $role = new Role(1, 'admin', 'Admin');
        $permission = new Permission(1, 'user:view', 'user', 'view', 'View users');

        $role->addPermission($permission);
        $this->assertTrue($role->hasPermission('user:view'));
    }

    /**
     * Test remove permission from role
     */
    public function testRemovePermissionRemovesFromCollection()
    {
        $role = new Role(1, 'admin', 'Admin');
        $permission = new Permission(1, 'user:view', 'user', 'view', 'View users');

        $role->addPermission($permission);
        $role->removePermission($permission);

        $this->assertFalse($role->hasPermission('user:view'));
    }

    /**
     * Test has permission returns true for existing
     */
    public function testHasPermissionReturnsTrueForExistingPermission()
    {
        $role = new Role(1, 'admin', 'Admin');
        $permission = new Permission(1, 'user:view', 'user', 'view', 'View');

        $role->addPermission($permission);
        $this->assertTrue($role->hasPermission('user:view'));
    }

    /**
     * Test has permission returns false for missing
     */
    public function testHasPermissionReturnsFalseForMissingPermission()
    {
        $role = new Role(1, 'admin', 'Admin');
        $this->assertFalse($role->hasPermission('user:view'));
    }

    /**
     * Test has all permissions
     */
    public function testHasAllPermissionsReturnsTrueWhenAllExist()
    {
        $role = new Role(1, 'admin', 'Admin');
        $role->addPermission(new Permission(1, 'user:view', 'user', 'view', ''));
        $role->addPermission(new Permission(2, 'user:edit', 'user', 'edit', ''));

        $this->assertTrue($role->hasAllPermissions(['user:view', 'user:edit']));
    }

    /**
     * Test has any permission
     */
    public function testHasAnyPermissionReturnsTrueWhenOneExists()
    {
        $role = new Role(1, 'admin', 'Admin');
        $role->addPermission(new Permission(1, 'user:view', 'user', 'view', ''));

        $this->assertTrue($role->hasAnyPermission(['user:view', 'user:edit']));
    }

    /**
     * Test has any permission returns false when none exist
     */
    public function testHasAnyPermissionReturnsFalseWhenNoneExist()
    {
        $role = new Role(1, 'user', 'User');

        $this->assertFalse($role->hasAnyPermission(['admin:view', 'admin:edit']));
    }
}
