<?php
namespace App\Models;

/**
 * PurchaseRequest Model
 * Replaces SQL from: pr-list.php, pr-create.php, pr-make.php, core-function.php case pr_list
 */
class PurchaseRequest extends BaseModel
{
    protected string $table = 'pr';
    protected bool $useCompanyFilter = false;

    public function countPRs(int $comId, string $direction, array $filters): int
    {
        $conds = $this->buildConditions($filters);
        if ($direction === 'out') {
            $join = "JOIN company ON pr.cus_id=company.id";
            $where = "ven_id='$comId'";
        } else {
            $join = "JOIN company ON pr.ven_id=company.id";
            $where = "cus_id='$comId'";
        }
        $statusCond = $this->getStatusCondition($filters['status'] ?? '');
        $sql = "SELECT COUNT(*) as total FROM pr $join WHERE cancel='0' AND $where $statusCond {$conds['search']} {$conds['date']}";
        $r = mysqli_query($this->conn, $sql);
        return $r ? intval(mysqli_fetch_assoc($r)['total']) : 0;
    }

    public function getPRs(int $comId, string $direction, array $filters, int $offset, int $limit): array
    {
        $conds = $this->buildConditions($filters);
        if ($direction === 'out') {
            $join = "JOIN company ON pr.cus_id=company.id";
            $where = "ven_id='$comId'";
        } else {
            $join = "JOIN company ON pr.ven_id=company.id";
            $where = "cus_id='$comId'";
        }
        $statusCond = $this->getStatusCondition($filters['status'] ?? '');
        $sql = "SELECT pr.id, pr.name, name_en, DATE_FORMAT(pr.date,'%d-%m-%Y') as createdate, status, cancel
            FROM pr $join WHERE cancel='0' AND $where $statusCond {$conds['search']} {$conds['date']}
            ORDER BY pr.id DESC LIMIT $offset, $limit";
        return $this->fetchAll($sql);
    }

    private function getStatusCondition(string $status): string
    {
        $map = ['pending'=>" AND status='0'", 'quotation'=>" AND status='1'", 'confirmed'=>" AND status='2'",
                'delivered'=>" AND status='3'", 'invoiced'=>" AND status='4'", 'completed'=>" AND status='5'"];
        return $map[$status] ?? '';
    }

    private function buildConditions(array $f): array
    {
        $search = '';
        if (!empty($f['search'])) {
            $s = \sql_escape($f['search']);
            $search = " AND (pr.name LIKE '%$s%' OR company.name_en LIKE '%$s%')";
        }
        $date = '';
        if (!empty($f['date_from'])) $date .= " AND pr.date >= '" . \sql_escape($f['date_from']) . "'";
        if (!empty($f['date_to'])) $date .= " AND pr.date <= '" . \sql_escape($f['date_to']) . "'";
        return ['search' => $search, 'date' => $date];
    }

    public function cancelPR(int $prId, int $comId): void
    {
        $cond = $comId > 0
            ? "id='" . \sql_int($prId) . "' AND (ven_id='$comId' OR cus_id='$comId')"
            : "id='" . \sql_int($prId) . "'";
        $args = ['table' => 'pr', 'value' => "cancel='1'", 'condition' => $cond];
        $this->hard->updateDb($args);
    }

    public function createPR(array $data, int $comId): int
    {
        $venId = !empty($data['ven_id']) ? intval($data['ven_id']) : $comId;
        $args = ['table' => 'pr'];
        $args['value'] = "'$comId','" . \sql_escape($data['name']) . "','" . \sql_escape($data['des']) . "','" .
            intval($data['user_id']) . "','" . intval($data['cus_id']) . "','" . $venId . "','" .
            date('Y-m-d') . "','0','0','0','0',NULL";
        $prId = $this->hard->insertDbMax($args);

        // Insert product rows
        for ($i = 0; $i < 9; $i++) {
            $typeId = $data['id' . $i] ?? '';
            $qty = $data['quantity' . $i] ?? '0';
            $price = $data['price' . $i] ?? '0';
            if (!empty($typeId) && $typeId != '0' && $qty != '0') {
                $args['table'] = 'tmp_product';
                $args['value'] = "NULL,'$prId','" . intval($typeId) . "','" . floatval($qty) . "','" . floatval($price) . "'";
                $this->hard->insertDB($args);
            }
        }
        return $prId;
    }

    public function getCategories(int $comId): array
    {
        $cf = \CompanyFilter::getInstance();
        return $this->fetchAll("SELECT * FROM category " . $cf->whereCompanyFilter());
    }

    public function getTypesByCategory(int $catId, int $comId): array
    {
        $cf = \CompanyFilter::getInstance();
        return $this->fetchAll("SELECT * FROM type " . $cf->whereCompanyFilter() . " AND cat_id='" . \sql_int($catId) . "'");
    }

    public function getVendors(): array
    {
        return $this->fetchAll("SELECT id, name_en, name_sh FROM company WHERE vender='1' AND deleted_at IS NULL ORDER BY name_en");
    }

    public function getCustomers(): array
    {
        return $this->fetchAll("SELECT id, name_en, name_sh FROM company WHERE customer='1' AND deleted_at IS NULL ORDER BY name_en");
    }

    public function getPRDetail(int $id, int $comId): ?array
    {
        $sql = "SELECT pr.*, company.name_en as company_name FROM pr
            JOIN company ON (pr.cus_id=company.id OR pr.ven_id=company.id)
            WHERE pr.id='" . \sql_int($id) . "' AND (pr.ven_id='$comId' OR pr.cus_id='$comId')
            LIMIT 1";
        $r = mysqli_query($this->conn, $sql);
        return ($r && mysqli_num_rows($r) > 0) ? mysqli_fetch_assoc($r) : null;
    }

    public function getTmpProducts(int $prId): array
    {
        return $this->fetchAll("SELECT tp.*, type.name as type_name FROM tmp_product tp
            LEFT JOIN type ON tp.type_id=type.id WHERE tp.pr_id='" . \sql_int($prId) . "'");
    }

    private function fetchAll(string $sql): array
    {
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) { while ($row = mysqli_fetch_assoc($result)) $rows[] = $row; }
        return $rows;
    }
}
