<?php
namespace App\Models;

/**
 * Brand Model
 * 
 * Manages the `brand` table with company-based multi-tenancy.
 * Table columns: id, company_id, brand_name, des, logo, ven_id, deleted_at
 */
class Brand extends BaseModel
{
    protected string $table = 'brand';
    protected bool $useCompanyFilter = true;

    /**
     * Get paginated brands with vendor name and product count
     */
    public function getPaginated(string $search = '', int $page = 1, int $perPage = 15, string $status = ''): array
    {
        $alias = 'b';
        $filterWhere = $this->companyFilter->whereCompanyFilter($alias);

        $searchCond = '';
        if (!empty($search)) {
            $escaped = \sql_escape($search);
            $searchCond = " AND (b.brand_name LIKE '%$escaped%' OR b.des LIKE '%$escaped%')";
        }
        if ($status === 'active')   $searchCond .= " AND b.is_active = 1";
        if ($status === 'inactive') $searchCond .= " AND b.is_active = 0";

        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}` $alias $filterWhere $searchCond";
        $countResult = mysqli_query($this->conn, $countSql);
        $total = $countResult ? intval(mysqli_fetch_assoc($countResult)['total']) : 0;

        require_once __DIR__ . '/../../inc/pagination.php';
        $pagination = paginate($total, $perPage, $page);
        $offset = $pagination['offset'];

        $sql = "SELECT b.id, b.brand_name, b.des, b.logo, b.ven_id, b.is_active,
                (SELECT name_en FROM company WHERE id = b.ven_id) as vendor_name,
                (SELECT COUNT(*) FROM map_type_to_brand m WHERE m.brand_id = b.id) as product_count
                FROM `{$this->table}` $alias $filterWhere $searchCond
                ORDER BY b.brand_name ASC LIMIT $offset, $perPage";

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
     * Get vendors for dropdown (companies marked as vendor)
     */
    public function getVendors(int $companyId): array
    {
        $sql = "SELECT id, name_en FROM company WHERE vender='1' AND company_id = '" . \sql_int($companyId) . "' ORDER BY name_en";
        $result = mysqli_query($this->conn, $sql);
        $vendors = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $vendors[] = $row;
            }
        }
        return $vendors;
    }

    /**
     * Get own company info
     */
    public function getOwnCompany(int $companyId): ?array
    {
        $sql = "SELECT id, name_en FROM company WHERE id = '" . \sql_int($companyId) . "'";
        $result = mysqli_query($this->conn, $sql);
        if ($result && mysqli_num_rows($result) > 0) {
            return mysqli_fetch_assoc($result);
        }
        return null;
    }

    /**
     * Handle logo upload
     * @return string|null Filename if uploaded, null otherwise
     */
    public function handleLogoUpload(): ?string
    {
        if (!empty($_FILES['logo']['tmp_name']) && 
            in_array($_FILES['logo']['type'], ['image/jpg', 'image/jpeg', 'image/JPG', 'image/pjpeg'])) {
            $filepath = 'logo' . md5(rand() . ($_REQUEST['brand_name'] ?? '')) . '.jpg';
            copy($_FILES['logo']['tmp_name'], __DIR__ . '/../../upload/' . $filepath);
            return $filepath;
        }
        return null;
    }
}
