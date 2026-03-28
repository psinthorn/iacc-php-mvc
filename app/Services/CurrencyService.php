<?php
namespace App\Services;

/**
 * CurrencyService — Multi-Currency Support
 * 
 * Handles currency conversion, exchange rates, and formatting.
 * Exchange rates sourced from Bank of Thailand (BOT) API.
 * 
 * Features:
 *   - Currency master data (code, name, symbol, decimal places)
 *   - Exchange rate caching (daily refresh)
 *   - Amount conversion between currencies
 *   - Locale-aware formatting
 *   - Per-document currency assignment
 * 
 * BOT API: https://apigw1.bot.or.th/bot/public/Stat-ExchangeRate/v2/DAILY_AVG_EXG_RATE/
 * 
 * @package App\Services
 * @version 1.0.0 — Q2 2026
 */
class CurrencyService
{
    /** Default base currency */
    private const BASE_CURRENCY = 'THB';
    
    /** BOT API endpoint */
    private const BOT_API_URL = 'https://apigw1.bot.or.th/bot/public/Stat-ExchangeRate/v2/DAILY_AVG_EXG_RATE/';
    
    /** Cache duration in seconds (24 hours) */
    private const CACHE_TTL = 86400;

    private \mysqli $conn;

    /** Built-in currency definitions */
    private static array $currencies = [
        'THB' => ['name' => 'Thai Baht',        'name_th' => 'บาท',        'symbol' => '฿', 'decimals' => 2, 'position' => 'before'],
        'USD' => ['name' => 'US Dollar',         'name_th' => 'ดอลลาร์สหรัฐ', 'symbol' => '$', 'decimals' => 2, 'position' => 'before'],
        'EUR' => ['name' => 'Euro',              'name_th' => 'ยูโร',       'symbol' => '€', 'decimals' => 2, 'position' => 'before'],
        'GBP' => ['name' => 'British Pound',     'name_th' => 'ปอนด์',      'symbol' => '£', 'decimals' => 2, 'position' => 'before'],
        'JPY' => ['name' => 'Japanese Yen',      'name_th' => 'เยน',        'symbol' => '¥', 'decimals' => 0, 'position' => 'before'],
        'CNY' => ['name' => 'Chinese Yuan',      'name_th' => 'หยวน',       'symbol' => '¥', 'decimals' => 2, 'position' => 'before'],
        'SGD' => ['name' => 'Singapore Dollar',  'name_th' => 'ดอลลาร์สิงคโปร์', 'symbol' => 'S$', 'decimals' => 2, 'position' => 'before'],
        'MYR' => ['name' => 'Malaysian Ringgit', 'name_th' => 'ริงกิต',     'symbol' => 'RM', 'decimals' => 2, 'position' => 'before'],
        'KRW' => ['name' => 'South Korean Won',  'name_th' => 'วอน',        'symbol' => '₩', 'decimals' => 0, 'position' => 'before'],
        'AUD' => ['name' => 'Australian Dollar', 'name_th' => 'ดอลลาร์ออสเตรเลีย', 'symbol' => 'A$', 'decimals' => 2, 'position' => 'before'],
    ];

    public function __construct(\mysqli $conn = null)
    {
        if ($conn) {
            $this->conn = $conn;
        } else {
            global $db;
            $this->conn = $db->conn;
        }
    }

