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
                WHERE success = 0 AND attempt_time > DATE_SUB(NOW(), INTERVAL $hours HOUR)";
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
}
