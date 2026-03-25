<?php
namespace App\Models;

/**
 * ProductModel Model
 * 
 * Named ProductModel to avoid collision with MVC "Model" concept.
 * Manages the `model` table with company-based multi-tenancy.
 * Table columns: id, company_id, type_id, brand_id, model_name, des, price, deleted_at
 */
class ProductModel extends BaseModel
{
    protected string $table = 'model';
    protected bool $useCompanyFilter = true;

    /**
     * Get paginated models with type name and brand name
     */
    public function getPaginated(string $search = '', int $page = 1, int $perPage = 15, int $typeId = 0, int $brandId = 0): array
    {
        $alias = 'm';
        $filterWhere = $this->companyFilter->whereCompanyFilter($alias);

        $searchCond = '';
        if (!empty($search)) {
            $escaped = \sql_escape($search);
            $searchCond = " AND (m.model_name LIKE '%$escaped%' OR type.name LIKE '%$escaped%' OR brand.brand_name LIKE '%$escaped%')";
        }
        if ($typeId > 0) {
            $searchCond .= " AND m.type_id = '" . \sql_int($typeId) . "'";
        }
        if ($brandId > 0) {
            $searchCond .= " AND m.brand_id = '" . \sql_int($brandId) . "'";
        }

        $join = "JOIN type ON m.type_id = type.id JOIN brand ON m.brand_id = brand.id";

        // Count total
        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}` $alias $join $filterWhere $searchCond";
        $countResult = mysqli_query($this->conn, $countSql);
        $total = $countResult ? intval(mysqli_fetch_assoc($countResult)['total']) : 0;

        // Pagination
        require_once __DIR__ . '/../../inc/pagination.php';
        $pagination = paginate($total, $perPage, $page);
        $offset = $pagination['offset'];

        // Fetch with type and brand names
        $sql = "SELECT m.id, m.model_name, m.type_id, m.brand_id, m.price, m.des,
                type.name as type_name, brand.brand_name
                FROM `{$this->table}` $alias $join $filterWhere $searchCond 
                ORDER BY m.id DESC LIMIT $offset, $perPage";

        $result = mysqli_query($this->conn, $sql);
        $items = [];
        $count = 0;
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $items[] = $row;
                $count++;
            }
        }

        return [
            'items'      => $items,
            'total'      => $total,
            'count'      => $count,
            'pagination' => $pagination,
        ];
    }

    /**
     * Check if a model can be deleted (not referenced by products)
     */
    public function canDelete(int $id): array
    {
        $companyFilter = $this->companyFilter->andCompanyFilter('m');
        $sql = "SELECT COUNT(*) as cnt FROM product p JOIN model m ON p.model = m.id 
                WHERE p.model = '" . \sql_int($id) . "' $companyFilter";
        $result = mysqli_query($this->conn, $sql);
        $count = $result ? intval(mysqli_fetch_assoc($result)['cnt']) : 0;
        
        return [
            'can_delete' => ($count === 0),
            'product_count' => $count,
        ];
    }

    /**
     * Get all types for dropdown
     */
    public function getTypes(): array
    {
        $companyFilter = $this->companyFilter->whereCompanyFilter();
        $sql = "SELECT id, name FROM type $companyFilter ORDER BY name";
        $result = mysqli_query($this->conn, $sql);
        $items = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $items[] = $row;
            }
        }
        return $items;
    }

    /**
     * Get all brands for dropdown
     */
    public function getBrands(): array
    {
        $companyFilter = $this->companyFilter->whereCompanyFilter();
        $sql = "SELECT id, brand_name FROM brand $companyFilter ORDER BY brand_name";
        $result = mysqli_query($this->conn, $sql);
        $items = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $items[] = $row;
            }
        }
        return $items;
    }

    /**
     * Get brands for a specific type (via map_type_to_brand junction)
     * Used by AJAX dropdown
     */
    public function getBrandsForType(int $typeId): array
    {
        $companyId = intval($_SESSION['com_id'] ?? 0);
        $sql = "SELECT brand.id, brand.brand_name FROM brand
                JOIN map_type_to_brand ON brand.id = map_type_to_brand.brand_id
                WHERE map_type_to_brand.type_id = '" . \sql_int($typeId) . "'";
        if ($companyId > 0) {
            $sql .= " AND brand.company_id = '" . \sql_int($companyId) . "'";
        }
        $sql .= " ORDER BY brand.brand_name";
        
        $result = mysqli_query($this->conn, $sql);
        $items = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $items[] = $row;
            }
        }
        return $items;
    }
}
