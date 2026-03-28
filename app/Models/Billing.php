<?php
namespace App\Models;

/**
 * Billing Model
 * Replaces SQL from: billing.php, billing-make.php, core-function.php case billing
 */
class Billing extends BaseModel
{
    protected string $table = 'billing';
    protected bool $useCompanyFilter = false;

    public function getStats(int $comId): array
    {
        $sql = "SELECT
            COUNT(DISTINCT COALESCE(bi.bil_id, CONCAT('inv_', iv.id))) as total,
            COUNT(DISTINCT bi.bil_id) as with_billing,
            COUNT(DISTINCT CASE WHEN bi.bil_id IS NULL THEN iv.id END) as without_billing,
            COALESCE(SUM(DISTINCT CASE WHEN b.bil_id IS NOT NULL THEN b.price ELSE 0 END), 0) as total_amount
            FROM iv JOIN po ON iv.tex=po.id JOIN pr ON po.ref=pr.id
            JOIN company ON (CASE WHEN pr.payby>0 THEN pr.payby ELSE pr.cus_id END)=company.id
            LEFT JOIN billing_items bi ON bi.inv_id=iv.id
            LEFT JOIN billing b ON bi.bil_id=b.bil_id
            WHERE pr.status>=3 AND po.po_id_new='' AND (pr.ven_id='$comId' OR pr.cus_id='$comId')";
        $r = mysqli_query($this->conn, $sql);
        return $r ? mysqli_fetch_assoc($r) : ['total'=>0,'with_billing'=>0,'without_billing'=>0,'total_amount'=>0];
    }

    public function countBillingItems(int $comId, array $filters): int
    {
        $conds = $this->buildConditions($filters);
        $sql = "SELECT COUNT(DISTINCT COALESCE(bi.bil_id, CONCAT('inv_', iv.id))) as total
            FROM iv JOIN po ON iv.tex=po.id JOIN pr ON po.ref=pr.id
            JOIN company ON (CASE WHEN pr.payby>0 THEN pr.payby ELSE pr.cus_id END)=company.id
            LEFT JOIN billing_items bi ON bi.inv_id=iv.id
            LEFT JOIN billing b ON bi.bil_id=b.bil_id
            WHERE pr.status>=3 AND po.po_id_new='' AND (pr.ven_id='$comId' OR pr.cus_id='$comId') $conds";
        $r = mysqli_query($this->conn, $sql);
        return $r ? intval(mysqli_fetch_assoc($r)['total']) : 0;
    }

