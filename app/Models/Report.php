<?php
namespace App\Models;

/**
 * Report Model - Invoice payment tracking & business summary
 * Replaces: invoice-payments.php, report.php (data layer)
 */
class Report extends BaseModel
{
    protected string $table = 'iv';
    protected bool $useCompanyFilter = false;

    /* ======== Invoice Payment Tracking ======== */

    public function getInvoicePaymentSummary(int $comId, string $search = ''): array
    {
        $cf  = $this->companyFilterPR($comId);
        $sc  = $this->searchCondIP($search);

        $sql = "SELECT
                    COUNT(DISTINCT iv.tex) AS total_invoices,
                    SUM(CASE WHEN paid.paid_amount >= prod.total_amount AND prod.total_amount > 0 THEN 1 ELSE 0 END) AS paid_count,
                    SUM(CASE WHEN paid.paid_amount > 0 AND paid.paid_amount < prod.total_amount THEN 1 ELSE 0 END) AS partial_count,
                    SUM(CASE WHEN (paid.paid_amount IS NULL OR paid.paid_amount = 0) AND prod.total_amount > 0 THEN 1 ELSE 0 END) AS unpaid_count,
                    COALESCE(SUM(prod.total_amount), 0) AS total_amount,
                    COALESCE(SUM(paid.paid_amount), 0) AS total_paid
                FROM iv
                JOIN po ON iv.tex = po.id
                JOIN pr ON po.ref = pr.id
                LEFT JOIN company ON pr.cus_id = company.id
                LEFT JOIN (SELECT po_id, SUM(price * quantity) AS total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
                LEFT JOIN (SELECT po_id, SUM(volumn) AS paid_amount FROM pay WHERE deleted_at IS NULL GROUP BY po_id) paid ON po.id = paid.po_id
                WHERE iv.deleted_at IS NULL $cf $sc";
        $r = mysqli_query($this->conn, $sql);
        return $r ? (mysqli_fetch_assoc($r) ?: []) : [];
    }

    public function countInvoicePayments(int $comId, string $search, string $statusCond): int
    {
        $cf = $this->companyFilterPR($comId);
        $sc = $this->searchCondIP($search);

        $sql = "SELECT COUNT(*) AS total FROM (
                    SELECT iv.tex,
                           COALESCE(prod.total_amount, 0) AS total_amount,
                           COALESCE(paid.paid_amount, 0) AS paid_amount
                    FROM iv
                    JOIN po ON iv.tex = po.id
                    JOIN pr ON po.ref = pr.id
                    LEFT JOIN company ON pr.cus_id = company.id
                    LEFT JOIN (SELECT po_id, SUM(price * quantity) AS total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
                    LEFT JOIN (SELECT po_id, SUM(volumn) AS paid_amount FROM pay WHERE deleted_at IS NULL GROUP BY po_id) paid ON po.id = paid.po_id
                    WHERE iv.deleted_at IS NULL $cf $sc
                    GROUP BY iv.tex
                    $statusCond
                ) AS filtered";
        $r = mysqli_query($this->conn, $sql);
        return $r ? intval(mysqli_fetch_assoc($r)['total'] ?? 0) : 0;
    }

    public function getInvoicePayments(int $comId, string $search, string $statusCond, int $limit, int $offset): array
    {
        $cf = $this->companyFilterPR($comId);
        $sc = $this->searchCondIP($search);

        $sql = "SELECT iv.tex AS invoice_id, iv.createdate, po.name AS description, po.tax AS po_number,
                       company.name_en AS customer_name, company.name_th AS customer_name_th,
                       COALESCE(prod.total_amount, 0) AS total_amount,
                       COALESCE(paid.paid_amount, 0) AS paid_amount
                FROM iv
                JOIN po ON iv.tex = po.id
                JOIN pr ON po.ref = pr.id
                LEFT JOIN company ON pr.cus_id = company.id
                LEFT JOIN (SELECT po_id, SUM(price * quantity) AS total_amount FROM product GROUP BY po_id) prod ON po.id = prod.po_id
                LEFT JOIN (SELECT po_id, SUM(volumn) AS paid_amount FROM pay WHERE deleted_at IS NULL GROUP BY po_id) paid ON po.id = paid.po_id
                WHERE iv.deleted_at IS NULL $cf $sc
                GROUP BY iv.tex
                $statusCond
                ORDER BY iv.createdate DESC
                LIMIT $limit OFFSET $offset";
        $rows = [];
        $r = mysqli_query($this->conn, $sql);
        if ($r) { while ($row = mysqli_fetch_assoc($r)) $rows[] = $row; }
        return $rows;
    }

    /* ======== Business Summary Report ======== */

    public function getBusinessReport(int $comId, string $dateFilter, bool $isAdmin): array
    {
        $rows = [];
        $totals = ['prs'=>0,'qas'=>0,'pos'=>0,'ivs'=>0,'txs'=>0];

        if ($comId > 0) {
            $venFilter = "ven_id='$comId'";
            $companyExclude = "company.id != '$comId' AND";
        } else {
            $venFilter = '1=1';
            $companyExclude = '';
        }

        $q = mysqli_query($this->conn, "SELECT name_en, name_th, id FROM company WHERE $companyExclude customer='1'");
        while ($f = mysqli_fetch_array($q)) {
            $cusId = \sql_int($f['id']);
            if ($comId > 0) {
                $base = "FROM pr WHERE ven_id='$comId' AND cus_id='$cusId' $dateFilter";
            } else {
                $base = "FROM pr WHERE cus_id='$cusId' $dateFilter";
            }
            $pr = $this->val("SELECT COUNT(id) AS ct $base");
            if ($pr == 0) continue;

            $qa = $this->val("SELECT COUNT(id) AS ct $base AND status>='1'");
            $po = $this->val("SELECT COUNT(id) AS ct $base AND status>='2'");
            $iv = $this->val("SELECT COUNT(id) AS ct $base AND status>='4'");
            $tx = $this->val("SELECT COUNT(id) AS ct $base AND status>='5'");

            $rows[] = ['name'=>$f['name_en']?:$f['name_th'], 'pr'=>$pr, 'qa'=>$qa, 'po'=>$po, 'iv'=>$iv, 'tx'=>$tx];
            $totals['prs'] += $pr; $totals['qas'] += $qa; $totals['pos'] += $po;
            $totals['ivs'] += $iv; $totals['txs'] += $tx;
        }
        return ['rows'=>$rows, 'totals'=>$totals];
    }

    /* ---- helpers ---- */

    private function companyFilterPR(int $comId): string
    {
        return $comId > 0 ? " AND (pr.ven_id = '$comId' OR pr.cus_id = '$comId')" : '';
    }

    private function searchCondIP(string $search): string
    {
        if (empty($search)) return '';
        $s = \sql_escape($search);
        return " AND (po.name LIKE '%$s%' OR po.tax LIKE '%$s%' OR company.name_en LIKE '%$s%')";
    }

    private function val(string $sql): int
    {
        $r = mysqli_query($this->conn, $sql);
        return $r ? intval(mysqli_fetch_assoc($r)['ct'] ?? 0) : 0;
    }
}
