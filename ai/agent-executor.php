<?php
/**
 * AI Agent Executor
 * 
 * Safely executes agent tools with permission checking,
 * confirmation handling, and audit logging
 * 
 * @package iACC
 * @subpackage AI
 * @version 1.0
 * @date 2026-01-04
 */

require_once __DIR__ . '/agent-tools.php';

class AgentExecutor
{
    private $db;
    private int $companyId;
    private int $userId;
    private string $sessionId;
    private array $config;
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     * @param int $companyId Current company ID
     * @param int $userId Current user ID
     * @param string $sessionId Chat session ID
     * @param array $config Agent configuration
     */
    public function __construct($db, int $companyId, int $userId, string $sessionId, array $config = [])
    {
        $this->db = $db;
        $this->companyId = $companyId;
        $this->userId = $userId;
        $this->sessionId = $sessionId;
        $this->config = $config;
    }
    
    /**
     * Execute a tool call
     * 
     * @param string $toolName Tool to execute
     * @param array $params Parameters for the tool
     * @param bool $confirmed Whether action is confirmed (for write operations)
     * @return array Execution result
     */
    public function execute(string $toolName, array $params, bool $confirmed = false): array
    {
        // Get tool definition
        $tool = getToolByName($toolName);
        
        if (!$tool) {
            return $this->error("Unknown tool: $toolName");
        }
        
        // Check permissions
        $userLevel = $_SESSION['user_level'] ?? 0;
        if (!userCanUseTool($tool, $userLevel)) {
            return $this->error("Permission denied for: $toolName");
        }
        
        // Check if confirmation is needed
        if (!empty($tool['confirm']) && !$confirmed) {
            return $this->requireConfirmation($toolName, $params, $tool);
        }
        
        // Log the action attempt
        $logId = $this->logAction($toolName, $params, 'pending');
        
        try {
            // Execute based on tool name
            $result = $this->executeToolMethod($toolName, $params);
            
            // Update log with success
            $this->updateActionLog($logId, 'executed', $result);
            
            return [
                'success' => true,
                'tool' => $toolName,
                'result' => $result,
            ];
            
        } catch (Exception $e) {
            // Update log with failure
            $this->updateActionLog($logId, 'failed', null, $e->getMessage());
            
            return $this->error($e->getMessage());
        }
    }
    
    /**
     * Execute the actual tool method
     * 
     * @param string $toolName Tool name
     * @param array $params Parameters
     * @return mixed Result
     */
    private function executeToolMethod(string $toolName, array $params)
    {
        switch ($toolName) {
            // ========== READ Operations ==========
            
            case 'search_invoices':
                return $this->searchInvoices($params);
                
            case 'get_invoice_details':
                return $this->getInvoiceDetails($params);
                
            case 'search_purchase_orders':
                return $this->searchPurchaseOrders($params);
                
            case 'search_quotations':
                return $this->searchQuotations($params);
                
            case 'search_customers':
                return $this->searchCustomers($params);
                
            case 'get_customer_summary':
                return $this->getCustomerSummary($params);
                
            case 'get_dashboard_summary':
                return $this->getDashboardSummary($params);
                
            case 'get_payment_status':
                return $this->getPaymentStatus($params);
                
            case 'get_overdue_invoices':
                return $this->getOverdueInvoices($params);
                
            case 'search_products':
                return $this->searchProducts($params);
            
            // ========== WRITE Operations ==========
            
            case 'mark_invoice_paid':
                return $this->markInvoicePaid($params);
                
            case 'update_invoice_status':
                return $this->updateInvoiceStatus($params);
                
            case 'update_po_status':
                return $this->updatePoStatus($params);
                
            case 'add_note':
                return $this->addNote($params);
                
            case 'update_customer_contact':
                return $this->updateCustomerContact($params);
                
            case 'record_payment':
                return $this->recordPayment($params);
            
            // ========== Utility Operations ==========
            
            case 'calculate_totals':
                return $this->calculateTotals($params);
                
            case 'format_currency':
                return $this->formatCurrency($params);
            
            // ========== Schema Discovery Operations ==========
            
            case 'list_database_tables':
                return $this->listDatabaseTables($params);
                
            case 'describe_table':
                return $this->describeTable($params);
                
            case 'search_schema':
                return $this->searchSchema($params);
                
            case 'get_table_relationships':
                return $this->getTableRelationships($params);
                
            case 'get_database_summary':
                return $this->getDatabaseSummary($params);
            
            // ========== Report & Analytics Operations ==========
            
            case 'get_sales_report':
                return $this->getSalesReport($params);
                
            case 'get_revenue_trend':
                return $this->getRevenueTrend($params);
                
            case 'get_customer_analysis':
                return $this->getCustomerAnalysis($params);
                
            case 'get_aging_report':
                return $this->getAgingReport($params);
                
            case 'get_payment_summary':
                return $this->getPaymentSummary($params);
                
            case 'export_data':
                return $this->exportData($params);
                
            default:
                throw new Exception("Tool not implemented: $toolName");
        }
    }
    
    // =========================================================
    // READ Operation Implementations
    // =========================================================
    
    private function searchInvoices(array $params): array
    {
        // Query matches the actual database schema:
        // iv -> po -> pr -> company, with product for totals and pay for payments
        $sql = "SELECT 
                    iv.tex as invoice_id,
                    po.id as po_id,
                    po.name as invoice_name,
                    DATE_FORMAT(iv.createdate, '%Y-%m-%d') as invoice_date,
                    iv.taxrw as invoice_number,
                    iv.status_iv as status,
                    iv.payment_status,
                    c.name_en as customer_name,
                    c.id as customer_id,
                    COALESCE(prod.total_amount, 0) as total_amount,
                    COALESCE(paid.paid_amount, 0) as paid_amount
                FROM iv
                JOIN po ON iv.tex = po.id
                JOIN pr ON po.ref = pr.id
                LEFT JOIN company c ON pr.cus_id = c.id
                LEFT JOIN (SELECT po_id, SUM(price * quantity) as total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
                LEFT JOIN (SELECT po_id, SUM(volumn) as paid_amount FROM pay WHERE deleted_at IS NULL GROUP BY po_id) paid ON po.id = paid.po_id
                WHERE iv.deleted_at IS NULL 
                AND pr.ven_id = ?";
        
        $bindings = [$this->companyId];
        
        if (!empty($params['customer'])) {
            $sql .= " AND c.name_en LIKE ?";
            $bindings[] = '%' . $params['customer'] . '%';
        }
        
        if (!empty($params['invoice_number'])) {
            $sql .= " AND (iv.taxrw LIKE ? OR po.name LIKE ?)";
            $bindings[] = '%' . $params['invoice_number'] . '%';
            $bindings[] = '%' . $params['invoice_number'] . '%';
        }
        
        if (!empty($params['status']) && $params['status'] !== 'all') {
            switch ($params['status']) {
                case 'paid':
                    $sql .= " AND (iv.payment_status = 'paid' OR (COALESCE(paid.paid_amount, 0) >= COALESCE(prod.total_amount, 0) AND prod.total_amount > 0))";
                    break;
                case 'unpaid':
                case 'pending':
                    $sql .= " AND (iv.payment_status = 'pending' OR iv.payment_status IS NULL OR (COALESCE(paid.paid_amount, 0) = 0 AND prod.total_amount > 0))";
                    break;
                case 'partial':
                    $sql .= " AND (iv.payment_status = 'partial' OR (COALESCE(paid.paid_amount, 0) > 0 AND COALESCE(paid.paid_amount, 0) < COALESCE(prod.total_amount, 0)))";
                    break;
                case 'overdue':
                    $sql .= " AND (iv.payment_status != 'paid' OR iv.payment_status IS NULL) AND iv.createdate < CURDATE() - INTERVAL 30 DAY";
                    break;
            }
        }
        
        if (!empty($params['date_from'])) {
            $sql .= " AND iv.createdate >= ?";
            $bindings[] = $params['date_from'];
        }
        
        if (!empty($params['date_to'])) {
            $sql .= " AND iv.createdate <= ?";
            $bindings[] = $params['date_to'];
        }
        
        if (!empty($params['min_amount'])) {
            $sql .= " AND COALESCE(prod.total_amount, 0) >= ?";
            $bindings[] = $params['min_amount'];
        }
        
        if (!empty($params['max_amount'])) {
            $sql .= " AND COALESCE(prod.total_amount, 0) <= ?";
            $bindings[] = $params['max_amount'];
        }
        
        $sql .= " ORDER BY iv.createdate DESC";
        
        $limit = min($params['limit'] ?? 10, 50);
        $sql .= " LIMIT " . intval($limit);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'count' => count($invoices),
            'invoices' => array_map(function($inv) {
                $total = floatval($inv['total_amount']);
                $paid = floatval($inv['paid_amount']);
                $outstanding = $total - $paid;
                
                // Determine status
                $status = 'unpaid';
                if ($paid >= $total && $total > 0) {
                    $status = 'paid';
                } elseif ($paid > 0) {
                    $status = 'partial';
                }
                
                return [
                    'id' => $inv['invoice_id'],
                    'po_id' => $inv['po_id'],
                    'number' => $inv['invoice_number'] ?: $inv['invoice_name'],
                    'name' => $inv['invoice_name'],
                    'date' => $inv['invoice_date'],
                    'customer' => $inv['customer_name'],
                    'customer_id' => $inv['customer_id'],
                    'amount' => $total,
                    'amount_formatted' => '฿' . number_format($total, 2),
                    'paid' => $paid,
                    'paid_formatted' => '฿' . number_format($paid, 2),
                    'outstanding' => $outstanding,
                    'outstanding_formatted' => '฿' . number_format($outstanding, 2),
                    'status' => $status,
                ];
            }, $invoices),
        ];
    }
    
