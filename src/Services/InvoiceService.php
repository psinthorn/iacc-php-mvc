<?php

namespace App\Services;

use App\Repositories\InvoiceRepository;
use App\Repositories\InvoiceDetailRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\SalesOrderRepository;
use App\Repositories\SalesOrderDetailRepository;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\BusinessException;
use App\Events\InvoiceCreated;
use App\Events\InvoicePaid;

/**
 * InvoiceService - Invoice management
 */
class InvoiceService extends Service implements ServiceInterface
{
    protected $repository;
    protected $detailRepository;
    protected $paymentRepository;
    protected $soRepository;
    protected $soDetailRepository;

    public function __construct(
        InvoiceRepository $repository,
        InvoiceDetailRepository $detailRepository,
        PaymentRepository $paymentRepository,
        SalesOrderRepository $soRepository,
        SalesOrderDetailRepository $soDetailRepository,
        \App\Foundation\Database $database,
        \App\Foundation\Logger $logger,
        \App\Validation\Validator $validator,
        \App\Events\EventBus $eventBus = null
    ) {
        parent::__construct($database, $logger, $validator, $eventBus);
        $this->repository = $repository;
        $this->detailRepository = $detailRepository;
        $this->paymentRepository = $paymentRepository;
        $this->soRepository = $soRepository;
        $this->soDetailRepository = $soDetailRepository;
    }

    public function getAll($filters = [], $page = 1, $perPage = 15)
    {
        $query = $this->repository->all();

        if (!empty($filters['payment_status'])) {
            $query = array_filter($query, fn($inv) => $inv->payment_status == $filters['payment_status']);
        }
        if (!empty($filters['customer_id'])) {
            $query = array_filter($query, fn($inv) => $inv->customer_id == $filters['customer_id']);
        }

        $total = count($query);
        $items = array_slice($query, ($page - 1) * $perPage, $perPage);

        return ['data' => array_values($items), 'page' => $page, 'per_page' => $perPage, 'total' => $total, 'last_page' => ceil($total / $perPage)];
    }

    public function getById($id)
    {
        $invoice = $this->repository->find($id);
        if (!$invoice) {
            throw new NotFoundException("Invoice not found");
        }
        return $invoice;
    }

    /**
     * Create invoice from sales order
     */
    public function createFromSalesOrder($soId)
    {
        $so = $this->soRepository->find($soId);
        if (!$so) {
            throw new NotFoundException("Sales order not found");
        }

        return $this->transaction(function () use ($so) {
            $invoice = $this->repository->create([
                'invoice_number' => $this->generateInvoiceNumber(),
                'invoice_date' => date('Y-m-d'),
                'so_id' => $so->id,
                'customer_id' => $so->customer_id,
                'total_amount' => $so->total_amount,
                'payment_status' => 'pending',
                'due_date' => date('Y-m-d', strtotime('+30 days')),
            ]);

            $soDetails = $this->soDetailRepository->where('so_id', $so->id);
            foreach ($soDetails as $detail) {
                $this->detailRepository->create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $detail->product_id,
                    'quantity' => $detail->quantity,
                    'unit_price' => $detail->unit_price,
                    'amount' => $detail->amount,
                ]);
            }

            $this->soRepository->update($so->id, ['status' => 'invoiced']);

            $this->log('invoice_created', ['invoice_id' => $invoice->id, 'so_id' => $so->id]);
            $this->dispatch(new InvoiceCreated($invoice));

            return $this->repository->find($invoice->id);
        });
    }

    public function create(array $data)
    {
        throw new BusinessException("Use createFromSalesOrder instead");
    }

    public function update($id, array $data)
    {
        $invoice = $this->getById($id);

        if ($invoice->payment_status !== 'pending') {
            throw new BusinessException("Cannot modify paid invoices");
        }

        return $this->transaction(function () use ($id, $data) {
            $invoice = $this->repository->update($id, array_filter($data));
            $this->log('invoice_updated', ['invoice_id' => $id]);
            return $invoice;
        });
    }

    /**
     * Record payment for invoice
     */
    public function recordPayment($invoiceId, array $paymentData)
    {
        $invoice = $this->getById($invoiceId);

        $errors = $this->validate($paymentData, [
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date:Y-m-d',
            'payment_method' => 'required|string',
        ]);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return $this->transaction(function () use ($invoice, $paymentData) {
            $this->paymentRepository->create([
                'invoice_id' => $invoice->id,
                'amount' => $paymentData['amount'],
                'payment_date' => $paymentData['payment_date'],
                'payment_method' => $paymentData['payment_method'],
                'reference_number' => $paymentData['reference_number'] ?? '',
            ]);

            $totalPaid = 0;
            $payments = $this->paymentRepository->where('invoice_id', $invoice->id);
            foreach ($payments as $p) {
                $totalPaid += $p->amount;
            }

            if ($totalPaid >= $invoice->total_amount) {
                $this->repository->update($invoice->id, [
                    'payment_status' => 'paid',
                    'paid_date' => date('Y-m-d'),
                ]);
                $this->dispatch(new InvoicePaid($this->repository->find($invoice->id)));
            }

            $this->log('payment_recorded', ['invoice_id' => $invoice->id, 'amount' => $paymentData['amount']]);

            return $this->repository->find($invoice->id);
        });
    }

    public function delete($id)
    {
        $invoice = $this->getById($id);

        if ($invoice->payment_status !== 'pending') {
            throw new BusinessException("Cannot delete paid invoices");
        }

        return $this->transaction(function () use ($id) {
            $this->repository->delete($id);
            $this->log('invoice_deleted', ['invoice_id' => $id]);
            return true;
        });
    }

    public function restore($id)
    {
        throw new BusinessException("Restore not yet implemented");
    }

    private function generateInvoiceNumber()
    {
        $year = date('Y');
        $month = date('m');
        $count = $this->repository->count() + 1;
        return "INV-{$year}{$month}-" . str_pad($count, 5, '0', STR_PAD_LEFT);
    }
}
