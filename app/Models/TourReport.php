<?php
namespace App\Models;

class TourReport extends BaseModel
{
    protected string $table = 'tour_bookings';
    protected bool $useCompanyFilter = true;

    // ─── Check-in List Data ────────────────────────────────────

    /**
     * Get bookings for Check-in List PDF, split into direct vs agent sections.
     *
     * @param int    $comId
     * @param string $tourDate      Y-m-d
     * @param string $section       all|direct|agent
     * @param string $tourActivity  Optional activity filter from booking items
     * @return array ['direct' => [...], 'agent' => [...grouped by agent...]]
     */
    public function getCheckinData(int $comId, string $tourDate, string $section = 'all', string $tourActivity = ''): array
    {
        $cid  = intval($comId);
        $date = sql_escape($tourDate);

        $where = "b.company_id = $cid
                  AND b.travel_date = '$date'
                  AND b.status IN ('confirmed','completed')
                  AND b.deleted_at IS NULL";

        // Optional tour activity filter — filter by product_type_id (integer ID)
        if (!empty($tourActivity) && is_numeric($tourActivity)) {
            $typeId = intval($tourActivity);
            $where .= " AND b.id IN (
                SELECT booking_id FROM tour_booking_items
                WHERE item_type = 'tour' AND product_type_id = $typeId
            )";
        }

        $sql = "SELECT b.*, 
                       cust.name_en AS customer_name, cust.name_th AS customer_name_th,
                       cust.phone AS customer_phone,
                       agt.name_en AS agent_name, agt.name_th AS agent_name_th,
                       loc.name AS pickup_location_name
                FROM tour_bookings b
                LEFT JOIN company cust ON b.customer_id = cust.id
                LEFT JOIN company agt  ON b.agent_id = agt.id
                LEFT JOIN tour_locations loc ON b.pickup_location_id = loc.id
                WHERE $where
                ORDER BY b.pickup_time ASC, b.id ASC";

        $result = mysqli_query($this->conn, $sql);
        $direct = [];
        $agent  = []; // grouped by agent_id

        while ($result && $row = mysqli_fetch_assoc($result)) {
            $row['total_pax'] = intval($row['pax_adult']) + intval($row['pax_child']) + intval($row['pax_infant']);

            if (empty($row['agent_id'])) {
                $direct[] = $row;
            } else {
                $agentKey = intval($row['agent_id']);
                if (!isset($agent[$agentKey])) {
                    $agent[$agentKey] = [
                        'agent_name' => $row['agent_name'] ?: ('Agent #' . $agentKey),
                        'bookings'   => [],
                    ];
                }
                $agent[$agentKey]['bookings'][] = $row;
            }
        }

        // Filter by section
        if ($section === 'direct') {
            $agent = [];
        } elseif ($section === 'agent') {
            $direct = [];
        }

        return ['direct' => $direct, 'agent' => $agent];
    }

    // ─── Pickup Report Data ────────────────────────────────────

    /**
     * Get bookings for Pickup Report PDF.
     *
     * @param int    $comId
     * @param string $tourDate     Y-m-d
     * @param string $grouping     time|location
     * @param string $tourActivity Optional
     * @return array ['groups' => [...], 'totals' => [...]]
     */
    public function getPickupData(int $comId, string $tourDate, string $grouping = 'time', string $tourActivity = ''): array
    {
        $cid  = intval($comId);
        $date = sql_escape($tourDate);

        $where = "b.company_id = $cid 
                  AND b.travel_date = '$date' 
                  AND b.status IN ('confirmed','completed') 
                  AND b.deleted_at IS NULL";

        // Optional tour activity filter — filter by product_type_id (integer ID)
        if (!empty($tourActivity) && is_numeric($tourActivity)) {
            $typeId = intval($tourActivity);
            $where .= " AND b.id IN (
                SELECT booking_id FROM tour_booking_items
                WHERE item_type = 'tour' AND product_type_id = $typeId
            )";
        }

        $orderBy = $grouping === 'location'
            ? 'loc.name ASC, b.pickup_time ASC, b.id ASC'
            : 'b.pickup_time ASC, b.id ASC';

        $sql = "SELECT b.*, 
                       cust.name_en AS customer_name, cust.name_th AS customer_name_th,
                       cust.phone AS customer_phone,
                       agt.name_en AS agent_name, agt.name_th AS agent_name_th,
                       loc.name AS pickup_location_name
                FROM tour_bookings b
                LEFT JOIN company cust ON b.customer_id = cust.id
                LEFT JOIN company agt  ON b.agent_id = agt.id
                LEFT JOIN tour_locations loc ON b.pickup_location_id = loc.id
                WHERE $where
                ORDER BY $orderBy";

        $result = mysqli_query($this->conn, $sql);
        $groups = [];
        $totalPax = 0;
        $totalBookings = 0;

        while ($result && $row = mysqli_fetch_assoc($result)) {
            $row['total_pax'] = intval($row['pax_adult']) + intval($row['pax_child']) + intval($row['pax_infant']);
            $totalPax += $row['total_pax'];
            $totalBookings++;

            if ($grouping === 'location') {
                $key = $row['pickup_location_name'] ?: ($row['pickup_hotel'] ?: 'Unknown');
            } else {
                $key = !empty($row['pickup_time']) ? date('H:i', strtotime($row['pickup_time'])) : 'No Time';
            }

            if (!isset($groups[$key])) {
                $groups[$key] = [];
            }
            $groups[$key][] = $row;
        }

        return [
            'groups' => $groups,
            'totals' => [
                'pax'      => $totalPax,
                'bookings' => $totalBookings,
            ],
        ];
    }

    // ─── Tour Activities Dropdown ──────────────────────────────

    /**
     * Get distinct product types used in tour booking items for filter dropdown.
     * Returns [{id, name}] from the type table.
     */
    public function getTourActivities(int $comId): array
    {
        $cid = intval($comId);
        $sql = "SELECT DISTINCT t.id, t.name
                FROM tour_booking_items bi
                JOIN tour_bookings b ON bi.booking_id = b.id
                JOIN type t ON bi.product_type_id = t.id
                WHERE b.company_id = $cid
                  AND b.deleted_at IS NULL
                  AND bi.item_type = 'tour'
                  AND bi.product_type_id IS NOT NULL
                  AND bi.product_type_id > 0
                ORDER BY t.name ASC";

        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        while ($result && $row = mysqli_fetch_assoc($result)) {
            $rows[] = ['id' => $row['id'], 'name' => $row['name']];
        }
        return $rows;
    }
}
