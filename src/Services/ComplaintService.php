<?php

namespace App\Services;

use App\Repositories\ComplaintTicketRepository;
use App\Repositories\CustomerRepository;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;

/**
 * ComplaintService - Customer complaint management
 */
class ComplaintService extends Service implements ServiceInterface
{
    protected $repository;
    protected $customerRepository;

    public function __construct(
        ComplaintTicketRepository $repository,
        CustomerRepository $customerRepository,
        \App\Foundation\Database $database,
        \App\Foundation\Logger $logger,
        \App\Validation\Validator $validator,
        \App\Events\EventBus $eventBus = null
    ) {
        parent::__construct($database, $logger, $validator, $eventBus);
        $this->repository = $repository;
        $this->customerRepository = $customerRepository;
    }

    public function getAll($filters = [], $page = 1, $perPage = 15)
    {
        $query = $this->repository->all();

        if (!empty($filters['status'])) {
            $query = array_filter($query, fn($c) => $c->status == $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $query = array_filter($query, fn($c) => $c->priority == $filters['priority']);
        }

        $total = count($query);
        $items = array_slice($query, ($page - 1) * $perPage, $perPage);

        return ['data' => array_values($items), 'page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => ceil($total / $perPage)];
    }

    public function getById($id)
    {
        $complaint = $this->repository->find($id);
        if (!$complaint) {
            throw new NotFoundException("Complaint not found");
        }
        return $complaint;
    }

    public function create(array $data)
    {
        $errors = $this->validate($data, [
            'customer_id' => 'required|exists:customer,id',
            'so_id' => 'required|exists:sales_order,id',
            'description' => 'required|string|min:10',
            'priority' => 'required|in:low,medium,high',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $this->transaction(function () use ($data) {
            $complaint = $this->repository->create([
                'ticket_number' => 'TICKET-' . date('YmdHis'),
                'customer_id' => $data['customer_id'],
                'so_id' => $data['so_id'],
                'description' => $data['description'],
                'priority' => $data['priority'],
                'status' => 'open',
            ]);

            $this->log('complaint_created', [
                'complaint_id' => $complaint->id,
                'customer_id' => $data['customer_id'],
            ]);

            return $complaint;
        });
    }

    public function update($id, array $data)
    {
        $complaint = $this->getById($id);

        return $this->transaction(function () use ($id, $data) {
            $complaint = $this->repository->update($id, array_filter($data));
            $this->log('complaint_updated', ['complaint_id' => $id]);
            return $complaint;
        });
    }

    /**
     * Resolve complaint
     */
    public function resolve($id, $resolution)
    {
        $complaint = $this->getById($id);

        if ($complaint->status === 'resolved') {
            throw new BusinessException("Complaint already resolved");
        }

        return $this->transaction(function () use ($id, $resolution) {
            $this->repository->update($id, [
                'status' => 'resolved',
                'resolution' => $resolution,
                'resolved_date' => date('Y-m-d H:i:s'),
            ]);

            $this->log('complaint_resolved', ['complaint_id' => $id]);

            return $this->repository->find($id);
        });
    }

    public function delete($id)
    {
        $this->getById($id);

        return $this->transaction(function () use ($id) {
            $this->repository->delete($id);
            $this->log('complaint_deleted', ['complaint_id' => $id]);
            return true;
        });
    }

    public function restore($id)
    {
        throw new BusinessException("Restore not yet implemented");
    }

    /**
     * Get open complaints
     */
    public function getOpenComplaints()
    {
        return array_filter($this->repository->all(), fn($c) => $c->status === 'open');
    }

    /**
     * Get complaints by customer
     */
    public function getByCustomer($customerId)
    {
        return $this->repository->getByCustomer($customerId);
    }
}
