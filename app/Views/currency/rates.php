<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fa fa-line-chart"></i> <?= $lang === 'th' ? 'อัตราแลกเปลี่ยน' : 'Exchange Rates' ?></h1>
                    <small class="text-muted"><?= $lang === 'th' ? 'อ้างอิงจากธนาคารแห่งประเทศไทย (BOT)' : 'Source: Bank of Thailand (BOT)' ?></small>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="index.php?page=currency_list" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> <?= $lang === 'th' ? 'จัดการสกุลเงิน' : 'Manage Currencies' ?>
                    </a>
                    <form method="post" action="index.php?page=currency_refresh" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <button type="submit" class="btn btn-primary">
                            <i class="fa fa-refresh"></i> <?= $lang === 'th' ? 'อัปเดตอัตรา' : 'Refresh Rates' ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <?php if (isset($_GET['msg']) && $_GET['msg'] === 'refreshed'): ?>
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fa fa-check"></i> <?= $lang === 'th' ? 'อัปเดตอัตราแลกเปลี่ยนเรียบร้อย' : 'Exchange rates refreshed' ?>
            <?php if (isset($_GET['count'])): ?>(<?= intval($_GET['count']) ?> <?= $lang === 'th' ? 'สกุลเงิน' : 'currencies' ?>)<?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="card card-primary card-outline">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fa fa-exchange"></i> <?= $lang === 'th' ? 'อัตราแลกเปลี่ยนล่าสุด (1 หน่วย = ? THB)' : 'Latest Exchange Rates (1 unit = ? THB)' ?>
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($rates)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fa fa-info-circle fa-2x mb-2"></i>
                    <p><?= $lang === 'th' ? 'ยังไม่มีข้อมูลอัตราแลกเปลี่ยน — กดปุ่ม "อัปเดตอัตรา" เพื่อดึงข้อมูลล่าสุด' : 'No exchange rate data yet — click "Refresh Rates" to fetch latest data' ?></p>
                </div>
                <?php else: ?>
                <div class="row">
                    <?php foreach ($rates as $code => $rateInfo): ?>
                    <?php $cur = $supported[$code] ?? ['name' => $code, 'symbol' => $code]; ?>
                    <div class="col-md-3 col-sm-4 col-6 mb-3">
                        <div class="info-box bg-light">
                            <span class="info-box-icon" style="font-size: 24px;"><?= $cur['symbol'] ?? $code ?></span>
                            <div class="info-box-content">
                                <span class="info-box-text"><?= $code ?></span>
                                <span class="info-box-number"><?= number_format($rateInfo['rate'], 4) ?> THB</span>
                                <small class="text-muted"><?= $rateInfo['date'] ?></small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
</div>
