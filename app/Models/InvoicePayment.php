<?php
namespace App\Models;

/**
 * InvoicePayment Model
 * 
 * Database operations for invoice online payment flow (checkout, success, cancel).
 * Extracted from inv-checkout.php, inv-payment-success.php, inv-payment-cancel.php
 */
class InvoicePayment
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * Get invoice data for checkout
     */
    public function getInvoiceForCheckout(int $invoiceId): ?array
    {
        $sql = "
            SELECT 
                po.id as po_id, po.name as po_name, po.dis, po.vat, po.over,
                iv.tex, iv.createdate, iv.payment_status, iv.paid_amount,
                pr.ven_id, pr.cus_id, pr.payby,
                ven.name_en as vendor_name, ven.logo as vendor_logo,
                cus.name_en as customer_name, cus.email as customer_email
            FROM po 
            JOIN pr ON po.ref = pr.id
            JOIN iv ON po.id = iv.tex
            LEFT JOIN company ven ON pr.ven_id = ven.id
            LEFT JOIN company cus ON pr.payby = cus.id
            WHERE po.id = ?
            AND iv.deleted_at IS NULL
            AND iv.payment_status != 'paid'";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $invoiceId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result) ?: null;
    }

    /**
     * Get full invoice data (for success/cancel display)
     */
    public function getInvoiceForDisplay(int $invoiceId): ?array
    {
        $sql = "SELECT iv.tex, iv.payment_status, iv.payment_gateway, iv.payment_order_id, iv.paid_amount,
                       po.id as po_id, po.name as po_name, po.dis, po.vat, po.over,
                       pr.ven_id, pr.cus_id, pr.payby,
                       cus.name_en as customer_name, cus.phone as customer_phone, cus.email as customer_email,
                       br.id as brand_id
                FROM iv
                JOIN po ON iv.tex = po.id
                JOIN pr ON po.ref = pr.id
                LEFT JOIN company cus ON pr.payby = cus.id
                LEFT JOIN brand br ON po.bandven = br.id
                WHERE iv.tex = ?
                AND iv.deleted_at IS NULL";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $invoiceId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result) ?: null;
    }

    /**
     * Get basic invoice data for display
     */
    public function getInvoiceBasicDisplay(int $invoiceId): ?array
    {
        $sql = "SELECT iv.tex, po.name as po_name, iv.paid_amount, c.name_en as vendor_name,
                       iv.payment_gateway
                FROM iv
                JOIN po ON iv.tex = po.id
                JOIN pr ON po.ref = pr.id
                LEFT JOIN company c ON pr.ven_id = c.id
                WHERE iv.tex = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $invoiceId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        return mysqli_fetch_assoc($result) ?: null;
    }

    /**
     * Calculate invoice total from products
     */
    public function calculateTotal(int $invoiceId, array $invoice): array
    {
        $prodSql = "SELECT product.price, product.quantity, product.valuelabour, type.activelabour
                    FROM product JOIN type ON product.type = type.id
                    WHERE product.po_id = ?";
        $stmt = mysqli_prepare($this->conn, $prodSql);
        mysqli_stmt_bind_param($stmt, "i", $invoiceId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        $subtotal = 0;
        while ($prod = mysqli_fetch_assoc($result)) {
            $equip = $prod['price'] * $prod['quantity'];
            $labour = ($prod['valuelabour'] * $prod['activelabour']) * $prod['quantity'];
            $subtotal += $equip + $labour;
        }

        $discount = $subtotal * ($invoice['dis'] / 100);
        $afterDiscount = $subtotal - $discount;

        $overhead = 0;
        if ($invoice['over'] > 0) {
            $overhead = $afterDiscount * ($invoice['over'] / 100);
            $afterDiscount += $overhead;
        }

        $vatAmount = $afterDiscount * ($invoice['vat'] / 100);
        $grandTotal = round($afterDiscount + $vatAmount, 2);
        $amountPaid = floatval($invoice['paid_amount'] ?? 0);
        $amountDue = $grandTotal - $amountPaid;

        return compact('subtotal', 'discount', 'afterDiscount', 'overhead', 'vatAmount', 'grandTotal', 'amountPaid', 'amountDue');
    }

    /**
     * Get active configured payment gateways
     */
    public function getActiveGateways(): array
    {
        $gateways = [];
        $gwSql = "SELECT pm.id, pm.code, pm.name, pm.icon 
                  FROM payment_method pm 
                  WHERE pm.is_gateway = 1 AND pm.is_active = 1 
                  ORDER BY pm.sort_order";
        $gwResult = mysqli_query($this->conn, $gwSql);
        while ($gw = mysqli_fetch_assoc($gwResult)) {
            $configSql = "SELECT COUNT(*) as cnt FROM payment_gateway_config WHERE payment_method_id = ? AND config_value != ''";
            $configStmt = mysqli_prepare($this->conn, $configSql);
            mysqli_stmt_bind_param($configStmt, "i", $gw['id']);
            mysqli_stmt_execute($configStmt);
            $configResult = mysqli_stmt_get_result($configStmt);
            $cnt = mysqli_fetch_assoc($configResult)['cnt'];
            if ($cnt > 0) {
                $gateways[] = $gw;
            }
        }
        return $gateways;
    }

    /**
     * Update invoice payment info
     */
    public function updatePaymentGateway(int $invoiceId, string $gateway, string $orderId): void
    {
        $sql = "UPDATE iv SET payment_gateway = ?, payment_order_id = ? WHERE tex = ?";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssi", $gateway, $orderId, $invoiceId);
        mysqli_stmt_execute($stmt);
    }

    /**
     * Mark invoice as paid and create receipt — atomic transaction
     */
    public function markPaid(int $invoiceId, float $paidAmount, string $gateway, string $transactionId, array $invoice): ?int
    {
        mysqli_begin_transaction($this->conn);
        try {
            // 1. Update invoice status
            $sql = "UPDATE iv SET payment_status = 'paid', paid_amount = ?, paid_date = NOW(),
                    payment_gateway = ?, payment_order_id = ? WHERE tex = ?";
            $stmt = mysqli_prepare($this->conn, $sql);
            mysqli_stmt_bind_param($stmt, "dssi", $paidAmount, $gateway, $transactionId, $invoiceId);
            mysqli_stmt_execute($stmt);

            // 2. Generate receipt number
            $year = date('Y') + 543;
            $yearPrefix = substr($year, -2) . '000';
            $maxSql = "SELECT MAX(rep_no) as max_no FROM receipt WHERE rep_rw LIKE ?";
            $maxStmt = mysqli_prepare($this->conn, $maxSql);
            $yearPattern = $yearPrefix . '%';
            mysqli_stmt_bind_param($maxStmt, "s", $yearPattern);
            mysqli_stmt_execute($maxStmt);
            $maxResult = mysqli_stmt_get_result($maxStmt);
            $maxRow = mysqli_fetch_assoc($maxResult);
            $newRepNo = ($maxRow['max_no'] ?? 0) + 1;
            $repRw = $yearPrefix . str_pad($newRepNo, 4, '0', STR_PAD_LEFT);

            // 3. Create receipt
            $insertSql = "INSERT INTO receipt (
                name, phone, email, createdate, description,
                payment_method, status, invoice_id, vender,
                rep_no, rep_rw, brand, vat, dis,
                payment_source, payment_transaction_id
            ) VALUES (?, ?, ?, NOW(), ?, ?, 'confirmed', ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $description = "Auto-generated receipt for Invoice #" . $invoiceId . " - Paid via " . ucfirst($gateway);
            $brandId = $invoice['brand_id'] ?: 0;

            $insertStmt = mysqli_prepare($this->conn, $insertSql);
            mysqli_stmt_bind_param($insertStmt, "sssssiiisiiiss",
                $invoice['customer_name'],
                $invoice['customer_phone'],
                $invoice['customer_email'],
                $description,
                $gateway,
                $invoiceId,
                $invoice['ven_id'],
                $newRepNo,
                $repRw,
                $brandId,
                $invoice['vat'],
                $invoice['dis'],
                $gateway,
                $transactionId
            );
            mysqli_stmt_execute($insertStmt);
            $receiptId = mysqli_insert_id($this->conn);

            // 4. Audit log
            if (function_exists('audit_log')) {
                audit_log('create', 'receipt', $receiptId, null, [
                    'invoice_id' => $invoiceId,
                    'gateway' => $gateway,
                    'amount' => $paidAmount,
                    'auto_created' => true,
                ]);
            }

            mysqli_commit($this->conn);
            return $receiptId;

        } catch (\Exception $e) {
            mysqli_rollback($this->conn);
            throw $e;
        }
    }

    /**
     * Get existing receipt for invoice
     */
    public function getExistingReceipt(int $invoiceId): ?int
    {
        $sql = "SELECT id FROM receipt WHERE invoice_id = ? AND deleted_at IS NULL ORDER BY id DESC LIMIT 1";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $invoiceId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = mysqli_fetch_assoc($result);
        return $row ? (int) $row['id'] : null;
    }

    /**
     * Clear pending payment order (on cancel)
     */
    public function clearPendingPayment(int $invoiceId): void
    {
        $sql = "UPDATE iv SET payment_order_id = NULL WHERE tex = ? AND payment_status = 'pending'";
        $stmt = mysqli_prepare($this->conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $invoiceId);
        mysqli_stmt_execute($stmt);
    }
}
