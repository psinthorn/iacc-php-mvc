<?php

namespace App\Services;

use App\Repositories\CustomerRepository;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;
use App\Events\CustomerCreated;
use App\Events\CustomerUpdated;
use App\Events\CustomerDeleted;

/**
 * CustomerService - Customer management
 */
class CustomerService extends Service implements ServiceInterface
{
    protected $repository;

    public function __construct(
        CustomerRepository $repository,
        \App\Foundation\Database $database,
        \App\Foundation\Logger $logger,
        \App\Validation\Validator $validator,
        \App\Events\EventBus $eventBus = null
    ) {
        parent::__construct($database, $logger, $validator, $eventBus);
        $this->repository = $repository;
    }

    public function getAll($filters = [], $page = 1, $perPage = 15)
    {
        $query = $this->repository->all();

        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $query = array_filter($query, fn($c) =>
                stripos($c->name, $search) !== false || stripos($c->code, $search) !== false
            );
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
        $customer = $this->repository->find($id);
        if (!$customer) {
            throw new NotFoundException("Customer not found");
        }
        return $customer;
    }

    public function create(array $data)
    {
        $errors = $this->validate($data, [
            'name' => 'required|string|min:3',
            'code' => 'required|string|unique:customer,code',
            'email' => 'required|email',
            'phone' => 'required|string|min:10',
            'address' => 'required|string',
            'credit_limit' => 'required|numeric|min:0',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $this->transaction(function () use ($data) {
            $customer = $this->repository->create([
                'name' => $data['name'],
                'code' => $data['code'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'credit_limit' => $data['credit_limit'],
            ]);

            $this->log('customer_created', ['customer_id' => $customer->id, 'code' => $customer->code]);
            $this->dispatch(new CustomerCreated($customer));

            return $customer;
        });
    }

    public function update($id, array $data)
    {
        $customer = $this->getById($id);

        $errors = $this->validate($data, [
            'name' => 'string|min:3',
            'code' => 'string|unique:customer,code,' . $id,
            'email' => 'email',
            'phone' => 'string|min:10',
            'credit_limit' => 'numeric|min:0',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $this->transaction(function () use ($id, $data) {
            $customer = $this->repository->update($id, array_filter($data));

            $this->log('customer_updated', ['customer_id' => $id]);
            $this->dispatch(new CustomerUpdated($customer));

            return $customer;
        });
    }

    public function delete($id)
    {
        $this->getById($id);

        return $this->transaction(function () use ($id) {
            $this->repository->delete($id);
            $this->log('customer_deleted', ['customer_id' => $id]);
            $this->dispatch(new CustomerDeleted($id));
            return true;
        });
    }

    public function restore($id)
    {
        throw new BusinessException("Restore not yet implemented");
    }

    /**
     * Check customer credit limit
     */
    public function checkCreditLimit($customerId, $amount)
    {
        $customer = $this->getById($customerId);
        return ($customer->credit_limit ?? 0) >= $amount;
    }
}
