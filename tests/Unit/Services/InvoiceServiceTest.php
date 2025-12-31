<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\InvoiceService;
use App\Repositories\InvoiceRepository;
use App\Repositories\PurchaseOrderRepository;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;

/**
 * Invoice Service Tests
 */
class InvoiceServiceTest extends TestCase
{
    protected $invoiceService;
    protected $invoiceRepository;
    protected $purchaseOrderRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->invoiceRepository = $this->createMock(InvoiceRepository::class);
        $this->purchaseOrderRepository = $this->createMock(PurchaseOrderRepository::class);

        $this->invoiceService = new InvoiceService(
            $this->invoiceRepository,
            $this->purchaseOrderRepository,
            $this->db,
            new \App\Foundation\Logger()
        );
    }

    /**
     * Test create invoice from purchase order
     */
    public function testCreateInvoiceFromPurchaseOrder()
    {
        $po = (object)[
            'id' => 1,
            'company_id' => 1,
            'total_amount' => 1000.00,
            'status' => 'received',
        ];

        $invoice = (object)[
            'id' => 1,
            'po_id' => 1,
            'total_amount' => 1000.00,
            'status' => 'draft',
        ];

        $this->purchaseOrderRepository->method('find')->willReturn($po);
        $this->invoiceRepository->method('create')->willReturn($invoice);

        $result = $this->invoiceService->createFromPurchaseOrder(1);

        $this->assertEquals(1000.00, $result->total_amount);
        $this->assertEquals('draft', $result->status);
    }

    /**
     * Test cannot create invoice from non-received PO
     */
    public function testCannotCreateInvoiceFromNonReceivedPo()
    {
        $po = (object)['id' => 1, 'status' => 'draft'];
        $this->purchaseOrderRepository->method('find')->willReturn($po);

        $this->expectException(ValidationException::class);
        $this->invoiceService->createFromPurchaseOrder(1);
    }

    /**
     * Test get invoice
     */
    public function testGetInvoiceReturnsInvoiceData()
    {
        $invoice = (object)[
            'id' => 1,
            'invoice_number' => 'INV-001',
            'total_amount' => 1000.00,
            'status' => 'draft',
        ];

        $this->invoiceRepository->method('find')->willReturn($invoice);

        $result = $this->invoiceService->getInvoice(1);

        $this->assertEquals('INV-001', $result->invoice_number);
    }

    /**
     * Test list invoices with pagination
     */
    public function testListInvoicesReturnsPaginated()
    {
        $invoices = [
            (object)['id' => 1, 'invoice_number' => 'INV-001', 'total_amount' => 1000.00],
            (object)['id' => 2, 'invoice_number' => 'INV-002', 'total_amount' => 2000.00],
        ];

        $this->invoiceRepository->method('paginate')->willReturn([
            'data' => $invoices,
            'total' => 2,
        ]);

        $result = $this->invoiceService->listInvoices(1, 10);

        $this->assertCount(2, $result['data']);
    }

    /**
     * Test send invoice
     */
    public function testSendInvoiceUpdatesStatus()
    {
        $invoice = (object)['id' => 1, 'status' => 'draft'];
        $this->invoiceRepository->method('find')->willReturn($invoice);
        $this->invoiceRepository->method('update')->willReturn((object)[
            'id' => 1,
            'status' => 'sent',
        ]);

        $result = $this->invoiceService->sendInvoice(1);

        $this->assertEquals('sent', $result->status);
    }

    /**
     * Test record invoice payment
     */
    public function testRecordInvoicePayment()
    {
        $invoice = (object)['id' => 1, 'total_amount' => 1000.00, 'paid_amount' => 0];
        $this->invoiceRepository->method('find')->willReturn($invoice);
        $this->invoiceRepository->method('recordPayment')->willReturn(true);

        $result = $this->invoiceService->recordPayment(1, 500.00);

        $this->assertTrue($result);
    }

    /**
     * Test payment cannot exceed invoice total
     */
    public function testPaymentCannotExceedTotal()
    {
        $invoice = (object)['id' => 1, 'total_amount' => 1000.00, 'paid_amount' => 500.00];
        $this->invoiceRepository->method('find')->willReturn($invoice);

        $this->expectException(ValidationException::class);
        $this->invoiceService->recordPayment(1, 600.00); // would exceed total
    }

    /**
     * Test mark invoice as paid
     */
    public function testMarkInvoiceAsPaid()
    {
        $invoice = (object)['id' => 1, 'status' => 'sent', 'total_amount' => 1000.00, 'paid_amount' => 1000.00];
        $this->invoiceRepository->method('find')->willReturn($invoice);
        $this->invoiceRepository->method('update')->willReturn((object)[
            'id' => 1,
            'status' => 'paid',
        ]);

        $result = $this->invoiceService->markAsPaid(1);

        $this->assertEquals('paid', $result->status);
    }

    /**
     * Test generate invoice number
     */
    public function testGenerateInvoiceNumber()
    {
        $this->invoiceRepository->method('getNextInvoiceNumber')->willReturn('INV-00001');

        $result = $this->invoiceService->generateInvoiceNumber();

        $this->assertStringContainsString('INV-', $result);
    }

    /**
     * Test cancel invoice
     */
    public function testCancelInvoice()
    {
        $invoice = (object)['id' => 1, 'status' => 'draft', 'paid_amount' => 0];
        $this->invoiceRepository->method('find')->willReturn($invoice);
        $this->invoiceRepository->method('update')->willReturn((object)[
            'id' => 1,
            'status' => 'cancelled',
        ]);

        $result = $this->invoiceService->cancelInvoice(1);

        $this->assertEquals('cancelled', $result->status);
    }

    /**
     * Test cannot cancel paid invoice
     */
    public function testCannotCancelPaidInvoice()
    {
        $invoice = (object)['id' => 1, 'status' => 'paid', 'paid_amount' => 1000.00];
        $this->invoiceRepository->method('find')->willReturn($invoice);

        $this->expectException(ValidationException::class);
        $this->invoiceService->cancelInvoice(1);
    }
}
