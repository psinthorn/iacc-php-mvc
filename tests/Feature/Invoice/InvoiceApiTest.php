<?php

namespace Tests\Feature\Invoice;

use Tests\Feature\FeatureTestCase;

/**
 * Invoice API Tests
 */
class InvoiceApiTest extends FeatureTestCase
{
    /**
     * Test list invoices returns 200
     */
    public function testListInvoicesReturns200()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['invoice:view']]);

        $response = $this->actingAs(['id' => 1], $token)->get('/api/v1/invoices');

        $this->assertOk();
    }

    /**
     * Test show invoice returns 200
     */
    public function testShowInvoiceReturns200()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['invoice:view']]);

        $response = $this->actingAs(['id' => 1], $token)->get('/api/v1/invoices/1');

        $this->assertOk();
    }

    /**
     * Test create invoice from PO returns 201
     */
    public function testCreateInvoiceFromPoReturns201()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['invoice:create']]);

        $data = ['purchase_order_id' => 1];

        $response = $this->actingAs(['id' => 1], $token)->post('/api/v1/invoices', $data);

        $this->assertCreated();
    }

    /**
     * Test send invoice
     */
    public function testSendInvoice()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['invoice:send']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->post('/api/v1/invoices/1/send');

        // Expected 200
    }

    /**
     * Test record invoice payment
     */
    public function testRecordInvoicePayment()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['invoice:pay']]);

        $data = ['amount' => 500.00];

        $response = $this->actingAs(['id' => 1], $token)
            ->post('/api/v1/invoices/1/payment', $data);

        // Expected 200
    }

    /**
     * Test cannot pay more than invoice amount
     */
    public function testCannotPayMoreThanInvoiceAmount()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['invoice:pay']]);

        $data = ['amount' => 5000.00]; // Invoice total is 1000

        $response = $this->actingAs(['id' => 1], $token)
            ->post('/api/v1/invoices/1/payment', $data);

        // Expected 422
    }

    /**
     * Test mark invoice as paid
     */
    public function testMarkInvoiceAsPaid()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['invoice:edit']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->post('/api/v1/invoices/1/mark-paid');

        // Expected 200
    }

    /**
     * Test cancel invoice
     */
    public function testCancelInvoice()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['invoice:delete']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->delete('/api/v1/invoices/1');

        // Expected 200
    }

    /**
     * Test cannot cancel paid invoice
     */
    public function testCannotCancelPaidInvoice()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['invoice:delete']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->delete('/api/v1/invoices/1'); // This invoice is already paid

        // Expected 422
    }

    /**
     * Test filter invoices by status
     */
    public function testFilterInvoicesByStatus()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['invoice:view']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->get('/api/v1/invoices?status=pending');

        $this->assertOk();
    }

    /**
     * Test filter invoices by date range
     */
    public function testFilterInvoicesByDateRange()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['invoice:view']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->get('/api/v1/invoices?from_date=2024-01-01&to_date=2024-12-31');

        $this->assertOk();
    }
}
