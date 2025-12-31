<?php

namespace Tests\Unit\Auth;

use Tests\TestCase;
use App\Auth\Permission;

/**
 * Permission Tests
 */
class PermissionTest extends TestCase
{
    /**
     * Test permission can be created
     */
    public function testPermissionCanBeCreated()
    {
        $permission = new Permission(1, 'user:view', 'user', 'view', 'View users');

        $this->assertEquals(1, $permission->getId());
        $this->assertEquals('user:view', $permission->getName());
        $this->assertEquals('user', $permission->getResource());
        $this->assertEquals('view', $permission->getAction());
    }

    /**
     * Test matches exact permission
     */
    public function testMatchesExactPermission()
    {
        $permission = new Permission(1, 'user:view', 'user', 'view', '');

        $this->assertTrue($permission->matches('user:view'));
    }

    /**
     * Test matches resource wildcard
     */
    public function testMatchesResourceWildcard()
    {
        $permission = new Permission(1, 'user:*', 'user', '*', '');

        $this->assertTrue($permission->matches('user:view'));
        $this->assertTrue($permission->matches('user:edit'));
    }

    /**
     * Test matches all wildcard
     */
    public function testMatchesAllWildcard()
    {
        $permission = new Permission(1, '*:*', '*', '*', '');

        $this->assertTrue($permission->matches('user:view'));
        $this->assertTrue($permission->matches('admin:delete'));
    }

    /**
     * Test does not match different resource
     */
    public function testDoesNotMatchDifferentResource()
    {
        $permission = new Permission(1, 'user:view', 'user', 'view', '');

        $this->assertFalse($permission->matches('admin:view'));
    }

    /**
     * Test does not match different action
     */
    public function testDoesNotMatchDifferentAction()
    {
        $permission = new Permission(1, 'user:view', 'user', 'view', '');

        $this->assertFalse($permission->matches('user:edit'));
    }
}
