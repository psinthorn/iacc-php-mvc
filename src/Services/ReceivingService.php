<?php

namespace App\Services;

use App\Repositories\PurchaseOrderRepository;
use App\Repositories\ReceiveItemRepository;
use App\Repositories\StockRepository;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;
use App\Events\ItemsReceived;

/**
 * ReceivingService - Receiving/Inbound inventory management
 */
class ReceivingService extends Service
{
    protected $poRepository;
    protected $receiveRepository;
    protected $stockRepository;

    public function __construct(
        PurchaseOrderRepository $poRepository,
        ReceiveItemRepository $receiveRepository,
        StockRepository $stockRepository,
        \App\Foundation\Database $database,
        \App\Foundation\Logger $logger,
        \App\Validation\Validator $validator,
        \App\Events\EventBus $eventBus = null
    ) {
        parent::__construct($database, $logger, $validator, $eventBus);
        $this->poRepository = $poRepository;
        $this->receiveRepository = $receiveRepository;
        $this->stockRepository = $stockRepository;
    }

    /**
     * Receive items for purchase order
     */
    public function receiveItems($poId, array $items)
    {
        $po = $this->poRepository->find($poId);
        if (!$po) {
            throw new NotFoundException("Purchase order not found");
        }

        if ($po->status !== 'approved') {
            throw new BusinessException("Can only receive approved purchase orders");
        }

        $errors = $this->validate(['items' => $items], [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:product,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.warehouse_id' => 'required|exists:warehouse,id',
            'items.*.location_id' => 'required|exists:location,id',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $this->transaction(function () use ($po, $items) {
            $receivedItems = [];

            foreach ($items as $item) {
                // Record receipt
                $receipt = $this->receiveRepository->create([
                    'po_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'received_date' => date('Y-m-d H:i:s'),
                    'remarks' => $item['remarks'] ?? '',
                ]);

                // Update stock
                $this->updateStock(
                    $item['product_id'],
                    $item['warehouse_id'],
                    $item['location_id'],
                    $item['quantity']
                );

                $receivedItems[] = $receipt;
            }

            // Update PO status
            $this->poRepository->update($po->id, [
                'status' => 'received',
                'received_at' => date('Y-m-d H:i:s'),
            ]);

            $this->log('items_received', [
                'po_id' => $po->id,
                'count' => count($items),
                'total_qty' => array_sum(array_column($items, 'quantity')),
            ]);

            $this->dispatch(new ItemsReceived($po));

            return $receivedItems;
        });
    }

    /**
     * Update stock after receiving
     */
    protected function updateStock($productId, $warehouseId, $locationId, $quantity)
    {
        // Get existing stock
        $stocks = $this->stockRepository->all();
        $stock = null;

        foreach ($stocks as $s) {
            if ($s->product_id == $productId && $s->warehouse_id == $warehouseId && $s->location_id == $locationId) {
                $stock = $s;
                break;
            }
        }

        if ($stock) {
            // Update existing
            $this->stockRepository->update($stock->id, [
                'quantity' => $stock->quantity + $quantity,
                'last_updated' => date('Y-m-d H:i:s'),
            ]);
        } else {
            // Create new stock record
            $this->stockRepository->create([
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'location_id' => $locationId,
                'quantity' => $quantity,
                'last_updated' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Get received items for PO
     */
    public function getReceivedItems($poId)
    {
        return $this->receiveRepository->where('po_id', $poId);
    }
}
