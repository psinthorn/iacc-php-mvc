<?php

namespace App\Services;

use App\Repositories\DeliveryRepository;
use App\Repositories\DeliveryDetailRepository;
use App\Repositories\SalesOrderRepository;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;
use App\Events\DeliveryCreated;
use App\Events\DeliveryCompleted;

/**
 * DeliveryService - Delivery tracking
 */
class DeliveryService extends Service implements ServiceInterface
{
    protected $repository;
    protected $detailRepository;
    protected $soRepository;

    public function __construct(
        DeliveryRepository $repository,
        DeliveryDetailRepository $detailRepository,
        SalesOrderRepository $soRepository,
        \App\Foundation\Database $database,
        \App\Foundation\Logger $logger,
        \App\Validation\Validator $validator,
        \App\Events\EventBus $eventBus = null
    ) {
        parent::__construct($database, $logger, $validator, $eventBus);
        $this->repository = $repository;
        $this->detailRepository = $detailRepository;
        $this->soRepository = $soRepository;
    }

    public function getAll($filters = [], $page = 1, $perPage = 15)
    {
        $query = $this->repository->all();

        if (!empty($filters['status'])) {
            $query = array_filter($query, fn($d) => $d->status == $filters['status']);
        }

        $total = count($query);
        $items = array_slice($query, ($page - 1) * $perPage, $perPage);

        return ['data' => array_values($items), 'page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => ceil($total / $perPage)];
    }

    public function getById($id)
    {
        $delivery = $this->repository->find($id);
        if (!$delivery) {
            throw new NotFoundException("Delivery not found");
        }
        return $delivery;
    }

    public function create(array $data)
    {
        $errors = $this->validate($data, [
            'delivery_number' => 'required|unique:delivery,delivery_number',
            'so_id' => 'required|exists:sales_order,id',
            'customer_id' => 'required|exists:customer,id',
            'destination' => 'required|string',
            'items' => 'required|array|min:1',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $this->transaction(function () use ($data) {
            $delivery = $this->repository->create([
                'delivery_number' => $data['delivery_number'],
                'delivery_date' => date('Y-m-d'),
                'so_id' => $data['so_id'],
                'customer_id' => $data['customer_id'],
                'destination' => $data['destination'],
                'status' => 'scheduled',
            ]);

            foreach ($data['items'] as $item) {
                $this->detailRepository->create([
                    'delivery_id' => $delivery->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                ]);
            }

            $this->log('delivery_created', ['delivery_id' => $delivery->id]);
            $this->dispatch(new DeliveryCreated($delivery));

            return $this->repository->find($delivery->id);
        });
    }

    public function update($id, array $data)
    {
        $delivery = $this->getById($id);

        if ($delivery->status === 'completed') {
            throw new BusinessException("Cannot update completed deliveries");
        }

        return $this->transaction(function () use ($id, $data) {
            $delivery = $this->repository->update($id, array_filter($data));
            $this->log('delivery_updated', ['delivery_id' => $id]);
            return $delivery;
        });
    }

    /**
     * Mark delivery as completed
     */
    public function complete($id)
    {
        $delivery = $this->getById($id);

        if ($delivery->status === 'completed') {
            throw new BusinessException("Delivery already completed");
        }

        return $this->transaction(function () use ($id, $delivery) {
            $this->repository->update($id, ['status' => 'completed']);
            $this->log('delivery_completed', ['delivery_id' => $id]);
            $this->dispatch(new DeliveryCompleted($this->repository->find($id)));
            return $this->repository->find($id);
        });
    }

    public function delete($id)
    {
        $delivery = $this->getById($id);

        if ($delivery->status !== 'scheduled') {
            throw new BusinessException("Can only delete scheduled deliveries");
        }

        return $this->transaction(function () use ($id) {
            $this->repository->delete($id);
            $this->log('delivery_deleted', ['delivery_id' => $id]);
            return true;
        });
    }

    public function restore($id)
    {
        throw new BusinessException("Restore not yet implemented");
    }
}
