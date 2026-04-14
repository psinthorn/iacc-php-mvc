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

/* Customer autocomplete */
.ac-wrap { position: relative; }
.ac-wrap input[type="text"] { width: 100%; }
.ac-list { position: absolute; top: 100%; left: 0; right: 0; z-index: 100; background: white; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 8px 8px; max-height: 240px; overflow-y: auto; box-shadow: 0 8px 20px rgba(0,0,0,0.1); display: none; }
.ac-list.show { display: block; }
.ac-item { padding: 8px 12px; font-size: 13px; cursor: pointer; border-bottom: 1px solid #f8fafc; }
.ac-item:hover, .ac-item.active { background: #f0fdfa; color: #0d9488; }
.ac-item .sub { font-size: 11px; color: #94a3b8; }
.ac-item.create-new { color: #0d9488; font-weight: 600; border-top: 1px solid #e2e8f0; }
.ac-item.create-new i { margin-right: 4px; }
.ac-selected { display: inline-flex; align-items: center; gap: 8px; padding: 4px 10px; background: #f0fdfa; border: 1px solid #0d9488; border-radius: 8px; font-size: 13px; color: #0d9488; font-weight: 600; }
.ac-selected .ac-clear { cursor: pointer; color: #ef4444; font-size: 14px; }

/* Quick create modal */
.qc-overlay { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); z-index: 10050; }
.qc-overlay.show { display: flex; align-items: center; justify-content: center; }
.qc-modal { background: white; border-radius: 14px; padding: 28px; width: 400px; max-width: 90vw; }
.qc-modal h4 { margin: 0 0 16px; font-size: 16px; color: #1e293b; }
.qc-modal .bk-field { margin-bottom: 12px; }
.qc-modal .qc-actions { display: flex; gap: 8px; justify-content: flex-end; margin-top: 16px; }
.qc-modal .qc-btn { padding: 8px 20px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; }
.qc-modal .qc-btn-save { background: #0d9488; color: white; }
.qc-modal .qc-btn-cancel { background: #f1f5f9; color: #64748b; }
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
                        <input type="hidden" name="customer_id" id="customer_id" value="<?= intval($booking['customer_id'] ?? 0) ?>">
                        <?php
                        $preselectedName = '';
                        if (!empty($booking['customer_id'])) {
                            foreach ($customers as $c) {
                                if (intval($c['id']) === intval($booking['customer_id'])) {
                                    $preselectedName = $c['name_en'];
                                    break;
                                }
                            }
                        }
                        ?>
                        <div class="ac-wrap" id="customerAcWrap">
                            <?php if (!empty($preselectedName)): ?>
                            <div class="ac-selected" id="customerSelected">
                                <?= htmlspecialchars($preselectedName) ?>
                                <span class="ac-clear" onclick="clearCustomer()">&times;</span>
                            </div>
                            <input type="text" id="customerSearch" placeholder="<?= $isThai ? 'พิมพ์ค้นหาลูกค้า...' : 'Type to search customer...' ?>" style="display:none;" autocomplete="off">
                            <?php else: ?>
                            <div class="ac-selected" id="customerSelected" style="display:none;"></div>
                            <input type="text" id="customerSearch" placeholder="<?= $isThai ? 'พิมพ์ค้นหาลูกค้า...' : 'Type to search customer...' ?>" autocomplete="off">
                            <?php endif; ?>
                            <div class="ac-list" id="customerAcList"></div>
                        </div>
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
                        <div class="ac-wrap">
                            <input type="text" name="booking_by" id="bookingBySearch" autocomplete="off"
                                   value="<?= htmlspecialchars($booking['booking_by'] ?? '') ?>"
                                   placeholder="<?= $isThai ? 'พิมพ์ชื่อพนักงาน...' : 'Type staff name...' ?>">
                            <div class="ac-list" id="bookingByAcList"></div>
                        </div>
                    </div>
                </div>

                <div class="bk-row cols-4">
                    <div class="bk-field">
                        <label><?= $isThai ? 'ผู้ใหญ่' : 'Adults' ?></label>
                        <input type="number" name="pax_adult" min="0" value="<?= intval($booking['pax_adult'] ?? 0) ?>" onchange="syncPaxQty()">
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'เด็ก' : 'Children' ?></label>
                        <input type="number" name="pax_child" min="0" value="<?= intval($booking['pax_child'] ?? 0) ?>" onchange="syncPaxQty()">
                    </div>
                    <div class="bk-field">
                        <label><?= $isThai ? 'ทารก' : 'Infants' ?></label>
                        <input type="number" name="pax_infant" min="0" value="<?= intval($booking['pax_infant'] ?? 0) ?>" onchange="syncPaxQty()">
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

                <div style="margin-bottom:12px; padding:8px 14px; background:#f0fdfa; border-radius:8px; font-size:12px; color:#0d9488;">
                    <i class="fa fa-info-circle"></i>
                    <?= $isThai ? 'จำนวนผู้เดินทาง' : 'Total Pax' ?>: <strong id="displayPaxCount">0</strong>
                </div>

                <table class="dyn-table" id="itemsTable">
                    <thead>
                        <tr>
                            <th style="width:120px;"><?= $isThai ? 'ประเภท' : 'Type' ?></th>
                            <th><?= $isThai ? 'รายละเอียด' : 'Description' ?></th>
                            <th style="width:110px;"><?= $isThai ? 'ราคา (ไทย)' : 'Price (Thai)' ?></th>
                            <th style="width:110px;"><?= $isThai ? 'ราคา (ต่างชาติ)' : 'Price (Foreign)' ?></th>
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
                                    <input type="hidden" name="item_qty_thai[]" class="qty-thai" value="<?= intval($item['qty_thai'] ?? 0) ?>">
                                    <input type="hidden" name="item_qty_foreigner[]" class="qty-foreigner" value="<?= intval($item['qty_foreigner'] ?? 0) ?>">
                                </td>
                                <td><div class="ac-wrap"><input type="text" name="item_description[]" class="prod-search" autocomplete="off" value="<?= htmlspecialchars($item['description']) ?>" placeholder="<?= $isThai ? 'ค้นหาสินค้า...' : 'Search product...' ?>"><div class="ac-list prod-ac-list"></div></div></td>
                                <td><input type="number" name="item_price_thai[]" class="num-input price-thai" step="0.01" min="0" value="<?= number_format(floatval($item['price_thai'] ?? 0), 2, '.', '') ?>" onchange="calcRow(this)"></td>
                                <td><input type="number" name="item_price_foreigner[]" class="num-input price-foreign" step="0.01" min="0" value="<?= number_format(floatval($item['price_foreigner'] ?? 0), 2, '.', '') ?>" onchange="calcRow(this)"></td>
                                <td><input type="text" name="item_amount[]" class="num-input" value="<?= number_format(floatval($item['amount'] ?? 0), 2, '.', '') ?>" readonly tabindex="-1" style="background:#f8fafc;"></td>
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
                                    <input type="hidden" name="item_qty_thai[]" class="qty-thai" value="0">
                                    <input type="hidden" name="item_qty_foreigner[]" class="qty-foreigner" value="0">
                                </td>
                                <td><div class="ac-wrap"><input type="text" name="item_description[]" class="prod-search" autocomplete="off" value="" placeholder="<?= $isThai ? 'ค้นหาสินค้า...' : 'Search product...' ?>"><div class="ac-list prod-ac-list"></div></div></td>
                                <td><input type="number" name="item_price_thai[]" class="num-input price-thai" step="0.01" min="0" value="0.00" onchange="calcRow(this)"></td>
                                <td><input type="number" name="item_price_foreigner[]" class="num-input price-foreign" step="0.01" min="0" value="0.00" onchange="calcRow(this)"></td>
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
    <div class="qc-modal">
        <h4><i class="fa fa-user-plus" style="color:#0d9488;"></i> <?= $isThai ? 'สร้างลูกค้าใหม่' : 'Create New Customer' ?></h4>
        <div class="bk-field">
            <label><?= $isThai ? 'ชื่อลูกค้า' : 'Customer Name' ?> <span class="req">*</span></label>
            <input type="text" id="qcName" placeholder="<?= $isThai ? 'ชื่อลูกค้า' : 'Customer name' ?>">
        </div>
        <div class="bk-field">
            <label><?= $isThai ? 'เบอร์โทร' : 'Phone' ?></label>
            <input type="text" id="qcPhone" placeholder="<?= $isThai ? 'เบอร์โทรศัพท์' : 'Phone number' ?>">
        </div>
        <div class="qc-actions">
            <button type="button" class="qc-btn qc-btn-cancel" onclick="closeQcModal()"><?= $isThai ? 'ยกเลิก' : 'Cancel' ?></button>
            <button type="button" class="qc-btn qc-btn-save" onclick="saveQuickCustomer()"><?= $isThai ? 'สร้าง' : 'Create' ?></button>
        </div>
    </div>
</div>

<script>
// ─── Config ────────────────────────────────────────────────
var itemTypeOptions = <?= json_encode(array_map(function($k, $l) { return '<option value="' . $k . '">' . $l . '</option>'; }, array_keys($itemTypes), $itemTypes)) ?>;
var paxTypeOptions = <?= json_encode(array_map(function($k, $l) { return '<option value="' . $k . '">' . $l . '</option>'; }, array_keys($paxTypes), $paxTypes)) ?>;
var csrfToken = '<?= csrf_token() ?>';

// ─── Item Rows ─────────────────────────────────────────────
function addItemRow() {
    var tbody = document.getElementById('itemsBody');
    var tr = document.createElement('tr');
    tr.className = 'item-row';
    tr.innerHTML = '<td><select name="item_type[]">' + itemTypeOptions.join('') + '</select>' +
        '<input type="hidden" name="item_contract_rate_id[]" value="0">' +
        '<input type="hidden" name="item_rate_label[]" value="">' +
        '<input type="hidden" name="item_notes[]" value="">' +
        '<input type="hidden" name="item_qty_thai[]" class="qty-thai" value="0">' +
        '<input type="hidden" name="item_qty_foreigner[]" class="qty-foreigner" value="0"></td>' +
        '<td><div class="ac-wrap"><input type="text" name="item_description[]" class="prod-search" autocomplete="off" value="" placeholder="<?= $isThai ? 'ค้นหาสินค้า...' : 'Search product...' ?>"><div class="ac-list prod-ac-list"></div></div></td>' +
        '<td><input type="number" name="item_price_thai[]" class="num-input price-thai" step="0.01" min="0" value="0.00" onchange="calcRow(this)"></td>' +
        '<td><input type="number" name="item_price_foreigner[]" class="num-input price-foreign" step="0.01" min="0" value="0.00" onchange="calcRow(this)"></td>' +
        '<td><input type="text" name="item_amount[]" class="num-input" value="0.00" readonly tabindex="-1" style="background:#f8fafc;"></td>' +
        '<td><button type="button" class="btn-rm-row" onclick="removeRow(this,\'item\')"><i class="fa fa-times"></i></button></td>';
    tbody.appendChild(tr);
    syncPaxQty(); // sync qty fields
}

function removeRow(btn, type) {
    if (type !== 'item') return;
    var tbody = document.getElementById('itemsBody');
    if (tbody.rows.length > 1) {
        btn.closest('tr').remove();
        calcTotals();
    }
}

// ─── Pax Qty Sync (from header pax fields) ─────────
function syncPaxQty() {
    var adults = parseInt(document.querySelector('[name="pax_adult"]').value) || 0;
    var children = parseInt(document.querySelector('[name="pax_child"]').value) || 0;
    var infants = parseInt(document.querySelector('[name="pax_infant"]').value) || 0;
    var totalCount = adults + children + infants;

    document.getElementById('displayPaxCount').textContent = totalCount;

    // Update hidden qty fields on all item rows
    document.querySelectorAll('#itemsBody .qty-thai').forEach(function(el) { el.value = totalCount; });
    document.querySelectorAll('#itemsBody .qty-foreigner').forEach(function(el) { el.value = totalCount; });

    // Recalculate all item amounts
    document.querySelectorAll('#itemsBody .item-row').forEach(function(row) {
        var priceThai = parseFloat(row.querySelector('.price-thai').value) || 0;
        var priceForeign = parseFloat(row.querySelector('.price-foreign').value) || 0;
        var amount = (totalCount * priceThai) + (totalCount * priceForeign);
        row.querySelector('[name="item_amount[]"]').value = amount.toFixed(2);
    });

    calcTotals();
}

// ─── Row Calculation ───────────────────────────────────────
function calcRow(el) {
    // When a price changes, recalculate using pax counts
    syncPaxQty();
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

// ─── Customer Autocomplete ─────────────────────────────────
var acTimer = null;
var acInput = document.getElementById('customerSearch');
var acList = document.getElementById('customerAcList');

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
                        html += '<div class="ac-item" onclick="selectCustomer(' + c.id + ', \'' + escHtml(c.name_en) + '\')">'
                            + escHtml(c.name_en)
                            + (sub ? ' <span class="sub">' + escHtml(sub) + '</span>' : '')
                            + '</div>';
                    });
                    html += '<div class="ac-item create-new" onclick="openQcModal()"><i class="fa fa-plus"></i> <?= $isThai ? "สร้างลูกค้าใหม่" : "Create New Customer" ?></div>';
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

function selectCustomer(id, name) {
    document.getElementById('customer_id').value = id;
    var sel = document.getElementById('customerSelected');
    sel.innerHTML = escHtml(name) + ' <span class="ac-clear" onclick="clearCustomer()">&times;</span>';
    sel.style.display = '';
    acInput.style.display = 'none';
    acList.classList.remove('show');
}

function clearCustomer() {
    document.getElementById('customer_id').value = '0';
    document.getElementById('customerSelected').style.display = 'none';
    acInput.value = '';
    acInput.style.display = '';
    acInput.focus();
}

function escHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

// ─── Quick Create Customer Modal ───────────────────────────
function openQcModal() {
    acList.classList.remove('show');
    document.getElementById('qcName').value = acInput.value.trim();
    document.getElementById('qcPhone').value = '';
    document.getElementById('qcOverlay').classList.add('show');
    document.getElementById('qcName').focus();
}

function closeQcModal() {
    document.getElementById('qcOverlay').classList.remove('show');
}

function saveQuickCustomer() {
    var name = document.getElementById('qcName').value.trim();
    var phone = document.getElementById('qcPhone').value.trim();
    if (!name) { document.getElementById('qcName').focus(); return; }

    fetch('index.php?page=tour_booking_customer_create', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'name=' + encodeURIComponent(name) + '&phone=' + encodeURIComponent(phone) + '&csrf_token=' + encodeURIComponent(csrfToken)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            selectCustomer(data.id, data.name);
            closeQcModal();
        }
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

// ─── Product Search (event delegation for dynamic rows) ────
var prodTimer = null;

document.getElementById('itemsBody').addEventListener('input', function(e) {
    if (!e.target.classList.contains('prod-search')) return;
    clearTimeout(prodTimer);
    var input = e.target;
    var acList = input.nextElementSibling;
    var q = input.value.trim();
    if (q.length < 1) { acList.classList.remove('show'); return; }
    prodTimer = setTimeout(function() {
        fetch('index.php?page=tour_booking_product_search&q=' + encodeURIComponent(q))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var html = '';
                data.forEach(function(p) {
                    var sub = [p.category_name, p.des].filter(Boolean).join(' | ');
                    var priceAttr = parseFloat(p.price) > 0 ? ' data-price="' + parseFloat(p.price).toFixed(2) + '"' : '';
                    var priceTag = parseFloat(p.price) > 0 ? ' <span class="sub" style="color:#0d9488;font-weight:600;">฿' + parseFloat(p.price).toLocaleString() + '</span>' : '';
                    html += '<div class="ac-item" data-name="' + escHtml(p.name) + '"' + priceAttr + '>'
                        + escHtml(p.name) + priceTag
                        + (sub ? ' <span class="sub">' + escHtml(sub) + '</span>' : '')
                        + '</div>';
                });
                if (!html) html = '<div class="ac-item" style="color:#999;pointer-events:none;"><?= $isThai ? "ไม่พบสินค้า" : "No products found" ?></div>';
                acList.innerHTML = html;
                acList.classList.add('show');
            });
    }, 250);
});

document.getElementById('itemsBody').addEventListener('click', function(e) {
    var item = e.target.closest('.ac-item');
    if (!item || !item.dataset.name) return;
    var acList = item.closest('.prod-ac-list');
    if (!acList) return;
    var input = acList.previousElementSibling;
    var row = item.closest('tr');
    input.value = item.dataset.name;
    acList.classList.remove('show');
    // Auto-fill price if available
    if (item.dataset.price && row) {
        var priceThai = row.querySelector('.price-thai');
        if (priceThai && (parseFloat(priceThai.value) === 0 || !priceThai.value)) {
            priceThai.value = item.dataset.price;
            syncPaxQty();
        }
    }
});

document.getElementById('itemsBody').addEventListener('focusin', function(e) {
    if (!e.target.classList.contains('prod-search')) return;
    var acList = e.target.nextElementSibling;
    if (e.target.value.trim().length >= 1 && acList.innerHTML) acList.classList.add('show');
});

document.getElementById('itemsBody').addEventListener('focusout', function(e) {
    if (!e.target.classList.contains('prod-search')) return;
    var acList = e.target.nextElementSibling;
    setTimeout(function() { acList.classList.remove('show'); }, 200);
});

// ─── Init ──────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    syncPaxQty();
});
</script>
