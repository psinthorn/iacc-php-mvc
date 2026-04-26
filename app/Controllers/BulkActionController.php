<?php
namespace App\Controllers;

use App\Traits\BulkActionTrait;
use App\Models\TourBookingPayment;
use App\Services\EmailService;

/**
 * BulkActionController — Tour booking bulk operations
 *
 * Route: 'tour_booking_bulk' => ['BulkActionController', 'handleBulkAction', 'standalone']
 * Supported actions: delete, export_csv, confirm, mark_payment, send_vouchers, send_invoices
 */
class BulkActionController extends BaseController
{
    use BulkActionTrait;

    private const ALLOWED_STATUSES  = ['draft', 'confirmed', 'completed', 'cancelled'];
    private const ALLOWED_METHODS   = ['cash', 'bank_transfer', 'credit_card', 'cheque', 'other'];

    protected function allowedBulkActions(): array
    {
        return ['delete', 'export_csv', 'confirm', 'change_status', 'mark_payment', 'send_vouchers', 'send_invoices'];
    }

    protected function executeBulkAction(string $action, array $ids): array
    {
        $comId    = $this->user['com_id'];
        $ownedIds = $this->filterOwnedIds('tour_bookings', $ids, $comId);

        $unowned = count($ids) - count($ownedIds);
        $errors  = $unowned > 0 ? ["$unowned record(s) skipped — not found or access denied"] : [];

        if (empty($ownedIds)) {
            return ['processed' => 0, 'failed' => count($ids), 'errors' => $errors];
        }

        return match ($action) {
            'delete'         => $this->bulkDelete($ownedIds, $errors),
            'export_csv'     => $this->bulkExportCsv($ownedIds, $comId),
            'confirm'        => $this->bulkConfirm($ownedIds, $errors),
            'change_status'  => $this->bulkChangeStatus($ownedIds, $errors),
            'mark_payment'   => $this->bulkMarkPayment($ownedIds, $comId, $errors),
            'send_vouchers'  => $this->bulkSendVouchers($ownedIds, $comId, $errors),
            'send_invoices'  => $this->bulkSendInvoices($ownedIds, $comId, $errors),
        };
    }

    // ─── Delete ────────────────────────────────────────────────────────────────

    private function bulkDelete(array $ids, array $errors = []): array
    {
        $processed = 0;
        $failed    = 0;
        $comId     = $this->user['com_id'];

        $stmt = mysqli_prepare(
            $this->conn,
            "UPDATE tour_bookings SET deleted_at = NOW()
             WHERE id = ? AND company_id = ? AND deleted_at IS NULL"
        );

        if (!$stmt) {
            return ['processed' => 0, 'failed' => count($ids), 'errors' => ['DB prepare failed']];
        }

        foreach ($ids as $id) {
            mysqli_stmt_bind_param($stmt, 'ii', $id, $comId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_affected_rows($stmt) > 0 ? $processed++ : $failed++;
        }
        mysqli_stmt_close($stmt);

        return ['processed' => $processed, 'failed' => $failed, 'errors' => $errors];
    }

    // ─── Export CSV ────────────────────────────────────────────────────────────

    private function bulkExportCsv(array $ids, int $comId): array
    {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT b.booking_number, b.travel_date, b.status,
                    b.total_amount, b.currency,
                    COALESCE(cust.name_en, '') AS customer_name,
                    COALESCE(agt.name_en, '')  AS agent_name,
                    b.booking_by, b.created_at
             FROM tour_bookings b
             LEFT JOIN company cust ON b.customer_id = cust.id
             LEFT JOIN company agt  ON b.agent_id    = agt.id
             WHERE b.company_id = ? AND b.id IN ($placeholders) AND b.deleted_at IS NULL
             ORDER BY b.travel_date, b.booking_number"
        );

        if (!$stmt) {
            return ['processed' => 0, 'failed' => count($ids), 'errors' => ['DB prepare failed']];
        }

        $types  = str_repeat('i', count($ids) + 1);
        $params = array_merge([$comId], $ids);
        $refs   = [];
        foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
        mysqli_stmt_bind_param($stmt, $types, ...$refs);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="tour_bookings_export_' . date('Ymd_His') . '.csv"');
        header('Cache-Control: no-cache, no-store');
        echo "\xEF\xBB\xBF";

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Booking #', 'Travel Date', 'Status', 'Amount', 'Currency', 'Customer', 'Agent', 'Booked By', 'Created At']);
        while ($row = mysqli_fetch_assoc($res)) {
            fputcsv($out, array_values($row));
        }
        fclose($out);
        mysqli_stmt_close($stmt);
        exit;
    }

