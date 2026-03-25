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

    // ========== Legacy Routes (file-based) ==========
    
    // Dashboard
    'dashboard'     => 'dashboard.php',
    'user'          => 'user-list.php',
    
    // Purchase Requisition
    'pr_list'       => 'pr-list.php',
    'pr_create'     => 'pr-create.php',
    'pr_make'       => 'pr-make.php',
    
    // Purchase Order
    'po_make'       => 'po-make.php',
    'po_list'       => 'po-list.php',
    'po_edit'       => 'po-edit.php',
    'po_view'       => 'po-view.php',
    'po_deliv'      => 'po-deliv.php',
    
    // Voucher
    'voucher_list'  => 'vou-list.php',
    'voc_make'      => 'voc-make.php',
    'voc_view'      => 'voc-view.php',
    'vou_print'     => 'vou-print.php',
    
    // Delivery
    'deliv_list'    => 'deliv-list.php',
    'deliv_view'    => 'deliv-view.php',
    'deliv_make'    => 'deliv-make.php',
    'deliv_edit'    => 'deliv-edit.php',
    
    // Complaint / QA
    'compl_list'    => 'compl-list.php',
    'compl_list2'   => 'compl-list2.php',
    'compl_view'    => 'compl-view.php',
    'qa_list'       => 'qa-list.php',
    
    // Payment & Reports
    'payment'           => 'payment-list.php',
    'invoice_payments'  => 'invoice-payments.php',
    'billing'           => 'billing.php',
    'billing_make'      => 'billing-make.php',
    'report'            => 'report.php',
    'receipt_list'      => 'rep-list.php',
    'rep_make'          => 'rep-make.php',
    'rep_view'          => 'rep-view.php',
    'rep_print'         => 'rep-print.php',
    
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
