<?php
namespace App\Services;

use App\Models\ChannelOrder;
use App\Models\Subscription;

/**
 * ChannelService — Business Logic for Sales Channel API
 * 
 * Receives a validated channel order and auto-creates:
 *   1. Customer company (if not exists) 
 *   2. Purchase Requisition (PR)
 *   3. Purchase Order (PO / Quotation)
 *   4. Product line items
 * 
 * All records are created under the API subscription owner's company.
 * This is 100% isolated — it does NOT modify any existing controllers or models.
 */
class ChannelService
{
    private \mysqli $conn;
    private \HardClass $hard;
    private ChannelOrder $orderModel;
    private Subscription $subscriptionModel;

    public function __construct()
    {
        global $db;
        $this->conn = $db->conn;
        $this->hard = new \HardClass();
        $this->hard->setConnection($this->conn);
        $this->orderModel = new ChannelOrder();
        $this->subscriptionModel = new Subscription();
    }

    /**
     * Process a channel order end-to-end
     * 
     * @param int   $orderId  The channel_orders.id
     * @param array $order    The channel_orders row
     * @param array $authData   API key + subscription data from authentication
     * @return array ['success' => bool, 'data' => [...], 'error' => '...']
     */
    public function processOrder(int $orderId, array $order, array $authData): array
    {
        // Mark as processing
        $this->orderModel->updateStatus($orderId, 'processing');

        try {
            $ownerCompanyId = intval($authData['company_id']);

            // Step 1: Find or create the customer company
            $customerId = $this->findOrCreateCustomer(
                $order['guest_name'],
                $order['guest_email'] ?? '',
                $order['guest_phone'] ?? '',
                $ownerCompanyId
            );

            // Step 2: Create PR (Purchase Requisition)
            $prId = $this->createPR($order, $ownerCompanyId, $customerId);

            // Step 3: Create PO (Quotation)
            $poId = $this->createPO($order, $prId, $ownerCompanyId);

            // Step 4: Create product line items
            $this->createProducts($order, $poId, $ownerCompanyId);

            // Step 5: Link order to created records
            $this->orderModel->linkRecords($orderId, $customerId, $prId, $poId);

            return [
                'success' => true,
                'data' => [
                    'order_id'  => $orderId,
                    'customer_id' => $customerId,
                    'pr_id'       => $prId,
                    'po_id'       => $poId,
                    'status'      => 'completed',
                ],
            ];
        } catch (\Exception $e) {
            // Mark as failed
            $this->orderModel->updateStatus($orderId, 'failed', [
                'error_message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
            ];
        }
    }

    /**
     * Find existing customer by email/phone or create a new company record
     */
    private function findOrCreateCustomer(string $name, string $email, string $phone, int $ownerCompanyId): int
    {
        // Try to find by email first
        if (!empty($email)) {
            $escaped = \sql_escape($email);
            $sql = "SELECT id FROM company WHERE email = '$escaped' AND company_id = '$ownerCompanyId' AND deleted_at IS NULL LIMIT 1";
            $result = mysqli_query($this->conn, $sql);
            if ($result && $row = mysqli_fetch_assoc($result)) {
                return intval($row['id']);
            }
        }

        // Try to find by phone
        if (!empty($phone)) {
            $escaped = \sql_escape($phone);
            $sql = "SELECT id FROM company WHERE phone = '$escaped' AND company_id = '$ownerCompanyId' AND deleted_at IS NULL LIMIT 1";
            $result = mysqli_query($this->conn, $sql);
            if ($result && $row = mysqli_fetch_assoc($result)) {
                return intval($row['id']);
            }
        }

        // Create new customer company
        $data = [
            'name_en'    => $name,
            'name_th'    => $name,
            'name_sh'    => mb_substr($name, 0, 30),
            'contact'    => $name,
            'email'      => $email ?: '',
            'phone'      => $phone ?: '',
            'fax'        => '',
            'tax'        => '',
            'customer'   => 1,
            'vender'     => 0,
            'logo'       => '',
            'term'       => '',
            'company_id' => $ownerCompanyId,
        ];

        $id = $this->hard->insertSafe('company', $data);
        if (!$id) {
            throw new \RuntimeException('Failed to create customer company');
        }

        return $id;
    }

    /**
     * Create Purchase Requisition
     */
    private function createPR(array $order, int $ownerCompanyId, int $customerId): int
    {
        $checkIn = $order['check_in'] ?? date('Y-m-d');
        $roomType = $order['room_type'] ?? 'Order';
        $guestName = $order['guest_name'];

        $prName = "Channel Order: $guestName - $roomType";
        if (strlen($prName) > 255) {
            $prName = substr($prName, 0, 255);
        }

        $notes = $order['notes'] ?? '';
        $description = "Order via Sales Channel API ({$order['channel']})\n";
        $description .= "Guest: $guestName\n";
        if (!empty($order['check_in'])) $description .= "Check-in: {$order['check_in']}\n";
        if (!empty($order['check_out'])) $description .= "Check-out: {$order['check_out']}\n";
        if (!empty($order['room_type'])) $description .= "Room: {$order['room_type']}\n";
        if (!empty($order['guests'])) $description .= "Guests: {$order['guests']}\n";
        if (!empty($notes)) $description .= "Notes: $notes\n";

        $data = [
            'company_id' => $ownerCompanyId,
            'name'       => $prName,
            'des'        => $description,
            'usr_id'     => 0, // API-created (no user session)
            'cus_id'     => $customerId,
            'ven_id'     => $ownerCompanyId,
            'date'       => $checkIn,
            'status'     => '1', // Quotation status
            'cancel'     => 0,
            'mailcount'  => 0,
            'payby'      => 0,
        ];

        $id = $this->hard->insertSafe('pr', $data);
        if (!$id) {
            throw new \RuntimeException('Failed to create Purchase Requisition');
        }

        return $id;
    }

    /**
     * Create Purchase Order (Quotation)
     */
    private function createPO(array $order, int $prId, int $ownerCompanyId): int
    {
        $checkIn = $order['check_in'] ?? date('Y-m-d');
        $checkOut = $order['check_out'] ?? date('Y-m-d', strtotime('+1 day'));
        $guestName = $order['guest_name'];
        $roomType = $order['room_type'] ?? 'Order';

        $poName = "API: $guestName - $roomType";
        if (strlen($poName) > 255) {
            $poName = substr($poName, 0, 255);
        }

        $data = [
            'company_id'   => $ownerCompanyId,
            'po_id_new'    => '',
            'name'         => $poName,
            'ref'          => $prId,
            'tax'          => '',
            'date'         => $checkIn,
            'valid_pay'    => $checkOut,
            'deliver_date' => $checkOut,
            'pic'          => '',
            'po_ref'       => 'API-' . date('Ymd') . '-' . $prId,
            'dis'          => 0,
            'bandven'      => 0,
            'vat'          => 7,  // Thailand VAT
            'over'         => 0,
        ];

        $id = $this->hard->insertSafe('po', $data);
        if (!$id) {
            throw new \RuntimeException('Failed to create Purchase Order');
        }

        return $id;
    }

    /**
     * Create product line items for the PO
     */
    private function createProducts(array $order, int $poId, int $ownerCompanyId): void
    {
        $roomType = $order['room_type'] ?? 'Order Item';
        $amount = floatval($order['total_amount'] ?? 0);
        $nights = 1;

        // Calculate nights from check-in/check-out
        if (!empty($order['check_in']) && !empty($order['check_out'])) {
            $in = new \DateTime($order['check_in']);
            $out = new \DateTime($order['check_out']);
            $diff = $in->diff($out)->days;
            $nights = max(1, $diff);
        }

        $pricePerNight = $nights > 0 ? ($amount / $nights) : $amount;
        $guests = intval($order['guests'] ?? 1);

        $description = "$roomType";
        if (!empty($order['check_in']) && !empty($order['check_out'])) {
            $description .= " ({$order['check_in']} to {$order['check_out']})";
        }
        if ($guests > 1) {
            $description .= " - $guests guests";
        }

        $data = [
            'company_id'    => $ownerCompanyId,
            'po_id'         => $poId,
            'price'         => $pricePerNight,
            'discount'      => 0,
            'ban_id'        => 0,
            'model'         => 0,
            'type'          => 0,
            'quantity'       => $nights,
            'pack_quantity' => 0,
            'so_id'         => 0,
            'des'           => $description,
            'activelabour'  => 0,
            'valuelabour'   => 0,
            'vo_id'         => 0,
            'vo_warranty'   => date('Y-m-d'),
            're_id'         => 0,
        ];

        $id = $this->hard->insertSafe('product', $data);
        if (!$id) {
            throw new \RuntimeException('Failed to create product line item');
        }
    }
}