    public function getBillingItems(int $comId, array $filters, int $offset, int $limit): array
    {
        $conds = $this->buildConditions($filters);
        return $this->fetchAll("SELECT iv.id, iv.tex, po.tax, po.name, DATE_FORMAT(iv.createdate,'%d-%m-%Y') as createdate,
            company.name_en, pr.cus_id, pr.ven_id, pr.payby, bi.bil_id, b.des as bil_des,
            DATE_FORMAT(b.created_at,'%d-%m-%Y') as bil_date, bi.amount,
            (SELECT SUM((product.price * product.quantity) + (product.valuelabour * product.activelabour * product.quantity) - (product.discount * product.quantity))
             FROM product WHERE product.po_id=po.id) as subtotal,
            po.vat, po.dis as discount, po.over as withholding
            FROM iv JOIN po ON iv.tex=po.id JOIN pr ON po.ref=pr.id
            JOIN company ON (CASE WHEN pr.payby>0 THEN pr.payby ELSE pr.cus_id END)=company.id
            LEFT JOIN billing_items bi ON bi.inv_id=iv.id
            LEFT JOIN billing b ON bi.bil_id=b.bil_id
            WHERE pr.status>=3 AND po.po_id_new='' AND (pr.ven_id='$comId' OR pr.cus_id='$comId') $conds
            ORDER BY COALESCE(b.created_at, iv.createdate) DESC LIMIT $offset, $limit");
    }

    private function buildConditions(array $f): string
    {
        $cond = '';
        if (!empty($f['search'])) {
            $s = \sql_escape($f['search']);
            $cond .= " AND (po.name LIKE '%$s%' OR po.tax LIKE '%$s%' OR company.name_en LIKE '%$s%')";
        }
        if (!empty($f['status'])) {
            if ($f['status'] === 'billed' || $f['status'] === 'with_billing') $cond .= " AND bi.bil_id IS NOT NULL";
            elseif ($f['status'] === 'unbilled' || $f['status'] === 'without_billing') $cond .= " AND bi.bil_id IS NULL";
        }
        if (!empty($f['date_from'])) $cond .= " AND iv.createdate >= '" . \sql_escape($f['date_from']) . "'";
        if (!empty($f['date_to'])) $cond .= " AND iv.createdate <= '" . \sql_escape($f['date_to']) . "'";
        return $cond;
    }

    /**
     * Count billing groups (billing notes + unbilled invoices counted individually)
     */
    public function countBillingGroups(int $comId, array $filters): int
    {
        $searchCond = '';
        $dateCond = '';
        if (!empty($filters['search'])) {
            $s = \sql_escape($filters['search']);
            $searchCond = " AND (po.name LIKE '%$s%' OR po.tax LIKE '%$s%' OR company.name_en LIKE '%$s%')";
        }
        if (!empty($filters['date_from'])) $dateCond .= " AND iv.createdate >= '" . \sql_escape($filters['date_from']) . "'";
        if (!empty($filters['date_to'])) $dateCond .= " AND iv.createdate <= '" . \sql_escape($filters['date_to']) . "'";

        $base = "FROM iv JOIN po ON iv.tex=po.id JOIN pr ON po.ref=pr.id
            JOIN company ON (CASE WHEN pr.payby>0 THEN pr.payby ELSE pr.cus_id END)=company.id
            LEFT JOIN billing_items bi ON bi.inv_id=iv.id
            LEFT JOIN billing b ON bi.bil_id=b.bil_id
            WHERE iv.createdate = (SELECT MAX(iv2.createdate) FROM iv iv2 WHERE iv2.id = iv.id)
            AND pr.status>=3 AND po.po_id_new='' AND (pr.ven_id='$comId' OR pr.cus_id='$comId') $searchCond $dateCond";

        $status = $filters['status'] ?? '';
        if ($status === 'billed') {
            $sql = "SELECT COUNT(DISTINCT b.bil_id) as cnt $base AND bi.bil_id IS NOT NULL";
        } elseif ($status === 'unbilled') {
            $sql = "SELECT COUNT(*) as cnt $base AND bi.bil_id IS NULL";
        } else {
            // billing notes count + unbilled invoices count
            $sql = "SELECT (SELECT COUNT(DISTINCT b.bil_id) $base AND bi.bil_id IS NOT NULL)
                    + (SELECT COUNT(*) $base AND bi.bil_id IS NULL) as cnt";
        }
        $r = mysqli_query($this->conn, $sql);
        return $r ? intval(mysqli_fetch_assoc($r)['cnt']) : 0;
    }

    /**
     * Get billing groups: billing notes (grouped) + unbilled invoices (flat)
     * Returns array of rows: billing notes first (with inv_count, total_amount), then unbilled invoices
     */
    public function getBillingGroups(int $comId, array $filters, int $offset, int $limit): array
    {
        $searchCond = '';
        $dateCond = '';
        if (!empty($filters['search'])) {
            $s = \sql_escape($filters['search']);
            $searchCond = " AND (po.name LIKE '%$s%' OR po.tax LIKE '%$s%' OR company.name_en LIKE '%$s%')";
        }
        if (!empty($filters['date_from'])) $dateCond .= " AND iv.createdate >= '" . \sql_escape($filters['date_from']) . "'";
        if (!empty($filters['date_to'])) $dateCond .= " AND iv.createdate <= '" . \sql_escape($filters['date_to']) . "'";

        $status = $filters['status'] ?? '';

        $parts = [];

        // Part 1: Billing notes (grouped)
        if ($status !== 'unbilled') {
            $parts[] = "(SELECT 'billing' as row_type, b.bil_id, CONCAT('BN-', LPAD(b.bil_id, 6, '0')) as display_id,
                MAX(b.des) as description, DATE_FORMAT(MAX(b.created_at), '%d-%m-%Y') as display_date,
                CASE WHEN MAX(b.price) > 0 THEN MAX(b.price) ELSE SUM(bi.amount) END as total_amount,
                MAX(b.customer_id) as customer_id,
                COUNT(DISTINCT bi.id) as inv_count,
                MAX(company.name_en) as customer_name,
                MAX(b.created_at) as sort_date,
                '' as tex, 0 as subtotal, 0 as vat, 0 as discount_pct, 0 as withholding
                FROM billing b
                JOIN billing_items bi ON bi.bil_id=b.bil_id
                JOIN iv ON bi.inv_id=iv.id
                    AND iv.createdate = (SELECT MAX(iv2.createdate) FROM iv iv2 WHERE iv2.id = iv.id)
                JOIN po ON iv.tex=po.id
                JOIN pr ON po.ref=pr.id
                JOIN company ON (CASE WHEN pr.payby>0 THEN pr.payby ELSE pr.cus_id END)=company.id
                WHERE pr.status>=3 AND po.po_id_new='' AND (pr.ven_id='$comId' OR pr.cus_id='$comId') $searchCond $dateCond
                GROUP BY b.bil_id)";
        }

        // Part 2: Unbilled invoices (flat rows)
        if ($status !== 'billed') {
            $parts[] = "(SELECT 'unbilled' as row_type, 0 as bil_id, po.tax as display_id,
                po.name as description, DATE_FORMAT(iv.createdate, '%d-%m-%Y') as display_date,
                0 as total_amount, 0 as customer_id, 0 as inv_count,
                company.name_en as customer_name,
                iv.createdate as sort_date,
                iv.tex as tex,
                (SELECT SUM((product.price * product.quantity) + (product.valuelabour * product.activelabour * product.quantity) - (product.discount * product.quantity))
                 FROM product WHERE product.po_id=po.id) as subtotal,
                po.vat, po.dis as discount_pct, po.over as withholding
                FROM iv
                JOIN po ON iv.tex=po.id
                JOIN pr ON po.ref=pr.id
                JOIN company ON (CASE WHEN pr.payby>0 THEN pr.payby ELSE pr.cus_id END)=company.id
                LEFT JOIN billing_items bi ON bi.inv_id=iv.id
                WHERE bi.bil_id IS NULL AND pr.status>=3 AND po.po_id_new='' AND (pr.ven_id='$comId' OR pr.cus_id='$comId') $searchCond $dateCond)";
        }

