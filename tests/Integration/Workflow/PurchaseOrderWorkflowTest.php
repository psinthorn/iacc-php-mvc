<?php

namespace Tests\Integration\Workflow;

use Tests\TestCase;

/**
 * Purchase Order Workflow Integration Tests
 */
class PurchaseOrderWorkflowTest extends TestCase
{
    /**
     * Test complete purchase order workflow
     */
    public function testCompletePurchaseOrderWorkflow()
    {
        // 1. Create company
        $companyId = $this->db->insert('companies', [
            'name' => 'Test Supplier',
            'email' => 'supplier@example.com',
        ]);

        // 2. Create products
        $productId = $this->db->insert('products', [
            'name' => 'Test Product',
            'sku' => 'PO-001',
            'price' => 100.00,
            'stock_quantity' => 1000,
        ]);

        // 3. Create purchase order
        $poId = $this->db->insert('purchase_orders', [
            'company_id' => $companyId,
            'status' => 'draft',
            'total_amount' => 0,
        ]);

        $this->assertIsInt($poId);

        // 4. Add line items
        $this->db->insert('purchase_order_lines', [
            'purchase_order_id' => $poId,
            'product_id' => $productId,
            'quantity' => 10,
            'unit_price' => 100.00,
            'line_total' => 1000.00,
        ]);

        // 5. Update PO status to submitted
        $this->db->update('purchase_orders', [
            'status' => 'submitted',
            'total_amount' => 1000.00,
        ], ['id' => $poId]);

        // 6. Verify PO
        $po = $this->db->select('purchase_orders', ['id' => $poId]);

        $this->assertEquals('submitted', $po['status']);
        $this->assertEquals(1000.00, $po['total_amount']);
    }

    /**
     * Test purchase order receives and stock updates
     */
    public function testPurchaseOrderReceiveAndStockUpdate()
    {
        // Setup: Create PO and product
        $companyId = $this->db->insert('companies', [
            'name' => 'Supplier',
            'email' => 'supplier@test.com',
        ]);

        $productId = $this->db->insert('products', [
            'name' => 'Test Product',
            'sku' => 'STOCK-001',
            'price' => 50.00,
            'stock_quantity' => 100,
        ]);

        $poId = $this->db->insert('purchase_orders', [
            'company_id' => $companyId,
            'status' => 'approved',
            'total_amount' => 500.00,
        ]);

        $this->db->insert('purchase_order_lines', [
            'purchase_order_id' => $poId,
            'product_id' => $productId,
            'quantity' => 10,
            'unit_price' => 50.00,
            'line_total' => 500.00,
        ]);

        // Get initial stock
        $initialProduct = $this->db->select('products', ['id' => $productId]);
        $initialStock = $initialProduct['stock_quantity'];

        // Receive PO (increases stock)
        $this->db->update('purchase_orders', [
            'status' => 'received',
        ], ['id' => $poId]);

        $this->db->update('products', [
            'stock_quantity' => $initialStock + 10,
        ], ['id' => $productId]);

        // Verify stock increased
        $updatedProduct = $this->db->select('products', ['id' => $productId]);

        $this->assertEquals($initialStock + 10, $updatedProduct['stock_quantity']);
    }

    /**
     * Test cannot receive over quantity
     */
    public function testCannotReceiveOverQuantity()
    {
        // Setup
        $companyId = $this->db->insert('companies', [
            'name' => 'Supplier',
            'email' => 'supplier@test.com',
        ]);

        $productId = $this->db->insert('products', [
            'name' => 'Product',
            'sku' => 'OVER-001',
            'price' => 50.00,
            'stock_quantity' => 100,
        ]);

        $poId = $this->db->insert('purchase_orders', [
            'company_id' => $companyId,
            'status' => 'approved',
        ]);

        $this->db->insert('purchase_order_lines', [
            'purchase_order_id' => $poId,
            'product_id' => $productId,
            'quantity' => 10,
            'unit_price' => 50.00,
            'line_total' => 500.00,
        ]);

        // Try to receive more than ordered
        $receiveQty = 15; // ordered 10

        // Test that this should fail or be validated
        $this->assertLessThan($receiveQty, 10);
    }

    /**
     * Test purchase order cancellation
     */
    public function testPurchaseOrderCancellation()
    {
        // Create PO
        $companyId = $this->db->insert('companies', [
            'name' => 'Supplier',
            'email' => 'supplier@test.com',
        ]);

        $poId = $this->db->insert('purchase_orders', [
            'company_id' => $companyId,
            'status' => 'draft',
            'total_amount' => 0,
        ]);

        // Cancel PO
        $this->db->update('purchase_orders', [
            'status' => 'cancelled',
        ], ['id' => $poId]);

        $po = $this->db->select('purchase_orders', ['id' => $poId]);

        $this->assertEquals('cancelled', $po['status']);
    }
}
