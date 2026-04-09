<?php
/**
 * Quick Create — Entry Point Selector
 * Choose: Quotation, Invoice, or Tax Invoice
 */
global $xml;
$isThaiLang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1);
?>

<div class="quick-create-page">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
.quick-create-page {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 16px;
}

.quick-create-page .page-header-qc {
    background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    color: white;
    padding: 28px 32px;
    border-radius: 16px;
    margin-bottom: 32px;
    box-shadow: 0 10px 40px rgba(245, 158, 11, 0.25);
}

.quick-create-page .page-header-qc h2 {
    margin: 0;
    font-size: 26px;
    font-weight: 700;
}

.quick-create-page .page-header-qc p {
    margin: 8px 0 0 0;
    opacity: 0.9;
    font-size: 14px;
}

.quick-create-page .entry-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
}

.quick-create-page .entry-card {
    background: white;
    border-radius: 16px;
    padding: 32px 28px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    border: 2px solid transparent;
    transition: all 0.3s;
    text-decoration: none;
    color: inherit;
    display: block;
    position: relative;
    overflow: hidden;
}

.quick-create-page .entry-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.12);
    text-decoration: none;
    color: inherit;
}

.quick-create-page .entry-card.card-quotation { border-color: #667eea; }
.quick-create-page .entry-card.card-quotation:hover { box-shadow: 0 12px 40px rgba(102, 126, 234, 0.2); }
.quick-create-page .entry-card.card-invoice { border-color: #10b981; }
.quick-create-page .entry-card.card-invoice:hover { box-shadow: 0 12px 40px rgba(16, 185, 129, 0.2); }
.quick-create-page .entry-card.card-tax { border-color: #f59e0b; }
.quick-create-page .entry-card.card-tax:hover { box-shadow: 0 12px 40px rgba(245, 158, 11, 0.2); }

.quick-create-page .entry-card .card-icon {
    width: 56px;
    height: 56px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    margin-bottom: 20px;
}

.quick-create-page .card-quotation .card-icon { background: rgba(102, 126, 234, 0.1); color: #667eea; }
.quick-create-page .card-invoice .card-icon { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.quick-create-page .card-tax .card-icon { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }

.quick-create-page .entry-card h3 {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 8px 0;
    color: #1f2937;
}

.quick-create-page .entry-card .card-desc {
    font-size: 13px;
    color: #6b7280;
    margin: 0 0 20px 0;
    line-height: 1.5;
}

.quick-create-page .entry-card .auto-badge {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #f3f4f6;
    color: #4b5563;
    padding: 6px 12px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

.quick-create-page .entry-card .auto-badge i { font-size: 10px; }

.quick-create-page .entry-card .card-arrow {
    position: absolute;
    right: 24px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 20px;
    color: #d1d5db;
    transition: all 0.3s;
}

.quick-create-page .entry-card:hover .card-arrow {
    color: #6b7280;
    right: 20px;
}

.quick-create-page .flow-diagram {
    background: #f8fafc;
    border-radius: 12px;
    padding: 24px;
    margin-top: 32px;
    border: 1px solid #e2e8f0;
}

.quick-create-page .flow-diagram h4 {
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin: 0 0 16px 0;
}

.quick-create-page .flow-steps {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}

.quick-create-page .flow-step {
    background: white;
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 12px;
    font-weight: 500;
    border: 1px solid #e5e7eb;
}

.quick-create-page .flow-step.auto { background: #fef3c7; border-color: #fcd34d; color: #92400e; }
.quick-create-page .flow-step.manual { background: #dbeafe; border-color: #93c5fd; color: #1e40af; }
.quick-create-page .flow-arrow { color: #9ca3af; font-size: 14px; }

@media (max-width: 576px) {
    .quick-create-page .entry-cards { grid-template-columns: 1fr; }
    .quick-create-page .flow-steps { flex-direction: column; align-items: flex-start; }
    .quick-create-page .flow-arrow { transform: rotate(90deg); }
}
</style>

<?php if (!empty($_SESSION['flash_success'])): ?>
    <div class="alert alert-success alert-dismissible" style="border-radius:12px;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fa fa-check-circle"></i> <?= htmlspecialchars($_SESSION['flash_success']) ?>
    </div>
    <?php unset($_SESSION['flash_success']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
    <div class="alert alert-danger alert-dismissible" style="border-radius:12px;">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <i class="fa fa-exclamation-circle"></i> <?= htmlspecialchars($_SESSION['flash_error']) ?>
    </div>
    <?php unset($_SESSION['flash_error']); ?>
<?php endif; ?>

<!-- Header -->
<div class="page-header-qc">
    <h2><i class="fa fa-bolt"></i> <?= $isThaiLang ? 'สร้างเอกสารด่วน' : 'Quick Create' ?></h2>
    <p><?= $isThaiLang ? 'เลือกจุดเริ่มต้น — ระบบจะสร้างเอกสารต้นทางให้อัตโนมัติ' : 'Choose your starting point — the system will auto-generate all upstream documents' ?></p>
</div>

<!-- Entry Point Cards -->
<div class="entry-cards">
    <!-- A: Quotation -->
    <a href="index.php?page=qc_quotation" class="entry-card card-quotation">
        <div class="card-icon"><i class="fa fa-file-text-o"></i></div>
        <h3><?= $isThaiLang ? 'เริ่มจากใบเสนอราคา' : 'Start from Quotation' ?></h3>
        <p class="card-desc"><?= $isThaiLang ? 'กรอกข้อมูลใบเสนอราคา ระบบจะสร้างใบขอซื้อ (PR) ให้อัตโนมัติ' : 'Fill in Quotation details. System will auto-create Purchase Request (PR).' ?></p>
        <span class="auto-badge"><i class="fa fa-magic"></i> <?= $isThaiLang ? 'สร้างอัตโนมัติ: PR' : 'Auto-creates: PR' ?></span>
        <span class="card-arrow"><i class="fa fa-chevron-right"></i></span>
    </a>

    <!-- B: Invoice -->
    <a href="index.php?page=qc_invoice" class="entry-card card-invoice">
        <div class="card-icon"><i class="fa fa-file-text"></i></div>
        <h3><?= $isThaiLang ? 'เริ่มจากใบแจ้งหนี้' : 'Start from Invoice' ?></h3>
        <p class="card-desc"><?= $isThaiLang ? 'กรอกข้อมูลใบแจ้งหนี้ ระบบจะสร้าง PR, PO, ใบส่งของ ให้อัตโนมัติ' : 'Fill in Invoice details. System will auto-create PR, PO, and Delivery.' ?></p>
        <span class="auto-badge"><i class="fa fa-magic"></i> <?= $isThaiLang ? 'สร้างอัตโนมัติ: PR → PO → ใบส่ง' : 'Auto-creates: PR → PO → Delivery' ?></span>
        <span class="card-arrow"><i class="fa fa-chevron-right"></i></span>
    </a>

    <!-- C: Tax Invoice -->
    <a href="index.php?page=qc_tax_invoice" class="entry-card card-tax">
        <div class="card-icon"><i class="fa fa-file"></i></div>
        <h3><?= $isThaiLang ? 'เริ่มจากใบกำกับภาษี' : 'Start from Tax Invoice' ?></h3>
        <p class="card-desc"><?= $isThaiLang ? 'กรอกข้อมูลใบกำกับภาษี ระบบจะสร้าง PR, PO, ใบส่งของ, ใบแจ้งหนี้ ให้อัตโนมัติ' : 'Fill in Tax Invoice details. System will auto-create PR, PO, Delivery, and Invoice.' ?></p>
        <span class="auto-badge"><i class="fa fa-magic"></i> <?= $isThaiLang ? 'สร้างอัตโนมัติ: PR → PO → ใบส่ง → ใบแจ้งหนี้' : 'Auto-creates: PR → PO → Delivery → Invoice' ?></span>
        <span class="card-arrow"><i class="fa fa-chevron-right"></i></span>
    </a>
</div>

<!-- Flow Diagram -->
<div class="flow-diagram">
    <h4><i class="fa fa-info-circle"></i> <?= $isThaiLang ? 'แผนภาพขั้นตอนเอกสาร' : 'Document Flow Diagram' ?></h4>
    <div class="flow-steps">
        <span class="flow-step">PR</span>
        <span class="flow-arrow"><i class="fa fa-long-arrow-right"></i></span>
        <span class="flow-step">PO / Quotation</span>
        <span class="flow-arrow"><i class="fa fa-long-arrow-right"></i></span>
        <span class="flow-step">Delivery</span>
        <span class="flow-arrow"><i class="fa fa-long-arrow-right"></i></span>
        <span class="flow-step">Invoice</span>
        <span class="flow-arrow"><i class="fa fa-long-arrow-right"></i></span>
        <span class="flow-step">Tax Invoice</span>
        <span class="flow-arrow"><i class="fa fa-long-arrow-right"></i></span>
        <span class="flow-step">Receipt</span>
    </div>
    <p style="margin: 16px 0 0 0; font-size: 12px; color: #6b7280;">
        <span class="flow-step auto" style="display:inline;padding:2px 8px;"><?= $isThaiLang ? 'อัตโนมัติ' : 'Auto' ?></span> = <?= $isThaiLang ? 'สร้างโดยระบบ' : 'Created by system' ?> &nbsp;
        <span class="flow-step manual" style="display:inline;padding:2px 8px;"><?= $isThaiLang ? 'สร้างเอง' : 'Manual' ?></span> = <?= $isThaiLang ? 'ผู้ใช้สร้างเอง' : 'Created by user' ?>
    </p>
</div>

</div>
