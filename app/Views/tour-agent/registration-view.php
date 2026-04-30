<?php
$pageTitle = 'Tour Operator — Agent Registration Detail';

/**
 * Agent Registration Detail (operator-side)
 * Variables: $registration, $contracts, $message
 */
$isThai = ($_SESSION['lang'] ?? '0') === '1';

$messages = [
    'approved'    => ['✅', $isThai ? 'อนุมัติตัวแทนสำเร็จ' : 'Agent approved successfully'],
    'rejected'    => ['✅', $isThai ? 'ปฏิเสธคำขอสำเร็จ' : 'Registration rejected'],
    'suspended'   => ['✅', $isThai ? 'ระงับตัวแทนสำเร็จ' : 'Agent suspended'],
    'reactivated' => ['✅', $isThai ? 'เปิดใช้งานสำเร็จ' : 'Agent reactivated'],
    'error'       => ['⚠️', $isThai ? 'เกิดข้อผิดพลาด' : 'An error occurred'],
];

$statusBadges = [
    'pending'   => [$isThai ? 'รออนุมัติ' : 'Pending',    '#d97706', '#fffbeb'],
    'approved'  => [$isThai ? 'อนุมัติแล้ว' : 'Approved',  '#059669', '#ecfdf5'],
    'suspended' => [$isThai ? 'ถูกระงับ'   : 'Suspended',  '#dc2626', '#fef2f2'],
    'rejected'  => [$isThai ? 'ปฏิเสธแล้ว' : 'Rejected',   '#94a3b8', '#f1f5f9'],
];
$st = $statusBadges[$registration['status']] ?? $statusBadges['pending'];
$name = $registration['agent_name_en'] ?: $registration['agent_name_th'] ?: ('ID#' . $registration['agent_company_id']);
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.detail-grid { display: grid; grid-template-columns: 1fr 360px; gap: 20px; }
.detail-main { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 28px; }
.detail-side { display: flex; flex-direction: column; gap: 16px; }
.action-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 20px; }
.action-card h3 { margin: 0 0 14px; font-size: 14px; color: #1e293b; }
.profile-header { display: flex; align-items: center; gap: 16px; margin-bottom: 24px; padding-bottom: 20px; border-bottom: 1px solid #f1f5f9; }
.profile-avatar { width: 64px; height: 64px; border-radius: 50%; background: #f0fdfa; color: #0d9488; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 24px; }
.profile-name { font-size: 20px; font-weight: 600; color: #1e293b; }
.profile-meta { font-size: 13px; color: #64748b; margin-top: 4px; }
.info-row { display: grid; grid-template-columns: 140px 1fr; gap: 12px; padding: 10px 0; border-bottom: 1px solid #f8fafc; font-size: 13px; }
.info-row:last-child { border-bottom: none; }
.info-row dt { color: #94a3b8; font-weight: 500; }
.info-row dd { margin: 0; color: #334155; }
.status-badge { display: inline-block; padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.btn-action { display: inline-flex; align-items: center; gap: 6px; padding: 10px 16px; border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer; border: none; text-decoration: none; width: 100%; justify-content: center; box-sizing: border-box; }
.btn-action + .btn-action { margin-top: 8px; }
.btn-approve { background: #059669; color: #fff; }
.btn-approve:hover { background: #047857; }
.btn-reject { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
.btn-reject:hover { background: #fee2e2; }
.btn-suspend { background: #fffbeb; color: #d97706; border: 1px solid #fde68a; }
.btn-suspend:hover { background: #fef3c7; }
.btn-reactivate { background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; }
.btn-reactivate:hover { background: #d1fae5; }
.form-group { margin-bottom: 12px; }
.form-group label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 4px; }
.form-group select, .form-group input, .form-group textarea {
    width: 100%; padding: 8px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; box-sizing: border-box;
}
.form-group textarea { min-height: 60px; resize: vertical; }
@media (max-width: 900px) {
    .detail-grid { grid-template-columns: 1fr; }
}
</style>

<div class="master-data-container">
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-user"></i> <?= htmlspecialchars($name) ?></h2>
                <p><?= $isThai ? 'รายละเอียดการลงทะเบียนตัวแทน' : 'Agent registration details' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=tour_agent_reg_list" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back' ?>
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($message) && isset($messages[$message])): ?>
    <div style="background:<?= strpos($messages[$message][0], '✅') !== false ? '#ecfdf5' : '#fef2f2' ?>; border-left:4px solid <?= strpos($messages[$message][0], '✅') !== false ? '#059669' : '#ef4444' ?>; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        <?= $messages[$message][0] ?> <?= $messages[$message][1] ?>
    </div>
    <?php endif; ?>

    <div class="detail-grid">
        <!-- Main Profile -->
        <div class="detail-main">
            <div class="profile-header">
                <div class="profile-avatar"><?= mb_strtoupper(mb_substr($name, 0, 1)) ?></div>
                <div style="flex:1;">
                    <div class="profile-name"><?= htmlspecialchars($name) ?></div>
                    <div class="profile-meta">
                        <span class="status-badge" style="background:<?= $st[2] ?>;color:<?= $st[1] ?>;"><?= $st[0] ?></span>
                        <span style="margin-left:8px;">ID#<?= $registration['agent_company_id'] ?></span>
                    </div>
                </div>
            </div>

            <dl>
                <div class="info-row"><dt><?= $isThai ? 'ชื่ออังกฤษ' : 'Name (EN)' ?></dt><dd><?= htmlspecialchars($registration['agent_name_en'] ?: '—') ?></dd></div>
                <div class="info-row"><dt><?= $isThai ? 'ชื่อไทย' : 'Name (TH)' ?></dt><dd><?= htmlspecialchars($registration['agent_name_th'] ?: '—') ?></dd></div>
                <div class="info-row"><dt><?= $isThai ? 'อีเมล' : 'Email' ?></dt><dd><?= htmlspecialchars($registration['agent_email'] ?: '—') ?></dd></div>
                <div class="info-row"><dt><?= $isThai ? 'โทรศัพท์' : 'Phone' ?></dt><dd><?= htmlspecialchars($registration['agent_phone'] ?: '—') ?></dd></div>
                <div class="info-row"><dt><?= $isThai ? 'ที่อยู่' : 'Address' ?></dt><dd><?= nl2br(htmlspecialchars($registration['agent_address'] ?: '—')) ?></dd></div>
                <div class="info-row"><dt><?= $isThai ? 'วิธีลงทะเบียน' : 'Registered Via' ?></dt><dd><?= ucfirst($registration['registered_via']) ?></dd></div>
                <div class="info-row"><dt><?= $isThai ? 'วันที่ลงทะเบียน' : 'Registered On' ?></dt><dd><?= date('d M Y H:i', strtotime($registration['created_at'])) ?></dd></div>
                <?php if (!empty($registration['approved_at'])): ?>
                <div class="info-row"><dt><?= $isThai ? 'อนุมัติเมื่อ' : 'Approved On' ?></dt><dd><?= date('d M Y H:i', strtotime($registration['approved_at'])) ?></dd></div>
                <?php endif; ?>
                <?php if (!empty($registration['notes'])): ?>
                <div class="info-row"><dt><?= $isThai ? 'หมายเหตุ' : 'Notes' ?></dt><dd style="white-space:pre-wrap;"><?= htmlspecialchars($registration['notes']) ?></dd></div>
                <?php endif; ?>
            </dl>
        </div>

        <!-- Side Actions -->
        <div class="detail-side">
            <?php if ($registration['status'] === 'pending'): ?>
            <!-- Approve form -->
            <div class="action-card">
                <h3><i class="fa fa-check-circle" style="color:#059669;"></i> <?= $isThai ? 'อนุมัติตัวแทน' : 'Approve Agent' ?></h3>
                <form method="post" action="index.php?page=tour_agent_reg_approve">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $registration['id'] ?>">

                    <div class="form-group">
                        <label><?= $isThai ? 'มอบหมายสัญญาเริ่มต้น (ไม่บังคับ)' : 'Assign Default Contract (optional)' ?></label>
                        <select name="default_contract_id">
                            <option value="0">— <?= $isThai ? 'ไม่มอบหมาย' : 'None' ?> —</option>
                            <?php foreach ($contracts as $c): ?>
                            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['contract_name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn-action btn-approve">
                        <i class="fa fa-check"></i> <?= $isThai ? 'อนุมัติ' : 'Approve' ?>
                    </button>
                </form>
            </div>

            <!-- Reject form -->
            <div class="action-card">
                <h3><i class="fa fa-times-circle" style="color:#dc2626;"></i> <?= $isThai ? 'ปฏิเสธคำขอ' : 'Reject Registration' ?></h3>
                <form method="post" action="index.php?page=tour_agent_reg_reject"
                      onsubmit="return confirm('<?= $isThai ? 'ปฏิเสธการลงทะเบียนนี้?' : 'Reject this registration?' ?>')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $registration['id'] ?>">

                    <div class="form-group">
                        <label><?= $isThai ? 'เหตุผล (ไม่บังคับ)' : 'Reason (optional)' ?></label>
                        <textarea name="reason" placeholder="<?= $isThai ? 'เหตุผลในการปฏิเสธ...' : 'Reason for rejection...' ?>"></textarea>
                    </div>

                    <button type="submit" class="btn-action btn-reject">
                        <i class="fa fa-times"></i> <?= $isThai ? 'ปฏิเสธ' : 'Reject' ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($registration['status'] === 'approved'): ?>
            <!-- Suspend form -->
            <div class="action-card">
                <h3><i class="fa fa-pause-circle" style="color:#d97706;"></i> <?= $isThai ? 'ระงับตัวแทน' : 'Suspend Agent' ?></h3>
                <form method="post" action="index.php?page=tour_agent_reg_suspend"
                      onsubmit="return confirm('<?= $isThai ? 'ระงับตัวแทนนี้? พวกเขาจะไม่สามารถเข้าถึงพอร์ทัลได้' : 'Suspend this agent? They will lose portal access.' ?>')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $registration['id'] ?>">

                    <div class="form-group">
                        <label><?= $isThai ? 'เหตุผล (ไม่บังคับ)' : 'Reason (optional)' ?></label>
                        <textarea name="reason" placeholder="<?= $isThai ? 'เหตุผลในการระงับ...' : 'Reason for suspension...' ?>"></textarea>
                    </div>

                    <button type="submit" class="btn-action btn-suspend">
                        <i class="fa fa-pause"></i> <?= $isThai ? 'ระงับ' : 'Suspend' ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>

            <?php if ($registration['status'] === 'suspended'): ?>
            <!-- Reactivate -->
            <div class="action-card">
                <h3><i class="fa fa-play-circle" style="color:#059669;"></i> <?= $isThai ? 'เปิดใช้งานอีกครั้ง' : 'Reactivate Agent' ?></h3>
                <form method="post" action="index.php?page=tour_agent_reg_reactivate">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $registration['id'] ?>">
                    <button type="submit" class="btn-action btn-reactivate">
                        <i class="fa fa-play"></i> <?= $isThai ? 'เปิดใช้งาน' : 'Reactivate' ?>
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
