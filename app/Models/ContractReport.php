<?php
namespace App\Models;

/**
 * ContractReport — Aggregate stats for tour operator contract management
 *
 * Produces daily/weekly/monthly summaries used by:
 *   - Operator admin dashboard
 *   - Super admin platform dashboard
 *   - Auto-email digests (cron-driven)
 */
class ContractReport extends BaseModel
{
    protected string $table = 'agent_contracts';
    protected bool $useCompanyFilter = false;

    /**
     * Daily summary for an operator
     */
    public function daily(int $operatorComId, ?string $date = null): array
    {
        $date = $date ?: date('Y-m-d');
        $dateEsc = mysqli_real_escape_string($this->conn, $date);
        $com = intval($operatorComId);

        return [
            'date'                => $date,
            'operator_id'         => $com,
            'new_contracts'       => $this->countByDate("agent_contracts", "company_id = $com AND is_operator_level = 1", 'created_at', $dateEsc),
            'new_agents'          => $this->countByDate("tour_operator_agents", "operator_company_id = $com AND status = 'approved'", 'approved_at', $dateEsc),
            'new_registrations'   => $this->countByDate("tour_operator_agents", "operator_company_id = $com", 'created_at', $dateEsc),
            'pending_approvals'   => $this->countWhere("tour_operator_agents", "operator_company_id = $com AND status = 'pending' AND deleted_at IS NULL"),
            'sync_events'         => $this->countByDate("tour_contract_sync_log", "company_id = $com", 'created_at', $dateEsc),
            'bookings_today'      => $this->countByDate("tour_bookings", "company_id = $com AND deleted_at IS NULL", 'created_at', $dateEsc),
        ];
    }

    /**
     * Weekly summary (last 7 days ending today, or ending on $weekEnd)
     */
    public function weekly(int $operatorComId, ?string $weekEnd = null): array
    {
        $weekEnd = $weekEnd ?: date('Y-m-d');
        $weekStart = date('Y-m-d', strtotime("$weekEnd -6 days"));
        $com = intval($operatorComId);
        $startEsc = mysqli_real_escape_string($this->conn, $weekStart);
        $endEsc   = mysqli_real_escape_string($this->conn, $weekEnd);

        // Top 5 agents by booking count in period
        $sqlAgents = "SELECT b.agent_id, c.name_en AS agent_name, COUNT(*) AS booking_count, SUM(b.total_amount) AS revenue
                      FROM tour_bookings b
                      LEFT JOIN company c ON b.agent_id = c.id
                      WHERE b.company_id = $com
                        AND b.deleted_at IS NULL
                        AND b.created_at BETWEEN '$startEsc 00:00:00' AND '$endEsc 23:59:59'
                        AND b.agent_id IS NOT NULL AND b.agent_id > 0
                      GROUP BY b.agent_id
                      ORDER BY booking_count DESC
                      LIMIT 5";
        $resAgents = mysqli_query($this->conn, $sqlAgents);
        $topAgents = $resAgents ? mysqli_fetch_all($resAgents, MYSQLI_ASSOC) : [];

        // Daily booking trend
        $sqlTrend = "SELECT DATE(created_at) AS day, COUNT(*) AS cnt, SUM(total_amount) AS rev
                     FROM tour_bookings
                     WHERE company_id = $com
                       AND deleted_at IS NULL
                       AND created_at BETWEEN '$startEsc 00:00:00' AND '$endEsc 23:59:59'
                     GROUP BY DATE(created_at)
                     ORDER BY day";
        $resTrend = mysqli_query($this->conn, $sqlTrend);
        $trend = $resTrend ? mysqli_fetch_all($resTrend, MYSQLI_ASSOC) : [];

        return [
            'period_start'      => $weekStart,
            'period_end'        => $weekEnd,
            'operator_id'       => $com,
            'new_contracts'     => $this->countBetween("agent_contracts", "company_id = $com AND is_operator_level = 1", 'created_at', $startEsc, $endEsc),
            'new_agents'        => $this->countBetween("tour_operator_agents", "operator_company_id = $com AND status = 'approved'", 'approved_at', $startEsc, $endEsc),
            'sync_events'       => $this->countBetween("tour_contract_sync_log", "company_id = $com", 'created_at', $startEsc, $endEsc),
            'bookings'          => $this->countBetween("tour_bookings", "company_id = $com AND deleted_at IS NULL", 'created_at', $startEsc, $endEsc),
            'revenue'           => $this->sumBetween("tour_bookings", "company_id = $com AND deleted_at IS NULL", 'total_amount', 'created_at', $startEsc, $endEsc),
            'top_agents'        => $topAgents,
            'daily_trend'       => $trend,
        ];
    }

