<?php

namespace App\Controllers;

use App\Services\SupplierService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * SupplierController - Supplier management endpoints
 */
class SupplierController extends Controller implements ControllerInterface
{
    protected $supplierService;

    public function __construct(SupplierService $supplierService)
    {
        $this->supplierService = $supplierService;
    }

    /**
     * GET /api/suppliers
     */
    public function index()
    {
        try {
            $page = $this->get('page', 1);
            $perPage = $this->get('per_page', 15);
            $companyId = $this->get('company_id');

            $filters = [];
            if ($companyId) {
                $filters['company_id'] = $companyId;
            }

            $result = $this->supplierService->getAll($filters, $page, $perPage);

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
     * GET /api/suppliers/:id
     */
    public function show($id)
    {
        try {
            $supplier = $this->supplierService->getById($id);
            return $this->json(['data' => $supplier]);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/suppliers
     */
    public function store()
    {
        try {
            $data = $this->all();

            $supplier = $this->supplierService->create($data);

            return $this->json(['data' => $supplier], 201);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /api/suppliers/:id
     */
    public function update($id)
    {
        try {
            $data = $this->all();

            $supplier = $this->supplierService->update($id, $data);

            return $this->json(['data' => $supplier]);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * DELETE /api/suppliers/:id
     */
    public function destroy($id)
    {
        try {
            $this->supplierService->delete($id);

            return $this->json(['message' => 'Supplier deleted']);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
