<?php
/**
 * Tour Booking — Create / Edit Form
 *
 * Variables: $booking (null = create), $agents, $customers, $locations, $message
 */

$isThai    = ($_SESSION['lang'] ?? '0') === '1';
$isEdit    = !empty($booking);

$loggedInDisplay = trim($_SESSION['user_name'] ?? '') ?: ($_SESSION['user_email'] ?? '');
$bookingByValue  = $isEdit ? ($booking['booking_by'] ?? $loggedInDisplay) : $loggedInDisplay;

$pageTitle = $isEdit
    ? ($isThai ? 'แก้ไขการจอง' : 'Edit Booking') . ' — ' . htmlspecialchars($booking['booking_number'] ?? '')
    : ($isThai ? 'สร้างการจองใหม่' : 'New Booking');

$statusOptions = [
    'draft'     => $isThai ? 'ฉบับร่าง'   : 'Draft',
    'confirmed' => $isThai ? 'ยืนยัน'     : 'Confirmed',
    'paid'      => $isThai ? 'ชำระแล้ว'   : 'Paid',
    'completed' => $isThai ? 'เสร็จสิ้น'  : 'Completed',
    'no_show'   => $isThai ? 'ไม่มาตามนัด' : 'No Show',
    'cancelled' => $isThai ? 'ยกเลิก'     : 'Cancelled',
];

$itemTypes = [
    'tour'     => $isThai ? 'ทัวร์' : 'Tour',
    'transfer' => $isThai ? 'รถรับส่ง' : 'Transfer',
    'entrance' => $isThai ? 'ค่าเข้าชม' : 'Entrance',
    'extra'    => $isThai ? 'อื่นๆ' : 'Extra',
    'hotel'    => $isThai ? 'โรงแรม' : 'Hotel',
];

$paxTypes = [
    'adult'  => $isThai ? 'ผู้ใหญ่' : 'Adult',
    'child'  => $isThai ? 'เด็ก' : 'Child',
    'infant' => $isThai ? 'ทารก' : 'Infant',
];