    /**
     * Monthly summary
     */
    public function monthly(int $operatorComId, ?string $month = null): array
    {
        $month = $month ?: date('Y-m');
        $monthStart = $month . '-01';
        $monthEnd = date('Y-m-t', strtotime($monthStart));
        $com = intval($operatorComId);
        $startEsc = mysqli_real_escape_string($this->conn, $monthStart);
        $endEsc   = mysqli_real_escape_string($this->conn, $monthEnd);

        // Previous month for comparison
        $prevMonth = date('Y-m', strtotime("$monthStart -1 month"));
        $prevStart = $prevMonth . '-01';
        $prevEnd = date('Y-m-t', strtotime($prevStart));
        $prevStartEsc = mysqli_real_escape_string($this->conn, $prevStart);
        $prevEndEsc   = mysqli_real_escape_string($this->conn, $prevEnd);

        $current = [
            'bookings' => $this->countBetween("tour_bookings", "company_id = $com AND deleted_at IS NULL", 'created_at', $startEsc, $endEsc),
            'revenue'  => $this->sumBetween("tour_bookings", "company_id = $com AND deleted_at IS NULL", 'total_amount', 'created_at', $startEsc, $endEsc),
        ];
        $previous = [
            'bookings' => $this->countBetween("tour_bookings", "company_id = $com AND deleted_at IS NULL", 'created_at', $prevStartEsc, $prevEndEsc),
            'revenue'  => $this->sumBetween("tour_bookings", "company_id = $com AND deleted_at IS NULL", 'total_amount', 'created_at', $prevStartEsc, $prevEndEsc),
        ];

        // Top products by booking count
        $sqlProducts = "SELECT bi.model_id, m.model_name, COUNT(*) AS bookings, SUM(bi.subtotal) AS revenue
                        FROM tour_booking_items bi
                        INNER JOIN tour_bookings b ON bi.booking_id = b.id
                        LEFT JOIN model m ON bi.model_id = m.id
                        WHERE b.company_id = $com
                          AND b.deleted_at IS NULL
                          AND b.created_at BETWEEN '$startEsc 00:00:00' AND '$endEsc 23:59:59'
                          AND bi.model_id IS NOT NULL
                        GROUP BY bi.model_id
                        ORDER BY bookings DESC
                        LIMIT 10";
        $resProducts = mysqli_query($this->conn, $sqlProducts);
        $topProducts = $resProducts ? mysqli_fetch_all($resProducts, MYSQLI_ASSOC) : [];

        return [
            'month'           => $month,
            'period_start'    => $monthStart,
            'period_end'      => $monthEnd,
            'operator_id'     => $com,
            'current'         => $current,
            'previous'        => $previous,
            'change_pct'      => [
                'bookings' => $this->pctChange($previous['bookings'], $current['bookings']),
                'revenue'  => $this->pctChange($previous['revenue'], $current['revenue']),
            ],
            'new_contracts'   => $this->countBetween("agent_contracts", "company_id = $com AND is_operator_level = 1", 'created_at', $startEsc, $endEsc),
            'new_agents'      => $this->countBetween("tour_operator_agents", "operator_company_id = $com AND status = 'approved'", 'approved_at', $startEsc, $endEsc),
            'top_products'    => $topProducts,
        ];
    }

    /**
     * Platform-wide super admin summary (across all operators)
     */
    public function platformDaily(?string $date = null): array
    {
        $date = $date ?: date('Y-m-d');
        $dateEsc = mysqli_real_escape_string($this->conn, $date);
        return [
            'date'                => $date,
            'new_contracts'       => $this->countByDate("agent_contracts", "is_operator_level = 1", 'created_at', $dateEsc),
            'new_registrations'   => $this->countByDate("tour_operator_agents", "1=1", 'created_at', $dateEsc),
            'pending_approvals'   => $this->countWhere("tour_operator_agents", "status = 'pending' AND deleted_at IS NULL"),
            'sync_events'         => $this->countByDate("tour_contract_sync_log", "1=1", 'created_at', $dateEsc),
            'bookings_today'      => $this->countByDate("tour_bookings", "deleted_at IS NULL", 'created_at', $dateEsc),
            'active_operators'    => $this->countDistinct("tour_operator_agents", "operator_company_id", "status = 'approved' AND deleted_at IS NULL"),
            'active_agents'       => $this->countDistinct("tour_operator_agents", "agent_company_id", "status = 'approved' AND deleted_at IS NULL"),
        ];
    }

    // ─── Helpers ──────────────────────────────────────────────

    private function countWhere(string $table, string $where): int
    {
        $sql = "SELECT COUNT(*) AS c FROM $table WHERE $where";
        $res = mysqli_query($this->conn, $sql);
        return $res ? intval(mysqli_fetch_assoc($res)['c']) : 0;
    }

    private function countByDate(string $table, string $where, string $dateCol, string $date): int
    {
        $sql = "SELECT COUNT(*) AS c FROM $table WHERE $where AND DATE($dateCol) = '$date'";
        $res = mysqli_query($this->conn, $sql);
        return $res ? intval(mysqli_fetch_assoc($res)['c']) : 0;
    }

    private function countBetween(string $table, string $where, string $dateCol, string $start, string $end): int
    {
        $sql = "SELECT COUNT(*) AS c FROM $table WHERE $where AND $dateCol BETWEEN '$start 00:00:00' AND '$end 23:59:59'";
        $res = mysqli_query($this->conn, $sql);
        return $res ? intval(mysqli_fetch_assoc($res)['c']) : 0;
    }

    private function sumBetween(string $table, string $where, string $col, string $dateCol, string $start, string $end): float
    {
        $sql = "SELECT COALESCE(SUM($col), 0) AS s FROM $table WHERE $where AND $dateCol BETWEEN '$start 00:00:00' AND '$end 23:59:59'";
        $res = mysqli_query($this->conn, $sql);
        return $res ? floatval(mysqli_fetch_assoc($res)['s']) : 0.0;
    }

    private function countDistinct(string $table, string $col, string $where): int
    {
        $sql = "SELECT COUNT(DISTINCT $col) AS c FROM $table WHERE $where";
        $res = mysqli_query($this->conn, $sql);
        return $res ? intval(mysqli_fetch_assoc($res)['c']) : 0;
    }

    private function pctChange(float $prev, float $curr): ?float
    {
        if ($prev <= 0) return null;
        return round((($curr - $prev) / $prev) * 100, 1);
    }
}
