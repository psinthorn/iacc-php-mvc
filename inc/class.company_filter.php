<?php
/**
 * Multi-Tenant Helper Class
 * 
 * Provides company-based data isolation for all queries.
 * Ensures users only see data belonging to their selected company.
 * 
 * @version 1.0.0
 * @date 2026-01-04
 * 
 * Usage:
 *   require_once 'inc/class.company_filter.php';
 *   $companyFilter = CompanyFilter::getInstance();
 *   
 *   // Get current company ID
 *   $companyId = $companyFilter->getCompanyId();
 *   
 *   // Add filter to existing WHERE clause
 *   $sql = "SELECT * FROM brand WHERE status = 1 " . $companyFilter->andCompanyFilter('brand');
 *   
 *   // Create WHERE clause with company filter
 *   $sql = "SELECT * FROM category " . $companyFilter->whereCompanyFilter('category');
 *   
 *   // For INSERT queries
 *   $companyId = $companyFilter->getCompanyIdForInsert();
 */

class CompanyFilter {
    private static $instance = null;
    private $companyId = null;
    private $userId = null;
    private $userRole = null;
    private $isAdmin = false;
    
    /**
     * Tables that require company_id filtering
     */
    private static $filteredTables = [
        'brand',
        'category', 
        'type',
        'model',
        'map_type_to_brand',
        'payment_methods',
        'payment_gateway_config',
        'po',
        'iv',
        'product',
        'deliver',
        'pay',
        'pr',
        'voucher',
        'receipt',
        'store',
        'sendoutitem',
        'receive',
        'audit_log'
    ];
    
    /**
     * Tables that are always global (no company filtering)
     */
    private static $globalTables = [
        'company',
        'company_addr', 
        'company_credit',
        'authorize',      // Users can belong to multiple companies
        'countries',
        '_migration_log'
    ];
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Private constructor - loads session data
     */
    private function __construct() {
        $this->loadSessionData();
    }
    
    /**
     * Load company and user data from session
     */
    private function loadSessionData() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Get company ID from session
        $this->companyId = isset($_SESSION['com_id']) && $_SESSION['com_id'] !== '' 
            ? intval($_SESSION['com_id']) 
            : null;
            
        // Get user info
        $this->userId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
        $this->userRole = isset($_SESSION['role_id']) ? intval($_SESSION['role_id']) : null;
        
