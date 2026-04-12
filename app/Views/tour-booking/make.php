<?php
/**
 * Tour Booking — Create / Edit Form
 *
 * Variables: $booking (null = create), $agents, $customers, $locations, $message
 */

$isThai    = ($_SESSION['lang'] ?? '0') === '1';
$isEdit    = !empty($booking);
$pageTitle = $isEdit
    ? ($isThai ? 'แก้ไขการจอง' : 'Edit Booking') . ' — ' . htmlspecialchars($booking['booking_number'] ?? '')
    : ($isThai ? 'สร้างการจองใหม่' : 'New Booking');

$statusOptions = [
    'draft'     => $isThai ? 'ฉบับร่าง' : 'Draft',
    'confirmed' => $isThai ? 'ยืนยัน' : 'Confirmed',
    'completed' => $isThai ? 'เสร็จสิ้น' : 'Completed',
    'cancelled' => $isThai ? 'ยกเลิก' : 'Cancelled',
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
.bk-form { max-width: 960px; margin: 0 auto; }
.bk-card { background: white; border-radius: 14px; padding: 24px; border: 1px solid #e2e8f0; margin-bottom: 16px; }
.bk-card h3 { font-size: 15px; font-weight: 600; margin: 0 0 18px; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; color: #1e293b; }
.bk-card h3 i { color: #0d9488; margin-right: 6px; }
.bk-row { display: grid; gap: 16px; margin-bottom: 16px; }
.bk-row.cols-2 { grid-template-columns: 1fr 1fr; }
.bk-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
.bk-row.cols-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }
.bk-field label { display: block; font-size: 12px; font-weight: 600; color: #475569; margin-bottom: 5px; }
.bk-field label .req { color: #ef4444; }
.bk-field input, .bk-field select, .bk-field textarea { width: 100%; padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; }
.bk-field input:focus, .bk-field select:focus, .bk-field textarea:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,0.1); }
.bk-field textarea { resize: vertical; min-height: 70px; }

/* Dynamic rows */
.dyn-table { width: 100%; border-collapse: collapse; }
.dyn-table th { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; text-align: left; padding: 6px 8px; background: #f8fafc; }
.dyn-table td { padding: 6px 8px; vertical-align: top; }
.dyn-table input, .dyn-table select { width: 100%; padding: 7px 8px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 12px; }
.dyn-table .num-input { text-align: right; }
.btn-add-row { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #f0fdfa; color: #0d9488; border: 1px dashed #0d9488; border-radius: 8px; font-size: 12px; font-weight: 600; cursor: pointer; margin-top: 10px; }
.btn-add-row:hover { background: #0d9488; color: white; }
.btn-rm-row { width: 28px; height: 28px; border-radius: 6px; border: 1px solid #fecaca; background: white; color: #ef4444; cursor: pointer; font-size: 11px; }
.btn-rm-row:hover { background: #ef4444; color: white; }

/* Totals */
.totals-grid { display: grid; grid-template-columns: 1fr 200px; gap: 8px; max-width: 400px; margin-left: auto; margin-top: 16px; font-size: 13px; }
.totals-grid .label { color: #64748b; text-align: right; padding: 6px 0; }
.totals-grid .value input { text-align: right; }
.totals-grid .grand { font-weight: 700; font-size: 16px; color: #0d9488; }

.form-actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 24px; }
.btn-save { padding: 10px 28px; background: #0d9488; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-save:hover { background: #0f766e; }
.btn-cancel { padding: 10px 28px; background: white; color: #64748b; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }

@media (max-width: 768px) {
    .bk-row.cols-2, .bk-row.cols-3, .bk-row.cols-4 { grid-template-columns: 1fr; }
    .dyn-table { font-size: 11px; }
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
                        <label><?= $isThai ? 'วันเดินทาง' : 'Travel Date' ?> <span class="req">*</span></label>
                        <input type="date" name="travel_date" value="<?= htmlspecialchars($booking['travel_date'] ?? date('Y-m-d')) ?>" required>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'สถานะ' : 'Status' ?></label>
                        <select name="status">
                            <?php foreach ($statusOptions as $key => $label): ?>
                            <option value="<?= $key ?>" <?= ($booking['status'] ?? 'draft') === $key ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'เลข Voucher' : 'Voucher Number' ?></label>
                        <input type="text" name="voucher_number" value="<?= htmlspecialchars($booking['voucher_number'] ?? '') ?>">
                    </div>
                </div>

                <div class="bk-row cols-3">
                    <div class="bk-field">
                        <label><?= $isThai ? 'ลูกค้า' : 'Customer' ?></label>
                        <select name="customer_id">
                            <option value="0"><?= $isThai ? '-- เลือก --' : '-- Select --' ?></option>
                            <?php foreach ($customers as $c): ?>
                            <option value="<?= $c['id'] ?>" <?= intval($booking['customer_id'] ?? 0) === intval($c['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name_en']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'ตัวแทน' : 'Agent' ?></label>
                        <select name="agent_id">
                            <option value="0"><?= $isThai ? '-- ไม่มี --' : '-- None --' ?></option>
                            <?php foreach ($agents as $a): ?>
                            <option value="<?= $a['id'] ?>" <?= intval($booking['agent_id'] ?? 0) === intval($a['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['name_en']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'ผู้จอง' : 'Booking By' ?></label>
                        <input type="text" name="booking_by" value="<?= htmlspecialchars($booking['booking_by'] ?? '') ?>">
                    </div>
                </div>

                <div class="bk-row cols-4">
                    <div class="bk-field">
                        <label><?= $isThai ? 'ผู้ใหญ่' : 'Adults' ?></label>
                        <input type="number" name="pax_adult" min="0" value="<?= intval($booking['pax_adult'] ?? 0) ?>">
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'เด็ก' : 'Children' ?></label>
                        <input type="number" name="pax_child" min="0" value="<?= intval($booking['pax_child'] ?? 0) ?>">
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'ทารก' : 'Infants' ?></label>
                        <input type="number" name="pax_infant" min="0" value="<?= intval($booking['pax_infant'] ?? 0) ?>">
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'สกุลเงิน' : 'Currency' ?></label>
                        <select name="currency">
                            <option value="THB" <?= ($booking['currency'] ?? 'THB') === 'THB' ? 'selected' : '' ?>>THB</option>
                            <option value="USD" <?= ($booking['currency'] ?? '') === 'USD' ? 'selected' : '' ?>>USD</option>
                            <option value="EUR" <?= ($booking['currency'] ?? '') === 'EUR' ? 'selected' : '' ?>>EUR</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Section 2: Pickup -->
            <div class="bk-card">
                <h3><i class="fa fa-car"></i> <?= $isThai ? 'ข้อมูลรับ-ส่ง' : 'Pickup Details' ?></h3>
                <div class="bk-row cols-4">
                    <div class="bk-field">
                        <label><?= $isThai ? 'จุดรับ' : 'Pickup Location' ?></label>
                        <select name="pickup_location_id">
                            <option value="0"><?= $isThai ? '-- เลือก --' : '-- Select --' ?></option>
                            <?php foreach ($locations as $loc): ?>
                            <option value="<?= $loc['id'] ?>" <?= intval($booking['pickup_location_id'] ?? 0) === intval($loc['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($loc['name']) ?> (<?= $loc['location_type'] ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'โรงแรม' : 'Hotel' ?></label>
                        <input type="text" name="pickup_hotel" value="<?= htmlspecialchars($booking['pickup_hotel'] ?? '') ?>">
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'ห้อง' : 'Room' ?></label>
                        <input type="text" name="pickup_room" value="<?= htmlspecialchars($booking['pickup_room'] ?? '') ?>">
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'เวลารับ' : 'Pickup Time' ?></label>
                        <input type="time" name="pickup_time" value="<?= htmlspecialchars($booking['pickup_time'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Section 3: Items -->
            <div class="bk-card">
                <h3><i class="fa fa-list"></i> <?= $isThai ? 'รายการ' : 'Items' ?></h3>

                <table class="dyn-table" id="itemsTable">
                    <thead>
                        <tr>
                            <th style="width:120px;"><?= $isThai ? 'ประเภท' : 'Type' ?></th>
                            <th><?= $isThai ? 'รายละเอียด' : 'Description' ?></th>
                            <th style="width:60px;"><?= $isThai ? 'จำนวน' : 'Qty' ?></th>
                            <th style="width:110px;"><?= $isThai ? 'ราคาต่อหน่วย' : 'Unit Price' ?></th>
                            <th style="width:110px;"><?= $isThai ? 'ยอดรวม' : 'Amount' ?></th>
                            <th style="width:32px;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $idx => $item): ?>
                            <tr class="item-row">
                                <td>
                                    <select name="item_type[]">
                                        <?php foreach ($itemTypes as $k => $l): ?>
                                        <option value="<?= $k ?>" <?= $item['item_type'] === $k ? 'selected' : '' ?>><?= $l ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="item_contract_rate_id[]" value="<?= intval($item['contract_rate_id'] ?? 0) ?>">
                                    <input type="hidden" name="item_rate_label[]" value="<?= htmlspecialchars($item['rate_label'] ?? '') ?>">
                                    <input type="hidden" name="item_notes[]" value="<?= htmlspecialchars($item['notes'] ?? '') ?>">
                                </td>
                                <td><input type="text" name="item_description[]" value="<?= htmlspecialchars($item['description']) ?>"></td>
                                <td><input type="number" name="item_quantity[]" class="num-input" min="1" value="<?= intval($item['quantity']) ?>" onchange="calcRow(this)"></td>
                                <td><input type="number" name="item_unit_price[]" class="num-input" step="0.01" min="0" value="<?= number_format($item['unit_price'], 2, '.', '') ?>" onchange="calcRow(this)"></td>
                                <td><input type="text" name="item_amount[]" class="num-input" value="<?= number_format($item['amount'], 2, '.', '') ?>" readonly tabindex="-1" style="background:#f8fafc;"></td>
                                <td><button type="button" class="btn-rm-row" onclick="removeRow(this, 'item')"><i class="fa fa-times"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="item-row">
                                <td>
                                    <select name="item_type[]">
                                        <?php foreach ($itemTypes as $k => $l): ?>
                                        <option value="<?= $k ?>"><?= $l ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="item_contract_rate_id[]" value="0">
                                    <input type="hidden" name="item_rate_label[]" value="">
                                    <input type="hidden" name="item_notes[]" value="">
                                </td>
                                <td><input type="text" name="item_description[]" value=""></td>
                                <td><input type="number" name="item_quantity[]" class="num-input" min="1" value="1" onchange="calcRow(this)"></td>
                                <td><input type="number" name="item_unit_price[]" class="num-input" step="0.01" min="0" value="0.00" onchange="calcRow(this)"></td>
                                <td><input type="text" name="item_amount[]" class="num-input" value="0.00" readonly tabindex="-1" style="background:#f8fafc;"></td>
                                <td><button type="button" class="btn-rm-row" onclick="removeRow(this, 'item')"><i class="fa fa-times"></i></button></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <button type="button" class="btn-add-row" onclick="addItemRow()"><i class="fa fa-plus"></i> <?= $isThai ? 'เพิ่มรายการ' : 'Add Item' ?></button>

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
                    <div class="value"><input type="number" name="total_amount" id="total_amount" class="num-input grand" step="0.01" value="<?= number_format($booking['total_amount'] ?? 0, 2, '.', '') ?>" readonly tabindex="-1" style="background:#f0fdfa; border-color:#0d9488; font-weight:700; color:#0d9488;"></div>
                </div>
            </div>

            <!-- Section 4: Passengers -->
            <div class="bk-card">
                <h3><i class="fa fa-users"></i> <?= $isThai ? 'ผู้เดินทาง' : 'Passengers' ?></h3>

                <table class="dyn-table" id="paxTable">
                    <thead>
                        <tr>
                            <th style="width:100px;"><?= $isThai ? 'ประเภท' : 'Type' ?></th>
                            <th><?= $isThai ? 'ชื่อ-นามสกุล' : 'Full Name' ?></th>
                            <th style="width:120px;"><?= $isThai ? 'สัญชาติ' : 'Nationality' ?></th>
                            <th style="width:140px;"><?= $isThai ? 'เลขพาสปอร์ต' : 'Passport #' ?></th>
                            <th style="width:32px;"></th>
                        </tr>
                    </thead>
                    <tbody id="paxBody">
                        <?php if (!empty($pax)): ?>
                            <?php foreach ($pax as $p): ?>
                            <tr class="pax-row">
                                <td>
                                    <select name="pax_type[]">
                                        <?php foreach ($paxTypes as $k => $l): ?>
                                        <option value="<?= $k ?>" <?= $p['pax_type'] === $k ? 'selected' : '' ?>><?= $l ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="pax_notes[]" value="<?= htmlspecialchars($p['notes'] ?? '') ?>">
                                </td>
                                <td><input type="text" name="pax_full_name[]" value="<?= htmlspecialchars($p['full_name']) ?>"></td>
                                <td><input type="text" name="pax_nationality[]" value="<?= htmlspecialchars($p['nationality'] ?? '') ?>"></td>
                                <td><input type="text" name="pax_passport[]" value="<?= htmlspecialchars($p['passport_number'] ?? '') ?>"></td>
                                <td><button type="button" class="btn-rm-row" onclick="removeRow(this, 'pax')"><i class="fa fa-times"></i></button></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr class="pax-row">
                                <td>
                                    <select name="pax_type[]">
                                        <?php foreach ($paxTypes as $k => $l): ?>
                                        <option value="<?= $k ?>"><?= $l ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="pax_notes[]" value="">
                                </td>
                                <td><input type="text" name="pax_full_name[]" value=""></td>
                                <td><input type="text" name="pax_nationality[]" value=""></td>
                                <td><input type="text" name="pax_passport[]" value=""></td>
                                <td><button type="button" class="btn-rm-row" onclick="removeRow(this, 'pax')"><i class="fa fa-times"></i></button></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <button type="button" class="btn-add-row" onclick="addPaxRow()"><i class="fa fa-plus"></i> <?= $isThai ? 'เพิ่มผู้เดินทาง' : 'Add Passenger' ?></button>
            </div>

            <!-- Section 5: Remark -->
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

<script>
// ─── Item Type options template ────────────────────────────
var itemTypeOptions = <?= json_encode(array_map(function($k, $l) { return '<option value="' . $k . '">' . $l . '</option>'; }, array_keys($itemTypes), $itemTypes)) ?>;
var paxTypeOptions = <?= json_encode(array_map(function($k, $l) { return '<option value="' . $k . '">' . $l . '</option>'; }, array_keys($paxTypes), $paxTypes)) ?>;

function addItemRow() {
    var tbody = document.getElementById('itemsBody');
    var tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.innerHTML = '<td><select name="item_type[]">' + itemTypeOptions.join('') + '</select>' +
        '<input type="hidden" name="item_contract_rate_id[]" value="0">' +
        '<input type="hidden" name="item_rate_label[]" value="">' +
        '<input type="hidden" name="item_notes[]" value=""></td>' +
        '<td><input type="text" name="item_description[]" value=""></td>' +
        '<td><input type="number" name="item_quantity[]" class="num-input" min="1" value="1" onchange="calcRow(this)"></td>' +
        '<td><input type="number" name="item_unit_price[]" class="num-input" step="0.01" min="0" value="0.00" onchange="calcRow(this)"></td>' +
        '<td><input type="text" name="item_amount[]" class="num-input" value="0.00" readonly tabindex="-1" style="background:#f8fafc;"></td>' +
        '<td><button type="button" class="btn-rm-row" onclick="removeRow(this,\'item\')"><i class="fa fa-times"></i></button></td>';
    tbody.appendChild(tr);
}

function addPaxRow() {
    var tbody = document.getElementById('paxBody');
    var tr = document.createElement('tr');
    tr.className = 'pax-row';
    tr.innerHTML = '<td><select name="pax_type[]">' + paxTypeOptions.join('') + '</select>' +
        '<input type="hidden" name="pax_notes[]" value=""></td>' +
        '<td><input type="text" name="pax_full_name[]" value=""></td>' +
        '<td><input type="text" name="pax_nationality[]" value=""></td>' +
        '<td><input type="text" name="pax_passport[]" value=""></td>' +
        '<td><button type="button" class="btn-rm-row" onclick="removeRow(this,\'pax\')"><i class="fa fa-times"></i></button></td>';
    tbody.appendChild(tr);
}

function removeRow(btn, type) {
    var tbody = type === 'item' ? document.getElementById('itemsBody') : document.getElementById('paxBody');
    if (tbody.rows.length > 1) {
        btn.closest('tr').remove();
        if (type === 'item') calcTotals();
    }
}

function calcRow(el) {
    var row = el.closest('tr');
    var qty = parseFloat(row.querySelector('[name="item_quantity[]"]').value) || 0;
    var price = parseFloat(row.querySelector('[name="item_unit_price[]"]').value) || 0;
    row.querySelector('[name="item_amount[]"]').value = (qty * price).toFixed(2);
    calcTotals();
}

function calcTotals() {
    var amounts = document.querySelectorAll('[name="item_amount[]"]');
    var sub = 0;
    amounts.forEach(function(el) { sub += parseFloat(el.value) || 0; });

    var entrance = parseFloat(document.getElementById('entrance_fee').value) || 0;
    var discount = parseFloat(document.getElementById('discount').value) || 0;
    var vat = parseFloat(document.getElementById('vat').value) || 0;

    document.getElementById('subtotal').value = sub.toFixed(2);
    var total = sub + entrance - discount + vat;
    document.getElementById('total_amount').value = total.toFixed(2);
}

// Initial calc on page load
document.addEventListener('DOMContentLoaded', function() { calcTotals(); });
</script>
