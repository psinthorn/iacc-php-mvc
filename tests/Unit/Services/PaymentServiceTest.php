<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\PaymentService;
use App\Repositories\PaymentRepository;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\PaymentException;

/**
 * Payment Service Tests
 */
class PaymentServiceTest extends TestCase
{
    protected $paymentService;
    protected $paymentRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->paymentRepository = $this->createMock(PaymentRepository::class);

        $this->paymentService = new PaymentService(
            $this->paymentRepository,
            $this->db,
            new \App\Foundation\Logger()
        );
    }

    /**
     * Test list payments with pagination
     */
    public function testListPaymentsReturnsPaginated()
    {
        $payments = [
            (object)['id' => 1, 'invoice_id' => 1, 'amount' => 1000.00, 'status' => 'completed'],
            (object)['id' => 2, 'invoice_id' => 2, 'amount' => 500.00, 'status' => 'completed'],
        ];

        $this->paymentRepository->method('paginate')->willReturn([
            'data' => $payments,
            'total' => 2,
        ]);

        $result = $this->paymentService->listPayments(1, 10);

        $this->assertCount(2, $result['data']);
    }

    /**
     * Test create payment with valid data
     */
    public function testCreatePaymentWithValidData()
    {
        $data = [
            'invoice_id' => 1,
            'amount' => 1000.00,
            'payment_method' => 'bank_transfer',
            'reference' => 'TRF-001',
        ];

        $payment = (object)array_merge($data, ['id' => 1, 'status' => 'pending']);
        $this->paymentRepository->method('create')->willReturn($payment);

        $result = $this->paymentService->createPayment($data);

        $this->assertEquals(1000.00, $result->amount);
        $this->assertEquals('pending', $result->status);
    }

    /**
     * Test cannot process payment without valid invoice
     */
    public function testCannotProcessPaymentWithoutValidInvoice()
    {
        $data = [
            'invoice_id' => 999,
            'amount' => 1000.00,
            'payment_method' => 'bank_transfer',
        ];

        $this->paymentRepository->method('findInvoice')->willReturn(null);

        $this->expectException(NotFoundException::class);
        $this->paymentService->createPayment($data);
    }

    /**
     * Test payment amount cannot exceed invoice total
     */
    public function testPaymentAmountCannotExceedInvoiceTotal()
    {
        $data = [
            'invoice_id' => 1,
            'amount' => 5000.00, // invoice total is 1000
            'payment_method' => 'bank_transfer',
        ];

        $invoice = (object)['id' => 1, 'total_amount' => 1000.00, 'status' => 'pending'];
        $this->paymentRepository->method('findInvoice')->willReturn($invoice);

        $this->expectException(PaymentException::class);
        $this->paymentService->createPayment($data);
    }

    /**
     * Test confirm payment changes status
     */
    public function testConfirmPaymentChangesStatus()
    {
        $payment = (object)['id' => 1, 'status' => 'pending', 'amount' => 1000.00];
        $this->paymentRepository->method('find')->willReturn($payment);
        $this->paymentRepository->method('update')->willReturn((object)array_merge(
            (array)$payment,
            ['status' => 'completed']
        ));

        $result = $this->paymentService->confirmPayment(1);

        $this->assertEquals('completed', $result->status);
    }

    /**
     * Test refund payment
     */
    public function testRefundPayment()
    {
        $payment = (object)['id' => 1, 'status' => 'completed', 'amount' => 1000.00];
        $this->paymentRepository->method('find')->willReturn($payment);
        $this->paymentRepository->method('update')->willReturn((object)array_merge(
            (array)$payment,
            ['status' => 'refunded']
        ));

        $result = $this->paymentService->refundPayment(1);

        $this->assertEquals('refunded', $result->status);
    }

    /**
     * Test cannot refund pending payment
     */
    public function testCannotRefundPendingPayment()
    {
        $payment = (object)['id' => 1, 'status' => 'pending'];
        $this->paymentRepository->method('find')->willReturn($payment);

        $this->expectException(PaymentException::class);
        $this->paymentService->refundPayment(1);
    }

    /**
     * Test get payment by reference
     */
    public function testGetPaymentByReference()
    {
        $payment = (object)['id' => 1, 'reference' => 'TRF-001', 'amount' => 1000.00];
        $this->paymentRepository->method('findByReference')->willReturn($payment);

        $result = $this->paymentService->getPaymentByReference('TRF-001');

        $this->assertEquals('TRF-001', $result->reference);
    }

    /**
     * Test filter payments by status
     */
    public function testFilterPaymentsByStatus()
    {
        $payments = [
            (object)['id' => 1, 'status' => 'completed'],
        ];

        $this->paymentRepository->method('filterByStatus')->willReturn($payments);

        $result = $this->paymentService->filterByStatus('completed');

        $this->assertCount(1, $result);
        $this->assertEquals('completed', $result[0]->status);
    }

    /**
     * Test generate payment report
     */
    public function testGeneratePaymentReport()
    {
        $report = [
            'total_payments' => 10000.00,
            'payment_count' => 5,
            'average_payment' => 2000.00,
            'by_status' => ['completed' => 3, 'pending' => 1, 'failed' => 1],
        ];

        $this->paymentRepository->method('generateReport')->willReturn($report);

        $result = $this->paymentService->generateReport();

        $this->assertArrayHasKeys(['total_payments', 'payment_count', 'average_payment', 'by_status'], $result);
    }
}
