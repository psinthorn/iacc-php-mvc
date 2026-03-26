<?php
namespace App\Services;

use App\Models\Booking;
use App\Models\Subscription;

/**
 * BookingService — Business Logic for Booking API
 * 
 * Receives a validated booking request and auto-creates:
 *   1. Customer company (if not exists) 
 *   2. Purchase Requisition (PR)
 *   3. Purchase Order (PO / Quotation)
 *   4. Product line items
 * 
 * All records are created under the API subscription owner's company.
 * This is 100% isolated — it does NOT modify any existing controllers or models.
 */
class BookingService
{
    private \mysqli $conn;
    private \HardClass $hard;
    private Booking $bookingModel;
    private Subscription $subscriptionModel;

    public function __construct()
    {
        global $db;
        $this->conn = $db->conn;
        $this->hard = new \HardClass();
        $this->hard->setConnection($this->conn);
        $this->bookingModel = new Booking();
        $this->subscriptionModel = new Subscription();
    }

    /**
     * Process a booking request end-to-end
     * 
     * @param int   $bookingId  The booking_requests.id
     * @param array $booking    The booking_requests row
     * @param array $authData   API key + subscription data from authentication
     * @return array ['success' => bool, 'data' => [...], 'error' => '...']
     */
    public function processBooking(int $bookingId, array $booking, array $authData): array
    {
        // Mark as processing
        $this->bookingModel->updateStatus($bookingId, 'processing');

        try {
            $ownerCompanyId = intval($authData['company_id']);

            // Step 1: Find or create the customer company
            $customerId = $this->findOrCreateCustomer(
                $booking['guest_name'],
                $booking['guest_email'] ?? '',
                $booking['guest_phone'] ?? '',
                $ownerCompanyId
            );

            // Step 2: Create PR (Purchase Requisition)
            $prId = $this->createPR($booking, $ownerCompanyId, $customerId);

            // Step 3: Create PO (Quotation)
            $poId = $this->createPO($booking, $prId, $ownerCompanyId);

            // Step 4: Create product line items
            $this->createProducts($booking, $poId, $ownerCompanyId);

            // Step 5: Link booking to created records
            $this->bookingModel->linkRecords($bookingId, $customerId, $prId, $poId);

            return [
                'success' => true,
                'data' => [
                    'booking_id'  => $bookingId,
                    'customer_id' => $customerId,
                    'pr_id'       => $prId,
                    'po_id'       => $poId,
                    'status'      => 'completed',
                ],
            ];
        } catch (\Exception $e) {
            // Mark as failed
            $this->bookingModel->updateStatus($bookingId, 'failed', [
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
    private function createPR(array $booking, int $ownerCompanyId, int $customerId): int
    {
        $checkIn = $booking['check_in'] ?? date('Y-m-d');
        $roomType = $booking['room_type'] ?? 'Booking';
        $guestName = $booking['guest_name'];

        $prName = "API Booking: $guestName - $roomType";
        if (strlen($prName) > 255) {
            $prName = substr($prName, 0, 255);
        }

        $notes = $booking['notes'] ?? '';
        $description = "Booking via API ({$booking['channel']})\n";
        $description .= "Guest: $guestName\n";
        if (!empty($booking['check_in'])) $description .= "Check-in: {$booking['check_in']}\n";
        if (!empty($booking['check_out'])) $description .= "Check-out: {$booking['check_out']}\n";
        if (!empty($booking['room_type'])) $description .= "Room: {$booking['room_type']}\n";
        if (!empty($booking['guests'])) $description .= "Guests: {$booking['guests']}\n";
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
    private function createPO(array $booking, int $prId, int $ownerCompanyId): int
    {
        $checkIn = $booking['check_in'] ?? date('Y-m-d');
        $checkOut = $booking['check_out'] ?? date('Y-m-d', strtotime('+1 day'));
        $guestName = $booking['guest_name'];
        $roomType = $booking['room_type'] ?? 'Booking';

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
    private function createProducts(array $booking, int $poId, int $ownerCompanyId): void
    {
        $roomType = $booking['room_type'] ?? 'Booking Item';
        $amount = floatval($booking['total_amount'] ?? 0);
        $nights = 1;

        // Calculate nights from check-in/check-out
        if (!empty($booking['check_in']) && !empty($booking['check_out'])) {
            $in = new \DateTime($booking['check_in']);
            $out = new \DateTime($booking['check_out']);
            $diff = $in->diff($out)->days;
            $nights = max(1, $diff);
        }

        $pricePerNight = $nights > 0 ? ($amount / $nights) : $amount;
        $guests = intval($booking['guests'] ?? 1);

        $description = "$roomType";
        if (!empty($booking['check_in']) && !empty($booking['check_out'])) {
            $description .= " ({$booking['check_in']} to {$booking['check_out']})";
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
