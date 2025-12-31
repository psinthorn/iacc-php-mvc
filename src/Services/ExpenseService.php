<?php

namespace App\Services;

use App\Repositories\ExpenseRepository;
use App\Repositories\ExpenseDetailRepository;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;
use App\Events\ExpenseCreated;
use App\Events\ExpenseApproved;

/**
 * ExpenseService - Expense management
 */
class ExpenseService extends Service implements ServiceInterface
{
    protected $repository;
    protected $detailRepository;

    public function __construct(
        ExpenseRepository $repository,
        ExpenseDetailRepository $detailRepository,
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
            $query = array_filter($query, fn($e) => $e->status == $filters['status']);
        }

        $total = count($query);
        $items = array_slice($query, ($page - 1) * $perPage, $perPage);

        return ['data' => array_values($items), 'page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => ceil($total / $perPage)];
    }

    public function getById($id)
    {
        $expense = $this->repository->find($id);
        if (!$expense) {
            throw new NotFoundException("Expense not found");
        }
        return $expense;
    }

    public function create(array $data)
    {
        $errors = $this->validate($data, [
            'expense_number' => 'required|unique:expense,expense_number',
            'description' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.amount' => 'required|numeric|min:0.01',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $this->transaction(function () use ($data) {
            $total = 0;

            $expense = $this->repository->create([
                'expense_number' => $data['expense_number'],
                'expense_date' => date('Y-m-d'),
                'description' => $data['description'],
                'total_amount' => 0,
                'status' => 'draft',
            ]);

            foreach ($data['items'] as $item) {
                $total += $item['amount'];
                $this->detailRepository->create([
                    'expense_id' => $expense->id,
                    'description' => $item['description'],
                    'amount' => $item['amount'],
                ]);
            }

            $this->repository->update($expense->id, ['total_amount' => $total]);

            $this->log('expense_created', ['expense_id' => $expense->id, 'total' => $total]);
            $this->dispatch(new ExpenseCreated($this->repository->find($expense->id)));

            return $this->repository->find($expense->id);
        });
    }

    public function update($id, array $data)
    {
        $expense = $this->getById($id);

        if ($expense->status !== 'draft') {
            throw new BusinessException("Can only edit draft expenses");
        }

        return $this->transaction(function () use ($id, $data) {
            $expense = $this->repository->update($id, array_filter($data));
            $this->log('expense_updated', ['expense_id' => $id]);
            return $expense;
        });
    }

    public function approve($id)
    {
        $expense = $this->getById($id);

        if ($expense->status !== 'draft') {
            throw new BusinessException("Only draft expenses can be approved");
        }

        return $this->transaction(function () use ($id) {
            $this->repository->update($id, ['status' => 'approved']);
            $this->log('expense_approved', ['expense_id' => $id]);
            $this->dispatch(new ExpenseApproved($this->repository->find($id)));
            return $this->repository->find($id);
        });
    }

    public function delete($id)
    {
        $expense = $this->getById($id);

        if ($expense->status !== 'draft') {
            throw new BusinessException("Can only delete draft expenses");
        }

        return $this->transaction(function () use ($id) {
            $this->repository->delete($id);
            $this->log('expense_deleted', ['expense_id' => $id]);
            return true;
        });
    }

    public function restore($id)
    {
        throw new BusinessException("Restore not yet implemented");
    }
}
