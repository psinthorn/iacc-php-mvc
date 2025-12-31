<?php

namespace App\Services;

use App\Repositories\PaymentRepository;
use App\Repositories\InvoiceRepository;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;

/**
 * PaymentService - Payment recording and tracking
 */
class PaymentService extends Service
{
    protected $repository;
    protected $invoiceRepository;

    public function __construct(
        PaymentRepository $repository,
        InvoiceRepository $invoiceRepository,
        \App\Foundation\Database $database,
        \App\Foundation\Logger $logger,
        \App\Validation\Validator $validator,
        \App\Events\EventBus $eventBus = null
    ) {
        parent::__construct($database, $logger, $validator, $eventBus);
        $this->repository = $repository;
        $this->invoiceRepository = $invoiceRepository;
    }

    public function createPayment(array $data)
    {
        $errors = $this->validate($data, [
            'invoice_id' => 'required|exists:invoice,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date:Y-m-d',
            'payment_method' => 'required|string',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $this->transaction(function () use ($data) {
            $payment = $this->repository->create([
                'invoice_id' => $data['invoice_id'],
                'amount' => $data['amount'],
                'payment_date' => $data['payment_date'],
                'payment_method' => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? '',
            ]);

            $this->log('payment_created', [
                'payment_id' => $payment->id,
                'invoice_id' => $data['invoice_id'],
                'amount' => $data['amount'],
            ]);

            return $payment;
        });
    }

    public function getTotalPaid($invoiceId)
    {
        $payments = $this->repository->where('invoice_id', $invoiceId);
        return array_sum(array_map(fn($p) => $p->amount, $payments));
    }

    public function getPaymentsByInvoice($invoiceId)
    {
        return $this->repository->where('invoice_id', $invoiceId);
    }
}
