<?php
/**
 * Bulk Toolbar Partial
 * Include once per list page. BulkSelect.init() populates the action buttons.
 */
?>
<style>
.bulk-toolbar {
    position: fixed; bottom: -80px; left: 50%; transform: translateX(-50%);
    z-index: 1050; display: flex; align-items: center; gap: 12px;
    background: #1e293b; color: #f8fafc; padding: 12px 20px;
    border-radius: 12px; box-shadow: 0 8px 30px rgba(0,0,0,0.25);
    min-width: 320px; transition: bottom 0.28s cubic-bezier(0.34,1.56,0.64,1);
    white-space: nowrap;
}
.bulk-toolbar--visible { bottom: 28px; }
.bulk-toolbar__count {
    font-size: 13px; font-weight: 700;
    background: rgba(255,255,255,0.12); padding: 3px 10px;
    border-radius: 20px; min-width: 90px; text-align: center;
}
.bulk-toolbar__actions { display: flex; gap: 8px; flex-wrap: wrap; }
.bulk-toolbar__close {
    margin-left: auto; background: none; border: none; color: #94a3b8;
    font-size: 16px; cursor: pointer; padding: 0 4px; line-height: 1; transition: color 0.15s;
}
.bulk-toolbar__close:hover { color: #f8fafc; }
tr.bulk-selected { background-color: #eff6ff !important; }
tr.bulk-selected td { border-color: #bfdbfe; }
.bulk-col { width: 36px; text-align: center; padding-left: 12px !important; }
.bulk-select-all, .bulk-select-row { width: 16px; height: 16px; cursor: pointer; accent-color: #0d9488; }
@media (max-width: 600px) {
    .bulk-toolbar {
        left: 0; right: 0; bottom: -80px; transform: none;
        border-radius: 0; min-width: unset; flex-wrap: wrap;
        padding-bottom: calc(12px + env(safe-area-inset-bottom));
    }
    .bulk-toolbar--visible { bottom: 0; }
}
</style>

<!-- Toolbar -->
<div class="bulk-toolbar" role="toolbar" aria-label="Bulk actions" aria-live="polite">
    <span class="bulk-toolbar__count">0 selected</span>
    <div class="bulk-toolbar__actions"></div>
    <button type="button" class="bulk-toolbar__close" title="Deselect all" aria-label="Deselect all">
        <i class="fa fa-times"></i>
    </button>
</div>

<!-- Payment Modal -->
<div id="bulkPaymentModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:2000; align-items:center; justify-content:center;">
    <div style="background:#fff; border-radius:12px; padding:24px; width:100%; max-width:420px; margin:16px; box-shadow:0 20px 60px rgba(0,0,0,0.3);">
        <h5 style="margin:0 0 16px; font-size:16px; font-weight:700; color:#1e293b;">
            <i class="fa fa-money" style="color:#0d9488;"></i> Mark Payment Received
        </h5>
        <div style="display:flex; flex-direction:column; gap:12px;">
            <div>
                <label style="font-size:12px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Amount (THB) *</label>
                <input type="number" id="bulkPayAmount" min="0.01" step="0.01" placeholder="0.00"
                       style="width:100%; padding:8px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; box-sizing:border-box;">
            </div>
            <div>
                <label style="font-size:12px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Payment Method *</label>
                <select id="bulkPayMethod" style="width:100%; padding:8px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; box-sizing:border-box;">
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="credit_card">Credit Card</option>
                    <option value="cheque">Cheque</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label style="font-size:12px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Payment Date</label>
                <input type="date" id="bulkPayDate" style="width:100%; padding:8px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; box-sizing:border-box;">
            </div>
            <div>
                <label style="font-size:12px; font-weight:600; color:#64748b; display:block; margin-bottom:4px;">Notes</label>
                <input type="text" id="bulkPayNotes" placeholder="Optional note..."
                       style="width:100%; padding:8px 12px; border:1px solid #e2e8f0; border-radius:8px; font-size:14px; box-sizing:border-box;">
            </div>
        </div>
        <div style="display:flex; gap:8px; margin-top:20px; justify-content:flex-end;">
            <button type="button" id="bulkPayCancel" class="btn btn-secondary btn-sm">Cancel</button>
            <button type="button" id="bulkPayConfirm" class="btn btn-success btn-sm">
                <i class="fa fa-check"></i> Record Payments
            </button>
        </div>
    </div>
</div>

<script>
// Set today's date as default for payment modal
document.addEventListener('DOMContentLoaded', function () {
    const d = document.getElementById('bulkPayDate');
    if (d) d.value = new Date().toISOString().split('T')[0];
});
</script>
