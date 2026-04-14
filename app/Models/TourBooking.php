<?php
namespace App\Models;

class TourBooking extends BaseModel
{
    protected string $table = 'tour_bookings';
    protected bool $useCompanyFilter = true;

    // ─── Booking Number ────────────────────────────────────────

    /**
     * Generate next booking number: BK-YYMMDD-001
     */
    public function generateBookingNumber(int $comId): string
    {
        $prefix = 'BK-' . date('ymd') . '-';
        $sql = "SELECT booking_number FROM tour_bookings 
                WHERE company_id = " . intval($comId) . " 
                  AND booking_number LIKE '" . sql_escape($prefix) . "%'
                ORDER BY booking_number DESC LIMIT 1";
        $result = mysqli_query($this->conn, $sql);
        $row = $result ? mysqli_fetch_assoc($result) : null;

        if ($row) {
            $last = intval(substr($row['booking_number'], -3));
            $seq = $last + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    // ─── List / Count ──────────────────────────────────────────

    /**
     * Get bookings with agent + customer JOINs
     */
    public function getBookings(int $comId, array $filters = [], int $offset = 0, int $limit = 25): array
    {
        $where = "b.company_id = " . intval($comId) . " AND b.deleted_at IS NULL";

        if (!empty($filters['search'])) {
            $s = sql_escape(trim($filters['search']));
            $where .= " AND (b.booking_number LIKE '%$s%' OR b.booking_by LIKE '%$s%' 
                         OR cust.name_en LIKE '%$s%' OR cust.name_th LIKE '%$s%'
                         OR b.voucher_number LIKE '%$s%')";
        }
        if (!empty($filters['status'])) {
            $where .= " AND b.status = '" . sql_escape($filters['status']) . "'";
        }
        if (!empty($filters['agent_id'])) {
            $where .= " AND b.agent_id = " . intval($filters['agent_id']);
        }
        if (!empty($filters['date_from'])) {
            $where .= " AND b.travel_date >= '" . sql_escape($filters['date_from']) . "'";
        }
        if (!empty($filters['date_to'])) {
            $where .= " AND b.travel_date <= '" . sql_escape($filters['date_to']) . "'";
        }

        $sql = "SELECT b.*, 
                       cust.name_en AS customer_name, cust.name_th AS customer_name_th,
                       agt.name_en AS agent_name
                FROM tour_bookings b
                LEFT JOIN company cust ON b.customer_id = cust.id
                LEFT JOIN company agt  ON b.agent_id = agt.id
                WHERE $where
                ORDER BY b.travel_date DESC, b.id DESC
                LIMIT $offset, $limit";

        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function countBookings(int $comId, array $filters = []): int
    {
        $where = "b.company_id = " . intval($comId) . " AND b.deleted_at IS NULL";

        if (!empty($filters['search'])) {
            $s = sql_escape(trim($filters['search']));
            $where .= " AND (b.booking_number LIKE '%$s%' OR b.booking_by LIKE '%$s%'
                         OR cust.name_en LIKE '%$s%' OR cust.name_th LIKE '%$s%')";
        }
        if (!empty($filters['status'])) {
            $where .= " AND b.status = '" . sql_escape($filters['status']) . "'";
        }
        if (!empty($filters['agent_id'])) {
            $where .= " AND b.agent_id = " . intval($filters['agent_id']);
        }
        if (!empty($filters['date_from'])) {
            $where .= " AND b.travel_date >= '" . sql_escape($filters['date_from']) . "'";
        }
        if (!empty($filters['date_to'])) {
            $where .= " AND b.travel_date <= '" . sql_escape($filters['date_to']) . "'";
        }

        $sql = "SELECT COUNT(*) AS total
                FROM tour_bookings b
                LEFT JOIN company cust ON b.customer_id = cust.id
                WHERE $where";

        $result = mysqli_query($this->conn, $sql);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        return intval($row['total'] ?? 0);
    }

    // ─── Single Booking ────────────────────────────────────────

    /**
     * Find booking with customer + agent names, items, and pax
     */
    public function findBooking(int $id, int $comId): ?array
    {
        $sql = "SELECT b.*, 
                       cust.name_en AS customer_name, cust.name_th AS customer_name_th,
                       agt.name_en AS agent_name, agt.name_th AS agent_name_th,
                       loc.name AS pickup_location_name, loc.location_type AS pickup_location_type
                FROM tour_bookings b
                LEFT JOIN company cust ON b.customer_id = cust.id
                LEFT JOIN company agt  ON b.agent_id = agt.id
                LEFT JOIN tour_locations loc ON b.pickup_location_id = loc.id
                WHERE b.id = " . intval($id) . " 
                  AND b.company_id = " . intval($comId) . "
                  AND b.deleted_at IS NULL
                LIMIT 1";

        $result = mysqli_query($this->conn, $sql);
        $booking = $result ? mysqli_fetch_assoc($result) : null;

        if ($booking) {
            $booking['items'] = $this->getBookingItems(intval($booking['id']));
            $booking['pax']   = $this->getBookingPax(intval($booking['id']));
        }

        return $booking ?: null;
    }

    // ─── Stats ─────────────────────────────────────────────────

    public function getStats(int $comId): array
    {
        $cid = intval($comId);
        $today = date('Y-m-d');

        $sql = "SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN status='draft' THEN 1 ELSE 0 END) AS draft,
                    SUM(CASE WHEN status='confirmed' THEN 1 ELSE 0 END) AS confirmed,
                    SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) AS completed,
                    SUM(CASE WHEN status='cancelled' THEN 1 ELSE 0 END) AS cancelled,
                    SUM(CASE WHEN travel_date='$today' THEN 1 ELSE 0 END) AS today_bookings,
                    COALESCE(SUM(CASE WHEN status IN ('confirmed','completed') THEN total_amount ELSE 0 END), 0) AS revenue
                FROM tour_bookings
                WHERE company_id = $cid AND deleted_at IS NULL";

        $result = mysqli_query($this->conn, $sql);
        $row = $result ? mysqli_fetch_assoc($result) : [];

        return [
            'total'          => intval($row['total'] ?? 0),
            'draft'          => intval($row['draft'] ?? 0),
            'confirmed'      => intval($row['confirmed'] ?? 0),
            'completed'      => intval($row['completed'] ?? 0),
            'cancelled'      => intval($row['cancelled'] ?? 0),
            'today_bookings' => intval($row['today_bookings'] ?? 0),
            'revenue'        => floatval($row['revenue'] ?? 0),
        ];
    }

