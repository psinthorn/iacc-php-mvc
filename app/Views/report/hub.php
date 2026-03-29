<?php
/**
 * Reports Hub — Central reports navigation
 * Variables: $com_id, $user_level
 */
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
    .reports-wrapper { max-width: 1400px; margin: 0 auto; padding: 0 20px; font-family: 'Inter', sans-serif; }
    .reports-header { padding: 24px 28px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 16px; box-shadow: 0 10px 40px rgba(102, 126, 234, 0.25); color: white; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; }
    .reports-header h2 { font-size: 24px; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 12px; }
    .reports-header p { font-size: 14px; opacity: 0.9; margin: 5px 0 0; }
    .report-section { margin-bottom: 30px; }
    .report-section h4 { font-size: 16px; font-weight: 600; color: #1f2937; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #e5e7eb; }
    .report-section h4 i { color: #667eea; margin-right: 8px; }
    .report-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    @media (max-width: 992px) { .report-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 576px) { .report-grid { grid-template-columns: 1fr; } }
    .report-card { background: white; border-radius: 12px; padding: 24px; border: 1px solid #e5e7eb; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: all 0.2s ease; text-decoration: none; color: inherit; display: block; }
    .report-card:hover { transform: translateY(-3px); box-shadow: 0 8px 24px rgba(0,0,0,0.12); border-color: #667eea; text-decoration: none; color: inherit; }
    .report-card-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 22px; margin-bottom: 14px; }
    .report-card-icon.blue { background: rgba(102, 126, 234, 0.1); color: #667eea; }
    .report-card-icon.green { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .report-card-icon.orange { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .report-card-icon.red { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
    .report-card-icon.purple { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
    .report-card h5 { font-size: 16px; font-weight: 600; margin: 0 0 6px; color: #1f2937; }
    .report-card p { font-size: 13px; color: #6b7280; margin: 0; line-height: 1.5; }
    .report-card .card-arrow { float: right; color: #9ca3af; transition: color 0.2s; }
    .report-card:hover .card-arrow { color: #667eea; }
</style>

<div class="reports-wrapper">
    <div class="reports-header">
        <h2><i class="fa fa-bar-chart"></i> Reports Center</h2>
        <p>Access all business reports, analytics, and financial data in one place</p>
    </div>

    <!-- Financial Reports -->
    <div class="report-section">
        <h4><i class="fa fa-usd"></i> Financial Reports</h4>
        <div class="report-grid">
            <a href="index.php?page=report" class="report-card">
                <div class="report-card-icon blue"><i class="fa fa-bar-chart-o"></i></div>
                <h5>Business Summary <span class="card-arrow"><i class="fa fa-chevron-right"></i></span></h5>
                <p>Transaction pipeline overview — PR to QA to PO to Invoice with conversion rates per customer.</p>
            </a>

            <a href="index.php?page=invoice_payments" class="report-card">
                <div class="report-card-icon green"><i class="fa fa-money"></i></div>
                <h5>Invoice Payment Tracking <span class="card-arrow"><i class="fa fa-chevron-right"></i></span></h5>
                <p>Track payment status for all invoices. See paid, partial, and unpaid invoices with amounts.</p>
            </a>

            <a href="index.php?page=report_ar_aging" class="report-card">
                <div class="report-card-icon red"><i class="fa fa-clock-o"></i></div>
                <h5>AR Aging Report <span class="card-arrow"><i class="fa fa-chevron-right"></i></span></h5>
                <p>Accounts receivable aging — outstanding invoices bucketed by 30/60/90/120+ days for collections.</p>
            </a>
        </div>
    </div>

    <!-- Expense Reports -->
    <div class="report-section">
        <h4><i class="fa fa-credit-card"></i> Expense Reports</h4>
        <div class="report-grid">
            <a href="index.php?page=expense_summary" class="report-card">
                <div class="report-card-icon orange"><i class="fa fa-pie-chart"></i></div>
                <h5>Expense Summary <span class="card-arrow"><i class="fa fa-chevron-right"></i></span></h5>
                <p>Summary of all expenses by category, status, and time period with totals and VAT/WHT breakdown.</p>
            </a>

            <a href="index.php?page=expense_project_report" class="report-card">
                <div class="report-card-icon purple"><i class="fa fa-folder-open"></i></div>
                <h5>Project Costs <span class="card-arrow"><i class="fa fa-chevron-right"></i></span></h5>
                <p>Expense breakdown by project — track costs allocated to specific projects or departments.</p>
            </a>

            <a href="index.php?page=expense_export" class="report-card">
                <div class="report-card-icon blue"><i class="fa fa-download"></i></div>
                <h5>Export Expenses <span class="card-arrow"><i class="fa fa-chevron-right"></i></span></h5>
                <p>Download expense data as CSV or JSON for external reporting and accounting software.</p>
            </a>
        </div>
    </div>

    <!-- Tax Reports -->
    <?php if ($user_level >= 2): ?>
    <div class="report-section">
        <h4><i class="fa fa-calculator"></i> Tax Reports</h4>
        <div class="report-grid">
            <a href="index.php?page=tax_reports" class="report-card">
                <div class="report-card-icon green"><i class="fa fa-file-text-o"></i></div>
                <h5>Tax Report Dashboard <span class="card-arrow"><i class="fa fa-chevron-right"></i></span></h5>
                <p>Overview of tax reporting including PP30 VAT returns and withholding tax summaries.</p>
            </a>

            <a href="index.php?page=tax_report_pp30" class="report-card">
                <div class="report-card-icon orange"><i class="fa fa-calculator"></i></div>
                <h5>PP30 VAT Return <span class="card-arrow"><i class="fa fa-chevron-right"></i></span></h5>
                <p>Generate PP30 VAT return form data for filing with the Revenue Department.</p>
            </a>

            <a href="index.php?page=tax_report_wht" class="report-card">
                <div class="report-card-icon red"><i class="fa fa-percent"></i></div>
                <h5>Withholding Tax (WHT) <span class="card-arrow"><i class="fa fa-chevron-right"></i></span></h5>
                <p>Withholding tax summary report — track WHT deductions for compliance filing.</p>
            </a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Data Exports -->
    <div class="report-section">
        <h4><i class="fa fa-download"></i> Data Exports</h4>
        <div class="report-grid">
            <a href="index.php?page=export_invoice_payments" class="report-card" target="_blank">
                <div class="report-card-icon blue"><i class="fa fa-file-excel-o"></i></div>
                <h5>Export Invoice Payments <span class="card-arrow"><i class="fa fa-chevron-right"></i></span></h5>
                <p>Download invoice payment data as Excel-compatible CSV file.</p>
            </a>

            <a href="index.php?page=export_report" class="report-card" target="_blank">
                <div class="report-card-icon green"><i class="fa fa-file-excel-o"></i></div>
                <h5>Export Business Summary <span class="card-arrow"><i class="fa fa-chevron-right"></i></span></h5>
                <p>Download business summary data for offline analysis.</p>
            </a>
        </div>
    </div>
</div>