    /**
     * Get all supported currencies
     * 
     * @return array Currency definitions
     */
    public function getSupportedCurrencies(): array
    {
        // Merge built-in with any custom currencies from DB
        $sql = "SELECT * FROM currencies WHERE is_active = 1 ORDER BY sort_order, code";
        $result = mysqli_query($this->conn, $sql);
        
        $dbCurrencies = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $dbCurrencies[$row['code']] = [
                    'name'     => $row['name'],
                    'name_th'  => $row['name_th'],
                    'symbol'   => $row['symbol'],
                    'decimals' => intval($row['decimal_places']),
                    'position' => $row['symbol_position'],
                ];
            }
        }
        
        return array_merge(self::$currencies, $dbCurrencies);
    }

    /**
     * Get exchange rate from one currency to another
     * Uses cached rates from DB, falls back to BOT API
     * 
     * @param string $from Source currency code
     * @param string $to   Target currency code
     * @param string $date Date (Y-m-d), defaults to today
     * @return float Exchange rate
     */
    public function getExchangeRate(string $from, string $to, string $date = ''): float
    {
        if ($from === $to) return 1.0;
        
        $date = $date ?: date('Y-m-d');
        
        // Check cache first
        $cached = $this->getCachedRate($from, $to, $date);
        if ($cached !== null) {
            return $cached;
        }
        
        // Calculate via THB base rate
        $fromToThb = ($from === self::BASE_CURRENCY) ? 1.0 : $this->fetchRate($from, $date);
        $toToThb   = ($to === self::BASE_CURRENCY)   ? 1.0 : $this->fetchRate($to, $date);
        
        if ($fromToThb <= 0 || $toToThb <= 0) {
            throw new \RuntimeException("Exchange rate not available for {$from}/{$to} on {$date}");
        }
        
        $rate = $fromToThb / $toToThb;
        
        // Cache it
        $this->cacheRate($from, $to, $date, $rate);
        
        return $rate;
    }

    /**
     * Convert amount between currencies
     * 
     * @param float  $amount Amount in source currency
     * @param string $from   Source currency code
     * @param string $to     Target currency code
     * @param string $date   Date for exchange rate
     * @return array ['amount' => float, 'rate' => float, 'from' => string, 'to' => string]
     */
    public function convert(float $amount, string $from, string $to, string $date = ''): array
    {
        $rate = $this->getExchangeRate($from, $to, $date);
        $converted = round($amount * $rate, self::$currencies[$to]['decimals'] ?? 2);
        
        return [
            'amount'    => $converted,
            'rate'      => $rate,
            'from'      => $from,
            'to'        => $to,
            'original'  => $amount,
            'date'      => $date ?: date('Y-m-d'),
        ];
    }

    /**
     * Format amount with currency symbol
     * 
     * @param float  $amount   Amount
     * @param string $currency Currency code
     * @param string $lang     Language for formatting (en/th)
     * @return string Formatted amount
     */
    public function format(float $amount, string $currency = 'THB', string $lang = 'en'): string
    {
        $cur = self::$currencies[$currency] ?? ['symbol' => $currency, 'decimals' => 2, 'position' => 'before'];
        $formatted = number_format($amount, $cur['decimals']);
        
        if ($cur['position'] === 'after') {
            return $formatted . ' ' . $cur['symbol'];
        }
        return $cur['symbol'] . $formatted;
    }

    /**
     * Refresh exchange rates from BOT API
     * Called by cron job or admin action
     * 
     * @return array ['success' => bool, 'rates_updated' => int, 'errors' => array]
     */
    public function refreshRates(): array
    {
        $date = date('Y-m-d');
        $updated = 0;
        $errors = [];
        
        foreach (array_keys(self::$currencies) as $code) {
            if ($code === self::BASE_CURRENCY) continue;
            
            try {
                $rate = $this->fetchRateFromBOT($code, $date);
                if ($rate > 0) {
                    $this->cacheRate($code, self::BASE_CURRENCY, $date, $rate);
                    $updated++;
                }
            } catch (\Exception $e) {
                $errors[] = "{$code}: {$e->getMessage()}";
            }
        }
        
        return [
            'success' => empty($errors),
            'rates_updated' => $updated,
            'date' => $date,
            'errors' => $errors,
        ];
    }

    /**
     * Get latest cached rates for display
     * 
     * @return array Exchange rates relative to THB
     */
    public function getLatestRates(): array
    {
        $sql = "SELECT from_currency, to_currency, rate, rate_date 
                FROM exchange_rates 
                WHERE to_currency = 'THB' AND rate_date = (SELECT MAX(rate_date) FROM exchange_rates)
                ORDER BY from_currency";
        $result = mysqli_query($this->conn, $sql);
        
        $rates = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rates[$row['from_currency']] = [
                    'rate' => floatval($row['rate']),
                    'date' => $row['rate_date'],
                ];
            }
        }
        return $rates;
    }

    // ─── Private Helpers ─────────────────────────────────────────────

    private function getCachedRate(string $from, string $to, string $date): ?float
    {
        $sql = "SELECT rate FROM exchange_rates 
                WHERE from_currency = '" . sql_escape($from) . "' 
                AND to_currency = '" . sql_escape($to) . "' 
                AND rate_date = '" . sql_escape($date) . "'";
        $result = mysqli_query($this->conn, $sql);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            return floatval($row['rate']);
        }
        return null;
    }

    private function cacheRate(string $from, string $to, string $date, float $rate): void
    {
        $from = sql_escape($from);
        $to = sql_escape($to);
        $date = sql_escape($date);
        
        $sql = "INSERT INTO exchange_rates (from_currency, to_currency, rate, rate_date, source, created_at)
                VALUES ('{$from}', '{$to}', {$rate}, '{$date}', 'bot', NOW())
                ON DUPLICATE KEY UPDATE rate = {$rate}, updated_at = NOW()";
        mysqli_query($this->conn, $sql);
    }

    private function fetchRate(string $currency, string $date): float
    {
        // Try cache first
        $cached = $this->getCachedRate($currency, self::BASE_CURRENCY, $date);
        if ($cached !== null) return $cached;
        
        // Fetch from BOT API
        return $this->fetchRateFromBOT($currency, $date);
    }

    /**
     * Fetch exchange rate from Bank of Thailand API
     * Returns rate as: 1 unit of foreign currency = X THB
     */
    private function fetchRateFromBOT(string $currency, string $date): float
    {
        $url = self::BOT_API_URL . '?' . http_build_query([
            'start_period' => $date,
            'end_period'   => $date,
            'currency'     => $currency,
        ]);
        
        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => "Accept: application/json\r\n",
                'timeout' => 10,
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        if ($response === false) {
            throw new \RuntimeException("Failed to fetch exchange rate for {$currency}");
        }
        
        $data = json_decode($response, true);
        if (!$data || !isset($data['result']['data']['data_detail'])) {
            throw new \RuntimeException("Invalid BOT API response for {$currency}");
        }
        
        $details = $data['result']['data']['data_detail'];
        if (empty($details)) {
            throw new \RuntimeException("No exchange rate data for {$currency} on {$date}");
        }
        
        $detail = $details[0];
        $midRate = floatval($detail['mid_rate'] ?? 0);
        $buyingRate = floatval($detail['buying_sight'] ?? 0);
        
        // mid_rate is per 1 unit of foreign currency (some currencies per 100 units)
        $rate = $midRate > 0 ? $midRate : $buyingRate;
        
        return $rate;
    }
}
