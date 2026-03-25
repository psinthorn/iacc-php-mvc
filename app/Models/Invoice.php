<?php
namespace App\Models;

/**
 * Invoice Model - Handles Invoice, Tax Invoice, and Quotation document queries
 * 
 * Replaces SQL from: compl-list.php, compl-view.php, compl-list2.php, qa-list.php
 * Also handles core-function.php cases: compl_list, compl_view, compl_list2
 */
class Invoice extends BaseModel
{
    protected string $table = 'po';
    protected bool $useCompanyFilter = false;

    // =====================================================
    // Invoice List (compl_list) — status >= 4
    // =====================================================

    public function countInvoices(int $comId, string $direction, array $filters): int
    {
        $conds = $this->buildInvoiceConditions($filters);
        $companyJoin = $direction === 'out'
            ? "JOIN company ON pr.cus_id=company.id"
            : "JOIN company ON pr.ven_id=company.id";
        $companyWhere = $direction === 'out'
            ? "pr.ven_id='$comId'"
            : "pr.cus_id='$comId'";

        $sql = "SELECT COUNT(*) as total FROM po
            JOIN pr ON po.ref=pr.id $companyJoin JOIN iv ON po.id=iv.tex
            WHERE po_id_new='' AND $companyWhere AND status>='4' {$conds['search']} {$conds['date']} {$conds['status']}";
        $r = mysqli_query($this->conn, $sql);
        return $r ? intval(mysqli_fetch_assoc($r)['total']) : 0;
    }

    public function getInvoices(int $comId, string $direction, array $filters, int $offset, int $limit): array
    {
        $conds = $this->buildInvoiceConditions($filters);
        if ($direction === 'out') {
            $companyJoin = "JOIN company ON pr.cus_id=company.id";
            $companyWhere = "pr.ven_id='$comId'";
            $extraCols = "countmailinv, status_iv,";
        } else {
            $companyJoin = "JOIN company ON pr.ven_id=company.id";
            $companyWhere = "pr.cus_id='$comId'";
            $extraCols = "";
        }

        $sql = "SELECT po.id as id, $extraCols po.name as name, taxrw as tax,
            DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en,
            DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, status, iv.payment_status
            FROM po JOIN pr ON po.ref=pr.id $companyJoin JOIN iv ON po.id=iv.tex
            WHERE po_id_new='' AND $companyWhere AND status>='4'
            {$conds['search']} {$conds['date']} {$conds['status']}
            ORDER BY iv.id DESC LIMIT $offset, $limit";
        return $this->fetchAll($sql);
    }

    private function buildInvoiceConditions(array $f): array
    {
        $search = '';
        if (!empty($f['search'])) {
            $s = \sql_escape($f['search']);
            $search = " AND (po.name LIKE '%$s%' OR iv.taxrw LIKE '%$s%' OR company.name_en LIKE '%$s%')";
        }
        $date = '';
        if (!empty($f['date_from'])) $date .= " AND iv.createdate >= '" . \sql_escape($f['date_from']) . "'";
        if (!empty($f['date_to'])) $date .= " AND iv.createdate <= '" . \sql_escape($f['date_to']) . "'";
        $status = '';
        if (($f['status'] ?? '') === 'pending') $status = " AND status='4'";
        elseif (($f['status'] ?? '') === 'completed') $status = " AND status='5'";
        return ['search' => $search, 'date' => $date, 'status' => $status];
    }

    // =====================================================
    // Invoice Detail (compl_view)
    // =====================================================

    public function getInvoiceDetail(int $id, int $comId): ?array
    {
        $sql = "SELECT po.name as name, ven_id, vat, cus_id, des, payby, `over`,
            DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, dis,
            DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, ref, pic, status
            FROM pr JOIN po ON pr.id=po.ref
            WHERE po.id='" . \sql_int($id) . "' AND status='4'
            AND (cus_id='$comId' OR ven_id='$comId') AND po_id_new=''";
        $r = mysqli_query($this->conn, $sql);
        return ($r && mysqli_num_rows($r) == 1) ? mysqli_fetch_assoc($r) : null;
    }

    public function getCompanyName(int $id): ?array
    {
        $r = mysqli_query($this->conn, "SELECT name_sh, name_en FROM company WHERE id='" . \sql_int($id) . "'");
        return $r ? mysqli_fetch_assoc($r) : null;
    }

    public function hasLabour(int $poId): bool
    {
        $r = mysqli_query($this->conn, "SELECT max(activelabour) as cklabour FROM product JOIN type ON product.type=type.id WHERE po_id='" . \sql_int($poId) . "'");
        $row = $r ? mysqli_fetch_assoc($r) : null;
        return ($row && $row['cklabour'] == 1);
    }

    public function getProducts(int $poId): array
    {
        $sql = "SELECT type.name as name, product.price as price, discount,
            COALESCE(model.model_name,'') as model, quantity, pack_quantity, activelabour, valuelabour
            FROM product LEFT JOIN type ON product.type=type.id LEFT JOIN model ON product.model=model.id
            WHERE po_id='" . \sql_int($poId) . "'";
        return $this->fetchAll($sql);
    }

