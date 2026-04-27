<?php
namespace App\Models;

/**
 * TourAllotment — Fleet definitions + daily seat allotment management
 *
 * Tracks seat capacity per travel date. Allotment rows are lazy-created
 * when the first booking is confirmed for a given date. Seats are
 * automatically booked/released on booking status transitions.
 */
class TourAllotment extends BaseModel
{
    protected string $table = 'tour_allotments';
    protected bool $useCompanyFilter = true;

    // ─── Fleet CRUD ───────────────────────────────────────────

    /**
     * Get all active fleets for a company
     */
    public function getFleets(int $comId): array
    {
        $sql = sprintf(
            "SELECT * FROM tour_fleets
             WHERE company_id = %d AND deleted_at IS NULL
             ORDER BY fleet_name ASC",
            intval($comId)
        );
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Find a single fleet
     */
    public function findFleet(int $id, int $comId): ?array
    {
        $sql = sprintf(
            "SELECT * FROM tour_fleets WHERE id = %d AND company_id = %d AND deleted_at IS NULL LIMIT 1",
            intval($id), intval($comId)
        );
        $result = mysqli_query($this->conn, $sql);
        return ($result && $row = mysqli_fetch_assoc($result)) ? $row : null;
    }

    /**
     * Get the first active fleet (default for auto-assignment)
     */
    public function getDefaultFleet(int $comId): ?array
    {
        $sql = sprintf(
            "SELECT * FROM tour_fleets
             WHERE company_id = %d AND is_active = 1 AND deleted_at IS NULL
             ORDER BY id ASC LIMIT 1",
            intval($comId)
        );
        $result = mysqli_query($this->conn, $sql);
        return ($result && $row = mysqli_fetch_assoc($result)) ? $row : null;
    }

    /**
     * Create a fleet
     */
    public function createFleet(array $data): int
    {
        $sql = sprintf(
            "INSERT INTO tour_fleets (company_id, fleet_name, fleet_type, capacity, unit_count, is_active, notes)
             VALUES (%d, '%s', '%s', %d, %d, %d, %s)",
            intval($data['company_id']),
            sql_escape($data['fleet_name']),
            sql_escape($data['fleet_type'] ?? 'speedboat'),
            intval($data['capacity'] ?? 38),
            intval($data['unit_count'] ?? 1),
            intval($data['is_active'] ?? 1),
            !empty($data['notes']) ? "'" . sql_escape($data['notes']) . "'" : 'NULL'
        );
        mysqli_query($this->conn, $sql);
        return mysqli_insert_id($this->conn);
    }

    /**
     * Update a fleet
     */
    public function updateFleet(int $id, array $data, int $comId): bool
    {
        $sql = sprintf(
            "UPDATE tour_fleets SET
                fleet_name = '%s',
                fleet_type = '%s',
                capacity   = %d,
                unit_count = %d,
                is_active  = %d,
                notes      = %s
             WHERE id = %d AND company_id = %d AND deleted_at IS NULL",
            sql_escape($data['fleet_name']),
            sql_escape($data['fleet_type'] ?? 'speedboat'),
            intval($data['capacity'] ?? 38),
            intval($data['unit_count'] ?? 1),
            intval($data['is_active'] ?? 1),
            !empty($data['notes']) ? "'" . sql_escape($data['notes']) . "'" : 'NULL',
            intval($id),
            intval($comId)
        );
        return mysqli_query($this->conn, $sql) && mysqli_affected_rows($this->conn) >= 0;
    }

    /**
     * Soft-delete a fleet
     */
    public function deleteFleet(int $id, int $comId): bool
    {
        $sql = sprintf(
            "UPDATE tour_fleets SET deleted_at = NOW() WHERE id = %d AND company_id = %d AND deleted_at IS NULL",
            intval($id), intval($comId)
        );
        return mysqli_query($this->conn, $sql) && mysqli_affected_rows($this->conn) > 0;
    }

    // ─── Allotment Core ───────────────────────────────────────

    /**
     * Get or lazy-create an allotment row for a date.
     * If no allotment exists, creates one using the default fleet's capacity.
     */
    public function getOrCreateAllotment(int $comId, string $travelDate, ?int $fleetId = null): ?array
    {
        // Resolve fleet
        if (!$fleetId) {
            $fleet = $this->getDefaultFleet($comId);
            if (!$fleet) return null;
            $fleetId = intval($fleet['id']);
            $totalSeats = intval($fleet['capacity']) * intval($fleet['unit_count']);
        } else {
            $fleet = $this->findFleet($fleetId, $comId);
            if (!$fleet) return null;
            $totalSeats = intval($fleet['capacity']) * intval($fleet['unit_count']);
        }

        // Try to find existing
        $sql = sprintf(
            "SELECT * FROM tour_allotments
             WHERE company_id = %d AND fleet_id = %d AND travel_date = '%s' AND deleted_at IS NULL
             LIMIT 1",
            intval($comId), intval($fleetId), sql_escape($travelDate)
        );
        $result = mysqli_query($this->conn, $sql);
        $row = $result ? mysqli_fetch_assoc($result) : null;

        if ($row) return $row;

        // Create new allotment
        $sql = sprintf(
            "INSERT INTO tour_allotments (company_id, fleet_id, travel_date, total_seats, booked_seats)
             VALUES (%d, %d, '%s', %d, 0)",
            intval($comId), intval($fleetId), sql_escape($travelDate), $totalSeats
        );
        mysqli_query($this->conn, $sql);
        $id = mysqli_insert_id($this->conn);

        if (!$id) return null;

        return [
            'id'              => $id,
            'company_id'      => $comId,
            'fleet_id'        => $fleetId,
            'travel_date'     => $travelDate,
            'total_seats'     => $totalSeats,
            'booked_seats'    => 0,
            'manual_override' => 0,
            'is_closed'       => 0,
            'closed_reason'   => null,
        ];
    }

    /**
     * Get aggregated allotment for a date (across all fleets)
     */
    public function getAllotmentByDate(int $comId, string $travelDate): ?array
    {
        $sql = sprintf(
            "SELECT
                SUM(total_seats)  AS total_seats,
                SUM(booked_seats) AS booked_seats,
                MAX(is_closed)    AS is_closed
             FROM tour_allotments
             WHERE company_id = %d AND travel_date = '%s' AND deleted_at IS NULL",
            intval($comId), sql_escape($travelDate)
        );
        $result = mysqli_query($this->conn, $sql);
        $row = $result ? mysqli_fetch_assoc($result) : null;

        if (!$row || $row['total_seats'] === null) return null;

        $total  = intval($row['total_seats']);
        $booked = intval($row['booked_seats']);
        return [
            'total_seats'  => $total,
            'booked_seats' => $booked,
            'available'    => $total - $booked,
            'is_closed'    => intval($row['is_closed']),
            'is_overbooked' => $booked > $total,
        ];
    }

    /**
     * Get allotments for a date range (for calendar view)
     */
    public function getAllotmentsByDateRange(int $comId, string $from, string $to): array
    {
        $sql = sprintf(
            "SELECT travel_date,
                    SUM(total_seats)  AS total_seats,
                    SUM(booked_seats) AS booked_seats,
                    MAX(is_closed)    AS is_closed
             FROM tour_allotments
             WHERE company_id = %d AND travel_date BETWEEN '%s' AND '%s' AND deleted_at IS NULL
             GROUP BY travel_date
             ORDER BY travel_date ASC",
            intval($comId), sql_escape($from), sql_escape($to)
        );
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $total  = intval($row['total_seats']);
            $booked = intval($row['booked_seats']);
            $rows[$row['travel_date']] = [
                'total_seats'   => $total,
                'booked_seats'  => $booked,
                'available'     => $total - $booked,
                'is_closed'     => intval($row['is_closed']),
                'is_overbooked' => $booked > $total,
            ];
        }
        return $rows;
    }

