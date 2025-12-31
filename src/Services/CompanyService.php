<?php

namespace App\Services;

use App\Repositories\CompanyRepository;
use App\Repositories\ContactRepository;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;
use App\Events\CompanyCreated;
use App\Events\CompanyUpdated;
use App\Events\CompanyDeleted;

/**
 * CompanyService - Company/Vendor management
 * 
 * Handles creation, updating, and deletion of companies.
 * Ensures business rules and validates data.
 */
class CompanyService extends Service implements ServiceInterface
{
    protected $repository;
    protected $contactRepository;

    public function __construct(
        CompanyRepository $repository,
        ContactRepository $contactRepository,
        \App\Foundation\Database $database,
        \App\Foundation\Logger $logger,
        \App\Validation\Validator $validator,
        \App\Events\EventBus $eventBus = null
    ) {
        parent::__construct($database, $logger, $validator, $eventBus);
        $this->repository = $repository;
        $this->contactRepository = $contactRepository;
    }

    /**
     * Get all companies with pagination
     */
    public function getAll($filters = [], $page = 1, $perPage = 15)
    {
        $query = $this->repository->all();

        // Apply filters
        if (!empty($filters['status'])) {
            $query = array_filter($query, fn($c) => $c->status == $filters['status']);
        }
        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $query = array_filter($query, fn($c) =>
                stripos($c->name, $search) !== false ||
                stripos($c->code, $search) !== false
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

    /**
     * Get company by ID
     */
    public function getById($id)
    {
        $company = $this->repository->find($id);
        if (!$company) {
            throw new NotFoundException("Company not found");
        }
        return $company;
    }

    /**
     * Create new company
     */
    public function create(array $data)
    {
        // Validate input
        $errors = $this->validate($data, [
            'name' => 'required|string|min:3|max:255',
            'code' => 'required|string|unique:company,code',
            'email' => 'required|email',
            'phone' => 'required|string|min:10',
            'tax_id' => 'required|string|regex:^[0-9]{13}$',
            'address' => 'required|string|min:5',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        // Create in transaction
        return $this->transaction(function () use ($data) {
            $company = $this->repository->create([
                'name' => $data['name'],
                'code' => $data['code'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'tax_id' => $data['tax_id'],
                'address' => $data['address'],
                'status' => 1,
            ]);

            $this->log('company_created', [
                'company_id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
            ]);

            $this->dispatch(new CompanyCreated($company));

            return $company;
        });
    }

    /**
     * Update company
     */
    public function update($id, array $data)
    {
        $company = $this->getById($id);

        // Validate input
        $errors = $this->validate($data, [
            'name' => 'string|min:3|max:255',
            'code' => 'string|unique:company,code,' . $id,
            'email' => 'email',
            'phone' => 'string|min:10',
            'tax_id' => 'regex:^[0-9]{13}$',
            'address' => 'string|min:5',
            'status' => 'in:0,1',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $this->transaction(function () use ($id, $data, $company) {
            $company = $this->repository->update($id, array_filter($data));

            $this->log('company_updated', [
                'company_id' => $id,
                'changes' => array_keys($data),
            ]);

            $this->dispatch(new CompanyUpdated($company));

            return $company;
        });
    }

    /**
     * Delete company
     */
    public function delete($id)
    {
        $company = $this->getById($id);

        // Check for dependencies
        $contacts = $this->contactRepository->where('company_id', $id);
        if (!empty($contacts)) {
            throw new BusinessException(
                "Cannot delete company with " . count($contacts) . " contacts"
            );
        }

        return $this->transaction(function () use ($id) {
            $this->repository->delete($id);

            $this->log('company_deleted', ['company_id' => $id]);
            $this->dispatch(new CompanyDeleted($id));

            return true;
        });
    }

    /**
     * Restore deleted company (soft delete support)
     */
    public function restore($id)
    {
        throw new BusinessException("Restore not yet implemented");
    }

    /**
     * Find company by code
     */
    public function findByCode($code)
    {
        return $this->repository->findByCode($code);
    }

    /**
     * Get active companies
     */
    public function getActiveCompanies()
    {
        $companies = $this->repository->all();
        return array_filter($companies, fn($c) => $c->status == 1);
    }

    /**
     * Get company with contacts
     */
    public function getCompanyDetails($id)
    {
        $company = $this->getById($id);
        $company->contacts = $this->contactRepository->where('company_id', $id);
        return $company;
    }
}