        // Check if user is admin (role_id = 1 is typically admin)
        $this->isAdmin = $this->userRole === 1;
    }
    
    /**
     * Reload session data (useful after company switch)
     */
    public function reload() {
        $this->loadSessionData();
        return $this;
    }
    
    /**
     * Get the current company ID
     * 
     * @param bool $required If true, throws exception when no company selected
     * @return int|null Company ID or null if not set
     * @throws Exception When required and no company selected
     */
    public function getCompanyId($required = false) {
        if ($required && $this->companyId === null) {
            throw new Exception('No company selected. Please select a company to continue.');
        }
        return $this->companyId;
    }
    
    /**
     * Get company ID for INSERT statements
     * Returns the current company ID or throws if not set
     * 
     * @return int
     * @throws Exception
     */
    public function getCompanyIdForInsert() {
        if ($this->companyId === null) {
            throw new Exception('Cannot insert record: No company selected.');
        }
        return $this->companyId;
    }
    
    /**
     * Check if a company is currently selected
     * 
     * @return bool
     */
    public function hasCompany() {
        return $this->companyId !== null;
    }
    
    /**
     * Get the current user ID
     * 
     * @return int|null
     */
    public function getUserId() {
        return $this->userId;
    }
    
    /**
     * Check if current user is admin
     * 
     * @return bool
     */
    public function isAdmin() {
        return $this->isAdmin;
    }
    
    /**
     * Check if table requires company filtering
     * 
     * @param string $table Table name
     * @return bool
     */
    public function requiresFilter($table) {
        return in_array($table, self::$filteredTables);
    }
    
    /**
     * Check if table is global (no filtering)
     * 
     * @param string $table Table name
     * @return bool
     */
    public function isGlobalTable($table) {
        return in_array($table, self::$globalTables);
    }
    
    /**
     * Generate WHERE clause with company filter
     * 
     * @param string $tableAlias Table name or alias
     * @param string $column Column name (default: company_id)
     * @return string SQL WHERE clause (e.g., "WHERE alias.company_id = 7")
     */
    public function whereCompanyFilter($tableAlias = '', $column = 'company_id') {
        if ($this->companyId === null) {
            return "WHERE 1=1"; // No filtering when no company selected
        }
        
        $prefix = $tableAlias ? "{$tableAlias}." : '';
        return "WHERE {$prefix}{$column} = " . intval($this->companyId);
    }
    
    /**
     * Generate AND clause to add to existing WHERE
     * 
     * @param string $tableAlias Table name or alias
     * @param string $column Column name (default: company_id)
     * @return string SQL AND clause (e.g., "AND alias.company_id = 7")
     */
    public function andCompanyFilter($tableAlias = '', $column = 'company_id') {
        if ($this->companyId === null) {
            return ""; // No filtering when no company selected
        }
        
        $prefix = $tableAlias ? "{$tableAlias}." : '';
        return "AND {$prefix}{$column} = " . intval($this->companyId);
    }
    
    /**
     * Generate OR clause for company filter
     * Useful for queries that can access multiple companies
     * 
     * @param string $tableAlias Table name or alias
     * @param string $column Column name
     * @return string SQL OR clause
     */
    public function orCompanyFilter($tableAlias = '', $column = 'company_id') {
        if ($this->companyId === null) {
            return "";
        }
        
        $prefix = $tableAlias ? "{$tableAlias}." : '';
        return "OR {$prefix}{$column} = " . intval($this->companyId);
    }
    
    /**
     * Get company filter as array for prepared statements
     * 
     * @return array ['column' => 'company_id', 'value' => 7, 'type' => 'i']
     */
    public function getFilterForPrepared() {
        return [
            'column' => 'company_id',
            'value' => $this->companyId,
            'type' => 'i'  // Integer type for mysqli bind
        ];
    }
    
    /**
     * Apply company filter to a query builder pattern
     * 
     * @param array &$conditions Reference to conditions array
     * @param array &$params Reference to parameters array
     * @param string $tableAlias Optional table alias
     */
    public function addToConditions(&$conditions, &$params, $tableAlias = '') {
        if ($this->companyId !== null) {
            $prefix = $tableAlias ? "{$tableAlias}." : '';
            $conditions[] = "{$prefix}company_id = ?";
            $params[] = $this->companyId;
        }
    }
    
    /**
     * Build a complete filtered query
     * 
     * @param string $baseQuery Base SELECT query without WHERE
     * @param string $tableAlias Main table alias
     * @param array $additionalConditions Additional WHERE conditions
     * @return string Complete SQL query
     */
    public function buildFilteredQuery($baseQuery, $tableAlias = '', $additionalConditions = []) {
        $conditions = [];
        
        // Add company filter
        if ($this->companyId !== null) {
            $prefix = $tableAlias ? "{$tableAlias}." : '';
            $conditions[] = "{$prefix}company_id = " . intval($this->companyId);
        }
        
        // Add additional conditions
        $conditions = array_merge($conditions, $additionalConditions);
        
        if (empty($conditions)) {
            return $baseQuery;
        }
        
        return $baseQuery . " WHERE " . implode(' AND ', $conditions);
    }
    
    /**
     * Validate that a record belongs to current company
     * 
     * @param mysqli $conn Database connection
     * @param string $table Table name
     * @param int $recordId Record ID to validate
     * @param string $idColumn ID column name (default: 'id')
     * @return bool True if record belongs to current company
     */
    public function validateRecordOwnership($conn, $table, $recordId, $idColumn = 'id') {
        if ($this->companyId === null) {
            return true; // No company restriction
        }
        
        $table = mysqli_real_escape_string($conn, $table);
        $idColumn = mysqli_real_escape_string($conn, $idColumn);
        $recordId = intval($recordId);
        $companyId = intval($this->companyId);
        
        $sql = "SELECT 1 FROM {$table} WHERE {$idColumn} = {$recordId} AND company_id = {$companyId} LIMIT 1";
        $result = mysqli_query($conn, $sql);
        
        return $result && mysqli_num_rows($result) > 0;
    }
    
    /**
     * Get list of accessible company IDs for current user
     * Useful for users who can access multiple companies
     * 
     * @param mysqli $conn Database connection
     * @return array List of company IDs
     */
    public function getAccessibleCompanyIds($conn) {
        if ($this->userId === null) {
            return [];
        }
        
        // For now, return single company
        // Can be extended to support multi-company access via a user_companies junction table
        if ($this->companyId !== null) {
            return [$this->companyId];
        }
        
        // If user's company is set in authorize table
        $userId = intval($this->userId);
        $sql = "SELECT company_id FROM authorize WHERE id = {$userId} AND status = 1";
        $result = mysqli_query($conn, $sql);
        
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return $row['company_id'] ? [$row['company_id']] : [];
        }
        
        return [];
    }
    
    /**
     * Generate IN clause for multiple companies
     * 
     * @param array $companyIds List of company IDs
     * @param string $tableAlias Optional table alias
     * @return string SQL IN clause
     */
    public function inCompanyFilter($companyIds, $tableAlias = '') {
        if (empty($companyIds)) {
            return "AND 1=0"; // No access
        }
        
        $prefix = $tableAlias ? "{$tableAlias}." : '';
        $ids = implode(',', array_map('intval', $companyIds));
        return "AND {$prefix}company_id IN ({$ids})";
    }
    
    /**
     * Set company ID for testing purposes
     * 
     * @param int|null $companyId
     */
    public function setCompanyId($companyId) {
        $this->companyId = $companyId !== null ? intval($companyId) : null;
    }
    
    /**
     * Get SQL-safe company ID for direct interpolation
     * Always returns an integer, never null
     * 
     * @param int $default Default value if no company (default: 0)
     * @return int
     */
    public function getSafeCompanyId($default = 0) {
        return $this->companyId !== null ? intval($this->companyId) : intval($default);
    }
}

// ============================================================================
// HELPER FUNCTIONS (for backward compatibility and ease of use)
// ============================================================================

/**
 * Get current company ID from session
 * 
 * @param bool $required Throw exception if not set
 * @return int|null
 */
function getCompanyId($required = false) {
    return CompanyFilter::getInstance()->getCompanyId($required);
}

/**
 * Get AND clause for company filter
 * 
 * @param string $tableAlias Table alias
 * @return string SQL AND clause
 */
function andCompanyFilter($tableAlias = '') {
    return CompanyFilter::getInstance()->andCompanyFilter($tableAlias);
}

/**
 * Get WHERE clause for company filter
 * 
 * @param string $tableAlias Table alias
 * @return string SQL WHERE clause
 */
function whereCompanyFilter($tableAlias = '') {
    return CompanyFilter::getInstance()->whereCompanyFilter($tableAlias);
}

/**
 * Get company ID safe for SQL queries
 * 
 * @param int $default Default value if not set
 * @return int
 */
function getSafeCompanyId($default = 0) {
    return CompanyFilter::getInstance()->getSafeCompanyId($default);
}
