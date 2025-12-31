<?php

namespace App\Controllers;

use App\Services\PaymentService;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * PaymentController - Payment processing endpoints
 */
class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * POST /api/payments
     */
    public function store()
    {
        try {
            $data = $this->all();

            $payment = $this->paymentService->createPayment($data);

            return $this->json(['data' => $payment], 201);
        } catch (ValidationException $e) {
            return $this->jsonError('Validation failed', 422, $e->getErrors());
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /api/invoices/:invoiceId/payments
     */
    public function getByInvoice($invoiceId)
    {
        try {
            $payments = $this->paymentService->getPaymentsByInvoice($invoiceId);
            $totalPaid = $this->paymentService->getTotalPaid($invoiceId);

            return $this->json([
                'invoice_id' => $invoiceId,
                'payments' => $payments,
                'total_paid' => $totalPaid,
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * GET /api/payments/invoice/:invoiceId/total
     */
    public function getTotalPaid($invoiceId)
    {
        try {
            $total = $this->paymentService->getTotalPaid($invoiceId);

            return $this->json([
                'invoice_id' => $invoiceId,
                'total_paid' => $total,
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }
}
