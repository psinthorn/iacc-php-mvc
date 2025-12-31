<?php

/**
 * API Routes - Phase 4 Step 5 Implementation
 * All application routes for REST API endpoints
 */

namespace App;

use App\Foundation\Router;
use App\Controllers\{
    CompanyController,
    ProductController,
    SupplierController,
    CustomerController,
    PurchaseOrderController,
    ReceivingController,
    SalesOrderController,
    InvoiceController,
    DeliveryController,
    PaymentController,
    ExpenseController,
    ReportController,
    ComplaintController,
};

/**
 * Initialize router
 */
$router = new Router();

/**
 * API v1 Routes
 * All routes prefixed with /api/v1
 */

// ============ COMPANY MANAGEMENT ============
$router->get('/api/v1/companies', [CompanyController::class, 'index']);
$router->get('/api/v1/companies/{id}', [CompanyController::class, 'show']);
$router->post('/api/v1/companies', [CompanyController::class, 'store']);
$router->put('/api/v1/companies/{id}', [CompanyController::class, 'update']);
$router->delete('/api/v1/companies/{id}', [CompanyController::class, 'destroy']);
$router->get('/api/v1/companies/{code}/by-code', [CompanyController::class, 'getByCode']);
$router->get('/api/v1/companies/active', [CompanyController::class, 'getActive']);

// ============ PRODUCT MANAGEMENT ============
$router->get('/api/v1/products', [ProductController::class, 'index']);
$router->get('/api/v1/products/{id}', [ProductController::class, 'show']);
$router->post('/api/v1/products', [ProductController::class, 'store']);
$router->put('/api/v1/products/{id}', [ProductController::class, 'update']);
$router->delete('/api/v1/products/{id}', [ProductController::class, 'destroy']);
$router->get('/api/v1/products/category/{categoryId}', [ProductController::class, 'getByCategory']);
$router->get('/api/v1/products/type/{typeId}', [ProductController::class, 'getByType']);

// ============ SUPPLIER MANAGEMENT ============
$router->get('/api/v1/suppliers', [SupplierController::class, 'index']);
$router->get('/api/v1/suppliers/{id}', [SupplierController::class, 'show']);
$router->post('/api/v1/suppliers', [SupplierController::class, 'store']);
$router->put('/api/v1/suppliers/{id}', [SupplierController::class, 'update']);
$router->delete('/api/v1/suppliers/{id}', [SupplierController::class, 'destroy']);

// ============ CUSTOMER MANAGEMENT ============
$router->get('/api/v1/customers', [CustomerController::class, 'index']);
$router->get('/api/v1/customers/{id}', [CustomerController::class, 'show']);
$router->post('/api/v1/customers', [CustomerController::class, 'store']);
$router->put('/api/v1/customers/{id}', [CustomerController::class, 'update']);
$router->delete('/api/v1/customers/{id}', [CustomerController::class, 'destroy']);
$router->post('/api/v1/customers/{id}/check-credit', [CustomerController::class, 'checkCreditLimit']);

// ============ PURCHASE ORDER WORKFLOW ============
$router->get('/api/v1/purchase-orders', [PurchaseOrderController::class, 'index']);
$router->get('/api/v1/purchase-orders/{id}', [PurchaseOrderController::class, 'show']);
$router->post('/api/v1/purchase-orders', [PurchaseOrderController::class, 'store']);
$router->put('/api/v1/purchase-orders/{id}', [PurchaseOrderController::class, 'update']);
$router->delete('/api/v1/purchase-orders/{id}', [PurchaseOrderController::class, 'destroy']);
$router->post('/api/v1/purchase-orders/{id}/submit', [PurchaseOrderController::class, 'submit']);
$router->post('/api/v1/purchase-orders/{id}/approve', [PurchaseOrderController::class, 'approve']);
$router->get('/api/v1/purchase-orders/pending', [PurchaseOrderController::class, 'getPending']);

// ============ RECEIVING MANAGEMENT ============
$router->post('/api/v1/receiving/{poId}/items', [ReceivingController::class, 'receiveItems']);
$router->get('/api/v1/receiving/{poId}/items', [ReceivingController::class, 'getReceipts']);