    /**
     * Get detailed allotment rows for a specific date (per-fleet breakdown)
     */
    public function getAllotmentDetailByDate(int $comId, string $travelDate): array
    {
        $sql = sprintf(
            "SELECT a.*, f.fleet_name, f.fleet_type, f.capacity AS fleet_capacity
             FROM tour_allotments a
             JOIN tour_fleets f ON a.fleet_id = f.id
             WHERE a.company_id = %d AND a.travel_date = '%s' AND a.deleted_at IS NULL
             ORDER BY f.fleet_name ASC",
            intval($comId), sql_escape($travelDate)
        );
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Get confirmed bookings consuming seats on a date
     */
    public function getConfirmedBookingsForDate(int $comId, string $travelDate): array
    {
        $sql = sprintf(
            "SELECT b.id, b.booking_number, b.travel_date,
                    b.pax_adult, b.pax_child, b.pax_infant,
                    (b.pax_adult + b.pax_child) AS seat_pax,
                    b.status, b.customer_id,
                    COALESCE(c.name_en, c.name_th, 'Walk-in') AS customer_name
             FROM tour_bookings b
             LEFT JOIN company c ON b.customer_id = c.id
             WHERE b.company_id = %d
               AND b.travel_date = '%s'
               AND b.status IN ('confirmed', 'completed')
               AND b.deleted_at IS NULL
             ORDER BY b.booking_number ASC",
            intval($comId), sql_escape($travelDate)
        );
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    // ─── Seat Booking / Release ───────────────────────────────

    /**
     * Book seats (increment booked_seats). Allows overbooking with warning.
     * Returns ['success' => bool, 'is_overbooked' => bool, 'available_after' => int]
     */
    public function bookSeats(int $allotmentId, int $bookingId, int $pax, int $userId): array
    {
        $sql = sprintf(
            "UPDATE tour_allotments SET booked_seats = booked_seats + %d, updated_by = %d
             WHERE id = %d AND deleted_at IS NULL",
            intval($pax), intval($userId), intval($allotmentId)
        );
        $ok = mysqli_query($this->conn, $sql);

        // Read back
        $sql2 = sprintf("SELECT total_seats, booked_seats FROM tour_allotments WHERE id = %d", intval($allotmentId));
        $result = mysqli_query($this->conn, $sql2);
        $row = $result ? mysqli_fetch_assoc($result) : null;

        $bookedAfter = intval($row['booked_seats'] ?? 0);
        $totalSeats  = intval($row['total_seats'] ?? 0);
        $isOverbooked = $bookedAfter > $totalSeats;

        // Audit log
        $this->writeLog($allotmentId, $bookingId, 'book', $pax, $bookedAfter, null, $userId);

        return [
            'success'         => (bool)$ok,
            'is_overbooked'   => $isOverbooked,
            'available_after'  => $totalSeats - $bookedAfter,
            'booked_after'     => $bookedAfter,
            'total_seats'      => $totalSeats,
        ];
    }

    /**
     * Release seats (decrement booked_seats, floor at 0)
     */
    public function releaseSeats(int $allotmentId, int $bookingId, int $pax, int $userId): bool
    {
        $sql = sprintf(
            "UPDATE tour_allotments SET
                booked_seats = GREATEST(0, booked_seats - %d),
                updated_by = %d
             WHERE id = %d AND deleted_at IS NULL",
            intval($pax), intval($userId), intval($allotmentId)
        );
        $ok = mysqli_query($this->conn, $sql);

        // Read back for log
        $sql2 = sprintf("SELECT booked_seats FROM tour_allotments WHERE id = %d", intval($allotmentId));
        $result = mysqli_query($this->conn, $sql2);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        $bookedAfter = intval($row['booked_seats'] ?? 0);

        $this->writeLog($allotmentId, $bookingId, 'release', -$pax, $bookedAfter, null, $userId);

        return (bool)$ok;
    }

    // ─── Status Change Handler (central method) ───────────────

    /**
     * Handle booking status transition for allotment.
     * Called from TourBookingController::store(), TourBookingPayment::syncBookingPaymentStatus(), etc.
     *
     * @param int         $comId      Company ID
     * @param int         $bookingId  Booking ID
     * @param string      $travelDate Travel date (Y-m-d)
     * @param string|null $oldStatus  Previous status (null for new bookings)
     * @param string      $newStatus  New status
     * @param int         $pax        Seat-consuming pax (adult + child, excluding infants)
     * @param int         $userId     User performing the action
     * @return array      ['success', 'is_overbooked', 'is_closed']
     */
    public function handleStatusChange(
        int $comId,
        int $bookingId,
        string $travelDate,
        ?string $oldStatus,
        string $newStatus,
        int $pax,
        int $userId
    ): array {
        $result = ['success' => true, 'is_overbooked' => false, 'is_closed' => false];

        $wasConfirmed = in_array($oldStatus, ['confirmed', 'completed']);
        $isNowConfirmed = in_array($newStatus, ['confirmed', 'completed']);

        if (!$wasConfirmed && $isNowConfirmed) {
            // Transitioning TO confirmed/completed → book seats
            $allotment = $this->getOrCreateAllotment($comId, $travelDate);
            if (!$allotment) return $result;

            $result['is_closed'] = (bool)($allotment['is_closed'] ?? false);
            $bookResult = $this->bookSeats(intval($allotment['id']), $bookingId, $pax, $userId);
            $result['is_overbooked'] = $bookResult['is_overbooked'];
            $result['success'] = $bookResult['success'];

        } elseif ($wasConfirmed && !$isNowConfirmed) {
            // Transitioning FROM confirmed/completed → release seats
            $allotment = $this->getOrCreateAllotment($comId, $travelDate);
            if (!$allotment) return $result;

            $this->releaseSeats(intval($allotment['id']), $bookingId, $pax, $userId);
        }

        return $result;
    }

    /**
     * Handle travel date change on an already-confirmed booking.
     * Releases seats from old date, books on new date.
     */
    public function handleDateChange(
        int $comId,
        int $bookingId,
        string $oldDate,
        string $newDate,
        string $status,
        int $pax,
        int $userId
    ): array {
        $result = ['success' => true, 'is_overbooked' => false, 'is_closed' => false];

        if (!in_array($status, ['confirmed', 'completed'])) return $result;
        if ($oldDate === $newDate) return $result;

        // Release from old date
        $oldAllotment = $this->getOrCreateAllotment($comId, $oldDate);
        if ($oldAllotment) {
            $this->releaseSeats(intval($oldAllotment['id']), $bookingId, $pax, $userId);
        }

        // Book on new date
        $newAllotment = $this->getOrCreateAllotment($comId, $newDate);
        if ($newAllotment) {
            $result['is_closed'] = (bool)($newAllotment['is_closed'] ?? false);
            $bookResult = $this->bookSeats(intval($newAllotment['id']), $bookingId, $pax, $userId);
            $result['is_overbooked'] = $bookResult['is_overbooked'];
        }

        return $result;
    }

    // ─── Admin Actions ────────────────────────────────────────

    /**
     * Recalculate booked_seats from confirmed bookings (safety net)
     */
    public function recalculateBooked(int $comId, string $travelDate): int
    {
        // Count seat-consuming pax from confirmed/completed bookings
        $sql = sprintf(
            "SELECT COALESCE(SUM(pax_adult + pax_child), 0) AS total_pax
             FROM tour_bookings
             WHERE company_id = %d AND travel_date = '%s'
               AND status IN ('confirmed', 'completed')
               AND deleted_at IS NULL",
            intval($comId), sql_escape($travelDate)
        );
        $result = mysqli_query($this->conn, $sql);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        $actualPax = intval($row['total_pax'] ?? 0);

        // Update all allotment rows for this date
        $sql2 = sprintf(
            "UPDATE tour_allotments SET booked_seats = %d
             WHERE company_id = %d AND travel_date = '%s' AND deleted_at IS NULL",
            $actualPax, intval($comId), sql_escape($travelDate)
        );
        mysqli_query($this->conn, $sql2);

        // Log
        $allotments = $this->getAllotmentDetailByDate($comId, $travelDate);
        foreach ($allotments as $a) {
            $this->writeLog(intval($a['id']), null, 'recalculate', 0, $actualPax, 'Recalculated from confirmed bookings', 0);
        }

        return $actualPax;
    }

    /**
     * Admin: manually set total capacity
     */
    public function manualSetCapacity(int $allotmentId, int $newTotal, int $userId): bool
    {
        $sql = sprintf(
            "UPDATE tour_allotments SET total_seats = %d, manual_override = 1, updated_by = %d
             WHERE id = %d AND deleted_at IS NULL",
            intval($newTotal), intval($userId), intval($allotmentId)
        );
        $ok = mysqli_query($this->conn, $sql) && mysqli_affected_rows($this->conn) >= 0;

        if ($ok) {
            $this->writeLog($allotmentId, null, 'manual_set', 0, 0, "Capacity set to {$newTotal}", $userId);
        }
        return $ok;
    }

    /**
     * Close a date for bookings
     */
    public function closeDate(int $allotmentId, string $reason, int $userId): bool
    {
        $sql = sprintf(
            "UPDATE tour_allotments SET is_closed = 1, closed_reason = '%s', updated_by = %d
             WHERE id = %d AND deleted_at IS NULL",
            sql_escape($reason), intval($userId), intval($allotmentId)
        );
        $ok = mysqli_query($this->conn, $sql);

        if ($ok) {
            $this->writeLog($allotmentId, null, 'close', 0, 0, $reason, $userId);
        }
        return (bool)$ok;
    }

    /**
     * Reopen a closed date
     */
    public function reopenDate(int $allotmentId, int $userId): bool
    {
        $sql = sprintf(
            "UPDATE tour_allotments SET is_closed = 0, closed_reason = NULL, updated_by = %d
             WHERE id = %d AND deleted_at IS NULL",
            intval($userId), intval($allotmentId)
        );
        $ok = mysqli_query($this->conn, $sql);

        if ($ok) {
            $this->writeLog($allotmentId, null, 'reopen', 0, 0, null, $userId);
        }
        return (bool)$ok;
    }

    // ─── Audit Log ────────────────────────────────────────────

    /**
     * Write an audit log entry
     */
    private function writeLog(int $allotmentId, ?int $bookingId, string $action, int $delta, int $bookedAfter, ?string $note, int $userId): void
    {
        $sql = sprintf(
            "INSERT INTO tour_allotment_logs (allotment_id, booking_id, action, seats_delta, booked_seats_after, note, created_by)
             VALUES (%d, %s, '%s', %d, %d, %s, %d)",
            intval($allotmentId),
            $bookingId ? intval($bookingId) : 'NULL',
            sql_escape($action),
            intval($delta),
            intval($bookedAfter),
            $note ? "'" . sql_escape($note) . "'" : 'NULL',
            intval($userId)
        );
        mysqli_query($this->conn, $sql);
    }

    /**
     * Get audit log for an allotment
     */
    public function getAuditLog(int $allotmentId): array
    {
        $sql = sprintf(
            "SELECT l.*, b.booking_number, u.name AS user_name
             FROM tour_allotment_logs l
             LEFT JOIN tour_bookings b ON l.booking_id = b.id
             LEFT JOIN authorize u ON l.created_by = u.id
             WHERE l.allotment_id = %d
             ORDER BY l.created_at DESC",
            intval($allotmentId)
        );
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    // ─── Dashboard Summary ───────────────────────────────────

    /**
     * Get allotment summary for upcoming dates (for booking list dashboard).
     * Returns array of dates with allotment + booking count data.
     */
    public function getUpcomingAllotmentSummary(int $comId, int $days = 7): array
    {
        $today = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+{$days} days"));

        // Get allotments for the range
        $allotments = $this->getAllotmentsByDateRange($comId, $today, $endDate);

        // Get default fleet for capacity fallback
        $fleet = $this->getDefaultFleet($comId);
        $defaultCapacity = $fleet ? intval($fleet['capacity']) * intval($fleet['unit_count']) : 0;

        // Count bookings per date (all statuses)
        $sql = sprintf(
            "SELECT travel_date,
                    COUNT(*) AS total_bookings,
                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_bookings,
                    SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) AS draft_bookings,
                    SUM(CASE WHEN status IN ('confirmed','completed') THEN pax_adult + pax_child ELSE 0 END) AS confirmed_pax,
                    SUM(pax_adult + pax_child) AS total_pax
             FROM tour_bookings
             WHERE company_id = %d
               AND travel_date BETWEEN '%s' AND '%s'
               AND deleted_at IS NULL
             GROUP BY travel_date",
            intval($comId), sql_escape($today), sql_escape($endDate)
        );
        $result = mysqli_query($this->conn, $sql);
        $bookingCounts = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $bookingCounts[$row['travel_date']] = $row;
        }

        // Build day-by-day array
        $summary = [];
        for ($i = 0; $i < $days; $i++) {
            $date = date('Y-m-d', strtotime("+{$i} days"));
            $a = $allotments[$date] ?? null;
            $bc = $bookingCounts[$date] ?? null;

            $totalSeats = $a ? intval($a['total_seats']) : $defaultCapacity;
            $bookedSeats = $a ? intval($a['booked_seats']) : 0;
            $available = $totalSeats - $bookedSeats;
            $fillPct = $totalSeats > 0 ? min(100, round(($bookedSeats / $totalSeats) * 100)) : 0;

            $summary[] = [
                'date'               => $date,
                'total_seats'        => $totalSeats,
                'booked_seats'       => $bookedSeats,
                'available'          => $available,
                'fill_pct'           => $fillPct,
                'is_closed'          => $a ? intval($a['is_closed']) : 0,
                'is_overbooked'      => $a ? (bool)$a['is_overbooked'] : false,
                'has_allotment'      => (bool)$a,
                'total_bookings'     => intval($bc['total_bookings'] ?? 0),
                'confirmed_bookings' => intval($bc['confirmed_bookings'] ?? 0),
                'draft_bookings'     => intval($bc['draft_bookings'] ?? 0),
                'confirmed_pax'      => intval($bc['confirmed_pax'] ?? 0),
                'total_pax'          => intval($bc['total_pax'] ?? 0),
            ];
        }

        return $summary;
    }

    // ─── Static Helpers (bilingual labels) ────────────────────

    public static function getFleetTypeLabels(bool $isThai = false): array
    {
        return $isThai ? [
            'speedboat' => 'สปีดโบ๊ท',
            'ferry'     => 'เรือเฟอร์รี่',
            'van'       => 'รถตู้',
            'bus'       => 'รถบัส',
        ] : [
            'speedboat' => 'Speedboat',
            'ferry'     => 'Ferry',
            'van'       => 'Van',
            'bus'       => 'Bus',
        ];
    }

    public static function getActionLabels(bool $isThai = false): array
    {
        return $isThai ? [
            'book'        => 'จองที่นั่ง',
            'release'     => 'ปล่อยที่นั่ง',
            'manual_set'  => 'ตั้งค่าด้วยตนเอง',
            'close'       => 'ปิดวัน',
            'reopen'      => 'เปิดวันใหม่',
            'recalculate' => 'คำนวณใหม่',
        ] : [
            'book'        => 'Seats booked',
            'release'     => 'Seats released',
            'manual_set'  => 'Manual override',
            'close'       => 'Date closed',
            'reopen'      => 'Date reopened',
            'recalculate' => 'Recalculated',
        ];
    }
}
