<?php
namespace App\Services;

/**
 * QuickCreateService — Orchestrates multi-document creation for Quick Create module
 * 
 * Supports 3 entry points, auto-generating all UPSTREAM documents:
 *   A) Quotation entry → auto-creates PR
 *   B) Invoice entry   → auto-creates PR + PO + Delivery
 *   C) Tax Invoice entry → auto-creates PR + PO + Delivery + Invoice
 * 
 * All auto-created documents are flagged with auto_generated=1.
 * Each method runs in a DB transaction for atomicity.
 */
class QuickCreateService
{
    private \mysqli $conn;
    private \HardClass $hard;

    public function __construct()
    {
        global $db;
        $this->conn = $db->conn;
        $this->hard = new \HardClass();
        $this->hard->setConnection($this->conn);
    }

    /**
     * Entry Point A: Start from Quotation
     * Auto-creates: PR (upstream)
     * User provides: Quotation/PO data + products
     *
     * @param array $data Form data (name, cus_id, ven_id, valid_pay, deliver_date, products, vat, dis, over)
     * @param int   $comId Company ID from session
     * @return array ['success' => bool, 'data' => ['pr_id' => int, 'po_id' => int], 'error' => string]
     */
    public function createFromQuotation(array $data, int $comId): array
    {
        mysqli_begin_transaction($this->conn);
        try {
            // Resolve customer (existing or new)
            $this->resolveCustomer($data, $comId);

            // Step 1: Auto-create PR
            $prId = $this->autoCreatePR($data, $comId);

            // Step 2: Create PO/Quotation with user-provided data
            $poId = $this->createPO($data, $prId, $comId, false);

            // Step 3: Set PR status to 1 (Quotation Created)
            $this->updatePRStatus($prId, '1');

            mysqli_commit($this->conn);

            return [
                'success' => true,
                'data' => ['pr_id' => $prId, 'po_id' => $poId],
            ];
        } catch (\Exception $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'data' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Entry Point B: Start from Invoice
     * Auto-creates: PR + PO + Delivery (upstream)
     * Creates Invoice via receiveDelivery logic
     *
     * @param array $data Form data (name, cus_id, ven_id, products, dates, vat, dis, over)
     * @param int   $comId Company ID from session
     * @return array ['success' => bool, 'data' => ['pr_id', 'po_id', 'deliver_id', 'iv_id'], 'error' => string]
     */
    public function createFromInvoice(array $data, int $comId): array
    {
        mysqli_begin_transaction($this->conn);
        try {
            // Resolve customer (existing or new)
            $this->resolveCustomer($data, $comId);

            // Step 1: Auto-create PR
            $prId = $this->autoCreatePR($data, $comId);

            // Step 2: Auto-create PO with products
            $poId = $this->createPO($data, $prId, $comId, true);

            // Step 3: Set PR status to 1 (Quotation), then confirm to 2
            $this->updatePRStatus($prId, '2');

            // Step 4: Auto-create Delivery
            $delivId = $this->autoCreateDelivery($poId, $prId, $comId);

            // Step 5: Set PR status to 3 (Delivered)
            $this->updatePRStatus($prId, '3');

            // Step 6: Auto-receive Delivery → creates Invoice
            $ivId = $this->autoReceiveDelivery($poId, $delivId, $prId, $comId);

            // Step 7: Set PR status to 4 (Invoiced)
            $this->updatePRStatus($prId, '4');

            mysqli_commit($this->conn);

            return [
                'success' => true,
                'data' => [
                    'pr_id' => $prId,
                    'po_id' => $poId,
                    'deliver_id' => $delivId,
                    'iv_id' => $ivId,
                ],
            ];
        } catch (\Exception $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'data' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Entry Point C: Start from Tax Invoice
     * Auto-creates: PR + PO + Delivery + Invoice (upstream)
     * Also assigns tax invoice number
     *
     * @param array $data Form data (same as Invoice + WHT fields)
     * @param int   $comId Company ID from session
     * @return array ['success' => bool, 'data' => ['pr_id', 'po_id', 'deliver_id', 'iv_id', 'taxrw'], 'error' => string]
     */
    public function createFromTaxInvoice(array $data, int $comId): array
    {
        mysqli_begin_transaction($this->conn);
        try {
            // Steps 1-7: Same as createFromInvoice
            $this->resolveCustomer($data, $comId);
            $prId = $this->autoCreatePR($data, $comId);
            $poId = $this->createPO($data, $prId, $comId, true);
            $this->updatePRStatus($prId, '2');
            $delivId = $this->autoCreateDelivery($poId, $prId, $comId);
            $this->updatePRStatus($prId, '3');
            $ivId = $this->autoReceiveDelivery($poId, $delivId, $prId, $comId);
            $this->updatePRStatus($prId, '4');

            // Step 8: Assign tax invoice number
            $taxrw = $this->assignTaxInvoiceNumber($poId, $prId, $comId);

            // Step 9: Set PR status to 5 (Completed)
            $this->updatePRStatus($prId, '5');

            mysqli_commit($this->conn);

            return [
                'success' => true,
                'data' => [
                    'pr_id' => $prId,
                    'po_id' => $poId,
                    'deliver_id' => $delivId,
                    'iv_id' => $ivId,
                    'taxrw' => $taxrw,
                ],
            ];
        } catch (\Exception $e) {
            mysqli_rollback($this->conn);
            return ['success' => false, 'data' => [], 'error' => $e->getMessage()];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  Private helpers — each uses isolated $args arrays
    // ─────────────────────────────────────────────────────────────

    /**
     * Resolve customer ID: use existing cus_id or create a new company record
     * Modifies $data['cus_id'] in-place so downstream methods use the resolved ID.
     */
    private function resolveCustomer(array &$data, int $comId): void
    {
        $mode = $data['customer_mode'] ?? 'existing';
        if ($mode === 'new' && !empty(trim($data['new_customer_name'] ?? ''))) {
            $customerName = trim($data['new_customer_name']);
            $escapedName = \sql_escape($customerName);

            // Fetch contact info from the current company
            $parentSql = "SELECT contact, email, phone, fax, tax FROM company WHERE id = " . intval($comId) . " LIMIT 1";
            $parentResult = mysqli_query($this->conn, $parentSql);
            $parent = $parentResult ? mysqli_fetch_assoc($parentResult) : [];

            $contact = \sql_escape($parent['contact'] ?? '');
            $email   = \sql_escape($parent['email'] ?? '');
            $phone   = \sql_escape($parent['phone'] ?? '');
            $fax     = \sql_escape($parent['fax'] ?? '');
            $taxId   = \sql_escape($parent['tax'] ?? '');

            $sql = "INSERT INTO company (name_en, name_th, name_sh, contact, email, phone, fax, tax, term, customer, vender, logo, company_id)
                    VALUES ('$escapedName', '$escapedName', '$escapedName', '$contact', '$email', '$phone', '$fax', '$taxId', '', '1', '0', '', '$comId')";
            mysqli_query($this->conn, $sql);
            $newId = intval(mysqli_insert_id($this->conn));
            if ($newId <= 0) {
                throw new \Exception('Failed to create new customer.');
            }

            // Auto-generate logo
            $generator = new \App\Services\LogoGenerator();
            $logo = $generator->generateForCompany($newId, $escapedName, $customerName);
            if ($logo) {
                mysqli_query($this->conn, "UPDATE company SET logo='" . \sql_escape($logo) . "' WHERE id='" . \sql_int($newId) . "'");
            }

            $data['cus_id'] = $newId;
        }
    }

    /**
     * Auto-create a minimal PR record (flagged as auto_generated)
     */
    private function autoCreatePR(array $data, int $comId): int
    {
        $venId = !empty($data['ven_id']) ? intval($data['ven_id']) : $comId;
        $cusId = intval($data['cus_id'] ?? 0);
        $userId = intval($_SESSION['user_id'] ?? 0);
        $name = \sql_escape($data['name'] ?? 'Quick Create');
        $des = \sql_escape($data['des'] ?? '');

        $argsPR = [];
        $argsPR['table'] = 'pr';
        $argsPR['columns'] = "company_id, name, des, usr_id, cus_id, ven_id, date, status, cancel, auto_generated, mailcount, payby, deleted_at";
        $argsPR['value'] = "'$comId','$name','$des','$userId','$cusId','$venId','" .
            date('Y-m-d') . "','0','0','1','0','0',NULL";
        $prId = $this->hard->insertDbMax($argsPR);

        if (!$prId) {
            throw new \RuntimeException('Failed to create PR record');
        }

        return $prId;
    }

    /**
     * Create PO with products (optionally flagged as auto_generated)
     */
    private function createPO(array $data, int $prId, int $comId, bool $autoGenerated): int
    {
        $argsPO = [];
        $argsPO['table'] = 'po';
        $newPoId = $this->hard->Maxid('po');
        $taxNumber = (date("y") + 43) . str_pad($newPoId, 6, '0', STR_PAD_LEFT);

        $name = \sql_escape($data['name'] ?? 'Quick Create');
        $validPay = date("Y-m-d", strtotime($data['valid_pay'] ?? 'today'));
        $deliverDate = date("Y-m-d", strtotime($data['deliver_date'] ?? 'today'));
        $dis = floatval($data['dis'] ?? 0);
        $brandven = intval($data['brandven'] ?? 0);
        $vat = floatval($data['vat'] ?? 0);
        $over = floatval($data['over'] ?? 0);
        $autoFlag = $autoGenerated ? '1' : '0';

        $argsPO['columns'] = "company_id, po_id_new, auto_generated, name, ref, tax, date, valid_pay, deliver_date, pic, po_ref, dis, bandven, vat, over, deleted_at";
        $argsPO['value'] = "'$comId', '', '$autoFlag', '$name', '$prId', '$taxNumber', '" .
            date('Y-m-d') . "', '$validPay', '$deliverDate', '', '', '$dis', '$brandven', '$vat', '$over', NULL";
        $createdPoId = $this->hard->insertDbMax($argsPO);

        if (!$createdPoId) {
            throw new \RuntimeException('Failed to create PO record');
        }

        // Insert product line items
        $this->insertProducts($data, $createdPoId, $comId);

        return $createdPoId;
    }

    /**
     * Insert product rows for a PO (indexed array pattern)
     */
    private function insertProducts(array $data, int $poId, int $comId): void
    {
        if (!isset($data['type']) || !is_array($data['type'])) {
            throw new \RuntimeException('No product line items provided');
        }

        foreach ($data['type'] as $key => $typeValue) {
            if (empty($typeValue) || $typeValue == '0') continue;

            $argsP = [];
            $argsP['table'] = 'product';
            $price = floatval($data['price'][$key] ?? 0);
            $discount = floatval($data['discount'][$key] ?? 0);
            $ban_id = intval($data['ban_id'][$key] ?? 0);
            $model = intval($data['model'][$key] ?? 0);
            $qty = floatval($data['quantity'][$key] ?? 1);
            $pack_qty = floatval($data['pack_quantity'][$key] ?? 1);
            $des = \sql_escape($data['des_product'][$key] ?? '');
            $a_labour = intval($data['a_labour'][$key] ?? 0);
            $v_labour = floatval($data['v_labour'][$key] ?? 0);

            $argsP['columns'] = "company_id, po_id, price, discount, ban_id, model, type, quantity, pack_quantity, so_id, des, activelabour, valuelabour, vo_id, vo_warranty, re_id, deleted_at";
            $argsP['value'] = "'$comId', '$poId', '$price', '$discount', '$ban_id', '$model', '" .
                intval($typeValue) . "', '$qty', '$pack_qty', '0', '$des', '$a_labour', '$v_labour', '0', '1970-01-01', '0', NULL";
            $this->hard->insertDB($argsP);
        }
    }

    /**
     * Auto-create a Delivery record (flagged as auto_generated)
     */
    private function autoCreateDelivery(int $poId, int $prId, int $comId): int
    {
        // Get products for this PO to create store/store_sale entries
        $products = $this->fetchAll("SELECT pro_id FROM product WHERE po_id='$poId' AND deleted_at IS NULL");

        foreach ($products as $p) {
            $proId = intval($p['pro_id']);

            // Auto-generate serial number
            $ms = mysqli_fetch_array(mysqli_query($this->conn, "SELECT max(id) as ms FROM gen_serial"));
            $sn = intval($ms['ms'] ?? 0) + 1;

            // Get max store number for this model
            $maxno = mysqli_fetch_array(mysqli_query($this->conn,
                "SELECT max(s.no) as maxno FROM store s JOIN product p ON s.pro_id=p.pro_id
                 JOIN store_sale ss ON s.id=ss.st_id WHERE ss.own_id='$comId'
                 AND p.model IN (SELECT model FROM product WHERE pro_id='$proId')"));

            $argsS = [];
            $argsS['table'] = 'store';
            $argsS['columns'] = "company_id, pro_id, s_n, no";
            $argsS['value'] = "'$comId','$proId','$sn','" . (intval($maxno['maxno'] ?? 0) + 1) . "'";
            $stId = $this->hard->insertDbMax($argsS);

            $argsSS = [];
            $argsSS['table'] = 'store_sale';
            $argsSS['columns'] = "st_id, warranty, sale, own_id";
            $argsSS['value'] = "'$stId','" . date("Y-m-d", strtotime("+1 year")) . "','0','$comId'";
            $this->hard->insertDB($argsSS);
        }

        // Create delivery record
        $argsD = [];
        $argsD['table'] = 'deliver';
        $argsD['columns'] = "company_id, po_id, deliver_date, out_id, auto_generated, deleted_at";
        $argsD['value'] = "'$comId','$poId','" . date("Y-m-d") . "','0','1',NULL";
        $delivId = $this->hard->insertDbMax($argsD);

        if (!$delivId) {
            throw new \RuntimeException('Failed to create Delivery record');
        }

        return $delivId;
    }

    /**
     * Auto-receive Delivery and create Invoice
     * Replicates logic from Delivery::receiveDelivery() + createSingleInvoice/createSplitInvoices
     */
    private function autoReceiveDelivery(int $poId, int $delivId, int $prId, int $comId): int
    {
        // Create receive record
        $argsR = [];
        $argsR['table'] = 'receive';
        $argsR['columns'] = "company_id, po_id, deliver_id, date";
        $argsR['value'] = "'$comId','$poId','$delivId','" . date('Y-m-d') . "'";
        $this->hard->insertDB($argsR);

        // Check if products have labour charges → split invoice needed
        $products = $this->fetchAll(
            "SELECT pro_id, type, model, quantity, pack_quantity, price, discount, des, activelabour, valuelabour, ban_id, company_id 
             FROM product WHERE po_id='$poId' AND deleted_at IS NULL"
        );

        $needsSplit = false;
        foreach ($products as $p) {
            if (intval($p['activelabour']) === 1 && floatval($p['valuelabour']) > 0) {
                $needsSplit = true;
                break;
            }
        }

        // Get PO + PR data
        $poData = mysqli_fetch_assoc(mysqli_query($this->conn,
            "SELECT po.*, pr.ven_id, pr.cus_id FROM po JOIN pr ON po.ref=pr.id WHERE po.id='$poId'"));
        if (!$poData) {
            throw new \RuntimeException('PO data not found for invoice creation');
        }
        $venId = $poData['ven_id'];

        if ($needsSplit) {
            // Split invoice: material + labour
            $ivId = $this->createSplitInvoices($poData, $products, $comId, $venId);
        } else {
            // Single invoice
            $ivId = $this->createSingleInvoice($poId, $comId, $venId);
        }

        return $ivId;
    }

    /**
     * Create a single invoice (no split)
     */
    private function createSingleInvoice(int $poId, int $comId, string $venId): int
    {
        $maxiv = mysqli_fetch_array(mysqli_query($this->conn,
            "SELECT max(id) as max_id FROM iv WHERE cus_id='$venId'"));
        $newId = intval($maxiv['max_id'] ?? 0) + 1;

        $argsIV = [];
        $argsIV['table'] = 'iv';
        $argsIV['columns'] = "id, company_id, tex, cus_id, createdate, taxrw, texiv, texiv_rw, texiv_create, status_iv, auto_generated, countmailinv, countmailtax, deleted_at, payment_status, payment_gateway, payment_order_id, paid_amount, paid_date";
        $argsIV['value'] = "'$newId','$comId','$poId','$venId','" .
            date("Y-m-d") . "','" . (date("y") + 43) . str_pad($newId, 6, '0', STR_PAD_LEFT) .
            "','0','0','" . date("Y-m-d") . "','0','1','0','0',NULL,'pending',NULL,NULL,'0.00',NULL";
        $this->hard->insertDB($argsIV);

        return $newId;
    }

    /**
     * Create split invoices: material (no WHT) + labour (with WHT)
     * Returns the material invoice ID
     */
    private function createSplitInvoices(array $poData, array $products, int $comId, string $venId): int
    {
        $originalPoId = intval($poData['id']);
        $originalTax = $poData['tax'];
        $originalRef = intval($poData['ref']);
        $originalOver = floatval($poData['over'] ?? 0);

        // --- Material PO ---
        $argsMat = [];
        $argsMat['table'] = 'po';
        $matPoId = $this->hard->Maxid('po');
        $matTax = $originalTax . '/1';

        $argsMat['columns'] = "company_id, po_id_new, auto_generated, name, ref, tax, date, valid_pay, deliver_date, pic, po_ref, dis, bandven, vat, `over`, split_group_id, split_type, deleted_at";
        $argsMat['value'] = "'" . intval($poData['company_id']) . "', '', '1', '" . \sql_escape($poData['name']) . "', '$originalRef', '" .
            \sql_escape($matTax) . "', '" . $poData['date'] . "', '" . $poData['valid_pay'] . "', '" .
            $poData['deliver_date'] . "', '" . \sql_escape($poData['pic'] ?? '') . "', '" . \sql_escape($poData['po_ref'] ?? '') . "', '" .
            floatval($poData['dis'] ?? 0) . "', '" . intval($poData['bandven'] ?? 0) . "', '" .
            floatval($poData['vat'] ?? 0) . "', '0', '$matPoId', 'material', NULL";
        $createdMatId = $this->hard->insertDbMax($argsMat);

        // Material products (all products, labour stripped)
        foreach ($products as $p) {
            $argsP = [];
            $argsP['table'] = 'product';
            $argsP['columns'] = "company_id, po_id, price, discount, ban_id, model, type, quantity, pack_quantity, so_id, des, activelabour, valuelabour, vo_id, vo_warranty, re_id, deleted_at";
            $argsP['value'] = "'" . intval($p['company_id'] ?: $comId) . "', '$createdMatId', '" . floatval($p['price']) . "', '" .
                floatval($p['discount'] ?? 0) . "', '" . intval($p['ban_id'] ?? 0) . "', '" . intval($p['model'] ?? 0) . "', '" .
                intval($p['type']) . "', '" . floatval($p['quantity']) . "', '" . floatval($p['pack_quantity'] ?? 1) .
                "', '0', '" . \sql_escape($p['des'] ?? '') . "', '0', '0', '0', '1970-01-01', '0', NULL";
            $this->hard->insertDB($argsP);
        }

        // Material Invoice
        $maxiv1 = mysqli_fetch_array(mysqli_query($this->conn,
            "SELECT max(id) as max_id FROM iv WHERE cus_id='$venId'"));
        $matIvId = intval($maxiv1['max_id'] ?? 0) + 1;

        $argsIV1 = [];
        $argsIV1['table'] = 'iv';
        $argsIV1['columns'] = "id, company_id, tex, cus_id, createdate, taxrw, texiv, texiv_rw, texiv_create, status_iv, auto_generated, countmailinv, countmailtax, deleted_at, payment_status, payment_gateway, payment_order_id, paid_amount, paid_date";
        $argsIV1['value'] = "'$matIvId','$comId','$createdMatId','$venId','" .
            date("Y-m-d") . "','" . (date("y") + 43) . str_pad($matIvId, 6, '0', STR_PAD_LEFT) .
            "','0','0','" . date("Y-m-d") . "','0','1','0','0',NULL,'pending',NULL,NULL,'0.00',NULL";
        $this->hard->insertDB($argsIV1);

        // --- Labour PO ---
        $argsLab = [];
        $argsLab['table'] = 'po';
        $labPoId = $this->hard->Maxid('po');
        $labTax = $originalTax . '/2';

        $argsLab['columns'] = "company_id, po_id_new, auto_generated, name, ref, tax, date, valid_pay, deliver_date, pic, po_ref, dis, bandven, vat, `over`, split_group_id, split_type, deleted_at";
        $argsLab['value'] = "'" . intval($poData['company_id']) . "', '', '1', '" . \sql_escape($poData['name']) . " (Labour)', '$originalRef', '" .
            \sql_escape($labTax) . "', '" . $poData['date'] . "', '" . $poData['valid_pay'] . "', '" .
            $poData['deliver_date'] . "', '" . \sql_escape($poData['pic'] ?? '') . "', '" . \sql_escape($poData['po_ref'] ?? '') . "', '" .
            floatval($poData['dis'] ?? 0) . "', '" . intval($poData['bandven'] ?? 0) . "', '" .
            floatval($poData['vat'] ?? 0) . "', '" . $originalOver . "', '$createdMatId', 'labour', NULL";
        $createdLabId = $this->hard->insertDbMax($argsLab);

        // Labour products (only those with labour)
        foreach ($products as $p) {
            if (intval($p['activelabour']) !== 1 || floatval($p['valuelabour']) <= 0) continue;
            $argsP = [];
            $argsP['table'] = 'product';
            $argsP['columns'] = "company_id, po_id, price, discount, ban_id, model, type, quantity, pack_quantity, so_id, des, activelabour, valuelabour, vo_id, vo_warranty, re_id, deleted_at";
            $argsP['value'] = "'" . intval($p['company_id'] ?: $comId) . "', '$createdLabId', '" . floatval($p['valuelabour']) . "', '" .
                floatval($p['discount'] ?? 0) . "', '" . intval($p['ban_id'] ?? 0) . "', '" . intval($p['model'] ?? 0) . "', '" .
                intval($p['type']) . "', '" . floatval($p['quantity']) . "', '" . floatval($p['pack_quantity'] ?? 1) .
                "', '0', '" . \sql_escape($p['des'] ?? '') . " (Labour)', '1', '" .
                floatval($p['valuelabour']) . "', '0', '1970-01-01', '0', NULL";
            $this->hard->insertDB($argsP);
        }

        // Labour Invoice
        $maxiv2 = mysqli_fetch_array(mysqli_query($this->conn,
            "SELECT max(id) as max_id FROM iv WHERE cus_id='$venId'"));
        $labIvId = intval($maxiv2['max_id'] ?? 0) + 1;

        $argsIV2 = [];
        $argsIV2['table'] = 'iv';
        $argsIV2['columns'] = "id, company_id, tex, cus_id, createdate, taxrw, texiv, texiv_rw, texiv_create, status_iv, auto_generated, countmailinv, countmailtax, deleted_at, payment_status, payment_gateway, payment_order_id, paid_amount, paid_date";
        $argsIV2['value'] = "'$labIvId','$comId','$createdLabId','$venId','" .
            date("Y-m-d") . "','" . (date("y") + 43) . str_pad($labIvId, 6, '0', STR_PAD_LEFT) .
            "','0','0','" . date("Y-m-d") . "','0','1','0','0',NULL,'pending',NULL,NULL,'0.00',NULL";
        $this->hard->insertDB($argsIV2);

        // Mark original PO as superseded
        $argsOrig = [];
        $argsOrig['table'] = 'po';
        $argsOrig['value'] = "po_id_new='$createdMatId'";
        $argsOrig['condition'] = "id='$originalPoId'";
        $this->hard->updateDb($argsOrig);

        return $matIvId;
    }

    /**
     * Assign tax invoice number to an invoice
     */
    private function assignTaxInvoiceNumber(int $poId, int $prId, int $comId): string
    {
        // Get vendor from PO→PR link
        $po = mysqli_fetch_assoc(mysqli_query($this->conn,
            "SELECT pr.ven_id FROM pr JOIN po ON pr.id=po.ref WHERE po.id='" . intval($poId) . "'"));
        if (!$po) {
            throw new \RuntimeException('Cannot find vendor for tax invoice number');
        }

        $venId = $po['ven_id'];

        // Get max tax invoice sequence for this vendor
        $max = mysqli_fetch_assoc(mysqli_query($this->conn,
            "SELECT max(texiv) as max_id FROM iv WHERE cus_id='$venId'"));
        $newNum = intval($max['max_id'] ?? 0) + 1;
        $rw = (date("y") + 43) . str_pad($newNum, 6, '0', STR_PAD_LEFT);

        // Update invoice with tax invoice number
        $args = [];
        $args['table'] = 'iv';
        $args['value'] = "texiv='$newNum', texiv_rw='$rw', texiv_create='" . date("Y-m-d") . "', status_iv='1'";
        $args['condition'] = "tex='" . intval($poId) . "'";
        $this->hard->updateDb($args);

        // Update PR status to completed
        $this->updatePRStatus($prId, '5');

        return $rw;
    }

    /**
     * Update PR status
     */
    private function updatePRStatus(int $prId, string $status): void
    {
        $args = [];
        $args['table'] = 'pr';
        $args['value'] = "status='$status'";
        $args['condition'] = "id='$prId'";
        $this->hard->updateDb($args);
    }

    /**
     * Execute a query and return all rows
     */
    private function fetchAll(string $sql): array
    {
        $result = mysqli_query($this->conn, $sql);
        if (!$result) return [];
        $rows = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Get dropdown data for forms (delegates to PurchaseOrder model methods)
     */
    public function getFormData(int $comId): array
    {
        $po = new \App\Models\PurchaseOrder();
        $cf = \CompanyFilter::getInstance();

        $flatModels = $this->getModelsFiltered($comId);
        $modelsByType = [];
        foreach ($flatModels as $m) {
            $tid = $m['type_id'];
            if (!isset($modelsByType[$tid])) $modelsByType[$tid] = [];
            $modelsByType[$tid][] = $m;
        }

        $companyFilter = $comId > 0 ? " AND company_id = " . intval($comId) : '';

        return [
            'types' => $po->getTypes($comId),
            'models' => $flatModels,
            'models_by_type' => $modelsByType,
            'brands' => $po->getBrands($comId),
            'companies' => $this->getCompaniesFiltered($comId),
            'payment_methods' => $po->getPaymentMethods($comId),
        ];
    }

    /**
     * Get companies filtered by current tenant's company_id
     */
    private function getCompaniesFiltered(int $comId): array
    {
        $filter = $comId > 0 ? " AND company_id = " . intval($comId) : '';
        $sql = "SELECT id, name_en, name_sh, customer, vender FROM company WHERE deleted_at IS NULL" . $filter . " ORDER BY name_en";
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) { while ($row = mysqli_fetch_assoc($result)) { $rows[] = $row; } }
        return $rows;
    }

    /**
     * Get models filtered by current tenant's company_id
     */
    private function getModelsFiltered(int $comId): array
    {
        $filter = $comId > 0 ? " AND m.company_id = " . intval($comId) : '';
        $sql = "SELECT m.id, m.type_id, m.model_name, m.des, m.price, m.brand_id FROM model m WHERE m.deleted_at IS NULL" . $filter;
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) { while ($row = mysqli_fetch_assoc($result)) { $rows[] = $row; } }
        return $rows;
    }
}
