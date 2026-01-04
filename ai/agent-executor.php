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
                
            default:
                throw new Exception("Tool not implemented: $toolName");
        }
    }
    
    // =========================================================
    // READ Operation Implementations
    // =========================================================
    
    private function searchInvoices(array $params): array
    {
        $sql = "SELECT iv.iv_id, iv.iv_rw, iv.iv_date, iv.total, iv.status,
                       c.com_name as customer_name
                FROM iv 
                LEFT JOIN company c ON iv.com_id_cus = c.com_id
                WHERE iv.company_id = ?";
        
        $bindings = [$this->companyId];
        
        if (!empty($params['customer'])) {
            $sql .= " AND c.com_name LIKE ?";
            $bindings[] = '%' . $params['customer'] . '%';
        }
        
        if (!empty($params['invoice_number'])) {
            $sql .= " AND iv.iv_rw LIKE ?";
            $bindings[] = '%' . $params['invoice_number'] . '%';
        }
        
        if (!empty($params['status']) && $params['status'] !== 'all') {
            switch ($params['status']) {
                case 'paid':
                    $sql .= " AND iv.status = '1'";
                    break;
                case 'unpaid':
                    $sql .= " AND (iv.status = '0' OR iv.status IS NULL)";
                    break;
                case 'overdue':
                    $sql .= " AND (iv.status = '0' OR iv.status IS NULL) AND iv.iv_date < CURDATE()";
                    break;
            }
        }
        
        if (!empty($params['date_from'])) {
            $sql .= " AND iv.iv_date >= ?";
            $bindings[] = $params['date_from'];
        }
        
        if (!empty($params['date_to'])) {
            $sql .= " AND iv.iv_date <= ?";
            $bindings[] = $params['date_to'];
        }
        
        if (!empty($params['min_amount'])) {
            $sql .= " AND iv.total >= ?";
            $bindings[] = $params['min_amount'];
        }
        
        if (!empty($params['max_amount'])) {
            $sql .= " AND iv.total <= ?";
            $bindings[] = $params['max_amount'];
        }
        
        $sql .= " ORDER BY iv.iv_date DESC";
        
        $limit = min($params['limit'] ?? 10, 50);
        $sql .= " LIMIT " . intval($limit);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'count' => count($invoices),
            'invoices' => array_map(function($inv) {
                return [
                    'id' => $inv['iv_id'],
                    'number' => $inv['iv_rw'],
                    'date' => $inv['iv_date'],
                    'customer' => $inv['customer_name'],
                    'amount' => floatval($inv['total']),
                    'amount_formatted' => '฿' . number_format($inv['total'], 2),
                    'status' => $inv['status'] == '1' ? 'paid' : 'unpaid',
                ];
            }, $invoices),
        ];
    }
    
    private function getInvoiceDetails(array $params): array
    {
        $sql = "SELECT iv.*, c.com_name, c.com_addr, c.com_tel, c.com_email
                FROM iv
                LEFT JOIN company c ON iv.com_id_cus = c.com_id
                WHERE iv.company_id = ?";
        
        $bindings = [$this->companyId];
        
        if (!empty($params['invoice_id'])) {
            $sql .= " AND iv.iv_id = ?";
            $bindings[] = $params['invoice_id'];
        } elseif (!empty($params['invoice_number'])) {
            $sql .= " AND iv.iv_rw = ?";
            $bindings[] = $params['invoice_number'];
        } else {
            throw new Exception("Invoice ID or number required");
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        $invoice = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invoice) {
            throw new Exception("Invoice not found");
        }
        
        return [
            'id' => $invoice['iv_id'],
            'number' => $invoice['iv_rw'],
            'date' => $invoice['iv_date'],
            'due_date' => $invoice['iv_due_date'] ?? null,
            'customer' => [
                'name' => $invoice['com_name'],
                'address' => $invoice['com_addr'],
                'phone' => $invoice['com_tel'],
                'email' => $invoice['com_email'],
            ],
            'subtotal' => floatval($invoice['subtotal'] ?? $invoice['total']),
            'discount' => floatval($invoice['discount'] ?? 0),
            'vat' => floatval($invoice['vat'] ?? 0),
            'total' => floatval($invoice['total']),
            'total_formatted' => '฿' . number_format($invoice['total'], 2),
            'status' => $invoice['status'] == '1' ? 'paid' : 'unpaid',
            'notes' => $invoice['notes'] ?? '',
        ];
    }
    
    private function searchPurchaseOrders(array $params): array
    {
        $sql = "SELECT po.po_id, po.po_rw, po.po_date, po.total, po.status,
                       c.com_name as customer_name
                FROM po 
                LEFT JOIN company c ON po.com_id_cus = c.com_id
                WHERE po.company_id = ?";
        
        $bindings = [$this->companyId];
        
        if (!empty($params['customer'])) {
            $sql .= " AND c.com_name LIKE ?";
            $bindings[] = '%' . $params['customer'] . '%';
        }
        
        if (!empty($params['po_number'])) {
            $sql .= " AND po.po_rw LIKE ?";
            $bindings[] = '%' . $params['po_number'] . '%';
        }
        
        if (!empty($params['date_from'])) {
            $sql .= " AND po.po_date >= ?";
            $bindings[] = $params['date_from'];
        }
        
        if (!empty($params['date_to'])) {
            $sql .= " AND po.po_date <= ?";
            $bindings[] = $params['date_to'];
        }
        
        $sql .= " ORDER BY po.po_date DESC";
        
        $limit = min($params['limit'] ?? 10, 50);
        $sql .= " LIMIT " . intval($limit);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'count' => count($orders),
            'purchase_orders' => array_map(function($po) {
                return [
                    'id' => $po['po_id'],
                    'number' => $po['po_rw'],
                    'date' => $po['po_date'],
                    'customer' => $po['customer_name'],
                    'amount' => floatval($po['total']),
                    'amount_formatted' => '฿' . number_format($po['total'], 2),
                    'status' => $po['status'],
                ];
            }, $orders),
        ];
    }
    
    private function searchQuotations(array $params): array
    {
        $sql = "SELECT pr.pr_id, pr.pr_rw, pr.pr_date, pr.total, pr.status,
                       c.com_name as customer_name
                FROM pr 
                LEFT JOIN company c ON pr.com_id_cus = c.com_id
                WHERE pr.company_id = ?";
        
        $bindings = [$this->companyId];
        
        if (!empty($params['customer'])) {
            $sql .= " AND c.com_name LIKE ?";
            $bindings[] = '%' . $params['customer'] . '%';
        }
        
        if (!empty($params['quote_number'])) {
            $sql .= " AND pr.pr_rw LIKE ?";
            $bindings[] = '%' . $params['quote_number'] . '%';
        }
        
        $sql .= " ORDER BY pr.pr_date DESC";
        
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
                    'number' => $q['pr_rw'],
                    'date' => $q['pr_date'],
                    'customer' => $q['customer_name'],
                    'amount' => floatval($q['total']),
                    'amount_formatted' => '฿' . number_format($q['total'], 2),
                    'status' => $q['status'],
                ];
            }, $quotes),
        ];
    }
    
    private function searchCustomers(array $params): array
    {
        $sql = "SELECT com_id, com_name, com_tel, com_email, com_addr
                FROM company 
                WHERE (com_type = 'C' OR com_type = 'V' OR com_type = 'B')";
        
        $bindings = [];
        
        if (!empty($params['name'])) {
            $sql .= " AND com_name LIKE ?";
            $bindings[] = '%' . $params['name'] . '%';
        }
        
        if (!empty($params['email'])) {
            $sql .= " AND com_email LIKE ?";
            $bindings[] = '%' . $params['email'] . '%';
        }
        
        if (!empty($params['phone'])) {
            $sql .= " AND com_tel LIKE ?";
            $bindings[] = '%' . $params['phone'] . '%';
        }
        
        $sql .= " ORDER BY com_name ASC";
        
        $limit = min($params['limit'] ?? 10, 50);
        $sql .= " LIMIT " . intval($limit);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'count' => count($customers),
            'customers' => array_map(function($c) {
                return [
                    'id' => $c['com_id'],
                    'name' => $c['com_name'],
                    'phone' => $c['com_tel'],
                    'email' => $c['com_email'],
                    'address' => $c['com_addr'],
                ];
            }, $customers),
        ];
    }
    
    private function getCustomerSummary(array $params): array
    {
        // Get customer info
        $sql = "SELECT * FROM company WHERE com_id = ?";
        $stmt = $this->db->prepare($sql);
        
        $customerId = $params['customer_id'] ?? null;
        
        if (!$customerId && !empty($params['customer_name'])) {
            // Find by name
            $findSql = "SELECT com_id FROM company WHERE com_name LIKE ? LIMIT 1";
            $findStmt = $this->db->prepare($findSql);
            $findStmt->execute(['%' . $params['customer_name'] . '%']);
            $found = $findStmt->fetch(PDO::FETCH_ASSOC);
            $customerId = $found['com_id'] ?? null;
        }
        
        if (!$customerId) {
            throw new Exception("Customer not found");
        }
        
        $stmt->execute([$customerId]);
        $customer = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$customer) {
            throw new Exception("Customer not found");
        }
        
        // Get invoice summary
        $invSql = "SELECT 
                      COUNT(*) as total_invoices,
                      SUM(total) as total_amount,
                      SUM(CASE WHEN status = '1' THEN total ELSE 0 END) as paid_amount,
                      SUM(CASE WHEN status != '1' OR status IS NULL THEN total ELSE 0 END) as outstanding
                   FROM iv 
                   WHERE company_id = ? AND com_id_cus = ?";
        $invStmt = $this->db->prepare($invSql);
        $invStmt->execute([$this->companyId, $customerId]);
        $invSummary = $invStmt->fetch(PDO::FETCH_ASSOC);
        
        return [
            'customer' => [
                'id' => $customer['com_id'],
                'name' => $customer['com_name'],
                'phone' => $customer['com_tel'],
                'email' => $customer['com_email'],
                'address' => $customer['com_addr'],
                'credit_limit' => floatval($customer['credit_limit'] ?? 0),
            ],
            'summary' => [
                'total_invoices' => intval($invSummary['total_invoices']),
                'total_amount' => floatval($invSummary['total_amount'] ?? 0),
                'total_amount_formatted' => '฿' . number_format($invSummary['total_amount'] ?? 0, 2),
                'paid_amount' => floatval($invSummary['paid_amount'] ?? 0),
                'outstanding' => floatval($invSummary['outstanding'] ?? 0),
                'outstanding_formatted' => '฿' . number_format($invSummary['outstanding'] ?? 0, 2),
            ],
        ];
    }
    
    private function getDashboardSummary(array $params): array
    {
        $period = $params['period'] ?? 'this_month';
        
        // Calculate date range
        $dateRange = $this->getDateRange($period);
        
        // Invoice summary
        $invSql = "SELECT 
                      COUNT(*) as count,
                      COALESCE(SUM(total), 0) as total,
                      SUM(CASE WHEN status = '1' THEN 1 ELSE 0 END) as paid_count,
                      SUM(CASE WHEN status = '1' THEN total ELSE 0 END) as paid_total
                   FROM iv 
                   WHERE company_id = ? AND iv_date BETWEEN ? AND ?";
        $invStmt = $this->db->prepare($invSql);
        $invStmt->execute([$this->companyId, $dateRange['start'], $dateRange['end']]);
        $invData = $invStmt->fetch(PDO::FETCH_ASSOC);
        
        // PO summary
        $poSql = "SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total
                  FROM po 
                  WHERE company_id = ? AND po_date BETWEEN ? AND ?";
        $poStmt = $this->db->prepare($poSql);
        $poStmt->execute([$this->companyId, $dateRange['start'], $dateRange['end']]);
        $poData = $poStmt->fetch(PDO::FETCH_ASSOC);
        
        // Quotation summary
        $prSql = "SELECT COUNT(*) as count, COALESCE(SUM(total), 0) as total
                  FROM pr 
                  WHERE company_id = ? AND pr_date BETWEEN ? AND ?";
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
                'total' => floatval($prData['total']),
                'total_formatted' => '฿' . number_format($prData['total'], 2),
            ],
        ];
    }
    
    private function getOverdueInvoices(array $params): array
    {
        $daysOverdue = $params['days_overdue'] ?? 1;
        
        $sql = "SELECT iv.iv_id, iv.iv_rw, iv.iv_date, iv.total,
                       c.com_name, c.com_tel, c.com_email,
                       DATEDIFF(CURDATE(), iv.iv_date) as days_overdue
                FROM iv 
                LEFT JOIN company c ON iv.com_id_cus = c.com_id
                WHERE iv.company_id = ?
                  AND (iv.status != '1' OR iv.status IS NULL)
                  AND DATEDIFF(CURDATE(), iv.iv_date) >= ?";
        
        $bindings = [$this->companyId, $daysOverdue];
        
        if (!empty($params['customer_id'])) {
            $sql .= " AND iv.com_id_cus = ?";
            $bindings[] = $params['customer_id'];
        }
        
        $sql .= " ORDER BY days_overdue DESC";
        
        $limit = min($params['limit'] ?? 10, 50);
        $sql .= " LIMIT " . intval($limit);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totalOverdue = array_sum(array_column($invoices, 'total'));
        
        return [
            'count' => count($invoices),
            'total_overdue' => floatval($totalOverdue),
            'total_overdue_formatted' => '฿' . number_format($totalOverdue, 2),
            'invoices' => array_map(function($inv) {
                return [
                    'id' => $inv['iv_id'],
                    'number' => $inv['iv_rw'],
                    'date' => $inv['iv_date'],
                    'days_overdue' => intval($inv['days_overdue']),
                    'customer' => $inv['com_name'],
                    'phone' => $inv['com_tel'],
                    'email' => $inv['com_email'],
                    'amount' => floatval($inv['total']),
                    'amount_formatted' => '฿' . number_format($inv['total'], 2),
                ];
            }, $invoices),
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
        $sql = "SELECT p.*, c.cat_name, b.brand_name
                FROM product p
                LEFT JOIN category c ON p.cat_id = c.id
                LEFT JOIN brand b ON p.brand_id = b.id
                WHERE p.company_id = ?";
        
        $bindings = [$this->companyId];
        
        if (!empty($params['name'])) {
            $sql .= " AND p.pro_name LIKE ?";
            $bindings[] = '%' . $params['name'] . '%';
        }
        
        if (!empty($params['code'])) {
            $sql .= " AND p.pro_code LIKE ?";
            $bindings[] = '%' . $params['code'] . '%';
        }
        
        $sql .= " ORDER BY p.pro_name ASC";
        
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
        $invoiceId = $params['invoice_id'] ?? null;
        
        if (!$invoiceId) {
            throw new Exception("Invoice ID required");
        }
        
        // Verify invoice exists and belongs to company
        $checkSql = "SELECT * FROM iv WHERE iv_id = ? AND company_id = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute([$invoiceId, $this->companyId]);
        $invoice = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$invoice) {
            throw new Exception("Invoice not found or access denied");
        }
        
        // Update invoice
        $updateSql = "UPDATE iv SET status = '1', updated_at = NOW() WHERE iv_id = ? AND company_id = ?";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->execute([$invoiceId, $this->companyId]);
        
        return [
            'message' => "Invoice {$invoice['iv_rw']} marked as paid",
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoice['iv_rw'],
            'amount' => floatval($invoice['total']),
            'payment_ref' => $params['payment_ref'] ?? null,
            'payment_date' => $params['payment_date'] ?? date('Y-m-d'),
        ];
    }
    
    private function updateInvoiceStatus(array $params): array
    {
        $invoiceId = $params['invoice_id'] ?? null;
        $status = $params['status'] ?? null;
        
        if (!$invoiceId || !$status) {
            throw new Exception("Invoice ID and status required");
        }
        
        // Map status to database value
        $statusMap = [
            'draft' => '0',
            'sent' => '0',
            'cancelled' => '2',
            'void' => '2',
        ];
        
        $dbStatus = $statusMap[$status] ?? '0';
        
        $updateSql = "UPDATE iv SET status = ?, updated_at = NOW() WHERE iv_id = ? AND company_id = ?";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->execute([$dbStatus, $invoiceId, $this->companyId]);
        
        return [
            'message' => "Invoice status updated to: $status",
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
}
