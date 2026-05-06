<?php
$pageTitle = 'LINE OA — Broadcasts';
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$isThai = ($lang === 'th');
$labels = [
    'en' => [
        'page_title' => 'Broadcast Campaigns',
        'new'        => 'New Broadcast',
        'name'       => 'Name',
        'status'     => 'Status',
        'audience'   => 'Audience',
        'recipients' => 'Recipients',
        'sent'       => 'Sent',
        'failed'     => 'Failed',
        'when'       => 'When',
        'actions'    => 'Actions',
        'no_broadcasts' => 'No broadcasts yet. Create your first campaign to reach LINE friends.',
        'view'       => 'View',
        'edit'       => 'Edit',
        'monthly_quota' => 'Monthly quota',
        'quota_used' => 'used',
        'audience_types' => [
            'all' => 'All friends', 'tag' => 'By tag', 'language' => 'By language',
            'has_booked' => 'Has booked', 'last_active' => 'Recently active',
        ],
        'status_labels' => [
            'draft' => 'Draft', 'scheduled' => 'Scheduled', 'sending' => 'Sending',
            'sent' => 'Sent', 'partial' => 'Partial', 'failed' => 'Failed', 'cancelled' => 'Cancelled',
        ],
    ],
    'th' => [
        'page_title' => 'แคมเปญส่งกลุ่ม',
        'new'        => 'สร้างแคมเปญ',
        'name'       => 'ชื่อ',
        'status'     => 'สถานะ',
        'audience'   => 'กลุ่มเป้าหมาย',
        'recipients' => 'จำนวนผู้รับ',
        'sent'       => 'ส่งแล้ว',
        'failed'     => 'ล้มเหลว',
        'when'       => 'เวลา',
        'actions'    => 'จัดการ',
        'no_broadcasts' => 'ยังไม่มีแคมเปญ สร้างแคมเปญแรกเพื่อส่งถึงเพื่อน LINE',
        'view'       => 'ดู',
        'edit'       => 'แก้ไข',
        'monthly_quota' => 'โควต้ารายเดือน',
        'quota_used' => 'ใช้ไป',
        'audience_types' => [
            'all' => 'เพื่อนทั้งหมด', 'tag' => 'ตามแท็ก', 'language' => 'ตามภาษา',
            'has_booked' => 'เคยจอง', 'last_active' => 'ใช้งานล่าสุด',
        ],
        'status_labels' => [
            'draft' => 'แบบร่าง', 'scheduled' => 'ตั้งเวลา', 'sending' => 'กำลังส่ง',
            'sent' => 'ส่งแล้ว', 'partial' => 'ส่งบางส่วน', 'failed' => 'ล้มเหลว', 'cancelled' => 'ยกเลิก',
        ],
    ],
];
$t = $labels[$lang];

$statusBadge = [
    'draft' => 'default', 'scheduled' => 'info', 'sending' => 'primary',
    'sent' => 'success', 'partial' => 'warning', 'failed' => 'danger', 'cancelled' => 'default',
];
$quotaPct = $quota_limit > 0 ? min(100, round(($quota_used / $quota_limit) * 100)) : 0;
$quotaColor = $quotaPct < 70 ? '#06C755' : ($quotaPct < 95 ? '#f39c12' : '#e74c3c');
?>
<?php $currentNavPage = 'line_broadcasts'; include __DIR__ . '/_nav.php'; ?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_error']); endif; ?>

<!-- Quota meter -->
<div style="background:white; border-radius:12px; padding:16px 20px; margin-bottom:16px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
        <strong><?= $t['monthly_quota'] ?></strong>
        <small><?= number_format($quota_used) ?> / <?= number_format($quota_limit) ?> <?= $t['quota_used'] ?> (<?= $quotaPct ?>%)</small>
    </div>
    <div style="height:8px; background:#eee; border-radius:4px; overflow:hidden;">
        <div style="height:100%; width:<?= $quotaPct ?>%; background:<?= $quotaColor ?>;"></div>
    </div>
</div>

<div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap; gap:10px;">
        <h4 style="margin:0;"><i class="fa fa-bullhorn"></i> <?= $t['page_title'] ?></h4>
        <a href="index.php?page=line_broadcast_compose" class="btn btn-success btn-sm">
            <i class="fa fa-plus"></i> <?= $t['new'] ?>
        </a>
    </div>

    <?php if (empty($broadcasts)): ?>
        <p style="text-align:center; padding:40px 20px; color:#999;"><?= $t['no_broadcasts'] ?></p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover" style="margin:0;">
            <thead>
                <tr>
                    <th><?= $t['name'] ?></th>
                    <th><?= $t['status'] ?></th>
                    <th><?= $t['audience'] ?></th>
                    <th style="text-align:right;"><?= $t['recipients'] ?></th>
                    <th style="text-align:right;"><?= $t['sent'] ?></th>
                    <th style="text-align:right;"><?= $t['failed'] ?></th>
                    <th><?= $t['when'] ?></th>
                    <th style="text-align:right;"><?= $t['actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($broadcasts as $b): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($b['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td>
                        <span class="label label-<?= $statusBadge[$b['status']] ?? 'default' ?>">
                            <?= $t['status_labels'][$b['status']] ?? $b['status'] ?>
                        </span>
                    </td>
                    <td><?= $t['audience_types'][$b['audience_type']] ?? $b['audience_type'] ?></td>
                    <td style="text-align:right;"><?= number_format($b['recipient_count'] ?? 0) ?></td>
                    <td style="text-align:right; color:#06C755;"><?= number_format($b['sent_count'] ?? 0) ?></td>
                    <td style="text-align:right; color:<?= ($b['failed_count'] ?? 0) > 0 ? '#e74c3c' : '#999' ?>;"><?= number_format($b['failed_count'] ?? 0) ?></td>
                    <td>
                        <?php
                        $when = $b['sent_completed_at'] ?? ($b['scheduled_at'] ?? $b['created_at']);
                        echo $when ? date('M d, H:i', strtotime($when)) : '-';
                        ?>
                    </td>
                    <td style="text-align:right;">
                        <?php $isReadonly = in_array($b['status'], ['sent','sending','partial']); ?>
                        <a href="index.php?page=line_broadcast_compose&amp;id=<?= (int)$b['id'] ?>" class="btn btn-xs btn-outline-primary">
                            <i class="fa fa-<?= $isReadonly ? 'eye' : 'pencil' ?>"></i>
                            <?= $isReadonly ? $t['view'] : $t['edit'] ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
</div>
