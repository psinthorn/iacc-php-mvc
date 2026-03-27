<?php
namespace App\Models;

/**
 * PurchaseOrder Model
 * Replaces SQL from: po-list.php, po-make.php, po-edit.php, po-view.php, po-deliv.php, core-function.php case po_list
 */
class PurchaseOrder extends BaseModel
{
    protected string $table = 'po';
    protected bool $useCompanyFilter = false;

    public function countPOs(int $comId, string $direction, array $filters, string $statusFilter = ''): int
    {
        $conds = $this->buildConditions($filters);
        $statusCond = $this->getStatusCondition($statusFilter);
        if ($direction === 'out') {
            $where = "ven_id='$comId'";
            $join = "JOIN pr ON po.ref=pr.id JOIN company ON pr.cus_id=company.id";
        } else {
            $where = "cus_id='$comId'";
            $join = "JOIN pr ON po.ref=pr.id JOIN company ON pr.ven_id=company.id";
        }
        $sql = "SELECT COUNT(*) as total FROM po $join WHERE po_id_new='' AND $where $statusCond {$conds['search']} {$conds['date']}";
        $r = mysqli_query($this->conn, $sql);
        return $r ? intval(mysqli_fetch_assoc($r)['total']) : 0;
    }

    public function getPOs(int $comId, string $direction, array $filters, int $offset, int $limit, string $statusFilter = ''): array
    {
        $conds = $this->buildConditions($filters);
        $statusCond = $this->getStatusCondition($statusFilter);
        if ($direction === 'out') {
            $where = "ven_id='$comId'";
            $join = "JOIN pr ON po.ref=pr.id JOIN company ON pr.cus_id=company.id";
        } else {
            $where = "cus_id='$comId'";
            $join = "JOIN pr ON po.ref=pr.id JOIN company ON pr.ven_id=company.id";
        }
        $sql = "SELECT po.id, po.tax, po.name, DATE_FORMAT(po.valid_pay,'%d-%m-%Y') as valid_pay,
                DATE_FORMAT(po.date,'%d-%m-%Y') as createdate, company.name_en, pr.status, pr.cancel, po.pic, po.po_ref
                FROM po $join WHERE po_id_new='' AND $where $statusCond {$conds['search']} {$conds['date']}
                ORDER BY pr.cancel ASC, po.id DESC LIMIT $offset, $limit";
        return $this->fetchAll($sql);
    }

    private function getStatusCondition(string $status): string
    {
        return match($status) {
            'quotation' => " AND pr.status='1'",
            'confirmed' => " AND pr.status='2'",
            'delivered' => " AND pr.status='3'",
            'invoiced' => " AND pr.status='4'",
            'completed' => " AND pr.status='5'",
            default => " AND pr.status='2'",
        };
    }

    private function buildConditions(array $f): array
    {
        $search = '';
        if (!empty($f['search'])) {
            $s = \sql_escape($f['search']);
            $search = " AND (po.name LIKE '%$s%' OR po.tax LIKE '%$s%' OR company.name_en LIKE '%$s%' OR company.name_th LIKE '%$s%')";
        }
        $date = '';
        if (!empty($f['date_from'])) $date .= " AND po.date >= '" . \sql_escape($f['date_from']) . "'";
        if (!empty($f['date_to'])) $date .= " AND po.date <= '" . \sql_escape($f['date_to']) . "'";
        return ['search' => $search, 'date' => $date];
    }

    // Cancel PO by setting cancel flag on PR
    public function cancelPO(int $poId, int $comId): ?string
    {
        $dataref = mysqli_fetch_array(mysqli_query($this->conn,
            "SELECT ref, status FROM po JOIN pr ON po.ref=pr.id WHERE po.id='" . \sql_int($poId) . "'"));
        if (!$dataref) return null;

        $args = ['table' => 'pr', 'value' => "cancel='1'"];
        if ($comId > 0) {
            $args['condition'] = "id='" . $dataref['ref'] . "' AND (ven_id='$comId' OR cus_id='$comId')";
        } else {
            $args['condition'] = "id='" . $dataref['ref'] . "'";
        }
        $this->hard->updateDb($args);
        return $dataref['status'];
    }

