<?php
namespace App\Models;

/**
 * User Model - User management (Super Admin)
 * Replaces: user-list.php (data layer)
 */
class User extends BaseModel
{
    protected string $table = 'authorize';
    protected bool $useCompanyFilter = false;

    /* ---------- List ---------- */

    public function getUsers(string $search = '', string $roleFilter = '', string $companyFilter = ''): array
    {
        $cond = '';
        if (!empty($search)) {
            $s = \sql_escape($search);
            $cond .= " AND (a.email LIKE '%$s%' OR c.name_en LIKE '%$s%')";
        }
        if ($roleFilter !== '') $cond .= " AND a.level = " . intval($roleFilter);
        if ($companyFilter !== '') $cond .= " AND a.company_id = " . intval($companyFilter);

        $sql = "SELECT a.id, a.email, a.name, a.level, a.company_id, a.lang, a.password_migrated,
                       a.locked_until, a.failed_attempts, c.name_en AS company_name
                FROM authorize a
                LEFT JOIN company c ON a.company_id = c.id
                WHERE 1=1 $cond
                ORDER BY a.level DESC, a.id ASC";
        $rows = [];
        $r = $this->conn->query($sql);
        while ($row = $r->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    public function getCompanies(): array
    {
        $rows = [];
        $r = $this->conn->query("SELECT id, name_en FROM company ORDER BY name_en ASC");
        while ($row = $r->fetch_assoc()) $rows[] = $row;
        return $rows;
    }

    /* ---------- Create ---------- */

    public function emailExists(string $email): bool
    {
        $stmt = $this->conn->prepare("SELECT id FROM authorize WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    public function createUser(string $email, string $password, int $level, ?int $companyId): bool
    {
        $hash = password_hash_secure($password);
        $finalCompanyId = ($level >= 1) ? null : $companyId;
        $stmt = $this->conn->prepare("INSERT INTO authorize (email, password, level, company_id, lang, password_migrated) VALUES (?, ?, ?, ?, 0, 1)");
        $stmt->bind_param('ssii', $email, $hash, $level, $finalCompanyId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /* ---------- Update ---------- */

    public function updateLevel(int $userId, int $level): bool
    {
        if ($level >= 1) {
            $stmt = $this->conn->prepare("UPDATE authorize SET level = ?, company_id = NULL WHERE id = ?");
        } else {
            $stmt = $this->conn->prepare("UPDATE authorize SET level = ? WHERE id = ?");
        }
        $stmt->bind_param('ii', $level, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function updateCompany(int $userId, ?int $companyId): bool
    {
        $stmt = $this->conn->prepare("UPDATE authorize SET company_id = ? WHERE id = ?");
        $stmt->bind_param('ii', $companyId, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function resetPassword(int $userId, string $newPassword): bool
    {
        $hash = password_hash_secure($newPassword);
        $stmt = $this->conn->prepare("UPDATE authorize SET password = ?, password_migrated = 1 WHERE id = ?");
        $stmt->bind_param('si', $hash, $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function unlockUser(int $userId): bool
    {
        $stmt = $this->conn->prepare("UPDATE authorize SET locked_until = NULL, failed_attempts = 0 WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function deleteUser(int $userId): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM authorize WHERE id = ?");
        $stmt->bind_param('i', $userId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
