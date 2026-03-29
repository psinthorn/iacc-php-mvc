<?php
namespace App\Models;

/**
 * Delivery Model
 * Replaces SQL from: deliv-list.php, deliv-make.php, deliv-edit.php, deliv-view.php, core-function.php case deliv_list
 */
class Delivery extends BaseModel
{
    protected string $table = 'deliver';
    protected bool $useCompanyFilter = false;

    // Count delivery notes (PO-based)
    public function countDeliveries(int $comId, string $direction, array $filters): int
    {
        $conds = $this->buildConditions($filters);
        if ($direction === 'out') {
            $where = "ven_id='$comId'";
        } else {
            $where = "cus_id='$comId'";
        }
        $sql = "SELECT COUNT(*) as total FROM po JOIN pr ON po.ref=pr.id JOIN company ON pr.cus_id=company.id
                JOIN deliver ON po.id=deliver.po_id
                WHERE po_id_new='' AND $where AND pr.status='3' $conds";
        $r = mysqli_query($this->conn, $sql);
        return $r ? intval(mysqli_fetch_assoc($r)['total']) : 0;
    }

    public function getDeliveries(int $comId, string $direction, array $filters, int $offset, int $limit): array
    {
        $conds = $this->buildConditions($filters);
        if ($direction === 'out') {
            $where = "ven_id='$comId'";
            $join = "JOIN company ON pr.cus_id=company.id";
        } else {
            $where = "cus_id='$comId'";
            $join = "JOIN company ON pr.ven_id=company.id";
        }
        return $this->fetchAll("SELECT deliver.id, po.id as po_id, po.name, DATE_FORMAT(po.valid_pay,'%d-%m-%Y') as valid_pay,
            company.name_en, DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date, pr.status
            FROM po JOIN pr ON po.ref=pr.id $join JOIN deliver ON po.id=deliver.po_id
            WHERE po_id_new='' AND $where AND pr.status='3' $conds
            ORDER BY deliver.id DESC LIMIT $offset, $limit");
    }

    // Count standalone sendout items
    public function countSendouts(int $comId, string $direction): int
    {
        if ($direction === 'out') {
            $where = "sendoutitem.ven_id='$comId'";
            $notIn = "AND deliver.id NOT IN (SELECT deliver_id FROM receive)";
        } else {
            $where = "sendoutitem.cus_id='$comId'";
            $notIn = "";
        }
        $sql = "SELECT COUNT(*) as total FROM sendoutitem JOIN deliver ON sendoutitem.id=deliver.out_id
                JOIN company ON sendoutitem.cus_id=company.id WHERE $where $notIn";
        $r = mysqli_query($this->conn, $sql);
        return $r ? intval(mysqli_fetch_assoc($r)['total']) : 0;
    }

    public function getSendouts(int $comId, string $direction): array
    {
        if ($direction === 'out') {
            $where = "sendoutitem.ven_id='$comId'";
            $notIn = "AND deliver.id NOT IN (SELECT deliver_id FROM receive)";
        } else {
            $where = "sendoutitem.cus_id='$comId'";
            $notIn = "";
        }
        return $this->fetchAll("SELECT sendoutitem.id, deliver.id as deliv_id, sendoutitem.tmp as description,
            company.name_en, DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date
            FROM sendoutitem JOIN deliver ON sendoutitem.id=deliver.out_id
            JOIN company ON sendoutitem.cus_id=company.id WHERE $where $notIn ORDER BY deliver.id DESC");
    }

    private function buildConditions(array $f): string
    {
        $cond = '';
        if (!empty($f['search'])) {
            $s = \sql_escape($f['search']);
            $cond .= " AND (po.name LIKE '%$s%' OR company.name_en LIKE '%$s%')";
        }
        if (!empty($f['date_from'])) $cond .= " AND deliver.deliver_date >= '" . \sql_escape($f['date_from']) . "'";
        if (!empty($f['date_to'])) $cond .= " AND deliver.deliver_date <= '" . \sql_escape($f['date_to']) . "'";
        return $cond;
    }

    // Get delivery detail for view page
    public function getDeliveryDetail(int $delivId, int $comId, string $mode = ''): ?array
    {
        if ($mode === 'ad') {
            $sql = "SELECT sendoutitem.id, sendoutitem.tmp, sendoutitem.ven_id, sendoutitem.cus_id,
                    company.name_sh, DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliver_date
                    FROM sendoutitem JOIN deliver ON sendoutitem.id=deliver.out_id
                    JOIN company ON sendoutitem.cus_id=company.id
                    WHERE deliver.id='" . \sql_int($delivId) . "'
                    AND (sendoutitem.cus_id='$comId' OR sendoutitem.ven_id='$comId')
                    AND deliver.id NOT IN (SELECT deliver_id FROM receive) LIMIT 1";
        } else {
            $sql = "SELECT po.name, po.id as po_id, po.tax, pr.ven_id, pr.cus_id, pr.des,
                    po.valid_pay, po.deliver_date, po.vat, po.dis, po.over, pr.id as pr_id, pr.status,
                    deliver.id as deliv_id, DATE_FORMAT(deliver.deliver_date,'%d-%m-%Y') as deliv_date,
                    company.name_en, company.name_sh
                    FROM pr JOIN po ON pr.id=po.ref JOIN deliver ON po.id=deliver.po_id
                    LEFT JOIN company ON pr.cus_id=company.id
                    WHERE deliver.id='" . \sql_int($delivId) . "' AND pr.status='3'
                    AND (pr.cus_id='$comId' OR pr.ven_id='$comId') AND po_id_new='' LIMIT 1";
        }
        $r = mysqli_query($this->conn, $sql);
        return ($r && mysqli_num_rows($r) > 0) ? mysqli_fetch_assoc($r) : null;
    }

    public function getDeliveryProducts(int $id, string $mode = ''): array
    {
        if ($mode === 'ad') {
            return $this->fetchAll("SELECT type.name as type_name, product.des, product.price, product.discount,
                model.model_name, store.s_n, DATE_FORMAT(store_sale.warranty,'%d-%m-%Y') as warranty,
                product.quantity, product.pack_quantity
                FROM product JOIN store ON product.pro_id=store.pro_id
                LEFT JOIN type ON product.type=type.id LEFT JOIN model ON product.model=model.id
                JOIN store_sale ON store.id=store_sale.st_id WHERE product.so_id='" . \sql_int($id) . "'");
        }
        return $this->fetchAll("SELECT type.name as type_name, product.des, product.price, product.discount,
            model.model_name, store.s_n, DATE_FORMAT(store_sale.warranty,'%d-%m-%Y') as warranty,
            product.quantity, product.pack_quantity, product.pro_id, product.activelabour, product.valuelabour
            FROM product JOIN store ON product.pro_id=store.pro_id
            LEFT JOIN type ON product.type=type.id LEFT JOIN model ON product.model=model.id
            JOIN store_sale ON store.id=store_sale.st_id WHERE product.po_id='" . \sql_int($id) . "'");
    }

    // Create delivery with serial numbers
    public function createDelivery(array $data, int $comId): void
    {
        $sns = $data['sn'] ?? [];
        $ci = 0;
        foreach ($sns as $sn) {
            if (empty($sn)) {
                $ms = mysqli_fetch_array(mysqli_query($this->conn, "SELECT max(id) as ms FROM gen_serial"));
                $sn = intval($ms['ms'] ?? 0) + 1;
            }
            $maxno = mysqli_fetch_array(mysqli_query($this->conn,
                "SELECT max(s.no) as maxno FROM store s JOIN product p ON s.pro_id=p.pro_id
                 JOIN store_sale ss ON s.id=ss.st_id WHERE ss.own_id='$comId'
                 AND p.model IN (SELECT model FROM product WHERE pro_id='" . \sql_int($data['pro_id'][$ci]) . "')"));

            $argsS = ['table' => 'store'];
            $argsS['columns'] = "company_id, pro_id, s_n, no";
            $argsS['value'] = "'$comId','" . \sql_int($data['pro_id'][$ci]) . "','$sn','" . (intval($maxno['maxno'] ?? 0) + 1) . "'";
            $stId = $this->hard->insertDbMax($argsS);

            $argsSS = ['table' => 'store_sale'];
            $argsSS['columns'] = "st_id, warranty, sale, own_id";
            $argsSS['value'] = "'$stId','" . date("Y-m-d", strtotime($data['exp'][$ci])) . "','0','$comId'";
            $this->hard->insertDB($argsSS);
            $ci++;
        }

        $argsD = ['table' => 'deliver'];
        $argsD['columns'] = "company_id, po_id, deliver_date, out_id, deleted_at";
        $argsD['value'] = "'$comId','" . \sql_int($data['po_id']) . "','" .
            date("Y-m-d", strtotime($data['deliver_date'])) . "','0',NULL";
        $this->hard->insertDB($argsD);

        $argsPR = ['table' => 'pr'];
        $argsPR['value'] = "status='3',payby='" . \sql_int($data['payby'] ?? 0) . "'";
        $argsPR['condition'] = "id='" . \sql_int($data['ref']) . "'";
        $this->hard->updateDb($argsPR);
    }

    // Receive delivery — create invoice (with WHT split support)
    public function receiveDelivery(array $data, int $comId): void
    {
        $argsR = ['table' => 'receive'];
        $argsR['columns'] = "company_id, po_id, deliver_id, date";
        $argsR['value'] = "'$comId','" . \sql_int($data['po_id']) . "','" . \sql_int($data['deliv_id']) . "','" . date('Y-m-d') . "'";
        $this->hard->insertDB($argsR);

        $poId = \sql_int($data['po_id']);

        // Check if this PO has both material and labour items (needs split)
        $hasMaterial = false;
        $hasLabour = false;
        $products = $this->fetchAll("SELECT pro_id, type, model, quantity, pack_quantity, price, discount, des, activelabour, valuelabour, ban_id, company_id FROM product WHERE po_id='$poId' AND deleted_at IS NULL");
        foreach ($products as $p) {
            if (intval($p['activelabour']) === 1) $hasLabour = true;
            else $hasMaterial = true;
        }
        $needsSplit = $hasMaterial && $hasLabour;

        // Get original PO data
        $poData = mysqli_fetch_assoc(mysqli_query($this->conn,
            "SELECT po.*, pr.ven_id, pr.cus_id FROM po JOIN pr ON po.ref=pr.id WHERE po.id='$poId'"));
        $venId = $poData['ven_id'];

        if ($needsSplit) {
            $this->createSplitInvoices($poData, $products, $comId, $venId);
        } else {
            // Normal single invoice (no split needed)
            $this->createSingleInvoice($poId, $comId, $venId);
        }

        $argsPR = ['table' => 'pr', 'value' => "status='4'", 'condition' => "id='" . \sql_int($data['ref']) . "'"];
        $this->hard->updateDb($argsPR);
    }

    /**
     * Create a single invoice (normal flow, no split)
     */
    private function createSingleInvoice(int $poId, int $comId, string $venId): void
    {
        $maxiv = mysqli_fetch_array(mysqli_query($this->conn,
            "SELECT max(id) as max_id FROM iv WHERE cus_id='$venId'"));
        $newId = intval($maxiv['max_id'] ?? 0) + 1;

        $argsIV = ['table' => 'iv'];
        $argsIV['columns'] = "id, company_id, tex, cus_id, createdate, taxrw, texiv, texiv_rw, texiv_create, status_iv, countmailinv, countmailtax, deleted_at, payment_status, payment_gateway, payment_order_id, paid_amount, paid_date";
        $argsIV['value'] = "'$newId','$comId','$poId','$venId','" .
            date("Y-m-d") . "','" . (date("y") + 43) . str_pad($newId, 6, '0', STR_PAD_LEFT) .
            "','0','0','" . date("Y-m-d") . "','0','0','0',NULL,'pending',NULL,NULL,'0.00',NULL";
        $this->hard->insertDB($argsIV);
    }

    /**
     * Create split invoices: one for materials (no WHT) and one for labour (with WHT)
     * Creates two new PO records linked via split_group_id, each with its own products and IV record.
     */
    private function createSplitInvoices(array $poData, array $products, int $comId, string $venId): void
    {
        $originalPoId = intval($poData['id']);
        $originalTax = $poData['tax'];
        $originalRef = intval($poData['ref']);
        $originalOver = floatval($poData['over'] ?? 0);

        // --- Create Material PO (split_type=material, over=0) ---
        $argsMat = ['table' => 'po'];
        $matPoId = $this->hard->Maxid('po');
        $matTax = $originalTax . '/1';

        $argsMat['columns'] = "company_id, po_id_new, name, ref, tax, date, valid_pay, deliver_date, pic, po_ref, dis, bandven, vat, `over`, split_group_id, split_type, deleted_at";
        $argsMat['value'] = "'" . intval($poData['company_id']) . "', '', '" . \sql_escape($poData['name']) . "', '$originalRef', '" .
            \sql_escape($matTax) . "', '" . $poData['date'] . "', '" . $poData['valid_pay'] . "', '" .
            $poData['deliver_date'] . "', '" . \sql_escape($poData['pic'] ?? '') . "', '" . \sql_escape($poData['po_ref'] ?? '') . "', '" .
            floatval($poData['dis'] ?? 0) . "', '" . intval($poData['bandven'] ?? 0) . "', '" .
            floatval($poData['vat'] ?? 0) . "', '0', '$matPoId', 'material', NULL";
        $createdMatId = $this->hard->insertDbMax($argsMat);

        // Insert material products into new PO
        foreach ($products as $p) {
            if (intval($p['activelabour']) === 1) continue;
            $argsP = ['table' => 'product'];
            $argsP['columns'] = "company_id, po_id, price, discount, ban_id, model, type, quantity, pack_quantity, so_id, des, activelabour, valuelabour, vo_id, vo_warranty, re_id, deleted_at";
            $argsP['value'] = "'" . intval($p['company_id'] ?: $comId) . "', '$createdMatId', '" . floatval($p['price']) . "', '" .
                floatval($p['discount'] ?? 0) . "', '" . intval($p['ban_id'] ?? 0) . "', '" . intval($p['model'] ?? 0) . "', '" .
                intval($p['type']) . "', '" . floatval($p['quantity']) . "', '" . floatval($p['pack_quantity'] ?? 1) .
                "', '0', '" . \sql_escape($p['des'] ?? '') . "', '0', '0', '0', '1970-01-01', '0', NULL";
            $this->hard->insertDB($argsP);
        }

        // Create IV record for material PO
        $maxiv1 = mysqli_fetch_array(mysqli_query($this->conn,
            "SELECT max(id) as max_id FROM iv WHERE cus_id='$venId'"));
        $matIvId = intval($maxiv1['max_id'] ?? 0) + 1;
        $argsIV1 = ['table' => 'iv'];
        $argsIV1['columns'] = "id, company_id, tex, cus_id, createdate, taxrw, texiv, texiv_rw, texiv_create, status_iv, countmailinv, countmailtax, deleted_at, payment_status, payment_gateway, payment_order_id, paid_amount, paid_date";
        $argsIV1['value'] = "'$matIvId','$comId','$createdMatId','$venId','" .
            date("Y-m-d") . "','" . (date("y") + 43) . str_pad($matIvId, 6, '0', STR_PAD_LEFT) .
            "','0','0','" . date("Y-m-d") . "','0','0','0',NULL,'pending',NULL,NULL,'0.00',NULL";
        $this->hard->insertDB($argsIV1);

        // --- Create Labour PO (split_type=labour, over=original WHT%) ---
        $argsLab = ['table' => 'po'];
        $labPoId = $this->hard->Maxid('po');
        $labTax = $originalTax . '/2';

        $argsLab['columns'] = "company_id, po_id_new, name, ref, tax, date, valid_pay, deliver_date, pic, po_ref, dis, bandven, vat, `over`, split_group_id, split_type, deleted_at";
        $argsLab['value'] = "'" . intval($poData['company_id']) . "', '', '" . \sql_escape($poData['name']) . " (Labour)', '$originalRef', '" .
            \sql_escape($labTax) . "', '" . $poData['date'] . "', '" . $poData['valid_pay'] . "', '" .
            $poData['deliver_date'] . "', '" . \sql_escape($poData['pic'] ?? '') . "', '" . \sql_escape($poData['po_ref'] ?? '') . "', '" .
            floatval($poData['dis'] ?? 0) . "', '" . intval($poData['bandven'] ?? 0) . "', '" .
            floatval($poData['vat'] ?? 0) . "', '" . $originalOver . "', '$createdMatId', 'labour', NULL";
        $createdLabId = $this->hard->insertDbMax($argsLab);

        // Update material PO's split_group_id to point to itself (the first in the group)
        // Both POs share the same split_group_id = createdMatId
        // (already set for material PO above)

        // Insert labour products into new PO
        foreach ($products as $p) {
            if (intval($p['activelabour']) !== 1) continue;
            $argsP = ['table' => 'product'];
            $argsP['columns'] = "company_id, po_id, price, discount, ban_id, model, type, quantity, pack_quantity, so_id, des, activelabour, valuelabour, vo_id, vo_warranty, re_id, deleted_at";
            $argsP['value'] = "'" . intval($p['company_id'] ?: $comId) . "', '$createdLabId', '" . floatval($p['price']) . "', '" .
                floatval($p['discount'] ?? 0) . "', '" . intval($p['ban_id'] ?? 0) . "', '" . intval($p['model'] ?? 0) . "', '" .
                intval($p['type']) . "', '" . floatval($p['quantity']) . "', '" . floatval($p['pack_quantity'] ?? 1) .
                "', '0', '" . \sql_escape($p['des'] ?? '') . "', '" . intval($p['activelabour']) . "', '" .
                floatval($p['valuelabour'] ?? 0) . "', '0', '1970-01-01', '0', NULL";
            $this->hard->insertDB($argsP);
        }

        // Create IV record for labour PO
        $maxiv2 = mysqli_fetch_array(mysqli_query($this->conn,
            "SELECT max(id) as max_id FROM iv WHERE cus_id='$venId'"));
        $labIvId = intval($maxiv2['max_id'] ?? 0) + 1;
        $argsIV2 = ['table' => 'iv'];
        $argsIV2['columns'] = "id, company_id, tex, cus_id, createdate, taxrw, texiv, texiv_rw, texiv_create, status_iv, countmailinv, countmailtax, deleted_at, payment_status, payment_gateway, payment_order_id, paid_amount, paid_date";
        $argsIV2['value'] = "'$labIvId','$comId','$createdLabId','$venId','" .
            date("Y-m-d") . "','" . (date("y") + 43) . str_pad($labIvId, 6, '0', STR_PAD_LEFT) .
            "','0','0','" . date("Y-m-d") . "','0','0','0',NULL,'pending',NULL,NULL,'0.00',NULL";
        $this->hard->insertDB($argsIV2);

        // Mark original PO as superseded (point to material PO)
        $argsOrig = ['table' => 'po', 'value' => "po_id_new='$createdMatId'", 'condition' => "id='$originalPoId'"];
        $this->hard->updateDb($argsOrig);
    }

    // Receive standalone delivery
    public function receiveStandalone(array $data, int $comId): void
    {
        $argsR = ['table' => 'receive'];
        $argsR['columns'] = "company_id, po_id, deliver_id, date";
        $argsR['value'] = "'$comId','ou" . \sql_int($data['po_id']) . "','" . \sql_int($data['deliv_id']) . "','" . date('Y-m-d') . "'";
        $this->hard->insertDB($argsR);
    }

    // Create standalone sendout delivery
    public function createSendout(array $data, int $comId): void
    {
        $argsSO = ['table' => 'sendoutitem'];
        $argsSO['columns'] = "company_id, ven_id, cus_id, tmp";
        $argsSO['value'] = "'$comId', '$comId', '" . \sql_int($data['cus_id']) . "', '" . \sql_escape($data['des'] ?? '') . "'";
        $opId = $this->hard->insertDbMax($argsSO);

        if (isset($data['type']) && is_array($data['type'])) {
            $i = 0;
            foreach ($data['type'] as $type) {
                $m_pro = mysqli_fetch_array(mysqli_query($this->conn, "SELECT max(pro_id) as pro_id FROM product"));
                $max_pro = intval($m_pro['pro_id'] ?? 0) + 1;

                $argsP = ['table' => 'product'];
                $argsP['columns'] = "pro_id, company_id, po_id, price, discount, ban_id, model, type, quantity, pack_quantity, so_id, des, activelabour, valuelabour, vo_id, vo_warranty, re_id, deleted_at";
                $argsP['value'] = "'$max_pro', '$comId', '0', '" . floatval($data['price'][$i] ?? 0) . "', '" . floatval($data['discount'][$i] ?? 0) .
                    "', '" . intval($data['ban_id'][$i] ?? 0) . "', '" . intval($data['model'][$i] ?? 0) . "', '" . intval($type) .
                    "', '" . floatval($data['quantity'][$i] ?? 1) . "', '" . floatval($data['pack_quantity'][$i] ?? 1) .
                    "', '$opId', '" . \sql_escape($data['des'][$i] ?? '') . "', '0', '0', '0', '1970-01-01', '0', NULL";
                $this->hard->insertDB($argsP);

                $argsS = ['table' => 'store'];
                $argsS['columns'] = "company_id, pro_id, s_n, no";
                $argsS['value'] = "'$comId', '$max_pro', '" . \sql_escape($data['s_n'][$i] ?? '') . "', '0'";
                $stId = $this->hard->insertDbMax($argsS);

                $argsSS = ['table' => 'store_sale'];
                $argsSS['columns'] = "st_id, warranty, sale, own_id";
                $argsSS['value'] = "'$stId', '" . date("Y-m-d", strtotime($data['warranty'][$i] ?? 'now')) . "', '0', '$comId'";
                $this->hard->insertDB($argsSS);
                $i++;
            }
        }

        $argsD = ['table' => 'deliver'];
        $argsD['columns'] = "company_id, po_id, deliver_date, out_id, deleted_at";
        $argsD['value'] = "'$comId', '0', '" . date("Y-m-d", strtotime($data['deliver_date'])) . "', '$opId', NULL";
        $this->hard->insertDB($argsD);
    }

    // Edit delivery
    public function editDelivery(array $data, int $comId): void
    {
        $fetoutid = mysqli_fetch_array(mysqli_query($this->conn,
            "SELECT out_id FROM deliver WHERE id='" . \sql_int($data['deliv_id']) . "'"));
        if (!$fetoutid) return;
        $outId = $fetoutid['out_id'];

        $argsSO = ['table' => 'sendoutitem'];
        $argsSO['value'] = "tmp='" . \sql_escape($data['des'] ?? '') . "',cus_id='" . \sql_int($data['cus_id']) . "'";
        $argsSO['condition'] = "id='$outId'";
        $this->hard->updateDb($argsSO);

        $argsD = ['table' => 'deliver'];
        $argsD['value'] = "deliver_date='" . date("Y-m-d", strtotime($data['deliver_date'])) . "'";
        $argsD['condition'] = "id='" . \sql_int($data['deliv_id']) . "'";
        $this->hard->updateDb($argsD);

        // Clean up old products, stores, store_sales
        $query_proid = mysqli_query($this->conn, "SELECT pro_id FROM product WHERE so_id='$outId'");
        while ($fet = mysqli_fetch_array($query_proid)) {
            $query_st = mysqli_query($this->conn, "SELECT id FROM store WHERE pro_id='" . $fet['pro_id'] . "'");
            while ($st = mysqli_fetch_array($query_st)) {
                mysqli_query($this->conn, "DELETE FROM store_sale WHERE st_id='" . $st['id'] . "'");
            }
            mysqli_query($this->conn, "DELETE FROM store WHERE pro_id='" . $fet['pro_id'] . "'");
        }
        mysqli_query($this->conn, "DELETE FROM product WHERE so_id='$outId'");

        // Re-insert products
        if (isset($data['type']) && is_array($data['type'])) {
            $i = 0;
            foreach ($data['type'] as $type) {
                $m_pro = mysqli_fetch_array(mysqli_query($this->conn, "SELECT max(pro_id) as pro_id FROM product"));
                $max_pro = intval($m_pro['pro_id'] ?? 0) + 1;

                $argsP = ['table' => 'product'];
                $argsP['columns'] = "pro_id, company_id, po_id, price, discount, ban_id, model, type, quantity, pack_quantity, so_id, des, activelabour, valuelabour, vo_id, vo_warranty, re_id, deleted_at";
                $argsP['value'] = "'$max_pro', '$comId', '0', '" . floatval($data['price'][$i] ?? 0) . "', '" . floatval($data['discount'][$i] ?? 0) .
                    "', '" . intval($data['ban_id'][$i] ?? 0) . "', '" . intval($data['model'][$i] ?? 0) . "', '" . intval($type) .
                    "', '" . floatval($data['quantity'][$i] ?? 1) . "', '" . floatval($data['pack_quantity'][$i] ?? 1) .
                    "', '$outId', '" . \sql_escape($data['des'][$i] ?? '') . "', '0', '0', '0', '1970-01-01', '0', NULL";
                $this->hard->insertDB($argsP);

                $argsS = ['table' => 'store'];
                $argsS['columns'] = "company_id, pro_id, s_n, no";
                $argsS['value'] = "'$comId', '$max_pro', '" . \sql_escape($data['s_n'][$i] ?? '') . "', '0'";
                $stId = $this->hard->insertDbMax($argsS);

                $argsSS = ['table' => 'store_sale'];
                $argsSS['columns'] = "st_id, warranty, sale, own_id";
                $argsSS['value'] = "'$stId', '" . date("Y-m-d", strtotime($data['exp'][$i] ?? 'now')) . "', '0', '$comId'";
                $this->hard->insertDB($argsSS);
                $i++;
            }
        }
    }

    public function getCustomers(): array
    {
        return $this->fetchAll("SELECT id, name_en FROM company WHERE customer='1' AND deleted_at IS NULL ORDER BY name_en");
    }

    public function getStoreItems(int $comId, int $typeId): array
    {
        return $this->fetchAll("SELECT store.id, type.name as type_name, store.s_n
            FROM store JOIN product ON store.pro_id=product.pro_id
            JOIN store_sale ON store.id=store_sale.st_id
            LEFT JOIN type ON product.type=type.id
            WHERE store_sale.own_id='$comId' AND product.type='" . \sql_int($typeId) . "' AND store_sale.sale='0'");
    }

    private function fetchAll(string $sql): array
    {
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) { while ($row = mysqli_fetch_assoc($result)) $rows[] = $row; }
        return $rows;
    }
}
