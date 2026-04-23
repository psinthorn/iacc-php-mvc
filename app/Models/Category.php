<?php
namespace App\Models;

class Category extends BaseModel
{
    protected string $table = 'category';
    protected bool $useCompanyFilter = true;

    public function getPaginated(string $search = '', int $page = 1, int $perPage = 15, string $status = ''): array
    {
        $alias = 'c';
        $filterWhere = $this->companyFilter->whereCompanyFilter($alias);

        $searchCond = '';
        if (!empty($search)) {
            $escaped = \sql_escape($search);
            $searchCond = " AND (c.cat_name LIKE '%$escaped%' OR c.des LIKE '%$escaped%')";
        }
        if ($status === 'active')   $searchCond .= " AND c.is_active = 1";
        if ($status === 'inactive') $searchCond .= " AND c.is_active = 0";

        $countSql = "SELECT COUNT(*) as total FROM `{$this->table}` $alias $filterWhere $searchCond";
        $countResult = mysqli_query($this->conn, $countSql);
        $total = $countResult ? intval(mysqli_fetch_assoc($countResult)['total']) : 0;

        require_once __DIR__ . '/../../inc/pagination.php';
        $pagination = paginate($total, $perPage, $page);
        $offset = $pagination['offset'];

        $companyFilterType = $this->companyFilter->andCompanyFilter('t');
        $sql = "SELECT c.id, c.cat_name, c.des, c.is_active,
                (SELECT COUNT(*) FROM type t WHERE t.cat_id = c.id $companyFilterType) as product_count
                FROM `{$this->table}` $alias $filterWhere $searchCond
                ORDER BY c.cat_name ASC LIMIT $offset, $perPage";

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
}