// ============ SALES ORDER WORKFLOW ============
$router->get('/api/v1/sales-orders', [SalesOrderController::class, 'index']);
$router->get('/api/v1/sales-orders/{id}', [SalesOrderController::class, 'show']);
$router->post('/api/v1/sales-orders', [SalesOrderController::class, 'store']);
$router->put('/api/v1/sales-orders/{id}', [SalesOrderController::class, 'update']);
$router->delete('/api/v1/sales-orders/{id}', [SalesOrderController::class, 'destroy']);
$router->post('/api/v1/sales-orders/{id}/confirm', [SalesOrderController::class, 'confirm']);

// ============ INVOICE MANAGEMENT ============
$router->get('/api/v1/invoices', [InvoiceController::class, 'index']);
$router->get('/api/v1/invoices/{id}', [InvoiceController::class, 'show']);
$router->post('/api/v1/invoices', [InvoiceController::class, 'store']);
$router->put('/api/v1/invoices/{id}', [InvoiceController::class, 'update']);
$router->delete('/api/v1/invoices/{id}', [InvoiceController::class, 'destroy']);
$router->post('/api/v1/invoices/{soId}/from-sales-order', [InvoiceController::class, 'createFromSalesOrder']);
$router->post('/api/v1/invoices/{id}/payments', [InvoiceController::class, 'recordPayment']);
$router->get('/api/v1/invoices/{id}/payments', [InvoiceController::class, 'getPayments']);

// ============ DELIVERY MANAGEMENT ============
$router->get('/api/v1/deliveries', [DeliveryController::class, 'index']);
$router->get('/api/v1/deliveries/{id}', [DeliveryController::class, 'show']);
$router->post('/api/v1/deliveries', [DeliveryController::class, 'store']);
$router->put('/api/v1/deliveries/{id}', [DeliveryController::class, 'update']);
$router->delete('/api/v1/deliveries/{id}', [DeliveryController::class, 'destroy']);
$router->post('/api/v1/deliveries/{id}/complete', [DeliveryController::class, 'complete']);

// ============ PAYMENT MANAGEMENT ============
$router->post('/api/v1/payments', [PaymentController::class, 'store']);
$router->get('/api/v1/invoices/{invoiceId}/payments', [PaymentController::class, 'getByInvoice']);
$router->get('/api/v1/payments/invoice/{invoiceId}/total', [PaymentController::class, 'getTotalPaid']);

// ============ EXPENSE MANAGEMENT ============
$router->get('/api/v1/expenses', [ExpenseController::class, 'index']);
$router->get('/api/v1/expenses/{id}', [ExpenseController::class, 'show']);
$router->post('/api/v1/expenses', [ExpenseController::class, 'store']);
$router->put('/api/v1/expenses/{id}', [ExpenseController::class, 'update']);
$router->delete('/api/v1/expenses/{id}', [ExpenseController::class, 'destroy']);
$router->post('/api/v1/expenses/{id}/approve', [ExpenseController::class, 'approve']);

// ============ REPORTS ============
$router->get('/api/v1/reports', [ReportController::class, 'index']);
$router->post('/api/v1/reports/{code}/execute', [ReportController::class, 'execute']);
$router->get('/api/v1/reports/sales-summary', [ReportController::class, 'getSalesSummary']);
$router->get('/api/v1/reports/inventory-status', [ReportController::class, 'getInventoryStatus']);
$router->get('/api/v1/reports/outstanding-invoices', [ReportController::class, 'getOutstandingInvoices']);

// ============ COMPLAINT MANAGEMENT ============
$router->get('/api/v1/complaints', [ComplaintController::class, 'index']);
$router->get('/api/v1/complaints/{id}', [ComplaintController::class, 'show']);
$router->post('/api/v1/complaints', [ComplaintController::class, 'store']);
$router->put('/api/v1/complaints/{id}', [ComplaintController::class, 'update']);
$router->delete('/api/v1/complaints/{id}', [ComplaintController::class, 'destroy']);
$router->post('/api/v1/complaints/{id}/resolve', [ComplaintController::class, 'resolve']);
$router->get('/api/v1/customers/{customerId}/complaints', [ComplaintController::class, 'getByCustomer']);
$router->get('/api/v1/complaints/open', [ComplaintController::class, 'getOpen']);

/**
 * Health check endpoint
 */
$router->get('/api/v1/health', function() {
    return json_encode(['status' => 'ok', 'timestamp' => date('Y-m-d H:i:s')]);
});

/**
 * 404 - Not Found
 */
$router->notFound(function() {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'message' => 'Endpoint not found',
        'code' => 404,
    ]);
});

return $router;
