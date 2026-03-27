<?php
namespace App\Models;

/**
 * UserAccount Model - Handles user profile & settings
 * Replaces: profile.php, settings.php (data layer)
 */
class UserAccount extends BaseModel
{
    protected string $table = 'authorize';
    protected bool $useCompanyFilter = false;

    /* ---------- Read ---------- */

    public function findUser(int $id): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT a.*, c.name_en AS company_name
             FROM authorize a
             LEFT JOIN company c ON a.company_id = c.id
             WHERE a.id = ?"
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /* ---------- Profile ---------- */

    public function updateProfile(int $id, string $name, string $phone): bool
    {
        $stmt = $this->conn->prepare("UPDATE authorize SET name = ?, phone = ? WHERE id = ?");
        $stmt->bind_param('ssi', $name, $phone, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    public function changePassword(int $id, string $currentPassword, string $newPassword, string $currentHash): array
    {
        if (!password_verify_secure($currentPassword, $currentHash)) {
            return ['ok' => false, 'error' => 'Current password is incorrect.'];
        }
        $hash = password_hash_secure($newPassword);
        $stmt = $this->conn->prepare("UPDATE authorize SET password = ?, password_migrated = 1 WHERE id = ?");
        $stmt->bind_param('si', $hash, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return ['ok' => $ok, 'error' => $ok ? '' : 'Failed to change password.'];
    }

    /* ---------- Settings ---------- */

    public function updateLanguage(int $id, int $lang): bool
    {
        $stmt = $this->conn->prepare("UPDATE authorize SET lang = ? WHERE id = ?");
        $stmt->bind_param('ii', $lang, $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }
}
