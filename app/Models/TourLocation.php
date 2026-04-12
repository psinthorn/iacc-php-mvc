<?php
namespace App\Models;

/**
 * TourLocation Model
 * 
 * Manages pickup/dropoff/activity/hotel locations for tour bookings.
 */
class TourLocation extends BaseModel
{
    protected string $table = 'tour_locations';
    protected bool $useCompanyFilter = true;

    /**
     * Get all locations for a tenant with optional filters
     */
    public function getLocations(int $comId, array $filters = []): array
    {
        $comId = \sql_int($comId);
        $conds = '';

        if (!empty($filters['search'])) {
            $s = \sql_escape($filters['search']);
            $conds .= " AND (name LIKE '%$s%' OR address LIKE '%$s%')";
        }
        if (!empty($filters['location_type'])) {
            $conds .= " AND location_type = '" . \sql_escape($filters['location_type']) . "'";
        }

        $sql = "SELECT * FROM tour_locations
                WHERE company_id = '$comId' AND deleted_at IS NULL
                $conds
                ORDER BY location_type ASC, name ASC";
        return $this->fetchAll($sql);
    }

    /**
     * Find a single location
     */
    public function findLocation(int $id, int $comId): ?array
    {
        $id = \sql_int($id);
        $comId = \sql_int($comId);
        $sql = "SELECT * FROM tour_locations WHERE id = '$id' AND company_id = '$comId' AND deleted_at IS NULL";
        $r = mysqli_query($this->conn, $sql);
        return ($r && mysqli_num_rows($r) > 0) ? mysqli_fetch_assoc($r) : null;
    }

    /**
     * Create a new location
     */
    public function createLocation(array $data): int
    {
        $sql = "INSERT INTO tour_locations (company_id, name, location_type, address, notes)
                VALUES (
                    '" . \sql_int($data['company_id']) . "',
                    '" . \sql_escape($data['name']) . "',
                    '" . \sql_escape($data['location_type'] ?? 'pickup') . "',
                    " . (!empty($data['address']) ? "'" . \sql_escape($data['address']) . "'" : "NULL") . ",
                    " . (!empty($data['notes']) ? "'" . \sql_escape($data['notes']) . "'" : "NULL") . "
                )";
        mysqli_query($this->conn, $sql);
        return mysqli_insert_id($this->conn);
    }

    /**
     * Update an existing location
     */
    public function updateLocation(int $id, array $data, int $comId): bool
    {
        $id = \sql_int($id);
        $comId = \sql_int($comId);
        $sql = "UPDATE tour_locations SET
                    name = '" . \sql_escape($data['name']) . "',
                    location_type = '" . \sql_escape($data['location_type'] ?? 'pickup') . "',
                    address = " . (!empty($data['address']) ? "'" . \sql_escape($data['address']) . "'" : "NULL") . ",
                    notes = " . (!empty($data['notes']) ? "'" . \sql_escape($data['notes']) . "'" : "NULL") . "
                WHERE id = '$id' AND company_id = '$comId' AND deleted_at IS NULL";
        return mysqli_query($this->conn, $sql);
    }

    /**
     * Soft delete a location
     */
    public function deleteLocation(int $id, int $comId): bool
    {
        $id = \sql_int($id);
        $comId = \sql_int($comId);
        $sql = "UPDATE tour_locations SET deleted_at = NOW() WHERE id = '$id' AND company_id = '$comId'";
        return mysqli_query($this->conn, $sql);
    }

    /**
     * Get locations for dropdown (by type)
     */
    public function getLocationDropdown(int $comId, string $type = ''): array
    {
        $comId = \sql_int($comId);
        $typeCond = !empty($type) ? " AND location_type = '" . \sql_escape($type) . "'" : '';
        $sql = "SELECT id, name, location_type FROM tour_locations
                WHERE company_id = '$comId' AND deleted_at IS NULL $typeCond
                ORDER BY name ASC";
        return $this->fetchAll($sql);
    }

    /**
     * Count locations by type
     */
    public function getStats(int $comId): array
    {
        $comId = \sql_int($comId);
        $sql = "SELECT location_type, COUNT(*) as cnt
                FROM tour_locations
                WHERE company_id = '$comId' AND deleted_at IS NULL
                GROUP BY location_type";
        $r = mysqli_query($this->conn, $sql);
        $stats = ['total' => 0, 'pickup' => 0, 'dropoff' => 0, 'activity' => 0, 'hotel' => 0];
        if ($r) {
            while ($row = mysqli_fetch_assoc($r)) {
                $stats[$row['location_type']] = intval($row['cnt']);
                $stats['total'] += intval($row['cnt']);
            }
        }
        return $stats;
    }

    private function fetchAll(string $sql): array
    {
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) { while ($row = mysqli_fetch_assoc($result)) $rows[] = $row; }
        return $rows;
    }
}
