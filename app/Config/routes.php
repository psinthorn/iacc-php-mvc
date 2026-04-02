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
    'invoice_split_group_json' => ['InvoiceController', 'splitGroupJson', 'standalone'],

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
    'voucher_make'  => ['VoucherController', 'make'],
    'voucher_view'  => ['VoucherController', 'view'],
    'voc_make'      => ['VoucherController', 'make'],   // legacy alias
    'voc_view'      => ['VoucherController', 'view'],   // legacy alias
    'voucher_store' => ['VoucherController', 'store'],
    'vou_print'     => ['VoucherController', 'print', 'standalone'],

    // Delivery (Phase 3E)
    'deliv_list'    => ['DeliveryController', 'index'],
    'deliv_make'    => ['DeliveryController', 'make'],
    'deliv_edit'    => ['DeliveryController', 'edit'],
    'deliv_view'    => ['DeliveryController', 'view'],
    'deliv_store'   => ['DeliveryController', 'store'],

    // Receipt (Phase 3E)
    'receipt_list'  => ['ReceiptController', 'index'],
    'receipt_make'  => ['ReceiptController', 'make'],
    'receipt_view'  => ['ReceiptController', 'view'],
    'rep_make'      => ['ReceiptController', 'make'],   // legacy alias
    'rep_view'      => ['ReceiptController', 'view'],   // legacy alias
    'receipt_store' => ['ReceiptController', 'store'],
    'rep_print'     => ['ReceiptController', 'print', 'standalone'],

    // Billing (Phase 3E)
    'billing'       => ['BillingController', 'index'],
    'billing_make'  => ['BillingController', 'make'],
    'billing_store' => ['BillingController', 'store'],
    'billing_view'  => ['BillingController', 'view'],
    'billing_print' => ['BillingController', 'print', 'standalone'],
    'billing_invoices_json' => ['BillingController', 'invoicesJson', 'standalone'],

    // ========== Phase 4 MVC Routes ==========
    
    // Dashboard
    'dashboard'         => ['DashboardController', 'index'],
    'dashboard_store'   => ['DashboardController', 'store'],
    
    // User Management
    'user'              => ['UserController', 'index'],
    'user_store'        => ['UserController', 'store'],
    
    // Reports & Invoice Payments
    'report_hub'        => ['ReportController', 'hub'],
    'report_ar_aging'   => ['ReportController', 'arAging'],
    'invoice_payments'  => ['ReportController', 'invoicePayments'],
    'report'            => ['ReportController', 'summary'],
    
    // Audit Log
    'audit_log'         => ['AuditLogController', 'index'],
    
    // User Account
    'profile'           => ['UserAccountController', 'profile'],
    'settings'          => ['UserAccountController', 'settings'],
    'account_store'     => ['UserAccountController', 'store'],

    // ========== Phase 6: Admin & Dev Tools (MVC) ==========
    'monitoring'            => ['DevToolsController', 'monitoring'],
    'containers'            => ['DevToolsController', 'containers'],
    // ========== Phase 5A: Payment Gateway (MVC) ==========
    'payment_gateway_config' => ['PaymentGatewayController', 'index'],
    'payment_gateway_test'   => ['PaymentGatewayController', 'test'],
    'payment_webhook'        => ['PaymentGatewayController', 'webhook'],

    // ========== Phase 5B: Invoice Payment Flow (MVC) ==========
    'inv_checkout'           => ['InvoicePaymentController', 'checkout'],
    'inv_payment_success'    => ['InvoicePaymentController', 'success'],
    'inv_payment_cancel'     => ['InvoicePaymentController', 'cancel'],

    // ========== Phase 5C: AI Admin Panel (MVC) ==========
    'ai_chat_history'        => ['AiAdminController', 'chatHistory'],
    'ai_schema_browser'      => ['AiAdminController', 'schemaBrowser'],
    'ai_action_log'          => ['AiAdminController', 'actionLog'],
    'ai_schema_refresh'      => ['AiAdminController', 'schemaRefresh'],

    // ========== Phase 5D: AI Core (MVC) ==========
    'ai_settings'            => ['AiSettingsController', 'index'],
    'ai_settings_api'        => ['AiSettingsController', 'api', 'standalone'],
    'ai_chat'                => ['AiChatController', 'index', 'standalone'],

    // Developer Tools (Admin Only) — rendered inside admin layout
    'test_crud'              => ['DevToolsController', 'testCrud'],
    'test_crud_ai'           => ['DevToolsController', 'testCrudAi'],
    'test_rbac'              => ['DevToolsController', 'testRbac'],
    'ai_documentation'       => ['AiAdminController', 'documentation'],
    'debug_session'          => ['DevToolsController', 'debugSession'],
    'debug_php'              => ['DevToolsController', 'debugPhp'],
    'test_containers'        => ['DevToolsController', 'testContainers'],
    'api_lang_debug'         => ['DevToolsController', 'langDebug'],
    'dev_roadmap'            => ['DevToolsController', 'roadmap'],
    // DevTools AJAX/API endpoints (standalone — JSON responses)
    'debug_session_api'      => ['DevToolsController', 'debugSessionApi', 'standalone'],
    'debug_php_api'          => ['DevToolsController', 'debugPhpApi', 'standalone'],
    'lang_debug_api'         => ['DevToolsController', 'langDebugApi', 'standalone'],
    
    // User Account
    'help'                   => ['HelpController', 'index'],
    'master_data_guide'      => ['HelpController', 'masterDataGuide', 'standalone'],
    'user_manual'            => ['HelpController', 'userManual'],
    'dev_summary'            => ['HelpController', 'devSummary', 'standalone'],

    // ========== System / DevOps ==========
    'health'                 => ['HealthController', 'index', 'public'],
    'health_diagnose'        => ['HealthController', 'diagnose'],

    // ========== Phase 7: Sales Channel API Admin (MVC) ==========
    'api_dashboard'          => ['AdminApiController', 'dashboard'],
    'api_subscriptions'      => ['AdminApiController', 'subscriptions'],
    'api_subscription_toggle'=> ['AdminApiController', 'toggleSubscription'],
    'api_subscription_plan'  => ['AdminApiController', 'changePlan'],
    'api_keys'               => ['AdminApiController', 'keys'],
    'api_activate_trial'     => ['AdminApiController', 'activateTrial'],
    'api_key_create'         => ['AdminApiController', 'createKey'],
    'api_key_revoke'         => ['AdminApiController', 'revokeKey'],
    'api_orders'           => ['AdminApiController', 'orders'],
    'api_order_detail'     => ['AdminApiController', 'orderDetail'],
    'api_order_update_status' => ['AdminApiController', 'updateOrderStatus'],
    'api_usage_logs'         => ['AdminApiController', 'usageLogs'],
    'api_webhooks'           => ['AdminApiController', 'webhooks'],
    'api_webhook_create'     => ['AdminApiController', 'createWebhook'],
    'api_webhook_toggle'     => ['AdminApiController', 'toggleWebhook'],
    'api_webhook_delete'     => ['AdminApiController', 'deleteAdminWebhook'],
    'api_key_rotate'         => ['AdminApiController', 'rotateKey'],
    'api_docs'               => ['AdminApiController', 'docs'],
    'api_orders_export'      => ['AdminApiController', 'exportOrders', 'standalone'],
    'api_webhook_deliveries' => ['AdminApiController', 'webhookDeliveries'],
    'api_upgrade'            => ['AdminApiController', 'upgradePlan'],
    'api_request_upgrade'    => ['AdminApiController', 'requestUpgrade'],
    'api_invoices'           => ['AdminApiController', 'invoices'],
    'api_invoice_generate'   => ['AdminApiController', 'generateInvoice'],

    // ========== LINE OA Sales Channel (MVC) ==========
    'line_dashboard'     => ['LineOAController', 'dashboard'],
    'line_settings'      => ['LineOAController', 'settings'],
    'line_store'         => ['LineOAController', 'store'],
    'line_orders'        => ['LineOAController', 'orders'],
    'line_order_detail'  => ['LineOAController', 'orderDetail'],
    'line_messages'      => ['LineOAController', 'messages'],
    'line_users'         => ['LineOAController', 'users'],
    'line_auto_replies'  => ['LineOAController', 'autoReplies'],
    'line_webhook_log'   => ['LineOAController', 'webhookLog'],
    'line_send_message'  => ['LineOAController', 'sendMessagePage'],

    // ========== Pre-Auth Routes (no login required, standalone HTML) ==========
    'authorize'              => ['AuthController', 'authenticate', 'public'],
    'forgot_password'        => ['AuthController', 'forgotPassword', 'public'],
    'reset_password'         => ['AuthController', 'resetPassword', 'public'],
    'lang_switch'            => ['AuthController', 'switchLanguage', 'public'],

    // ========== Self-Registration (v6.0) ==========
    'register'               => ['RegistrationController', 'showForm', 'public'],
    'register_submit'        => ['RegistrationController', 'register', 'public'],
    'register_sent'          => ['RegistrationController', 'sent', 'public'],
    'register_verify'        => ['RegistrationController', 'verify', 'public'],
    'onboarding'             => ['RegistrationController', 'onboarding'],
    'onboarding_complete'    => ['RegistrationController', 'completeOnboarding'],
    'plans'                  => ['RegistrationController', 'plans'],

    // ========== Phase 8: Q2 2026 – Payment Gateway & Multi-Currency (MVC) ==========

    // ========== Q3 2026 – Expense Module ==========
    'expense_list'           => ['ExpenseController', 'index'],
    'expense_form'           => ['ExpenseController', 'form'],
    'expense_store'          => ['ExpenseController', 'store'],
    'expense_view'           => ['ExpenseController', 'view'],
    'expense_delete'         => ['ExpenseController', 'delete'],
    'expense_status'         => ['ExpenseController', 'status'],
    'expense_summary'        => ['ExpenseController', 'summary'],
    'expense_cat_list'       => ['ExpenseController', 'categories'],
    'expense_cat_store'      => ['ExpenseController', 'categoryStore'],
    'expense_cat_toggle'     => ['ExpenseController', 'categoryToggle'],
    'expense_cat_delete'     => ['ExpenseController', 'categoryDelete'],
    'expense_project_report' => ['ExpenseController', 'projectReport'],
    'expense_export'         => ['ExpenseController', 'export', 'standalone'],

    // Tax Reports

    'tax_reports'            => ['TaxReportController', 'index'],
    'tax_report_pp30'        => ['TaxReportController', 'pp30'],
    'tax_report_wht'         => ['TaxReportController', 'wht'],
    'tax_report_save'        => ['TaxReportController', 'save'],
    'tax_report_export'      => ['TaxReportController', 'export', 'standalone'],

    // Currency Management
    'currency_list'          => ['CurrencyController', 'index'],
    'currency_rates'         => ['CurrencyController', 'rates'],
    'currency_refresh'       => ['CurrencyController', 'refresh'],
    'currency_toggle'        => ['CurrencyController', 'toggle'],

    // ========== Q3 2026 – Journal Module & Voucher Classification ==========
    'journal_list'           => ['JournalController', 'index'],
    'journal_form'           => ['JournalController', 'form'],
    'journal_store'          => ['JournalController', 'store'],
    'journal_view'           => ['JournalController', 'view'],
    'journal_post'           => ['JournalController', 'post'],
    'journal_cancel'         => ['JournalController', 'cancelVoucher'],
    'journal_delete'         => ['JournalController', 'delete'],
    'journal_accounts'       => ['JournalController', 'accounts'],
    'journal_account_store'  => ['JournalController', 'accountStore'],
    'journal_account_toggle' => ['JournalController', 'accountToggle'],
    'journal_trial_balance'  => ['JournalController', 'trialBalance'],

    // PromptPay Payment
    'promptpay_checkout'     => ['InvoicePaymentController', 'promptpayCheckout'],
    'promptpay_confirm'      => ['InvoicePaymentController', 'promptpayConfirm'],

    // Slip Review (Admin)
    'slip_review'            => ['SlipReviewController', 'index'],
    'slip_review_approve'    => ['SlipReviewController', 'approve'],
    'slip_review_reject'     => ['SlipReviewController', 'reject'],

    // ========== Standalone Routes (auth required, no admin shell) ==========

    // PDF / Print Generators
    'pdf_quotation'          => ['PdfController', 'quotation', 'standalone'],
    'pdf_quotation_mail'     => ['PdfController', 'quotationMail', 'standalone'],
    'pdf_invoice'            => ['PdfController', 'invoice', 'standalone'],
    'pdf_invoice_mail'       => ['PdfController', 'invoiceMail', 'standalone'],
    'pdf_tax_invoice'        => ['PdfController', 'taxInvoice', 'standalone'],
    'pdf_tax_invoice_mail'   => ['PdfController', 'taxInvoiceMail', 'standalone'],
    'pdf_receipt'            => ['PdfController', 'receipt', 'standalone'],
    'pdf_split_invoice'      => ['PdfController', 'splitInvoice', 'standalone'],

    // Data Exports
    'export_invoice_payments'=> ['ExportController', 'invoicePayments', 'standalone'],
    'export_report'          => ['ExportController', 'report', 'standalone'],

    // AJAX Endpoints
    'ajax_options'           => ['AjaxController', 'productOptions', 'standalone'],
    'ajax_mail'              => ['AjaxController', 'emailPreview', 'standalone'],
];
