<?php

namespace App\Services;

use App\Repositories\SalesOrderRepository;
use App\Repositories\SalesOrderDetailRepository;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;
use App\Events\SalesOrderCreated;
use App\Events\SalesOrderConfirmed;
use App\Events\SalesOrderCancelled;

/**
 * SalesOrderService - Sales order management
 */
class SalesOrderService extends Service implements ServiceInterface
{
    protected $repository;
    protected $detailRepository;

    public function __construct(
        SalesOrderRepository $repository,
        SalesOrderDetailRepository $detailRepository,
        \App\Foundation\Database $database,
        \App\Foundation\Logger $logger,
        \App\Validation\Validator $validator,
        \App\Events\EventBus $eventBus = null
    ) {
        parent::__construct($database, $logger, $validator, $eventBus);
        $this->repository = $repository;
        $this->detailRepository = $detailRepository;
    }

    public function getAll($filters = [], $page = 1, $perPage = 15)
    {
        $query = $this->repository->all();

        if (!empty($filters['status'])) {
            $query = array_filter($query, fn($so) => $so->status == $filters['status']);
        }
        if (!empty($filters['customer_id'])) {
            $query = array_filter($query, fn($so) => $so->customer_id == $filters['customer_id']);
        }

        $total = count($query);
        $items = array_slice($query, ($page - 1) * $perPage, $perPage);

        return ['data' => array_values($items), 'page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => ceil($total / $perPage)];
    }

    public function getById($id)
    {
        $so = $this->repository->find($id);
        if (!$so) {
            throw new NotFoundException("Sales order not found");
        }
        return $so;
    }

    public function create(array $data)
    {
        $errors = $this->validate($data, [
            'so_number' => 'required|unique:sales_order,so_number',
            'customer_id' => 'required|exists:customer,id',
            'so_date' => 'required|date:Y-m-d',
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

            $so = $this->repository->create([
                'so_number' => $data['so_number'],
                'customer_id' => $data['customer_id'],
                'so_date' => $data['so_date'],
                'total_amount' => 0,
                'status' => 'draft',
            ]);

            foreach ($data['items'] as $item) {
                $amount = $item['quantity'] * $item['unit_price'];
                $total += $amount;

                $this->detailRepository->create([
                    'so_id' => $so->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'amount' => $amount,
                ]);
            }

            $this->repository->update($so->id, ['total_amount' => $total]);

            $this->log('so_created', ['so_id' => $so->id, 'so_number' => $so->so_number, 'total' => $total]);
            $this->dispatch(new SalesOrderCreated($so));

            return $this->repository->find($so->id);
        });
    }

    public function update($id, array $data)
    {
        $so = $this->getById($id);

        if ($so->status !== 'draft') {
            throw new BusinessException("Can only edit draft sales orders");
        }

        return $this->transaction(function () use ($id, $data) {
            $so = $this->repository->update($id, array_filter($data));
            $this->log('so_updated', ['so_id' => $id]);
            return $so;
        });
    }

    public function confirm($id)
    {
        $so = $this->getById($id);

        if ($so->status !== 'draft') {
            throw new BusinessException("Only draft SOs can be confirmed");
        }

        return $this->transaction(function () use ($id) {
            $this->repository->update($id, ['status' => 'confirmed']);
            $this->log('so_confirmed', ['so_id' => $id]);
            $this->dispatch(new SalesOrderConfirmed($this->repository->find($id)));
            return $this->repository->find($id);
        });
    }

    public function delete($id)
    {
        $so = $this->getById($id);

        if ($so->status !== 'draft') {
            throw new BusinessException("Can only delete draft sales orders");
        }

        return $this->transaction(function () use ($id) {
            $items = $this->detailRepository->where('so_id', $id);
            foreach ($items as $item) {
                $this->detailRepository->delete($item->id);
            }
            $this->repository->delete($id);
            $this->log('so_deleted', ['so_id' => $id]);
            $this->dispatch(new SalesOrderCancelled($id));
            return true;
        });
    }

    public function restore($id)
    {
        throw new BusinessException("Restore not yet implemented");
    }

    public function getWithDetails($id)
    {
        $so = $this->getById($id);
        $so->details = $this->detailRepository->where('so_id', $id);
        return $so;
    }
}
