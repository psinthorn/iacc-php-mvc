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

        // Use isolated array for PR insert (prevents state leakage)
        $argsPR = array();
        $argsPR['table'] = 'pr';
        $argsPR['value'] = "'$comId','" . \sql_escape($data['name']) . "','" . \sql_escape($data['des']) . "','" .
            intval($data['user_id']) . "','" . intval($data['cus_id']) . "','" . $venId . "','" .
            date('Y-m-d') . "','0','0','0','0',NULL";
        $prId = $this->hard->insertDbMax($argsPR);

        // Insert product rows — fresh array per product
        for ($i = 0; $i < 9; $i++) {
            $typeId = $data['id' . $i] ?? '';
            $qty = $data['quantity' . $i] ?? '0';
            $price = $data['price' . $i] ?? '0';
            if (!empty($typeId) && $typeId != '0' && $qty != '0') {
                $argsProduct = array();
                $argsProduct['table'] = 'tmp_product';
                $argsProduct['value'] = "NULL,'$prId','" . intval($typeId) . "','" . floatval($qty) . "','" . floatval($price) . "'";
                $this->hard->insertDB($argsProduct);
            }
        }
        return $prId;
    }

    public function getCategories(int $comId): array
    {
        $cf = \CompanyFilter::getInstance();
        return $this->fetchAll("SELECT * FROM category " . $cf->whereCompanyFilter());
    }

    /**
     * Get categories with nested types and average prices for product selection modal.
     * Returns categories that have at least one type.
     */
    public function getCategoriesWithTypes(int $comId): array
    {
        $companyCondition = $comId > 0 ? " AND company_id = " . intval($comId) : '';

        $categories = [];
        $querycat = mysqli_query($this->conn, "SELECT * FROM category WHERE deleted_at IS NULL" . $companyCondition);
        if ($querycat) {
            while ($cat = mysqli_fetch_assoc($querycat)) {
                $cat['types'] = [];
                $query_type = mysqli_query($this->conn, "SELECT * FROM type WHERE cat_id='" . intval($cat['id']) . "' AND deleted_at IS NULL" . $companyCondition);
                if ($query_type) {
                    while ($type = mysqli_fetch_assoc($query_type)) {
                        $sql = "SELECT COALESCE(SUM(p.price)/NULLIF(SUM(p.quantity),0), 0) as net 
                                FROM product p WHERE p.type='" . intval($type['id']) . "'";
                        $netResult = mysqli_fetch_assoc(mysqli_query($this->conn, $sql));
                        $type['price'] = floor($netResult['net'] ?? 0);
                        $cat['types'][] = $type;
                    }
                }
                if (!empty($cat['types'])) {
                    $categories[] = $cat;
                }
            }
        }
        return $categories;
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
        $where = $comId > 0 ? " AND (pr.ven_id='$comId' OR pr.cus_id='$comId')" : '';
        $sql = "SELECT pr.*, DATE_FORMAT(pr.date,'%d-%m-%Y') as createdate,
                cust.name_en as customer_name, ven.name_en as vendor_name,
                COALESCE(cust.name_en, ven.name_en) as company_name
            FROM pr
            LEFT JOIN company cust ON pr.cus_id=cust.id
            LEFT JOIN company ven ON pr.ven_id=ven.id
            WHERE pr.id='" . \sql_int($id) . "'" . $where . "
            LIMIT 1";
        $r = mysqli_query($this->conn, $sql);
        return ($r && mysqli_num_rows($r) > 0) ? mysqli_fetch_assoc($r) : null;
    }

    public function getTmpProducts(int $prId): array
    {
        return $this->fetchAll("SELECT tp.*, t.name as type_name FROM tmp_product tp
            LEFT JOIN type t ON tp.type=t.id WHERE tp.pr_id='" . \sql_int($prId) . "'");
    }

    private function fetchAll(string $sql): array
    {
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) { while ($row = mysqli_fetch_assoc($result)) $rows[] = $row; }
        return $rows;
    }
}
