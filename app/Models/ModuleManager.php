<?php
namespace App\Models;

class ModuleManager extends BaseModel
{
    protected string $table = 'company_modules';
    protected bool $useCompanyFilter = false; // Super admin sees all companies

    /** Known modules with metadata */
    public const MODULES = [
        'tour_operator' => ['name' => 'Tour Operator', 'icon' => 'fa-map', 'description' => 'Bookings, agents, locations, reports'],
        'sales_channel' => ['name' => 'Sales Channel API', 'icon' => 'fa-plug', 'description' => 'REST API, webhooks, order sync'],
        'line_oa'       => ['name' => 'LINE OA', 'icon' => 'fa-comment', 'description' => 'LINE messaging, auto-replies, orders'],
    ];

    /** Valid plan types */
    public const PLANS = ['trial', 'basic', 'pro', 'enterprise'];

    /**
     * Get all companies with their module status
     */
    public function getCompaniesWithModules(string $search = ''): array
    {
        $searchCond = '';
        if (!empty($search)) {
            $escaped = \sql_escape($search);
            $searchCond = "AND (c.name_en LIKE '%{$escaped}%' OR c.name_th LIKE '%{$escaped}%' OR c.email LIKE '%{$escaped}%')";
        }

        $sql = "SELECT c.id, c.name_en, c.name_th, c.email, c.phone, c.created_at
                FROM company c
                WHERE c.deleted_at IS NULL 
                AND c.company_id = 0
                {$searchCond}
                ORDER BY c.id DESC";

        $result = mysqli_query($this->conn, $sql);
        $companies = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $row['modules'] = $this->getModulesForCompany(intval($row['id']));
                $companies[] = $row;
            }
        }
        return $companies;
    }

    /**
     * Get all modules for a specific company
     */
    public function getModulesForCompany(int $companyId): array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM company_modules WHERE company_id = ? ORDER BY module_key"
        );
        $stmt->bind_param('i', $companyId);
        $stmt->execute();
        $result = $stmt->get_result();

        $modules = [];
        while ($row = $result->fetch_assoc()) {
            $modules[$row['module_key']] = $row;
        }
        $stmt->close();
        return $modules;
    }

    /**
     * Toggle module enabled/disabled (UPSERT)
     */
    public function toggleModule(int $companyId, string $moduleKey): array
    {
        if (!isset(self::MODULES[$moduleKey])) {
            return ['success' => false, 'error' => 'Invalid module key'];
        }

        $existing = $this->getModuleRow($companyId, $moduleKey);

        if ($existing) {
            $newStatus = $existing['is_enabled'] ? 0 : 1;
            $stmt = $this->conn->prepare(
                "UPDATE company_modules SET is_enabled = ?, updated_at = NOW() WHERE id = ?"
            );
            $stmt->bind_param('ii', $newStatus, $existing['id']);
            $stmt->execute();
            $stmt->close();
        } else {
            // Insert new — enable with trial plan
            $newStatus = 1;
            $stmt = $this->conn->prepare(
                "INSERT INTO company_modules (company_id, module_key, is_enabled, plan, usage_count, created_at, updated_at)
                 VALUES (?, ?, 1, 'trial', 0, NOW(), NOW())"
            );
            $stmt->bind_param('is', $companyId, $moduleKey);
            $stmt->execute();
            $stmt->close();
        }

        clearModuleCache($companyId);

        return [
            'success' => true,
            'is_enabled' => $newStatus,
            'module_key' => $moduleKey,
            'company_id' => $companyId,
        ];
    }

    /**
     * Update module plan settings
     */
    public function updateModule(int $companyId, string $moduleKey, array $data): array
    {
        if (!isset(self::MODULES[$moduleKey])) {
            return ['success' => false, 'error' => 'Invalid module key'];
        }

        $existing = $this->getModuleRow($companyId, $moduleKey);
        if (!$existing) {
            return ['success' => false, 'error' => 'Module not found for this company'];
        }

        $plan = in_array($data['plan'] ?? '', self::PLANS) ? $data['plan'] : $existing['plan'];
        $usageLimit = isset($data['usage_limit']) && $data['usage_limit'] !== '' ? intval($data['usage_limit']) : null;
        $validFrom = !empty($data['valid_from']) ? $data['valid_from'] : null;
        $validTo = !empty($data['valid_to']) ? $data['valid_to'] : null;

        $stmt = $this->conn->prepare(
            "UPDATE company_modules 
             SET plan = ?, usage_limit = ?, valid_from = ?, valid_to = ?, updated_at = NOW()
             WHERE id = ?"
        );
        $stmt->bind_param('sissi', $plan, $usageLimit, $validFrom, $validTo, $existing['id']);
        $stmt->execute();
        $stmt->close();

        clearModuleCache($companyId);

        return ['success' => true];
    }

    /**
     * Get summary stats for the dashboard header
     */
    public function getStats(): array
    {
        $sql = "SELECT 
                    COUNT(DISTINCT company_id) as total_companies,
                    SUM(is_enabled = 1) as active_modules,
                    SUM(plan = 'trial') as trial_count,
                    SUM(valid_to IS NOT NULL AND valid_to < CURDATE() AND is_enabled = 1) as expired_count
                FROM company_modules";
        $result = mysqli_query($this->conn, $sql);
        return $result ? mysqli_fetch_assoc($result) : ['total_companies' => 0, 'active_modules' => 0, 'trial_count' => 0, 'expired_count' => 0];
    }

    /**
     * Get a single module row
     */
    private function getModuleRow(int $companyId, string $moduleKey): ?array
    {
        $stmt = $this->conn->prepare(
            "SELECT * FROM company_modules WHERE company_id = ? AND module_key = ? LIMIT 1"
        );
        $stmt->bind_param('is', $companyId, $moduleKey);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }
}
