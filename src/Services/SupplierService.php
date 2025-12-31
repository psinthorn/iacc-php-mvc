<?php

namespace App\Services;

use App\Repositories\SupplierRepository;
use App\Repositories\CompanyRepository;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;
use App\Events\SupplierCreated;
use App\Events\SupplierUpdated;
use App\Events\SupplierDeleted;

/**
 * SupplierService - Supplier management
 */
class SupplierService extends Service implements ServiceInterface
{
    protected $repository;
    protected $companyRepository;

    public function __construct(
        SupplierRepository $repository,
        CompanyRepository $companyRepository,
        \App\Foundation\Database $database,
        \App\Foundation\Logger $logger,
        \App\Validation\Validator $validator,
        \App\Events\EventBus $eventBus = null
    ) {
        parent::__construct($database, $logger, $validator, $eventBus);
        $this->repository = $repository;
        $this->companyRepository = $companyRepository;
    }

    public function getAll($filters = [], $page = 1, $perPage = 15)
    {
        $query = $this->repository->all();

        if (!empty($filters['company_id'])) {
            $query = array_filter($query, fn($s) => $s->company_id == $filters['company_id']);
        }
        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $query = array_filter($query, fn($s) =>
                stripos($s->name, $search) !== false || stripos($s->code, $search) !== false
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
        $supplier = $this->repository->find($id);
        if (!$supplier) {
            throw new NotFoundException("Supplier not found");
        }
        return $supplier;
    }

    public function create(array $data)
    {
        $errors = $this->validate($data, [
            'company_id' => 'required|exists:company,id',
            'name' => 'required|string|min:3',
            'code' => 'required|string|unique:supplier,code',
            'email' => 'required|email',
            'phone' => 'required|string|min:10',
            'contact_person' => 'required|string',
            'address' => 'required|string',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $this->transaction(function () use ($data) {
            $supplier = $this->repository->create([
                'company_id' => $data['company_id'],
                'name' => $data['name'],
                'code' => $data['code'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'contact_person' => $data['contact_person'],
                'address' => $data['address'],
            ]);

            $this->log('supplier_created', ['supplier_id' => $supplier->id, 'code' => $supplier->code]);
            $this->dispatch(new SupplierCreated($supplier));

            return $supplier;
        });
    }

    public function update($id, array $data)
    {
        $supplier = $this->getById($id);

        $errors = $this->validate($data, [
            'name' => 'string|min:3',
            'code' => 'string|unique:supplier,code,' . $id,
            'email' => 'email',
            'phone' => 'string|min:10',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $this->transaction(function () use ($id, $data) {
            $supplier = $this->repository->update($id, array_filter($data));

            $this->log('supplier_updated', ['supplier_id' => $id]);
            $this->dispatch(new SupplierUpdated($supplier));

            return $supplier;
        });
    }

    public function delete($id)
    {
        $this->getById($id);

        return $this->transaction(function () use ($id) {
            $this->repository->delete($id);
            $this->log('supplier_deleted', ['supplier_id' => $id]);
            $this->dispatch(new SupplierDeleted($id));
            return true;
        });
    }

    public function restore($id)
    {
        throw new BusinessException("Restore not yet implemented");
    }
}