    // ─── CRUD ──────────────────────────────────────────────────

    public function createBooking(array $data): int
    {
        $cols = "company_id, booking_number, customer_id, agent_id, booking_by, travel_date,
                 pax_adult, pax_child, pax_infant,
                 pickup_location_id, pickup_hotel, pickup_room, pickup_time,
                 voucher_number, entrance_fee, subtotal, discount, vat, total_amount, currency,
                 status, remark, created_by";

        $vals = sprintf(
            "'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s'",
            intval($data['company_id']),
            sql_escape($data['booking_number']),
            intval($data['customer_id'] ?? 0),
            intval($data['agent_id'] ?? 0),
            sql_escape($data['booking_by'] ?? ''),
            sql_escape($data['travel_date']),
            intval($data['pax_adult'] ?? 0),
            intval($data['pax_child'] ?? 0),
            intval($data['pax_infant'] ?? 0),
            intval($data['pickup_location_id'] ?? 0),
            sql_escape($data['pickup_hotel'] ?? ''),
            sql_escape($data['pickup_room'] ?? ''),
            sql_escape($data['pickup_time'] ?? ''),
            sql_escape($data['voucher_number'] ?? ''),
            floatval($data['entrance_fee'] ?? 0),
            floatval($data['subtotal'] ?? 0),
            floatval($data['discount'] ?? 0),
            floatval($data['vat'] ?? 0),
            floatval($data['total_amount'] ?? 0),
            sql_escape($data['currency'] ?? 'THB'),
            sql_escape($data['status'] ?? 'draft'),
            sql_escape($data['remark'] ?? ''),
            intval($data['created_by'] ?? 0)
        );

        $args = [];
        $args['table'] = 'tour_bookings';
        $args['columns'] = $cols;
        $args['value'] = $vals;
        $id = $this->hard->insertDbMax($args);

        if (!$id) {
            throw new \RuntimeException('Failed to create booking');
        }

        return $id;
    }

