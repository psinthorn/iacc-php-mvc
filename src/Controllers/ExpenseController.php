<?php

namespace App\Controllers;

use App\Services\ExpenseService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;

/**
 * ExpenseController - Expense management endpoints
 */
class ExpenseController extends Controller implements ControllerInterface
{
    protected $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }

    /**
     * GET /api/expenses
     */
    public function index()
    {
        try {
            $page = $this->get('page', 1);
            $perPage = $this->get('per_page', 15);
            $status = $this->get('status');

            $filters = [];
            if ($status) {
                $filters['status'] = $status;
            }

            $result = $this->expenseService->getAll($filters, $page, $perPage);

            return $this->jsonPaginated(
                $result['data'],
                $result['page'],
                $result['per_page'],
                $result['total']
            );
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /api/expenses/:id
     */
    public function show($id)
    {
        try {
            $expense = $this->expenseService->getById($id);
            return $this->json(['data' => $expense]);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/expenses
     */
    public function store()
    {
        try {
            $data = $this->all();

            $expense = $this->expenseService->create($data);

            return $this->json(['data' => $expense], 201);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /api/expenses/:id
     */
    public function update($id)
    {
        try {
            $data = $this->all();

            $expense = $this->expenseService->update($id, $data);

            return $this->json(['data' => $expense]);
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
     * DELETE /api/expenses/:id
     */
    public function destroy($id)
    {
        try {
            $this->expenseService->delete($id);

            return $this->json(['message' => 'Expense deleted']);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (BusinessException $e) {
            return $this->jsonError($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/expenses/:id/approve
     */
    public function approve($id)
    {
        try {
            $expense = $this->expenseService->approve($id);

            return $this->json([
                'message' => 'Expense approved',
                'data' => $expense,
            ]);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (BusinessException $e) {
            return $this->jsonError($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
