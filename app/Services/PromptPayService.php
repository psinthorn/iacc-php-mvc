<?php
namespace App\Services;

/**
 * PromptPayService — QR Code Payment via Thai PromptPay
 * 
 * Generates QR codes following the EMVCo QR Code standard for PromptPay.
 * Supports both phone number and national ID as payee identifiers.
 * 
 * Flow:
 *   1. Invoice → Generate QR code with amount
 *   2. Customer scans with banking app
 *   3. Admin confirms payment manually (or via bank API in future)
 *   4. Invoice marked as paid, receipt created
 * 
 * EMVCo Spec: https://www.emvco.com/emv-technologies/qrcodes/
 * PromptPay AID: A000000677010111 (PromptPay Credit Transfer)
 * 
 * @package App\Services
 * @version 1.0.0 — Q2 2026
 */
class PromptPayService
{
    /** PromptPay Application ID */
    private const PROMPTPAY_AID = 'A000000677010111';
    
    /** Tag constants for EMVCo QR */
    private const TAG_PAYLOAD_FORMAT = '00';
    private const TAG_POI_METHOD = '01';
    private const TAG_MERCHANT_INFO = '29';
    private const TAG_COUNTRY_CODE = '58';
    private const TAG_CURRENCY = '53';
    private const TAG_AMOUNT = '54';
    private const TAG_CHECKSUM = '63';
    
    /** Currency code for Thai Baht */
    private const CURRENCY_THB = '764';
    
    /** Country code */
    private const COUNTRY_TH = 'TH';

    private \mysqli $conn;
    private array $config;

    public function __construct(\mysqli $conn = null)
    {
        if ($conn) {
            $this->conn = $conn;
        } else {
            global $db;
            $this->conn = $db->conn;
        }
        $this->config = [];
    }

