<?php
namespace App\Models;

/**
 * TourBookingPayment — Payment records for tour bookings
 * 
 * Tracks individual payments (manual + gateway) per booking.
 * Manages payment_status sync on parent tour_bookings table.
 */
class TourBookingPayment extends BaseModel
{
    protected string $table = 'tour_booking_payments';
    protected bool $useCompanyFilter = true;

    // ─── List Payments ─────────────────────────────────────────

    /**
     * Get all payments for a booking
     */
    public function getPayments(int $bookingId, int $comId): array
    {
        $sql = "SELECT p.*, 
                       u.name AS created_by_name,
                       a.name AS approved_by_name
                FROM tour_booking_payments p
                LEFT JOIN authorize u ON p.created_by = u.id
                LEFT JOIN authorize a ON p.approved_by = a.id
                WHERE p.booking_id = " . intval($bookingId) . "
                  AND p.company_id = " . intval($comId) . "
                  AND p.deleted_at IS NULL
                ORDER BY p.payment_date DESC, p.id DESC";
        
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Get single payment record
     */
    public function findPayment(int $id, int $comId): ?array
    {
        $sql = "SELECT p.*, 
                       u.name AS created_by_name,
                       a.name AS approved_by_name
                FROM tour_booking_payments p
                LEFT JOIN authorize u ON p.created_by = u.id
                LEFT JOIN authorize a ON p.approved_by = a.id
                WHERE p.id = " . intval($id) . "
                  AND p.company_id = " . intval($comId) . "
                  AND p.deleted_at IS NULL
                LIMIT 1";
        
        $result = mysqli_query($this->conn, $sql);
        return ($result && $row = mysqli_fetch_assoc($result)) ? $row : null;
    }

    // ─── Create Payment ────────────────────────────────────────

    /**
     * Record a manual payment (cash, bank transfer, cheque, credit card)
     */
    public function recordPayment(array $data): int
    {
        $sql = sprintf(
            "INSERT INTO tour_booking_payments 
                (booking_id, company_id, payment_method, gateway, amount, currency,
                 reference_id, payment_date, status, payment_type, slip_image, notes, created_by)
             VALUES (%d, %d, '%s', %s, %s, '%s', %s, '%s', '%s', '%s', %s, %s, %d)",
            intval($data['booking_id']),
            intval($data['company_id']),
            sql_escape($data['payment_method'] ?? 'cash'),
            !empty($data['gateway']) ? "'" . sql_escape($data['gateway']) . "'" : 'NULL',
            floatval($data['amount']),
            sql_escape($data['currency'] ?? 'THB'),
            !empty($data['reference_id']) ? "'" . sql_escape($data['reference_id']) . "'" : 'NULL',
            sql_escape($data['payment_date'] ?? date('Y-m-d')),
            sql_escape($data['status'] ?? 'completed'),
            sql_escape($data['payment_type'] ?? 'full'),
            !empty($data['slip_image']) ? "'" . sql_escape($data['slip_image']) . "'" : 'NULL',
            !empty($data['notes']) ? "'" . sql_escape($data['notes']) . "'" : 'NULL',
            intval($data['created_by'] ?? 0)
        );

        mysqli_query($this->conn, $sql);
        $id = mysqli_insert_id($this->conn);

        if ($id && ($data['status'] ?? 'completed') === 'completed') {
            $this->syncBookingPaymentStatus(intval($data['booking_id']), intval($data['company_id']));
        }

        return $id;
    }

    // ─── Update Payment ────────────────────────────────────────

    /**
     * Approve a pending payment (slip review)
     */
    public function approvePayment(int $id, int $comId, int $approvedBy): bool
    {
        $sql = sprintf(
            "UPDATE tour_booking_payments 
             SET status = 'completed', approved_by = %d, approved_at = NOW()
             WHERE id = %d AND company_id = %d AND status IN ('pending','pending_review') AND deleted_at IS NULL",
            intval($approvedBy),
            intval($id),
            intval($comId)
        );
        $ok = mysqli_query($this->conn, $sql) && mysqli_affected_rows($this->conn) > 0;

        if ($ok) {
            $payment = $this->findPayment($id, $comId);
            if ($payment) {
                $this->syncBookingPaymentStatus(intval($payment['booking_id']), $comId);
            }
        }
        return $ok;
    }

    /**
     * Reject a pending payment
     */
    public function rejectPayment(int $id, int $comId, int $rejectedBy, string $reason): bool
    {
        $sql = sprintf(
            "UPDATE tour_booking_payments 
             SET status = 'rejected', approved_by = %d, approved_at = NOW(), reject_reason = '%s'
             WHERE id = %d AND company_id = %d AND status IN ('pending','pending_review') AND deleted_at IS NULL",
            intval($rejectedBy),
            sql_escape($reason),
            intval($id),
            intval($comId)
        );
        return mysqli_query($this->conn, $sql) && mysqli_affected_rows($this->conn) > 0;
    }

    /**
     * Soft-delete a payment
     */
    public function deletePayment(int $id, int $comId): bool
    {
        $payment = $this->findPayment($id, $comId);
        if (!$payment) return false;

        $sql = sprintf(
            "UPDATE tour_booking_payments SET deleted_at = NOW() WHERE id = %d AND company_id = %d",
            intval($id), intval($comId)
        );
        $ok = mysqli_query($this->conn, $sql) && mysqli_affected_rows($this->conn) > 0;

        if ($ok) {
            $this->syncBookingPaymentStatus(intval($payment['booking_id']), $comId);
        }
        return $ok;
    }

    // ─── Refund ────────────────────────────────────────────────

    /**
     * Record a refund (creates a negative payment record)
     */
    public function recordRefund(array $data): int
    {
        $data['payment_type'] = 'refund';
        $data['status'] = 'completed';
        $data['amount'] = abs(floatval($data['amount']));
        
        $sql = sprintf(
            "INSERT INTO tour_booking_payments 
                (booking_id, company_id, payment_method, amount, currency,
                 reference_id, payment_date, status, payment_type, notes, created_by)
             VALUES (%d, %d, '%s', %s, '%s', %s, '%s', 'completed', 'refund', %s, %d)",
            intval($data['booking_id']),
            intval($data['company_id']),
            sql_escape($data['payment_method'] ?? 'cash'),
            floatval($data['amount']),
            sql_escape($data['currency'] ?? 'THB'),
            !empty($data['reference_id']) ? "'" . sql_escape($data['reference_id']) . "'" : 'NULL',
            sql_escape($data['payment_date'] ?? date('Y-m-d')),
            !empty($data['notes']) ? "'" . sql_escape($data['notes']) . "'" : 'NULL',
            intval($data['created_by'] ?? 0)
        );

        mysqli_query($this->conn, $sql);
        $id = mysqli_insert_id($this->conn);

        if ($id) {
            $this->syncBookingPaymentStatus(intval($data['booking_id']), intval($data['company_id']));
        }
        return $id;
    }

    // ─── Payment Status Sync ───────────────────────────────────

    /**
     * Recalculate booking payment totals and update status
     */
    public function syncBookingPaymentStatus(int $bookingId, int $comId): void
    {
        // Sum completed payments (excluding refunds)
        $sql = "SELECT 
                    COALESCE(SUM(CASE WHEN payment_type != 'refund' AND status = 'completed' THEN amount ELSE 0 END), 0) AS total_paid,
                    COALESCE(SUM(CASE WHEN payment_type = 'refund' AND status = 'completed' THEN amount ELSE 0 END), 0) AS total_refunded,
                    COALESCE(SUM(CASE WHEN payment_type = 'deposit' AND status = 'completed' THEN amount ELSE 0 END), 0) AS deposit_total
                FROM tour_booking_payments
                WHERE booking_id = " . intval($bookingId) . "
                  AND company_id = " . intval($comId) . "
                  AND deleted_at IS NULL";

        $result = mysqli_query($this->conn, $sql);
        $sums = $result ? mysqli_fetch_assoc($result) : [];

        $totalPaid = floatval($sums['total_paid'] ?? 0) - floatval($sums['total_refunded'] ?? 0);
        $depositTotal = floatval($sums['deposit_total'] ?? 0);

        // Get booking total_amount
        $sql2 = "SELECT total_amount FROM tour_bookings WHERE id = " . intval($bookingId) . " AND company_id = " . intval($comId);
        $result2 = mysqli_query($this->conn, $sql2);
        $booking = $result2 ? mysqli_fetch_assoc($result2) : null;
        if (!$booking) return;

        $totalAmount = floatval($booking['total_amount']);
        $amountDue = max(0, $totalAmount - $totalPaid);

        // Determine payment_status
        if ($totalPaid <= 0 && floatval($sums['total_refunded'] ?? 0) > 0) {
            $paymentStatus = 'refunded';
        } elseif ($totalPaid <= 0) {
            $paymentStatus = 'unpaid';
        } elseif ($totalPaid >= $totalAmount) {
            $paymentStatus = 'paid';
        } elseif ($depositTotal > 0 && $totalPaid == $depositTotal) {
            $paymentStatus = 'deposit';
        } else {
            $paymentStatus = 'partial';
        }

        // Update booking
        $sql3 = sprintf(
            "UPDATE tour_bookings SET 
                payment_status = '%s', 
                amount_paid = %s, 
                amount_due = %s, 
                deposit_amount = %s
             WHERE id = %d AND company_id = %d",
            sql_escape($paymentStatus),
            $totalPaid,
            $amountDue,
            $depositTotal,
            intval($bookingId),
            intval($comId)
        );
        mysqli_query($this->conn, $sql3);
    }

    // ─── Stats ─────────────────────────────────────────────────

    /**
     * Get payment summary for a booking
     */
    public function getBookingPaymentSummary(int $bookingId, int $comId): array
    {
        $sql = "SELECT 
                    COUNT(*) AS payment_count,
                    COALESCE(SUM(CASE WHEN payment_type != 'refund' AND status = 'completed' THEN amount ELSE 0 END), 0) AS total_paid,
                    COALESCE(SUM(CASE WHEN payment_type = 'refund' AND status = 'completed' THEN amount ELSE 0 END), 0) AS total_refunded,
                    COALESCE(SUM(CASE WHEN status IN ('pending','pending_review') THEN amount ELSE 0 END), 0) AS pending_amount
                FROM tour_booking_payments
                WHERE booking_id = " . intval($bookingId) . "
                  AND company_id = " . intval($comId) . "
                  AND deleted_at IS NULL";

        $result = mysqli_query($this->conn, $sql);
        $row = $result ? mysqli_fetch_assoc($result) : [];

        return [
            'payment_count'  => intval($row['payment_count'] ?? 0),
            'total_paid'     => floatval($row['total_paid'] ?? 0),
            'total_refunded' => floatval($row['total_refunded'] ?? 0),
            'pending_amount' => floatval($row['pending_amount'] ?? 0),
            'net_paid'       => floatval($row['total_paid'] ?? 0) - floatval($row['total_refunded'] ?? 0),
        ];
    }

    /**
     * Get pending slip reviews for a company (admin dashboard)
     */
    public function getPendingSlipReviews(int $comId): array
    {
        $sql = "SELECT p.*, b.booking_number, b.travel_date,
                       u.name AS created_by_name
                FROM tour_booking_payments p
                JOIN tour_bookings b ON p.booking_id = b.id
                LEFT JOIN authorize u ON p.created_by = u.id
                WHERE p.company_id = " . intval($comId) . "
                  AND p.status = 'pending_review'
                  AND p.deleted_at IS NULL
                ORDER BY p.created_at ASC";

        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    // ─── Helpers ───────────────────────────────────────────────

    /**
     * Get payment method labels (bilingual)
     */
    public static function getMethodLabels(bool $isThai = false): array
    {
        return $isThai ? [
            'cash'          => 'เงินสด',
            'bank_transfer' => 'โอนเงิน',
            'credit_card'   => 'บัตรเครดิต',
            'promptpay'     => 'พร้อมเพย์',
            'stripe'        => 'Stripe',
            'cheque'        => 'เช็ค',
        ] : [
            'cash'          => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'credit_card'   => 'Credit Card',
            'promptpay'     => 'PromptPay',
            'stripe'        => 'Stripe',
            'cheque'        => 'Cheque',
        ];
    }

    /**
     * Get payment type labels (bilingual)
     */
    public static function getTypeLabels(bool $isThai = false): array
    {
        return $isThai ? [
            'deposit' => 'มัดจำ',
            'partial' => 'ชำระบางส่วน',
            'full'    => 'ชำระเต็ม',
            'refund'  => 'คืนเงิน',
        ] : [
            'deposit' => 'Deposit',
            'partial' => 'Partial',
            'full'    => 'Full Payment',
            'refund'  => 'Refund',
        ];
    }

    /**
     * Get status labels with colors (bilingual)
     */
    public static function getStatusConfig(bool $isThai = false): array
    {
        return [
            'pending'        => ['label' => $isThai ? 'รอดำเนินการ' : 'Pending',        'color' => '#f59e0b', 'bg' => '#fef3c7', 'icon' => 'fa-clock-o'],
            'pending_review' => ['label' => $isThai ? 'รอตรวจสอบ' : 'Pending Review',    'color' => '#8b5cf6', 'bg' => '#ede9fe', 'icon' => 'fa-eye'],
            'completed'      => ['label' => $isThai ? 'สำเร็จ' : 'Completed',            'color' => '#059669', 'bg' => '#d1fae5', 'icon' => 'fa-check-circle'],
            'rejected'       => ['label' => $isThai ? 'ปฏิเสธ' : 'Rejected',             'color' => '#dc2626', 'bg' => '#fee2e2', 'icon' => 'fa-times-circle'],
            'refunded'       => ['label' => $isThai ? 'คืนเงิน' : 'Refunded',            'color' => '#6366f1', 'bg' => '#e0e7ff', 'icon' => 'fa-undo'],
        ];
    }
}