        if (empty($parts)) return [];

        $sql = implode(" UNION ALL ", $parts) . " ORDER BY sort_date DESC LIMIT $offset, $limit";
        return $this->fetchAll($sql);
    }

    /**
     * Get invoices for a specific billing note (for AJAX expand)
     */
    public function getBillingNoteInvoices(int $bilId): array
    {
        $sql = "SELECT bi.inv_id, bi.amount, iv.tex as po_id,
            po.tax as po_number, po.name as po_name,
            iv.texiv_rw as inv_no,
            DATE_FORMAT(iv.createdate, '%d-%m-%Y') as invoice_date,
            pr.des as pr_description,
            (SELECT SUM((product.price * product.quantity) + (product.valuelabour * product.activelabour * product.quantity) - (product.discount * product.quantity))
             FROM product WHERE product.po_id=po.id) as subtotal,
            po.vat, po.dis as discount_pct, po.over as withholding
            FROM billing_items bi
            JOIN iv ON bi.inv_id = iv.id
                AND iv.createdate = (SELECT MAX(iv2.createdate) FROM iv iv2 WHERE iv2.id = iv.id)
            JOIN po ON iv.tex = po.id
            JOIN pr ON po.ref = pr.id
            WHERE bi.bil_id = '" . \sql_int($bilId) . "'
            AND po.po_id_new = ''
            ORDER BY iv.createdate ASC";
        return $this->fetchAll($sql);
    }

    private function buildUnbilledWhere(int $customerId, string $dateFrom = '', string $dateTo = '', string $search = ''): string
    {
        $where = "(CASE WHEN pr.payby>0 THEN pr.payby ELSE pr.cus_id END)='" . \sql_int($customerId) . "'
            AND po.po_id_new='' AND pr.status>=3
            AND iv.id NOT IN (SELECT inv_id FROM billing_items)";
        if ($dateFrom !== '') {
            $where .= " AND iv.createdate >= '" . \sql_str($dateFrom) . "'";
        }
        if ($dateTo !== '') {
            $where .= " AND iv.createdate <= '" . \sql_str($dateTo) . "'";
        }
        if ($search !== '') {
            $s = \sql_str($search);
            $where .= " AND (pr.des LIKE '%$s%' OR iv.texiv_rw LIKE '%$s%' OR po.name LIKE '%$s%')";
        }
        return $where;
    }

    public function countUnbilledInvoices(int $customerId, int $comId, string $dateFrom = '', string $dateTo = '', string $search = ''): int
    {
        $where = $this->buildUnbilledWhere($customerId, $dateFrom, $dateTo, $search);
        $r = mysqli_query($this->conn, "SELECT COUNT(*) as cnt
            FROM iv JOIN po ON iv.tex=po.id JOIN pr ON po.ref=pr.id
            WHERE $where");
        return ($r && $row = mysqli_fetch_assoc($r)) ? intval($row['cnt']) : 0;
    }

    public function getUnbilledInvoices(int $customerId, int $comId, string $dateFrom = '', string $dateTo = '', string $search = '', int $offset = 0, int $limit = 0): array
    {
        $where = $this->buildUnbilledWhere($customerId, $dateFrom, $dateTo, $search);
        $sql = "SELECT iv.id, iv.tex as po_id, iv.texiv_rw as inv_no, po.tax, DATE_FORMAT(iv.createdate,'%d-%m-%Y') as iv_date,
            pr.des,
            (SELECT SUM((product.price * product.quantity) + (product.valuelabour * product.activelabour * product.quantity) - (product.discount * product.quantity))
             FROM product WHERE product.po_id=po.id) as subtotal,
            po.vat, po.dis as discount, po.over as withholding
            FROM iv JOIN po ON iv.tex=po.id JOIN pr ON po.ref=pr.id
            WHERE $where
            ORDER BY iv.createdate DESC";
        if ($limit > 0) {
            $sql .= " LIMIT $offset, $limit";
        }
        return $this->fetchAll($sql);
    }

    public function getCustomersWithUnbilledInvoices(int $comId): array
    {
        return $this->fetchAll("SELECT DISTINCT 
            CASE WHEN pr.payby>0 THEN pr.payby ELSE pr.cus_id END as id,
            company.name_en, company.name_sh
            FROM iv 
            JOIN po ON iv.tex=po.id 
            JOIN pr ON po.ref=pr.id
            JOIN company ON (CASE WHEN pr.payby>0 THEN pr.payby ELSE pr.cus_id END)=company.id
            WHERE pr.status>=3 AND po.po_id_new='' 
            AND (pr.ven_id='$comId' OR pr.cus_id='$comId')
            AND iv.id NOT IN (SELECT inv_id FROM billing_items)
            ORDER BY company.name_en");
    }

    public function getCustomerById(int $customerId): ?array
    {
        $r = mysqli_query($this->conn, "SELECT id, name_en, name_sh, tax, phone, email FROM company WHERE id='" . \sql_int($customerId) . "'");
        return ($r && mysqli_num_rows($r) > 0) ? mysqli_fetch_assoc($r) : null;
    }

    public function getCustomerFromInvoice(int $invId): ?array
    {
        return $this->getCustomerFromPO($invId);
    }

    /**
     * Lookup customer from PO id (iv.tex = po.id = primary key, unique).
     * iv.id is NOT unique, so always use iv.tex for reliable lookups.
     */
    public function getCustomerFromPO(int $poId): ?array
    {
        $r = mysqli_query($this->conn, "SELECT CASE WHEN pr.payby>0 THEN pr.payby ELSE pr.cus_id END as customer_id
            FROM iv JOIN po ON iv.tex=po.id JOIN pr ON po.ref=pr.id WHERE iv.tex='" . \sql_int($poId) . "' LIMIT 1");
        if (!$r || mysqli_num_rows($r) == 0) return null;
        $custId = mysqli_fetch_assoc($r)['customer_id'];
        $r2 = mysqli_query($this->conn, "SELECT id, name_en, name_sh, tax, phone, email FROM company WHERE id='" . \sql_int($custId) . "'");
        return ($r2 && mysqli_num_rows($r2) > 0) ? mysqli_fetch_assoc($r2) : null;
    }

    public function createBilling(array $data, int $comId): void
    {
        $des = \sql_escape($data['des']);
        $price = floatval(str_replace(',', '', $data['price'] ?? '0'));
        $customer_id = \sql_int($data['customer_id'] ?? 0);
        $invoices = $data['invoices'] ?? [];
        if (empty($invoices) && !empty($data['inv_id'])) {
            $invoices = [\sql_int($data['inv_id'])];
        }

        if (!empty($invoices)) {
            $first_inv_id = \sql_int($invoices[0]);
            $sql = "INSERT INTO billing (bil_id, des, inv_id, customer_id, price, created_at)
                    VALUES (NULL, '$des', '$first_inv_id', '$customer_id', '$price', NOW())";
            mysqli_query($this->conn, $sql);
            $bilId = mysqli_insert_id($this->conn);

            foreach ($invoices as $inv_id) {
                $inv_id = \sql_int($inv_id);
                $amount = floatval($this->calculateInvoiceAmount($inv_id));
                mysqli_query($this->conn, "INSERT INTO billing_items (bil_id, inv_id, amount) VALUES ('" . intval($bilId) . "', '$inv_id', '$amount')");
            }
        }
    }

    public function updateBilling(int $bilId, array $data): void
    {
        $des = \sql_escape($data['des']);
        $price = floatval(str_replace(',', '', $data['price'] ?? '0'));
        mysqli_query($this->conn, "UPDATE billing SET des='$des', price='$price' WHERE bil_id='" . \sql_int($bilId) . "'");
    }

    public function deleteBilling(int $bilId): void
    {
        mysqli_query($this->conn, "DELETE FROM billing_items WHERE bil_id='" . \sql_int($bilId) . "'");
        mysqli_query($this->conn, "DELETE FROM billing WHERE bil_id='" . \sql_int($bilId) . "'");
    }

    /**
     * Get a single billing note with customer info
     */
    public function getBillingById(int $bilId): ?array
    {
        $sql = "SELECT b.bil_id, b.des, b.price, b.customer_id, b.created_at,
            DATE_FORMAT(b.created_at, '%d/%m/%Y') as billing_date,
            c.name_en, c.name_sh, c.tax as cust_tax, c.phone as cust_phone, c.email as cust_email
            FROM billing b
            LEFT JOIN company c ON b.customer_id = c.id
            WHERE b.bil_id = '" . \sql_int($bilId) . "'";
        $r = mysqli_query($this->conn, $sql);
        return ($r && mysqli_num_rows($r) > 0) ? mysqli_fetch_assoc($r) : null;
    }

    /**
     * Get all invoices linked to a billing note
     */
    public function getBillingInvoices(int $bilId): array
    {
        $sql = "SELECT bi.inv_id, bi.amount,
            po.tax as po_number, po.name as po_name,
            iv.texiv_rw as inv_no,
            DATE_FORMAT(iv.createdate, '%d/%m/%Y') as invoice_date,
            pr.des as pr_description,
            (SELECT SUM((product.price * product.quantity) + (product.valuelabour * product.activelabour * product.quantity) - (product.discount * product.quantity))
             FROM product WHERE product.po_id=po.id) as subtotal,
            po.vat, po.dis as discount, po.over as withholding
            FROM billing_items bi
            JOIN iv ON bi.inv_id = iv.id
            JOIN po ON iv.tex = po.id
            JOIN pr ON po.ref = pr.id
            WHERE bi.bil_id = '" . \sql_int($bilId) . "'
            AND po.po_id_new = ''
            AND iv.createdate = (SELECT MAX(iv2.createdate) FROM iv iv2 WHERE iv2.id = iv.id)
            ORDER BY iv.createdate ASC";
        return $this->fetchAll($sql);
    }

    /**
     * Get vendor/company info with address for billing print
     */
    public function getCompanyWithAddress(int $comId): ?array
    {
        $sql = "SELECT c.id, c.name_en, c.name_sh, c.tax, c.phone, c.email, c.fax,
            ca.adr_tax, ca.city_tax, ca.district_tax, ca.province_tax, ca.zip_tax,
            b.logo
            FROM company c
            LEFT JOIN company_addr ca ON c.id = ca.com_id AND ca.valid_end = '0000-00-00'
            LEFT JOIN brand b ON c.id = b.company_id
            WHERE c.id = '" . \sql_int($comId) . "' LIMIT 1";
        $r = mysqli_query($this->conn, $sql);
        return ($r && mysqli_num_rows($r) > 0) ? mysqli_fetch_assoc($r) : null;
    }

    private function calculateInvoiceAmount(int $invId): float
    {
        $sql = "SELECT
            (SELECT SUM((product.price * product.quantity) + (product.valuelabour * product.activelabour * product.quantity) - (product.discount * product.quantity))
             FROM product WHERE product.po_id=po.id) as subtotal,
            po.vat, po.dis as discount, po.over as withholding
            FROM iv JOIN po ON iv.tex=po.id WHERE iv.id='" . \sql_int($invId) . "' AND po.po_id_new='' LIMIT 1";
        $r = mysqli_query($this->conn, $sql);
        if (!$r || mysqli_num_rows($r) == 0) return 0;
        $d = mysqli_fetch_assoc($r);
        $sub = floatval($d['subtotal'] ?? 0);
        $after = $sub - floatval($d['discount'] ?? 0);
        $vatAmt = $after * (floatval($d['vat'] ?? 0) / 100);
        $withAmt = $after * (floatval($d['withholding'] ?? 0) / 100);
        return $after + $vatAmt - $withAmt;
    }

    private function fetchAll(string $sql): array
    {
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) { while ($row = mysqli_fetch_assoc($result)) $rows[] = $row; }
        return $rows;
    }
}