    private function getInvoiceDetails(array $params): array
    {
        $sql = "SELECT 
                    iv.tex as invoice_id,
                    po.id as po_id,
                    po.name as invoice_name,
                    DATE_FORMAT(iv.createdate, '%Y-%m-%d') as invoice_date,
                    iv.taxrw as invoice_number,
                    iv.status_iv as status,
                    iv.payment_status,
                    c.name_en as customer_name,
                    c.phone as customer_phone,
                    c.email as customer_email,
                    c.id as customer_id,
                    po.vat,
                    po.dis as discount,
                    COALESCE(prod.total_amount, 0) as total_amount,
                    COALESCE(paid.paid_amount, 0) as paid_amount
                FROM iv
                JOIN po ON iv.tex = po.id
                JOIN pr ON po.ref = pr.id
                LEFT JOIN company c ON pr.cus_id = c.id
                LEFT JOIN (SELECT po_id, SUM(price * quantity) as total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
                LEFT JOIN (SELECT po_id, SUM(volumn) as paid_amount FROM pay WHERE deleted_at IS NULL GROUP BY po_id) paid ON po.id = paid.po_id
                WHERE iv.deleted_at IS NULL 
                AND pr.ven_id = ?";
        
        $bindings = [$this->companyId];
        
        if (!empty($params['invoice_id'])) {
            $sql .= " AND iv.tex = ?";
            $bindings[] = $params['invoice_id'];
        } elseif (!empty($params['po_id'])) {
            $sql .= " AND po.id = ?";
            $bindings[] = $params['po_id'];
        } elseif (!empty($params['invoice_number'])) {
            $sql .= " AND (iv.taxrw = ? OR po.name LIKE ?)";
            $bindings[] = $params['invoice_number'];
            $bindings[] = '%' . $params['invoice_number'] . '%';
        } else {
            throw new Exception("Invoice ID, PO ID, or invoice number required");
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invoice) {
            throw new Exception("Invoice not found");
        }
        
        $total = floatval($invoice['total_amount']);
        $paid = floatval($invoice['paid_amount']);
        $outstanding = $total - $paid;
        
        $status = 'unpaid';
        if ($paid >= $total && $total > 0) {
            $status = 'paid';
        } elseif ($paid > 0) {
            $status = 'partial';
        }
        
        return [
            'id' => $invoice['invoice_id'],
            'po_id' => $invoice['po_id'],
            'number' => $invoice['invoice_number'] ?: $invoice['invoice_name'],
            'name' => $invoice['invoice_name'],
            'date' => $invoice['invoice_date'],
            'customer' => [
                'id' => $invoice['customer_id'],
                'name' => $invoice['customer_name'],
                'address' => $invoice['customer_address'],
                'phone' => $invoice['customer_phone'],
                'email' => $invoice['customer_email'],
            ],
            'discount' => floatval($invoice['discount'] ?? 0),
            'vat' => floatval($invoice['vat'] ?? 0),
            'total' => $total,
            'total_formatted' => '฿' . number_format($total, 2),
            'paid' => $paid,
            'paid_formatted' => '฿' . number_format($paid, 2),
            'outstanding' => $outstanding,
            'outstanding_formatted' => '฿' . number_format($outstanding, 2),
            'status' => $status,
        ];
    }
    
    private function searchPurchaseOrders(array $params): array
    {
        // Query matches actual database schema
        $sql = "SELECT 
                    po.id as po_id,
                    po.name as po_name,
                    DATE_FORMAT(po.date, '%Y-%m-%d') as po_date,
                    c.name_en as customer_name,
                    c.id as customer_id,
                    COALESCE(prod.total_amount, 0) as total_amount
                FROM po
                JOIN pr ON po.ref = pr.id
                LEFT JOIN company c ON pr.cus_id = c.id
                LEFT JOIN (SELECT po_id, SUM(price * quantity) as total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
                WHERE po.po_id_new = '' 
                AND pr.ven_id = ?";
        
        $bindings = [$this->companyId];
        
        if (!empty($params['customer'])) {
            $sql .= " AND c.name_en LIKE ?";
            $bindings[] = '%' . $params['customer'] . '%';
        }
        
        if (!empty($params['po_number'])) {
            $sql .= " AND po.name LIKE ?";
            $bindings[] = '%' . $params['po_number'] . '%';
        }
        
        if (!empty($params['date_from'])) {
            $sql .= " AND po.date >= ?";
            $bindings[] = $params['date_from'];
        }
        
        if (!empty($params['date_to'])) {
            $sql .= " AND po.date <= ?";
            $bindings[] = $params['date_to'];
        }
        
        $sql .= " ORDER BY po.date DESC";
        
        $limit = min($params['limit'] ?? 10, 50);
        $sql .= " LIMIT " . intval($limit);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'count' => count($orders),
            'purchase_orders' => array_map(function($po) {
                $total = floatval($po['total_amount']);
                return [
                    'id' => $po['po_id'],
                    'number' => $po['po_name'],
                    'date' => $po['po_date'],
                    'customer' => $po['customer_name'],
                    'customer_id' => $po['customer_id'],
                    'amount' => $total,
                    'amount_formatted' => '฿' . number_format($total, 2),
                    'status' => $po['status'],
                ];
            }, $orders),
        ];
    }
    
    private function searchQuotations(array $params): array
    {
        // Query matches actual database schema - pr is the quotation/purchase request table
        $sql = "SELECT 
                    pr.id as pr_id,
                    pr.name as pr_name,
                    DATE_FORMAT(pr.date, '%Y-%m-%d') as pr_date,
                    pr.status,
                    c.name_en as customer_name,
                    c.id as customer_id
                FROM pr 
                LEFT JOIN company c ON pr.cus_id = c.id
                WHERE pr.ven_id = ?";
                
        $bindings = [$this->companyId];
        
        if (!empty($params['customer'])) {
            $sql .= " AND c.name_en LIKE ?";
            $bindings[] = '%' . $params['customer'] . '%';
        }
        
        if (!empty($params['quote_number'])) {
            $sql .= " AND pr.name LIKE ?";
            $bindings[] = '%' . $params['quote_number'] . '%';
        }
        
        $sql .= " ORDER BY pr.date DESC";
        
        $limit = min($params['limit'] ?? 10, 50);
        $sql .= " LIMIT " . intval($limit);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'count' => count($quotes),
            'quotations' => array_map(function($q) {
                return [
                    'id' => $q['pr_id'],
                    'number' => $q['pr_name'],
                    'date' => $q['pr_date'],
                    'customer' => $q['customer_name'],
                    'customer_id' => $q['customer_id'],
                    'status' => $q['status'],
                ];
            }, $quotes),
        ];
    }
    
