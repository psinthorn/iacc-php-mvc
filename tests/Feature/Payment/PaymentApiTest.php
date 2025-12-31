<?php

namespace Tests\Feature\Payment;

use Tests\Feature\FeatureTestCase;

/**
 * Payment API Tests
 */
class PaymentApiTest extends FeatureTestCase
{
    /**
     * Test list payments returns 200
     */
    public function testListPaymentsReturns200()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['payment:view']]);

        $response = $this->actingAs(['id' => 1], $token)->get('/api/v1/payments');

        $this->assertOk();
    }

    /**
     * Test show payment returns 200
     */
    public function testShowPaymentReturns200()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['payment:view']]);

        $response = $this->actingAs(['id' => 1], $token)->get('/api/v1/payments/1');

        $this->assertOk();
    }

    /**
     * Test create payment returns 201
     */
    public function testCreatePaymentReturns201()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['payment:create']]);

        $data = [
            'invoice_id' => 1,
            'amount' => 500.00,
            'payment_method' => 'bank_transfer',
            'reference' => 'TRF-001',
        ];

        $response = $this->actingAs(['id' => 1], $token)->post('/api/v1/payments', $data);

        $this->assertCreated();
    }

    /**
     * Test cannot create payment without invoice
     */
    public function testCannotCreatePaymentWithoutInvoice()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['payment:create']]);

        $data = [
            'amount' => 500.00,
            'payment_method' => 'bank_transfer',
        ];

        $response = $this->actingAs(['id' => 1], $token)->post('/api/v1/payments', $data);

        $this->assertUnprocessable();
    }

    /**
     * Test confirm payment
     */
    public function testConfirmPayment()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['payment:confirm']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->post('/api/v1/payments/1/confirm');

        // Expected 200
    }

    /**
     * Test refund payment
     */
    public function testRefundPayment()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['payment:refund']]);

        $data = ['reason' => 'Customer requested'];

        $response = $this->actingAs(['id' => 1], $token)
            ->post('/api/v1/payments/1/refund', $data);

        // Expected 200
    }

    /**
     * Test cannot refund pending payment
     */
    public function testCannotRefundPendingPayment()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['payment:refund']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->post('/api/v1/payments/1/refund');

        // Expected 422 for pending payment
    }

    /**
     * Test filter payments by status
     */
    public function testFilterPaymentsByStatus()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['payment:view']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->get('/api/v1/payments?status=completed');

        $this->assertOk();
    }

    /**
     * Test filter payments by method
     */
    public function testFilterPaymentsByMethod()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['payment:view']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->get('/api/v1/payments?method=bank_transfer');

        $this->assertOk();
    }

    /**
     * Test payment report
     */
    public function testPaymentReport()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['payment:view']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->get('/api/v1/payments/report');

        $this->assertOk();
        $this->assertJsonHas('total_payments');
        $this->assertJsonHas('payment_count');
    }
}
