<?php
namespace App\Models;

/**
 * TourCheckin — DB layer for customer self-check-in module.
 *
 * Handles: token lookup, check-in write, staff override/reset,
 *          audit log, staff dashboard data.
 */
class TourCheckin extends BaseModel
{
    protected string $table = 'tour_bookings';

    // ─── Token Lookup ──────────────────────────────────────────

    /**
     * Find a booking by its check-in token.
     * Returns full booking row + primary contact name, or null if not found.
     */
    public function findByToken(string $token): ?array
    {
        $tok = sql_escape($token);
        $sql = "SELECT
                    b.id, b.booking_number, b.company_id, b.agent_id,
                    b.travel_date, b.status,
                    b.pax_adult, b.pax_child, b.pax_infant, b.total_pax,
                    b.total_amount, b.remark,
                    b.checkin_token, b.checkin_token_exp,
                    b.checkin_status, b.checkin_at, b.checkin_by,
                    (SELECT contact_name FROM tour_booking_contacts
                     WHERE booking_id = b.id ORDER BY id LIMIT 1) AS contact_name,
                    (SELECT mobile FROM tour_booking_contacts
                     WHERE booking_id = b.id ORDER BY id LIMIT 1) AS contact_phone,
                    c.name_en AS company_name_en,
                    c.name_th AS company_name_th,
                    c.logo    AS company_logo
                FROM tour_bookings b
                LEFT JOIN company c ON b.company_id = c.id
                WHERE b.checkin_token = '$tok'
                  AND b.deleted_at IS NULL
                LIMIT 1";

        $result = mysqli_query($this->conn, $sql);
        return ($result && $row = mysqli_fetch_assoc($result)) ? $row : null;
    }

    /**
     * Validate that a token is not expired and booking is in a check-in-eligible status.
     * Returns array ['valid'=>bool, 'reason'=>string].
     */
    public function validateToken(array $booking): array
    {
        $today = date('Y-m-d');

        if (empty($booking['checkin_token_exp']) || $today > $booking['checkin_token_exp']) {
            return ['valid' => false, 'reason' => 'expired'];
        }

        $ineligible = ['draft', 'pending', 'cancelled'];
        if (in_array($booking['status'], $ineligible)) {
            return ['valid' => false, 'reason' => 'status:' . $booking['status']];
        }

        return ['valid' => true, 'reason' => ''];
    }

    // ─── Check-In Actions ─────────────────────────────────────

    /**
     * Mark a booking as checked in (customer self-check-in).
     * Idempotent — safe to call if already checked in.
     */
    public function markCheckedIn(int $bookingId, string $ip = '', string $ua = ''): bool
    {
        $bid = intval($bookingId);
        $now = date('Y-m-d H:i:s');

        $ok = mysqli_query($this->conn,
            "UPDATE tour_bookings
             SET checkin_status=1, checkin_at='$now', checkin_by='self'
             WHERE id=$bid AND deleted_at IS NULL"
        );

        if ($ok && mysqli_affected_rows($this->conn) > 0) {
            $this->log($bookingId, 'checkin', 'customer', null, $ip, $ua);
            return true;
        }
        return false;
    }

    /**
     * Staff manual check-in override.
     * Returns the checkin_at timestamp string on success, or null on failure.
     */
    public function staffOverride(int $bookingId, int $staffId, string $ip = ''): ?string
    {
        $bid = intval($bookingId);
        $now = date('Y-m-d H:i:s');

        $ok = mysqli_query($this->conn,
            "UPDATE tour_bookings
             SET checkin_status=1, checkin_at='$now', checkin_by='staff'
             WHERE id=$bid AND deleted_at IS NULL"
        );

        if (!$ok || mysqli_affected_rows($this->conn) < 1) {
            return null;
        }

        $this->log($bookingId, 'staff_override', 'staff', $staffId, $ip);
        return $now;
    }

    /**
     * Reset check-in status (staff only). Token remains valid for re-check-in.
     */
    public function resetCheckin(int $bookingId, int $staffId, string $ip = ''): bool
    {
        $bid = intval($bookingId);

        $ok = mysqli_query($this->conn,
            "UPDATE tour_bookings
             SET checkin_status=0, checkin_at=NULL, checkin_by=NULL
             WHERE id=$bid AND deleted_at IS NULL"
        );

        if ($ok && mysqli_affected_rows($this->conn) > 0) {
            $this->log($bookingId, 'reset', 'staff', $staffId, $ip);
        }
        return (bool) $ok;
    }

