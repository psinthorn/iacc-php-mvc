<?php
namespace App\Models;

/**
 * Voucher Model
 * Replaces SQL from: voucher-list views, voc-make.php, voc-view.php, core-function.php case voucher_list
 */
class Voucher extends BaseModel
{
    protected string $table = 'voucher';
    protected bool $useCompanyFilter = false;

    public function getStats(int $comId): array
    {
        $sql = "SELECT COUNT(*) as total,
            SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status='draft' THEN 1 ELSE 0 END) as draft,
            SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) as cancelled
            FROM voucher WHERE vender='$comId' AND deleted_at IS NULL";
        $r = mysqli_query($this->conn, $sql);
        return $r ? mysqli_fetch_assoc($r) : ['total'=>0,'confirmed'=>0,'draft'=>0,'cancelled'=>0];
    }

    public function countVouchers(int $comId, array $filters): int
    {
        $conds = $this->buildConditions($filters);
        $sql = "SELECT COUNT(*) as total FROM voucher WHERE vender='$comId' AND deleted_at IS NULL $conds";
        $r = mysqli_query($this->conn, $sql);
        return $r ? intval(mysqli_fetch_assoc($r)['total']) : 0;
    }

    public function getVouchers(int $comId, array $filters, int $offset, int $limit): array
    {
        $conds = $this->buildConditions($filters);
        return $this->fetchAll("SELECT id, name, email, phone, DATE_FORMAT(createdate,'%d-%m-%Y') as createdate,
            description, vou_rw, brand, vender, payment_method, status, invoice_id
            FROM voucher WHERE vender='$comId' AND deleted_at IS NULL $conds ORDER BY id DESC LIMIT $offset, $limit");
    }

    private function buildConditions(array $f): string
    {
        $cond = '';
        if (!empty($f['search'])) {
            $s = \sql_escape($f['search']);
            $cond .= " AND (name LIKE '%$s%' OR email LIKE '%$s%' OR phone LIKE '%$s%' OR vou_rw LIKE '%$s%')";
        }
        if (!empty($f['status'])) $cond .= " AND status='" . \sql_escape($f['status']) . "'";
        if (!empty($f['date_from'])) $cond .= " AND createdate >= '" . \sql_escape($f['date_from']) . "'";
        if (!empty($f['date_to'])) $cond .= " AND createdate <= '" . \sql_escape($f['date_to']) . "'";
        return $cond;
    }

    public function findVoucher(int $id, int $comId): ?array
    {
        $r = mysqli_query($this->conn, "SELECT * FROM voucher WHERE id='" . \sql_int($id) . "' AND vender='$comId'");
        return ($r && mysqli_num_rows($r) > 0) ? mysqli_fetch_assoc($r) : null;
    }

    public function getVoucherProducts(int $id): array
    {
        return $this->fetchAll("SELECT p.*, t.name as type_name, b.brand_name, m.model_name
            FROM product p LEFT JOIN type t ON p.type=t.id LEFT JOIN brand b ON p.ban_id=b.id
            LEFT JOIN model m ON p.model=m.id WHERE p.vo_id='" . \sql_int($id) . "'");
    }

    public function createVoucher(array $data, int $comId): int
    {
        $max_no = mysqli_fetch_array(mysqli_query($this->conn,
            "SELECT max(vou_no) as maxvou FROM voucher WHERE vender='$comId'"));
        $new_rw = intval($max_no['maxvou'] ?? 0) + 1;

        $payment_method = \sql_escape($data['payment_method'] ?? 'cash');
        $status = \sql_escape($data['status'] ?? 'confirmed');
        $invoice_id = !empty($data['invoice_id']) ? intval($data['invoice_id']) : 'NULL';

        $args = ['table' => 'voucher'];
        $args['columns'] = "company_id, name, phone, email, createdate, description, payment_method, status, invoice_id, vender, vou_no, vou_rw, brand, vat, discount, deleted_at";
        $args['value'] = "'$comId', '" . \sql_escape($data['name']) . "','" . \sql_escape($data['phone']) . "','" .
            \sql_escape($data['email']) . "','" . date("Y-m-d") . "','" . \sql_escape($data['des']) . "','" .
            $payment_method . "','" . $status . "'," . $invoice_id . ",'" . $comId . "','" . $new_rw . "','" .
            (date("y") + 43) . str_pad($new_rw, 6, '0', STR_PAD_LEFT) . "','" . \sql_int($data['brandven'] ?? 0) .
            "','" . \sql_escape($data['vat'] ?? '0') . "','" . \sql_escape($data['dis'] ?? '0') . "',NULL";
        $vouId = $this->hard->insertDbMax($args);

        $this->insertProducts($data, $vouId, 'vo_id');
        return $vouId;
    }

    public function updateVoucher(int $id, array $data, int $comId): void
    {
        $payment_method = \sql_escape($data['payment_method'] ?? 'cash');
        $status = \sql_escape($data['status'] ?? 'confirmed');
        $invoice_id_sql = !empty($data['invoice_id']) ? "invoice_id='" . intval($data['invoice_id']) . "'" : "invoice_id=NULL";

        $args = ['table' => 'voucher'];
        $args['value'] = "name='" . \sql_escape($data['name']) . "',phone='" . \sql_escape($data['phone']) .
            "',email='" . \sql_escape($data['email']) . "',description='" . \sql_escape($data['des']) .
            "',brand='" . \sql_int($data['brandven'] ?? 0) . "',vat='" . \sql_escape($data['vat'] ?? '0') .
            "',discount='" . \sql_escape($data['dis'] ?? '0') . "',payment_method='" . $payment_method .
            "',status='" . $status . "'," . $invoice_id_sql;
        $args['condition'] = "id='" . \sql_int($id) . "' AND vender='$comId'";
        $this->hard->updateDb($args);

        // Delete old products and re-insert
        mysqli_query($this->conn, "DELETE FROM product WHERE vo_id='" . \sql_int($id) . "' AND po_id='0' AND so_id='0'");
        $this->insertProducts($data, $id, 'vo_id');
    }

    private function insertProducts(array $data, int $docId, string $idField): void
    {
        if (!isset($data['type']) || !is_array($data['type'])) return;
        $i = 0;
        foreach ($data['type'] as $type) {
            $args = ['table' => 'product'];
            $voId = $idField === 'vo_id' ? $docId : '0';
            $reId = $idField === 're_id' ? $docId : '0';
            $args['columns'] = "company_id, po_id, price, discount, ban_id, model, type, quantity, pack_quantity, so_id, des, activelabour, valuelabour, vo_id, vo_warranty, re_id, deleted_at";
            $args['value'] = "'0', '0', '" . floatval($data['price'][$i] ?? 0) . "', '0', '" . intval($data['ban_id'][$i] ?? 0) .
                "', '" . intval($data['model'][$i] ?? 0) . "', '" . intval($type) . "', '" . floatval($data['quantity'][$i] ?? 1) .
                "', '1', '', '" . \sql_escape($data['des'][$i] ?? '') . "', '" . intval($data['a_labour'][$i] ?? 0) .
                "', '" . floatval($data['v_labour'][$i] ?? 0) . "', '$voId', '" .
                date("Y-m-d", strtotime($data['warranty'][$i] ?? 'now')) . "', '$reId', NULL";
            $this->hard->insertDB($args);
            $i++;
        }
    }

    public function getTypes(int $comId): array
    {
        $cf = \CompanyFilter::getInstance();
        return $this->fetchAll("SELECT t.name, t.id, t.des, c.cat_name FROM type t LEFT JOIN category c ON t.cat_id=c.id WHERE 1=1 " . $cf->andCompanyFilter('t'));
    }

    /** Get all brands for company (for cascading dropdown) */
    public function getBrands(int $comId): array
    {
        $cf = \CompanyFilter::getInstance();
        return $this->fetchAll("SELECT brand_name, id FROM brand WHERE 1=1 " . $cf->andCompanyFilter('brand'));
    }

    /** Get all models (for client-side type→model filtering) */
    public function getModels(): array
    {
        return $this->fetchAll("SELECT m.id, m.type_id, m.model_name, m.des, m.price, m.brand_id FROM model m WHERE m.deleted_at IS NULL");
    }

    /** Get vendor/company brand logos for brandven dropdown */
    public function getVendorBrands(int $comId): array
    {
        return $this->fetchAll("SELECT brand_name, id FROM brand WHERE ven_id='$comId'");
    }

    /** Get active payment methods for dropdown */
    public function getPaymentMethods(int $comId): array
    {
        return $this->fetchAll("SELECT * FROM payment_methods WHERE com_id='$comId' AND is_active=1 ORDER BY method_type");
    }

    private function fetchAll(string $sql): array
    {
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) { while ($row = mysqli_fetch_assoc($result)) $rows[] = $row; }
        return $rows;
    }
}
