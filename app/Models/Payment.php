<?php
namespace App\Models;

/**
 * Payment Model - Handles payment records (company payment methods)
 * Replaces: payment-list.php, core-function.php case payment
 */
class Payment extends BaseModel
{
    protected string $table = 'payment';
    protected bool $useCompanyFilter = false;

    public function getPayments(int $comId, string $search = ''): array
    {
        $cond = '';
        if (!empty($search)) {
            $s = \sql_escape($search);
            $cond = " AND (payment_name LIKE '%$s%' OR payment_des LIKE '%$s%')";
        }
        return $this->fetchAll("SELECT * FROM payment WHERE com_id='$comId' AND deleted_at IS NULL $cond ORDER BY id DESC");
    }

    public function countPayments(int $comId): int
    {
        $r = mysqli_query($this->conn, "SELECT COUNT(*) as cnt FROM payment WHERE com_id='$comId' AND deleted_at IS NULL");
        return $r ? intval(mysqli_fetch_assoc($r)['cnt']) : 0;
    }

    public function findPayment(int $id, int $comId): ?array
    {
        $r = mysqli_query($this->conn, "SELECT * FROM payment WHERE id='" . \sql_int($id) . "' AND com_id='$comId'");
        return ($r && mysqli_num_rows($r) > 0) ? mysqli_fetch_assoc($r) : null;
    }

    public function createPayment(int $comId, string $name, string $des): void
    {
        $args = ['table' => 'payment'];
        $args['value'] = "NULL,'" . \sql_escape($name) . "','" . \sql_escape($des) . "','$comId',NULL";
        $this->hard->insertDB($args);
    }

    public function updatePayment(int $id, string $name, string $des): void
    {
        $args = ['table' => 'payment',
            'value' => "payment_name='" . \sql_escape($name) . "', payment_des='" . \sql_escape($des) . "'",
            'condition' => "id='" . \sql_int($id) . "'"];
        $this->hard->updateDb($args);
    }

    private function fetchAll(string $sql): array
    {
        $result = mysqli_query($this->conn, $sql);
        $rows = [];
        if ($result) { while ($row = mysqli_fetch_assoc($result)) $rows[] = $row; }
        return $rows;
    }
}
