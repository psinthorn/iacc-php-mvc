<?php
$pageTitle = 'LINE OA — Broadcast';
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$isThai = ($lang === 'th');
$t = [
    'page_title'    => $isThai ? 'ดูแคมเปญ' : 'Broadcast Detail',
    'back'          => $isThai ? 'กลับไปยังรายการ' : 'Back to list',
    'name'          => $isThai ? 'ชื่อ' : 'Name',
    'status'        => $isThai ? 'สถานะ' : 'Status',
    'audience'      => $isThai ? 'กลุ่มเป้าหมาย' : 'Audience',
    'recipients'    => $isThai ? 'จำนวนผู้รับ' : 'Recipients',
    'sent'          => $isThai ? 'ส่งสำเร็จ' : 'Sent',
    'failed'        => $isThai ? 'ล้มเหลว' : 'Failed',
    'started'       => $isThai ? 'เริ่มส่ง' : 'Started',
    'completed'     => $isThai ? 'เสร็จสิ้น' : 'Completed',
    'last_error'    => $isThai ? 'ข้อผิดพลาดล่าสุด' : 'Last error',
];
?>
<?php $currentNavPage = 'line_broadcasts'; include __DIR__ . '/_nav.php'; ?>

<a href="index.php?page=line_broadcasts" class="btn btn-default btn-sm" style="margin-bottom:16px;">
    <i class="fa fa-arrow-left"></i> <?= $t['back'] ?>
</a>

<div style="background:white; border-radius:12px; padding:24px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <h4 style="margin-top:0;"><i class="fa fa-bullhorn"></i> <?= htmlspecialchars($broadcast['name'], ENT_QUOTES, 'UTF-8') ?></h4>

    <table class="table table-condensed">
        <tr><th style="width:180px;"><?= $t['status'] ?></th>
            <td><span class="label label-info"><?= htmlspecialchars($broadcast['status'], ENT_QUOTES, 'UTF-8') ?></span></td></tr>
        <tr><th><?= $t['audience'] ?></th>
            <td><?= htmlspecialchars($broadcast['audience_type'], ENT_QUOTES, 'UTF-8') ?></td></tr>
        <tr><th><?= $t['recipients'] ?></th>
            <td><?= number_format((int)($broadcast['recipient_count'] ?? 0)) ?></td></tr>
        <tr><th><?= $t['sent'] ?></th>
            <td style="color:#06C755;"><strong><?= number_format((int)($broadcast['sent_count'] ?? 0)) ?></strong></td></tr>
        <tr><th><?= $t['failed'] ?></th>
            <td style="color:<?= ($broadcast['failed_count'] ?? 0) > 0 ? '#e74c3c' : '#999' ?>;">
                <?= number_format((int)($broadcast['failed_count'] ?? 0)) ?>
            </td></tr>
        <tr><th><?= $t['started'] ?></th>
            <td><?= !empty($broadcast['sent_started_at']) ? date('Y-m-d H:i:s', strtotime($broadcast['sent_started_at'])) : '-' ?></td></tr>
        <tr><th><?= $t['completed'] ?></th>
            <td><?= !empty($broadcast['sent_completed_at']) ? date('Y-m-d H:i:s', strtotime($broadcast['sent_completed_at'])) : '-' ?></td></tr>
        <?php if (!empty($broadcast['last_error'])): ?>
        <tr><th><?= $t['last_error'] ?></th>
            <td><code><?= htmlspecialchars($broadcast['last_error'], ENT_QUOTES, 'UTF-8') ?></code></td></tr>
        <?php endif; ?>
    </table>
</div>
</div>
