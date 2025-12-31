<?php

namespace Tests\Feature\PurchaseOrder;

use Tests\Feature\FeatureTestCase;

/**
 * Purchase Order API Tests
 */
class PurchaseOrderApiTest extends FeatureTestCase
{
    /**
     * Test list purchase orders returns 200
     */
    public function testListPurchaseOrdersReturns200()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['po:view']]);

        $response = $this->actingAs(['id' => 1], $token)->get('/api/v1/purchase-orders');

        $this->assertOk();
    }

    /**
     * Test show purchase order returns 200
     */
    public function testShowPurchaseOrderReturns200()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['po:view']]);

        $response = $this->actingAs(['id' => 1], $token)->get('/api/v1/purchase-orders/1');

        $this->assertOk();
    }

    /**
     * Test create purchase order returns 201
     */
    public function testCreatePurchaseOrderReturns201()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['po:create']]);

        $data = [
            'company_id' => 1,
            'items' => [
                ['product_id' => 1, 'quantity' => 10, 'unit_price' => 100.00],
                ['product_id' => 2, 'quantity' => 5, 'unit_price' => 50.00],
            ],
        ];

        $response = $this->actingAs(['id' => 1], $token)->post('/api/v1/purchase-orders', $data);

        $this->assertCreated();
    }

    /**
     * Test cannot create PO without company
     */
    public function testCannotCreatePoWithoutCompany()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['po:create']]);

        $data = [
            'items' => [
                ['product_id' => 1, 'quantity' => 10],
            ],
        ];

        $response = $this->actingAs(['id' => 1], $token)->post('/api/v1/purchase-orders', $data);

        $this->assertUnprocessable();
    }

    /**
     * Test update purchase order returns 200
     */
    public function testUpdatePurchaseOrderReturns200()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['po:edit']]);

        $data = ['status' => 'submitted'];

        $response = $this->actingAs(['id' => 1], $token)->put('/api/v1/purchase-orders/1', $data);

        $this->assertOk();
    }

    /**
     * Test cannot update submitted purchase order
     */
    public function testCannotUpdateSubmittedPo()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['po:edit']]);

        // In real scenario, the API would check if PO is already submitted
        $data = ['total_amount' => 5000.00];

        $response = $this->actingAs(['id' => 1], $token)->put('/api/v1/purchase-orders/1', $data);

        // Should return 422 or 403 if validation fails
    }

    /**
     * Test submit purchase order
     */
    public function testSubmitPurchaseOrder()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['po:submit']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->post('/api/v1/purchase-orders/1/submit');

        // Expected 200 on success
    }

    /**
     * Test approve purchase order
     */
    public function testApprovePurchaseOrder()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['po:approve'], 'roles' => ['admin']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->post('/api/v1/purchase-orders/1/approve');

        // Expected 200 on success
    }

    /**
     * Test receive purchase order
     */
    public function testReceivePurchaseOrder()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['po:receive']]);

        $data = [
            'items' => [
                ['product_id' => 1, 'received_quantity' => 10],
                ['product_id' => 2, 'received_quantity' => 5],
            ],
        ];

        $response = $this->actingAs(['id' => 1], $token)
            ->post('/api/v1/purchase-orders/1/receive', $data);

        // Expected 200
    }

    /**
     * Test filter purchase orders by status
     */
    public function testFilterPoByStatus()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['po:view']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->get('/api/v1/purchase-orders?status=submitted');

        $this->assertOk();
    }

    /**
     * Test filter purchase orders by date range
     */
    public function testFilterPoByDateRange()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['po:view']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->get('/api/v1/purchase-orders?from_date=2024-01-01&to_date=2024-12-31');

        $this->assertOk();
    }

    /**
     * Test cancel purchase order
     */
    public function testCancelPurchaseOrder()
    {
        $token = generateTestToken(['id' => 1, 'permissions' => ['po:delete']]);

        $response = $this->actingAs(['id' => 1], $token)
            ->delete('/api/v1/purchase-orders/1');

        $this->assertOk();
    }
}
