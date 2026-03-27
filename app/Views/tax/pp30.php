<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fa fa-file-text"></i> <?= $lang === 'th' ? 'ภ.พ.30 — แบบยื่น VAT รายเดือน' : 'PP30 — Monthly VAT Return' ?></h1>
                    <small class="text-muted">
                        <?= date('F', mktime(0,0,0,$month,1)) ?> <?= $year ?>
                    </small>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="index.php?page=tax_reports&year=<?= $year ?>" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> <?= $lang === 'th' ? 'กลับ' : 'Back' ?>
                    </a>
                    <a href="index.php?page=tax_report_export&type=pp30&year=<?= $year ?>&month=<?= $month ?>&format=csv" class="btn btn-success">
                        <i class="fa fa-download"></i> CSV
                    </a>
                    <form method="post" action="index.php?page=tax_report_save" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="report_type" value="PP30">
                        <input type="hidden" name="year" value="<?= $year ?>">
                        <input type="hidden" name="month" value="<?= $month ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-save"></i> <?= $lang === 'th' ? 'บันทึก' : 'Save' ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <!-- Output VAT (ภาษีขาย) -->
        <div class="card card-success card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-arrow-up"></i> <?= $lang === 'th' ? 'ภาษีขาย (Output VAT)' : 'Output VAT (Sales)' ?></h3>
                <span class="float-right badge badge-success"><?= count($report['output_vat']['items']) ?> <?= $lang === 'th' ? 'รายการ' : 'items' ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?= $lang === 'th' ? 'เลขที่ใบกำกับภาษี' : 'Tax Invoice No.' ?></th>
                                <th><?= $lang === 'th' ? 'วันที่' : 'Date' ?></th>
                                <th><?= $lang === 'th' ? 'ชื่อลูกค้า' : 'Customer' ?></th>
                                <th><?= $lang === 'th' ? 'เลขประจำตัวผู้เสียภาษี' : 'Tax ID' ?></th>
                                <th class="text-right"><?= $lang === 'th' ? 'มูลค่า' : 'Base Amount' ?></th>
                                <th class="text-right"><?= $lang === 'th' ? 'ภาษี' : 'VAT' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($report['output_vat']['items'])): ?>
                            <tr><td colspan="7" class="text-center text-muted"><?= $lang === 'th' ? 'ไม่มีข้อมูล' : 'No data' ?></td></tr>
                            <?php else: ?>
                            <?php foreach ($report['output_vat']['items'] as $i => $item): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($item['tax_invoice_no'] ?? '-') ?></td>
                                <td><?= $item['tax_invoice_date'] ?? '-' ?></td>
                                <td><?= htmlspecialchars($item['customer_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($item['customer_tax_id'] ?? '-') ?></td>
                                <td class="text-right"><?= number_format($item['base_amount'], 2) ?></td>
                                <td class="text-right"><?= number_format($item['vat_amount'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td colspan="5" class="text-right"><?= $lang === 'th' ? 'รวมภาษีขาย' : 'Total Output VAT' ?></td>
                                <td class="text-right"><?= number_format($report['output_vat']['total_base'], 2) ?></td>
                                <td class="text-right"><?= number_format($report['output_vat']['total_vat'], 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Input VAT (ภาษีซื้อ) -->
        <div class="card card-danger card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-arrow-down"></i> <?= $lang === 'th' ? 'ภาษีซื้อ (Input VAT)' : 'Input VAT (Purchases)' ?></h3>
                <span class="float-right badge badge-danger"><?= count($report['input_vat']['items']) ?> <?= $lang === 'th' ? 'รายการ' : 'items' ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?= $lang === 'th' ? 'เลขที่ PO' : 'PO No.' ?></th>
                                <th><?= $lang === 'th' ? 'วันที่' : 'Date' ?></th>
                                <th><?= $lang === 'th' ? 'ชื่อผู้ขาย' : 'Vendor' ?></th>
                                <th><?= $lang === 'th' ? 'เลขประจำตัวผู้เสียภาษี' : 'Tax ID' ?></th>
                                <th class="text-right"><?= $lang === 'th' ? 'มูลค่า' : 'Base Amount' ?></th>
                                <th class="text-right"><?= $lang === 'th' ? 'ภาษี' : 'VAT' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($report['input_vat']['items'])): ?>
                            <tr><td colspan="7" class="text-center text-muted"><?= $lang === 'th' ? 'ไม่มีข้อมูล' : 'No data' ?></td></tr>
                            <?php else: ?>
                            <?php foreach ($report['input_vat']['items'] as $i => $item): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($item['po_number'] ?? '-') ?></td>
                                <td><?= $item['po_date'] ?? '-' ?></td>
                                <td><?= htmlspecialchars($item['vendor_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($item['vendor_tax_id'] ?? '-') ?></td>
                                <td class="text-right"><?= number_format($item['base_amount'], 2) ?></td>
                                <td class="text-right"><?= number_format($item['vat_amount'], 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td colspan="5" class="text-right"><?= $lang === 'th' ? 'รวมภาษีซื้อ' : 'Total Input VAT' ?></td>
                                <td class="text-right"><?= number_format($report['input_vat']['total_base'], 2) ?></td>
                                <td class="text-right"><?= number_format($report['input_vat']['total_vat'], 2) ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Net VAT Summary -->
        <div class="card card-primary">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-calculator"></i> <?= $lang === 'th' ? 'สรุป VAT สุทธิ' : 'Net VAT Summary' ?></h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 text-center">
                        <h5><?= $lang === 'th' ? 'ภาษีขาย' : 'Output VAT' ?></h5>
                        <h3 class="text-success"><?= number_format($report['output_vat']['total_vat'], 2) ?></h3>
                    </div>
                    <div class="col-md-4 text-center">
                        <h5><?= $lang === 'th' ? 'ภาษีซื้อ' : 'Input VAT' ?></h5>
                        <h3 class="text-danger"><?= number_format($report['input_vat']['total_vat'], 2) ?></h3>
                    </div>
                    <div class="col-md-4 text-center">
                        <h5><?= $lang === 'th' ? 'VAT สุทธิ' : 'Net VAT' ?></h5>
                        <h3 class="<?= $report['net_vat'] >= 0 ? 'text-danger' : 'text-success' ?>">
                            <?= number_format($report['net_vat'], 2) ?>
                        </h3>
                        <small class="text-muted">
                            <?php if ($report['vat_payable'] > 0): ?>
                                <?= $lang === 'th' ? 'ต้องชำระ' : 'Tax payable' ?>
                            <?php else: ?>
                                <?= $lang === 'th' ? 'ขอคืนได้' : 'Refundable' ?>
                            <?php endif; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
