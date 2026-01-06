<?php
/**
 * AI Agent Tool Definitions
 * 
 * Defines all tools/functions the AI agent can use to interact with the database
 * Each tool has: name, description, parameters, permission, operation type
 * 
 * @package iACC
 * @subpackage AI
 * @version 1.0
 * @date 2026-01-04
 */

/**
 * Get all available agent tools
 * 
 * @return array Tool definitions
 */
function getAgentTools(): array
{
    return [
        // =========================================================
        // READ Operations - Query Data
        // =========================================================
        
        [
            'name' => 'search_invoices',
            'description' => 'Search invoices by customer name, invoice number, status, date range, or amount. Returns list of matching invoices with key details.',
            'parameters' => [
                'customer' => [
                    'type' => 'string',
                    'description' => 'Customer/company name to search (partial match)',
                ],
                'invoice_number' => [
                    'type' => 'string',
                    'description' => 'Invoice number to search for',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['all', 'paid', 'unpaid', 'overdue', 'partial'],
                    'description' => 'Invoice payment status filter',
                ],
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format',
                ],
                'min_amount' => [
                    'type' => 'number',
                    'description' => 'Minimum invoice amount',
                ],
                'max_amount' => [
                    'type' => 'number',
                    'description' => 'Maximum invoice amount',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum number of results (default 10)',
                ],
            ],
            'permission' => 'view_invoice',
            'operation' => 'read',
        ],
        
        [
            'name' => 'get_invoice_details',
            'description' => 'Get complete details of a specific invoice including line items, customer info, and payment status.',
            'parameters' => [
                'invoice_id' => [
                    'type' => 'integer',
                    'description' => 'Invoice ID (primary key)',
                ],
                'invoice_number' => [
                    'type' => 'string',
                    'description' => 'Invoice number (e.g., INV-2026-001)',
                ],
            ],
            'permission' => 'view_invoice',
            'operation' => 'read',
        ],
        
        [
            'name' => 'search_purchase_orders',
            'description' => 'Search purchase orders by customer, PO number, status, or date range.',
            'parameters' => [
                'customer' => [
                    'type' => 'string',
                    'description' => 'Customer/company name',
                ],
                'po_number' => [
                    'type' => 'string',
                    'description' => 'PO number to search for',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['all', 'draft', 'pending', 'approved', 'shipped', 'delivered', 'cancelled'],
                    'description' => 'PO status filter',
                ],
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date YYYY-MM-DD',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date YYYY-MM-DD',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum results',
                ],
            ],
            'permission' => 'view_po',
            'operation' => 'read',
        ],
        
        [
            'name' => 'search_quotations',
            'description' => 'Search quotations/proposals by customer, quote number, or date.',
            'parameters' => [
                'customer' => [
                    'type' => 'string',
                    'description' => 'Customer name',
                ],
                'quote_number' => [
                    'type' => 'string',
                    'description' => 'Quotation number',
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['all', 'draft', 'sent', 'accepted', 'rejected', 'expired'],
                    'description' => 'Quotation status',
                ],
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum results',
                ],
            ],
            'permission' => 'view_quotation',
            'operation' => 'read',
        ],
        
        [
            'name' => 'search_customers',
            'description' => 'Search customers/companies by name, contact info, or type.',
            'parameters' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Company or contact name',
                ],
                'email' => [
                    'type' => 'string',
                    'description' => 'Email address',
                ],
                'phone' => [
                    'type' => 'string',
                    'description' => 'Phone number',
                ],
                'type' => [
                    'type' => 'string',
                    'enum' => ['all', 'customer', 'vendor', 'both'],
                    'description' => 'Company type',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum results',
                ],
            ],
            'permission' => 'view_company',
            'operation' => 'read',
        ],
        
        [
            'name' => 'get_customer_summary',
            'description' => 'Get customer profile including outstanding balance, credit limit, recent transactions, and payment history.',
            'parameters' => [
                'customer_id' => [
                    'type' => 'integer',
                    'description' => 'Customer ID',
                ],
                'customer_name' => [
                    'type' => 'string',
                    'description' => 'Customer name (if ID not known)',
                ],
            ],
            'permission' => 'view_company',
            'operation' => 'read',
        ],
        
        [
            'name' => 'get_dashboard_summary',
            'description' => 'Get overview summary of invoices, payments, receivables, and key metrics for a period.',
            'parameters' => [
                'period' => [
                    'type' => 'string',
                    'enum' => ['today', 'yesterday', 'this_week', 'last_week', 'this_month', 'last_month', 'this_year'],
                    'description' => 'Time period for summary',
                    'required' => true,
                ],
            ],
            'permission' => 'view_dashboard',
            'operation' => 'read',
        ],
        
        [
            'name' => 'get_payment_status',
            'description' => 'Get payment status and history for a specific invoice or customer.',
            'parameters' => [
                'invoice_id' => [
                    'type' => 'integer',
                    'description' => 'Invoice ID to check',
                ],
                'customer_id' => [
                    'type' => 'integer',
                    'description' => 'Get all payments for this customer',
                ],
            ],
            'permission' => 'view_payment',
            'operation' => 'read',
        ],
        
        [
            'name' => 'get_overdue_invoices',
            'description' => 'Get list of overdue invoices with days overdue and customer contact info.',
            'parameters' => [
                'days_overdue' => [
                    'type' => 'integer',
                    'description' => 'Minimum days overdue (default: 1)',
                ],
                'customer_id' => [
                    'type' => 'integer',
                    'description' => 'Filter by specific customer',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum results',
                ],
            ],
            'permission' => 'view_invoice',
            'operation' => 'read',
        ],
        
        [
            'name' => 'search_products',
            'description' => 'Search products/services by name, code, category, or brand.',
            'parameters' => [
                'name' => [
                    'type' => 'string',
                    'description' => 'Product name',
                ],
                'code' => [
                    'type' => 'string',
                    'description' => 'Product code/SKU',
                ],
                'category_id' => [
                    'type' => 'integer',
                    'description' => 'Category ID',
                ],
                'brand_id' => [
                    'type' => 'integer',
                    'description' => 'Brand ID',
                ],
                'limit' => [
                    'type' => 'integer',
                    'description' => 'Maximum results',
                ],
            ],
            'permission' => 'view_product',
            'operation' => 'read',
        ],
        
        // =========================================================
        // WRITE Operations - Modify Data (require confirmation)
        // =========================================================
        
        [
            'name' => 'mark_invoice_paid',
            'description' => 'Mark an invoice as paid. Records payment reference and date.',
            'parameters' => [
                'invoice_id' => [
                    'type' => 'integer',
                    'description' => 'Invoice ID to mark as paid',
                    'required' => true,
                ],
                'payment_ref' => [
                    'type' => 'string',
                    'description' => 'Payment reference number (e.g., transfer ref)',
                ],
                'payment_date' => [
                    'type' => 'string',
                    'description' => 'Payment date YYYY-MM-DD (default: today)',
                ],
                'amount_paid' => [
                    'type' => 'number',
                    'description' => 'Amount paid (default: full invoice amount)',
                ],
                'payment_method' => [
                    'type' => 'string',
                    'enum' => ['transfer', 'cash', 'check', 'credit_card', 'other'],
                    'description' => 'Payment method used',
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Additional payment notes',
                ],
            ],
            'permission' => 'edit_invoice',
            'operation' => 'write',
            'confirm' => true,
            'audit' => true,
        ],
        
        [
            'name' => 'update_invoice_status',
            'description' => 'Update invoice status (draft, sent, cancelled, etc.).',
            'parameters' => [
                'invoice_id' => [
                    'type' => 'integer',
                    'description' => 'Invoice ID',
                    'required' => true,
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'sent', 'cancelled', 'void'],
                    'description' => 'New status',
                    'required' => true,
                ],
                'reason' => [
                    'type' => 'string',
                    'description' => 'Reason for status change',
                ],
            ],
            'permission' => 'edit_invoice',
            'operation' => 'write',
            'confirm' => true,
            'audit' => true,
        ],
        
        [
            'name' => 'update_po_status',
            'description' => 'Update purchase order status.',
            'parameters' => [
                'po_id' => [
                    'type' => 'integer',
                    'description' => 'Purchase Order ID',
                    'required' => true,
                ],
                'status' => [
                    'type' => 'string',
                    'enum' => ['draft', 'pending', 'approved', 'shipped', 'delivered', 'cancelled'],
                    'description' => 'New status',
                    'required' => true,
                ],
                'notes' => [
                    'type' => 'string',
                    'description' => 'Status update notes',
                ],
            ],
            'permission' => 'edit_po',
            'operation' => 'write',
            'confirm' => true,
            'audit' => true,
        ],
        
        [
            'name' => 'add_note',
            'description' => 'Add a note/comment to an invoice, PO, customer, or other record.',
            'parameters' => [
                'entity_type' => [
                    'type' => 'string',
                    'enum' => ['invoice', 'po', 'quotation', 'customer', 'payment'],
                    'description' => 'Type of record to add note to',
                    'required' => true,
                ],
                'entity_id' => [
                    'type' => 'integer',
                    'description' => 'ID of the record',
                    'required' => true,
                ],
                'note' => [
                    'type' => 'string',
                    'description' => 'The note content',
                    'required' => true,
                ],
            ],
            'permission' => 'edit_notes',
            'operation' => 'write',
            'confirm' => false,  // Low-risk action
            'audit' => true,
        ],
        
        [
            'name' => 'update_customer_contact',
            'description' => 'Update customer contact information (email, phone, address).',
            'parameters' => [
                'customer_id' => [
                    'type' => 'integer',
                    'description' => 'Customer ID',
                    'required' => true,
                ],
                'email' => [
                    'type' => 'string',
                    'description' => 'New email address',
                ],
                'phone' => [
                    'type' => 'string',
                    'description' => 'New phone number',
                ],
                'contact_person' => [
                    'type' => 'string',
                    'description' => 'Contact person name',
                ],
            ],
            'permission' => 'edit_company',
            'operation' => 'write',
            'confirm' => true,
            'audit' => true,
        ],
        
        [
            'name' => 'record_payment',
            'description' => 'Record a new payment received from a customer.',
            'parameters' => [
                'customer_id' => [
                    'type' => 'integer',
                    'description' => 'Customer making payment',
                    'required' => true,
                ],
                'amount' => [
                    'type' => 'number',
                    'description' => 'Payment amount',
                    'required' => true,
                ],
                'invoice_id' => [
                    'type' => 'integer',
                    'description' => 'Invoice to apply payment to',
                ],
                'payment_ref' => [
                    'type' => 'string',
                    'description' => 'Payment reference number',
                ],
                'payment_date' => [
                    'type' => 'string',
                    'description' => 'Payment date YYYY-MM-DD',
                ],
                'payment_method' => [
                    'type' => 'string',
                    'enum' => ['transfer', 'cash', 'check', 'credit_card', 'other'],
                    'description' => 'Payment method',
                ],
            ],
            'permission' => 'create_payment',
            'operation' => 'write',
            'confirm' => true,
            'audit' => true,
        ],
        
        // =========================================================
        // UTILITY Operations
        // =========================================================
        
        [
            'name' => 'calculate_totals',
            'description' => 'Calculate invoice totals with discount and VAT.',
            'parameters' => [
                'subtotal' => [
                    'type' => 'number',
                    'description' => 'Subtotal before discount',
                    'required' => true,
                ],
                'discount_percent' => [
                    'type' => 'number',
                    'description' => 'Discount percentage',
                ],
                'discount_amount' => [
                    'type' => 'number',
                    'description' => 'Fixed discount amount',
                ],
                'include_vat' => [
                    'type' => 'boolean',
                    'description' => 'Include 7% VAT',
                ],
            ],
            'permission' => null,  // No permission needed
            'operation' => 'utility',
        ],
        
        [
            'name' => 'format_currency',
            'description' => 'Format a number as Thai Baht currency.',
            'parameters' => [
                'amount' => [
                    'type' => 'number',
                    'description' => 'Amount to format',
                    'required' => true,
                ],
            ],
            'permission' => null,
            'operation' => 'utility',
        ],
        
        // =========================================================
        // REPORT & ANALYTICS Tools
        // =========================================================
        
        [
            'name' => 'get_sales_report',
            'description' => 'Get sales report for a date range. Returns total revenue, invoice count, top customers, and monthly breakdown.',
            'parameters' => [
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format',
                    'required' => true,
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format',
                    'required' => true,
                ],
                'group_by' => [
                    'type' => 'string',
                    'enum' => ['day', 'week', 'month', 'customer'],
                    'description' => 'Group results by period or customer',
                ],
            ],
            'permission' => 'view_report',
            'operation' => 'read',
        ],
        
        [
            'name' => 'get_revenue_trend',
            'description' => 'Analyze revenue trends over time. Compare periods, identify growth, and forecast.',
            'parameters' => [
                'months' => [
                    'type' => 'integer',
                    'description' => 'Number of months to analyze (default 12)',
                ],
                'compare_previous' => [
                    'type' => 'boolean',
                    'description' => 'Compare with previous period',
                ],
            ],
            'permission' => 'view_report',
            'operation' => 'read',
        ],
        
        [
            'name' => 'get_customer_analysis',
            'description' => 'Analyze customer purchasing behavior. Returns top customers by revenue, purchase frequency, and average order value.',
            'parameters' => [
                'top_count' => [
                    'type' => 'integer',
                    'description' => 'Number of top customers to return (default 10)',
                ],
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date for analysis period',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date for analysis period',
                ],
            ],
            'permission' => 'view_report',
            'operation' => 'read',
        ],
        
        [
            'name' => 'get_aging_report',
            'description' => 'Get accounts receivable aging report. Shows outstanding invoices grouped by age (30, 60, 90+ days).',
            'parameters' => [
                'customer_id' => [
                    'type' => 'integer',
                    'description' => 'Filter by specific customer',
                ],
                'include_paid' => [
                    'type' => 'boolean',
                    'description' => 'Include paid invoices in analysis',
                ],
            ],
            'permission' => 'view_report',
            'operation' => 'read',
        ],
        
        [
            'name' => 'get_payment_summary',
            'description' => 'Get payment summary and collection rate. Shows total collected, pending, overdue amounts.',
            'parameters' => [
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date in YYYY-MM-DD format',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date in YYYY-MM-DD format',
                ],
            ],
            'permission' => 'view_report',
            'operation' => 'read',
        ],
        
        [
            'name' => 'export_data',
            'description' => 'Export data to a downloadable format. Returns a link to download the exported file.',
            'parameters' => [
                'data_type' => [
                    'type' => 'string',
                    'enum' => ['invoices', 'purchase_orders', 'customers', 'payments'],
                    'description' => 'Type of data to export',
                    'required' => true,
                ],
                'date_from' => [
                    'type' => 'string',
                    'description' => 'Start date filter',
                ],
                'date_to' => [
                    'type' => 'string',
                    'description' => 'End date filter',
                ],
                'format' => [
                    'type' => 'string',
                    'enum' => ['csv', 'json'],
                    'description' => 'Export format (default csv)',
                ],
            ],
            'permission' => 'export_data',
            'operation' => 'read',
        ],
    ];
}

