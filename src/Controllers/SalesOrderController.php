<?php

namespace App\Controllers;

use App\Services\SalesOrderService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;

/**
 * SalesOrderController - Sales order workflow endpoints
 */
class SalesOrderController extends Controller implements ControllerInterface
{
    protected $salesOrderService;

    public function __construct(SalesOrderService $salesOrderService)
    {
        $this->salesOrderService = $salesOrderService;
    }

    /**
     * GET /api/sales-orders
     */
    public function index()
    {
        try {
            $page = $this->get('page', 1);
            $perPage = $this->get('per_page', 15);
            $status = $this->get('status');
            $customerId = $this->get('customer_id');

            $filters = [];
            if ($status) {
                $filters['status'] = $status;
            }
            if ($customerId) {
                $filters['customer_id'] = $customerId;
            }

            $result = $this->salesOrderService->getAll($filters, $page, $perPage);

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
     * GET /api/sales-orders/:id
     */
    public function show($id)
    {
        try {
            $so = $this->salesOrderService->getWithDetails($id);
            return $this->json(['data' => $so]);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/sales-orders
     */
    public function store()
    {
        try {
            $data = $this->all();

            $so = $this->salesOrderService->create($data);

            return $this->json(['data' => $so], 201);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /api/sales-orders/:id
     */
    public function update($id)
    {
        try {
            $data = $this->all();

            $so = $this->salesOrderService->update($id, $data);

            return $this->json(['data' => $so]);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (BusinessException $e) {
            return $this->jsonError($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * DELETE /api/sales-orders/:id
     */
    public function destroy($id)
    {
        try {
            $this->salesOrderService->delete($id);

            return $this->json(['message' => 'Sales order deleted']);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (BusinessException $e) {
            return $this->jsonError($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/sales-orders/:id/confirm
     */
    public function confirm($id)
    {
        try {
            $so = $this->salesOrderService->confirm($id);

            return $this->json([
                'message' => 'Sales order confirmed',
                'data' => $so,
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
