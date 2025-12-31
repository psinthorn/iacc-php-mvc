<?php

namespace App\Controllers;

use App\Services\ReceivingService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;

/**
 * ReceivingController - Inbound inventory management
 */
class ReceivingController extends Controller
{
    protected $receivingService;

    public function __construct(ReceivingService $receivingService)
    {
        $this->receivingService = $receivingService;
    }

    /**
     * POST /api/receiving/:poId/items
     */
    public function receiveItems($poId)
    {
        try {
            $data = $this->all();
            $data['po_id'] = $poId;

            $result = $this->receivingService->receiveItems($poId, $data['items'] ?? []);

            return $this->json([
                'message' => 'Items received',
                'data' => $result,
            ], 201);
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
     * GET /api/receiving/:poId/items
     */
    public function getReceipts($poId)
    {
        try {
            $items = $this->receivingService->getReceivedItems($poId);
            return $this->json(['data' => $items]);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
