<?php
namespace App\Models;

/**
 * Category Model
 * 
 * Manages the `category` table with company-based multi-tenancy.
 * Table columns: id, company_id, cat_name, des, deleted_at
 */
class Category extends BaseModel
{
    protected string $table = 'category';
    protected bool $useCompanyFilter = true;

    /**
     * Get paginated categories with product count (types using this category)
     * 
     * @param string $search Search term
     * @param int    $page   Current page
     * @param int    $perPage Items per page
     * @return array ['items' => [], 'total' => int, 'pagination' => []]
     */
    public function getPaginated(string $search = '', int $page = 1, int $perPage = 15): array
    {
        $alias = 'c';
        $filterWhere = $this->companyFilter->whereCompanyFilter($alias);

        // Search condition
        $searchCond = '';
        if (!empty($search)) {
            $escaped = \sql_escape($search);
            $searchCond = " AND (c.cat_name LIKE '%$escaped%' OR c.des LIKE '%$escaped%')";
        }

        // Count total
        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}` $alias $filterWhere $searchCond";
        $countResult = mysqli_query($this->conn, $countSql);
        $total = $countResult ? intval(mysqli_fetch_assoc($countResult)['total']) : 0;

        // Pagination
        require_once __DIR__ . '/../../inc/pagination.php';
        $pagination = paginate($total, $perPage, $page);
        $offset = $pagination['offset'];

        // Fetch with product (type) count
        $companyFilterType = $this->companyFilter->andCompanyFilter('t');
        $sql = "SELECT c.id, c.cat_name, c.des, 
                (SELECT COUNT(*) FROM type t WHERE t.cat_id = c.id $companyFilterType) as product_count
                FROM `{$this->table}` $alias $filterWhere $searchCond 
                ORDER BY c.id DESC LIMIT $offset, $perPage";
        
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
            'count'      => count($items),
            'pagination' => $pagination,
        ];
    }
}
