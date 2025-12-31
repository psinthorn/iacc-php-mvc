<?php

namespace Tests\Integration\Workflow;

use Tests\TestCase;

/**
 * Invoice Workflow Integration Tests
 */
class InvoiceWorkflowTest extends TestCase
{
    /**
     * Test complete invoice workflow
     */
    public function testCompleteInvoiceWorkflow()
    {
        // 1. Create company (vendor)
        $companyId = $this->db->insert('companies', [
            'name' => 'Vendor Company',
            'email' => 'vendor@example.com',
        ]);

        // 2. Create purchase order
        $poId = $this->db->insert('purchase_orders', [
            'company_id' => $companyId,
            'status' => 'received',
            'total_amount' => 5000.00,
        ]);

        // 3. Create invoice from PO
        $invoiceId = $this->db->insert('invoices', [
            'po_id' => $poId,
            'invoice_number' => 'INV-001',
            'total_amount' => 5000.00,
            'status' => 'draft',
            'due_date' => date('Y-m-d', strtotime('+30 days')),
        ]);

        $this->assertIsInt($invoiceId);

        // 4. Send invoice
        $this->db->update('invoices', [
            'status' => 'sent',
            'sent_date' => date('Y-m-d H:i:s'),
        ], ['id' => $invoiceId]);

        // 5. Record partial payment
        $this->db->insert('payments', [
            'invoice_id' => $invoiceId,
            'amount' => 2500.00,
            'status' => 'completed',
            'payment_date' => date('Y-m-d H:i:s'),
        ]);

        $this->db->update('invoices', [
            'paid_amount' => 2500.00,
        ], ['id' => $invoiceId]);

        // 6. Record remaining payment
        $this->db->insert('payments', [
            'invoice_id' => $invoiceId,
            'amount' => 2500.00,
            'status' => 'completed',
            'payment_date' => date('Y-m-d H:i:s'),
        ]);

        // 7. Mark as fully paid
        $this->db->update('invoices', [
            'status' => 'paid',
            'paid_amount' => 5000.00,
        ], ['id' => $invoiceId]);

        // Verify final state
        $invoice = $this->db->select('invoices', ['id' => $invoiceId]);

        $this->assertEquals('paid', $invoice['status']);
        $this->assertEquals(5000.00, $invoice['paid_amount']);
    }

    /**
     * Test invoice payment tracking
     */
    public function testInvoicePaymentTracking()
    {
        // Create invoice
        $invoiceId = $this->db->insert('invoices', [
            'invoice_number' => 'INV-002',
            'total_amount' => 1000.00,
            'status' => 'sent',
            'paid_amount' => 0,
        ]);

        // Add payments
        $payment1 = $this->db->insert('payments', [
            'invoice_id' => $invoiceId,
            'amount' => 300.00,
            'status' => 'completed',
        ]);

        $payment2 = $this->db->insert('payments', [
            'invoice_id' => $invoiceId,
            'amount' => 700.00,
            'status' => 'completed',
        ]);

        // Update invoice paid amount
        $this->db->update('invoices', [
            'paid_amount' => 1000.00,
            'status' => 'paid',
        ], ['id' => $invoiceId]);

        // Verify
        $invoice = $this->db->select('invoices', ['id' => $invoiceId]);
        $this->assertEquals(1000.00, $invoice['paid_amount']);
        $this->assertEquals('paid', $invoice['status']);
    }
}