    public function updateBooking(int $id, array $data, int $comId): bool
    {
        $set = sprintf(
            "customer_id='%s', agent_id='%s', booking_by='%s', travel_date='%s',
             pax_adult='%s', pax_child='%s', pax_infant='%s',
             pickup_location_id='%s', pickup_hotel='%s', pickup_room='%s', pickup_time='%s',
             voucher_number='%s', entrance_fee='%s', subtotal='%s', discount='%s', vat='%s', total_amount='%s',
             currency='%s', status='%s', remark='%s'",
            intval($data['customer_id'] ?? 0),
            intval($data['agent_id'] ?? 0),
            sql_escape($data['booking_by'] ?? ''),
            sql_escape($data['travel_date']),
            intval($data['pax_adult'] ?? 0),
            intval($data['pax_child'] ?? 0),
            intval($data['pax_infant'] ?? 0),
            intval($data['pickup_location_id'] ?? 0),
            sql_escape($data['pickup_hotel'] ?? ''),
            sql_escape($data['pickup_room'] ?? ''),
            sql_escape($data['pickup_time'] ?? ''),
            sql_escape($data['voucher_number'] ?? ''),
            floatval($data['entrance_fee'] ?? 0),
            floatval($data['subtotal'] ?? 0),
            floatval($data['discount'] ?? 0),
            floatval($data['vat'] ?? 0),
            floatval($data['total_amount'] ?? 0),
            sql_escape($data['currency'] ?? 'THB'),
            sql_escape($data['status'] ?? 'draft'),
            sql_escape($data['remark'] ?? '')
        );

        $sql = "UPDATE tour_bookings SET $set WHERE id = " . intval($id) . " AND company_id = " . intval($comId);
        return mysqli_query($this->conn, $sql) ? true : false;
    }

    public function deleteBooking(int $id, int $comId): bool
    {
        $sql = "UPDATE tour_bookings SET deleted_at = NOW() WHERE id = " . intval($id) . " AND company_id = " . intval($comId);
        return mysqli_query($this->conn, $sql) && mysqli_affected_rows($this->conn) > 0;
    }

    // ─── Items ─────────────────────────────────────────────────

