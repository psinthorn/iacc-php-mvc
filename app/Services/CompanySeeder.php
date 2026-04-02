<?php
namespace App\Services;

/**
 * CompanySeeder — Seeds default master data for new companies
 * 
 * Provides standard expense categories, payment methods, and chart of accounts
 * so new companies start with useful example data instead of empty screens.
 * 
 * Called automatically during:
 *   - Self-registration (Registration::createAccount)
 *   - Admin company creation (Company::createCompany)
 * 
 * @package App\Services
 */
class CompanySeeder
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Seed all default master data for a new company
     * 
     * @param int $companyId The newly created company ID
     * @return array Summary of seeded records
     */
    public function seedAll(int $companyId): array
    {
        $summary = [];
        $summary['expense_categories'] = $this->seedExpenseCategories($companyId);
        $summary['payment_methods'] = $this->seedPaymentMethods($companyId);
        $summary['chart_of_accounts'] = $this->seedChartOfAccounts($companyId);
        return $summary;
    }

    /**
     * Seed default expense categories
     */
    public function seedExpenseCategories(int $companyId): int
    {
        $categories = [
            ['Office Rent',              'ค่าเช่าสำนักงาน',          'EXP-RENT',  'fa-building',   '#6366f1', 1],
            ['Utilities',                'ค่าสาธารณูปโภค',           'EXP-UTIL',  'fa-bolt',       '#f59e0b', 2],
            ['Office Supplies',          'วัสดุสำนักงาน',            'EXP-SUPP',  'fa-paperclip',  '#10b981', 3],
            ['Travel & Transport',       'ค่าเดินทาง',              'EXP-TRAV',  'fa-car',        '#3b82f6', 4],
            ['Salary & Wages',           'เงินเดือนและค่าจ้าง',      'EXP-SAL',   'fa-users',      '#ef4444', 5],
            ['Marketing & Advertising',  'การตลาดและโฆษณา',          'EXP-MKT',   'fa-bullhorn',   '#f97316', 6],
            ['Professional Fees',        'ค่าบริการวิชาชีพ',         'EXP-PROF',  'fa-briefcase',  '#8b5cf6', 7],
            ['Equipment & Maintenance',  'อุปกรณ์และซ่อมบำรุง',      'EXP-EQUIP', 'fa-wrench',     '#64748b', 8],
            ['Insurance',                'ค่าประกันภัย',             'EXP-INS',   'fa-shield',     '#06b6d4', 9],
            ['Miscellaneous',            'อื่นๆ',                   'EXP-MISC',  'fa-folder',     '#a855f7', 10],
        ];

        $stmt = $this->conn->prepare(
            "INSERT INTO expense_categories (com_id, name, name_th, code, icon, color, is_active, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, 1, ?)"
        );

        $count = 0;
        foreach ($categories as $cat) {
            $stmt->bind_param('isssssi', $companyId, $cat[0], $cat[1], $cat[2], $cat[3], $cat[4], $cat[5]);
            if ($stmt->execute()) {
                $count++;
            }
        }
        $stmt->close();

        return $count;
    }

    /**
     * Seed default payment methods
     */
    public function seedPaymentMethods(int $companyId): int
    {
        $methods = [
            ['CASH',     'Cash',          'เงินสด',                'fa-money',         0, 1],
            ['BANK',     'Bank Transfer', 'โอนเงินผ่านธนาคาร',     'fa-university',    0, 2],
            ['CREDIT',   'Credit Card',   'บัตรเครดิต',            'fa-credit-card',   1, 3],
            ['CHEQUE',   'Cheque',        'เช็ค',                  'fa-file-text-o',   0, 4],
        ];

        $stmt = $this->conn->prepare(
            "INSERT INTO payment_method (company_id, code, name, name_th, icon, is_gateway, is_active, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, 1, ?)"
        );

        $count = 0;
        foreach ($methods as $m) {
            // Make code unique per company: CODE-{companyId}
            $code = $m[0] . '-' . $companyId;
            $stmt->bind_param('issssii', $companyId, $code, $m[1], $m[2], $m[3], $m[4], $m[5]);
            if ($stmt->execute()) {
                $count++;
            }
        }
        $stmt->close();

        return $count;
    }

    /**
     * Seed default chart of accounts (basic Thai accounting structure)
     */
    public function seedChartOfAccounts(int $companyId): int
    {
        // [account_code, account_name, account_name_th, account_type, normal_balance, level]
        $accounts = [
            // Assets
            ['1000', 'Cash',                'เงินสด',                'asset',     'debit',  1],
            ['1100', 'Bank Account',        'เงินฝากธนาคาร',         'asset',     'debit',  1],
            ['1200', 'Accounts Receivable',  'ลูกหนี้การค้า',          'asset',     'debit',  1],
            ['1300', 'Inventory',            'สินค้าคงเหลือ',          'asset',     'debit',  1],
            ['1400', 'Prepaid Expenses',     'ค่าใช้จ่ายจ่ายล่วงหน้า',   'asset',     'debit',  1],
            // Liabilities
            ['2000', 'Accounts Payable',     'เจ้าหนี้การค้า',         'liability', 'credit', 1],
            ['2100', 'Accrued Expenses',     'ค่าใช้จ่ายค้างจ่าย',      'liability', 'credit', 1],
            ['2200', 'VAT Payable',          'ภาษีมูลค่าเพิ่มค้างจ่าย',  'liability', 'credit', 1],
            ['2300', 'Withholding Tax Payable', 'ภาษีหัก ณ ที่จ่ายค้างจ่าย', 'liability', 'credit', 1],
            // Equity
            ['3000', 'Owner\'s Equity',      'ทุนเจ้าของ',            'equity',    'credit', 1],
            ['3100', 'Retained Earnings',    'กำไรสะสม',             'equity',    'credit', 1],
            // Revenue
            ['4000', 'Sales Revenue',        'รายได้จากการขาย',       'revenue',   'credit', 1],
            ['4100', 'Service Revenue',      'รายได้จากการบริการ',     'revenue',   'credit', 1],
            ['4200', 'Other Income',         'รายได้อื่น',            'revenue',   'credit', 1],
            // Expenses
            ['5000', 'Cost of Goods Sold',   'ต้นทุนสินค้าขาย',       'expense',   'debit',  1],
            ['5100', 'Operating Expenses',   'ค่าใช้จ่ายในการดำเนินงาน', 'expense',   'debit',  1],
            ['5200', 'Salary Expense',       'เงินเดือนและค่าจ้าง',    'expense',   'debit',  1],
            ['5300', 'Rent Expense',         'ค่าเช่า',              'expense',   'debit',  1],
            ['5400', 'Utilities Expense',    'ค่าสาธารณูปโภค',        'expense',   'debit',  1],
            ['5500', 'Depreciation Expense', 'ค่าเสื่อมราคา',         'expense',   'debit',  1],
        ];

        $stmt = $this->conn->prepare(
            "INSERT INTO chart_of_accounts (com_id, account_code, account_name, account_name_th, account_type, normal_balance, level, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
        );

        $count = 0;
        foreach ($accounts as $a) {
            $stmt->bind_param('isssssi', $companyId, $a[0], $a[1], $a[2], $a[3], $a[4], $a[5]);
            if ($stmt->execute()) {
                $count++;
            }
        }
        $stmt->close();

        return $count;
    }
}