$items = $booking['items'] ?? [];
$pax   = $booking['pax'] ?? [];
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
/* ─── Form Layout ──────────────────────────────────────── */
.bk-form { max-width: 1400px; margin: 0 auto; }
.bk-card { background: white; border-radius: 14px; padding: 28px 28px 24px; border: 1px solid #e2e8f0; margin-bottom: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
.bk-card h3 { font-size: 15px; font-weight: 700; margin: 0 0 20px; padding-bottom: 14px; border-bottom: 2px solid #f1f5f9; color: #1e293b; display: flex; align-items: center; gap: 8px; }
.bk-card h3 i { color: #0d9488; font-size: 16px; width: 28px; height: 28px; display: inline-flex; align-items: center; justify-content: center; background: #f0fdfa; border-radius: 8px; }
.bk-card h3 .h3-right { margin-left: auto; display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 500; color: #64748b; }
.bk-card h3 .h3-right select { height: 34px; padding: 4px 32px 4px 10px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 13px; font-family: inherit; color: #1e293b; background: #fff; cursor: pointer; -webkit-appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; transition: border-color 0.2s; }
.bk-card h3 .h3-right select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,0.12); }
.bk-row { display: grid; gap: 18px; margin-bottom: 18px; }
.bk-row:last-child { margin-bottom: 0; }
.bk-row.cols-2 { grid-template-columns: 1fr 1fr; }
.bk-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
.bk-row.cols-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }

/* ─── Field & Labels ───────────────────────────────────── */
.bk-field { position: relative; }
.bk-field label { display: block; font-size: 12.5px; font-weight: 600; color: #374151; margin-bottom: 6px; letter-spacing: 0.01em; }
.bk-field label .req { color: #ef4444; margin-left: 2px; }

/* ─── Input / Select / Textarea ────────────────────────── */
.bk-field input,
.bk-field select,
.bk-field textarea {
    width: 100%;
    height: 42px;
    padding: 10px 14px;
    border: 1.5px solid #d1d5db;
    border-radius: 10px;
    font-size: 14px;
    font-family: inherit;
    color: #1e293b;
    background: #fff;
    transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
    box-sizing: border-box;
    -webkit-appearance: none;
}
.bk-field select {
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='7' fill='none'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 14px center;
    padding-right: 36px;
    cursor: pointer;
}
.bk-field input:hover, .bk-field select:hover, .bk-field textarea:hover {
    border-color: #a7b4c0;
}
.bk-field input:focus, .bk-field select:focus, .bk-field textarea:focus {
    outline: none;
    border-color: #0d9488;
    box-shadow: 0 0 0 3px rgba(13,148,136,0.12);
    background: #fafffe;
}
.bk-field input::placeholder { color: #9ca3af; font-weight: 400; }
.bk-field input[type="number"] { font-variant-numeric: tabular-nums; }
.bk-field textarea { resize: vertical; min-height: 80px; height: auto; }

/* Icon-prefixed inputs */
.bk-input-icon { position: relative; }
.bk-input-icon i { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: 14px; pointer-events: none; z-index: 1; transition: color 0.2s; }
.bk-input-icon input,
.bk-input-icon select { padding-left: 38px; }
.bk-input-icon:focus-within i { color: #0d9488; }

/* ─── Items Table ──────────────────────────────────────── */
.dyn-table { width: 100%; border-collapse: separate; border-spacing: 0; }
.dyn-table thead { position: sticky; top: 0; }
.dyn-table th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; color: #64748b; text-align: left; padding: 10px 10px; background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
.dyn-table th:first-child { border-radius: 8px 0 0 0; }
.dyn-table th:last-child { border-radius: 0 8px 0 0; }
.dyn-table td { padding: 8px 6px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }
.dyn-table tr:last-child td { border-bottom: none; }
.dyn-table tr:hover td { background: #fafffe; }
.dyn-table input, .dyn-table select {
    width: 100%;
    height: 38px;
    padding: 8px 10px;
    border: 1.5px solid #e2e8f0;
    border-radius: 8px;
    font-size: 13px;
    font-family: inherit;
    color: #1e293b;
    background: #fff;
    transition: border-color 0.2s, box-shadow 0.2s;
    box-sizing: border-box;
}
.dyn-table input:focus, .dyn-table select:focus {
    outline: none;
    border-color: #0d9488;
    box-shadow: 0 0 0 3px rgba(13,148,136,0.12);
}
.dyn-table select {
    -webkit-appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    padding-right: 28px;
    cursor: pointer;
}
.dyn-table .num-input { text-align: right; font-variant-numeric: tabular-nums; }

.btn-add-row { display: inline-flex; align-items: center; gap: 6px; padding: 10px 20px; background: #f0fdfa; color: #0d9488; border: 1.5px dashed #0d9488; border-radius: 10px; font-size: 13px; font-weight: 600; cursor: pointer; margin-top: 12px; transition: all 0.2s; }
.btn-add-row:hover { background: #0d9488; color: white; border-style: solid; transform: translateY(-1px); box-shadow: 0 2px 8px rgba(13,148,136,0.25); }
.btn-rm-row { width: 32px; height: 32px; border-radius: 8px; border: 1.5px solid #fecaca; background: #fff5f5; color: #ef4444; cursor: pointer; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; }
.btn-rm-row:hover { background: #ef4444; color: white; border-color: #ef4444; transform: scale(1.05); }

/* ─── Totals ───────────────────────────────────────────── */
.totals-grid { display: grid; grid-template-columns: 1fr 200px; gap: 8px; max-width: 420px; margin-left: auto; margin-top: 20px; font-size: 14px; }
.totals-grid .label { color: #64748b; text-align: right; padding: 8px 0; font-weight: 500; }
.totals-grid .value input { text-align: right; height: 38px; padding: 8px 12px; border: 1.5px solid #d1d5db; border-radius: 8px; font-size: 14px; font-variant-numeric: tabular-nums; transition: border-color 0.2s, box-shadow 0.2s; }
.totals-grid .value input:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,0.12); }
.totals-grid .grand { font-weight: 700; font-size: 17px; color: #0d9488; }

/* ─── Action Buttons ───────────────────────────────────── */
.form-actions { display: flex; gap: 12px; justify-content: flex-end; margin-top: 28px; padding-top: 20px; border-top: 2px solid #f1f5f9; }
.btn-save { padding: 12px 32px; background: linear-gradient(135deg, #0d9488, #0f766e); color: white; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: 0 2px 8px rgba(13,148,136,0.25); }
.btn-save:hover { background: linear-gradient(135deg, #0f766e, #115e59); transform: translateY(-1px); box-shadow: 0 4px 14px rgba(13,148,136,0.35); }
.btn-cancel { padding: 12px 28px; background: white; color: #64748b; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 15px; font-weight: 500; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; transition: all 0.2s; }
.btn-cancel:hover { background: #f8fafc; border-color: #cbd5e1; color: #475569; }

/* ─── Responsive ───────────────────────────────────────── */
@media (max-width: 768px) {
    .bk-card { padding: 20px 16px 16px; }
    .bk-row.cols-2, .bk-row.cols-3, .bk-row.cols-4 { grid-template-columns: 1fr; }
    .dyn-table { font-size: 12px; }
    .dyn-table th, .dyn-table td { padding: 8px 6px; }
    .totals-grid { max-width: 100%; }
    .form-actions { flex-direction: column-reverse; }
    .form-actions .btn-save, .form-actions .btn-cancel { width: 100%; justify-content: center; }
}

/* ─── Customer Autocomplete ────────────────────────────── */
.ac-wrap { position: relative; }
.ac-wrap input[type="text"] { width: 100%; }
.ac-list { position: absolute; top: calc(100% + 2px); left: 0; right: 0; z-index: 100; background: white; border: 1.5px solid #e2e8f0; border-radius: 10px; max-height: 260px; overflow-y: auto; box-shadow: 0 10px 30px rgba(0,0,0,0.12); display: none; }
.ac-list.show { display: block; }
.ac-item { padding: 10px 14px; font-size: 13px; cursor: pointer; border-bottom: 1px solid #f8fafc; transition: background 0.15s; }
.ac-item:last-child { border-bottom: none; }
.ac-item:hover, .ac-item.active { background: #f0fdfa; color: #0d9488; }
.ac-item .sub { font-size: 11px; color: #94a3b8; display: block; margin-top: 2px; }
.ac-item.create-new { color: #0d9488; font-weight: 600; border-top: 2px solid #e2e8f0; background: #f8fffe; }
.ac-item.create-new:hover { background: #e6f7f5; }
.ac-item.create-new i { margin-right: 4px; }
.ac-selected { display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; background: #f0fdfa; border: 1.5px solid #99f6e4; border-radius: 10px; font-size: 14px; color: #0d9488; font-weight: 600; height: 42px; box-sizing: border-box; }
.ac-selected .ac-clear { cursor: pointer; color: #ef4444; font-size: 16px; margin-left: 4px; transition: transform 0.15s; }
.ac-selected .ac-clear:hover { transform: scale(1.2); }

/* ─── Quick Create Modal ──────────────────────────────── */
.qc-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); backdrop-filter: blur(2px); z-index: 10050; }
.qc-overlay.active { display: flex; align-items: center; justify-content: center; }
.qc-modal { background: white; border-radius: 16px; padding: 32px; width: 600px; max-width: 92vw; box-shadow: 0 20px 50px rgba(0,0,0,0.15); }
.qc-modal h4 { margin: 0 0 20px; font-size: 17px; color: #1e293b; font-weight: 700; display: flex; align-items: center; gap: 8px; }
.qc-modal .bk-field { margin-bottom: 14px; }
.qc-modal .qc-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.qc-modal .qc-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px; }
.qc-modal .qc-btn { padding: 10px 24px; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; }
.qc-modal .qc-btn-save { background: #0d9488; color: white; box-shadow: 0 2px 8px rgba(13,148,136,0.25); }
.qc-modal .qc-btn-save:hover { background: #0f766e; }
.qc-modal .qc-btn-cancel { background: #f1f5f9; color: #64748b; }
.qc-modal .qc-btn-cancel:hover { background: #e2e8f0; }

/* ─── Pax Info Bar ─────────────────────────────────────── */
.pax-info-bar { display: flex; align-items: center; gap: 10px; padding: 10px 16px; background: linear-gradient(135deg, #f0fdfa, #e6fffa); border: 1.5px solid #99f6e4; border-radius: 10px; font-size: 13px; color: #0d9488; margin-bottom: 14px; }
.pax-info-bar i { font-size: 15px; }
.pax-info-bar strong { font-size: 18px; font-weight: 800; margin: 0 2px; }

/* ─── Item Cards ───────────────────────────────────────── */
.item-card { background: #fff; border: 1.5px solid #e2e8f0; border-radius: 12px; margin-bottom: 14px; overflow: hidden; transition: border-color 0.2s, box-shadow 0.2s; }
.item-card:hover { border-color: #cbd5e1; box-shadow: 0 2px 8px rgba(0,0,0,0.04); }
.item-card-header { display: flex; align-items: center; gap: 10px; padding: 10px 16px; background: #f8fafc; border-bottom: 1px solid #f1f5f9; }
.item-card-num { width: 26px; height: 26px; display: inline-flex; align-items: center; justify-content: center; background: #0d9488; color: white; border-radius: 8px; font-size: 12px; font-weight: 700; flex-shrink: 0; }
.item-card-title { font-size: 13px; font-weight: 600; color: #475569; flex: 1; }
.item-card-body { padding: 16px; }
.item-row-grid { display: grid; gap: 14px; margin-bottom: 14px; }
.item-row-grid.cols-1 { grid-template-columns: 1fr; }
.item-row-grid.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
.item-card-body .bk-field input,
.item-card-body .bk-field select { height: 38px; padding: 8px 12px; font-size: 13px; border-radius: 8px; }

/* ─── Pax Lines ────────────────────────────────────────── */
.pax-lines-wrap { background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 10px; overflow: hidden; }
.pax-lines-header { display: grid; grid-template-columns: 100px 110px 70px 100px 100px 36px; background: linear-gradient(135deg, #f1f5f9, #e8ecf1); border-bottom: 1.5px solid #e2e8f0; }
.pax-lines-header > div { padding: 8px 6px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.03em; color: #64748b; text-align: center; }
.pax-lines-header > div:first-child { text-align: left; padding-left: 10px; }
.pax-line { display: grid; grid-template-columns: 100px 110px 70px 100px 100px 36px; border-bottom: 1px solid #f1f5f9; align-items: center; }
.pax-line:last-child { border-bottom: none; }
.pax-line > div { padding: 5px 4px; }
.pax-line > div:first-child { padding-left: 6px; }
.pax-line select, .pax-line input { width: 100%; height: 32px; padding: 4px 6px; border: 1.5px solid #d1d5db; border-radius: 7px; font-size: 12px; font-family: inherit; color: #1e293b; background: #fff; transition: border-color 0.2s, box-shadow 0.2s; box-sizing: border-box; }
.pax-line select { -webkit-appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6' fill='none'%3E%3Cpath d='M1 1l4 4 4-4' stroke='%236b7280' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 6px center; padding-right: 22px; cursor: pointer; }
.pax-line input:focus, .pax-line select:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 2px rgba(13,148,136,0.12); }
.pax-line .num-input { text-align: right; font-variant-numeric: tabular-nums; }
.pax-line .pl-total-input { background: #e6fffa; border-color: #99f6e4; font-weight: 600; color: #0d9488; }
.pax-line .btn-rm-row { width: 26px; height: 26px; font-size: 10px; border-radius: 6px; }
.pax-lines-footer { display: flex; align-items: center; justify-content: space-between; padding: 8px 10px; border-top: 1.5px solid #e2e8f0; background: #f0fdfa; }
.btn-add-pax { display: inline-flex; align-items: center; gap: 4px; padding: 5px 12px; background: transparent; color: #0d9488; border: 1px dashed #0d9488; border-radius: 7px; font-size: 11px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-add-pax:hover { background: #0d9488; color: white; border-style: solid; }
.pax-item-total { font-size: 13px; font-weight: 700; color: #0d9488; }
.pax-item-total span { font-size: 15px; }
@media (max-width: 768px) {
    .item-row-grid.cols-3 { grid-template-columns: 1fr; }
    .pax-lines-header, .pax-line { grid-template-columns: 80px 90px 55px 80px 80px 32px; }
    .pax-line select, .pax-line input { font-size: 11px; height: 30px; }
}
</style>

<div class="master-data-container">
    <!-- Header -->
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-calendar-check-o"></i> <?= $pageTitle ?></h2>
                <p><?= $isThai ? 'กรอกข้อมูลการจอง, รายการ, และผู้เดินทาง' : 'Fill in booking details, items, and passengers' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=tour_booking_list" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back' ?>
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($message)): ?>
    <div style="background:#fef2f2; border-left:4px solid #ef4444; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        ⚠️ <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>

    <form method="post" action="index.php?page=tour_booking_store" id="bookingForm">
        <?= csrf_field() ?>
        <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= $booking['id'] ?>">
        <?php endif; ?>

        <div class="bk-form">

            <!-- Section 1: Booking Info -->
            <div class="bk-card">
                <h3><i class="fa fa-info-circle"></i> <?= $isThai ? 'ข้อมูลการจอง' : 'Booking Info' ?></h3>

                <div class="bk-row cols-3">
                    <div class="bk-field">
                        <label><?= $isThai ? 'วันที่จอง' : 'Booking Date' ?> <span class="req">*</span></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-calendar"></i>
                            <input type="date" name="booking_date" value="<?= htmlspecialchars($booking['booking_date'] ?? date('Y-m-d')) ?>" required>
                        </div>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'วันเดินทาง' : 'Trip Date' ?> <span class="req">*</span></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-plane"></i>
                            <input type="date" name="travel_date" id="travelDateInput" value="<?= htmlspecialchars($booking['travel_date'] ?? date('Y-m-d')) ?>" required>
                        </div>
                        <div id="allotmentBadge" style="margin-top:4px;font-size:12px;font-weight:600;display:none;"></div>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'เลข Voucher' : 'Voucher Number' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-ticket"></i>
                            <input type="text" name="voucher_number" value="<?= htmlspecialchars($booking['voucher_number'] ?? '') ?>" placeholder="<?= $isThai ? 'จะสร้างอัตโนมัติ' : 'Auto-generated' ?>">
                        </div>
                    </div>
                </div>

                <div class="bk-row cols-3">
                    <div class="bk-field">
                        <label><?= $isThai ? 'ผู้ใหญ่' : 'Adults' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-user"></i>
                            <input type="number" name="pax_adult" min="0" value="<?= intval($booking['pax_adult'] ?? 0) ?>" onchange="syncPaxQty()">
                        </div>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'เด็ก' : 'Children' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-child"></i>
                            <input type="number" name="pax_child" min="0" value="<?= intval($booking['pax_child'] ?? 0) ?>" onchange="syncPaxQty()">
                        </div>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'ทารก' : 'Infants' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-heart"></i>
                            <input type="number" name="pax_infant" min="0" value="<?= intval($booking['pax_infant'] ?? 0) ?>" onchange="syncPaxQty()">
                        </div>
                    </div>
                </div>

                <div class="bk-row cols-3">
                    <div class="bk-field">
                        <label><?= $isThai ? 'สกุลเงิน' : 'Currency' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-money"></i>
                            <select name="currency">
                                <option value="THB" <?= ($booking['currency'] ?? 'THB') === 'THB' ? 'selected' : '' ?>>THB ฿</option>
                                <option value="USD" <?= ($booking['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD $</option>
                                <option value="EUR" <?= ($booking['currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR €</option>
                            </select>
                        </div>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'สถานะ' : 'Status' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-flag"></i>
                            <select name="status">
                                <?php foreach ($statusOptions as $key => $label): ?>
                                <option value="<?= $key ?>" <?= ($booking['status'] ?? 'draft') === $key ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'ผู้บันทึก' : 'Booking By' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-user-circle-o"></i>
                            <input type="text" name="booking_by" value="<?= htmlspecialchars($bookingByValue) ?>" readonly style="background:#f1f5f9;cursor:default;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 2: Agent -->
            <div class="bk-card">
                <h3><i class="fa fa-building-o"></i> <?= $isThai ? 'ข้อมูลตัวแทน' : 'Agent Info' ?></h3>
                <?php
                $agentId = intval($booking['agent_id'] ?? 0);
                $agentName = '';
                if ($agentId > 0) {
                    foreach ($agents as $a) {
                        if (intval($a['id']) === $agentId) { $agentName = $a['name_en']; break; }
                    }
                }
                ?>
                <!-- Smart search: find existing agent or create new -->
                <input type="hidden" name="agent_id" id="agent_id" value="<?= $agentId ?>">
                <div class="bk-row cols-1">
                    <div class="bk-field">
                        <label><?= $isThai ? 'ค้นหาตัวแทน' : 'Search Agent' ?></label>
                        <div class="ac-wrap" id="agentAcWrap">
                            <?php if ($agentId > 0 && $agentName): ?>
                            <div class="ac-selected" id="agentSelected">
                                <?= htmlspecialchars($agentName) ?>
                                <span class="ac-clear" onclick="clearAgent()">&times;</span>
                            </div>
                            <input type="text" id="agentSearch" placeholder="<?= $isThai ? 'พิมพ์ชื่อตัวแทน...' : 'Type agent name...' ?>" style="display:none;" autocomplete="off">
                            <?php else: ?>
                            <div class="ac-selected" id="agentSelected" style="display:none;"></div>
                            <input type="text" id="agentSearch" placeholder="<?= $isThai ? 'พิมพ์ชื่อตัวแทน... (ไม่พบ = ไม่มีตัวแทน)' : 'Type agent name... (leave blank = no agent)' ?>" autocomplete="off">
                            <?php endif; ?>
                            <div class="ac-list" id="agentAcList"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Customer Info -->
            <div class="bk-card">
                <h3><i class="fa fa-user-circle"></i> <?= $isThai ? 'ข้อมูลลูกค้า' : 'Customer Info' ?></h3>
                <?php
                $contact = $booking['contact'] ?? null;
                $preselectedName = '';
                $preselectedEmail = '';
                $preselectedPhone = '';
                if (!empty($booking['customer_id'])) {
                    foreach ($customers as $c) {
                        if (intval($c['id']) === intval($booking['customer_id'])) {
                            $preselectedName = $c['name_en'];
                            $preselectedEmail = $c['email'] ?? '';
                            $preselectedPhone = $c['phone'] ?? '';
                            break;
                        }
                    }
                }
                // Per-booking contact overrides (from tour_booking_contacts)
                $bkName      = $contact['contact_name'] ?? $preselectedName;
                $bkEmail     = $contact['email'] ?? $preselectedEmail;
                $bkMobile    = $contact['mobile'] ?? $preselectedPhone;
                $bkGender    = $contact['gender'] ?? '';
                $bkNational  = $contact['nationality'] ?? '';
                $bkMessengers = $contact['contact_messengers'] ?? '';
                ?>
                <!-- Smart search: find existing customer or create new -->
                <input type="hidden" name="customer_id" id="customer_id" value="<?= intval($booking['customer_id'] ?? 0) ?>">
                <div class="bk-row cols-1" style="margin-bottom:4px;">
                    <div class="bk-field">
                        <label><?= $isThai ? 'ค้นหาลูกค้า' : 'Search Customer' ?></label>
                        <div class="ac-wrap" id="customerAcWrap">
                            <?php if (!empty($preselectedName)): ?>
                            <div class="ac-selected" id="customerSelected">
                                <?= htmlspecialchars($preselectedName) ?>
                                <span class="ac-clear" onclick="clearCustomer()">&times;</span>
                            </div>
                            <input type="text" id="customerSearch" placeholder="<?= $isThai ? 'พิมพ์ชื่อลูกค้า...' : 'Type customer name...' ?>" style="display:none;" autocomplete="off">
                            <?php else: ?>
                            <div class="ac-selected" id="customerSelected" style="display:none;"></div>
                            <input type="text" id="customerSearch" placeholder="<?= $isThai ? 'พิมพ์ชื่อลูกค้า... (ไม่พบ = สร้างใหม่)' : 'Type customer name... (not found = create new)' ?>" autocomplete="off">
                            <?php endif; ?>
                            <div class="ac-list" id="customerAcList"></div>
                        </div>
                    </div>
                </div>

                <!-- Row 1: Customer Name | Gender | Nationality -->
                <div class="bk-row cols-3">
                    <div class="bk-field">
                        <label><?= $isThai ? 'ชื่อลูกค้า' : 'Customer Name' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-user"></i>
                            <input type="text" name="contact_name" id="cusName" value="<?= htmlspecialchars($bkName) ?>" placeholder="<?= $isThai ? 'ชื่อลูกค้า' : 'Customer name' ?>">
                        </div>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'เพศ' : 'Gender' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-venus-mars"></i>
                            <select name="contact_gender" id="cusGender">
                                <option value=""><?= $isThai ? '-- ไม่ระบุ --' : '-- Not specified --' ?></option>
                                <option value="male" <?= $bkGender === 'male' ? 'selected' : '' ?>><?= $isThai ? 'ชาย' : 'Male' ?></option>
                                <option value="female" <?= $bkGender === 'female' ? 'selected' : '' ?>><?= $isThai ? 'หญิง' : 'Female' ?></option>
                                <option value="other" <?= $bkGender === 'other' ? 'selected' : '' ?>><?= $isThai ? 'อื่นๆ' : 'Other' ?></option>
                            </select>
                        </div>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'สัญชาติ' : 'Nationality' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-globe"></i>
                            <input type="text" name="contact_nationality" id="cusNationality" value="<?= htmlspecialchars($bkNational) ?>" placeholder="<?= $isThai ? 'เช่น Thai, American' : 'e.g. Thai, American' ?>">
                        </div>
                    </div>
                </div>

                <!-- Row 2: Email | Mobile | Messengers -->
                <div class="bk-row cols-3">
                    <div class="bk-field">
                        <label><?= $isThai ? 'อีเมล' : 'Email' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-envelope-o"></i>
                            <input type="text" name="contact_email" id="cusEmail" value="<?= htmlspecialchars($bkEmail) ?>" placeholder="<?= $isThai ? 'อีเมล' : 'Email' ?>">
                        </div>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'เบอร์โทร' : 'Mobile' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-mobile" style="font-size:18px;"></i>
                            <input type="text" name="contact_mobile" id="cusPhone" value="<?= htmlspecialchars($bkMobile) ?>" placeholder="<?= $isThai ? 'เบอร์โทร' : 'Phone number' ?>">
                        </div>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'ไลน์ / WhatsApp / อื่นๆ' : 'Messengers (Line/WhatsApp/etc)' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-comments-o"></i>
                            <input type="text" name="contact_messengers" id="cusMessengers" value="<?= htmlspecialchars($bkMessengers) ?>" placeholder="<?= $isThai ? 'Line: @user, WhatsApp: +668xxx' : 'Line: @user, WhatsApp: +668xxx' ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 4: Sales Rep Info -->
            <div class="bk-card">
                <h3><i class="fa fa-handshake-o"></i> <?= $isThai ? 'ข้อมูลพนักงานขาย' : 'Sales Rep Info' ?></h3>
                <?php
                $salesRepId = intval($booking['sales_rep_id'] ?? 0);
                $salesRepName = '';
                $salesRepEmail = '';
                $salesRepPhone = '';
                $salesRepMessengers = '';
                if ($salesRepId > 0) {
                    foreach ($agents as $a) {
                        if (intval($a['id']) === $salesRepId) {
                            $salesRepName = $a['name_en'];
                            $salesRepEmail = $a['contact_email'] ?? '';
                            $salesRepPhone = $a['contact_mobile'] ?? '';
                            $salesRepMessengers = trim(($a['contact_line'] ?? '') . ', ' . ($a['contact_whatsapp'] ?? ''), ', ');
                            break;
                        }
                    }
                }
                ?>
                <!-- Smart search: find existing sales rep or create new -->
                <input type="hidden" name="sales_rep_id" id="sales_rep_id" value="<?= $salesRepId ?>">
                <div class="bk-row cols-1" style="margin-bottom:4px;">
                    <div class="bk-field">
                        <label><?= $isThai ? 'ค้นหาพนักงานขาย' : 'Search Sales Rep' ?></label>
                        <div class="ac-wrap" id="salesRepAcWrap">
                            <?php if ($salesRepId > 0 && $salesRepName): ?>
                            <div class="ac-selected" id="salesRepSelected">
                                <?= htmlspecialchars($salesRepName) ?>
                                <span class="ac-clear" onclick="clearSalesRep()">&times;</span>
                            </div>
                            <input type="text" id="salesRepSearch" placeholder="<?= $isThai ? 'พิมพ์ชื่อพนักงานขาย...' : 'Type sales rep name...' ?>" style="display:none;" autocomplete="off">
                            <?php else: ?>
                            <div class="ac-selected" id="salesRepSelected" style="display:none;"></div>
                            <input type="text" id="salesRepSearch" placeholder="<?= $isThai ? 'พิมพ์ชื่อพนักงานขาย... (ไม่พบ = สร้างใหม่)' : 'Type sales rep name... (not found = create new)' ?>" autocomplete="off">
                            <?php endif; ?>
                            <div class="ac-list" id="salesRepAcList"></div>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Email | Mobile | Messengers (auto-filled, editable) -->
                <div class="bk-row cols-3">
                    <div class="bk-field">
                        <label><?= $isThai ? 'อีเมล' : 'Email' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-envelope-o"></i>
                            <input type="text" name="sales_rep_email" id="salesRepEmail" value="<?= htmlspecialchars($salesRepEmail) ?>" placeholder="<?= $isThai ? 'อีเมล' : 'Email' ?>">
                        </div>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'เบอร์โทร' : 'Mobile' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-mobile" style="font-size:18px;"></i>
                            <input type="text" name="sales_rep_mobile" id="salesRepMobile" value="<?= htmlspecialchars($salesRepPhone) ?>" placeholder="<?= $isThai ? 'เบอร์โทร' : 'Phone' ?>">
                        </div>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'ไลน์ / WhatsApp / อื่นๆ' : 'Messengers (Line/WhatsApp/etc)' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-comments-o"></i>
                            <input type="text" name="sales_rep_messengers" id="salesRepMessengers" value="<?= htmlspecialchars($salesRepMessengers) ?>" placeholder="<?= $isThai ? 'Line: @user, WhatsApp: +668xxx' : 'Line: @user, WhatsApp: +668xxx' ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Pickup -->
            <div class="bk-card">
                <h3><i class="fa fa-car"></i> <?= $isThai ? 'ข้อมูลรับ-ส่ง' : 'Pickup Details' ?></h3>
                <div class="bk-row cols-4">
                    <div class="bk-field">
                        <label><?= $isThai ? 'จุดรับ' : 'Pickup Location' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-map-marker"></i>
                            <select name="pickup_location_id">
                                <option value="0"><?= $isThai ? '-- เลือก --' : '-- Select --' ?></option>
                                <?php foreach ($locations as $loc): ?>
                                <option value="<?= $loc['id'] ?>" <?= intval($booking['pickup_location_id'] ?? 0) === intval($loc['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($loc['name']) ?> (<?= $loc['location_type'] ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'โรงแรม' : 'Hotel' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-bed"></i>
                            <input type="text" name="pickup_hotel" value="<?= htmlspecialchars($booking['pickup_hotel'] ?? '') ?>" placeholder="<?= $isThai ? 'ชื่อโรงแรม' : 'Hotel name' ?>">
                        </div>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'ห้อง' : 'Room' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-key"></i>
                            <input type="text" name="pickup_room" value="<?= htmlspecialchars($booking['pickup_room'] ?? '') ?>" placeholder="<?= $isThai ? 'เลขห้อง' : 'Room no.' ?>">
                        </div>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'เวลารับ' : 'Pickup Time' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-clock-o"></i>
                            <input type="time" name="pickup_time" value="<?= htmlspecialchars($booking['pickup_time'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'คนขับ' : 'Driver' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-id-card-o"></i>
                            <input type="text" name="driver_name" value="<?= htmlspecialchars($booking['driver_name'] ?? '') ?>" placeholder="<?= $isThai ? 'ชื่อคนขับ' : 'Driver name' ?>">
                        </div>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'ทะเบียนรถ' : 'Vehicle No.' ?></label>
                        <div class="bk-input-icon">
                            <i class="fa fa-bus"></i>
                            <input type="text" name="vehicle_no" value="<?= htmlspecialchars($booking['vehicle_no'] ?? '') ?>" placeholder="<?= $isThai ? 'ทะเบียนรถ' : 'Vehicle number' ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section 3: Items -->
            <div class="bk-card">
                <h3><i class="fa fa-list"></i> <?= $isThai ? 'รายการ' : 'Items' ?></h3>

                <div class="pax-info-bar">
                    <i class="fa fa-users"></i>
                    <?= $isThai ? 'จำนวนผู้เดินทาง' : 'Total Passengers' ?>: <strong id="displayPaxCount">0</strong> <?= $isThai ? 'คน' : 'pax' ?>
                </div>

                <div id="itemsContainer">
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $idx => $item): ?>
                        <div class="item-card" data-index="<?= $idx ?>">
                            <div class="item-card-header">
                                <span class="item-card-num"><?= $idx + 1 ?></span>
                                <span class="item-card-title"><?= $isThai ? 'รายการ' : 'Item' ?> <?= $idx + 1 ?></span>
                                <button type="button" class="btn-rm-row" onclick="removeItemCard(this)"><i class="fa fa-times"></i></button>
                            </div>
                            <div class="item-card-body">
                                <!-- Row 1: Product + Model + Type -->
                                <div class="item-row-grid cols-3">
                                    <div class="bk-field">
                                        <label><?= $isThai ? 'สินค้า/ทัวร์' : 'Product / Tour' ?></label>
                                        <select name="item_product_type_id[]" class="product-type-select" data-idx="<?= $idx ?>">
                                            <option value=""><?= $isThai ? '-- เลือกสินค้า --' : '-- Select Product --' ?></option>
                                            <?php foreach ($types as $t): ?>
                                            <option value="<?= $t['id'] ?>" <?= ($item['product_type_id'] ?? 0) == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="bk-field">
                                        <label><?= $isThai ? 'โมเดล/แพ็คเกจ' : 'Model / Package' ?></label>
                                        <select name="item_model_id[]" class="model-select" data-idx="<?= $idx ?>">
                                            <option value="0"><?= $isThai ? '-- ไม่ระบุ --' : '-- None --' ?></option>
                                            <?php
                                            $itemTypeId = intval($item['product_type_id'] ?? 0);
                                            $itemModelId = intval($item['model_id'] ?? 0);
                                            foreach ($models_by_type[$itemTypeId] ?? [] as $m):
                                            ?>
                                            <option value="<?= $m['id'] ?>" <?= $itemModelId === intval($m['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($m['model_name']) ?><?= !empty($m['des']) ? ' — ' . htmlspecialchars($m['des']) : '' ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="bk-field">
                                        <label><?= $isThai ? 'ประเภท' : 'Type' ?></label>
                                        <select name="item_type[]">
                                            <?php foreach ($itemTypes as $k => $l): ?>
                                            <option value="<?= $k ?>" <?= ($item['item_type'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <!-- Row 2: Description -->
                                <div class="item-row-grid cols-1">
                                    <div class="bk-field">
                                        <label><?= $isThai ? 'รายละเอียด' : 'Description' ?></label>
                                        <input type="text" name="item_description[]" value="<?= htmlspecialchars($item['description'] ?? '') ?>" placeholder="<?= $isThai ? 'รายละเอียดเพิ่มเติม...' : 'Additional details...' ?>">
                                    </div>
                                </div>
                                <!-- Row 3: Pax Lines -->
                                <?php
                                $paxLines = [];
                                if (floatval($item['price_thai'] ?? 0) > 0 || intval($item['qty_thai'] ?? 0) > 0) {
                                    $paxLines[] = ['type' => 'adult', 'nat' => 'thai', 'qty' => intval($item['qty_thai'] ?? 0), 'price' => floatval($item['price_thai'] ?? 0)];
                                }
                                if (floatval($item['price_foreigner'] ?? 0) > 0 || intval($item['qty_foreigner'] ?? 0) > 0) {
                                    $paxLines[] = ['type' => 'adult', 'nat' => 'foreigner', 'qty' => intval($item['qty_foreigner'] ?? 0), 'price' => floatval($item['price_foreigner'] ?? 0)];
                                }
                                if (empty($paxLines)) $paxLines[] = ['type' => 'adult', 'nat' => 'thai', 'qty' => 0, 'price' => 0];
                                ?>
                                <div class="pax-lines-wrap">
                                    <div class="pax-lines-header">
                                        <div><?= $isThai ? 'ประเภท' : 'Type' ?></div>
                                        <div><?= $isThai ? 'สัญชาติ' : 'Nationality' ?></div>
                                        <div><?= $isThai ? 'จำนวน' : 'Pax' ?></div>
                                        <div><?= $isThai ? 'ราคา/คน' : 'Price' ?></div>
                                        <div><?= $isThai ? 'รวม' : 'Total' ?></div>
                                        <div></div>
                                    </div>
                                    <div class="pax-lines-body">
                                        <?php foreach ($paxLines as $pl): ?>
                                        <div class="pax-line">
                                            <div><select class="pl-pax-type"><option value="adult" <?= $pl['type']==='adult'?'selected':'' ?>><?= $isThai?'ผู้ใหญ่':'Adult' ?></option><option value="child" <?= $pl['type']==='child'?'selected':'' ?>><?= $isThai?'เด็ก':'Child' ?></option></select></div>
                                            <div><select class="pl-nationality"><option value="thai" <?= $pl['nat']==='thai'?'selected':'' ?>>🇹🇭 <?= $isThai?'ไทย':'Thai' ?></option><option value="foreigner" <?= $pl['nat']==='foreigner'?'selected':'' ?>>🌍 <?= $isThai?'ต่างชาติ':'Foreign' ?></option></select></div>
                                            <div><input type="number" class="num-input pl-qty" min="0" value="<?= $pl['qty'] ?>" onchange="calcPaxLine(this)"></div>
                                            <div><input type="number" class="num-input pl-price" step="0.01" min="0" value="<?= number_format($pl['price'], 2, '.', '') ?>" onchange="calcPaxLine(this)"></div>
                                            <div><input type="text" class="num-input pl-total-input" value="<?= number_format($pl['qty'] * $pl['price'], 2, '.', '') ?>" readonly tabindex="-1"></div>
                                            <div><button type="button" class="btn-rm-row" onclick="removePaxLine(this)"><i class="fa fa-times"></i></button></div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="pax-lines-footer">
                                        <button type="button" class="btn-add-pax" onclick="addPaxLine(this)"><i class="fa fa-plus"></i> <?= $isThai ? 'เพิ่ม' : 'Add' ?></button>
                                        <div class="pax-item-total"><?= $isThai ? 'รวม' : 'Total' ?>: <span class="item-amount-display"><?= number_format(floatval($item['amount'] ?? 0), 2) ?></span></div>
                                    </div>
                                </div>
                                <input type="hidden" name="item_pax_lines[]" class="pax-lines-json" value='<?= htmlspecialchars(json_encode($paxLines)) ?>'>
                                <input type="hidden" name="item_amount[]" class="item-amount" value="<?= number_format(floatval($item['amount'] ?? 0), 2, '.', '') ?>">
                                <input type="hidden" name="item_contract_rate_id[]" value="<?= intval($item['contract_rate_id'] ?? 0) ?>">
                                <input type="hidden" name="item_rate_label[]" value="<?= htmlspecialchars($item['rate_label'] ?? '') ?>">
                                <input type="hidden" name="item_notes[]" value="<?= htmlspecialchars($item['notes'] ?? '') ?>">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="item-card" data-index="0">
                            <div class="item-card-header">
                                <span class="item-card-num">1</span>
                                <span class="item-card-title"><?= $isThai ? 'รายการ' : 'Item' ?> 1</span>
                                <button type="button" class="btn-rm-row" onclick="removeItemCard(this)"><i class="fa fa-times"></i></button>
                            </div>
                            <div class="item-card-body">
                                <div class="item-row-grid cols-3">
                                    <div class="bk-field">
                                        <label><?= $isThai ? 'สินค้า/ทัวร์' : 'Product / Tour' ?></label>
                                        <select name="item_product_type_id[]" class="product-type-select" data-idx="0">
                                            <option value=""><?= $isThai ? '-- เลือกสินค้า --' : '-- Select Product --' ?></option>
                                            <?php foreach ($types as $t): ?>
                                            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="bk-field">
                                        <label><?= $isThai ? 'โมเดล/แพ็คเกจ' : 'Model / Package' ?></label>
                                        <select name="item_model_id[]" class="model-select" data-idx="0">
                                            <option value="0"><?= $isThai ? '-- ไม่ระบุ --' : '-- None --' ?></option>
                                        </select>
                                    </div>
                                    <div class="bk-field">
                                        <label><?= $isThai ? 'ประเภท' : 'Type' ?></label>
                                        <select name="item_type[]">
                                            <?php foreach ($itemTypes as $k => $l): ?>
                                            <option value="<?= $k ?>"><?= $l ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="item-row-grid cols-1">
                                    <div class="bk-field">
                                        <label><?= $isThai ? 'รายละเอียด' : 'Description' ?></label>
                                        <input type="text" name="item_description[]" value="" placeholder="<?= $isThai ? 'รายละเอียดเพิ่มเติม...' : 'Additional details...' ?>">
                                    </div>
                                </div>
                                <div class="pax-lines-wrap">
                                    <div class="pax-lines-header">
                                        <div><?= $isThai ? 'ประเภท' : 'Type' ?></div>
                                        <div><?= $isThai ? 'สัญชาติ' : 'Nationality' ?></div>
                                        <div><?= $isThai ? 'จำนวน' : 'Pax' ?></div>
                                        <div><?= $isThai ? 'ราคา/คน' : 'Price' ?></div>
                                        <div><?= $isThai ? 'รวม' : 'Total' ?></div>
                                        <div></div>
                                    </div>
                                    <div class="pax-lines-body">
                                        <div class="pax-line">
                                            <div><select class="pl-pax-type"><option value="adult"><?= $isThai?'ผู้ใหญ่':'Adult' ?></option><option value="child"><?= $isThai?'เด็ก':'Child' ?></option></select></div>
                                            <div><select class="pl-nationality"><option value="thai">🇹🇭 <?= $isThai?'ไทย':'Thai' ?></option><option value="foreigner">🌍 <?= $isThai?'ต่างชาติ':'Foreign' ?></option></select></div>
                                            <div><input type="number" class="num-input pl-qty" min="0" value="0" onchange="calcPaxLine(this)"></div>
                                            <div><input type="number" class="num-input pl-price" step="0.01" min="0" value="0.00" onchange="calcPaxLine(this)"></div>
                                            <div><input type="text" class="num-input pl-total-input" value="0.00" readonly tabindex="-1"></div>
                                            <div><button type="button" class="btn-rm-row" onclick="removePaxLine(this)"><i class="fa fa-times"></i></button></div>
                                        </div>
                                    </div>
                                    <div class="pax-lines-footer">
                                        <button type="button" class="btn-add-pax" onclick="addPaxLine(this)"><i class="fa fa-plus"></i> <?= $isThai ? 'เพิ่ม' : 'Add' ?></button>
                                        <div class="pax-item-total"><?= $isThai ? 'รวม' : 'Total' ?>: <span class="item-amount-display">0.00</span></div>
                                    </div>
                                </div>
                                <input type="hidden" name="item_pax_lines[]" class="pax-lines-json" value='[]'>
                                <input type="hidden" name="item_amount[]" class="item-amount" value="0.00">
                                <input type="hidden" name="item_contract_rate_id[]" value="0">
                                <input type="hidden" name="item_rate_label[]" value="">
                                <input type="hidden" name="item_notes[]" value="">
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <button type="button" class="btn-add-row" onclick="addItemCard()"><i class="fa fa-plus"></i> <?= $isThai ? 'เพิ่มรายการ' : 'Add Item' ?></button>

                <!-- Entrance Fee + Totals -->
                <div class="totals-grid">
                    <div class="label"><?= $isThai ? 'รวมย่อย' : 'Subtotal' ?></div>
                    <div class="value"><input type="number" name="subtotal" id="subtotal" class="num-input" step="0.01" value="<?= number_format($booking['subtotal'] ?? 0, 2, '.', '') ?>" readonly tabindex="-1" style="background:#f8fafc;"></div>

                    <div class="label"><?= $isThai ? 'ค่าเข้าชม' : 'Entrance Fee' ?></div>
                    <div class="value"><input type="number" name="entrance_fee" id="entrance_fee" class="num-input" step="0.01" min="0" value="<?= number_format($booking['entrance_fee'] ?? 0, 2, '.', '') ?>" onchange="calcTotals()"></div>

                    <div class="label"><?= $isThai ? 'ส่วนลด' : 'Discount' ?></div>
                    <div class="value"><input type="number" name="discount" id="discount" class="num-input" step="0.01" min="0" value="<?= number_format($booking['discount'] ?? 0, 2, '.', '') ?>" onchange="calcTotals()"></div>

                    <div class="label"><?= $isThai ? 'ภาษี (VAT)' : 'VAT' ?></div>
                    <div class="value"><input type="number" name="vat" id="vat" class="num-input" step="0.01" min="0" value="<?= number_format($booking['vat'] ?? 0, 2, '.', '') ?>" onchange="calcTotals()"></div>

                    <div class="label grand"><?= $isThai ? 'ยอดรวมทั้งหมด' : 'Grand Total' ?></div>
                    <div class="value"><input type="number" name="total_amount" id="total_amount" class="num-input grand" step="0.01" value="<?= number_format($booking['total_amount'] ?? 0, 2, '.', '') ?>" readonly tabindex="-1" style="background:linear-gradient(135deg,#f0fdfa,#e6fffa); border-color:#99f6e4; font-weight:700; color:#0d9488;"></div>
                </div>
            </div>

            <!-- Section 4: Remark -->
            <div class="bk-card">
                <h3><i class="fa fa-sticky-note-o"></i> <?= $isThai ? 'หมายเหตุ' : 'Remark' ?></h3>
                <div class="bk-field">
                    <textarea name="remark" rows="3" placeholder="<?= $isThai ? 'หมายเหตุเพิ่มเติม...' : 'Additional remarks...' ?>"><?= htmlspecialchars($booking['remark'] ?? '') ?></textarea>
                </div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <a href="index.php?page=tour_booking_list" class="btn-cancel"><?= $isThai ? 'ยกเลิก' : 'Cancel' ?></a>
                <button type="submit" class="btn-save">
                    <i class="fa fa-check"></i> <?= $isEdit ? ($isThai ? 'บันทึก' : 'Save') : ($isThai ? 'สร้างการจอง' : 'Create Booking') ?>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Quick Create Customer Modal -->
<div class="qc-overlay" id="qcOverlay">
    <div class="qc-modal" style="width:500px;">
        <h4><i class="fa fa-user-plus" style="color:#0d9488;"></i> <?= $isThai ? 'สร้างลูกค้าใหม่' : 'Create New Customer' ?></h4>
        <div class="bk-field" style="margin-bottom:14px;">
            <label><?= $isThai ? 'ชื่อลูกค้า' : 'Customer Name' ?> <span class="req">*</span></label>
            <div class="bk-input-icon">
                <i class="fa fa-user"></i>
                <input type="text" id="qcName" placeholder="<?= $isThai ? 'ชื่อลูกค้า' : 'Customer name' ?>">
            </div>
        </div>
        <div class="bk-field" style="margin-bottom:14px;">
            <label><?= $isThai ? 'เบอร์โทร' : 'Phone' ?></label>
            <div class="bk-input-icon">
                <i class="fa fa-mobile" style="font-size:18px;"></i>
                <input type="text" id="qcPhone" placeholder="<?= $isThai ? 'เบอร์โทรศัพท์' : 'Phone number' ?>">
            </div>
        </div>
        <div class="bk-field" style="margin-bottom:14px;">
            <label><?= $isThai ? 'ไลน์ / WhatsApp / อื่นๆ' : 'Line / WhatsApp / Messengers' ?></label>
            <div class="bk-input-icon">
                <i class="fa fa-comments-o"></i>
                <input type="text" id="qcMessengers" placeholder="<?= $isThai ? 'Line: @user, WhatsApp: +668xxx' : 'Line: @user, WhatsApp: +668xxx' ?>">
            </div>
        </div>
        <div class="qc-actions">
            <button type="button" class="qc-btn qc-btn-cancel" onclick="closeQcModal()"><?= $isThai ? 'ยกเลิก' : 'Cancel' ?></button>
            <button type="button" class="qc-btn qc-btn-save" onclick="saveQuickCustomer()"><?= $isThai ? 'สร้าง' : 'Create' ?></button>
        </div>
    </div>
</div>

<!-- Sales Rep Creation Modal -->
<div class="qc-overlay" id="srOverlay">
    <div class="qc-modal" style="width:500px;">
        <h4><i class="fa fa-user-plus" style="color:#0d9488;"></i> <?= $isThai ? 'สร้างพนักงานขายใหม่' : 'Create New Sales Rep' ?></h4>
        <div class="bk-field" style="margin-bottom:14px;">
            <label><?= $isThai ? 'ชื่อพนักงานขาย' : 'Sales Rep Name' ?> <span class="req">*</span></label>
            <div class="bk-input-icon">
                <i class="fa fa-user"></i>
                <input type="text" id="srModalName" placeholder="<?= $isThai ? 'ชื่อพนักงานขาย' : 'Sales rep name' ?>">
            </div>
        </div>
        <div class="bk-field" style="margin-bottom:14px;">
            <label><?= $isThai ? 'อีเมล' : 'Email' ?></label>
            <div class="bk-input-icon">
                <i class="fa fa-envelope-o"></i>
                <input type="text" id="srModalEmail" placeholder="<?= $isThai ? 'อีเมล' : 'Email' ?>">
            </div>
        </div>
        <div class="bk-field" style="margin-bottom:14px;">
            <label><?= $isThai ? 'เบอร์โทร' : 'Phone' ?></label>
            <div class="bk-input-icon">
                <i class="fa fa-mobile" style="font-size:18px;"></i>
                <input type="text" id="srModalPhone" placeholder="<?= $isThai ? 'เบอร์โทรศัพท์' : 'Phone number' ?>">
            </div>
        </div>
        <div class="bk-field" style="margin-bottom:14px;">
            <label><?= $isThai ? 'ไลน์ / WhatsApp / อื่นๆ' : 'Messengers (Line/WhatsApp/etc)' ?></label>
            <div class="bk-input-icon">
                <i class="fa fa-comments-o"></i>
                <input type="text" id="srModalMessengers" placeholder="<?= $isThai ? 'Line: @user, WhatsApp: +668xxx' : 'Line: @user, WhatsApp: +668xxx' ?>">
            </div>
        </div>
        <div class="qc-actions">
            <button type="button" class="qc-btn qc-btn-cancel" onclick="closeSrModal()"><?= $isThai ? 'ยกเลิก' : 'Cancel' ?></button>
            <button type="button" class="qc-btn qc-btn-save" onclick="saveSalesRep()"><?= $isThai ? 'สร้าง' : 'Create' ?></button>
        </div>
    </div>
</div>

<script>
// ─── Config ────────────────────────────────────────────────
var itemTypeOptions = <?= json_encode(array_map(function($k, $l) { return '<option value="' . $k . '">' . $l . '</option>'; }, array_keys($itemTypes), $itemTypes)) ?>;
var productTypeOptions = <?= json_encode(array_map(function($t) { return '<option value="' . $t['id'] . '">' . htmlspecialchars($t['name']) . '</option>'; }, $types)) ?>;
var allModelsData = <?= json_encode($models_by_type ?? [], JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
var csrfToken = '<?= csrf_token() ?>';
var itemCounter = document.querySelectorAll('.item-card').length;

// ─── Labels ────────────────────────────────────────────────
var LBL = {
    product: '<?= $isThai ? "สินค้า/ทัวร์" : "Product / Tour" ?>',
    model: '<?= $isThai ? "โมเดล/แพ็คเกจ" : "Model / Package" ?>',
    type: '<?= $isThai ? "ประเภท" : "Type" ?>',
    desc: '<?= $isThai ? "รายละเอียด" : "Description" ?>',
    descPh: '<?= $isThai ? "รายละเอียดเพิ่มเติม..." : "Additional details..." ?>',
    selProduct: '<?= $isThai ? "-- เลือกสินค้า --" : "-- Select Product --" ?>',
    selModel: '<?= $isThai ? "-- ไม่ระบุ --" : "-- None --" ?>',
    thai: '<?= $isThai ? "ไทย" : "Thai" ?>',
    foreign: '<?= $isThai ? "ต่างชาติ" : "Foreigner" ?>',
    pricePax: '<?= $isThai ? "ราคา/คน" : "Price" ?>',
    adult: '<?= $isThai ? "ผู้ใหญ่" : "Adult" ?>',
    child: '<?= $isThai ? "เด็ก" : "Child" ?>',
    amount: '<?= $isThai ? "ยอดรวม" : "Amount" ?>',
    item: '<?= $isThai ? "รายการ" : "Item" ?>',
    paxType: '<?= $isThai ? "ประเภท" : "Type" ?>',
    nationality: '<?= $isThai ? "สัญชาติ" : "Nationality" ?>',
    pax: '<?= $isThai ? "จำนวน" : "Pax" ?>',
    total: '<?= $isThai ? "รวม" : "Total" ?>',
    addPax: '<?= $isThai ? "เพิ่ม" : "Add" ?>'
};

// ─── Add Item Card ─────────────────────────────────────────
function addItemCard() {
    var idx = itemCounter++;
    var container = document.getElementById('itemsContainer');
    var card = document.createElement('div');
    card.className = 'item-card';
    card.dataset.index = idx;
    card.innerHTML = `
        <div class="item-card-header">
            <span class="item-card-num">${document.querySelectorAll('.item-card').length + 1}</span>
            <span class="item-card-title">${LBL.item} ${document.querySelectorAll('.item-card').length + 1}</span>
            <button type="button" class="btn-rm-row" onclick="removeItemCard(this)"><i class="fa fa-times"></i></button>
        </div>
        <div class="item-card-body">
            <div class="item-row-grid cols-3">
                <div class="bk-field">
                    <label>${LBL.product}</label>
                    <select name="item_product_type_id[]" class="product-type-select" data-idx="${idx}">
                        <option value="">${LBL.selProduct}</option>
                        ${productTypeOptions.join('')}
                    </select>
                </div>
                <div class="bk-field">
                    <label>${LBL.model}</label>
                    <select name="item_model_id[]" class="model-select" data-idx="${idx}">
                        <option value="0">${LBL.selModel}</option>
                    </select>
                </div>
                <div class="bk-field">
                    <label>${LBL.type}</label>
                    <select name="item_type[]">${itemTypeOptions.join('')}</select>
                </div>
            </div>
            <div class="item-row-grid cols-1">
                <div class="bk-field">
                    <label>${LBL.desc}</label>
                    <input type="text" name="item_description[]" value="" placeholder="${LBL.descPh}">
                </div>
            </div>
            <div class="pax-lines-wrap">
                <div class="pax-lines-header">
                    <div>${LBL.paxType}</div>
                    <div>${LBL.nationality}</div>
                    <div>${LBL.pax}</div>
                    <div>${LBL.pricePax}</div>
                    <div>${LBL.total}</div>
                    <div></div>
                </div>
                <div class="pax-lines-body">
                    <div class="pax-line">
                        <div><select class="pl-pax-type"><option value="adult">${LBL.adult}</option><option value="child">${LBL.child}</option></select></div>
                        <div><select class="pl-nationality"><option value="thai">🇹🇭 ${LBL.thai}</option><option value="foreigner">🌍 ${LBL.foreign}</option></select></div>
                        <div><input type="number" class="num-input pl-qty" min="0" value="0" onchange="calcPaxLine(this)"></div>
                        <div><input type="number" class="num-input pl-price" step="0.01" min="0" value="0.00" onchange="calcPaxLine(this)"></div>
                        <div><input type="text" class="num-input pl-total-input" value="0.00" readonly tabindex="-1"></div>
                        <div><button type="button" class="btn-rm-row" onclick="removePaxLine(this)"><i class="fa fa-times"></i></button></div>
                    </div>
                </div>
                <div class="pax-lines-footer">
                    <button type="button" class="btn-add-pax" onclick="addPaxLine(this)"><i class="fa fa-plus"></i> ${LBL.addPax}</button>
                    <div class="pax-item-total">${LBL.total}: <span class="item-amount-display">0.00</span></div>
                </div>
            </div>
            <input type="hidden" name="item_pax_lines[]" class="pax-lines-json" value="[]">
            <input type="hidden" name="item_amount[]" class="item-amount" value="0.00">
            <input type="hidden" name="item_contract_rate_id[]" value="0">
            <input type="hidden" name="item_rate_label[]" value="">
            <input type="hidden" name="item_notes[]" value="">
        </div>`;
    container.appendChild(card);
    renumberItemCards();
}

function removeItemCard(btn) {
    var cards = document.querySelectorAll('.item-card');
    if (cards.length <= 1) return;
    btn.closest('.item-card').remove();
    renumberItemCards();
    calcTotals();
}

function renumberItemCards() {
    document.querySelectorAll('.item-card').forEach(function(card, i) {
        card.querySelector('.item-card-num').textContent = i + 1;
        card.querySelector('.item-card-title').textContent = LBL.item + ' ' + (i + 1);
    });
}

// ─── Product → Model Cascade ───────────────────────────────
document.addEventListener('change', function(e) {
    if (!e.target.classList.contains('product-type-select')) return;
    var card = e.target.closest('.item-card');
    var typeId = e.target.value;
    var modelSelect = card.querySelector('.model-select');
    var descField = card.querySelector('[name="item_description[]"]');

    // Reset model dropdown
    modelSelect.innerHTML = '<option value="0">' + LBL.selModel + '</option>';

    if (typeId && allModelsData[String(typeId)]) {
        allModelsData[String(typeId)].forEach(function(m) {
            var opt = document.createElement('option');
            opt.value = m.id;
            opt.textContent = m.model_name + (m.des ? ' — ' + m.des : '');
            opt.dataset.price = m.price || 0;
            opt.dataset.des = m.des || '';
            modelSelect.appendChild(opt);
        });
    }

    // Auto-fill description from product type name
    var selectedOpt = e.target.options[e.target.selectedIndex];
    if (selectedOpt && selectedOpt.value && !descField.value.trim()) {
        descField.value = selectedOpt.textContent;
    }
});

document.addEventListener('change', function(e) {
    if (!e.target.classList.contains('model-select')) return;
    var card = e.target.closest('.item-card');
    var selectedOpt = e.target.options[e.target.selectedIndex];
    if (!selectedOpt || selectedOpt.value === '0') return;

    var price = parseFloat(selectedOpt.dataset.price) || 0;
    var des = selectedOpt.dataset.des || '';

    // Auto-fill price in first pax line if still zero
    var firstPrice = card.querySelector('.pax-line .pl-price');
    if (firstPrice && (parseFloat(firstPrice.value) === 0 || !firstPrice.value)) {
        firstPrice.value = price.toFixed(2);
        calcPaxLine(firstPrice);
    }

    // Auto-fill description if empty
    var descField = card.querySelector('[name="item_description[]"]');
    if (des && descField && !descField.value.trim()) {
        descField.value = des;
    }
});

// ─── Pax Line Functions ────────────────────────────────────
function addPaxLine(btn) {
    var wrap = btn.closest('.pax-lines-wrap');
    var body = wrap.querySelector('.pax-lines-body');
    var line = document.createElement('div');
    line.className = 'pax-line';
    line.innerHTML = `
        <div><select class="pl-pax-type"><option value="adult">${LBL.adult}</option><option value="child">${LBL.child}</option></select></div>
        <div><select class="pl-nationality"><option value="thai">🇹🇭 ${LBL.thai}</option><option value="foreigner">🌍 ${LBL.foreign}</option></select></div>
        <div><input type="number" class="num-input pl-qty" min="0" value="0" onchange="calcPaxLine(this)"></div>
        <div><input type="number" class="num-input pl-price" step="0.01" min="0" value="0.00" onchange="calcPaxLine(this)"></div>
        <div><input type="text" class="num-input pl-total-input" value="0.00" readonly tabindex="-1"></div>
        <div><button type="button" class="btn-rm-row" onclick="removePaxLine(this)"><i class="fa fa-times"></i></button></div>`;
    body.appendChild(line);
}

function removePaxLine(btn) {
    var wrap = btn.closest('.pax-lines-wrap');
    var lines = wrap.querySelectorAll('.pax-line');
    if (lines.length <= 1) return;
    btn.closest('.pax-line').remove();
    calcItemTotal(wrap);
}

function calcPaxLine(el) {
    var line = el.closest('.pax-line');
    var qty = parseInt(line.querySelector('.pl-qty').value) || 0;
    var price = parseFloat(line.querySelector('.pl-price').value) || 0;
    line.querySelector('.pl-total-input').value = (qty * price).toFixed(2);
    calcItemTotal(line.closest('.pax-lines-wrap'));
}

function calcItemTotal(wrap) {
    var card = wrap.closest('.item-card');
    var totals = wrap.querySelectorAll('.pl-total-input');
    var sum = 0;
    totals.forEach(function(t) { sum += parseFloat(t.value) || 0; });
    card.querySelector('.item-amount').value = sum.toFixed(2);
    var display = wrap.querySelector('.item-amount-display');
    if (display) display.textContent = sum.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    calcTotals();
}

// ─── Pax Display Sync ──────────────────────────────────────
function syncPaxQty() {
    updatePaxDisplay();
}

function updatePaxDisplay() {
    var adults = parseInt(document.querySelector('[name="pax_adult"]').value) || 0;
    var children = parseInt(document.querySelector('[name="pax_child"]').value) || 0;
    var infants = parseInt(document.querySelector('[name="pax_infant"]').value) || 0;
    var totalCount = adults + children + infants;
    document.getElementById('displayPaxCount').textContent = totalCount;
}

// ─── Totals Calculation ────────────────────────────────────
function calcTotals() {
    var amounts = document.querySelectorAll('.item-amount');
    var sub = 0;
    amounts.forEach(function(el) { sub += parseFloat(el.value) || 0; });

    var entrance = parseFloat(document.getElementById('entrance_fee').value) || 0;
    var discount = parseFloat(document.getElementById('discount').value) || 0;
    var vat = parseFloat(document.getElementById('vat').value) || 0;

    document.getElementById('subtotal').value = sub.toFixed(2);
    var total = sub + entrance - discount + vat;
    document.getElementById('total_amount').value = total.toFixed(2);
}

// ─── Customer Autocomplete ─────────────────────────────────
var acTimer = null;
var acInput = document.getElementById('customerSearch');
var acList = document.getElementById('customerAcList');
var customerCache = {}; // Store customer contact data by id

if (acInput) {
    acInput.addEventListener('input', function() {
        clearTimeout(acTimer);
        var q = this.value.trim();
        if (q.length < 1) { acList.classList.remove('show'); return; }
        acTimer = setTimeout(function() {
            fetch('index.php?page=tour_booking_customer_search&q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var html = '';
                    data.forEach(function(c) {
                        var sub = [c.phone, c.email].filter(Boolean).join(' | ');
                        customerCache[c.id] = {contact: c.contact||'', phone: c.phone||'', email: c.email||''};
                        html += '<div class="ac-item" onclick="selectCustomer(' + c.id + ', \'' + escHtml(c.name_en) + '\')">'
                            + escHtml(c.name_en)
                            + (sub ? ' <span class="sub">' + escHtml(sub) + '</span>' : '')
                            + '</div>';
                    });
                    html += '<div class="ac-item create-new" onclick="openCreateCustomerModal()"><i class="fa fa-plus"></i> <?= $isThai ? "สร้างลูกค้าใหม่" : "Create New Customer" ?></div>';
                    acList.innerHTML = html;
                    acList.classList.add('show');
                });
        }, 250);
    });

    acInput.addEventListener('blur', function() {
        setTimeout(function() { acList.classList.remove('show'); }, 200);
    });

    acInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 1 && acList.innerHTML) acList.classList.add('show');
    });
}

// ─── Agent Autocomplete (Smart Search) ─────────────────────
var agTimer = null;
var agInput = document.getElementById('agentSearch');
var agList  = document.getElementById('agentAcList');
var agCache = {};

if (agInput) {
    agInput.addEventListener('input', function() {
        clearTimeout(agTimer);
        var q = this.value.trim();
        if (q.length < 1) { agList.classList.remove('show'); return; }
        agTimer = setTimeout(function() {
            fetch('index.php?page=tour_booking_agent_search&q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var html = '';
                    data.forEach(function(a) {
                        var sub = [a.contact_mobile, a.contact_email].filter(Boolean).join(' | ');
                        agCache[a.id] = { email: a.contact_email || '', phone: a.contact_mobile || '' };
                        html += '<div class="ac-item" onclick="selectAgent(' + a.id + ', \'' + escHtml(a.name_en) + '\')">'
                            + escHtml(a.name_en)
                            + (sub ? ' <span class="sub">' + escHtml(sub) + '</span>' : '')
                            + '</div>';
                    });
                    agList.innerHTML = html;
                    agList.classList.add('show');
                });
        }, 250);
    });

    agInput.addEventListener('blur', function() {
        setTimeout(function() { agList.classList.remove('show'); }, 200);
    });

    agInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 1 && agList.innerHTML) agList.classList.add('show');
    });
}

function selectAgent(id, name) {
    document.getElementById('agent_id').value = id;
    var sel = document.getElementById('agentSelected');
    sel.innerHTML = escHtml(name) + ' <span class="ac-clear" onclick="clearAgent()">&times;</span>';
    sel.style.display = '';
    agInput.style.display = 'none';
    agList.classList.remove('show');
}

function clearAgent() {
    document.getElementById('agent_id').value = '0';
    document.getElementById('agentSelected').style.display = 'none';
    agInput.value = '';
    agInput.style.display = '';
    agInput.focus();
}

// ─── Sales Rep Autocomplete (Smart Search) ─────────────────
var srTimer = null;
var srInput = document.getElementById('salesRepSearch');
var srList = document.getElementById('salesRepAcList');
var srCache = {};

if (srInput) {
    srInput.addEventListener('input', function() {
        clearTimeout(srTimer);
        var q = this.value.trim();
        if (q.length < 1) { srList.classList.remove('show'); return; }
        srTimer = setTimeout(function() {
            fetch('index.php?page=tour_booking_sales_rep_search&q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var html = '';
                    data.forEach(function(a) {
                        var sub = [a.contact_mobile, a.contact_email].filter(Boolean).join(' | ');
                        srCache[a.id] = {
                            email: a.contact_email || '',
                            phone: a.contact_mobile || '',
                            messengers: a.contact_messengers || ''
                        };
                        html += '<div class="ac-item" onclick="selectSalesRep(' + a.id + ', \'' + escHtml(a.name_en) + '\')">'
                            + escHtml(a.name_en)
                            + (sub ? ' <span class="sub">' + escHtml(sub) + '</span>' : '')
                            + '</div>';
                    });
                    html += '<div class="ac-item create-new" onclick="openCreateSalesRepModal()"><i class="fa fa-plus"></i> <?= $isThai ? "สร้างพนักงานขายใหม่" : "Create New Sales Rep" ?></div>';
                    srList.innerHTML = html;
                    srList.classList.add('show');
                });
        }, 250);
    });

    srInput.addEventListener('blur', function() {
        setTimeout(function() { srList.classList.remove('show'); }, 200);
    });

    srInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 1 && srList.innerHTML) srList.classList.add('show');
    });
}

function selectSalesRep(id, name, info) {
    document.getElementById('sales_rep_id').value = id;
    var sel = document.getElementById('salesRepSelected');
    sel.innerHTML = escHtml(name) + ' <span class="ac-clear" onclick="clearSalesRep()">&times;</span>';
    sel.style.display = '';
    srInput.style.display = 'none';
    srList.classList.remove('show');
    // Auto-fill sales rep contact info
    var info_data = info || srCache[id] || {};
    document.getElementById('salesRepEmail').value = info_data.email || '';
    document.getElementById('salesRepMobile').value = info_data.phone || '';
    document.getElementById('salesRepMessengers').value = info_data.messengers || '';
}

function clearSalesRep() {
    document.getElementById('sales_rep_id').value = '0';
    document.getElementById('salesRepSelected').style.display = 'none';
    srInput.value = '';
    srInput.style.display = '';
    srInput.focus();
    // Clear auto-filled fields
    document.getElementById('salesRepEmail').value = '';
    document.getElementById('salesRepMobile').value = '';
    document.getElementById('salesRepMessengers').value = '';
}

function selectCustomer(id, name, info) {
    document.getElementById('customer_id').value = id;
    var sel = document.getElementById('customerSelected');
    sel.innerHTML = escHtml(name) + ' <span class="ac-clear" onclick="clearCustomer()">&times;</span>';
    sel.style.display = '';
    acInput.style.display = 'none';
    acList.classList.remove('show');
    // Auto-fill customer contact info from company master
    var ci = info || customerCache[id] || {};
    document.getElementById('cusName').value = name;
    document.getElementById('cusEmail').value = ci.email || '';
    document.getElementById('cusPhone').value = ci.phone || '';
}

function clearCustomer() {
    document.getElementById('customer_id').value = '0';
    document.getElementById('customerSelected').style.display = 'none';
    acInput.value = '';
    acInput.style.display = '';
    acInput.focus();
    // Clear auto-filled fields
    document.getElementById('cusName').value = '';
    document.getElementById('cusEmail').value = '';
    document.getElementById('cusPhone').value = '';
}

function escHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

// ─── Quick Create Customer Modal ───────────────────────────
function openCreateCustomerModal() {
    acList.classList.remove('show');
    document.getElementById('qcName').value = acInput ? acInput.value.trim() : '';
    document.getElementById('qcPhone').value = '';
    document.getElementById('qcMessengers').value = '';
    document.getElementById('qcOverlay').classList.add('active');
    document.getElementById('qcName').focus();
}

// ─── Create New Sales Rep Modal ────────────────────────────
function openCreateSalesRepModal() {
    srList.classList.remove('show');
    document.getElementById('srModalName').value = srInput.value.trim();
    document.getElementById('srModalEmail').value = '';
    document.getElementById('srModalPhone').value = '';
    document.getElementById('srModalMessengers').value = '';
    document.getElementById('srOverlay').classList.add('active');
    document.getElementById('srModalName').focus();
}

function closeSrModal() {
    document.getElementById('srOverlay').classList.remove('active');
}

function saveSalesRep() {
    var name = document.getElementById('srModalName').value.trim();
    var email = document.getElementById('srModalEmail').value.trim();
    var phone = document.getElementById('srModalPhone').value.trim();
    var messengers = document.getElementById('srModalMessengers').value.trim();
    if (!name) { document.getElementById('srModalName').focus(); return; }
    
    fetch('index.php?page=tour_booking_sales_rep_create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'name=' + encodeURIComponent(name)
            + '&email=' + encodeURIComponent(email)
            + '&phone=' + encodeURIComponent(phone)
            + '&messengers=' + encodeURIComponent(messengers)
            + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            selectSalesRep(data.id, data.name, {
                email: data.email || '',
                phone: data.phone || '',
                messengers: data.messengers || ''
            });
            closeSrModal();
        } else {
            alert(data.message || '<?= $isThai ? "เกิดข้อผิดพลาด" : "Error occurred" ?>');
        }
    })
    .catch(function(err) {
        alert('<?= $isThai ? "เกิดข้อผิดพลาด" : "Error occurred" ?>');
    });
}

function closeQcModal() {
    document.getElementById('qcOverlay').classList.remove('active');
}

function saveQuickCustomer() {
    var name       = document.getElementById('qcName').value.trim();
    var phone      = document.getElementById('qcPhone').value.trim();
    var messengers = document.getElementById('qcMessengers').value.trim();
    if (!name) { document.getElementById('qcName').focus(); return; }

    fetch('index.php?page=tour_booking_customer_create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'name=' + encodeURIComponent(name)
            + '&phone=' + encodeURIComponent(phone)
            + '&messengers=' + encodeURIComponent(messengers)
            + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            selectCustomer(data.id, data.name, {
                contact: '',
                phone: data.phone || '',
                email: ''
            });
            // Pre-fill messengers into the contact form
            if (messengers) document.getElementById('cusMessengers').value = messengers;
            if (phone)      document.getElementById('cusPhone').value = phone;
            closeQcModal();
        } else {
            alert(data.error || '<?= $isThai ? "เกิดข้อผิดพลาด" : "Error occurred" ?>');
        }
    })
    .catch(function() {
        alert('<?= $isThai ? "เกิดข้อผิดพลาด กรุณาลองใหม่" : "Error occurred. Please try again." ?>');
    });
}

// ─── Booking By Autocomplete ───────────────────────────────
var bbTimer = null;
var bbInput = document.getElementById('bookingBySearch');
var bbList = document.getElementById('bookingByAcList');

if (bbInput) {
    bbInput.addEventListener('input', function() {
        clearTimeout(bbTimer);
        var q = this.value.trim();
        if (q.length < 1) { bbList.classList.remove('show'); return; }
        bbTimer = setTimeout(function() {
            fetch('index.php?page=tour_booking_staff_search&q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var html = '';
                    data.forEach(function(u) {
                        var sub = [u.email, u.phone].filter(Boolean).join(' | ');
                        html += '<div class="ac-item" data-name="' + escHtml(u.name) + '">'
                            + escHtml(u.name)
                            + (sub ? ' <span class="sub">' + escHtml(sub) + '</span>' : '')
                            + '</div>';
                    });
                    if (!html) html = '<div class="ac-item" style="color:#999;pointer-events:none;"><?= $isThai ? "ไม่พบผลลัพธ์" : "No results" ?></div>';
                    bbList.innerHTML = html;
                    bbList.classList.add('show');
                });
        }, 250);
    });

    bbList.addEventListener('click', function(e) {
        var item = e.target.closest('.ac-item');
        if (item && item.dataset.name) {
            bbInput.value = item.dataset.name;
            bbList.classList.remove('show');
        }
    });

    bbInput.addEventListener('blur', function() {
        setTimeout(function() { bbList.classList.remove('show'); }, 200);
    });

    bbInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 1 && bbList.innerHTML) bbList.classList.add('show');
    });
}

// ─── Serialize Pax Lines before submit ─────────────────────
function serializePaxLines() {
    document.querySelectorAll('.item-card').forEach(function(card) {
        var lines = [];
        card.querySelectorAll('.pax-line').forEach(function(line) {
            lines.push({
                type: line.querySelector('.pl-pax-type').value,
                nat: line.querySelector('.pl-nationality').value,
                qty: parseInt(line.querySelector('.pl-qty').value) || 0,
                price: parseFloat(line.querySelector('.pl-price').value) || 0
            });
        });
        var jsonField = card.querySelector('.pax-lines-json');
        if (jsonField) jsonField.value = JSON.stringify(lines);
    });
}

// ─── Allotment Availability Check ─────────────────────────
function checkAllotment(date) {
    var badge = document.getElementById('allotmentBadge');
    if (!date || !badge) return;
    badge.style.display = 'inline-block';
    badge.style.color = '#6b7280';
    badge.innerHTML = '<i class="fa fa-spinner fa-spin"></i> <?= $isThai ? "ตรวจสอบที่นั่ง..." : "Checking seats..." ?>';

    fetch('index.php?page=tour_allotment_availability&date=' + encodeURIComponent(date))
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.no_fleet) {
                badge.style.display = 'none';
                return;
            }
            var avail = d.available;
            var total = d.total_seats;
            var booked = d.booked_seats;
            if (d.is_closed) {
                badge.style.color = '#dc2626';
                badge.innerHTML = '<i class="fa fa-lock"></i> <?= $isThai ? "ปิดรับจอง" : "Date Closed" ?>';
            } else if (avail < 0) {
                badge.style.color = '#dc2626';
                badge.innerHTML = '<i class="fa fa-exclamation-triangle"></i> <?= $isThai ? "จองเกิน" : "Overbooked" ?>: ' + booked + '/' + total + ' <?= $isThai ? "ที่นั่ง" : "seats" ?>';
            } else if (avail <= Math.ceil(total * 0.1)) {
                badge.style.color = '#f59e0b';
                badge.innerHTML = '<i class="fa fa-exclamation-circle"></i> ' + avail + '/' + total + ' <?= $isThai ? "ที่นั่งว��าง" : "seats available" ?>';
            } else {
                badge.style.color = '#059669';
                badge.innerHTML = '<i class="fa fa-check-circle"></i> ' + avail + '/' + total + ' <?= $isThai ? "ที่นั่งว่าง" : "seats available" ?>';
            }
        })
        .catch(function() {
            badge.style.display = 'none';
        });
}

// ─── Init ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    updatePaxDisplay();
    var form = document.getElementById('bookingForm');
    if (form) form.addEventListener('submit', function() { serializePaxLines(); });

    // Allotment: check on load and on date change
    var dateInput = document.getElementById('travelDateInput');
    if (dateInput) {
        if (dateInput.value) checkAllotment(dateInput.value);
        dateInput.addEventListener('change', function() { checkAllotment(this.value); });
    }
});
</script>
