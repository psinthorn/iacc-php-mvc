<?php
namespace App\Models;

/**
 * Company Model
 * 
 * Manages the `company` table with company-based multi-tenancy.
 * A company can be a customer, vendor, or both. The logged-in company 
 * sees itself + companies it created (company_id = session com_id).
 * 
 * Related tables:
 *   - company_addr: Tax and billing addresses (versioned with valid_start/valid_end)
 *   - company_credit: Credit limits between companies
 */
class Company extends BaseModel
{
    protected string $table = 'company';
    protected bool $useCompanyFilter = false; // Custom filter logic below

    /**
     * Execute a query and return the first row as an associative array
     */
    private function fetchOne(string $sql): ?array
    {
        $result = mysqli_query($this->conn, $sql);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return $row;
        }
        return null;
    }

    /**
     * Get paginated companies with stats, search, and type filter
     */
    public function getPaginated(string $search = '', string $typeFilter = '', int $page = 1, int $perPage = 15): array
    {
        $comId = intval($_SESSION['com_id'] ?? 0);

        // Base WHERE
        $baseWhere = "c.deleted_at IS NULL";
        if ($comId > 0) {
            $baseWhere .= " AND (c.id = $comId OR c.company_id = $comId)";
        }

        // Search
        $searchCond = '';
        if (!empty($search)) {
            $escaped = \sql_escape($search);
            $searchCond = " AND (c.name_en LIKE '%$escaped%' OR c.name_th LIKE '%$escaped%' OR c.contact LIKE '%$escaped%' OR c.email LIKE '%$escaped%' OR c.phone LIKE '%$escaped%')";
        }

        // Type filter
        $typeCond = '';
        if ($typeFilter === 'vendor') {
            $typeCond = " AND c.vender = '1'";
        } elseif ($typeFilter === 'customer') {
            $typeCond = " AND c.customer = '1'";
        }

        // Stats (unfiltered by type/search)
        $statsRow = $this->fetchOne(
            "SELECT COUNT(*) as total,
                SUM(CASE WHEN c.vender = '1' THEN 1 ELSE 0 END) as vendors,
                SUM(CASE WHEN c.customer = '1' THEN 1 ELSE 0 END) as customers
             FROM company c WHERE $baseWhere"
        );
        $stats = [
            'total'     => intval($statsRow['total'] ?? 0),
            'vendors'   => intval($statsRow['vendors'] ?? 0),
            'customers' => intval($statsRow['customers'] ?? 0),
        ];

        // Count with filters
        $countRow = $this->fetchOne(
            "SELECT COUNT(*) as total FROM company c WHERE $baseWhere $searchCond $typeCond"
        );
        $total = intval($countRow['total'] ?? 0);

        // Pagination
        require_once __DIR__ . '/../../inc/pagination.php';
        $pagination = paginate($total, $perPage, $page);
        $offset = $pagination['offset'];

        // Order: own company first, then alphabetical
        $orderBy = $comId > 0
            ? "ORDER BY CASE WHEN c.id = $comId THEN 0 ELSE 1 END, c.name_en ASC"
            : "ORDER BY c.id DESC";

        $sql = "SELECT c.id, c.name_en, c.name_th, c.name_sh, c.contact, c.email, c.phone,
                       c.vender, c.customer, c.logo, c.tax, c.company_id,
                       CASE 
                           WHEN c.id = $comId THEN 'self'
                           WHEN c.vender = '1' AND c.customer = '1' THEN 'both'
                           WHEN c.vender = '1' THEN 'vendor'
                           WHEN c.customer = '1' THEN 'customer'
                           ELSE 'partner'
                       END as relationship
                FROM company c
                WHERE $baseWhere $searchCond $typeCond
                $orderBy
                LIMIT $offset, $perPage";

        $result = mysqli_query($this->conn, $sql);
        $items = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $items[] = $row;
            }
        }

        return [
            'items'      => $items,
            'total'      => $total,
            'count'      => count($items),
            'pagination' => $pagination,
            'stats'      => $stats,
        ];
    }

    /**
     * Find company with its current address merged in
     */
    public function findWithAddress(int $id): ?array
    {
        $data = $this->find($id);
        if (!$data) return null;

        // Get most recent active address
        $addr = $this->fetchOne(
            "SELECT * FROM company_addr 
             WHERE com_id = '" . \sql_int($id) . "' AND deleted_at IS NULL 
             ORDER BY (valid_end = '0000-00-00' OR valid_end = '9999-12-31') DESC, valid_start DESC 
             LIMIT 1"
        );

        if ($addr) {
            $data['adr_tax']      = $addr['adr_tax'];
            $data['city_tax']     = $addr['city_tax'];
            $data['district_tax'] = $addr['district_tax'];
            $data['province_tax'] = $addr['province_tax'];
            $data['zip_tax']      = $addr['zip_tax'];
            $data['adr_bil']      = $addr['adr_bil'];
            $data['city_bil']     = $addr['city_bil'];
            $data['district_bil'] = $addr['district_bil'];
            $data['province_bil'] = $addr['province_bil'];
            $data['zip_bil']      = $addr['zip_bil'];
            $data['addr_id']      = $addr['id'];
        }

        return $data;
    }

    /**
     * Create a new company with raw SQL (custom column order)
     */
    public function createCompany(array $fields): int
    {
        $comId = intval($_SESSION['com_id'] ?? 0);
        
        // Handle logo upload
        $logo = $this->handleLogoUpload($fields['name_en'] ?? '');

        $sql = "INSERT INTO company (name_en, name_th, name_sh, contact, email, phone, fax, tax, 
                    customer, vender, logo, term, company_id)
                VALUES (
                    '" . \sql_escape($fields['name_en'] ?? '') . "',
                    '" . \sql_escape($fields['name_th'] ?? '') . "',
                    '" . \sql_escape($fields['name_sh'] ?? '') . "',
                    '" . \sql_escape($fields['contact'] ?? '') . "',
                    '" . \sql_escape($fields['email'] ?? '') . "',
                    '" . \sql_escape($fields['phone'] ?? '') . "',
                    '" . \sql_escape($fields['fax'] ?? '') . "',
                    '" . \sql_escape($fields['tax'] ?? '') . "',
                    '" . intval($fields['customer'] ?? 0) . "',
                    '" . intval($fields['vender'] ?? 0) . "',
                    '" . \sql_escape($logo) . "',
                    '" . \sql_escape($fields['term'] ?? '') . "',
                    '$comId'
                )";
        mysqli_query($this->conn, $sql);
        $newId = intval(mysqli_insert_id($this->conn));

        // Auto-generate logo if none was uploaded
        if ($newId > 0 && $logo === '') {
            $generator = new \App\Services\LogoGenerator();
            $logo = $generator->generateForCompany(
                $newId,
                $fields['name_sh'] ?? '',
                $fields['name_en'] ?? ''
            );
            mysqli_query($this->conn, "UPDATE company SET logo='" . \sql_escape($logo) . "' WHERE id='" . \sql_int($newId) . "'");
        }

        return $newId;
    }

    /**
     * Update company
     */
    public function updateCompany(int $id, array $fields): bool
    {
        // Handle logo upload (only update if new file uploaded)
        $logoSql = '';
        $logo = $this->handleLogoUpload($fields['name_en'] ?? '');
        if ($logo) {
            $logoSql = ", logo='" . \sql_escape($logo) . "'";
        } else {
            // Auto-generate if no existing logo file
            $existing = mysqli_fetch_assoc(mysqli_query($this->conn, "SELECT logo FROM company WHERE id='" . \sql_int($id) . "'"));
            $existingLogo = trim($existing['logo'] ?? '');
            $uploadDir = dirname(__DIR__, 2) . '/upload';
            if ($existingLogo === '' || !file_exists($uploadDir . '/' . $existingLogo)) {
                $generator = new \App\Services\LogoGenerator();
                $logo = $generator->generateForCompany($id, $fields['name_sh'] ?? '', $fields['name_en'] ?? '');
                $logoSql = ", logo='" . \sql_escape($logo) . "'";
            }
        }

        $sql = "UPDATE company SET 
                    name_en='" . \sql_escape($fields['name_en'] ?? '') . "',
                    name_th='" . \sql_escape($fields['name_th'] ?? '') . "',
                    name_sh='" . \sql_escape($fields['name_sh'] ?? '') . "',
                    contact='" . \sql_escape($fields['contact'] ?? '') . "',
                    email='" . \sql_escape($fields['email'] ?? '') . "',
                    phone='" . \sql_escape($fields['phone'] ?? '') . "',
                    fax='" . \sql_escape($fields['fax'] ?? '') . "',
                    tax='" . \sql_escape($fields['tax'] ?? '') . "',
                    customer='" . intval($fields['customer'] ?? 0) . "',
                    vender='" . intval($fields['vender'] ?? 0) . "'
                    $logoSql,
                    term='" . \sql_escape($fields['term'] ?? '') . "'
                WHERE id='" . \sql_int($id) . "'";
        return (bool) mysqli_query($this->conn, $sql);
    }

    /**
     * Soft delete company and its addresses
     */
    public function softDeleteCompany(int $id): bool
    {
        $idSafe = \sql_int($id);
        mysqli_query($this->conn, "UPDATE company SET deleted_at = NOW() WHERE id = '$idSafe'");
        mysqli_query($this->conn, "UPDATE company_addr SET deleted_at = NOW() WHERE com_id = '$idSafe' AND deleted_at IS NULL");
        return true;
    }

    /**
     * Save address (insert or update)
     */
    public function saveAddress(int $companyId, array $fields, int $addrId = 0): bool
    {
        $adrTax = trim($fields['adr_tax'] ?? '');
        if ($adrTax === '') return false;

        // Fill billing from tax if empty
        foreach (['adr', 'city', 'district', 'province', 'zip'] as $prefix) {
            if (empty($fields[$prefix . '_bil'])) {
                $fields[$prefix . '_bil'] = $fields[$prefix . '_tax'] ?? '';
            }
        }

        if ($addrId > 0) {
            // Update existing
            $sql = "UPDATE company_addr SET 
                        adr_tax='" . \sql_escape($fields['adr_tax']) . "',
                        city_tax='" . \sql_escape($fields['city_tax'] ?? '') . "',
                        district_tax='" . \sql_escape($fields['district_tax'] ?? '') . "',
                        province_tax='" . \sql_escape($fields['province_tax'] ?? '') . "',
                        zip_tax='" . \sql_escape($fields['zip_tax'] ?? '') . "',
                        adr_bil='" . \sql_escape($fields['adr_bil']) . "',
                        city_bil='" . \sql_escape($fields['city_bil'] ?? '') . "',
                        district_bil='" . \sql_escape($fields['district_bil'] ?? '') . "',
                        province_bil='" . \sql_escape($fields['province_bil'] ?? '') . "',
                        zip_bil='" . \sql_escape($fields['zip_bil'] ?? '') . "'
                    WHERE id='" . \sql_int($addrId) . "'";
        } else {
            // Insert new
            $sql = "INSERT INTO company_addr VALUES(
                        NULL,
                        '" . \sql_int($companyId) . "',
                        '" . \sql_escape($fields['adr_tax']) . "',
                        '" . \sql_escape($fields['city_tax'] ?? '') . "',
                        '" . \sql_escape($fields['district_tax'] ?? '') . "',
                        '" . \sql_escape($fields['province_tax'] ?? '') . "',
                        '" . \sql_escape($fields['zip_tax'] ?? '') . "',
                        '" . \sql_escape($fields['adr_bil']) . "',
                        '" . \sql_escape($fields['city_bil'] ?? '') . "',
                        '" . \sql_escape($fields['district_bil'] ?? '') . "',
                        '" . \sql_escape($fields['province_bil'] ?? '') . "',
                        '" . \sql_escape($fields['zip_bil'] ?? '') . "',
                        '" . date('Y-m-d') . "',
                        '9999-12-31',
                        NULL
                    )";
        }
        return (bool) mysqli_query($this->conn, $sql);
    }

    /**
     * Create new address version (close old, insert new)
     */
    public function addAddressVersion(int $companyId, array $fields): bool
    {
        // Close current address (match both legacy 0000-00-00 and new 9999-12-31)
        // Use range comparisons to avoid NO_ZERO_DATE strict mode issues
        mysqli_query($this->conn, 
            "UPDATE company_addr SET valid_end='" . date('Y-m-d') . "' 
             WHERE com_id='" . \sql_int($companyId) . "' AND (valid_end < '0001-01-01' OR valid_end > '9000-01-01')");
        
        return $this->saveAddress($companyId, $fields, 0);
    }

    /**
     * Get credit records for a company
     */
    public function getCreditRecords(int $companyId): array
    {
        $idSafe = \sql_int($companyId);
        
        // Vendor credits: credits given BY vendors TO this company (this company is the customer)
        $vendorCredits = [];
        $r1 = mysqli_query($this->conn,
            "SELECT cc.id, cc.limit_credit, cc.limit_day, cc.valid_start, cc.valid_end, 
                    c.name_sh, c.name_en
             FROM company_credit cc JOIN company c ON cc.ven_id = c.id 
             WHERE cc.cus_id = '$idSafe' AND (cc.valid_end = '0000-00-00' OR cc.valid_end = '9999-12-31')");
        if ($r1) { while ($row = mysqli_fetch_assoc($r1)) $vendorCredits[] = $row; }

        // Customer credits: credits given BY this company TO customers (this company is the vendor)
        $customerCredits = [];
        $r2 = mysqli_query($this->conn,
            "SELECT cc.id, cc.limit_credit, cc.limit_day, cc.valid_start, cc.valid_end,
                    c.name_sh, c.name_en
             FROM company_credit cc JOIN company c ON cc.cus_id = c.id 
             WHERE cc.ven_id = '$idSafe' AND (cc.valid_end = '0000-00-00' OR cc.valid_end = '9999-12-31')");
        if ($r2) { while ($row = mysqli_fetch_assoc($r2)) $customerCredits[] = $row; }

        return [
            'vendor_credits'   => $vendorCredits,
            'customer_credits' => $customerCredits,
        ];
    }

    /**
     * Save credit record (create or update/version)
     */
    public function saveCredit(array $fields): bool
    {
        $existingId = intval($fields['id'] ?? 0);
        
        if ($existingId > 0) {
            // Close existing credit
            mysqli_query($this->conn,
                "UPDATE company_credit SET valid_end='" . date('Y-m-d') . "' 
                 WHERE id='" . \sql_int($existingId) . "'");
        }

        $sql = "INSERT INTO company_credit (cus_id, ven_id, limit_credit, limit_day, valid_start, valid_end)
                VALUES (
                    '" . \sql_int($fields['cus_id']) . "',
                    '" . \sql_int($fields['ven_id']) . "',
                    '" . \sql_escape($fields['limit_credit']) . "',
                    '" . \sql_escape($fields['limit_day']) . "',
                    '" . date('Y-m-d') . "',
                    '9999-12-31'
                )";
        return (bool) mysqli_query($this->conn, $sql);
    }

    /**
     * Get customers available for credit assignment (not already assigned)
     */
    public function getAvailableCustomersForCredit(int $vendorId): array
    {
        $vIdSafe = \sql_int($vendorId);
        $sql = "SELECT id, name_en FROM company 
                WHERE id NOT IN (SELECT cus_id FROM company_credit WHERE ven_id='$vIdSafe' AND (valid_end='0000-00-00' OR valid_end='9999-12-31'))
                AND id != '$vIdSafe' AND customer='1' AND deleted_at IS NULL
                ORDER BY name_en";
        $result = mysqli_query($this->conn, $sql);
        $items = [];
        if ($result) { while ($row = mysqli_fetch_assoc($result)) $items[] = $row; }
        return $items;
    }

    /**
     * Handle logo upload (JPG/PNG)
     * @return string Filename if uploaded, empty string otherwise
     */
    public function handleLogoUpload(string $nameHint = ''): string
    {
        if (!isset($_FILES['logo']) || $_FILES['logo']['error'] !== 0 || empty($_FILES['logo']['tmp_name'])) {
            return '';
        }
        $allowed = ['image/jpg', 'image/jpeg', 'image/JPG', 'image/pjpeg', 'image/png', 'image/PNG'];
        if (!in_array($_FILES['logo']['type'], $allowed)) {
            return '';
        }
        $ext = (strpos($_FILES['logo']['type'], 'png') !== false || strpos($_FILES['logo']['type'], 'PNG') !== false) ? '.png' : '.jpg';
        $filename = 'logo' . md5(rand() . $nameHint) . $ext;
        move_uploaded_file($_FILES['logo']['tmp_name'], __DIR__ . '/../../upload/' . $filename);
        return $filename;
    }
}
