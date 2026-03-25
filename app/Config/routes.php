<?php
/**
 * Application Route Configuration
 * 
 * Maps URL page parameters to file paths or controller actions.
 * 
 * Two formats supported:
 *   1. Legacy (string): 'page_name' => 'filename.php'          — includes file directly
 *   2. MVC (array):     'page_name' => ['Controller', 'method'] — dispatches to controller
 * 
 * Migration: As pages are migrated to MVC, change their entry from string to array format.
 */

return [
    // ========== MVC Routes (migrated) ==========
    
    // Category
    'category'        => ['CategoryController', 'index'],
    'category_form'   => ['CategoryController', 'form'],
    'category_store'  => ['CategoryController', 'store'],
    'category_delete' => ['CategoryController', 'delete'],
    
    // Brand
    'brand'           => ['BrandController', 'index'],
    'brand_form'      => ['BrandController', 'form'],
    'brand_store'     => ['BrandController', 'store'],
    'brand_delete'    => ['BrandController', 'delete'],
    
    // Type
    'type'            => ['TypeController', 'index'],
    'type_store'      => ['TypeController', 'store'],
    'type_delete'     => ['TypeController', 'delete'],
    
    // Model
    'mo_list'         => ['ModelController', 'index'],
    'mo_list_store'   => ['ModelController', 'store'],
    'mo_list_delete'  => ['ModelController', 'delete'],
    'mo_list_brands'  => ['ModelController', 'getBrands'],
    
    // Payment Method
    'payment_method_list'   => ['PaymentMethodController', 'index'],
    'payment_method'        => ['PaymentMethodController', 'form'],
    'payment_method_store'  => ['PaymentMethodController', 'store'],
    'payment_method_delete' => ['PaymentMethodController', 'delete'],
    'payment_method_toggle' => ['PaymentMethodController', 'toggle'],

    // Company
    'company'          => ['CompanyController', 'index'],
    'company_form'     => ['CompanyController', 'form'],
    'company_store'    => ['CompanyController', 'store'],
    'company_delete'   => ['CompanyController', 'delete'],
    'company_credits'  => ['CompanyController', 'credits'],

    // ========== Invoice / QA (Phase 3A) ==========
    'compl_list'    => ['InvoiceController', 'index'],
    'compl_view'    => ['InvoiceController', 'view'],
    'compl_list2'   => ['InvoiceController', 'taxList'],
    'qa_list'       => ['InvoiceController', 'quotations'],
    'invoice_store' => ['InvoiceController', 'store'],

    // Purchase Requisition (Phase 3B)
    'pr_list'       => ['PurchaseRequestController', 'index'],
    'pr_create'     => ['PurchaseRequestController', 'create'],
    'pr_make'       => ['PurchaseRequestController', 'create'],
    'pr_view'       => ['PurchaseRequestController', 'view'],
    'pr_store'      => ['PurchaseRequestController', 'store'],

    // Payment (Phase 3C)
    'payment'       => ['PaymentController', 'index'],
    'payment_store' => ['PaymentController', 'store'],

    // Purchase Order (Phase 3D)
    'po_list'       => ['PurchaseOrderController', 'index'],
    'po_make'       => ['PurchaseOrderController', 'make'],
    'po_edit'       => ['PurchaseOrderController', 'edit'],
    'po_view'       => ['PurchaseOrderController', 'view'],
    'po_deliv'      => ['PurchaseOrderController', 'delivery'],
    'po_store'      => ['PurchaseOrderController', 'store'],

    // Voucher (Phase 3E)
    'voucher_list'  => ['VoucherController', 'index'],
    'voc_make'      => ['VoucherController', 'make'],
    'voc_view'      => ['VoucherController', 'view'],
    'voucher_store' => ['VoucherController', 'store'],
    'vou_print'     => 'vou-print.php',

    // Delivery (Phase 3E)
    'deliv_list'    => ['DeliveryController', 'index'],
    'deliv_make'    => ['DeliveryController', 'make'],
    'deliv_edit'    => ['DeliveryController', 'edit'],
    'deliv_view'    => ['DeliveryController', 'view'],
    'deliv_store'   => ['DeliveryController', 'store'],

    // Receipt (Phase 3E)
    'receipt_list'  => ['ReceiptController', 'index'],
    'rep_make'      => ['ReceiptController', 'make'],
    'rep_view'      => ['ReceiptController', 'view'],
    'receipt_store' => ['ReceiptController', 'store'],
    'rep_print'     => 'rep-print.php',

    // Billing (Phase 3E)
    'billing'       => ['BillingController', 'index'],
    'billing_make'  => ['BillingController', 'make'],
    'billing_store' => ['BillingController', 'store'],

    // ========== Legacy Routes (file-based) ==========
    
    // Dashboard
    'dashboard'     => 'dashboard.php',
    'user'          => 'user-list.php',
    
    // Reports & Invoice Payments (still legacy)
    'invoice_payments'  => 'invoice-payments.php',
    'report'            => 'report.php',
    
    // Admin Tools
    'audit_log'             => 'audit-log.php',
    'monitoring'            => 'admin-monitoring.php',
    'containers'            => 'admin-containers.php',
    // Payment Gateway
    'payment_gateway_config' => 'payment-gateway-config.php',
    'payment_gateway_test'   => 'payment-gateway-test.php',
    'payment_webhook'        => 'payment-webhook.php',
    
    // Developer Tools (Admin Only)
    'test_crud'              => 'tests/test-crud.php',
    'test_crud_ai'           => 'tests/test-crud-ai.php',
    'test_rbac'              => 'tests/test-rbac.php',
    'ai_settings'            => 'ai-settings.php',
    'ai_chat_history'        => 'ai-chat-history.php',
    'ai_schema_browser'      => 'ai-schema-browser.php',
    'ai_action_log'          => 'ai-action-log.php',
    'ai_schema_refresh'      => 'ai-schema-refresh.php',
    'ai_documentation'       => 'ai-documentation.php',
    'debug_session'          => 'tests/debug-session.php',
    'debug_invoice'          => 'tests/debug-invoice.php',
    'debug_php'              => 'tests/debug-php.php',
    'docker_test'            => 'tests/docker-test.php',
    'test_containers'        => 'tests/test-containers.php',
    'api_lang_debug'         => 'api-lang-debug.php',
    'dev_roadmap'            => 'dev-roadmap.php',
    
    // AI Chat API
    'ai_chat'                => 'ai/chat-handler.php',
    
    // Invoice Payment
    'inv_checkout'           => 'inv-checkout.php',
    'inv_payment_success'    => 'inv-payment-success.php',
    'inv_payment_cancel'     => 'inv-payment-cancel.php',
    
    // User Account
    'profile'                => 'profile.php',
    'settings'               => 'settings.php',
    'help'                   => 'help.php',
];
