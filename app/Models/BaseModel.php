<?php
namespace App\Models;

/**
 * BaseModel - Foundation for all MVC models
 * 
 * Wraps HardClass safe methods to provide a clean, fluent API.
 * Each model maps to a single database table.
 * 
 * Usage:
 *   class Category extends BaseModel {
 *       protected string $table = 'category';
 *   }
 *   
 *   $model = new Category();
 *   $items = $model->all();
 *   $item  = $model->find(5);
 *   $id    = $model->create(['cat_name' => 'Test', 'des' => 'Desc']);
 *   $model->update(5, ['cat_name' => 'Updated']);
 *   $model->delete(5);
 */
class BaseModel
{
    /** @var string Table name — must be set by child class */
    protected string $table = '';

    /** @var string Primary key column */
    protected string $primaryKey = 'id';

    /** @var \HardClass Database abstraction */
    protected \HardClass $hard;

    /** @var \mysqli Database connection */
    protected \mysqli $conn;

    /** @var \CompanyFilter Multi-tenant filter */
    protected \CompanyFilter $companyFilter;

    /** @var bool Whether this table uses company_id filtering */
    protected bool $useCompanyFilter = true;

    public function __construct()
    {
        global $db;

        $this->conn = $db->conn;
        $this->hard = new \HardClass();
        $this->hard->setConnection($this->conn);

        require_once __DIR__ . '/../../inc/class.company_filter.php';
        $this->companyFilter = \CompanyFilter::getInstance();
    }

    // =====================================================
    // CRUD Operations
    // =====================================================

    /**
     * Find a single record by primary key
     * 
     * @param int $id Record ID
     * @return array|null
     */
    public function find(int $id): ?array
    {
        $filter = $this->useCompanyFilter 
            ? $this->companyFilter->andCompanyFilter() 
            : '';
        
        $sql = "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = '" . \sql_int($id) . "' $filter";
        $result = mysqli_query($this->conn, $sql);
        
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
        return null;
    }

    /**
     * Get all records (with optional company filtering)
     * 
     * @param string $orderBy ORDER BY clause
     * @return array
     */
    public function all(string $orderBy = 'id DESC'): array
    {
        $where = $this->useCompanyFilter 
            ? $this->companyFilter->whereCompanyFilter() 
            : '';
        
        $sql = "SELECT * FROM `{$this->table}` $where ORDER BY $orderBy";
        $result = mysqli_query($this->conn, $sql);
        
        $rows = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }
        }
        return $rows;
    }

    /**
     * Get paginated records with search
     * 
     * @param array  $options  ['search' => '', 'search_columns' => [], 'page' => 1, 'per_page' => 15, 'order_by' => 'id DESC']
     * @return array ['items' => [], 'total' => int, 'pagination' => []]
     */
    public function paginate(array $options = []): array
    {
        $search       = $options['search'] ?? '';
        $searchCols   = $options['search_columns'] ?? [];
        $currentPage  = max(1, intval($options['page'] ?? 1));
        $perPage      = intval($options['per_page'] ?? 15);
        $orderBy      = $options['order_by'] ?? 'id DESC';
        $selectCols   = $options['select'] ?? '*';
        $extraJoin    = $options['join'] ?? '';
        $extraWhere   = $options['where'] ?? '';
        $tableAlias   = $options['alias'] ?? '';

        $alias = $tableAlias ?: $this->table[0]; // First letter as default alias

        // Build WHERE
        $filterWhere = $this->useCompanyFilter 
            ? $this->companyFilter->whereCompanyFilter($alias) 
            : 'WHERE 1=1';

        // Search condition
        $searchCond = '';
        if (!empty($search) && !empty($searchCols)) {
            $escaped = \sql_escape($search);
            $parts = [];
            foreach ($searchCols as $col) {
                $parts[] = "$col LIKE '%$escaped%'";
            }
            $searchCond = ' AND (' . implode(' OR ', $parts) . ')';
        }

        if ($extraWhere) {
            $searchCond .= " AND ($extraWhere)";
        }

        // Count total
        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}` $alias $extraJoin $filterWhere $searchCond";
        $countResult = mysqli_query($this->conn, $countSql);
        $total = $countResult ? mysqli_fetch_assoc($countResult)['total'] : 0;

        // Pagination calculation
        require_once __DIR__ . '/../../inc/pagination.php';
        $pagination = paginate($total, $perPage, $currentPage);
        $offset = $pagination['offset'];

        // Fetch items
        $sql = "SELECT $selectCols FROM `{$this->table}` $alias $extraJoin $filterWhere $searchCond ORDER BY $orderBy LIMIT $offset, $perPage";
        $result = mysqli_query($this->conn, $sql);

        $items = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $items[] = $row;
            }
        }

        return [
            'items'      => $items,
            'total'      => $total,
            'pagination' => $pagination,
        ];
    }

    /**
     * Create a new record
     * 
     * @param array $data Column => value pairs
     * @return int|false Insert ID or false
     */
    public function create(array $data)
    {
        // Auto-add company_id if applicable
        if ($this->useCompanyFilter && !isset($data['company_id'])) {
            $companyId = intval($_SESSION['com_id'] ?? 0);
            // Use NULL for company_id=0 to satisfy FK constraints
            $data['company_id'] = $companyId > 0 ? $companyId : null;
        }

        return $this->hard->insertSafe($this->table, $data);
    }

    /**
     * Update a record by primary key
     * 
     * @param int   $id   Record ID
     * @param array $data Column => value pairs to update
     * @return bool
     */
    public function update(int $id, array $data): bool
    {
        $where = [$this->primaryKey => $id];
        
        // Add company filter for safety
        if ($this->useCompanyFilter) {
            $companyId = intval($_SESSION['com_id'] ?? 0);
            if ($companyId > 0) {
                $where['company_id'] = $companyId;
            }
        }

        return $this->hard->updateSafe($this->table, $data, $where);
    }

    /**
     * Hard delete a record by primary key
     * 
     * @param int $id Record ID
     * @return bool
     */
    public function delete(int $id): bool
    {
        $where = [$this->primaryKey => $id];
        
        // Add company filter for safety
        if ($this->useCompanyFilter) {
            $companyId = intval($_SESSION['com_id'] ?? 0);
            if ($companyId > 0) {
                $where['company_id'] = $companyId;
            }
        }

        return $this->hard->deleteSafe($this->table, $where);
    }

    /**
     * Soft delete a record (set deleted_at)
     * 
     * @param int $id Record ID
     * @return bool
     */
    public function softDelete(int $id): bool
    {
        return $this->hard->softDelete($this->table, [$this->primaryKey => $id]);
    }

    /**
     * Count records (with company filter)
     * 
     * @param string $extraWhere Additional WHERE conditions
     * @return int
     */
    public function count(string $extraWhere = ''): int
    {
        $where = $this->useCompanyFilter 
            ? $this->companyFilter->whereCompanyFilter() 
            : '';
        
        if ($extraWhere) {
            $where .= ($where ? ' AND ' : 'WHERE ') . $extraWhere;
        }

        $sql = "SELECT COUNT(*) as cnt FROM `{$this->table}` $where";
        $result = mysqli_query($this->conn, $sql);
        return $result ? intval(mysqli_fetch_assoc($result)['cnt']) : 0;
    }

    /**
     * Execute a raw query on this model's connection
     * For complex queries not suited to the abstraction
     */
    public function rawQuery(string $sql)
    {
        return mysqli_query($this->conn, $sql);
    }
}
