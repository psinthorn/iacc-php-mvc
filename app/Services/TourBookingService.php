<?php
namespace App\Services;

/**
 * TourBookingService — Generate accounting documents from a tour booking
 *
 * Flow: Booking → PR → PO (with products) → Delivery → Invoice
 * Reuses the same pattern as QuickCreateService but sources data from
 * tour_bookings + tour_booking_items instead of form POST data.
 *
 * All auto-created documents are flagged with auto_generated=1.
 */
class TourBookingService
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
     * Generate full document chain from a booking
     *
     * @param array $booking  Booking record (with items array)
     * @param int   $comId    Company ID
     * @return array ['success' => bool, 'data' => [...ids], 'error' => string]
     */
    public function generateDocuments(array $booking, int $comId): array
    {
        // Don't regenerate if documents already exist
        if (!empty($booking['pr_id']) || !empty($booking['po_id'])) {
            return ['success' => false, 'data' => [], 'error' => 'Documents already generated for this booking'];
        }

        mysqli_begin_transaction($this->conn);
        try {
            $bookingId = intval($booking['id']);
            $cusId     = intval($booking['customer_id'] ?? 0);
            $venId     = $comId; // vendor = own company for tour bookings
            $userId    = intval($_SESSION['user_id'] ?? 0);
            $bookingNo = $booking['booking_number'] ?? '';

            // Fetch full customer info
            $customerInfo = $this->getCustomerInfo($cusId);

            // Step 1: Auto-create PR (with customer info in description)
            $prId = $this->createPR($bookingNo, $cusId, $venId, $comId, $userId, $customerInfo);

            // Step 2: Create PO with booking items as products (+ customer info line)
            $poId = $this->createPO($booking, $prId, $comId, $customerInfo);

            // Step 3: Update PR status → 2 (Confirmed)
            $this->updatePRStatus($prId, '2');

            // Step 4: Auto-create Delivery
            $delivId = $this->createDelivery($poId, $comId);

            // Step 5: Update PR status → 3 (Delivered)
            $this->updatePRStatus($prId, '3');

            // Step 6: Auto-create Invoice
            $ivId = $this->createInvoice($poId, $comId, $venId);

            // Step 7: Update PR status → 4 (Invoiced)
            $this->updatePRStatus($prId, '4');

            // Step 8: Store generated IDs back to booking
            $this->linkDocuments($bookingId, $prId, $poId, $ivId, $delivId);

            mysqli_commit($this->conn);

            return [
                'success' => true,
                'data' => [
                    'pr_id'      => $prId,
                    'po_id'      => $poId,
                    'deliver_id' => $delivId,
                    'iv_id'      => $ivId,
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

    private function createPR(string $bookingNo, int $cusId, int $venId, int $comId, int $userId, array $customerInfo = []): int
    {
        $name = \sql_escape('Tour: ' . $bookingNo);
        $des  = \sql_escape($this->formatCustomerInfo($customerInfo));

        $args = [];
        $args['table'] = 'pr';
        $args['columns'] = "company_id, name, des, usr_id, cus_id, ven_id, date, status, cancel, auto_generated, mailcount, payby, deleted_at";
        $args['value'] = "'$comId','$name','$des','$userId','$cusId','$venId','" . date('Y-m-d') . "','0','0','1','0','0',NULL";
        $prId = $this->hard->insertDbMax($args);

        if (!$prId) {
            throw new \RuntimeException('Failed to create PR');
        }
        return $prId;
    }

    private function createPO(array $booking, int $prId, int $comId, array $customerInfo = []): int
    {
        $args = [];
        $args['table'] = 'po';

        $newPoId = $this->hard->Maxid('po');
        $taxNumber = (date("y") + 43) . str_pad($newPoId, 6, '0', STR_PAD_LEFT);
        $name = \sql_escape('Tour: ' . ($booking['booking_number'] ?? ''));
        $today = date('Y-m-d');
        $travelDate = $booking['travel_date'] ?? $today;
        $dis = floatval($booking['discount'] ?? 0);
        $vat = floatval($booking['vat'] ?? 0);

        $args['columns'] = "company_id, po_id_new, auto_generated, name, ref, tax, date, valid_pay, deliver_date, pic, po_ref, dis, bandven, vat, over, deleted_at";
        $args['value'] = "'$comId','','1','$name','$prId','$taxNumber','$today','$travelDate','$travelDate','','','$dis','0','$vat','0',NULL";
        $poId = $this->hard->insertDbMax($args);

        if (!$poId) {
            throw new \RuntimeException('Failed to create PO');
        }

        // Insert booking items as product rows
        $this->insertProducts($booking, $poId, $comId, $customerInfo);

        return $poId;
    }

    private function insertProducts(array $booking, int $poId, int $comId, array $customerInfo = []): void
    {
        $items = $booking['items'] ?? [];
        if (empty($items)) {
            // If no line items, insert one summary row
            $items = [[
                'description' => 'Tour: ' . ($booking['booking_number'] ?? ''),
                'quantity'    => 1,
                'unit_price'  => floatval($booking['subtotal'] ?? $booking['total_amount'] ?? 0),
                'item_type'   => 'tour',
            ]];
        }

        foreach ($items as $item) {
            $des = $this->buildRichDescription($item);
            $qty = floatval($item['quantity'] ?? 1);
            $price = floatval($item['unit_price'] ?? 0);

            $args = [];
            $args['table'] = 'product';
            $args['columns'] = "company_id, po_id, price, discount, ban_id, model, type, quantity, pack_quantity, so_id, des, activelabour, valuelabour, vo_id, vo_warranty, re_id, deleted_at";
            $args['value'] = "'$comId','$poId','$price','0','0','0','1','$qty','1','0','" . \sql_escape($des) . "','0','0','0','1970-01-01','0',NULL";
            $this->hard->insertDB($args);
        }

        // Add entrance fee as separate product row if > 0
        $entrance = floatval($booking['entrance_fee'] ?? 0);
        if ($entrance > 0) {
            $args = [];
            $args['table'] = 'product';
            $args['columns'] = "company_id, po_id, price, discount, ban_id, model, type, quantity, pack_quantity, so_id, des, activelabour, valuelabour, vo_id, vo_warranty, re_id, deleted_at";
            $args['value'] = "'$comId','$poId','$entrance','0','0','0','1','1','1','0','Entrance Fee','0','0','0','1970-01-01','0',NULL";
            $this->hard->insertDB($args);
        }

        // Add customer info as final product line
        if (!empty($customerInfo)) {
            $cusDesc = \sql_escape("Customer Information\n" . $this->formatCustomerInfo($customerInfo));
            $args = [];
            $args['table'] = 'product';
            $args['columns'] = "company_id, po_id, price, discount, ban_id, model, type, quantity, pack_quantity, so_id, des, activelabour, valuelabour, vo_id, vo_warranty, re_id, deleted_at";
            $args['value'] = "'$comId','$poId','0','0','0','0','1','0','1','0','$cusDesc','0','0','0','1970-01-01','0',NULL";
            $this->hard->insertDB($args);
        }
    }

    /**
     * Build rich description with product/model info and pax breakdown lines
     */
    private function buildRichDescription(array $item): string
    {
        $lines = [];

        // Line 1: Description | Model Name | Model Description
        $header = trim($item['description'] ?? '');
        if (!empty($item['model_name'])) {
            $header .= ' | ' . $item['model_name'];
        }
        if (!empty($item['model_des'])) {
            $header .= ' | ' . $item['model_des'];
        }
        $lines[] = $header;

        // Pax breakdown lines
        $paxLines = [];
        if (!empty($item['pax_lines_json'])) {
            $paxLines = is_string($item['pax_lines_json'])
                ? (json_decode($item['pax_lines_json'], true) ?: [])
                : $item['pax_lines_json'];
        }
        foreach ($paxLines as $pl) {
            $type  = ($pl['type'] ?? 'adult') === 'child' ? 'Child' : 'Adult';
            $nat   = ($pl['nat'] ?? 'thai') === 'foreigner' ? 'Foreign' : 'Thai';
            $qty   = intval($pl['qty'] ?? 0);
            $price = floatval($pl['price'] ?? 0);
            $total = $qty * $price;
            if ($qty > 0) {
                $lines[] = sprintf('%s/%s x%d @%s = %s', $type, $nat, $qty, number_format($price, 2), number_format($total, 2));
            }
        }

        return implode("\n", $lines);
    }

    private function createDelivery(int $poId, int $comId): int
    {
        // Tour services are not physical inventory — skip store/store_sale creation
        // Just create the delivery record directly

        $args = [];
        $args['table'] = 'deliver';
        $args['columns'] = "company_id, po_id, deliver_date, out_id, auto_generated, deleted_at";
        $args['value'] = "'$comId','$poId','" . date("Y-m-d") . "','0','1',NULL";
        $delivId = $this->hard->insertDbMax($args);

        if (!$delivId) {
            throw new \RuntimeException('Failed to create Delivery');
        }
        return $delivId;
    }

    private function createInvoice(int $poId, int $comId, int $venId): int
    {
        $args = [];
        $args['table'] = 'iv';
        $newId = $this->hard->Maxid('iv');
        $taxNumber = (date("y") + 43) . str_pad($newId, 6, '0', STR_PAD_LEFT);

        $args['columns'] = "company_id, tex, cus_id, createdate, taxrw, texiv, texiv_rw, texiv_create, status_iv, auto_generated, countmailinv, countmailtax, deleted_at, payment_status, payment_gateway, payment_order_id, paid_amount, paid_date";
        $args['value'] = "'$comId','$poId','$venId','" . date("Y-m-d") . "','$taxNumber','0','0','" . date("Y-m-d") . "','0','1','0','0',NULL,'pending',NULL,NULL,'0.00',NULL";
        $ivId = $this->hard->insertDbMax($args);

        if (!$ivId) {
            throw new \RuntimeException('Failed to create Invoice');
        }
        return $ivId;
    }

    private function updatePRStatus(int $prId, string $status): void
    {
        mysqli_query($this->conn,
            "UPDATE pr SET status='$status' WHERE id=" . intval($prId));
    }

    private function linkDocuments(int $bookingId, int $prId, int $poId, int $ivId, int $delivId): void
    {
        $sql = sprintf(
            "UPDATE tour_bookings SET pr_id=%d, po_id=%d, invoice_id=%d, delivery_id=%d WHERE id=%d",
            $prId, $poId, $ivId, $delivId, $bookingId
        );
        mysqli_query($this->conn, $sql);
    }

    private function getCustomerInfo(int $cusId): array
    {
        if ($cusId <= 0) return [];
        $cusId = intval($cusId);
        $result = mysqli_query($this->conn,
            "SELECT name_en, name_th, name_sh, contact, email, phone, fax, tax FROM company WHERE id='$cusId' LIMIT 1");
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return $row;
        }
        return [];
    }

    private function formatCustomerInfo(array $info): string
    {
        if (empty($info)) return '';
        $lines = [];
        $name = trim($info['name_en'] ?? '');
        if (!empty($info['name_th'])) {
            $name .= ' (' . trim($info['name_th']) . ')';
        }
        if ($name) $lines[] = 'Customer: ' . $name;
        if (!empty(trim($info['contact'] ?? ''))) $lines[] = 'Contact: ' . trim($info['contact']);
        if (!empty(trim($info['phone'] ?? '')))   $lines[] = 'Phone: ' . trim($info['phone']);
        if (!empty(trim($info['email'] ?? '')))   $lines[] = 'Email: ' . trim($info['email']);
        if (!empty(trim($info['fax'] ?? '')))     $lines[] = 'Fax: ' . trim($info['fax']);
        if (!empty(trim($info['tax'] ?? '')))     $lines[] = 'Tax ID: ' . trim($info['tax']);
        return implode("\n", $lines);
    }

    private function fetchAll(string $sql): array
    {
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }
}
