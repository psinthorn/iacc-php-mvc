<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fa fa-money"></i> <?= $lang === 'th' ? 'จัดการสกุลเงิน' : 'Currency Management' ?></h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="index.php?page=currency_rates" class="btn btn-info">
                        <i class="fa fa-line-chart"></i> <?= $lang === 'th' ? 'อัตราแลกเปลี่ยน' : 'Exchange Rates' ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <?php if (!empty($message)): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fa fa-check"></i> <?= $lang === 'th' ? 'อัปเดตเรียบร้อย' : 'Updated successfully' ?>
        </div>
        <?php endif; ?>

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title"><i class="fa fa-globe"></i> <?= $lang === 'th' ? 'สกุลเงินที่รองรับ' : 'Supported Currencies' ?></h3>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    <?= $lang === 'th' 
                        ? 'สกุลเงินเริ่มต้น: <strong>' . $defaultCurrency . '</strong> — เปิด/ปิดสกุลเงินที่ต้องการใช้' 
                        : 'Default currency: <strong>' . $defaultCurrency . '</strong> — Toggle currencies you want to use' ?>
                </p>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th><?= $lang === 'th' ? 'รหัส' : 'Code' ?></th>
                                <th><?= $lang === 'th' ? 'สัญลักษณ์' : 'Symbol' ?></th>
                                <th><?= $lang === 'th' ? 'ชื่อ' : 'Name' ?></th>
                                <th><?= $lang === 'th' ? 'ชื่อไทย' : 'Thai Name' ?></th>
                                <th class="text-center"><?= $lang === 'th' ? 'ทศนิยม' : 'Decimals' ?></th>
                                <th class="text-center"><?= $lang === 'th' ? 'สถานะ' : 'Status' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($currencies as $code => $cur): ?>
                            <?php $isActive = ($code === $defaultCurrency) || isset(array_column($activeCurrencies, null, 'code')[$code]); ?>
                            <tr>
                                <td><strong><?= $code ?></strong></td>
                                <td class="text-center" style="font-size: 18px;"><?= $cur['symbol'] ?></td>
                                <td><?= $cur['name'] ?></td>
                                <td><?= $cur['name_th'] ?? '-' ?></td>
                                <td class="text-center"><?= $cur['decimals'] ?></td>
                                <td class="text-center">
                                    <?php if ($code === $defaultCurrency): ?>
                                        <span class="badge badge-primary"><?= $lang === 'th' ? 'ค่าเริ่มต้น' : 'Default' ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-<?= $isActive ? 'success' : 'secondary' ?>">
                                            <?= $isActive ? ($lang === 'th' ? 'เปิดใช้' : 'Active') : ($lang === 'th' ? 'ปิด' : 'Inactive') ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