    private function searchCustomers(array $params): array
    {
        $sql = "SELECT id, name_en, name_sh, phone, email, contact
                FROM company 
                WHERE 1=1";
        
        $bindings = [];
        
        if (!empty($params['name'])) {
            $sql .= " AND (name_en LIKE ? OR name_sh LIKE ?)";
            $bindings[] = '%' . $params['name'] . '%';
            $bindings[] = '%' . $params['name'] . '%';
        }
        
        if (!empty($params['email'])) {
            $sql .= " AND email LIKE ?";
            $bindings[] = '%' . $params['email'] . '%';
        }
        
        if (!empty($params['phone'])) {
            $sql .= " AND phone LIKE ?";
            $bindings[] = '%' . $params['phone'] . '%';
        }
        
        $sql .= " ORDER BY name_en ASC";
        
        $limit = min($params['limit'] ?? 10, 50);
        $sql .= " LIMIT " . intval($limit);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'count' => count($customers),
            'customers' => array_map(function($c) {
                return [
                    'id' => $c['id'],
                    'name' => $c['name_en'] ?: $c['name_sh'],
                    'phone' => $c['phone'],
                    'email' => $c['email'],
                    'contact' => $c['contact'],
                ];
            }, $customers),
        ];
    }
    
    private function getCustomerSummary(array $params): array
    {
        // Get customer info
        $sql = "SELECT * FROM company WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        $customerId = $params['customer_id'] ?? null;
        
        if (!$customerId && !empty($params['customer_name'])) {
            // Find by name
            $findSql = "SELECT id FROM company WHERE name_en LIKE ? OR name_sh LIKE ? LIMIT 1";
            $findStmt = $this->db->prepare($findSql);
            $findStmt->execute(['%' . $params['customer_name'] . '%', '%' . $params['customer_name'] . '%']);
            $found = $findStmt->fetch(PDO::FETCH_ASSOC);
            $customerId = $found['id'] ?? null;
        }
        
        if (!$customerId) {
            throw new Exception("Customer not found");
        }
        
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            throw new Exception("Customer not found");
        }
        
        // Get invoice summary for this customer
        $invSql = "SELECT 
                      COUNT(DISTINCT iv.tex) as total_invoices,
                      COALESCE(SUM(prod.total_amount), 0) as total_amount,
                      COALESCE(SUM(paid.paid_amount), 0) as paid_amount
                   FROM iv
                   JOIN po ON iv.tex = po.id
                   JOIN pr ON po.ref = pr.id
                   LEFT JOIN (SELECT po_id, SUM(price * quantity) as total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
                   LEFT JOIN (SELECT po_id, SUM(volumn) as paid_amount FROM pay WHERE deleted_at IS NULL GROUP BY po_id) paid ON po.id = paid.po_id
                   WHERE iv.deleted_at IS NULL 
                   AND pr.ven_id = ?
                   AND pr.cus_id = ?";
        $invStmt = $this->db->prepare($invSql);
        $invStmt->execute([$this->companyId, $customerId]);
        $invSummary = $invStmt->fetch(PDO::FETCH_ASSOC);
        
        $totalAmount = floatval($invSummary['total_amount'] ?? 0);
        $paidAmount = floatval($invSummary['paid_amount'] ?? 0);
        $outstanding = $totalAmount - $paidAmount;
        
        return [
            'customer' => [
                'id' => $customer['id'],
                'name' => $customer['name_en'] ?: $customer['name_sh'],
                'phone' => $customer['phone'],
                'email' => $customer['email'],
                'address' => $customer['address'],
            ],
            'summary' => [
                'total_invoices' => intval($invSummary['total_invoices']),
                'total_amount' => $totalAmount,
                'total_amount_formatted' => '฿' . number_format($totalAmount, 2),
                'paid_amount' => $paidAmount,
                'paid_amount_formatted' => '฿' . number_format($paidAmount, 2),
                'outstanding' => $outstanding,
                'outstanding_formatted' => '฿' . number_format($outstanding, 2),
            ],
        ];
    }
    
    private function getDashboardSummary(array $params): array
    {
        $period = $params['period'] ?? 'this_month';
        
        // Calculate date range
        $dateRange = $this->getDateRange($period);
        
        // Invoice summary with actual schema
        $invSql = "SELECT 
                      COUNT(DISTINCT iv.tex) as count,
                      COALESCE(SUM(prod.total_amount), 0) as total,
                      COALESCE(SUM(paid.paid_amount), 0) as paid_total,
                      SUM(CASE WHEN COALESCE(paid.paid_amount, 0) >= COALESCE(prod.total_amount, 0) AND prod.total_amount > 0 THEN 1 ELSE 0 END) as paid_count
                   FROM iv
                   JOIN po ON iv.tex = po.id
                   JOIN pr ON po.ref = pr.id
                   LEFT JOIN (SELECT po_id, SUM(price * quantity) as total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
                   LEFT JOIN (SELECT po_id, SUM(volumn) as paid_amount FROM pay WHERE deleted_at IS NULL GROUP BY po_id) paid ON po.id = paid.po_id
                   WHERE iv.deleted_at IS NULL 
                   AND pr.ven_id = ?
                   AND iv.createdate BETWEEN ? AND ?";
        $invStmt = $this->db->prepare($invSql);
        $invStmt->execute([$this->companyId, $dateRange['start'], $dateRange['end']]);
        $invData = $invStmt->fetch(PDO::FETCH_ASSOC);
        
        // PO summary with actual schema
        $poSql = "SELECT 
                    COUNT(*) as count, 
                    COALESCE(SUM(prod.total_amount), 0) as total
                  FROM po
                  JOIN pr ON po.ref = pr.id
                  LEFT JOIN (SELECT po_id, SUM(price * quantity) as total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
                  WHERE po.po_id_new = '' 
                  AND pr.ven_id = ?
                  AND po.date BETWEEN ? AND ?";
        $poStmt = $this->db->prepare($poSql);
        $poStmt->execute([$this->companyId, $dateRange['start'], $dateRange['end']]);
        $poData = $poStmt->fetch(PDO::FETCH_ASSOC);
        
        // Quotation summary with actual schema
        $prSql = "SELECT COUNT(*) as count
                  FROM pr 
                  WHERE pr.ven_id = ?
                  AND pr.date BETWEEN ? AND ?";
        $prStmt = $this->db->prepare($prSql);
        $prStmt->execute([$this->companyId, $dateRange['start'], $dateRange['end']]);
        $prData = $prStmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'period' => $period,
            'date_range' => $dateRange,
            'invoices' => [
                'count' => intval($invData['count']),
                'total' => floatval($invData['total']),
                'total_formatted' => '฿' . number_format($invData['total'], 2),
                'paid_count' => intval($invData['paid_count']),
                'paid_total' => floatval($invData['paid_total']),
                'unpaid_total' => floatval($invData['total'] - $invData['paid_total']),
            ],
            'purchase_orders' => [
                'count' => intval($poData['count']),
                'total' => floatval($poData['total']),
                'total_formatted' => '฿' . number_format($poData['total'], 2),
            ],
            'quotations' => [
                'count' => intval($prData['count']),
            ],
        ];
    }
    
    private function getOverdueInvoices(array $params): array
    {
        $daysOverdue = $params['days_overdue'] ?? 30;
        
        $sql = "SELECT 
                    iv.tex as invoice_id,
                    po.id as po_id,
                    po.name as invoice_name,
                    DATE_FORMAT(iv.createdate, '%Y-%m-%d') as invoice_date,
                    iv.taxrw as invoice_number,
                    c.name_en as customer_name,
                    c.phone as customer_phone,
                    c.email as customer_email,
                    c.id as customer_id,
                    COALESCE(prod.total_amount, 0) as total_amount,
                    COALESCE(paid.paid_amount, 0) as paid_amount,
                    DATEDIFF(CURDATE(), iv.createdate) as days_overdue
                FROM iv
                JOIN po ON iv.tex = po.id
                JOIN pr ON po.ref = pr.id
                LEFT JOIN company c ON pr.cus_id = c.id
                LEFT JOIN (SELECT po_id, SUM(price * quantity) as total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
                LEFT JOIN (SELECT po_id, SUM(volumn) as paid_amount FROM pay WHERE deleted_at IS NULL GROUP BY po_id) paid ON po.id = paid.po_id
                WHERE iv.deleted_at IS NULL 
                AND pr.ven_id = ?
                AND (iv.payment_status != 'paid' OR iv.payment_status IS NULL)
                AND COALESCE(paid.paid_amount, 0) < COALESCE(prod.total_amount, 0)
                AND DATEDIFF(CURDATE(), iv.createdate) >= ?";
        
        $bindings = [$this->companyId, $daysOverdue];
        
        if (!empty($params['customer_id'])) {
            $sql .= " AND pr.cus_id = ?";
            $bindings[] = $params['customer_id'];
        }
        
        $sql .= " ORDER BY days_overdue DESC";
        
        $limit = min($params['limit'] ?? 10, 50);
        $sql .= " LIMIT " . intval($limit);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totalOverdue = 0;
        $formattedInvoices = [];
        
        foreach ($invoices as $inv) {
            $total = floatval($inv['total_amount']);
            $paid = floatval($inv['paid_amount']);
            $outstanding = $total - $paid;
            $totalOverdue += $outstanding;
            
            $formattedInvoices[] = [
                'id' => $inv['invoice_id'],
                'po_id' => $inv['po_id'],
                'number' => $inv['invoice_number'] ?: $inv['invoice_name'],
                'date' => $inv['invoice_date'],
                'customer' => $inv['customer_name'],
                'customer_phone' => $inv['customer_phone'],
                'customer_email' => $inv['customer_email'],
                'amount' => $total,
                'amount_formatted' => '฿' . number_format($total, 2),
                'outstanding' => $outstanding,
                'outstanding_formatted' => '฿' . number_format($outstanding, 2),
                'days_overdue' => intval($inv['days_overdue']),
            ];
        }
        
        return [
            'count' => count($formattedInvoices),
            'total_overdue' => $totalOverdue,
            'total_overdue_formatted' => '฿' . number_format($totalOverdue, 2),
            'invoices' => $formattedInvoices,
        ];
    }
    
    private function getPaymentStatus(array $params): array
    {
        // Simplified - just return invoice status
        if (!empty($params['invoice_id'])) {
            return $this->getInvoiceDetails(['invoice_id' => $params['invoice_id']]);
        }
        
        throw new Exception("Invoice ID required");
    }
    
    private function searchProducts(array $params): array
    {
        // Product table in this schema is related to PO line items
        $sql = "SELECT p.pro_id, p.des, p.price, p.quantity, p.model
                FROM product p
                JOIN po ON p.po_id = po.id
                JOIN pr ON po.ref = pr.id
                WHERE pr.ven_id = ?";
        
        $bindings = [$this->companyId];
        
        if (!empty($params['name'])) {
            $sql .= " AND p.des LIKE ?";
            $bindings[] = '%' . $params['name'] . '%';
        }
        
        $sql .= " ORDER BY p.des ASC";
        
        $limit = min($params['limit'] ?? 10, 50);
        $sql .= " LIMIT " . intval($limit);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'count' => count($products),
            'products' => $products,
        ];
    }
    
    // =========================================================
    // WRITE Operation Implementations
    // =========================================================
    
    private function markInvoicePaid(array $params): array
    {
        $invoiceId = $params['invoice_id'] ?? $params['po_id'] ?? null;
        
        if (!$invoiceId) {
            throw new Exception("Invoice ID required");
        }
        
        // Verify invoice exists and belongs to company (using correct schema)
        $checkSql = "SELECT 
                        iv.tex as invoice_id,
                        po.id as po_id,
                        po.name as invoice_name,
                        iv.taxrw as invoice_number,
                        COALESCE(prod.total_amount, 0) as total_amount
                     FROM iv
                     JOIN po ON iv.tex = po.id
                     JOIN pr ON po.ref = pr.id
                     LEFT JOIN (SELECT po_id, SUM(price * quantity) as total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
                     WHERE (iv.tex = ? OR po.id = ?)
                     AND pr.ven_id = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([$invoiceId, $invoiceId, $this->companyId]);
        $invoice = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invoice) {
            throw new Exception("Invoice not found or access denied");
        }
        
        // Update invoice payment status
        $updateSql = "UPDATE iv SET payment_status = 'paid', paid_date = NOW() WHERE tex = ?";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->execute([$invoice['invoice_id']]);
        
        return [
            'message' => "Invoice {$invoice['invoice_number']} ถูกทำเครื่องหมายว่าชำระแล้ว",
            'invoice_id' => $invoice['invoice_id'],
            'po_id' => $invoice['po_id'],
            'invoice_number' => $invoice['invoice_number'] ?: $invoice['invoice_name'],
            'amount' => floatval($invoice['total_amount']),
            'payment_ref' => $params['payment_ref'] ?? null,
            'payment_date' => $params['payment_date'] ?? date('Y-m-d'),
        ];
    }
    
    private function updateInvoiceStatus(array $params): array
    {
        $invoiceId = $params['invoice_id'] ?? $params['po_id'] ?? null;
        $status = $params['status'] ?? null;
        
        if (!$invoiceId || !$status) {
            throw new Exception("Invoice ID and status required");
        }
        
        // Map status to database value
        $statusMap = [
            'pending' => 'pending',
            'partial' => 'partial',
            'paid' => 'paid',
        ];
        
        $dbStatus = $statusMap[$status] ?? 'pending';
        
        $updateSql = "UPDATE iv SET payment_status = ? WHERE tex = ?";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->execute([$dbStatus, $invoiceId]);
        
        return [
            'message' => "สถานะใบแจ้งหนี้อัปเดตเป็น: $status",
            'invoice_id' => $invoiceId,
            'new_status' => $status,
        ];
    }
    
    private function updatePoStatus(array $params): array
    {
        $poId = $params['po_id'] ?? null;
        $status = $params['status'] ?? null;
        
        if (!$poId || !$status) {
            throw new Exception("PO ID and status required");
        }
        
        $updateSql = "UPDATE po SET status = ?, updated_at = NOW() WHERE po_id = ? AND company_id = ?";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->execute([$status, $poId, $this->companyId]);
        
        return [
            'message' => "PO status updated to: $status",
            'po_id' => $poId,
            'new_status' => $status,
        ];
    }
    
    private function addNote(array $params): array
    {
        // Simplified - add to notes column
        $entityType = $params['entity_type'] ?? null;
        $entityId = $params['entity_id'] ?? null;
        $note = $params['note'] ?? null;
        
        if (!$entityType || !$entityId || !$note) {
            throw new Exception("Entity type, ID, and note required");
        }
        
        $tableMap = [
            'invoice' => ['table' => 'iv', 'id' => 'iv_id', 'notes' => 'notes'],
            'po' => ['table' => 'po', 'id' => 'po_id', 'notes' => 'notes'],
            'quotation' => ['table' => 'pr', 'id' => 'pr_id', 'notes' => 'notes'],
            'customer' => ['table' => 'company', 'id' => 'com_id', 'notes' => 'notes'],
        ];
        
        if (!isset($tableMap[$entityType])) {
            throw new Exception("Invalid entity type");
        }
        
        $config = $tableMap[$entityType];
        $timestamp = date('Y-m-d H:i:s');
        $userName = $_SESSION['user_name'] ?? 'AI Agent';
        $noteWithMeta = "[$timestamp - $userName via AI] $note\n";
        
        $sql = "UPDATE {$config['table']} 
                SET {$config['notes']} = CONCAT(COALESCE({$config['notes']}, ''), ?) 
                WHERE {$config['id']} = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$noteWithMeta, $entityId]);
        
        return [
            'message' => "Note added to $entityType #$entityId",
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ];
    }
    
    private function updateCustomerContact(array $params): array
    {
        $customerId = $params['customer_id'] ?? null;
        
        if (!$customerId) {
            throw new Exception("Customer ID required");
        }
        
        $updates = [];
        $bindings = [];
        
        if (!empty($params['email'])) {
            $updates[] = "com_email = ?";
            $bindings[] = $params['email'];
        }
        
        if (!empty($params['phone'])) {
            $updates[] = "com_tel = ?";
            $bindings[] = $params['phone'];
        }
        
        if (!empty($params['contact_person'])) {
            $updates[] = "contact_person = ?";
            $bindings[] = $params['contact_person'];
        }
        
        if (empty($updates)) {
            throw new Exception("No updates provided");
        }
        
        $bindings[] = $customerId;
        
        $sql = "UPDATE company SET " . implode(', ', $updates) . " WHERE com_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        
        return [
            'message' => "Customer contact updated",
            'customer_id' => $customerId,
            'updates' => $params,
        ];
    }
    
    private function recordPayment(array $params): array
    {
        // Simplified payment recording
        return [
            'message' => "Payment recording requires manual entry for accuracy",
            'suggestion' => "Please use the Payments module to record this payment",
            'amount' => $params['amount'] ?? 0,
        ];
    }
    
    // =========================================================
    // Utility Operations
    // =========================================================
    
    private function calculateTotals(array $params): array
    {
        $subtotal = floatval($params['subtotal'] ?? 0);
        $discountPercent = floatval($params['discount_percent'] ?? 0);
        $discountAmount = floatval($params['discount_amount'] ?? 0);
        $includeVat = $params['include_vat'] ?? false;
        
        // Apply discount
        if ($discountPercent > 0) {
            $discountAmount = $subtotal * ($discountPercent / 100);
        }
        
        $afterDiscount = $subtotal - $discountAmount;
        
        // Apply VAT (7% for Thailand)
        $vatAmount = $includeVat ? ($afterDiscount * 0.07) : 0;
        $total = $afterDiscount + $vatAmount;
        
        return [
            'subtotal' => $subtotal,
            'discount' => $discountAmount,
            'after_discount' => $afterDiscount,
            'vat_rate' => $includeVat ? 7 : 0,
            'vat_amount' => $vatAmount,
            'total' => $total,
            'total_formatted' => '฿' . number_format($total, 2),
        ];
    }
    
    private function formatCurrency(array $params): array
    {
        $amount = floatval($params['amount'] ?? 0);
        
        return [
            'amount' => $amount,
            'formatted' => '฿' . number_format($amount, 2),
            'words' => $this->numberToThaiWords($amount),
        ];
    }
    
    // =========================================================
    // Helper Methods
    // =========================================================
    
    private function getDateRange(string $period): array
    {
        $today = date('Y-m-d');
        
        switch ($period) {
            case 'today':
                return ['start' => $today, 'end' => $today];
            case 'yesterday':
                $yesterday = date('Y-m-d', strtotime('-1 day'));
                return ['start' => $yesterday, 'end' => $yesterday];
            case 'this_week':
                return [
                    'start' => date('Y-m-d', strtotime('monday this week')),
                    'end' => date('Y-m-d', strtotime('sunday this week')),
                ];
            case 'last_week':
                return [
                    'start' => date('Y-m-d', strtotime('monday last week')),
                    'end' => date('Y-m-d', strtotime('sunday last week')),
                ];
            case 'this_month':
                return [
                    'start' => date('Y-m-01'),
                    'end' => date('Y-m-t'),
                ];
            case 'last_month':
                return [
                    'start' => date('Y-m-01', strtotime('first day of last month')),
                    'end' => date('Y-m-t', strtotime('last day of last month')),
                ];
            case 'this_year':
                return [
                    'start' => date('Y-01-01'),
                    'end' => date('Y-12-31'),
                ];
            default:
                return ['start' => date('Y-m-01'), 'end' => $today];
        }
    }
    
    private function numberToThaiWords(float $number): string
    {
        // Simplified - return formatted string
        return number_format($number, 2) . ' บาท';
    }
    
    private function requireConfirmation(string $toolName, array $params, array $tool): array
    {
        // Log pending action
        $logId = $this->logAction($toolName, $params, 'pending');
        
        return [
            'success' => false,
            'requires_confirmation' => true,
            'confirmation_id' => $logId,
            'tool' => $toolName,
            'params' => $params,
            'message' => "This action requires confirmation. Please confirm to proceed.",
        ];
    }
    
    private function logAction(string $action, array $params, string $status): int
    {
        $sql = "INSERT INTO ai_action_log 
                (company_id, user_id, session_id, action_type, action_params, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $this->companyId,
            $this->userId,
            $this->sessionId,
            $action,
            json_encode($params),
            $status,
        ]);
        
        return (int) $this->db->lastInsertId();
    }
    
    private function updateActionLog(int $logId, string $status, $result = null, ?string $error = null): void
    {
        $sql = "UPDATE ai_action_log 
                SET status = ?, result = ?, error_message = ?, executed_at = NOW()
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $status,
            $result ? json_encode($result) : null,
            $error,
            $logId,
        ]);
    }
    
    private function error(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
        ];
    }
    
    /**
     * Confirm a pending action
     * 
     * @param int $confirmationId The action log ID
     * @return array Result
     */
    public function confirmAction(int $confirmationId): array
    {
        // Get pending action
        $sql = "SELECT * FROM ai_action_log 
                WHERE id = ? AND company_id = ? AND user_id = ? AND status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$confirmationId, $this->companyId, $this->userId]);
        $action = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$action) {
            return $this->error("Pending action not found or already processed");
        }
        
        // Update to confirmed
        $updateSql = "UPDATE ai_action_log SET status = 'confirmed', confirmed_at = NOW() WHERE id = ?";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->execute([$confirmationId]);
        
        // Execute the action
        $params = json_decode($action['action_params'], true);
        return $this->execute($action['action_type'], $params, true);
    }
    
    /**
     * Cancel a pending action
     * 
     * @param int $confirmationId The action log ID
     * @return array Result
     */
    public function cancelAction(int $confirmationId): array
    {
        $sql = "UPDATE ai_action_log 
                SET status = 'cancelled' 
                WHERE id = ? AND company_id = ? AND user_id = ? AND status = 'pending'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$confirmationId, $this->companyId, $this->userId]);
        
        if ($stmt->rowCount() === 0) {
            return $this->error("Pending action not found");
        }
        
        return [
            'success' => true,
            'message' => 'Action cancelled',
        ];
    }
    
    // =========================================================
    // Schema Discovery Operations
    // =========================================================
    
    /**
     * List all database tables with row counts
     */
    private function listDatabaseTables(array $params): array
    {
        $sql = "SELECT 
                    TABLE_NAME as table_name,
                    TABLE_ROWS as row_count,
                    TABLE_COMMENT as comment,
                    CREATE_TIME as created_at,
                    UPDATE_TIME as updated_at
                FROM information_schema.TABLES 
                WHERE TABLE_SCHEMA = DATABASE() 
                ORDER BY TABLE_NAME";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Categorize tables
        $categories = [
            'Core Business' => ['iv', 'po', 'pr', 'product', 'pay', 'company', 'deliv'],
            'Products & Categories' => ['cate', 'model', 'type', 'brand'],
            'User & Auth' => ['users', 'user_sessions', 'user_permissions'],
            'AI System' => ['ai_chat_history', 'ai_action_log', 'ai_settings'],
            'System' => ['migrations', 'settings', 'audit_log'],
        ];
        
        $categorized = [];
        foreach ($tables as $table) {
            $name = $table['table_name'];
            $found = false;
            foreach ($categories as $cat => $tableList) {
                if (in_array($name, $tableList)) {
                    $categorized[$cat][] = $table;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $categorized['Other'][] = $table;
            }
        }
        
        return [
            'total_tables' => count($tables),
            'tables' => $tables,
            'categorized' => $categorized,
        ];
    }
    
    /**
     * Describe a specific table
     */
    private function describeTable(array $params): array
    {
        $tableName = $params['table_name'] ?? '';
        if (empty($tableName)) {
            throw new Exception("table_name is required");
        }
        
        // Validate table exists
        $checkSql = "SELECT TABLE_NAME FROM information_schema.TABLES 
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([$tableName]);
        if (!$checkStmt->fetch()) {
            throw new Exception("Table not found: $tableName");
        }
        
        // Get columns
        $colSql = "SELECT 
                    COLUMN_NAME as name,
                    DATA_TYPE as type,
                    COLUMN_TYPE as full_type,
                    IS_NULLABLE as nullable,
                    COLUMN_KEY as key_type,
                    COLUMN_DEFAULT as default_value,
                    EXTRA as extra,
                    COLUMN_COMMENT as comment
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                ORDER BY ORDINAL_POSITION";
        $colStmt = $this->db->prepare($colSql);
        $colStmt->execute([$tableName]);
        $columns = $colStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get indexes
        $idxSql = "SHOW INDEX FROM `$tableName`";
        $idxStmt = $this->db->query($idxSql);
        $indexes = $idxStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get foreign keys
        $fkSql = "SELECT 
                    COLUMN_NAME as column_name,
                    REFERENCED_TABLE_NAME as ref_table,
                    REFERENCED_COLUMN_NAME as ref_column
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = ? 
                AND REFERENCED_TABLE_NAME IS NOT NULL";
        $fkStmt = $this->db->prepare($fkSql);
        $fkStmt->execute([$tableName]);
        $foreignKeys = $fkStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get sample data
        $sample = [];
        $includeSample = $params['include_sample'] ?? true;
        if ($includeSample) {
            $sampleSql = "SELECT * FROM `$tableName` LIMIT 3";
            $sampleStmt = $this->db->query($sampleSql);
            $sample = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Get row count
        $countSql = "SELECT COUNT(*) as cnt FROM `$tableName`";
        $countStmt = $this->db->query($countSql);
        $rowCount = $countStmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        
        return [
            'table_name' => $tableName,
            'row_count' => (int)$rowCount,
            'columns' => $columns,
            'indexes' => $indexes,
            'foreign_keys' => $foreignKeys,
            'sample_data' => $sample,
        ];
    }
    
    /**
     * Search for tables or columns matching a pattern
     */
    private function searchSchema(array $params): array
    {
        $pattern = $params['pattern'] ?? '';
        if (empty($pattern)) {
            throw new Exception("pattern is required");
        }
        
        $likePattern = '%' . $pattern . '%';
        
        // Search tables
        $tableSql = "SELECT TABLE_NAME as table_name, TABLE_COMMENT as comment
                     FROM information_schema.TABLES 
                     WHERE TABLE_SCHEMA = DATABASE() 
                     AND TABLE_NAME LIKE ?";
        $tableStmt = $this->db->prepare($tableSql);
        $tableStmt->execute([$likePattern]);
        $tables = $tableStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Search columns
        $colSql = "SELECT TABLE_NAME as table_name, COLUMN_NAME as column_name, 
                   DATA_TYPE as type, COLUMN_COMMENT as comment
                   FROM information_schema.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND COLUMN_NAME LIKE ?
                   ORDER BY TABLE_NAME, COLUMN_NAME";
        $colStmt = $this->db->prepare($colSql);
        $colStmt->execute([$likePattern]);
        $columns = $colStmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'pattern' => $pattern,
            'matching_tables' => $tables,
            'matching_columns' => $columns,
            'total_matches' => count($tables) + count($columns),
        ];
    }
    
    /**
     * Get foreign key relationships
     */
    private function getTableRelationships(array $params): array
    {
        $tableName = $params['table_name'] ?? null;
        
        $sql = "SELECT 
                    TABLE_NAME as from_table,
                    COLUMN_NAME as from_column,
                    REFERENCED_TABLE_NAME as to_table,
                    REFERENCED_COLUMN_NAME as to_column,
                    CONSTRAINT_NAME as constraint_name
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND REFERENCED_TABLE_NAME IS NOT NULL";
        
        $bindings = [];
        if ($tableName) {
            $sql .= " AND (TABLE_NAME = ? OR REFERENCED_TABLE_NAME = ?)";
            $bindings = [$tableName, $tableName];
        }
        
        $sql .= " ORDER BY TABLE_NAME, COLUMN_NAME";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        $relationships = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Build a relationship map
        $map = [];
        foreach ($relationships as $rel) {
            $key = $rel['from_table'];
            if (!isset($map[$key])) {
                $map[$key] = [];
            }
            $map[$key][] = [
                'column' => $rel['from_column'],
                'references' => $rel['to_table'] . '.' . $rel['to_column'],
            ];
        }
        
        return [
            'table_filter' => $tableName,
            'relationships' => $relationships,
            'relationship_map' => $map,
            'summary' => $this->buildRelationshipSummary($relationships),
        ];
    }
    
    /**
     * Build a human-readable relationship summary
     */
    private function buildRelationshipSummary(array $relationships): string
    {
        if (empty($relationships)) {
            return "No foreign key relationships found.";
        }
        
        $summary = "Database Relationships:\n";
        foreach ($relationships as $rel) {
            $summary .= "  {$rel['from_table']}.{$rel['from_column']} → {$rel['to_table']}.{$rel['to_column']}\n";
        }
        return $summary;
    }
    
    /**
     * Get a comprehensive database summary
     */
    private function getDatabaseSummary(array $params): array
    {
        // Try to load cached summary first
        require_once __DIR__ . '/schema-discovery.php';
        $cached = SchemaDiscovery::loadCompactSchema();
        
        if ($cached) {
            return [
                'source' => 'cache',
                'summary' => $cached,
                'note' => 'This is a cached schema summary. Use list_database_tables or describe_table for live data.',
            ];
        }
        
        // Generate summary on the fly
        $tables = $this->listDatabaseTables([]);
        $relationships = $this->getTableRelationships([]);
        
        // Key tables for iACC system
        $keyTables = [
            'iv' => 'Invoices - linked to po via tex field',
            'po' => 'Purchase Orders - main transaction table',
            'pr' => 'Projects/Proposals - links customers (cus_id) and vendors (ven_id)',
            'product' => 'Line items - price and quantity per po_id',
            'pay' => 'Payments - volumn field contains amount',
            'company' => 'Companies - customers and vendors',
            'deliv' => 'Deliveries',
        ];
        
        // Build summary
        $summary = "iACC Database Schema Summary\n";
        $summary .= "============================\n\n";
        $summary .= "Total Tables: {$tables['total_tables']}\n\n";
        
        $summary .= "Key Business Tables:\n";
        foreach ($keyTables as $table => $desc) {
            $summary .= "  - $table: $desc\n";
        }
        
        $summary .= "\n" . $relationships['summary'];
        
        $summary .= "\nCommon Query Pattern:\n";
        $summary .= "  iv → po → pr → company (for invoices with customer info)\n";
        $summary .= "  product joined by po_id (for line items/totals)\n";
        $summary .= "  pay joined by po_id (for payment tracking)\n";
        
        return [
            'source' => 'live',
            'total_tables' => $tables['total_tables'],
            'key_tables' => $keyTables,
            'relationships' => $relationships['relationships'],
            'summary' => $summary,
        ];
    }
    
    // =========================================================
    // REPORT & ANALYTICS Operation Implementations
    // =========================================================
    
    private function getSalesReport(array $params): array
    {
        $dateFrom = $params['date_from'] ?? date('Y-m-01');
        $dateTo = $params['date_to'] ?? date('Y-m-d');
        $groupBy = $params['group_by'] ?? 'month';
        
        // Total revenue and invoice count
        $sql = "SELECT 
                    COUNT(DISTINCT iv.tex) as invoice_count,
                    COALESCE(SUM(prod.total_amount), 0) as total_revenue,
                    COALESCE(SUM(paid.paid_amount), 0) as total_collected
                FROM iv
                JOIN po ON iv.tex = po.id
                JOIN pr ON po.ref = pr.id
                LEFT JOIN (SELECT po_id, SUM(price * quantity) as total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
                LEFT JOIN (SELECT po_id, SUM(volumn) as paid_amount FROM pay WHERE deleted_at IS NULL GROUP BY po_id) paid ON po.id = paid.po_id
                WHERE iv.deleted_at IS NULL 
                AND pr.ven_id = ?
                AND iv.createdate BETWEEN ? AND ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->companyId, $dateFrom, $dateTo]);
        $totals = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Top customers
        $sqlCustomers = "SELECT 
                    c.name_en as customer_name,
                    c.id as customer_id,
                    COUNT(DISTINCT iv.tex) as invoice_count,
                    COALESCE(SUM(prod.total_amount), 0) as total_revenue
                FROM iv
                JOIN po ON iv.tex = po.id
                JOIN pr ON po.ref = pr.id
                LEFT JOIN company c ON pr.cus_id = c.id
                LEFT JOIN (SELECT po_id, SUM(price * quantity) as total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
                WHERE iv.deleted_at IS NULL 
                AND pr.ven_id = ?
                AND iv.createdate BETWEEN ? AND ?
                GROUP BY c.id, c.name_en
                ORDER BY total_revenue DESC
                LIMIT 5";
        
        $stmt = $this->db->prepare($sqlCustomers);
        $stmt->execute([$this->companyId, $dateFrom, $dateTo]);
        $topCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Monthly breakdown
        $sqlMonthly = "SELECT 
                    DATE_FORMAT(iv.createdate, '%Y-%m') as month,
                    COUNT(DISTINCT iv.tex) as invoice_count,
                    COALESCE(SUM(prod.total_amount), 0) as revenue
                FROM iv
                JOIN po ON iv.tex = po.id
                JOIN pr ON po.ref = pr.id
                LEFT JOIN (SELECT po_id, SUM(price * quantity) as total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
                WHERE iv.deleted_at IS NULL 
                AND pr.ven_id = ?
                AND iv.createdate BETWEEN ? AND ?
                GROUP BY DATE_FORMAT(iv.createdate, '%Y-%m')
                ORDER BY month ASC";
        
        $stmt = $this->db->prepare($sqlMonthly);
        $stmt->execute([$this->companyId, $dateFrom, $dateTo]);
        $monthlyBreakdown = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'total_revenue' => (float)$totals['total_revenue'],
            'total_collected' => (float)$totals['total_collected'],
            'collection_rate' => $totals['total_revenue'] > 0 
                ? round(($totals['total_collected'] / $totals['total_revenue']) * 100, 1) 
                : 0,
            'invoice_count' => (int)$totals['invoice_count'],
            'top_customers' => $topCustomers,
            'monthly_breakdown' => $monthlyBreakdown,
        ];
    }
    
    private function getRevenueTrend(array $params): array
    {
        $months = $params['months'] ?? 12;
        $comparePrevious = $params['compare_previous'] ?? true;
        
        $endDate = date('Y-m-d');
        $startDate = date('Y-m-d', strtotime("-{$months} months"));
        
        // Monthly revenue
        $sql = "SELECT 
                    DATE_FORMAT(iv.createdate, '%Y-%m') as month,
                    COALESCE(SUM(prod.total_amount), 0) as revenue
                FROM iv
                JOIN po ON iv.tex = po.id
                JOIN pr ON po.ref = pr.id
                LEFT JOIN (SELECT po_id, SUM(price * quantity) as total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
                WHERE iv.deleted_at IS NULL 
                AND pr.ven_id = ?
                AND iv.createdate >= ?
                GROUP BY DATE_FORMAT(iv.createdate, '%Y-%m')
                ORDER BY month ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->companyId, $startDate]);
        $monthlyData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate trends
        $revenues = array_column($monthlyData, 'revenue');
        $totalRevenue = array_sum($revenues);
        $avgMonthly = count($revenues) > 0 ? $totalRevenue / count($revenues) : 0;
        
        // Growth calculation
        $growth = null;
        if (count($revenues) >= 2) {
            $firstHalf = array_sum(array_slice($revenues, 0, (int)(count($revenues) / 2)));
            $secondHalf = array_sum(array_slice($revenues, (int)(count($revenues) / 2)));
            if ($firstHalf > 0) {
                $growth = round((($secondHalf - $firstHalf) / $firstHalf) * 100, 1);
            }
        }
        
        return [
            'period' => ['from' => $startDate, 'to' => $endDate],
            'months_analyzed' => $months,
            'total_revenue' => $totalRevenue,
            'average_monthly' => round($avgMonthly, 2),
            'growth_percent' => $growth,
            'monthly_data' => $monthlyData,
            'trend' => $growth > 0 ? 'increasing' : ($growth < 0 ? 'decreasing' : 'stable'),
        ];
    }
    
    private function getCustomerAnalysis(array $params): array
    {
        $topCount = $params['top_count'] ?? 10;
        $dateFrom = $params['date_from'] ?? date('Y-01-01');
        $dateTo = $params['date_to'] ?? date('Y-m-d');
        
        $sql = "SELECT 
                    c.id as customer_id,
                    c.name_en as customer_name,
                    COUNT(DISTINCT iv.tex) as invoice_count,
                    COUNT(DISTINCT po.id) as order_count,
                    COALESCE(SUM(prod.total_amount), 0) as total_revenue,
                    COALESCE(AVG(prod.total_amount), 0) as avg_order_value,
                    MIN(iv.createdate) as first_invoice,
                    MAX(iv.createdate) as last_invoice
                FROM company c
                JOIN pr ON c.id = pr.cus_id
                JOIN po ON po.ref = pr.id
                LEFT JOIN iv ON iv.tex = po.id AND iv.deleted_at IS NULL
                LEFT JOIN (SELECT po_id, SUM(price * quantity) as total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
                WHERE pr.ven_id = ?
                AND (iv.createdate IS NULL OR iv.createdate BETWEEN ? AND ?)
                GROUP BY c.id, c.name_en
                ORDER BY total_revenue DESC
                LIMIT " . intval($topCount);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->companyId, $dateFrom, $dateTo]);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate total for percentage
        $totalRevenue = array_sum(array_column($customers, 'total_revenue'));
        
        // Add revenue percentage
        foreach ($customers as &$customer) {
            $customer['revenue_percent'] = $totalRevenue > 0 
                ? round(($customer['total_revenue'] / $totalRevenue) * 100, 1) 
                : 0;
        }
        
        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'top_customers' => $customers,
            'total_revenue' => $totalRevenue,
        ];
    }
    
    private function getAgingReport(array $params): array
    {
        $customerId = $params['customer_id'] ?? null;
        
        $sql = "SELECT 
                    iv.tex as invoice_id,
                    po.name as invoice_name,
                    iv.taxrw as invoice_number,
                    iv.createdate as invoice_date,
                    c.name_en as customer_name,
                    c.id as customer_id,
                    COALESCE(prod.total_amount, 0) as total_amount,
                    COALESCE(paid.paid_amount, 0) as paid_amount,
                    COALESCE(prod.total_amount, 0) - COALESCE(paid.paid_amount, 0) as outstanding,
                    DATEDIFF(CURDATE(), iv.createdate) as days_old,
                    CASE 
                        WHEN DATEDIFF(CURDATE(), iv.createdate) <= 30 THEN 'current'
                        WHEN DATEDIFF(CURDATE(), iv.createdate) <= 60 THEN '31-60 days'
                        WHEN DATEDIFF(CURDATE(), iv.createdate) <= 90 THEN '61-90 days'
                        ELSE '90+ days'
                    END as aging_bucket
                FROM iv
                JOIN po ON iv.tex = po.id
                JOIN pr ON po.ref = pr.id
                LEFT JOIN company c ON pr.cus_id = c.id
                LEFT JOIN (SELECT po_id, SUM(price * quantity) as total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
                LEFT JOIN (SELECT po_id, SUM(volumn) as paid_amount FROM pay WHERE deleted_at IS NULL GROUP BY po_id) paid ON po.id = paid.po_id
                WHERE iv.deleted_at IS NULL 
                AND pr.ven_id = ?
                AND (COALESCE(prod.total_amount, 0) - COALESCE(paid.paid_amount, 0)) > 0";
        
        $bindings = [$this->companyId];
        
        if ($customerId) {
            $sql .= " AND c.id = ?";
            $bindings[] = $customerId;
        }
        
        $sql .= " ORDER BY days_old DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Summary by bucket
        $buckets = ['current' => 0, '31-60 days' => 0, '61-90 days' => 0, '90+ days' => 0];
        foreach ($invoices as $inv) {
            $buckets[$inv['aging_bucket']] += (float)$inv['outstanding'];
        }
        
        $totalOutstanding = array_sum($buckets);
        
        return [
            'total_outstanding' => $totalOutstanding,
            'invoice_count' => count($invoices),
            'aging_summary' => $buckets,
            'invoices' => array_slice($invoices, 0, 20), // Limit for response size
        ];
    }
    
    private function getPaymentSummary(array $params): array
    {
        $dateFrom = $params['date_from'] ?? date('Y-01-01');
        $dateTo = $params['date_to'] ?? date('Y-m-d');
        
        // Total invoiced vs collected
        $sql = "SELECT 
                    COALESCE(SUM(prod.total_amount), 0) as total_invoiced,
                    COALESCE(SUM(paid.paid_amount), 0) as total_collected
                FROM iv
                JOIN po ON iv.tex = po.id
                JOIN pr ON po.ref = pr.id
                LEFT JOIN (SELECT po_id, SUM(price * quantity) as total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
                LEFT JOIN (SELECT po_id, SUM(volumn) as paid_amount FROM pay WHERE deleted_at IS NULL GROUP BY po_id) paid ON po.id = paid.po_id
                WHERE iv.deleted_at IS NULL 
                AND pr.ven_id = ?
                AND iv.createdate BETWEEN ? AND ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->companyId, $dateFrom, $dateTo]);
        $totals = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Payment by method (if available)
        $sqlPayments = "SELECT 
                    p.method as payment_method,
                    COUNT(*) as payment_count,
                    SUM(p.volumn) as total_amount
                FROM pay p
                JOIN po ON p.po_id = po.id
                JOIN pr ON po.ref = pr.id
                WHERE p.deleted_at IS NULL 
                AND pr.ven_id = ?
                AND p.date BETWEEN ? AND ?
                GROUP BY p.method";
        
        $stmt = $this->db->prepare($sqlPayments);
        $stmt->execute([$this->companyId, $dateFrom, $dateTo]);
        $byMethod = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totalInvoiced = (float)$totals['total_invoiced'];
        $totalCollected = (float)$totals['total_collected'];
        
        return [
            'period' => ['from' => $dateFrom, 'to' => $dateTo],
            'total_invoiced' => $totalInvoiced,
            'total_collected' => $totalCollected,
            'outstanding' => $totalInvoiced - $totalCollected,
            'collection_rate' => $totalInvoiced > 0 
                ? round(($totalCollected / $totalInvoiced) * 100, 1) 
                : 0,
            'by_method' => $byMethod,
        ];
    }
    
    private function exportData(array $params): array
    {
        $dataType = $params['data_type'] ?? 'invoices';
        $dateFrom = $params['date_from'] ?? date('Y-01-01');
        $dateTo = $params['date_to'] ?? date('Y-m-d');
        $format = $params['format'] ?? 'csv';
        
        // Build query based on type
        switch ($dataType) {
            case 'invoices':
                $sql = "SELECT 
                        iv.taxrw as invoice_number,
                        po.name as description,
                        DATE_FORMAT(iv.createdate, '%Y-%m-%d') as invoice_date,
                        c.name_en as customer,
                        COALESCE(prod.total_amount, 0) as amount,
                        COALESCE(paid.paid_amount, 0) as paid,
                        iv.payment_status
                    FROM iv
                    JOIN po ON iv.tex = po.id
                    JOIN pr ON po.ref = pr.id
                    LEFT JOIN company c ON pr.cus_id = c.id
                    LEFT JOIN (SELECT po_id, SUM(price * quantity) as total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
                    LEFT JOIN (SELECT po_id, SUM(volumn) as paid_amount FROM pay WHERE deleted_at IS NULL GROUP BY po_id) paid ON po.id = paid.po_id
                    WHERE iv.deleted_at IS NULL 
                    AND pr.ven_id = ?
                    AND iv.createdate BETWEEN ? AND ?
                    ORDER BY iv.createdate DESC";
                break;
                
            case 'customers':
                $sql = "SELECT 
                        name_en as name,
                        name_sh as short_name,
                        email,
                        phone,
                        fax,
                        contact
                    FROM company 
                    WHERE deleted_at IS NULL
                    AND (customer = 1 OR vender = 1)
                    ORDER BY name_en";
                $dateFrom = null;
                $dateTo = null;
                break;
                
            case 'payments':
                $sql = "SELECT 
                        p.date as date,
                        p.volumn as amount,
                        p.method as method,
                        po.name as invoice,
                        c.name_en as customer
                    FROM pay p
                    JOIN po ON p.po_id = po.id
                    JOIN pr ON po.ref = pr.id
                    LEFT JOIN company c ON pr.cus_id = c.id
                    WHERE p.deleted_at IS NULL 
                    AND pr.ven_id = ?
                    AND p.date BETWEEN ? AND ?
                    ORDER BY p.date DESC";
                break;
                
            default:
                throw new Exception("Unknown data type: $dataType");
        }
        
        $bindings = [$this->companyId];
        if ($dateFrom && $dateTo) {
            $bindings[] = $dateFrom;
            $bindings[] = $dateTo;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(count($bindings) > 1 ? $bindings : [$this->companyId]);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Generate filename
        $filename = "{$dataType}_export_" . date('Ymd_His') . ".{$format}";
        $filepath = "/var/www/html/upload/exports/{$filename}";
        
        // Ensure directory exists
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }
        
        // Write file
        if ($format === 'csv' && count($data) > 0) {
            $fp = fopen($filepath, 'w');
            fputcsv($fp, array_keys($data[0])); // Header
            foreach ($data as $row) {
                fputcsv($fp, $row);
            }
            fclose($fp);
        } else {
            file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT));
        }
        
        return [
            'data_type' => $dataType,
            'record_count' => count($data),
            'format' => $format,
            'filename' => $filename,
            'download_url' => "/upload/exports/{$filename}",
            'message' => "Exported {$dataType} data ({count($data)} records) to {$filename}",
        ];
    }
}
