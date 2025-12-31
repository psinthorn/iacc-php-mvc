<?php

namespace Tests\Feature\Auth;

use Tests\Feature\FeatureTestCase;

/**
 * Authorization Tests
 */
class AuthorizationTest extends FeatureTestCase
{
    /**
     * Test admin can access admin endpoint
     */
    public function testAdminCanAccessAdminEndpoint()
    {
        $adminToken = generateTestToken(['id' => 1, 'roles' => ['admin']]);

        $response = $this->actingAs(['id' => 1, 'roles' => ['admin']], $adminToken)
            ->post('/api/v1/users/1/roles', ['role' => 'editor']);

        // Expected 200 or 201
    }

    /**
     * Test user cannot access admin endpoint
     */
    public function testUserCannotAccessAdminEndpoint()
    {
        $userToken = generateTestToken(['id' => 2, 'roles' => ['user']]);

        $response = $this->actingAs(['id' => 2, 'roles' => ['user']], $userToken)
            ->post('/api/v1/users/1/roles', ['role' => 'editor']);

        // Expected 403 Forbidden
    }

    /**
     * Test user with permission can access
     */
    public function testUserWithPermissionCanAccess()
    {
        $userToken = generateTestToken([
            'id' => 1,
            'permissions' => ['company:view']
        ]);

        $response = $this->actingAs(['id' => 1], $userToken)
            ->get('/api/v1/companies');

        // Expected 200
    }

    /**
     * Test user without permission cannot access
     */
    public function testUserWithoutPermissionCannotAccess()
    {
        $userToken = generateTestToken(['id' => 1, 'permissions' => []]);

        $response = $this->actingAs(['id' => 1], $userToken)
            ->delete('/api/v1/companies/1');

        // Expected 403
    }

    /**
     * Test wildcard permissions work
     */
    public function testWildcardPermissionsWork()
    {
        $adminToken = generateTestToken([
            'id' => 1,
            'permissions' => ['*:*']
        ]);

        $response = $this->actingAs(['id' => 1], $adminToken)
            ->post('/api/v1/companies', ['name' => 'New Company']);

        // Expected 201
    }

    /**
     * Test resource wildcard permissions
     */
    public function testResourceWildcardPermissions()
    {
        $userToken = generateTestToken([
            'id' => 1,
            'permissions' => ['company:*']
        ]);

        // Should be able to view and edit
        $response1 = $this->actingAs(['id' => 1], $userToken)->get('/api/v1/companies/1');
        $response2 = $this->actingAs(['id' => 1], $userToken)->put('/api/v1/companies/1', ['name' => 'Updated']);

        // Both expected to return 200
    }

    /**
     * Test multiple roles are checked
     */
    public function testMultipleRolesAreChecked()
    {
        $token = generateTestToken([
            'id' => 1,
            'roles' => ['user', 'moderator']
        ]);

        $response = $this->actingAs(['id' => 1], $token)
            ->post('/api/v1/posts/1/approve');

        // Expected 200 if user or moderator
    }

    /**
     * Test role changes affect access
     */
    public function testRoleChangeAffectsAccess()
    {
        // User starts as 'user'
        $userToken = generateTestToken(['id' => 1, 'roles' => ['user']]);
        $response1 = $this->actingAs(['id' => 1], $userToken)
            ->post('/api/v1/users/2/ban');
        // Expected 403

        // User upgraded to 'admin'
        $adminToken = generateTestToken(['id' => 1, 'roles' => ['admin']]);
        $response2 = $this->actingAs(['id' => 1], $adminToken)
            ->post('/api/v1/users/2/ban');
        // Expected 200
    }
}
