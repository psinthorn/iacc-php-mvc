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
            $argsS['value'] = "'$comId','" . \sql_int($data['pro_id'][$ci]) . "','$sn','" . (intval($maxno['maxno'] ?? 0) + 1) . "'";
            $stId = $this->hard->insertDbMax($argsS);

            $argsSS = ['table' => 'store_sale'];
            $argsSS['value'] = "NULL,'$stId','" . date("Y-m-d", strtotime($data['exp'][$ci])) . "','0','$comId'";
            $this->hard->insertDB($argsSS);
            $ci++;
        }

        $argsD = ['table' => 'deliver'];
        $argsD['value'] = "NULL,'$comId','" . \sql_int($data['po_id']) . "','" .
            date("Y-m-d", strtotime($data['deliver_date'])) . "','0',NULL";
        $this->hard->insertDB($argsD);

        $argsPR = ['table' => 'pr'];
        $argsPR['value'] = "status='3',payby='" . \sql_int($data['payby'] ?? 0) . "'";
        $argsPR['condition'] = "id='" . \sql_int($data['ref']) . "'";
        $this->hard->updateDb($argsPR);
    }

    // Receive delivery — create invoice
    public function receiveDelivery(array $data, int $comId): void
    {
        $argsR = ['table' => 'receive'];
        $argsR['value'] = "NULL,'$comId','" . \sql_int($data['po_id']) . "','" . \sql_int($data['deliv_id']) . "','" . date('Y-m-d') . "'";
        $this->hard->insertDB($argsR);

        $veniv = mysqli_fetch_array(mysqli_query($this->conn,
            "SELECT ven_id FROM pr JOIN po ON pr.id=po.ref WHERE po.id='" . \sql_int($data['po_id']) . "'"));
        $maxiv = mysqli_fetch_array(mysqli_query($this->conn,
            "SELECT max(id) as max_id FROM iv WHERE cus_id='" . $veniv['ven_id'] . "'"));

        $newId = intval($maxiv['max_id'] ?? 0) + 1;
        $argsIV = ['table' => 'iv'];
        $argsIV['value'] = "'$newId','$comId','" . \sql_int($data['po_id']) . "','" . $veniv['ven_id'] . "','" .
            date("Y-m-d") . "','" . (date("y") + 43) . str_pad($newId, 6, '0', STR_PAD_LEFT) .
            "','0','0','" . date("Y-m-d") . "','0','0','0',NULL,'pending',NULL,NULL,'0.00',NULL";
        $this->hard->insertDB($argsIV);

        $argsPR = ['table' => 'pr', 'value' => "status='4'", 'condition' => "id='" . \sql_int($data['ref']) . "'"];
        $this->hard->updateDb($argsPR);
    }

    // Receive standalone delivery
    public function receiveStandalone(array $data, int $comId): void
    {
        $argsR = ['table' => 'receive'];
        $argsR['value'] = "NULL,'$comId','ou" . \sql_int($data['po_id']) . "','" . \sql_int($data['deliv_id']) . "','" . date('Y-m-d') . "'";
        $this->hard->insertDB($argsR);
    }

    // Create standalone sendout delivery
    public function createSendout(array $data, int $comId): void
    {
        $argsSO = ['table' => 'sendoutitem'];
        $argsSO['value'] = "'$comId','" . \sql_int($data['cus_id']) . "','" . \sql_escape($data['des'] ?? '') . "'";
        $opId = $this->hard->insertDbMax($argsSO);

        if (isset($data['type']) && is_array($data['type'])) {
            $i = 0;
            foreach ($data['type'] as $type) {
                $m_pro = mysqli_fetch_array(mysqli_query($this->conn, "SELECT max(pro_id) as pro_id FROM product"));
                $max_pro = intval($m_pro['pro_id'] ?? 0) + 1;

                $argsP = ['table' => 'product'];
                $argsP['value'] = "'$max_pro','','" . floatval($data['price'][$i] ?? 0) . "','" . floatval($data['discount'][$i] ?? 0) .
                    "','" . intval($data['ban_id'][$i] ?? 0) . "','" . intval($data['model'][$i] ?? 0) . "','" . intval($type) .
                    "','" . floatval($data['quantity'][$i] ?? 1) . "','" . floatval($data['pack_quantity'][$i] ?? 1) .
                    "','$opId','" . \sql_escape($data['des'][$i] ?? '') . "','0','0','0','0000-00-00',''";
                $this->hard->insertDB($argsP);

                $argsS = ['table' => 'store'];
                $argsS['value'] = "'$max_pro','" . \sql_escape($data['s_n'][$i] ?? '') . "',''";
                $stId = $this->hard->insertDbMax($argsS);

                $argsSS = ['table' => 'store_sale'];
                $argsSS['value'] = "'','$stId','" . date("Y-m-d", strtotime($data['warranty'][$i] ?? 'now')) . "','0','$comId'";
                $this->hard->insertDB($argsSS);
                $i++;
            }
        }

        $argsD = ['table' => 'deliver'];
        $argsD['value'] = "'','','" . date("Y-m-d", strtotime($data['deliver_date'])) . "','$opId'";
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
                $argsP['value'] = "'$max_pro','','" . floatval($data['price'][$i] ?? 0) . "','" . floatval($data['discount'][$i] ?? 0) .
                    "','" . intval($data['ban_id'][$i] ?? 0) . "','" . intval($data['model'][$i] ?? 0) . "','" . intval($type) .
                    "','" . floatval($data['quantity'][$i] ?? 1) . "','" . floatval($data['pack_quantity'][$i] ?? 1) .
                    "','$outId','" . \sql_escape($data['des'][$i] ?? '') . "','0','0000-00-00',''";
                $this->hard->insertDB($argsP);

                $argsS = ['table' => 'store'];
                $argsS['value'] = "'$max_pro','" . \sql_escape($data['s_n'][$i] ?? '') . "'";
                $stId = $this->hard->insertDbMax($argsS);

                $argsSS = ['table' => 'store_sale'];
                $argsSS['value'] = "'','$stId','" . date("Y-m-d", strtotime($data['exp'][$i] ?? 'now')) . "','0','$comId'";
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