    public function getPayments(int $poId): array
    {
        $sql = "SELECT DATE_FORMAT(date,'%d-%m-%Y') as date, value, id, volumn FROM pay WHERE po_id='" . \sql_int($poId) . "'";
        return $this->fetchAll($sql);
    }

    public function getPaymentTotal(int $poId): float
    {
        $r = mysqli_query($this->conn, "SELECT sum(volumn) as stotal FROM pay WHERE po_id='" . \sql_int($poId) . "'");
        $row = $r ? mysqli_fetch_assoc($r) : null;
        return floatval($row['stotal'] ?? 0);
    }

    public function getPoRef(int $poId): ?array
    {
        $r = mysqli_query($this->conn, "SELECT ref FROM po WHERE id='" . \sql_int($poId) . "'");
        return $r ? mysqli_fetch_assoc($r) : null;
    }

    public function getPaymentMethods(int $comId): array
    {
        return $this->fetchAll("SELECT payment_name, id FROM payment WHERE com_id='$comId'");
    }

    // =====================================================
    // Payment recording (core-function.php case compl_list)
    // =====================================================

    public function recordPayment(int $comId, int $poId, string $paymentMethodId, string $remark, string $amount): void
    {
        $args = [];
        $args['table'] = 'pay';
        $args['value'] = "NULL,'$comId','" . \sql_int($poId) . "','" . \sql_escape($paymentMethodId) . "','" . \sql_escape($remark) . "','" . \sql_escape($amount) . "','" . date("Y-m-d") . "',NULL";
        $this->hard->insertDB($args);
    }

    public function updatePaymentMethod(int $prId, string $payby): void
    {
        $args = ['table' => 'pr', 'value' => "payby='" . \sql_escape($payby) . "'", 'condition' => "id='" . \sql_int($prId) . "'"];
        $this->hard->updateDb($args);
    }

    // =====================================================
    // Tax Invoice List (compl_list2) — status = 5
    // =====================================================

    public function countTaxInvoices(int $comId, string $direction, array $filters): int
    {
        $conds = $this->buildTaxConditions($filters);
        $companyJoin = $direction === 'out'
            ? "JOIN company ON pr.cus_id=company.id"
            : "JOIN company ON pr.ven_id=company.id";
        $companyWhere = $direction === 'out'
            ? "ven_id='$comId'"
            : "pr.cus_id='$comId'";

        $sql = "SELECT COUNT(*) as total FROM po
            JOIN pr ON po.ref=pr.id $companyJoin JOIN iv ON po.id=iv.tex
            WHERE po_id_new='' AND $companyWhere AND status='5'
            AND iv.texiv_rw IS NOT NULL AND iv.texiv_rw != '' {$conds['search']} {$conds['date']}";
        $r = mysqli_query($this->conn, $sql);
        return $r ? intval(mysqli_fetch_assoc($r)['total'] ?? 0) : 0;
    }

    public function getTaxInvoices(int $comId, string $direction, array $filters, int $offset, int $limit): array
    {
        $conds = $this->buildTaxConditions($filters);
        if ($direction === 'out') {
            $companyJoin = "JOIN company ON pr.cus_id=company.id";
            $companyWhere = "ven_id='$comId'";
            $extra = "countmailtax,";
        } else {
            $companyJoin = "JOIN company ON pr.ven_id=company.id";
            $companyWhere = "pr.cus_id='$comId'";
            $extra = "iv.id as tax,";
        }

        $sql = "SELECT po.id as id, $extra po.name as name, iv.texiv_rw,
            DATE_FORMAT(iv.texiv_create,'%d-%m-%Y') as texiv_create, name_en, status
            FROM po JOIN pr ON po.ref=pr.id $companyJoin JOIN iv ON po.id=iv.tex
            WHERE po_id_new='' AND $companyWhere AND status='5'
            AND iv.texiv_rw IS NOT NULL AND iv.texiv_rw != '' {$conds['search']} {$conds['date']}
            ORDER BY iv.texiv_rw DESC LIMIT $offset, $limit";
        return $this->fetchAll($sql);
    }

    private function buildTaxConditions(array $f): array
    {
        $search = '';
        if (!empty($f['search'])) {
            $s = \sql_escape($f['search']);
            $search = " AND (po.name LIKE '%$s%' OR name_en LIKE '%$s%' OR iv.texiv_rw LIKE '%$s%')";
        }
        $date = '';
        if (!empty($f['date_from'])) $date .= " AND iv.texiv_create >= '" . \sql_escape($f['date_from']) . "'";
        if (!empty($f['date_to'])) $date .= " AND iv.texiv_create <= '" . \sql_escape($f['date_to']) . "'";
        return ['search' => $search, 'date' => $date];
    }

    // =====================================================
    // Tax Invoice actions (core-function.php case compl_list2)
    // =====================================================

