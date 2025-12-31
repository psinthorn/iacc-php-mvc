<?php

namespace App\Controllers;

use App\Services\CustomerService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;

/**
 * CustomerController - Customer management endpoints
 */
class CustomerController extends Controller implements ControllerInterface
{
    protected $customerService;

    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * GET /api/customers
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

            $result = $this->customerService->getAll($filters, $page, $perPage);

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
     * GET /api/customers/:id
     */
    public function show($id)
    {
        try {
            $customer = $this->customerService->getById($id);
            return $this->json(['data' => $customer]);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/customers
     */
    public function store()
    {
        try {
            $data = $this->all();

            $customer = $this->customerService->create($data);

            return $this->json(['data' => $customer], 201);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /api/customers/:id
     */
    public function update($id)
    {
        try {
            $data = $this->all();

            $customer = $this->customerService->update($id, $data);

            return $this->json(['data' => $customer]);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * DELETE /api/customers/:id
     */
    public function destroy($id)
    {
        try {
            $this->customerService->delete($id);

            return $this->json(['message' => 'Customer deleted']);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/customers/:id/check-credit
     */
    public function checkCreditLimit($id)
    {
        try {
            $amount = $this->get('amount');

            if (!$amount) {
                return $this->jsonError('Amount required', 400);
            }

            $canOrder = $this->customerService->checkCreditLimit($id, $amount);

            return $this->json([
                'customer_id' => $id,
                'amount' => $amount,
                'can_order' => $canOrder,
            ]);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (BusinessException $e) {
            return $this->jsonError($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