    public function getBookingItems(int $bookingId): array
    {
        $sql = "SELECT * FROM tour_booking_items WHERE booking_id = " . intval($bookingId) . " ORDER BY id";
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Save items — delete-and-reinsert pattern
     */
    public function saveBookingItems(int $bookingId, array $items): void
    {
        $bid = intval($bookingId);
        mysqli_query($this->conn, "DELETE FROM tour_booking_items WHERE booking_id = $bid");

        foreach ($items as $item) {
            $priceThai     = floatval($item['price_thai'] ?? 0);
            $priceForeign  = floatval($item['price_foreigner'] ?? 0);
            $qtyThai       = intval($item['qty_thai'] ?? 0);
            $qtyForeigner  = intval($item['qty_foreigner'] ?? 0);
            $amount        = ($qtyThai * $priceThai) + ($qtyForeigner * $priceForeign);
            // Keep legacy fields for backward compat
            $quantity  = $qtyThai + $qtyForeigner;
            $unitPrice = $quantity > 0 ? ($amount / $quantity) : 0;

            $vals = sprintf(
                "'%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s'",
                $bid,
                sql_escape($item['item_type'] ?? 'tour'),
                sql_escape($item['description'] ?? ''),
                intval($item['contract_rate_id'] ?? 0),
                sql_escape($item['rate_label'] ?? ''),
                $quantity,
                $unitPrice,
                $priceThai,
                $priceForeign,
                $qtyThai,
                $qtyForeigner,
                $amount,
                sql_escape($item['notes'] ?? '')
            );

            $args = [];
            $args['table'] = 'tour_booking_items';
            $args['columns'] = 'booking_id, item_type, description, contract_rate_id, rate_label, quantity, unit_price, price_thai, price_foreigner, qty_thai, qty_foreigner, amount, notes';
            $args['value'] = $vals;
            $this->hard->insertDB($args);
        }
    }

    // ─── Passengers ────────────────────────────────────────────

    public function getBookingPax(int $bookingId): array
    {
        $sql = "SELECT * FROM tour_booking_pax WHERE booking_id = " . intval($bookingId) . " ORDER BY id";
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Save passengers — delete-and-reinsert pattern
     */
    public function saveBookingPax(int $bookingId, array $paxList): void
    {
        $bid = intval($bookingId);
        mysqli_query($this->conn, "DELETE FROM tour_booking_pax WHERE booking_id = $bid");

        foreach ($paxList as $pax) {
            $vals = sprintf(
                "'%s','%s','%s','%s','%s','%s','%s'",
                $bid,
                sql_escape($pax['pax_type'] ?? 'adult'),
                intval($pax['is_thai'] ?? 0),
                sql_escape($pax['full_name'] ?? ''),
                sql_escape($pax['nationality'] ?? ''),
                sql_escape($pax['passport_number'] ?? ''),
                sql_escape($pax['notes'] ?? '')
            );

            $args = [];
            $args['table'] = 'tour_booking_pax';
            $args['columns'] = 'booking_id, pax_type, is_thai, full_name, nationality, passport_number, notes';
            $args['value'] = $vals;
            $this->hard->insertDB($args);
        }
    }

    // ─── Calendar Data ─────────────────────────────────────────

    /**
     * Get bookings for calendar view (date range)
     */
    public function getCalendarBookings(int $comId, string $start, string $end): array
    {
        $sql = "SELECT b.id, b.booking_number, b.travel_date, b.status, b.total_pax, b.total_amount,
                       cust.name_en AS customer_name,
                       agt.name_en AS agent_name
                FROM tour_bookings b
                LEFT JOIN company cust ON b.customer_id = cust.id
                LEFT JOIN company agt  ON b.agent_id = agt.id
                WHERE b.company_id = " . intval($comId) . "
                  AND b.deleted_at IS NULL
                  AND b.travel_date BETWEEN '" . sql_escape($start) . "' AND '" . sql_escape($end) . "'
                ORDER BY b.travel_date, b.id";

        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    // ─── Agent Dropdown ────────────────────────────────────────

    /**
     * Get agents for dropdown in booking form
     */
    public function getAgentDropdown(int $comId): array
    {
        $sql = "SELECT c.id, c.name_en, c.name_th
                FROM company c
                INNER JOIN tour_agent_profiles tap ON c.id = tap.company_ref_id
                WHERE tap.company_id = " . intval($comId) . "
                  AND tap.deleted_at IS NULL
                  AND c.deleted_at IS NULL
                ORDER BY c.name_en";

        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Get customers for dropdown
     */
    public function getCustomerDropdown(int $comId): array
    {
        $sql = "SELECT id, name_en, name_th 
                FROM company 
                WHERE company_id = " . intval($comId) . "
                  AND customer = '1'
                  AND deleted_at IS NULL
                ORDER BY name_en";

        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Get pickup locations for dropdown
     */
    public function getPickupLocations(int $comId): array
    {
        $sql = "SELECT id, name, location_type
                FROM tour_locations
                WHERE company_id = " . intval($comId) . "
                  AND location_type IN ('pickup','hotel')
                  AND deleted_at IS NULL
                ORDER BY location_type, name";

        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    // ─── Customer Search (AJAX) ────────────────────────────────

    /**
     * Search customers by name for autocomplete
     */
    public function searchCustomers(int $comId, string $term, int $limit = 15): array
    {
        $s = sql_escape(trim($term));
        $sql = "SELECT id, name_en, name_th, phone, email
                FROM company
                WHERE company_id = " . intval($comId) . "
                  AND customer = '1'
                  AND deleted_at IS NULL
                  AND (name_en LIKE '%$s%' OR name_th LIKE '%$s%' OR phone LIKE '%$s%' OR email LIKE '%$s%')
                ORDER BY name_en
                LIMIT " . intval($limit);

        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Quick-create a customer record
     */
    public function quickCreateCustomer(int $comId, string $nameEn, string $phone = ''): int
    {
        $vals = sprintf(
            "'%s','%s','%s','1'",
            intval($comId),
            sql_escape($nameEn),
            sql_escape($phone)
        );

        $args = [];
        $args['table'] = 'company';
        $args['columns'] = 'company_id, name_en, phone, customer';
        $args['value'] = $vals;
        return intval($this->hard->insertDbMax($args));
    }

    /**
     * Search products (type + model tables) for company
     */
    public function searchProducts(int $comId, string $term, int $limit = 15): array
    {
        $s = sql_escape(trim($term));
        $cid = intval($comId);
        $lim = intval($limit);

        // Search model table first (actual products with prices)
        $sql = "SELECT m.id, m.model_name as name, CONCAT(t.name, COALESCE(CONCAT(' - ', m.des), '')) as des,
                       t.name as category_name, m.price
                FROM model m
                LEFT JOIN type t ON m.type_id = t.id
                WHERE m.company_id = $cid
                  AND m.deleted_at IS NULL
                  AND (m.model_name LIKE '%$s%' OR m.des LIKE '%$s%' OR t.name LIKE '%$s%')
                ORDER BY m.model_name
                LIMIT $lim";

        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $row['source'] = 'model';
            $rows[] = $row;
        }

        // Also search type table (categories) if not enough results
        if (count($rows) < $lim) {
            $remaining = $lim - count($rows);
            $sql2 = "SELECT t.id, t.name, t.des, c.name as category_name, 0 as price
                     FROM type t
                     LEFT JOIN category c ON t.cat_id = c.id
                     WHERE t.company_id = $cid
                       AND t.deleted_at IS NULL
                       AND (t.name LIKE '%$s%' OR t.des LIKE '%$s%')
                     ORDER BY t.name
                     LIMIT $remaining";

            $result2 = mysqli_query($this->conn, $sql2);
            while ($result2 && $row2 = mysqli_fetch_assoc($result2)) {
                $row2['source'] = 'type';
                $rows[] = $row2;
            }
        }

        return $rows;
    }

    /**
     * Search staff (user table) for company
     */
    public function searchStaff(int $comId, string $term, int $limit = 15): array
    {
        $s = sql_escape(trim($term));
        $sql = "SELECT usr_id, name, surname, email, phone
                FROM user
                WHERE com_id = " . intval($comId) . "
                  AND (name LIKE '%$s%' OR surname LIKE '%$s%' OR email LIKE '%$s%')
                ORDER BY name
                LIMIT " . intval($limit);

        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = [
                'id'   => $row['usr_id'],
                'name' => trim($row['name'] . ' ' . $row['surname']),
                'email' => $row['email'],
                'phone' => $row['phone'],
            ];
        }
        return $rows;
    }
}
