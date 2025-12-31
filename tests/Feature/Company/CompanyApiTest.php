<?php

namespace Tests\Feature\Company;

use Tests\Feature\FeatureTestCase;

/**
 * Company API Tests
 */
class CompanyApiTest extends FeatureTestCase
{
    /**
     * Test list companies returns 200
     */
    public function testListCompaniesReturns200()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['company:view']]);

        $response = $this->actingAs(['id' => 1], $token)->get('/api/v1/companies');

        $this->assertOk();
    }

    /**
     * Test list companies returns paginated results
     */
    public function testListCompaniesReturnsPaginatedResults()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['company:view']]);

        $response = $this->actingAs(['id' => 1], $token)->get('/api/v1/companies?page=1&per_page=10');

        $this->assertJsonHas('data');
        $this->assertJsonHas('pagination');
    }

    /**
     * Test show company returns 200
     */
    public function testShowCompanyReturns200()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['company:view']]);

        $response = $this->actingAs(['id' => 1], $token)->get('/api/v1/companies/1');

        $this->assertOk();
    }

    /**
     * Test show nonexistent company returns 404
     */
    public function testShowNonexistentCompanyReturns404()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['company:view']]);

        $response = $this->actingAs(['id' => 1], $token)->get('/api/v1/companies/99999');

        $this->assertNotFound();
    }

    /**
     * Test create company with valid data returns 201
     */
    public function testCreateCompanyWithValidDataReturns201()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['company:create']]);

        $data = [
            'name' => 'New Company',
            'email' => 'newcompany@example.com',
            'phone' => '1234567890',
        ];

        $response = $this->actingAs(['id' => 1], $token)->post('/api/v1/companies', $data);

        $this->assertCreated();
    }

    /**
     * Test create company without auth returns 401
     */
    public function testCreateCompanyWithoutAuthReturns401()
    {
        $data = [
            'name' => 'New Company',
            'email' => 'newcompany@example.com',
        ];

        $response = $this->post('/api/v1/companies', $data);

        $this->assertUnauthorized();
    }

    /**
     * Test create company without permission returns 403
     */
    public function testCreateCompanyWithoutPermissionReturns403()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['company:view']]);

        $data = [
            'name' => 'New Company',
            'email' => 'newcompany@example.com',
        ];

        $response = $this->actingAs(['id' => 1], $token)->post('/api/v1/companies', $data);

        $this->assertForbidden();
    }

    /**
     * Test create company with invalid data returns 422
     */
    public function testCreateCompanyWithInvalidDataReturns422()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['company:create']]);

        $data = [
            'email' => 'invalid-email',
            // missing 'name'
        ];

        $response = $this->actingAs(['id' => 1], $token)->post('/api/v1/companies', $data);

        $this->assertUnprocessable();
    }

    /**
     * Test update company with valid data returns 200
     */
    public function testUpdateCompanyWithValidDataReturns200()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['company:edit']]);

        $data = [
            'name' => 'Updated Company',
            'phone' => '9876543210',
        ];

        $response = $this->actingAs(['id' => 1], $token)->put('/api/v1/companies/1', $data);

        $this->assertOk();
    }

    /**
     * Test update company without auth returns 401
     */
    public function testUpdateCompanyWithoutAuthReturns401()
    {
        $data = ['name' => 'Updated Company'];

        $response = $this->put('/api/v1/companies/1', $data);

        $this->assertUnauthorized();
    }

    /**
     * Test update nonexistent company returns 404
     */
    public function testUpdateNonexistentCompanyReturns404()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['company:edit']]);

        $data = ['name' => 'Updated Company'];

        $response = $this->actingAs(['id' => 1], $token)->put('/api/v1/companies/99999', $data);

        $this->assertNotFound();
    }

    /**
     * Test delete company returns 200
     */
    public function testDeleteCompanyReturns200()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['company:delete']]);

        $response = $this->actingAs(['id' => 1], $token)->delete('/api/v1/companies/1');

        $this->assertOk();
    }

    /**
     * Test delete company without auth returns 401
     */
    public function testDeleteCompanyWithoutAuthReturns401()
    {
        $response = $this->delete('/api/v1/companies/1');

        $this->assertUnauthorized();
    }

    /**
     * Test filter companies by status
     */
    public function testFilterCompaniesByStatus()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['company:view']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->get('/api/v1/companies?status=active');

        $this->assertOk();
    }

    /**
     * Test sort companies
     */
    public function testSortCompanies()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['company:view']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->get('/api/v1/companies?sort=name&order=asc');

        $this->assertOk();
    }

    /**
     * Test search companies
     */
    public function testSearchCompanies()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['company:view']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->get('/api/v1/companies?search=ABC');

        $this->assertOk();
    }
}