/**
 * Get all available tools (agent tools + schema tools)
 * 
 * @return array All tool definitions
 */
function getAllTools(): array
{
    return array_merge(getAgentTools(), getSchemaTools());
}

/**
 * Get tool by name
 * 
 * @param string $toolName Tool name
 * @return array|null Tool definition or null
 */
function getToolByName(string $toolName): ?array
{
    $tools = getAllTools();
    
    foreach ($tools as $tool) {
        if ($tool['name'] === $toolName) {
            return $tool;
        }
    }
    
    return null;
}

/**
 * Get tools filtered by operation type
 * 
 * @param string $operation 'read', 'write', or 'utility'
 * @return array Filtered tools
 */
function getToolsByOperation(string $operation): array
{
    return array_filter(getAllTools(), function($tool) use ($operation) {
        return ($tool['operation'] ?? '') === $operation;
    });
}

/**
 * Get tools that require confirmation
 * 
 * @return array Tools requiring confirmation
 */
function getConfirmableTools(): array
{
    return array_filter(getAgentTools(), function($tool) {
        return !empty($tool['confirm']);
    });
}

/**
 * Get schema discovery tools
 * These allow the AI to understand database structure
 * 
 * @return array Schema tools
 */
function getSchemaTools(): array
{
    return [
        [
            'name' => 'list_database_tables',
            'description' => 'List all tables in the database with row counts. Use this to understand the database structure.',
            'parameters' => [],
            'permission' => null, // No permission needed for schema discovery
            'operation' => 'read',
        ],
        
        [
            'name' => 'describe_table',
            'description' => 'Get detailed information about a specific database table including columns, data types, keys, and sample data.',
            'parameters' => [
                'table_name' => [
                    'type' => 'string',
                    'description' => 'Name of the table to describe',
                    'required' => true,
                ],
                'include_sample' => [
                    'type' => 'boolean',
                    'description' => 'Include sample data rows (default: true)',
                ],
            ],
            'permission' => null,
            'operation' => 'read',
        ],
        
        [
            'name' => 'search_schema',
            'description' => 'Search for tables or columns matching a pattern. Use this to find where specific data is stored.',
            'parameters' => [
                'pattern' => [
                    'type' => 'string',
                    'description' => 'Search pattern (supports partial matching)',
                    'required' => true,
                ],
            ],
            'permission' => null,
            'operation' => 'read',
        ],
        
        [
            'name' => 'get_table_relationships',
            'description' => 'Get foreign key relationships for a table or all tables.',
            'parameters' => [
                'table_name' => [
                    'type' => 'string',
                    'description' => 'Table name (optional, shows all if not provided)',
                ],
            ],
            'permission' => null,
            'operation' => 'read',
        ],
        
        [
            'name' => 'get_database_summary',
            'description' => 'Get a high-level summary of the database including all tables, key relationships, and common query patterns.',
            'parameters' => [],
            'permission' => null,
            'operation' => 'read',
        ],
    ];
}

/**
 * Check if user has permission for a tool
 * 
 * @param array $tool Tool definition
 * @param int $userLevel User's permission level
 * @param array $userPermissions User's specific permissions
 * @return bool
 */
function userCanUseTool(array $tool, int $userLevel, array $userPermissions = []): bool
{
    // Utility tools don't need permission
    if (empty($tool['permission'])) {
        return true;
    }
    
    // Super admin can do everything
    if ($userLevel >= 2) {
        return true;
    }
    
    // Check specific permission
    return in_array($tool['permission'], $userPermissions);
}
