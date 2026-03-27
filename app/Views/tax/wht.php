<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fa fa-file"></i> <?= $report['report_name'] ?> — <?= $report['report_name_en'] ?></h1>
                    <small class="text-muted"><?= date('F', mktime(0,0,0,$month,1)) ?> <?= $year ?></small>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="index.php?page=tax_reports&year=<?= $year ?>" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> <?= $lang === 'th' ? 'กลับ' : 'Back' ?>
                    </a>
                    <a href="index.php?page=tax_report_export&type=<?= $type ?>&year=<?= $year ?>&month=<?= $month ?>&format=csv" class="btn btn-success">
                        <i class="fa fa-download"></i> CSV
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <!-- Type Toggle -->
        <div class="mb-3">
            <div class="btn-group">
                <a href="index.php?page=tax_report_wht&year=<?= $year ?>&month=<?= $month ?>&type=pnd3" 
                   class="btn btn-<?= $type === 'pnd3' ? 'primary' : 'default' ?>">
                    ภ.ง.ด.3 (<?= $lang === 'th' ? 'บุคคลธรรมดา' : 'Individual' ?>)
                </a>
                <a href="index.php?page=tax_report_wht&year=<?= $year ?>&month=<?= $month ?>&type=pnd53" 
                   class="btn btn-<?= $type === 'pnd53' ? 'primary' : 'default' ?>">
                    ภ.ง.ด.53 (<?= $lang === 'th' ? 'นิติบุคคล' : 'Company' ?>)
                </a>
            </div>
        </div>

        <!-- WHT Records -->
        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa fa-list"></i> <?= $lang === 'th' ? 'รายการหักภาษี ณ ที่จ่าย' : 'Withholding Tax Records' ?>
                </h3>
                <span class="float-right badge badge-primary"><?= count($report['items']) ?> <?= $lang === 'th' ? 'รายการ' : 'records' ?></span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th><?= $lang === 'th' ? 'วันที่ชำระ' : 'Payment Date' ?></th>
                                <th><?= $lang === 'th' ? 'ผู้รับเงิน' : 'Payee' ?></th>
                                <th><?= $lang === 'th' ? 'เลขประจำตัวผู้เสียภาษี' : 'Tax ID' ?></th>
                                <th><?= $lang === 'th' ? 'ประเภทเงินได้' : 'Income Type' ?></th>
                                <th class="text-right"><?= $lang === 'th' ? 'จำนวนเงินที่จ่าย' : 'Payment Amount' ?></th>
                                <th class="text-right"><?= $lang === 'th' ? 'อัตรา %' : 'Rate %' ?></th>
                                <th class="text-right"><?= $lang === 'th' ? 'ภาษีหัก ณ ที่จ่าย' : 'WHT Amount' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($report['items'])): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fa fa-info-circle"></i> 
                                    <?= $lang === 'th' ? 'ไม่พบรายการหักภาษี ณ ที่จ่ายในช่วงนี้' : 'No withholding tax records found for this period' ?>
                                    <br><small><?= $lang === 'th' ? 'หมายเหตุ: ต้องเพิ่มฟิลด์ WHT ในตาราง pay ก่อน' : 'Note: WHT fields need to be added to the pay table first' ?></small>
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($report['items'] as $i => $item): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= $item['payment_date'] ?></td>
                                <td><?= htmlspecialchars($item['payee_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($item['payee_tax_id'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($item['income_type'] ?? '-') ?></td>
                                <td class="text-right"><?= number_format(floatval($item['payment_amount']), 2) ?></td>
                                <td class="text-right"><?= $item['wht_rate'] ?? '-' ?>%</td>
                                <td class="text-right"><?= number_format(floatval($item['wht_amount']), 2) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($report['items'])): ?>
                        <tfoot class="font-weight-bold bg-light">
                            <tr>
                                <td colspan="5" class="text-right"><?= $lang === 'th' ? 'รวม' : 'Total' ?></td>
                                <td class="text-right"><?= number_format($report['total_payment'], 2) ?></td>
                                <td></td>
                                <td class="text-right"><?= number_format($report['total_wht'], 2) ?></td>
                            </tr>
                        </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