    // ─── Confirm ───────────────────────────────────────────────────────────────

    private function bulkConfirm(array $ids, array $errors = []): array
    {
        $processed = 0;
        $skipped   = 0;
        $comId     = $this->user['com_id'];

        $stmt = mysqli_prepare(
            $this->conn,
            "UPDATE tour_bookings SET status = 'confirmed', updated_at = NOW()
             WHERE id = ? AND company_id = ? AND status = 'draft' AND deleted_at IS NULL"
        );

        if (!$stmt) {
            return ['processed' => 0, 'failed' => count($ids), 'errors' => ['DB prepare failed']];
        }

        foreach ($ids as $id) {
            mysqli_stmt_bind_param($stmt, 'ii', $id, $comId);
            mysqli_stmt_execute($stmt);
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                $processed++;
            } else {
                $skipped++;
                $errors[] = "Booking ID $id skipped — already confirmed or not in draft";
            }
        }
        mysqli_stmt_close($stmt);

        return ['processed' => $processed, 'failed' => $skipped, 'errors' => $errors];
    }

    // ─── Change Status ─────────────────────────────────────────────────────────

    private function bulkChangeStatus(array $ids, array $errors = []): array
    {
        $status = trim($_POST['new_status'] ?? '');

        if (!in_array($status, self::ALLOWED_STATUSES, true)) {
            return ['processed' => 0, 'failed' => count($ids), 'errors' => ["Invalid status: $status"]];
        }

        $comId     = $this->user['com_id'];
        $processed = 0;
        $skipped   = 0;

        $stmt = mysqli_prepare(
            $this->conn,
            "UPDATE tour_bookings SET status = ?, updated_at = NOW()
             WHERE id = ? AND company_id = ? AND deleted_at IS NULL"
        );

        if (!$stmt) {
            return ['processed' => 0, 'failed' => count($ids), 'errors' => ['DB prepare failed']];
        }

        foreach ($ids as $id) {
            mysqli_stmt_bind_param($stmt, 'sii', $status, $id, $comId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_affected_rows($stmt) > 0 ? $processed++ : $skipped++;
        }
        mysqli_stmt_close($stmt);

        $label = ucfirst($status);
        return [
            'processed' => $processed,
            'failed'    => $skipped,
            'errors'    => $errors,
            'message'   => "$processed booking(s) changed to $label" . ($skipped > 0 ? ", $skipped skipped" : ''),
        ];
    }

    // ─── Mark Payment Received ─────────────────────────────────────────────────

    private function bulkMarkPayment(array $ids, int $comId, array $errors = []): array
    {
        $amount  = floatval($_POST['amount']  ?? 0);
        $method  = trim($_POST['method']      ?? 'cash');
        $date    = trim($_POST['payment_date'] ?? date('Y-m-d'));
        $notes   = trim($_POST['notes']        ?? '');
        $userId  = intval($this->user['id']    ?? 0);

        if ($amount <= 0) {
            return ['processed' => 0, 'failed' => count($ids), 'errors' => ['Amount must be greater than 0']];
        }
        if (!in_array($method, self::ALLOWED_METHODS, true)) {
            return ['processed' => 0, 'failed' => count($ids), 'errors' => ["Invalid payment method: $method"]];
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $paymentModel = new TourBookingPayment();
        $processed    = 0;
        $failed       = 0;

        foreach ($ids as $id) {
            $newId = $paymentModel->recordPayment([
                'booking_id'     => $id,
                'company_id'     => $comId,
                'payment_method' => $method,
                'amount'         => $amount,
                'currency'       => 'THB',
                'payment_date'   => $date,
                'status'         => 'completed',
                'payment_type'   => 'full',
                'notes'          => $notes ?: "Bulk payment recorded",
                'created_by'     => $userId,
            ]);

            if ($newId > 0) {
                $processed++;
            } else {
                $failed++;
                $errors[] = "Booking ID $id: payment record failed";
            }
        }

        return ['processed' => $processed, 'failed' => $failed, 'errors' => $errors];
    }

    // ─── Send Vouchers ─────────────────────────────────────────────────────────

    private function bulkSendVouchers(array $ids, int $comId, array $errors = []): array
    {
        $processed   = 0;
        $failed      = 0;
        $emailSent   = 0;
        $noEmail     = 0;
        $emailSvc    = new EmailService($this->conn, $comId);

        // Fetch company name once
        $companyRow  = mysqli_fetch_assoc(mysqli_query($this->conn,
            "SELECT name_en, name_th FROM company WHERE id = $comId LIMIT 1"
        ));
        $companyName = $companyRow['name_en'] ?: $companyRow['name_th'] ?: '';

        foreach ($ids as $id) {
            $bid = intval($id);

            // Update timestamp (only confirmed/completed)
            $upd = mysqli_query($this->conn,
                "UPDATE tour_bookings SET voucher_sent_at = NOW(), updated_at = NOW()
                 WHERE id = $bid AND company_id = $comId
                   AND status IN ('confirmed','completed') AND deleted_at IS NULL"
            );

            if (!$upd || mysqli_affected_rows($this->conn) === 0) {
                $failed++;
                $errors[] = "Booking #$bid skipped — must be confirmed or completed";
                continue;
            }

            $processed++;

            // Fetch booking + primary contact for email
            $bRow = mysqli_fetch_assoc(mysqli_query($this->conn,
                "SELECT b.*, '$companyName' AS company_name FROM tour_bookings b WHERE b.id = $bid LIMIT 1"
            ));
            $cRow = mysqli_fetch_assoc(mysqli_query($this->conn,
                "SELECT contact_name, mobile AS email FROM tour_booking_contacts WHERE booking_id = $bid ORDER BY id LIMIT 1"
            ));

            if (!empty($cRow['email'])) {
                $result = $emailSvc->sendVoucher($bRow ?? [], $cRow);
                if ($result['sent']) {
                    $emailSent++;
                } else {
                    $noEmail++;
                    $errors[] = "Booking #$bid: email failed — " . $result['error'];
                }
            } else {
                $noEmail++;
            }
        }

        $note = '';
        if ($emailSent > 0)   $note .= " — {$emailSent} email(s) sent";
        if ($noEmail  > 0)    $note .= ", {$noEmail} skipped (no email address)";

        return ['processed' => $processed, 'failed' => $failed, 'errors' => $errors, 'note' => $note];
    }

    // ─── Send Invoices ─────────────────────────────────────────────────────────

    private function bulkSendInvoices(array $ids, int $comId, array $errors = []): array
    {
        $processed   = 0;
        $failed      = 0;
        $emailSent   = 0;
        $noEmail     = 0;
        $emailSvc    = new EmailService($this->conn, $comId);

        $companyRow  = mysqli_fetch_assoc(mysqli_query($this->conn,
            "SELECT name_en, name_th FROM company WHERE id = $comId LIMIT 1"
        ));
        $companyName = $companyRow['name_en'] ?: $companyRow['name_th'] ?: '';

        foreach ($ids as $id) {
            $bid = intval($id);

            $upd = mysqli_query($this->conn,
                "UPDATE tour_bookings SET invoice_sent_at = NOW(), updated_at = NOW()
                 WHERE id = $bid AND company_id = $comId AND deleted_at IS NULL"
            );

            if (!$upd || mysqli_affected_rows($this->conn) === 0) {
                $failed++;
                continue;
            }

            $processed++;

            $bRow = mysqli_fetch_assoc(mysqli_query($this->conn,
                "SELECT b.*, '$companyName' AS company_name FROM tour_bookings b WHERE b.id = $bid LIMIT 1"
            ));
            $cRow = mysqli_fetch_assoc(mysqli_query($this->conn,
                "SELECT contact_name, mobile AS email FROM tour_booking_contacts WHERE booking_id = $bid ORDER BY id LIMIT 1"
            ));

            if (!empty($cRow['email'])) {
                $result = $emailSvc->sendInvoiceNotification($bRow ?? [], $cRow);
                if ($result['sent']) {
                    $emailSent++;
                } else {
                    $noEmail++;
                    $errors[] = "Booking #$bid: email failed — " . $result['error'];
                }
            } else {
                $noEmail++;
            }
        }

        $note = '';
        if ($emailSent > 0) $note .= " — {$emailSent} email(s) sent";
        if ($noEmail  > 0)  $note .= ", {$noEmail} skipped (no email address)";

        return ['processed' => $processed, 'failed' => $failed, 'errors' => $errors, 'note' => $note];
    }
}