    /**
     * Load PromptPay config for a company from payment_gateway_config
     */
    public function loadConfig(int $companyId): self
    {
        $sql = "SELECT pgc.config_key, pgc.config_value 
                FROM payment_gateway_config pgc
                JOIN payment_method pm ON pm.id = pgc.payment_method_id
                WHERE pm.code = 'promptpay' AND pgc.company_id = " . intval($companyId);
        $result = mysqli_query($this->conn, $sql);
        
        $this->config = [];
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $this->config[$row['config_key']] = $row['config_value'];
            }
        }
        return $this;
    }

    /**
     * Get current config array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Generate PromptPay QR payload string (EMVCo format)
     * 
     * @param float  $amount  Payment amount in THB (0 for open amount)
     * @param string $target  Phone number (0812345678) or National ID (1234567890123)
     * @return string EMVCo QR payload string
     */
    public function generatePayload(float $amount, string $target = ''): string
    {
        $target = $target ?: ($this->config['promptpay_id'] ?? '');
        if (empty($target)) {
            throw new \InvalidArgumentException('PromptPay target (phone or national ID) is required');
        }

        // Sanitize target: remove dashes, spaces
        $target = preg_replace('/[^0-9]/', '', $target);
        
        // Determine target type and format
        $formattedTarget = $this->formatTarget($target);
        
        // Build EMVCo payload
        $payload = '';
        $payload .= $this->tlv(self::TAG_PAYLOAD_FORMAT, '01');  // Payload Format Indicator
        $payload .= $this->tlv(self::TAG_POI_METHOD, $amount > 0 ? '12' : '11');  // 11=static, 12=dynamic
        
        // Merchant Account Information (Tag 29 — PromptPay)
        $merchantInfo = '';
        $merchantInfo .= $this->tlv('00', self::PROMPTPAY_AID);  // Application ID
        $merchantInfo .= $this->tlv('01', $formattedTarget);     // Mobile or National ID
        $payload .= $this->tlv(self::TAG_MERCHANT_INFO, $merchantInfo);
        
        $payload .= $this->tlv(self::TAG_COUNTRY_CODE, self::COUNTRY_TH);
        $payload .= $this->tlv(self::TAG_CURRENCY, self::CURRENCY_THB);
        
        if ($amount > 0) {
            $payload .= $this->tlv(self::TAG_AMOUNT, number_format($amount, 2, '.', ''));
        }
        
        // CRC16 checksum (calculated over entire payload + tag 63 + length 04)
        $payload .= self::TAG_CHECKSUM . '04';
        $checksum = $this->crc16($payload);
        $payload .= strtoupper(str_pad(dechex($checksum), 4, '0', STR_PAD_LEFT));
        
        return $payload;
    }

    /**
     * Generate QR code as base64-encoded PNG image
     * 
     * Uses a simple QR generation approach. For production, integrate
     * endroid/qr-code or chillerlan/php-qrcode via Composer.
     * 
     * @param float  $amount   Payment amount in THB
     * @param int    $size     QR code pixel size
     * @param string $target   Optional override for payee ID
     * @return array ['payload' => string, 'qr_base64' => string, 'qr_url' => string]
     */
    public function generateQR(float $amount, int $size = 300, string $target = ''): array
    {
        $payload = $this->generatePayload($amount, $target);

        // api.qrserver.com — free, reliable, no deprecated status
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size
               . '&data=' . urlencode($payload)
               . '&format=png&ecc=M&margin=10';

        return [
            'payload'   => $payload,
            'qr_url'    => $qrUrl,
            'qr_base64' => '',
            'amount'    => $amount,
            'target'    => $target ?: ($this->config['promptpay_id'] ?? ''),
        ];
    }

    /**
     * Create a pending PromptPay payment record
     * 
     * @param int   $invoiceId  Invoice tex/id
     * @param float $amount     Amount in THB
     * @param int   $companyId  Company ID
     * @return int  Payment log ID
     */
    public function createPendingPayment(int $invoiceId, float $amount, int $companyId = 0): int
    {
        $requestData = sql_escape(json_encode([
            'invoice_id' => $invoiceId,
            'company_id' => $companyId,
            'promptpay_id' => $this->config['promptpay_id'] ?? '',
            'generated_at' => date('Y-m-d H:i:s'),
        ]));
        $sql = "INSERT INTO payment_log (gateway, order_id, reference_id, amount, currency, status, 
                    request_data, created_at)
                VALUES ('promptpay', 'INV-{$invoiceId}', 'PP-{$invoiceId}', {$amount}, 'THB', 'pending',
                    '{$requestData}', NOW())";
        mysqli_query($this->conn, $sql);
        return (int) mysqli_insert_id($this->conn);
    }

    /**
     * Confirm a PromptPay payment (manual confirmation by admin)
     * 
     * @param int    $paymentLogId  Payment log ID
     * @param string $transRef      Bank transaction reference
     * @param string $slipImage     Uploaded slip image path (optional)
     * @return bool
     */
    public function confirmPayment(int $paymentLogId, string $transRef = '', string $slipImage = ''): bool
    {
        $responseData = sql_escape(json_encode([
            'confirmed_at' => date('Y-m-d H:i:s'),
            'confirmed_by' => $_SESSION['user_id'] ?? 0,
            'transaction_ref' => $transRef,
            'slip_image' => $slipImage,
        ]));
        
        $slipEsc = sql_escape($slipImage);
        $refEsc = sql_escape($transRef);
        $sql = "UPDATE payment_log SET status = 'completed', 
                    reference_id = '{$refEsc}',
                    slip_image = '{$slipEsc}',
                    response_data = '{$responseData}',
                    updated_at = NOW()
                WHERE id = " . intval($paymentLogId) . " AND gateway = 'promptpay'";
        return mysqli_query($this->conn, $sql) && mysqli_affected_rows($this->conn) > 0;
    }

    /**
     * Format target number for EMVCo payload
     * Phone: 0066812345678 format
     * National ID: 13 digits as-is
     */
    private function formatTarget(string $target): string
    {
        $len = strlen($target);
        
        if ($len === 13) {
            // National ID — use as-is
            return $target;
        }
        
        if ($len === 10 && $target[0] === '0') {
            // Thai phone number — convert to international format (0066...)
            return '0066' . substr($target, 1);
        }
        
        if ($len === 9) {
            // Phone without leading 0
            return '0066' . $target;
        }
        
        throw new \InvalidArgumentException("Invalid PromptPay target: must be 10-digit phone or 13-digit national ID");
    }

    /**
     * Create TLV (Tag-Length-Value) string
     */
    private function tlv(string $tag, string $value): string
    {
        return $tag . str_pad(strlen($value), 2, '0', STR_PAD_LEFT) . $value;
    }

    /**
     * Calculate CRC16-CCITT checksum
     */
    private function crc16(string $data): int
    {
        $crc = 0xFFFF;
        for ($i = 0; $i < strlen($data); $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                if ($crc & 0x8000) {
                    $crc = ($crc << 1) ^ 0x1021;
                } else {
                    $crc = $crc << 1;
                }
            }
            $crc &= 0xFFFF;
        }
        return $crc;
    }

    /**
     * Get PromptPay gateway configuration fields (for admin UI)
     */
    public static function getConfigFields(): array
    {
        return [
            'promptpay_id' => [
                'label' => 'PromptPay ID',
                'label_th' => 'หมายเลข PromptPay',
                'type' => 'text',
                'placeholder' => 'Phone (0812345678) or National ID (1234567890123)',
                'required' => true,
            ],
            'promptpay_name' => [
                'label' => 'Account Name',
                'label_th' => 'ชื่อบัญชี',
                'type' => 'text',
                'placeholder' => 'Company or person name',
                'required' => true,
            ],
            'promptpay_auto_confirm' => [
                'label' => 'Auto Confirm',
                'label_th' => 'ยืนยันอัตโนมัติ',
                'type' => 'select',
                'options' => ['0' => 'Manual confirmation', '1' => 'Auto (bank API)'],
                'default' => '0',
            ],
        ];
    }
}
