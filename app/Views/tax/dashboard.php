<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fa fa-calculator"></i> <?= $lang === 'th' ? 'รายงานภาษี' : 'Tax Reports' ?></h1>
                </div>
                <div class="col-sm-6 text-right">
                    <form class="form-inline justify-content-end" method="get">
                        <input type="hidden" name="page" value="tax_reports">
                        <div class="input-group">
                            <select name="year" class="form-control" onchange="this.form.submit()">
                                <?php for ($y = date('Y'); $y >= date('Y') - 5; $y--): ?>
                                <option value="<?= $y ?>" <?= $year == $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <?php if (isset($_GET['saved'])): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fa fa-check"></i> <?= $lang === 'th' ? 'บันทึกรายงานเรียบร้อย' : 'Report saved successfully' ?>
        </div>
        <?php endif; ?>

        <!-- Annual VAT Summary -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-bar-chart"></i> <?= $lang === 'th' ? 'สรุป VAT ประจำปี ' . $year : 'Annual VAT Summary ' . $year ?></h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th><?= $lang === 'th' ? 'เดือน' : 'Month' ?></th>
                                <th class="text-right"><?= $lang === 'th' ? 'ภาษีขาย' : 'Output VAT' ?></th>
                                <th class="text-right"><?= $lang === 'th' ? 'ภาษีซื้อ' : 'Input VAT' ?></th>
                                <th class="text-right"><?= $lang === 'th' ? 'ภาษีสุทธิ' : 'Net VAT' ?></th>
                                <th class="text-center"><?= $lang === 'th' ? 'สถานะ' : 'Status' ?></th>
                                <th class="text-center"><?= $lang === 'th' ? 'จัดการ' : 'Actions' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($summary['months'] as $m): ?>
                            <tr>
                                <td><?= $m['month_name'] ?></td>
                                <td class="text-right"><?= number_format($m['output_vat'], 2) ?></td>
                                <td class="text-right"><?= number_format($m['input_vat'], 2) ?></td>
                                <td class="text-right font-weight-bold <?= $m['net_vat'] < 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= number_format($m['net_vat'], 2) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($m['status'] === 'filed'): ?>
                                        <span class="badge badge-success"><?= $lang === 'th' ? 'ยื่นแล้ว' : 'Filed' ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-warning"><?= $lang === 'th' ? 'รอยื่น' : 'Pending' ?></span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="index.php?page=tax_report_pp30&year=<?= $year ?>&month=<?= $m['month'] ?>" class="btn btn-sm btn-info" title="ภ.พ.30">
                                        <i class="fa fa-file-text"></i> PP30
                                    </a>
                                    <a href="index.php?page=tax_report_wht&year=<?= $year ?>&month=<?= $m['month'] ?>&type=pnd53" class="btn btn-sm btn-secondary" title="ภ.ง.ด.53">
                                        <i class="fa fa-file"></i> WHT
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="font-weight-bold">
                            <tr>
                                <td><?= $lang === 'th' ? 'รวมทั้งปี' : 'Annual Total' ?></td>
                                <td class="text-right"><?= number_format($summary['totals']['output_vat'], 2) ?></td>
                                <td class="text-right"><?= number_format($summary['totals']['input_vat'], 2) ?></td>
                                <td class="text-right"><?= number_format($summary['totals']['net_vat'], 2) ?></td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Saved Reports -->
        <?php if (!empty($savedReports)): ?>
        <div class="card card-secondary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-archive"></i> <?= $lang === 'th' ? 'รายงานที่บันทึกไว้' : 'Saved Reports' ?></h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th><?= $lang === 'th' ? 'ประเภท' : 'Type' ?></th>
                                <th><?= $lang === 'th' ? 'เดือน/ปี' : 'Month/Year' ?></th>
                                <th><?= $lang === 'th' ? 'สถานะ' : 'Status' ?></th>
                                <th><?= $lang === 'th' ? 'สร้างเมื่อ' : 'Created' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($savedReports as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['report_type']) ?></strong></td>
                                <td><?= $r['tax_month'] ?>/<?= $r['tax_year'] ?></td>
                                <td><span class="badge badge-<?= $r['status'] === 'filed' ? 'success' : 'warning' ?>"><?= ucfirst($r['status']) ?></span></td>
                                <td><?= $r['created_at'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </section>
</div>
