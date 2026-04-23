<?php
namespace App\Models;

/**
 * Type Model (Product Types)
 * 
 * Manages the `type` table with company-based multi-tenancy.
 * Table columns: id, company_id, name, des, cat_id, deleted_at
 * 
 * Also manages brand associations via `map_type_to_brand` junction table.
 */
class Type extends BaseModel
{
    protected string $table = 'type';
    protected bool $useCompanyFilter = true;

    /**
     * Get paginated types with category name and brand count
     */
    public function getPaginated(string $search = '', int $page = 1, int $perPage = 15, int $catId = 0, string $status = ''): array
    {
        $alias = 't';
        $filterWhere = $this->companyFilter->whereCompanyFilter($alias);

        $searchCond = '';
        if (!empty($search)) {
            $escaped = \sql_escape($search);
            $searchCond = " AND (t.name LIKE '%$escaped%' OR category.cat_name LIKE '%$escaped%')";
        }
        if ($catId > 0)             $searchCond .= " AND t.cat_id = '" . \sql_int($catId) . "'";
        if ($status === 'active')   $searchCond .= " AND t.is_active = 1";
        if ($status === 'inactive') $searchCond .= " AND t.is_active = 0";

        $join = "JOIN category ON t.cat_id = category.id";

        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}` $alias $join $filterWhere $searchCond";
        $countResult = mysqli_query($this->conn, $countSql);
        $total = $countResult ? intval(mysqli_fetch_assoc($countResult)['total']) : 0;

        require_once __DIR__ . '/../../inc/pagination.php';
        $pagination = paginate($total, $perPage, $page);
        $offset = $pagination['offset'];

        $sql = "SELECT t.id, t.name, t.des, t.cat_id, t.is_active, category.cat_name,
                (SELECT COUNT(*) FROM map_type_to_brand m WHERE m.type_id = t.id) as brand_count
                FROM `{$this->table}` $alias $join $filterWhere $searchCond
                ORDER BY t.name ASC LIMIT $offset, $perPage";

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
     * Get all categories for dropdown
     */
    public function getCategories(): array
    {
        $companyFilter = $this->companyFilter->whereCompanyFilter();
        $sql = "SELECT id, cat_name FROM category $companyFilter ORDER BY cat_name";
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
     * Get all brands for checkboxes
     */
    public function getAllBrands(): array
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
     * Get brand IDs associated with a type
     */
    public function getAssociatedBrandIds(int $typeId): array
    {
        $sql = "SELECT brand_id FROM map_type_to_brand WHERE type_id = '" . \sql_int($typeId) . "'";
        $result = mysqli_query($this->conn, $sql);
        $ids = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $ids[] = intval($row['brand_id']);
            }
        }
        return $ids;
    }

    /**
     * Sync brand associations for a type (delete all + re-insert)
     */
    public function syncBrands(int $typeId, array $brandIds): void
    {
        // Delete existing associations
        mysqli_query($this->conn, "DELETE FROM map_type_to_brand WHERE type_id = '" . \sql_int($typeId) . "'");

        // Re-insert selected brands
        $companyId = intval($_SESSION['com_id'] ?? 0);
        foreach ($brandIds as $brandId) {
            $brandId = \sql_int($brandId);
            if ($brandId > 0) {
                $companyVal = $companyId > 0 ? "'$companyId'" : 'NULL';
                mysqli_query($this->conn, 
                    "INSERT INTO map_type_to_brand VALUES(NULL, $companyVal, '" . \sql_int($typeId) . "', '$brandId')");
            }
        }
    }

    /**
     * Delete a type and its brand associations
     */
    public function deleteWithBrands(int $id): bool
    {
        mysqli_query($this->conn, "DELETE FROM map_type_to_brand WHERE type_id = '" . \sql_int($id) . "'");
        return $this->delete($id);
    }
}