    // ─── Rate Limiting ────────────────────────────────────────

    /**
     * Check if an IP has exceeded 5 check-in attempts in the last 60 seconds.
     */
    public function isRateLimited(string $ip): bool
    {
        $safeIp = sql_escape($ip);
        $cutoff = date('Y-m-d H:i:s', time() - 60);
        $result = mysqli_query($this->conn,
            "SELECT COUNT(*) AS cnt FROM tour_checkin_log
             WHERE actor_ip = '$safeIp' AND created_at >= '$cutoff'"
        );
        $row = $result ? mysqli_fetch_assoc($result) : null;
        return $row && intval($row['cnt']) >= 5;
    }

    // ─── Audit Log ────────────────────────────────────────────

    public function log(
        int $bookingId,
        string $action,
        string $actorType,
        ?int $actorId = null,
        string $ip = '',
        string $ua = ''
    ): void {
        $bid       = intval($bookingId);
        $action    = sql_escape($action);
        $actorType = sql_escape($actorType);
        $actorId   = $actorId !== null ? intval($actorId) : 'NULL';
        $ip        = sql_escape(substr($ip, 0, 45));
        $ua        = sql_escape(substr($ua, 0, 255));

        mysqli_query($this->conn,
            "INSERT INTO tour_checkin_log (booking_id, action, actor_type, actor_id, actor_ip, actor_ua)
             VALUES ($bid, '$action', '$actorType', $actorId, '$ip', '$ua')"
        );
    }

    /**
     * Get check-in log entries for a booking.
     */
    public function getLog(int $bookingId): array
    {
        $bid    = intval($bookingId);
        $result = mysqli_query($this->conn,
            "SELECT l.*, CONCAT(u.name, ' ', u.surname) AS staff_name
             FROM tour_checkin_log l
             LEFT JOIN users u ON l.actor_id = u.usr_id
             WHERE l.booking_id = $bid
             ORDER BY l.created_at DESC"
        );
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    // ─── Staff Dashboard ──────────────────────────────────────

    /**
     * Get all bookings for a given date for the staff check-in dashboard.
     */
    public function getStaffDashboard(int $comId, string $date): array
    {
        $cid  = intval($comId);
        $d    = sql_escape($date);
        $sql  = "SELECT
                    b.id, b.booking_number, b.travel_date, b.status,
                    b.total_pax, b.pax_adult, b.pax_child, b.pax_infant,
                    b.checkin_status, b.checkin_at, b.checkin_by,
                    (SELECT contact_name FROM tour_booking_contacts
                     WHERE booking_id = b.id ORDER BY id LIMIT 1) AS contact_name,
                    (SELECT mobile FROM tour_booking_contacts
                     WHERE booking_id = b.id ORDER BY id LIMIT 1) AS contact_phone,
                    COALESCE(c.name_en, c.name_th) AS agent_name
                 FROM tour_bookings b
                 LEFT JOIN company c ON b.agent_id = c.id
                 WHERE b.company_id = $cid
                   AND b.travel_date = '$d'
                   AND b.status NOT IN ('draft','cancelled')
                   AND b.deleted_at IS NULL
                 ORDER BY b.checkin_status ASC, b.id ASC";

        $result = mysqli_query($this->conn, $sql);
        $rows   = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Summary counts for a date: total, checked_in, not_checked_in.
     */
    public function getDashboardSummary(int $comId, string $date): array
    {
        $cid = intval($comId);
        $d   = sql_escape($date);
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(checkin_status) AS checked_in,
                    SUM(total_pax) AS total_pax,
                    SUM(CASE WHEN checkin_status=1 THEN total_pax ELSE 0 END) AS pax_checked_in
                FROM tour_bookings
                WHERE company_id = $cid
                  AND travel_date = '$d'
                  AND status NOT IN ('draft','cancelled')
                  AND deleted_at IS NULL";
        $result = mysqli_query($this->conn, $sql);
        $row    = $result ? mysqli_fetch_assoc($result) : [];
        return [
            'total'          => intval($row['total']          ?? 0),
            'checked_in'     => intval($row['checked_in']     ?? 0),
            'not_checked_in' => intval($row['total'] ?? 0) - intval($row['checked_in'] ?? 0),
            'total_pax'      => intval($row['total_pax']      ?? 0),
            'pax_checked_in' => intval($row['pax_checked_in'] ?? 0),
        ];
    }
}
