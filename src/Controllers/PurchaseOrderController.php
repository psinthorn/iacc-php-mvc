<?php

namespace App\Controllers;

use App\Services\PurchaseOrderService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;

/**
 * PurchaseOrderController - Purchase order workflow endpoints
 */
class PurchaseOrderController extends Controller implements ControllerInterface
{
    protected $purchaseOrderService;

    public function __construct(PurchaseOrderService $purchaseOrderService)
    {
        $this->purchaseOrderService = $purchaseOrderService;
    }

    /**
     * GET /api/purchase-orders
     */
    public function index()
    {
        try {
            $page = $this->get('page', 1);
            $perPage = $this->get('per_page', 15);
            $status = $this->get('status');

            $filters = [];
            if ($status) {
                $filters['status'] = $status;
            }

            $result = $this->purchaseOrderService->getAll($filters, $page, $perPage);

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
     * GET /api/purchase-orders/:id
     */
    public function show($id)
    {
        try {
            $po = $this->purchaseOrderService->getWithDetails($id);
            return $this->json(['data' => $po]);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/purchase-orders
     */
    public function store()
    {
        try {
            $data = $this->all();

            $po = $this->purchaseOrderService->create($data);

            return $this->json(['data' => $po], 201);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /api/purchase-orders/:id
     */
    public function update($id)
    {
        try {
            $data = $this->all();

            $po = $this->purchaseOrderService->update($id, $data);

            return $this->json(['data' => $po]);
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
     * DELETE /api/purchase-orders/:id
     */
    public function destroy($id)
    {
        try {
            $this->purchaseOrderService->delete($id);

            return $this->json(['message' => 'Purchase order deleted']);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (BusinessException $e) {
            return $this->jsonError($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/purchase-orders/:id/submit
     */
    public function submit($id)
    {
        try {
            $po = $this->purchaseOrderService->submit($id);

            return $this->json([
                'message' => 'Purchase order submitted',
                'data' => $po,
            ]);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (BusinessException $e) {
            return $this->jsonError($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/purchase-orders/:id/approve
     */
    public function approve($id)
    {
        try {
            $po = $this->purchaseOrderService->approve($id);

            return $this->json([
                'message' => 'Purchase order approved',
                'data' => $po,
            ]);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (BusinessException $e) {
            return $this->jsonError($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /api/purchase-orders/pending
     */
    public function getPending()
    {
        try {
            $orders = $this->purchaseOrderService->getPendingOrders();
            return $this->json(['data' => $orders]);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
