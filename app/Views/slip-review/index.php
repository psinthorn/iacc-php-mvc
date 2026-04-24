<?php
$pageTitle = 'Slip Review';

/**
 * Slip Review — Admin PromptPay Payment Review
 * 
 * Modern UI using master-data.css design system
 * Variables from controller: $stats, $payments, $total, $totalPages, $page,
 *   $status, $search, $dateFrom, $dateTo, $successMsg, $errorMsg, $lang
 */

$statusColors = [
    'pending'        => 'warning',
    'pending_review' => 'info',
    'completed'      => 'success',
    'rejected'       => 'danger',
    'failed'         => 'danger',
];

$statusLabels = [
    'pending'        => $lang === 'th' ? 'รอดำเนินการ' : 'Pending',
    'pending_review' => $lang === 'th' ? 'รอตรวจสอบ' : 'Pending Review',
    'completed'      => $lang === 'th' ? 'อนุมัติแล้ว' : 'Approved',
    'rejected'       => $lang === 'th' ? 'ปฏิเสธ' : 'Rejected',
    'failed'         => $lang === 'th' ? 'ล้มเหลว' : 'Failed',
];
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
/* Slip Review — Additional Styles */
.slip-thumb {
    width: 56px;
    height: 56px;
    border-radius: 10px;
    object-fit: cover;
    border: 2px solid var(--md-border, #e2e8f0);
    cursor: pointer;
    transition: all 0.2s ease;
    background: var(--md-bg-light, #f8fafc);
}
.slip-thumb:hover {
    transform: scale(1.08);
    border-color: var(--md-primary, #4f46e5);
    box-shadow: 0 4px 12px rgba(79, 70, 229, 0.25);
}
.slip-placeholder {
    width: 56px;
    height: 56px;
    border-radius: 10px;
    background: var(--md-bg-light, #f8fafc);
    border: 2px dashed var(--md-border, #e2e8f0);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--md-text-muted, #94a3b8);
    font-size: 20px;
}
.amount-cell {
    font-weight: 700;
    font-size: 14px;
    color: var(--md-text-primary, #1e293b);
    font-family: 'SF Mono', 'Fira Code', monospace;
}
.invoice-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px;
    border-radius: 6px;
    background: rgba(79, 70, 229, 0.08);
    color: var(--md-primary, #4f46e5);
    font-weight: 600;
    font-size: 12px;
    text-decoration: none;
    transition: all 0.2s;
}
.invoice-link:hover {
    background: rgba(79, 70, 229, 0.15);
    text-decoration: none;
    color: var(--md-primary, #4f46e5);
}
.customer-name {
    font-size: 12px;
    color: var(--md-text-secondary, #64748b);
    margin-top: 2px;
}
.status-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    letter-spacing: 0.3px;
}
.status-badge.pending { background: #fef3c7; color: #d97706; }
.status-badge.pending_review { background: #dbeafe; color: #2563eb; }
.status-badge.completed { background: #d1fae5; color: #059669; }
.status-badge.rejected { background: #fee2e2; color: #dc2626; }
.status-badge.failed { background: #fee2e2; color: #dc2626; }
.status-badge i { font-size: 10px; }

.action-btn-group {
    display: flex;
    gap: 6px;
    align-items: center;
}
.action-btn-group .btn-approve,
.action-btn-group .btn-reject,
.action-btn-group .btn-view-slip {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
}
.action-btn-group .btn-approve {
    background: rgba(16, 185, 129, 0.12);
    color: #059669;
}
.action-btn-group .btn-approve:hover {
    background: #10b981;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(16, 185, 129, 0.3);
}
.action-btn-group .btn-reject {
    background: rgba(239, 68, 68, 0.10);
    color: #dc2626;
}
.action-btn-group .btn-reject:hover {
    background: #ef4444;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(239, 68, 68, 0.3);
}
.action-btn-group .btn-view-slip {
    background: rgba(79, 70, 229, 0.10);
    color: #4f46e5;
}
.action-btn-group .btn-view-slip:hover {
    background: #4f46e5;
    color: white;
    transform: translateY(-1px);
    box-shadow: 0 3px 8px rgba(79, 70, 229, 0.3);
}
.time-cell {
    font-size: 12px;
    color: var(--md-text-secondary, #64748b);
    line-height: 1.5;
}
.time-cell .date { font-weight: 600; color: var(--md-text-primary, #1e293b); }
.ref-cell {
    font-size: 12px;
    color: var(--md-text-secondary, #64748b);
    font-family: 'SF Mono', 'Fira Code', monospace;
}

/* Lightbox overlay */
.slip-lightbox {
    display: none;
    position: fixed;
    z-index: 9999;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.8);
    align-items: center;
    justify-content: center;
    padding: 40px;
}
.slip-lightbox.active { display: flex; }
.slip-lightbox img {
    max-width: 90%;
    max-height: 85vh;
    border-radius: 12px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}
.slip-lightbox .close-btn {
    position: absolute;
    top: 20px; right: 30px;
    font-size: 32px;
    color: white;
    cursor: pointer;
    background: rgba(0,0,0,0.4);
    width: 48px; height: 48px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s;
}
.slip-lightbox .close-btn:hover { background: rgba(239,68,68,0.8); }

/* Reject modal */
.reject-modal-overlay {
    display: none;
    position: fixed;
    z-index: 9998;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5);
    align-items: center;
    justify-content: center;
}
.reject-modal-overlay.active { display: flex; }
.reject-modal {
    background: white;
    border-radius: 16px;
    padding: 28px;
    width: 420px;
    max-width: 95vw;
    box-shadow: 0 20px 60px rgba(0,0,0,0.2);
}
.reject-modal h4 {
    margin: 0 0 16px;
    font-size: 18px;
    font-weight: 700;
    color: #dc2626;
}
.reject-modal textarea {
    width: 100%;
    min-height: 80px;
    border: 2px solid var(--md-border, #e2e8f0);
    border-radius: 10px;
    padding: 12px;
    font-size: 14px;
    font-family: inherit;
    resize: vertical;
    transition: border-color 0.2s;
}
.reject-modal textarea:focus {
    outline: none;
    border-color: #ef4444;
}
.reject-modal .modal-actions {
    display: flex;
    gap: 10px;
    margin-top: 16px;
    justify-content: flex-end;
}
.reject-modal .btn-cancel-modal {
    padding: 8px 20px;
    border-radius: 8px;
    border: 2px solid var(--md-border, #e2e8f0);
    background: white;
    color: var(--md-text-secondary, #64748b);
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
}
.reject-modal .btn-confirm-reject {
    padding: 8px 20px;
    border-radius: 8px;
    border: none;
    background: #ef4444;
    color: white;
    font-weight: 600;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.2s;
}
.reject-modal .btn-confirm-reject:hover { background: #dc2626; }
</style>

<div class="master-data-container">

    <!-- Page Header -->
    <div class="master-data-header">
        <h2><i class="fa fa-qrcode"></i> <?= $lang === 'th' ? 'ตรวจสอบสลิปพร้อมเพย์' : 'PromptPay Slip Review' ?></h2>
        <div>
            <a href="index.php?page=invoice_payments" class="btn btn-sm btn-outline-primary" style="border-radius:8px; font-weight:600;">
                <i class="fa fa-money"></i> <?= $lang === 'th' ? 'ติดตามการชำระ' : 'Payment Tracking' ?>
            </a>
        </div>
    </div>

    <!-- Flash Messages -->
    <?php if ($successMsg): ?>
    <div class="alert alert-success alert-dismissible" style="border-radius:10px; border-left:4px solid #10b981;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fa fa-check-circle"></i> <?= htmlspecialchars($successMsg) ?>
    </div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
    <div class="alert alert-danger alert-dismissible" style="border-radius:10px; border-left:4px solid #ef4444;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($errorMsg) ?>
    </div>
    <?php endif; ?>

    <!-- Stats Cards -->
    <div class="stats-row">
        <div class="stat-card primary">
            <i class="fa fa-list-alt stat-icon"></i>
            <div class="stat-value"><?= intval($stats['total']) ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'ทั้งหมด' : 'Total' ?></div>
        </div>
        <div class="stat-card warning">
            <i class="fa fa-clock-o stat-icon"></i>
            <div class="stat-value"><?= intval($stats['pending_review']) + intval($stats['pending']) ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'รอตรวจสอบ' : 'Pending Review' ?></div>
        </div>
        <div class="stat-card success">
            <i class="fa fa-check-circle stat-icon"></i>
            <div class="stat-value"><?= intval($stats['completed']) ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'อนุมัติแล้ว' : 'Approved' ?></div>
        </div>
        <div class="stat-card danger">
            <i class="fa fa-times-circle stat-icon"></i>
            <div class="stat-value"><?= intval($stats['rejected']) ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'ปฏิเสธ' : 'Rejected' ?></div>
        </div>
        <div class="stat-card info">
            <i class="fa fa-money stat-icon"></i>
            <div class="stat-value">฿<?= number_format(floatval($stats['total_confirmed']), 0) ?></div>
            <div class="stat-label"><?= $lang === 'th' ? 'ยอดอนุมัติรวม' : 'Total Confirmed' ?></div>
        </div>
    </div>

    <!-- Filter Toolbar -->
    <div class="action-toolbar">
        <form method="get" style="display:flex; gap:10px; flex-wrap:wrap; align-items:end; width:100%;">
            <input type="hidden" name="page" value="slip_review">
            
            <div style="flex:1; min-width:180px;">
                <input type="text" name="search" class="form-control" 
                       placeholder="<?= $lang === 'th' ? 'ค้นหา Invoice ID, Ref...' : 'Search Invoice, Ref...' ?>" 
                       value="<?= htmlspecialchars($search) ?>"
                       style="border-radius:8px; border:2px solid var(--md-border, #e2e8f0); padding:8px 12px;">
            </div>
            
            <div>
                <select name="status" class="form-control" style="border-radius:8px; border:2px solid var(--md-border, #e2e8f0); padding:8px 12px; min-width:160px;">
                    <option value=""><?= $lang === 'th' ? '— ทุกสถานะ —' : '— All Status —' ?></option>
                    <option value="pending_review" <?= $status === 'pending_review' ? 'selected' : '' ?>><?= $lang === 'th' ? 'รอตรวจสอบ' : 'Pending Review' ?></option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>><?= $lang === 'th' ? 'รอดำเนินการ' : 'Pending' ?></option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>><?= $lang === 'th' ? 'อนุมัติแล้ว' : 'Approved' ?></option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>><?= $lang === 'th' ? 'ปฏิเสธ' : 'Rejected' ?></option>
                </select>
            </div>
            
            <div>
                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>" 
                       style="border-radius:8px; border:2px solid var(--md-border, #e2e8f0); padding:8px 12px;"
                       placeholder="<?= $lang === 'th' ? 'จากวันที่' : 'From' ?>">
            </div>
            
            <div>
                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($dateTo) ?>"
                       style="border-radius:8px; border:2px solid var(--md-border, #e2e8f0); padding:8px 12px;"
                       placeholder="<?= $lang === 'th' ? 'ถึงวันที่' : 'To' ?>">
            </div>
            
            <button type="submit" class="btn btn-primary" style="border-radius:8px; padding:8px 18px; font-weight:600;">
                <i class="fa fa-search"></i> <?= $lang === 'th' ? 'ค้นหา' : 'Search' ?>
            </button>
            
            <a href="index.php?page=slip_review" class="btn btn-outline-secondary" style="border-radius:8px; padding:8px 18px; font-weight:600;">
                <i class="fa fa-refresh"></i> <?= $lang === 'th' ? 'ล้าง' : 'Clear' ?>
            </a>
        </form>
    </div>

    <!-- Results Table -->
    <div class="master-data-table">
        <div style="display:flex; justify-content:space-between; align-items:center; padding:12px 0;">
            <span style="font-size:13px; color:var(--md-text-secondary, #64748b); font-weight:500;">
                <?= $total ?> <?= $lang === 'th' ? 'รายการ' : 'records' ?>
                <?php if ($status): ?> 
                    — <span class="status-badge <?= $status ?>"><?= $statusLabels[$status] ?? $status ?></span>
                <?php endif; ?>
            </span>
        </div>

        <?php if (empty($payments)): ?>
        <div class="empty-state">
            <i class="fa fa-inbox"></i>
            <h4><?= $lang === 'th' ? 'ไม่พบรายการ' : 'No Records Found' ?></h4>
            <p><?= $lang === 'th' ? 'ยังไม่มีรายการชำระเงินผ่าน PromptPay' : 'No PromptPay payment records yet' ?></p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width:70px;"><?= $lang === 'th' ? 'สลิป' : 'Slip' ?></th>
                    <th><?= $lang === 'th' ? 'ใบแจ้งหนี้' : 'Invoice' ?></th>
                    <th style="text-align:right;"><?= $lang === 'th' ? 'จำนวนเงิน' : 'Amount' ?></th>
                    <th><?= $lang === 'th' ? 'อ้างอิง' : 'Reference' ?></th>
                    <th style="text-align:center;"><?= $lang === 'th' ? 'สถานะ' : 'Status' ?></th>
                    <th><?= $lang === 'th' ? 'วันที่' : 'Date' ?></th>
                    <th style="text-align:center; width:130px;"><?= $lang === 'th' ? 'จัดการ' : 'Actions' ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($payments as $pay): 
                    $isPending = in_array($pay['status'], ['pending', 'pending_review']);
                    $hasSlip = !empty($pay['slip_image']);
                    $invoiceInfo = $pay['invoice_info'] ?? null;
                ?>
                <tr style="<?= $isPending ? 'background: rgba(251, 191, 36, 0.04);' : '' ?>">
                    <!-- Slip Thumbnail -->
                    <td>
                        <?php if ($hasSlip): ?>
                        <img src="<?= htmlspecialchars($pay['slip_image']) ?>" 
                             alt="Slip" class="slip-thumb"
                             onclick="openSlipLightbox('<?= htmlspecialchars($pay['slip_image']) ?>')"
                             title="<?= $lang === 'th' ? 'คลิกเพื่อดูขนาดเต็ม' : 'Click to view full size' ?>">
                        <?php else: ?>
                        <div class="slip-placeholder" title="<?= $lang === 'th' ? 'ยังไม่ได้อัพโหลดสลิป' : 'No slip uploaded' ?>">
                            <i class="fa fa-image"></i>
                        </div>
                        <?php endif; ?>
                    </td>

                    <!-- Invoice Info -->
                    <td>
                        <?php if ($invoiceInfo): ?>
                        <a href="index.php?page=inv_view&id=<?= $pay['invoice_id'] ?>" class="invoice-link">
                            <i class="fa fa-file-text-o"></i> INV-<?= $pay['invoice_id'] ?>
                        </a>
                        <div class="customer-name">
                            <?= htmlspecialchars($invoiceInfo['customer_name'] ?? '-') ?>
                        </div>
                        <?php else: ?>
                        <span class="ref-cell"><?= htmlspecialchars($pay['order_id']) ?></span>
                        <?php endif; ?>
                    </td>

                    <!-- Amount -->
                    <td style="text-align:right;">
                        <span class="amount-cell">฿<?= number_format(floatval($pay['amount']), 2) ?></span>
                        <div style="font-size:11px; color:var(--md-text-muted, #94a3b8);"><?= $pay['currency'] ?? 'THB' ?></div>
                    </td>

                    <!-- Reference -->
                    <td>
                        <span class="ref-cell"><?= htmlspecialchars($pay['reference_id'] ?? '-') ?></span>
                    </td>

                    <!-- Status -->
                    <td style="text-align:center;">
                        <?php
                            $st = $pay['status'];
                            $stIcon = match($st) {
                                'pending' => 'fa-clock-o',
                                'pending_review' => 'fa-eye',
                                'completed' => 'fa-check-circle',
                                'rejected' => 'fa-times-circle',
                                default => 'fa-question-circle',
                            };
                        ?>
                        <span class="status-badge <?= $st ?>">
                            <i class="fa <?= $stIcon ?>"></i>
                            <?= $statusLabels[$st] ?? $st ?>
                        </span>
                    </td>

                    <!-- Date -->
                    <td>
                        <div class="time-cell">
                            <div class="date"><?= date('d M Y', strtotime($pay['created_at'])) ?></div>
                            <div><?= date('H:i', strtotime($pay['created_at'])) ?></div>
                        </div>
                    </td>

                    <!-- Actions -->
                    <td style="text-align:center;">
                        <div class="action-btn-group">
                            <?php if ($hasSlip): ?>
                            <button type="button" class="btn-view-slip" 
                                    onclick="openSlipLightbox('<?= htmlspecialchars($pay['slip_image']) ?>')"
                                    title="<?= $lang === 'th' ? 'ดูสลิป' : 'View Slip' ?>">
                                <i class="fa fa-eye"></i>
                            </button>
                            <?php endif; ?>

                            <?php if ($isPending): ?>
                            <!-- Approve -->
                            <form method="post" action="index.php?page=slip_review_approve" style="display:inline;">
                                <input type="hidden" name="id" value="<?= $pay['id'] ?>">
                                <button type="submit" class="btn-approve" 
                                        onclick="return confirm('<?= $lang === 'th' ? 'อนุมัติการชำระเงิน #' . $pay['id'] . '?' : 'Approve payment #' . $pay['id'] . '?' ?>')"
                                        title="<?= $lang === 'th' ? 'อนุมัติ' : 'Approve' ?>">
                                    <i class="fa fa-check"></i>
                                </button>
                            </form>

                            <!-- Reject -->
                            <button type="button" class="btn-reject"
                                    onclick="openRejectModal(<?= $pay['id'] ?>)"
                                    title="<?= $lang === 'th' ? 'ปฏิเสธ' : 'Reject' ?>">
                                <i class="fa fa-times"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div style="display:flex; justify-content:center; padding:20px 0; gap:4px;">
            <?php for ($i = 1; $i <= $totalPages; $i++): 
                $params = $_GET;
                $params['p'] = $i;
                $qs = http_build_query($params);
            ?>
            <a href="index.php?<?= $qs ?>" 
               style="width:36px; height:36px; border-radius:8px; display:inline-flex; align-items:center; justify-content:center; 
                      font-weight:600; font-size:13px; text-decoration:none; transition:all 0.2s;
                      <?= $i === $page ? 'background:var(--md-primary, #4f46e5); color:white;' : 'background:white; color:var(--md-text-secondary, #64748b); border:1px solid var(--md-border, #e2e8f0);' ?>">
                <?= $i ?>
            </a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>

<!-- Slip Lightbox -->
<div class="slip-lightbox" id="slipLightbox" onclick="closeSlipLightbox()">
    <div class="close-btn" onclick="closeSlipLightbox()"><i class="fa fa-times"></i></div>
    <img id="slipLightboxImg" src="" alt="Payment Slip" onclick="event.stopPropagation()">
</div>

<!-- Reject Reason Modal -->
<div class="reject-modal-overlay" id="rejectOverlay">
    <div class="reject-modal" onclick="event.stopPropagation()">
        <h4><i class="fa fa-times-circle"></i> <?= $lang === 'th' ? 'ปฏิเสธการชำระเงิน' : 'Reject Payment' ?></h4>
        <form method="post" action="index.php?page=slip_review_reject" id="rejectForm">
            <input type="hidden" name="id" id="rejectPaymentId" value="">
            <div style="margin-bottom:12px;">
                <label style="font-weight:600; font-size:13px; color:var(--md-text-secondary, #64748b); display:block; margin-bottom:6px;">
                    <i class="fa fa-comment"></i> <?= $lang === 'th' ? 'เหตุผล (ถ้ามี)' : 'Reason (optional)' ?>
                </label>
                <textarea name="reason" placeholder="<?= $lang === 'th' ? 'ระบุเหตุผลที่ปฏิเสธ...' : 'Enter rejection reason...' ?>"></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-cancel-modal" onclick="closeRejectModal()">
                    <?= $lang === 'th' ? 'ยกเลิก' : 'Cancel' ?>
                </button>
                <button type="submit" class="btn-confirm-reject">
                    <i class="fa fa-times"></i> <?= $lang === 'th' ? 'ยืนยันปฏิเสธ' : 'Confirm Reject' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Slip Lightbox
function openSlipLightbox(src) {
    document.getElementById('slipLightboxImg').src = src;
    document.getElementById('slipLightbox').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeSlipLightbox() {
    document.getElementById('slipLightbox').classList.remove('active');
    document.body.style.overflow = '';
}

// Reject Modal
function openRejectModal(paymentId) {
    document.getElementById('rejectPaymentId').value = paymentId;
    document.getElementById('rejectOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeRejectModal() {
    document.getElementById('rejectOverlay').classList.remove('active');
    document.body.style.overflow = '';
}

// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeSlipLightbox();
        closeRejectModal();
    }
});

// Click outside reject modal to close
document.getElementById('rejectOverlay').addEventListener('click', function(e) {
    if (e.target === this) closeRejectModal();
});
</script>
