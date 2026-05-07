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
    public function getPaginated(string $search = '', int $page = 1, int $perPage = 15, int $typeId = 0, int $brandId = 0, string $status = ''): array
    {
        $alias = 'm';
        $filterWhere = $this->companyFilter->whereCompanyFilter($alias);

        $searchCond = '';
        if (!empty($search)) {
            $escaped = \sql_escape($search);
            $searchCond = " AND (m.model_name LIKE '%$escaped%' OR type.name LIKE '%$escaped%' OR brand.brand_name LIKE '%$escaped%')";
        }
        if ($typeId > 0)            $searchCond .= " AND m.type_id = '" . \sql_int($typeId) . "'";
        if ($brandId > 0)           $searchCond .= " AND m.brand_id = '" . \sql_int($brandId) . "'";
        if ($status === 'active')   $searchCond .= " AND m.is_active = 1";
        if ($status === 'inactive') $searchCond .= " AND m.is_active = 0";

        $join = "JOIN type ON m.type_id = type.id JOIN brand ON m.brand_id = brand.id";

        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}` $alias $join $filterWhere $searchCond";
        $countResult = mysqli_query($this->conn, $countSql);
        $total = $countResult ? intval(mysqli_fetch_assoc($countResult)['total']) : 0;

        require_once __DIR__ . '/../../inc/pagination.php';
        $pagination = paginate($total, $perPage, $page);
        $offset = $pagination['offset'];

        $sql = "SELECT m.id, m.model_name, m.type_id, m.brand_id, m.price, m.des, m.is_active,
                type.name as type_name, brand.brand_name
                FROM `{$this->table}` $alias $join $filterWhere $searchCond
                ORDER BY m.model_name ASC LIMIT $offset, $perPage";

        $result = mysqli_query($this->conn, $sql);
        $items = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) $items[] = $row;
        }

        return ['items' => $items, 'total' => $total, 'count' => count($items), 'pagination' => $pagination];
    }

    public function getStats(): array
    {
        $filterWhere = $this->companyFilter->whereCompanyFilter();
        $sql = "SELECT COUNT(*) AS total, SUM(is_active=1) AS active, SUM(is_active=0) AS inactive
                FROM `{$this->table}` $filterWhere";
        $r = mysqli_query($this->conn, $sql);
        $row = $r ? mysqli_fetch_assoc($r) : [];
        return ['total' => (int)($row['total']??0), 'active' => (int)($row['active']??0), 'inactive' => (int)($row['inactive']??0)];
    }

    public function toggle(int $id, int $active): bool
    {
        $id = \sql_int($id);
        $active = $active ? 1 : 0;
        $filterWhere = $this->companyFilter->andCompanyFilter();
        return (bool) mysqli_query($this->conn, "UPDATE `{$this->table}` SET is_active=$active WHERE id=$id $filterWhere");
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

    /**
     * v6.6 — list models eligible to be a "parent" in the parent_model_id
     * dropdown. Two-level hierarchy only: parents are themselves top-level
     * (parent_model_id IS NULL), so a model never has a chain of ancestors.
     *
     * Excludes the current row when editing so a model can't pick itself
     * as its own parent.
     */
    public function getParentOptions(int $excludeId = 0): array
    {
        $companyId = intval($_SESSION['com_id'] ?? 0);
        $excludeId = intval($excludeId);
        $sql = "SELECT id, model_name FROM model
                WHERE company_id = " . \sql_int($companyId) . "
                  AND deleted_at IS NULL
                  AND parent_model_id IS NULL";
        if ($excludeId > 0) {
            $sql .= " AND id != " . \sql_int($excludeId);
        }
        $sql .= " ORDER BY model_name";
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * v6.6 — fetch active sub-models of a parent. Used by
     * LineAgentController to auto-seed line items when the parent tour
     * is booked — each child becomes one item_type='entrance' (or
     * configurable later) row in tour_booking_items.
     */
    public function getChildModels(int $parentId, int $companyId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT id, model_name, des, price, type_id
             FROM model
             WHERE company_id = ?
               AND parent_model_id = ?
               AND deleted_at IS NULL
               AND is_active = 1
             ORDER BY id ASC"
        );
        $stmt->bind_param('ii', $companyId, $parentId);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }
}