    public function voidInvoice(int $prId): void
    {
        $po = mysqli_fetch_assoc(mysqli_query($this->conn,
            "SELECT po.id as po_id, ven_id FROM pr JOIN po ON pr.id=po.ref WHERE po_id_new='' AND pr.id='" . \sql_int($prId) . "'"));
        if ($po) {
            $args = ['table' => 'iv', 'value' => "status_iv='2'", 'condition' => "tex='" . \sql_int($po['po_id']) . "'"];
            $this->hard->updateDb($args);
        }
    }

    public function completeTaxInvoice(int $prId): void
    {
        // Update PR status to 5
        $args = ['table' => 'pr', 'value' => "status='5'", 'condition' => "id='" . \sql_int($prId) . "'"];
        $this->hard->updateDb($args);

        // Get PO and vendor
        $po = mysqli_fetch_assoc(mysqli_query($this->conn,
            "SELECT po.id as po_id, ven_id FROM pr JOIN po ON pr.id=po.ref WHERE po_id_new='' AND pr.id='" . \sql_int($prId) . "'"));
        if (!$po) return;

        // Get max tax invoice number
        $max = mysqli_fetch_assoc(mysqli_query($this->conn,
            "SELECT max(texiv) as max_id FROM iv WHERE cus_id='" . $po['ven_id'] . "'"));
        $newNum = (number_format($max['max_id'] ?? 0) + 1);
        $rw = (date("y") + 43) . str_pad($newNum, 6, '0', STR_PAD_LEFT);

        $args2 = ['table' => 'iv',
            'value' => "texiv='$newNum', texiv_rw='$rw', texiv_create='" . date("Y-m-d") . "', status_iv='1'",
            'condition' => "tex='" . $po['po_id'] . "'"];
        $this->hard->updateDb($args2);
    }

    // =====================================================
    // Quotation List (qa_list) — status = 1
    // =====================================================

    public function countQuotations(int $comId, string $direction, array $filters): int
    {
        $conds = $this->buildQuotationConditions($filters);
        $companyJoin = $direction === 'out'
            ? "JOIN company ON pr.cus_id=company.id"
            : "JOIN company ON pr.ven_id=company.id";
        $companyWhere = $direction === 'out'
            ? "ven_id='$comId'"
            : "cus_id='$comId'";

        $sql = "SELECT COUNT(*) as total FROM po
            JOIN pr ON po.ref=pr.id $companyJoin
            WHERE po_id_new='' AND $companyWhere AND status='1' {$conds['search']} {$conds['date']}";
        $r = mysqli_query($this->conn, $sql);
        return $r ? intval(mysqli_fetch_assoc($r)['total'] ?? 0) : 0;
    }

    public function getQuotations(int $comId, string $direction, array $filters, int $offset, int $limit): array
    {
        $conds = $this->buildQuotationConditions($filters);
        $extra = $direction === 'out' ? "mailcount," : "";
        $companyJoin = $direction === 'out'
            ? "JOIN company ON pr.cus_id=company.id"
            : "JOIN company ON pr.ven_id=company.id";
        $companyWhere = $direction === 'out'
            ? "ven_id='$comId'"
            : "cus_id='$comId'";

        $sql = "SELECT po.id as id, po.name as name, po.tax as tax, $extra cancel,
            DATE_FORMAT(valid_pay,'%d-%m-%Y') as valid_pay, name_en, vat, dis, `over`,
            DATE_FORMAT(deliver_date,'%d-%m-%Y') as deliver_date, status
            FROM po JOIN pr ON po.ref=pr.id $companyJoin
            WHERE po_id_new='' AND $companyWhere AND status='1' {$conds['search']} {$conds['date']}
            ORDER BY cancel, po.id DESC LIMIT $offset, $limit";
        return $this->fetchAll($sql);
    }

    public function calculatePoTotal(int $poId): array
    {
        $hasLabour = $this->hasLabour($poId);
        $products = $this->getProducts($poId);
        $summary = 0;
        foreach ($products as $p) {
            if ($hasLabour) {
                $equip = $p['price'] * $p['quantity'];
                $labour = ($p['valuelabour'] * $p['activelabour']) * $p['quantity'];
                $summary += $equip + $labour;
            } else {
                $summary += $p['price'] * $p['quantity'];
            }
        }
        return ['summary' => $summary];
    }

    private function buildQuotationConditions(array $f): array
    {
        $search = '';
        if (!empty($f['search'])) {
            $s = \sql_escape($f['search']);
            $search = " AND (po.name LIKE '%$s%' OR po.tax LIKE '%$s%' OR company.name_en LIKE '%$s%')";
        }
        $date = '';
        if (!empty($f['date_from'])) $date .= " AND po.date >= '" . \sql_escape($f['date_from']) . "'";
        if (!empty($f['date_to'])) $date .= " AND po.date <= '" . \sql_escape($f['date_to']) . "'";
        return ['search' => $search, 'date' => $date];
    }

    // =====================================================
    // Helpers
    // =====================================================

    private function fetchAll(string $sql): array
    {
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) { while ($row = mysqli_fetch_assoc($result)) $rows[] = $row; }
        return $rows;
    }
}
