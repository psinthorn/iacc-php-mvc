<?php

namespace App\Controllers;

use App\Services\InvoiceService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;

/**
 * InvoiceController - Invoice generation and payment tracking
 */
class InvoiceController extends Controller implements ControllerInterface
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * GET /api/invoices
     */
    public function index()
    {
        try {
            $page = $this->get('page', 1);
            $perPage = $this->get('per_page', 15);
            $status = $this->get('status');
            $customerId = $this->get('customer_id');

            $filters = [];
            if ($status) {
                $filters['status'] = $status;
            }
            if ($customerId) {
                $filters['customer_id'] = $customerId;
            }

            $result = $this->invoiceService->getAll($filters, $page, $perPage);

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
     * GET /api/invoices/:id
     */
    public function show($id)
    {
        try {
            $invoice = $this->invoiceService->getById($id);
            return $this->json(['data' => $invoice]);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/invoices
     */
    public function store()
    {
        try {
            $data = $this->all();

            $invoice = $this->invoiceService->create($data);

            return $this->json(['data' => $invoice], 201);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * PUT /api/invoices/:id
     */
    public function update($id)
    {
        try {
            $data = $this->all();

            $invoice = $this->invoiceService->update($id, $data);

            return $this->json(['data' => $invoice]);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * DELETE /api/invoices/:id
     */
    public function destroy($id)
    {
        try {
            $this->invoiceService->delete($id);

            return $this->json(['message' => 'Invoice deleted']);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/invoices/:id/payments
     */
    public function recordPayment($id)
    {
        try {
            $data = $this->all();
            $data['invoice_id'] = $id;

            $payment = $this->invoiceService->recordPayment($id, $data);

            return $this->json([
                'message' => 'Payment recorded',
                'data' => $payment,
            ], 201);
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
     * GET /api/invoices/:id/payments
     */
    public function getPayments($id)
    {
        try {
            $invoice = $this->invoiceService->getById($id);
            $payments = $this->invoiceService->getPayments($id);

            return $this->json([
                'invoice_id' => $id,
                'payments' => $payments,
            ]);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * POST /api/invoices/:soId/from-sales-order
     */
    public function createFromSalesOrder($soId)
    {
        try {
            $invoice = $this->invoiceService->createFromSalesOrder($soId);

            return $this->json([
                'message' => 'Invoice created from sales order',
                'data' => $invoice,
            ], 201);
        } catch (NotFoundException $e) {
            return $this->jsonError($e->getMessage(), 404);
        } catch (BusinessException $e) {
            return $this->jsonError($e->getMessage(), 400);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