    // Create PO from PR
    public function createPO(array $data, int $comId): int
    {
        $argsPO = [];
        $argsPO['table'] = 'po';
        $newPoId = $this->hard->Maxid('po');
        $taxNumber = (date("y") + 43) . str_pad($newPoId, 6, '0', STR_PAD_LEFT);

        $argsPO['columns'] = "company_id, po_id_new, name, ref, tax, date, valid_pay, deliver_date, pic, po_ref, dis, bandven, vat, over, deleted_at";
        $argsPO['value'] = "'" . $comId . "', '', '" . \sql_escape($data['name']) . "', '" . intval($data['ref']) . "', '" .
            $taxNumber . "', '" . date('Y-m-d') . "', '" . date("Y-m-d", strtotime($data['valid_pay'])) . "', '" .
            date("Y-m-d", strtotime($data['deliver_date'])) . "', '', '', '" . floatval($data['dis'] ?? 0) . "', '" .
            intval($data['brandven'] ?? 0) . "', '" . floatval($data['vat'] ?? 0) . "', '" . floatval($data['over'] ?? 0) . "', NULL";
        $createdPoId = $this->hard->insertDbMax($argsPO);

        // Update PR status to quotation
        $argsPR = ['table' => 'pr', 'value' => "status='1'", 'condition' => "id='" . intval($data['ref']) . "'"];
        $this->hard->updateDb($argsPR);

        // Insert products
        $this->insertProducts($data, $createdPoId, $comId);
        return $createdPoId;
    }

    // Edit PO (create new version)
    public function editPO(array $data, int $comId): int
    {
        // Update PR customer
        $argsPR = ['table' => 'pr',
            'value' => "cus_id='" . \sql_escape($data['cus_id'] ?? '') . "'",
            'condition' => "id='" . intval($data['ref']) . "' AND ven_id='$comId'"];
        $this->hard->updateDb($argsPR);

        // Create new PO version
        $argsPO = ['table' => 'po'];
        $newPoId = $this->hard->Maxid('po');
        $taxNumber = (date("y") + 43) . str_pad($newPoId, 6, '0', STR_PAD_LEFT);
        $argsPO['columns'] = "company_id, po_id_new, name, ref, tax, date, valid_pay, deliver_date, pic, po_ref, dis, bandven, vat, over, deleted_at";
        $argsPO['value'] = "'" . $comId . "', '', '" . \sql_escape($data['name']) . "', '" . intval($data['ref']) . "', '" .
            $taxNumber . "', '" . date("Y-m-d", strtotime($data['create_date'])) . "', '" .
            date("Y-m-d", strtotime($data['valid_pay'])) . "', '" . date("Y-m-d", strtotime($data['deliver_date'])) . "', '', '', '" .
            floatval($data['dis'] ?? 0) . "', '" . intval($data['brandven'] ?? 0) . "', '" .
            floatval($data['vat'] ?? 0) . "', '" . floatval($data['over'] ?? 0) . "', NULL";
        $createdPoId = $this->hard->insertDbMax($argsPO);

        // Link old PO to new
        $argsOld = ['table' => 'po', 'value' => "po_id_new='$createdPoId'", 'condition' => "id='" . intval($data['id']) . "'"];
        $this->hard->updateDb($argsOld);

        $this->insertProducts($data, $createdPoId, $comId, true);
        return $createdPoId;
    }

    // Confirm PO with file upload
    public function confirmPO(array $data, array $file, int $comId): void
    {
        $namefile = '';
        $type = '';
        if (!empty($file["file"]["name"]) && $file["file"]["error"] == 0) {
            $temp = explode(".", $file["file"]["name"]);
            $ext = strtolower(end($temp));
            $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xls', 'xlsx'];
            if (in_array($ext, $allowed) && $file["file"]["size"] < 10000000) {
                $type = $ext;
                $namefile = md5(date("Y:m:d:h:m:s") . rand());
                move_uploaded_file($file["file"]["tmp_name"], "upload/" . $namefile . "." . $type);
            }
        }
        $args = ['table' => 'po'];
        $po_ref = \sql_escape($data['po_ref'] ?? '');
        $args['value'] = !empty($namefile) ? "pic='$namefile.$type', po_ref='$po_ref'" : "po_ref='$po_ref'";
        $args['condition'] = "po_id_new='' AND ref='" . \sql_int($data['ref']) . "'";
        $this->hard->updateDb($args);

        $argsPR = ['table' => 'pr', 'value' => "status='2'", 'condition' => "id='" . \sql_int($data['ref']) . "'"];
        $this->hard->updateDb($argsPR);
    }

