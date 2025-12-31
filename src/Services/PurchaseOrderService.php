<?php

namespace App\Services;

use App\Repositories\PurchaseOrderRepository;
use App\Repositories\PurchaseOrderDetailRepository;
use App\Repositories\ReceiveItemRepository;
use App\Repositories\ProductRepository;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;
use App\Events\PurchaseOrderCreated;
use App\Events\PurchaseOrderSubmitted;
use App\Events\PurchaseOrderReceived;

/**
 * PurchaseOrderService - Purchase order management
 */
class PurchaseOrderService extends Service implements ServiceInterface
{
    protected $repository;
    protected $detailRepository;
    protected $receiveRepository;
    protected $productRepository;

    public function __construct(
        PurchaseOrderRepository $repository,
        PurchaseOrderDetailRepository $detailRepository,
        ReceiveItemRepository $receiveRepository,
        ProductRepository $productRepository,
        \App\Foundation\Database $database,
        \App\Foundation\Logger $logger,
        \App\Validation\Validator $validator,
        \App\Events\EventBus $eventBus = null
    ) {
        parent::__construct($database, $logger, $validator, $eventBus);
        $this->repository = $repository;
        $this->detailRepository = $detailRepository;
        $this->receiveRepository = $receiveRepository;
        $this->productRepository = $productRepository;
    }

    public function getAll($filters = [], $page = 1, $perPage = 15)
    {
        $query = $this->repository->all();

        if (!empty($filters['status'])) {
            $query = array_filter($query, fn($po) => $po->status == $filters['status']);
        }
        if (!empty($filters['supplier_id'])) {
            $query = array_filter($query, fn($po) => $po->supplier_id == $filters['supplier_id']);
        }

        $total = count($query);
        $items = array_slice($query, ($page - 1) * $perPage, $perPage);

        return [
            'data' => array_values($items),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
        ];
    }

    public function getById($id)
    {
        $po = $this->repository->find($id);
        if (!$po) {
            throw new NotFoundException("Purchase order not found");
        }
        return $po;
    }

    /**
     * Create purchase order with line items
     */
    public function create(array $data)
    {
        $errors = $this->validate($data, [
            'po_number' => 'required|unique:purchase_order,po_number',
            'supplier_id' => 'required|exists:supplier,id',
            'po_date' => 'required|date:Y-m-d',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:product,id',
            'items.*.quantity' => 'required|numeric|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $this->transaction(function () use ($data) {
            $total = 0;

            // Create PO
            $po = $this->repository->create([
                'po_number' => $data['po_number'],
                'supplier_id' => $data['supplier_id'],
                'po_date' => $data['po_date'],
                'total_amount' => 0,
                'status' => 'draft',
            ]);

            // Create line items
            foreach ($data['items'] as $item) {
                $amount = $item['quantity'] * $item['unit_price'];
                $total += $amount;

                $this->detailRepository->create([
                    'po_id' => $po->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $amount,
                ]);
            }

            // Update total
            $this->repository->update($po->id, ['total_amount' => $total]);

            $this->log('po_created', [
                'po_id' => $po->id,
                'po_number' => $po->po_number,
                'total' => $total,
                'items' => count($data['items']),
            ]);

            $this->dispatch(new PurchaseOrderCreated($po));

            return $this->repository->find($po->id);
        });
    }

    public function update($id, array $data)
    {
        $po = $this->getById($id);

        if ($po->status !== 'draft') {
            throw new BusinessException("Can only edit draft purchase orders");
        }

        $errors = $this->validate($data, [
            'delivery_date' => 'date:Y-m-d',
            'notes' => 'string|max:1000',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $this->transaction(function () use ($id, $data) {
            $po = $this->repository->update($id, array_filter($data));

            $this->log('po_updated', ['po_id' => $id]);

            return $po;
        });
    }

    /**
     * Submit PO for approval
     */
    public function submit($id)
    {
        $po = $this->getById($id);

        if ($po->status !== 'draft') {
            throw new BusinessException("Only draft POs can be submitted");
        }

        return $this->transaction(function () use ($id, $po) {
            $this->repository->update($id, [
                'status' => 'submitted',
                'submitted_at' => date('Y-m-d H:i:s'),
            ]);

            $this->log('po_submitted', ['po_id' => $id]);
            $this->dispatch(new PurchaseOrderSubmitted($po));

            return $this->repository->find($id);
        });
    }

    /**
     * Approve PO
     */
    public function approve($id)
    {
        $po = $this->getById($id);

        if ($po->status !== 'submitted') {
            throw new BusinessException("Only submitted POs can be approved");
        }

        return $this->transaction(function () use ($id) {
            $this->repository->update($id, ['status' => 'approved']);
            $this->log('po_approved', ['po_id' => $id]);
            return $this->repository->find($id);
        });
    }

    public function delete($id)
    {
        $po = $this->getById($id);

        if ($po->status !== 'draft') {
            throw new BusinessException("Can only delete draft purchase orders");
        }

        return $this->transaction(function () use ($id) {
            // Delete items
            $items = $this->detailRepository->where('po_id', $id);
            foreach ($items as $item) {
                $this->detailRepository->delete($item->id);
            }

            // Delete PO
            $this->repository->delete($id);
            $this->log('po_deleted', ['po_id' => $id]);

            return true;
        });
    }

    public function restore($id)
    {
        throw new BusinessException("Restore not yet implemented");
    }

    /**
     * Get PO with details
     */
    public function getWithDetails($id)
    {
        $po = $this->getById($id);
        $po->details = $this->detailRepository->where('po_id', $id);
        return $po;
    }

    /**
     * Get pending orders
     */
    public function getPendingOrders()
    {
        return $this->repository->getPendingOrders();
    }
}
