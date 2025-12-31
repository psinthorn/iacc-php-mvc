<?php

namespace App\Controllers;

use App\Services\CompanyService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * CompanyController - Company management endpoints
 */
class CompanyController extends Controller implements ControllerInterface
{
    protected $companyService;

    public function __construct(CompanyService $companyService)
    {
        $this->companyService = $companyService;
    }

    /**
     * GET /api/companies
     */
    public function index()
    {
        try {
            $page = $this->get('page', 1);
            $perPage = $this->get('per_page', 15);
            $search = $this->get('search');

            $filters = [];
            if ($search) {
                $filters['search'] = $search;
            }

            $result = $this->companyService->getAll($filters, $page, $perPage);

            return $this->jsonPaginated(
                $result['data'],
                $result['page'],
                $result['per_page'],
                $result['total']
            );
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /api/companies/:id
     */
    public function show($id)
    {
        try {
            $company = $this->companyService->getById($id);
            return $this->json(['data' => $company]);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/companies
     */
    public function store()
    {
        try {
            $data = $this->all();

            $company = $this->companyService->create($data);

            return $this->json(['data' => $company], 201);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /api/companies/:id
     */
    public function update($id)
    {
        try {
            $data = $this->all();

            $company = $this->companyService->update($id, $data);

            return $this->json(['data' => $company]);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * DELETE /api/companies/:id
     */
    public function destroy($id)
    {
        try {
            $this->companyService->delete($id);

            return $this->json(['message' => 'Company deleted']);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /api/companies/:code/by-code
     */
    public function getByCode($code)
    {
        try {
            $company = $this->companyService->findByCode($code);
            return $this->json(['data' => $company]);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /api/companies/active
     */
    public function getActive()
    {
        try {
            $companies = $this->companyService->getActiveCompanies();
            return $this->json(['data' => $companies]);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
