<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\CompanyService;
use App\Repositories\CompanyRepository;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * Company Service Tests
 */
class CompanyServiceTest extends TestCase
{
    protected $companyService;
    protected $companyRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->companyRepository = $this->createMock(CompanyRepository::class);

        $this->companyService = new CompanyService(
            $this->companyRepository,
            $this->db,
            new \App\Foundation\Logger(),
            new \App\Validation\Validator(),
            new \App\Events\EventBus()
        );
    }

    /**
     * Test list companies returns paginated results
     */
    public function testListCompaniesReturnsPaginatedResults()
    {
        $companies = [
            (object)['id' => 1, 'name' => 'Company 1', 'email' => 'company1@test.com'],
            (object)['id' => 2, 'name' => 'Company 2', 'email' => 'company2@test.com'],
        ];

        $this->companyRepository->method('paginate')->willReturn([
            'data' => $companies,
            'total' => 2,
            'per_page' => 10,
            'page' => 1,
        ]);

        $result = $this->companyService->listCompanies(1, 10);

        $this->assertCount(2, $result['data']);
        $this->assertEquals(2, $result['total']);
    }

    /**
     * Test get company returns company data
     */
    public function testGetCompanyReturnsCompanyData()
    {
        $company = (object)[
            'id' => 1,
            'name' => 'Test Company',
            'email' => 'test@example.com',
            'phone' => '1234567890',
        ];

        $this->companyRepository->method('find')->willReturn($company);

        $result = $this->companyService->getCompany(1);

        $this->assertEquals(1, $result->id);
        $this->assertEquals('Test Company', $result->name);
    }

    /**
     * Test get nonexistent company throws exception
     */
    public function testGetNonexistentCompanyThrowsException()
    {
        $this->companyRepository->method('find')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->companyService->getCompany(999);
    }

    /**
     * Test create company with valid data
     */
    public function testCreateCompanyWithValidData()
    {
        $data = [
            'name' => 'New Company',
            'email' => 'new@example.com',
            'phone' => '1234567890',
        ];

        $company = (object)array_merge($data, ['id' => 1]);
        $this->companyRepository->method('create')->willReturn($company);

        $result = $this->companyService->createCompany($data);

        $this->assertEquals('New Company', $result->name);
        $this->assertEquals('new@example.com', $result->email);
    }

    /**
     * Test create company without required fields throws validation exception
     */
    public function testCreateCompanyWithoutRequiredFieldsThrowsException()
    {
        $data = [
            'email' => 'test@example.com',
            // missing 'name'
        ];

        $this->expectException(ValidationException::class);
        $this->companyService->createCompany($data);
    }

    /**
     * Test update company
     */
    public function testUpdateCompanyWithValidData()
    {
        $data = [
            'name' => 'Updated Company',
            'phone' => '9876543210',
        ];

        $company = (object)array_merge($data, ['id' => 1, 'email' => 'test@example.com']);
        $this->companyRepository->method('find')->willReturn($company);
        $this->companyRepository->method('update')->willReturn($company);

        $result = $this->companyService->updateCompany(1, $data);

        $this->assertEquals('Updated Company', $result->name);
        $this->assertEquals('9876543210', $result->phone);
    }

    /**
     * Test delete company
     */
    public function testDeleteCompanyRemovesRecord()
    {
        $company = (object)['id' => 1, 'name' => 'Test Company'];
        $this->companyRepository->method('find')->willReturn($company);
        $this->companyRepository->method('delete')->willReturn(true);

        $result = $this->companyService->deleteCompany(1);

        $this->assertTrue($result);
    }

    /**
     * Test search companies
     */
    public function testSearchCompaniesReturnsFilteredResults()
    {
        $companies = [
            (object)['id' => 1, 'name' => 'ABC Company'],
        ];

        $this->companyRepository->method('search')->willReturn($companies);

        $result = $this->companyService->searchCompanies('ABC');

        $this->assertCount(1, $result);
        $this->assertEquals('ABC Company', $result[0]->name);
    }

    /**
     * Test filter companies by criteria
     */
    public function testFilterCompaniesByCriteria()
    {
        $companies = [
            (object)['id' => 1, 'name' => 'Test Company', 'status' => 'active'],
        ];

        $this->companyRepository->method('filter')->willReturn($companies);

        $result = $this->companyService->filterCompanies(['status' => 'active']);

        $this->assertCount(1, $result);
        $this->assertEquals('active', $result[0]->status);
    }
}
