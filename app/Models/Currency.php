<?php
namespace App\Models;

/**
 * Currency Model — Currency Master Data & Exchange Rates
 * 
 * Manages currency definitions and exchange rate records in the database.
 * Works with CurrencyService for rate fetching and conversion.
 * 
 * @package App\Models
 * @version 1.0.0 — Q2 2026
 */
class Currency extends BaseModel
{
    protected string $table = 'currencies';

    /**
     * Get all active currencies
     */
    public function getActiveCurrencies(): array
    {
        $sql = "SELECT * FROM currencies WHERE is_active = 1 ORDER BY sort_order, code";
        $result = mysqli_query($this->conn, $sql);
        $currencies = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $currencies[] = $row;
            }
        }
        return $currencies;
    }

    /**
     * Get currency by code
     */
    public function getByCode(string $code): ?array
    {
        $code = sql_escape($code);
        $sql = "SELECT * FROM currencies WHERE code = '{$code}' LIMIT 1";
        $result = mysqli_query($this->conn, $sql);
        return $result ? (mysqli_fetch_assoc($result) ?: null) : null;
    }

    /**
     * Get exchange rates for a date
     */
    public function getRatesByDate(string $date): array
    {
        $date = sql_escape($date);
        $sql = "SELECT * FROM exchange_rates WHERE rate_date = '{$date}' ORDER BY from_currency";
        $result = mysqli_query($this->conn, $sql);
        $rates = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rates[] = $row;
            }
        }
        return $rates;
    }

    /**
     * Get company default currency
     */
    public function getCompanyDefaultCurrency(int $companyId): string
    {
        $sql = "SELECT default_currency FROM company WHERE com_id = {$companyId} LIMIT 1";
        $result = mysqli_query($this->conn, $sql);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return $row['default_currency'] ?: 'THB';
        }
        return 'THB';
    }

    /**
     * Toggle currency active status
     */
    public function toggleActive(int $id): bool
    {
        $sql = "UPDATE currencies SET is_active = IF(is_active = 1, 0, 1), updated_at = NOW() WHERE id = {$id}";
        return mysqli_query($this->conn, $sql) && mysqli_affected_rows($this->conn) > 0;
    }
}
