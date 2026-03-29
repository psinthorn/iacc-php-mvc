<?php

namespace App\Models;

class Dashboard
{
    private $db;

    public function __construct()
    {
        global $db;
        $this->db = $db;
    }

    // ========== Admin System Stats ==========

    public function getTotalUsers(): int
    {
        $sql = "SELECT COUNT(*) as count FROM authorize";
        $result = mysqli_query($this->db->conn, $sql);
        return (int) (mysqli_fetch_assoc($result)['count'] ?? 0);
    }

    public function getUsersByRole(): array
    {
        $sql = "SELECT level, COUNT(*) as count FROM authorize GROUP BY level";
        $result = mysqli_query($this->db->conn, $sql);
        $roles = [0 => 0, 1 => 0, 2 => 0];
        while ($row = mysqli_fetch_assoc($result)) {
            $roles[$row['level']] = (int) $row['count'];
        }
        return $roles;
    }

    public function getTotalCompanies(): int
    {
        $sql = "SELECT COUNT(*) as count FROM company WHERE deleted_at IS NULL";
        $result = mysqli_query($this->db->conn, $sql);
        return (int) (mysqli_fetch_assoc($result)['count'] ?? 0);
    }

    public function getActiveCompanies(int $days = 30): int
    {
        $sql = "SELECT COUNT(DISTINCT company_id) as count FROM (
            SELECT ven_id as company_id FROM pr WHERE date >= DATE_SUB(NOW(), INTERVAL $days DAY)
            UNION
            SELECT cus_id as company_id FROM pr WHERE date >= DATE_SUB(NOW(), INTERVAL $days DAY)
        ) as active";
        $result = mysqli_query($this->db->conn, $sql);
        return (int) (mysqli_fetch_assoc($result)['count'] ?? 0);
    }

    public function getLockedAccounts(): int
    {
        $sql = "SELECT COUNT(*) as count FROM authorize WHERE locked_until > NOW()";
        $result = mysqli_query($this->db->conn, $sql);
        return (int) (mysqli_fetch_assoc($result)['count'] ?? 0);
    }

    public function getFailedLogins(int $hours = 24): int
    {
        $sql = "SELECT COUNT(*) as count FROM login_attempts 
                WHERE successful = 0 AND attempted_at > DATE_SUB(NOW(), INTERVAL $hours HOUR)";
        $result = @mysqli_query($this->db->conn, $sql);
        if (!$result) {
            return 0; // Table/column may not exist
        }
        return (int) (mysqli_fetch_assoc($result)['count'] ?? 0);
    }

    // ========== Business Summary Report ==========

    public function getReportSummary(string $dateFilter): array
    {
        $sql = "SELECT 
            COUNT(DISTINCT pr.id) as total_pr,
            SUM(CASE WHEN pr.status >= 1 THEN 1 ELSE 0 END) as total_qa,
            SUM(CASE WHEN pr.status >= 2 THEN 1 ELSE 0 END) as total_po,
            SUM(CASE WHEN pr.status >= 4 THEN 1 ELSE 0 END) as total_iv,
            SUM(CASE WHEN pr.status >= 5 THEN 1 ELSE 0 END) as total_tax
            FROM pr WHERE $dateFilter";
        $result = mysqli_query($this->db->conn, $sql);
        return mysqli_fetch_assoc($result) ?: [
            'total_pr' => 0, 'total_qa' => 0, 'total_po' => 0,
            'total_iv' => 0, 'total_tax' => 0
        ];
    }

    public function getTopCustomers(string $dateFilter, int $limit = 5): ?\mysqli_result
    {
        $sql = "SELECT c.id, c.name_en, c.name_th,
            COUNT(pr.id) as tx_count,
            SUM(CASE WHEN pr.status >= 4 THEN 1 ELSE 0 END) as invoice_count
            FROM pr 
            JOIN company c ON pr.cus_id = c.id
            WHERE $dateFilter
            GROUP BY c.id, c.name_en, c.name_th
            ORDER BY tx_count DESC
            LIMIT $limit";
        return mysqli_query($this->db->conn, $sql);
    }

    public function getQuickCompanies(int $limit = 8): ?\mysqli_result
    {
        $sql = "SELECT DISTINCT c.id, c.name_en, c.name_th, c.name_sh, c.logo,
            (SELECT MAX(pr.date) FROM pr WHERE pr.ven_id = c.id OR pr.cus_id = c.id) as last_activity,
            c.customer, c.vender
            FROM company c
            WHERE c.deleted_at IS NULL
            ORDER BY last_activity DESC
            LIMIT $limit";
        return mysqli_query($this->db->conn, $sql);
    }

    // ========== User Dashboard (Company Data) ==========

    public function getSalesToday(int $comId, string $companyFilter): float
    {
        $sql = "SELECT IFNULL(SUM(pay.volumn), 0) as total FROM pay 
                JOIN po ON pay.po_id = po.id
                JOIN pr ON po.ref = pr.id
                WHERE DATE(pay.date) = CURDATE() $companyFilter";
        $result = mysqli_query($this->db->conn, $sql);
        return (float) (mysqli_fetch_assoc($result)['total'] ?? 0);
    }

    public function getSalesMonth(int $comId, string $companyFilter): float
    {
        $monthStart = date('Y-m-01');
        $curDate = date('Y-m-d');
        $sql = "SELECT IFNULL(SUM(pay.volumn), 0) as total FROM pay 
                JOIN po ON pay.po_id = po.id
                JOIN pr ON po.ref = pr.id
                WHERE DATE(pay.date) >= '$monthStart' AND DATE(pay.date) <= '$curDate' $companyFilter";
        $result = mysqli_query($this->db->conn, $sql);
        return (float) (mysqli_fetch_assoc($result)['total'] ?? 0);
    }

    public function getPendingOrderCount(string $companyFilter): int
    {
        $sql = "SELECT COUNT(po.id) as count FROM po 
                JOIN pr ON po.ref = pr.id
                WHERE po.over = 0 $companyFilter";
        $result = mysqli_query($this->db->conn, $sql);
        return (int) (mysqli_fetch_assoc($result)['count'] ?? 0);
    }

    public function getTotalOrderCount(string $companyFilter): int
    {
        $sql = "SELECT COUNT(po.id) as count FROM po
                JOIN pr ON po.ref = pr.id
                WHERE 1=1 $companyFilter";
        $result = mysqli_query($this->db->conn, $sql);
        return (int) (mysqli_fetch_assoc($result)['count'] ?? 0);
    }

    public function getRecentPayments(string $companyFilter, int $limit = 5): ?\mysqli_result
    {
        $sql = "SELECT pay.*, po.name, po.tax 
                FROM pay 
                LEFT JOIN po ON pay.po_id = po.id 
                LEFT JOIN pr ON po.ref = pr.id
                WHERE 1=1 $companyFilter
                ORDER BY pay.date DESC LIMIT $limit";
        return mysqli_query($this->db->conn, $sql);
    }

    public function getPendingPOs(string $companyFilter, int $limit = 5): ?\mysqli_result
    {
        $sql = "SELECT po.*, 
                (SELECT SUM(volumn) FROM pay WHERE po_id = po.id) as paid_amount
                FROM po 
                JOIN pr ON po.ref = pr.id
                WHERE po.over = 0 $companyFilter
                ORDER BY po.date DESC LIMIT $limit";
        return mysqli_query($this->db->conn, $sql);
    }

    public function getCompletedOrders(string $companyFilter): int
    {
        $monthStart = date('Y-m-01');
        $sql = "SELECT COUNT(po.id) as count FROM po 
                JOIN pr ON po.ref = pr.id
                WHERE po.over = 1 AND DATE(po.date) >= '$monthStart' $companyFilter";
        $result = mysqli_query($this->db->conn, $sql);
        return (int) (mysqli_fetch_assoc($result)['count'] ?? 0);
    }

    public function getInvoiceCount(string $companyFilter): int
    {
        $monthStart = date('Y-m-01');
        $sql = "SELECT COUNT(DISTINCT iv.tex) as count FROM iv 
                JOIN po ON iv.tex = po.id
                JOIN pr ON po.ref = pr.id
                WHERE DATE(iv.createdate) >= '$monthStart' $companyFilter";
        $result = mysqli_query($this->db->conn, $sql);
        return (int) (mysqli_fetch_assoc($result)['count'] ?? 0);
    }

    public function getTaxInvoiceCount(string $companyFilter): int
    {
        $monthStart = date('Y-m-01');
        $sql = "SELECT COUNT(DISTINCT iv.texiv) as count FROM iv 
                JOIN po ON iv.tex = po.id
                JOIN pr ON po.ref = pr.id
                WHERE iv.texiv > 0 AND DATE(iv.texiv_create) >= '$monthStart' $companyFilter";
        $result = mysqli_query($this->db->conn, $sql);
        return (int) (mysqli_fetch_assoc($result)['count'] ?? 0);
    }

    public function getRecentInvoices(string $companyFilter, int $limit = 5): ?\mysqli_result
    {
        $sql = "SELECT iv.tex as po_id, iv.createdate, iv.status_iv,
                po.name as description,
                (SELECT SUM(price*quantity) FROM product WHERE po_id = po.id) as subtotal
                FROM iv 
                JOIN po ON iv.tex = po.id
                JOIN pr ON po.ref = pr.id
                WHERE 1=1 $companyFilter
                ORDER BY iv.createdate DESC LIMIT $limit";
        return mysqli_query($this->db->conn, $sql);
    }

    public function getRecentTaxInvoices(string $companyFilter, int $limit = 5): ?\mysqli_result
    {
        $sql = "SELECT iv.texiv, iv.tex as po_id, iv.texiv_create, iv.countmailtax,
                po.name as description,
                (SELECT SUM(price*quantity) FROM product WHERE po_id = po.id) as subtotal
                FROM iv 
                JOIN po ON iv.tex = po.id
                JOIN pr ON po.ref = pr.id
                WHERE iv.texiv > 0 $companyFilter
                ORDER BY iv.texiv_create DESC LIMIT $limit";
        return mysqli_query($this->db->conn, $sql);
    }

    // ========== Chart Data (Dashboard Charts) ==========

    /**
     * Monthly revenue for last N months (payments received).
     */
    public function getMonthlyRevenue(string $companyFilter, int $months = 12): array
    {
        $sql = "SELECT 
                    DATE_FORMAT(pay.date, '%Y-%m') as month,
                    IFNULL(SUM(pay.volumn), 0) as total
                FROM pay 
                JOIN po ON pay.po_id = po.id
                JOIN pr ON po.ref = pr.id
                WHERE pay.date >= DATE_SUB(CURDATE(), INTERVAL $months MONTH) $companyFilter
                GROUP BY DATE_FORMAT(pay.date, '%Y-%m')
                ORDER BY month ASC";
        $result = mysqli_query($this->db->conn, $sql);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[$row['month']] = (float) $row['total'];
            }
        }

        // Fill missing months with 0
        $filled = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $m = date('Y-m', strtotime("-$i months"));
            $filled[$m] = $data[$m] ?? 0;
        }
        return $filled;
    }

    /**
     * Monthly expenses for last N months.
     */
    public function getMonthlyExpenses(string $companyFilter, int $months = 12): array
    {
        $comId = (int) ($_SESSION['com_id'] ?? 0);
        $comFilter = $comId > 0 ? " AND com_id = $comId" : "";
        $sql = "SELECT 
                    DATE_FORMAT(expense_date, '%Y-%m') as month,
                    IFNULL(SUM(amount), 0) as total
                FROM expenses 
                WHERE expense_date >= DATE_SUB(CURDATE(), INTERVAL $months MONTH)
                  AND deleted_at IS NULL $comFilter
                GROUP BY DATE_FORMAT(expense_date, '%Y-%m')
                ORDER BY month ASC";
        $result = @mysqli_query($this->db->conn, $sql);
        $data = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $data[$row['month']] = (float) $row['total'];
            }
        }

        $filled = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $m = date('Y-m', strtotime("-$i months"));
            $filled[$m] = $data[$m] ?? 0;
        }
        return $filled;
    }

    /**
     * Payment status distribution (paid / partial / unpaid invoices).
     */
    public function getPaymentStatusDistribution(string $companyFilter): array
    {
        $sql = "SELECT 
                    SUM(CASE WHEN paid >= total AND total > 0 THEN 1 ELSE 0 END) as paid,
                    SUM(CASE WHEN paid > 0 AND paid < total THEN 1 ELSE 0 END) as partial,
                    SUM(CASE WHEN (paid = 0 OR paid IS NULL) AND total > 0 THEN 1 ELSE 0 END) as unpaid
                FROM (
                    SELECT iv.tex,
                        COALESCE((SELECT SUM(price*quantity) FROM product WHERE po_id = po.id), 0) as total,
                        COALESCE((SELECT SUM(volumn) FROM pay WHERE po_id = po.id), 0) as paid
                    FROM iv
                    JOIN po ON iv.tex = po.id
                    JOIN pr ON po.ref = pr.id
                    WHERE iv.createdate >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) $companyFilter
                    GROUP BY iv.tex
                ) as invoice_summary";
        $result = mysqli_query($this->db->conn, $sql);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return [
                'paid'    => (int) ($row['paid'] ?? 0),
                'partial' => (int) ($row['partial'] ?? 0),
                'unpaid'  => (int) ($row['unpaid'] ?? 0),
            ];
        }
        return ['paid' => 0, 'partial' => 0, 'unpaid' => 0];
    }

    /**
     * Order status distribution (pending vs completed).
     */
    public function getOrderStatusDistribution(string $companyFilter): array
    {
        $sql = "SELECT 
                    SUM(CASE WHEN po.over = 0 THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN po.over = 1 THEN 1 ELSE 0 END) as completed
                FROM po 
                JOIN pr ON po.ref = pr.id
                WHERE po.date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) $companyFilter";
        $result = mysqli_query($this->db->conn, $sql);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return [
                'pending'   => (int) ($row['pending'] ?? 0),
                'completed' => (int) ($row['completed'] ?? 0),
            ];
        }
        return ['pending' => 0, 'completed' => 0];
    }

    // ========== AR Aging Report ==========

    /**
     * Accounts Receivable Aging — invoices bucketed by days outstanding.
     */
    public function getArAging(string $companyFilter): array
    {
        $sql = "SELECT 
                    c.id as company_id,
                    COALESCE(c.name_en, c.name_th) as company_name,
                    iv.tex as po_id,
                    po.tax as po_number,
                    iv.createdate as invoice_date,
                    DATEDIFF(CURDATE(), iv.createdate) as days_outstanding,
                    COALESCE((SELECT SUM(price*quantity) FROM product WHERE po_id = po.id), 0) as total_amount,
                    COALESCE((SELECT SUM(volumn) FROM pay WHERE po_id = po.id), 0) as paid_amount
                FROM iv
                JOIN po ON iv.tex = po.id
                JOIN pr ON po.ref = pr.id
                LEFT JOIN company c ON pr.cus_id = c.id
                WHERE 1=1 $companyFilter
                  AND iv.createdate = (SELECT MAX(iv2.createdate) FROM iv iv2 WHERE iv2.tex = iv.tex)
                GROUP BY iv.tex
                HAVING (total_amount - paid_amount) > 0
                ORDER BY days_outstanding DESC";
        $result = mysqli_query($this->db->conn, $sql);
        
        $buckets = [
            'current'  => ['label' => '0-30 days',  'min' => 0,   'max' => 30,  'items' => [], 'total' => 0],
            'days31'   => ['label' => '31-60 days', 'min' => 31,  'max' => 60,  'items' => [], 'total' => 0],
            'days61'   => ['label' => '61-90 days', 'min' => 61,  'max' => 90,  'items' => [], 'total' => 0],
            'days91'   => ['label' => '91-120 days','min' => 91,  'max' => 120, 'items' => [], 'total' => 0],
            'days121'  => ['label' => '120+ days',  'min' => 121, 'max' => 99999,'items' => [], 'total' => 0],
        ];
        $grandTotal = 0;

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $outstanding = (float) $row['total_amount'] - (float) $row['paid_amount'];
                $days = (int) $row['days_outstanding'];
                $row['outstanding'] = $outstanding;

                foreach ($buckets as $key => &$bucket) {
                    if ($days >= $bucket['min'] && $days <= $bucket['max']) {
                        $bucket['items'][] = $row;
                        $bucket['total'] += $outstanding;
                        $grandTotal += $outstanding;
                        break;
                    }
                }
                unset($bucket);
            }
        }

        return ['buckets' => $buckets, 'grand_total' => $grandTotal];
    }
}
