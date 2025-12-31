<?php

namespace App\Controllers;

use App\Services\DeliveryService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;

/**
 * DeliveryController - Delivery tracking and fulfillment
 */
class DeliveryController extends Controller implements ControllerInterface
{
    protected $deliveryService;

    public function __construct(DeliveryService $deliveryService)
    {
        $this->deliveryService = $deliveryService;
    }

    /**
     * GET /api/deliveries
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

            $result = $this->deliveryService->getAll($filters, $page, $perPage);

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
     * GET /api/deliveries/:id
     */
    public function show($id)
    {
        try {
            $delivery = $this->deliveryService->getById($id);
            return $this->json(['data' => $delivery]);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/deliveries
     */
    public function store()
    {
        try {
            $data = $this->all();

            $delivery = $this->deliveryService->create($data);

            return $this->json(['data' => $delivery], 201);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /api/deliveries/:id
     */
    public function update($id)
    {
        try {
            $data = $this->all();

            $delivery = $this->deliveryService->update($id, $data);

            return $this->json(['data' => $delivery]);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * DELETE /api/deliveries/:id
     */
    public function destroy($id)
    {
        try {
            $this->deliveryService->delete($id);

            return $this->json(['message' => 'Delivery deleted']);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/deliveries/:id/complete
     */
    public function complete($id)
    {
        try {
            $delivery = $this->deliveryService->complete($id);

            return $this->json([
                'message' => 'Delivery completed',
                'data' => $delivery,
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
