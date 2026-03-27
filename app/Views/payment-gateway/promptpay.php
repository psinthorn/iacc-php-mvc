<div class="content-wrapper">
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fa fa-qrcode"></i> <?= $lang === 'th' ? 'ชำระเงินผ่าน PromptPay' : 'PromptPay Payment' ?></h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="index.php?page=inv_checkout&id=<?= $invoice['tex'] ?? '' ?>" class="btn btn-default">
                        <i class="fa fa-arrow-left"></i> <?= $lang === 'th' ? 'กลับ' : 'Back' ?>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <section class="content">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card card-primary">
                    <div class="card-header text-center">
                        <h3 class="card-title">
                            <i class="fa fa-qrcode"></i> <?= $lang === 'th' ? 'สแกน QR Code เพื่อชำระเงิน' : 'Scan QR Code to Pay' ?>
                        </h3>
                    </div>
                    <div class="card-body text-center">
                        <!-- Invoice Info -->
                        <div class="mb-3">
                            <h5><?= $lang === 'th' ? 'ใบแจ้งหนี้' : 'Invoice' ?> #<?= htmlspecialchars($invoice['tex'] ?? '-') ?></h5>
                            <p class="text-muted"><?= htmlspecialchars($invoice['po_name'] ?? '') ?></p>
                        </div>

                        <!-- Amount -->
                        <div class="mb-4">
                            <h2 class="text-primary">
                                ฿<?= number_format($qrData['amount'] ?? 0, 2) ?>
                            </h2>
                        </div>

                        <!-- QR Code Image -->
                        <div class="mb-4">
                            <?php if (!empty($qrData['qr_url'])): ?>
                            <img src="<?= htmlspecialchars($qrData['qr_url']) ?>" 
                                 alt="PromptPay QR Code" 
                                 class="img-fluid border rounded p-2"
                                 style="max-width: 300px;">
                            <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i>
                                <?= $lang === 'th' ? 'ไม่สามารถสร้าง QR Code ได้' : 'Unable to generate QR code' ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- PromptPay ID -->
                        <div class="mb-3">
                            <small class="text-muted">
                                <?= $lang === 'th' ? 'PromptPay ID' : 'PromptPay ID' ?>: 
                                <strong><?= htmlspecialchars($qrData['target'] ?? '-') ?></strong>
                            </small>
                            <?php if (!empty($promptpayName)): ?>
                            <br><small class="text-muted"><?= htmlspecialchars($promptpayName) ?></small>
                            <?php endif; ?>
                        </div>

                        <hr>

                        <!-- Instructions -->
                        <div class="text-left">
                            <h6><i class="fa fa-info-circle"></i> <?= $lang === 'th' ? 'วิธีชำระเงิน' : 'How to Pay' ?></h6>
                            <ol class="text-muted small">
                                <li><?= $lang === 'th' ? 'เปิดแอปธนาคารของคุณ' : 'Open your banking app' ?></li>
                                <li><?= $lang === 'th' ? 'เลือก "สแกน QR Code"' : 'Select "Scan QR Code"' ?></li>
                                <li><?= $lang === 'th' ? 'สแกน QR Code ด้านบน' : 'Scan the QR code above' ?></li>
                                <li><?= $lang === 'th' ? 'ตรวจสอบจำนวนเงินแล้วยืนยัน' : 'Verify the amount and confirm' ?></li>
                                <li><?= $lang === 'th' ? 'แจ้งหลักฐานการโอนเงิน (ถ้ามี)' : 'Submit transfer proof (if required)' ?></li>
                            </ol>
                        </div>

                        <!-- Upload Slip (for manual confirmation) -->
                        <div class="mt-3">
                            <form method="post" action="index.php?page=promptpay_confirm" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <input type="hidden" name="invoice_id" value="<?= $invoice['tex'] ?? '' ?>">
                                <input type="hidden" name="payment_log_id" value="<?= $paymentLogId ?? '' ?>">
                                <div class="form-group">
                                    <label><?= $lang === 'th' ? 'อัปโหลดสลิปการโอน (ไม่บังคับ)' : 'Upload transfer slip (optional)' ?></label>
                                    <input type="file" name="slip" class="form-control-file" accept="image/*">
                                </div>
                                <div class="form-group">
                                    <label><?= $lang === 'th' ? 'เลขอ้างอิงการโอน' : 'Transaction reference' ?></label>
                                    <input type="text" name="trans_ref" class="form-control" placeholder="<?= $lang === 'th' ? 'ไม่บังคับ' : 'Optional' ?>">
                                </div>
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fa fa-check"></i> <?= $lang === 'th' ? 'แจ้งชำระเงินแล้ว' : 'Notify Payment' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