    private function insertProducts(array $data, int $poId, int $comId, bool $withDiscount = false): void
    {
        if (!isset($data['type']) || !is_array($data['type'])) return;
        foreach ($data['type'] as $key => $typeValue) {
            $argsP = ['table' => 'product'];
            $price = floatval($data['price'][$key] ?? 0);
            $discount = $withDiscount ? floatval($data['discount'][$key] ?? 0) : 0;
            $ban_id = intval($data['ban_id'][$key] ?? 0);
            $model = intval($data['model'][$key] ?? 0);
            $qty = floatval($data['quantity'][$key] ?? 1);
            $pack_qty = floatval($data['pack_quantity'][$key] ?? 1);
            $des = \sql_escape($data['des'][$key] ?? '');
            $a_labour = intval($data['a_labour'][$key] ?? 0);
            $v_labour = floatval($data['v_labour'][$key] ?? 0);
            $argsP['value'] = "NULL, '$comId', '$poId', '$price', '$discount', '$ban_id', '$model', '" .
                intval($typeValue) . "', '$qty', '$pack_qty', '0', '$des', '$a_labour', '$v_labour', '0', '1970-01-01', '0', NULL";
            $this->hard->insertDB($argsP);
        }
    }

    // View data fetchers
    public function getPODetail(int $id, int $comId): ?array
    {
        $sql = "SELECT po.id, po.name, po.tax, po.date, po.valid_pay, po.deliver_date, po.vat, po.dis, po.over,
                po.pic, po.po_ref, po.bandven, pr.id as pr_id, pr.status, pr.cus_id, pr.ven_id, pr.des, pr.payby,
                company.name_en, company.name_sh, company.tax as company_tax, company.phone, company.email
                FROM po JOIN pr ON po.ref=pr.id
                LEFT JOIN company ON pr.cus_id=company.id
                WHERE po.id='" . \sql_int($id) . "' AND po_id_new='' AND (pr.cus_id='$comId' OR pr.ven_id='$comId')
                LIMIT 1";
        $r = mysqli_query($this->conn, $sql);
        return ($r && mysqli_num_rows($r) > 0) ? mysqli_fetch_assoc($r) : null;
    }

    public function getPOForEdit(int $id, int $comId): ?array
    {
        $sql = "SELECT po.id, po.ref, po.name, po.date, po.valid_pay, po.deliver_date, po.vat, po.dis, po.over,
                po.bandven, pr.cus_id, pr.ven_id, pr.des, pr.status,
                ca.adr_tax, ca.city_tax, ca.district_tax, ca.province_tax, ca.zip_tax
                FROM po JOIN pr ON po.ref=pr.id
                LEFT JOIN company_addr ca ON pr.cus_id=ca.com_id AND ca.deleted_at IS NULL
                WHERE po.id='" . \sql_int($id) . "' AND pr.status='1' AND po_id_new=''
                AND pr.ven_id='$comId'
                ORDER BY (ca.valid_end = '0000-00-00' OR ca.valid_end = '9999-12-31') DESC, ca.valid_start DESC
                LIMIT 1";
        $r = mysqli_query($this->conn, $sql);
        return ($r && mysqli_num_rows($r) > 0) ? mysqli_fetch_assoc($r) : null;
    }

    public function getPOForMake(int $prId, int $comId): ?array
    {
        $sql = "SELECT pr.id, pr.name, pr.des, pr.cus_id, pr.ven_id
                FROM pr WHERE pr.id='" . \sql_int($prId) . "' AND pr.status='0' AND pr.ven_id='$comId' LIMIT 1";
        $r = mysqli_query($this->conn, $sql);
        return ($r && mysqli_num_rows($r) > 0) ? mysqli_fetch_assoc($r) : null;
    }

