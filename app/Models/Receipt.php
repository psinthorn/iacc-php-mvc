<?php
namespace App\Models;

/**
 * Receipt Model
 * Replaces SQL from: rep-list.php, rep-make.php, rep-view.php, core-function.php case receipt_list
 */
class Receipt extends BaseModel
{
    protected string $table = 'receipt';
    protected bool $useCompanyFilter = false;

    public function getStats(int $comId): array
    {
        $sql = "SELECT COUNT(*) as total,
            SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status='draft' THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM receipt WHERE vender='$comId' AND deleted_at IS NULL";
        $r = mysqli_query($this->conn, $sql);
        return $r ? mysqli_fetch_assoc($r) : ['total'=>0,'confirmed'=>0,'draft'=>0,'cancelled'=>0];
    }

    public function countReceipts(int $comId, array $filters): int
    {
        $conds = $this->buildConditions($filters);
        $sql = "SELECT COUNT(*) as total FROM receipt r WHERE r.vender='$comId' AND r.deleted_at IS NULL $conds";
        $r = mysqli_query($this->conn, $sql);
        return $r ? intval(mysqli_fetch_assoc($r)['total']) : 0;
    }

    public function getReceipts(int $comId, array $filters, int $offset, int $limit): array
    {
        $conds = $this->buildConditions($filters);
        return $this->fetchAll("SELECT r.id, r.name, r.email, r.phone, DATE_FORMAT(r.createdate,'%d-%m-%Y') as createdate,
            r.description, r.rep_rw, r.brand, r.vender, r.payment_method, r.status, r.invoice_id, r.quotation_id,
            r.source_type, r.include_vat, i.taxrw, p.tax as po_tax
            FROM receipt r LEFT JOIN iv i ON r.invoice_id=i.id LEFT JOIN po p ON r.quotation_id=p.id
            WHERE r.vender='$comId' AND r.deleted_at IS NULL $conds ORDER BY r.id DESC LIMIT $offset, $limit");
    }

    private function buildConditions(array $f): string
    {
        $cond = '';
        if (!empty($f['search'])) {
            $s = \sql_escape($f['search']);
            $cond .= " AND (r.name LIKE '%$s%' OR r.email LIKE '%$s%' OR r.phone LIKE '%$s%' OR r.rep_rw LIKE '%$s%')";
        }
        if (!empty($f['status'])) $cond .= " AND r.status='" . \sql_escape($f['status']) . "'";
        if (!empty($f['date_from'])) $cond .= " AND r.createdate >= '" . \sql_escape($f['date_from']) . "'";
        if (!empty($f['date_to'])) $cond .= " AND r.createdate <= '" . \sql_escape($f['date_to']) . "'";
        return $cond;
    }

    public function findReceipt(int $id, int $comId): ?array
    {
        $id = \sql_int($id);
        $r = mysqli_query($this->conn, "
            SELECT r.*, i.taxrw, qpo.tax as po_tax,
                   COALESCE(ic.name_en, qc.name_en) as cust_name,
                   COALESCE(ic.email, qc.email) as cust_email,
                   COALESCE(ic.phone, qc.phone) as cust_phone,
                   COALESCE(ic.fax, qc.fax) as cust_fax,
                   COALESCE(ic.tax, qc.tax) as cust_tax,
                   COALESCE(ica.adr_tax, qca.adr_tax) as cust_address,
                   COALESCE(ica.city_tax, qca.city_tax) as cust_city,
                   COALESCE(ica.district_tax, qca.district_tax) as cust_district,
                   COALESCE(ica.province_tax, qca.province_tax) as cust_province,
                   COALESCE(ica.zip_tax, qca.zip_tax) as cust_zip,
                   vc.name_en as vendor_name, vc.logo as vendor_logo,
                   vc.phone as vendor_phone, vc.fax as vendor_fax,
                   vc.email as vendor_email, vc.tax as vendor_tax,
                   va.adr_tax as vendor_address, va.city_tax as vendor_city,
                   va.district_tax as vendor_district, va.province_tax as vendor_province,
                   va.zip_tax as vendor_zip,
                   COALESCE(ipo.tax, qpo.tax) as source_doc_no,
                   COALESCE(ipo.date, qpo.date) as source_doc_date
            FROM receipt r
            LEFT JOIN iv i ON r.invoice_id = i.id
            LEFT JOIN po qpo ON r.quotation_id = qpo.id
            LEFT JOIN po ipo ON r.invoice_id = ipo.id
            LEFT JOIN pr ipr ON ipo.ref = ipr.id
            LEFT JOIN company ic ON ipr.cus_id = ic.id
            LEFT JOIN company_addr ica ON ic.id = ica.com_id AND ica.deleted_at IS NULL
            LEFT JOIN pr qpr ON qpo.ref = qpr.id
            LEFT JOIN company qc ON qpr.cus_id = qc.id
            LEFT JOIN company_addr qca ON qc.id = qca.com_id AND qca.deleted_at IS NULL
            LEFT JOIN company vc ON r.vender = vc.id
            LEFT JOIN company_addr va ON vc.id = va.com_id AND va.deleted_at IS NULL
            WHERE r.id='$id' AND r.vender='$comId'
        ");
        return ($r && mysqli_num_rows($r) > 0) ? mysqli_fetch_assoc($r) : null;
    }

    public function getReceiptProducts(int $id): array
    {
        return $this->fetchAll("SELECT p.*, t.name as type_name, b.brand_name, m.model_name
            FROM product p LEFT JOIN type t ON p.type=t.id LEFT JOIN brand b ON p.ban_id=b.id
            LEFT JOIN model m ON p.model=m.id WHERE p.re_id='" . \sql_int($id) . "'");
    }

    public function getSourceProducts(int $sourceId, string $sourceType): array
    {
        if ($sourceType === 'invoice' || $sourceType === 'quotation') {
            return $this->fetchAll("SELECT p.*, t.name as type_name, b.brand_name, m.model_name
                FROM product p LEFT JOIN type t ON p.type=t.id LEFT JOIN brand b ON p.ban_id=b.id
                LEFT JOIN model m ON p.model=m.id WHERE p.po_id='" . \sql_int($sourceId) . "'");
        }
        return [];
    }

    public function getQuotations(int $comId): array
    {
        return $this->fetchAll("SELECT po.id, po.tax, company.name_en, DATE_FORMAT(po.date,'%d-%m-%Y') as po_date
            FROM po JOIN pr ON po.ref=pr.id JOIN company ON pr.cus_id=company.id
            WHERE pr.ven_id='$comId' AND pr.status='1' AND po.po_id_new='' ORDER BY po.id DESC LIMIT 100");
    }

    public function getInvoices(int $comId): array
    {
        return $this->fetchAll("SELECT po.id, iv.taxrw, company.name_en, DATE_FORMAT(iv.createdate,'%d-%m-%Y') as iv_date
            FROM po JOIN pr ON po.ref=pr.id JOIN company ON pr.cus_id=company.id
            JOIN iv ON po.id=iv.tex
            WHERE pr.ven_id='$comId' AND po.po_id_new='' ORDER BY iv.id DESC LIMIT 100");
    }

    public function createReceipt(array $data, int $comId): int
    {
        $max_no = mysqli_fetch_array(mysqli_query($this->conn,
            "SELECT max(rep_no) as maxrep FROM receipt WHERE vender='$comId'"));
        $new_rw = intval($max_no['maxrep'] ?? 0) + 1;

        $pm = \sql_escape($data['payment_method'] ?? 'cash');
        $status = \sql_escape($data['status'] ?? 'confirmed');
        $inv_id = !empty($data['invoice_id']) ? intval($data['invoice_id']) : 'NULL';
        $quo_id = !empty($data['quotation_id']) ? intval($data['quotation_id']) : 'NULL';
        $source = \sql_escape($data['source_type'] ?? 'manual');
        $vat = isset($data['include_vat']) ? 1 : 0;
        $pref = \sql_escape($data['payment_ref'] ?? '');
        $pdate = !empty($data['payment_date']) ? date('Y-m-d', strtotime($data['payment_date'])) : date('Y-m-d');

        $sql = "INSERT INTO receipt (name, phone, email, createdate, description, payment_method, payment_ref,
                payment_date, status, invoice_id, quotation_id, source_type, include_vat, vender, rep_no, rep_rw,
                brand, vat, dis, deleted_at) VALUES ('" . \sql_escape($data['name']) . "','" .
            \sql_escape($data['phone']) . "','" . \sql_escape($data['email']) . "','" . date("Y-m-d") . "','" .
            \sql_escape($data['des']) . "','$pm','$pref','$pdate','$status',$inv_id,$quo_id,'$source','$vat',
            '$comId','$new_rw','" . (date("y") + 43) . str_pad($new_rw, 6, '0', STR_PAD_LEFT) . "','" .
            \sql_int($data['brandven'] ?? 0) . "','" . \sql_escape($data['vat'] ?? '0') . "','" .
            \sql_escape($data['dis'] ?? '0') . "',NULL)";
        mysqli_query($this->conn, $sql);
        $repId = mysqli_insert_id($this->conn);

        if ($source == 'manual' && isset($data['type']) && is_array($data['type'])) {
            $this->insertProducts($data, $repId, $comId);
        }
        return $repId;
    }

    public function updateReceipt(int $id, array $data, int $comId): void
    {
        $pm = \sql_escape($data['payment_method'] ?? 'cash');
        $status = \sql_escape($data['status'] ?? 'confirmed');
        $inv_sql = !empty($data['invoice_id']) ? "invoice_id='" . intval($data['invoice_id']) . "'" : "invoice_id=NULL";
        $quo_sql = !empty($data['quotation_id']) ? "quotation_id='" . intval($data['quotation_id']) . "'" : "quotation_id=NULL";
        $source = \sql_escape($data['source_type'] ?? 'manual');
        $vat = isset($data['include_vat']) ? 1 : 0;
        $pref = \sql_escape($data['payment_ref'] ?? '');
        $pdate = !empty($data['payment_date']) ? date('Y-m-d', strtotime($data['payment_date'])) : date('Y-m-d');

        $sql = "UPDATE receipt SET name='" . \sql_escape($data['name']) . "', phone='" . \sql_escape($data['phone']) .
            "', email='" . \sql_escape($data['email']) . "', description='" . \sql_escape($data['des']) .
            "', brand='" . \sql_int($data['brandven'] ?? 0) . "', vat='" . \sql_escape($data['vat'] ?? '0') .
            "', dis='" . \sql_escape($data['dis'] ?? '0') . "', payment_method='$pm', payment_ref='$pref',
            payment_date='$pdate', status='$status', $inv_sql, $quo_sql, source_type='$source', include_vat='$vat'
            WHERE id='" . \sql_int($id) . "' AND vender='$comId'";
        mysqli_query($this->conn, $sql);

        if ($source == 'manual' && isset($data['type']) && is_array($data['type'])) {
            mysqli_query($this->conn, "DELETE FROM product WHERE re_id='" . \sql_int($id) . "' AND po_id='0' AND so_id='0'");
            $this->insertProducts($data, $id, $comId);
        }
    }

    private function insertProducts(array $data, int $repId, int $comId): void
    {
        if (!isset($data['type']) || !is_array($data['type'])) return;
        $i = 0;
        foreach ($data['type'] as $type) {
            $args = ['table' => 'product'];
            $args['columns'] = "company_id, po_id, price, discount, ban_id, model, type, quantity, pack_quantity, so_id, des, activelabour, valuelabour, vo_id, vo_warranty, re_id, deleted_at";
            $args['value'] = "'$comId', '0', '" . floatval($data['price'][$i] ?? 0) . "', '0', '" .
                intval($data['ban_id'][$i] ?? 0) . "', '" . intval($data['model'][$i] ?? 0) . "', '" . intval($type) .
                "', '" . floatval($data['quantity'][$i] ?? 1) . "', '1', '0', '" . \sql_escape($data['des'][$i] ?? '') .
                "', '" . intval($data['a_labour'][$i] ?? 0) . "', '" . floatval($data['v_labour'][$i] ?? 0) .
                "', '0', '" . date("Y-m-d", strtotime($data['warranty'][$i] ?? 'now')) . "', '$repId', NULL";
            $this->hard->insertDB($args);
            $i++;
        }
    }

    private function fetchAll(string $sql): array
    {
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) { while ($row = mysqli_fetch_assoc($result)) $rows[] = $row; }
        return $rows;
    }
}