    public function getPOForDelivery(int $id, int $comId): ?array
    {
        $sql = "SELECT po.id, po.name, po.tax, po.valid_pay, po.deliver_date, po.vat, po.dis, po.over,
                pr.cus_id, pr.ven_id, pr.id as pr_id, pr.status, company.name_en
                FROM po JOIN pr ON po.ref=pr.id
                LEFT JOIN company ON pr.cus_id=company.id
                WHERE po.id='" . \sql_int($id) . "' AND (pr.status='1' OR pr.status='2')
                AND pr.ven_id='$comId' AND po_id_new='' LIMIT 1";
        $r = mysqli_query($this->conn, $sql);
        return ($r && mysqli_num_rows($r) > 0) ? mysqli_fetch_assoc($r) : null;
    }

    public function getProducts(int $poId): array
    {
        return $this->fetchAll("SELECT product.*, type.name as type_name, model.model_name, brand.brand_name
            FROM product
            LEFT JOIN type ON product.type=type.id
            LEFT JOIN model ON product.model=model.id
            LEFT JOIN brand ON product.ban_id=brand.id
            WHERE product.po_id='" . \sql_int($poId) . "' AND product.deleted_at IS NULL");
    }

    public function getProductsForDelivery(int $poId): array
    {
        return $this->fetchAll("SELECT type.name as type_name, product.des, product.price, product.pro_id,
            model.model_name, product.quantity, product.activelabour, product.valuelabour
            FROM product
            LEFT JOIN type ON product.type=type.id
            LEFT JOIN model ON product.model=model.id
            WHERE product.po_id='" . \sql_int($poId) . "' AND product.deleted_at IS NULL");
    }

    public function getCredit(int $venId, int $cusId): ?array
    {
        $r = mysqli_query($this->conn, "SELECT limit_day FROM company_credit WHERE ven_id='" . \sql_int($venId) . "' AND cus_id='" . \sql_int($cusId) . "'");
        return ($r && mysqli_num_rows($r) > 0) ? mysqli_fetch_assoc($r) : null;
    }

    public function getCompanies(): array
    {
        return $this->fetchAll("SELECT id, name_en, name_sh, customer, vender FROM company WHERE deleted_at IS NULL ORDER BY name_en");
    }

    public function getBrands(int $comId): array
    {
        $cf = \CompanyFilter::getInstance();
        return $this->fetchAll("SELECT brand_name, id FROM brand WHERE 1=1 " . $cf->andCompanyFilter('brand'));
    }

    public function getTypes(int $comId): array
    {
        $cf = \CompanyFilter::getInstance();
        return $this->fetchAll("SELECT t.name, t.id, t.des, c.cat_name FROM type t LEFT JOIN category c ON t.cat_id=c.id WHERE 1=1 " . $cf->andCompanyFilter('t'));
    }

    public function getModels(): array
    {
        return $this->fetchAll("SELECT m.id, m.type_id, m.model_name, m.des, m.price, m.brand_id FROM model m WHERE m.deleted_at IS NULL");
    }

    public function getTmpProducts(int $prId): array
    {
        return $this->fetchAll("SELECT tmp_product.*, type.name as type_name, type.des as type_des, category.cat_name
            FROM tmp_product
            JOIN type ON tmp_product.type_id=type.id
            LEFT JOIN category ON type.cat_id=category.id
            WHERE tmp_product.pr_id='" . \sql_int($prId) . "'");
    }

    public function getPaymentMethods(int $comId): array
    {
        return $this->fetchAll("SELECT * FROM payment_methods WHERE com_id='$comId' AND is_active=1 ORDER BY method_type");
    }

    public function hasLabour(int $poId): bool
    {
        $r = mysqli_query($this->conn, "SELECT COUNT(*) as cnt FROM product WHERE po_id='" . \sql_int($poId) . "' AND activelabour='1'");
        return $r && intval(mysqli_fetch_assoc($r)['cnt']) > 0;
    }

    private function fetchAll(string $sql): array
    {
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) { while ($row = mysqli_fetch_assoc($result)) $rows[] = $row; }
        return $rows;
    }
}
