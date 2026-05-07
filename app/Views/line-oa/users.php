<?php
$pageTitle = 'LINE OA — Users';

/**
 * LINE OA Users List
 */
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$labels = [
    'en' => [
        'page_title' => 'LINE Users',
        'display_name' => 'Display Name',
        'user_type' => 'Type',
        'last_active' => 'Last Active',
        'status' => 'Status',
        'actions' => 'Actions',
        'customer' => 'Customer',
        'agent' => 'Agent',
        'all' => 'All',
        'active' => 'Active',
        'blocked' => 'Blocked',
        'view_messages' => 'Messages',
        'send_message' => 'Send',
        'no_users' => 'No LINE users found.',
        'joined' => 'Joined',
        'save' => 'Save',
        'change_type_hint' => 'Change to "Agent" before binding to an iACC user on the Agent Bindings page.',
    ],
    'th' => [
        'page_title' => 'ผู้ใช้ LINE',
        'display_name' => 'ชื่อที่แสดง',
        'user_type' => 'ประเภท',
        'last_active' => 'ใช้งานล่าสุด',
        'status' => 'สถานะ',
        'actions' => 'จัดการ',
        'customer' => 'ลูกค้า',
        'agent' => 'ตัวแทน',
        'all' => 'ทั้งหมด',
        'active' => 'ใช้งาน',
        'blocked' => 'บล็อก',
        'view_messages' => 'ข้อความ',
        'send_message' => 'ส่ง',
        'no_users' => 'ไม่พบผู้ใช้ LINE',
        'joined' => 'เข้าร่วม',
        'save' => 'บันทึก',
        'change_type_hint' => 'เปลี่ยนเป็น "ตัวแทน" ก่อนจึงจะผูกบัญชีกับผู้ใช้ iACC ในหน้า Agent Bindings ได้',
    ]
];
$t = $labels[$lang];
?>

<?php $currentNavPage = 'line_users'; include __DIR__ . '/_nav.php'; ?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_error']); endif; ?>

<div class="row" style="margin-bottom: 15px;">
    <div class="col-lg-12">
        <div class="btn-group">
            <a href="index.php?page=line_users" class="btn btn-<?= !$currentType ? 'primary' : 'default' ?>"><?= $t['all'] ?></a>
            <a href="index.php?page=line_users&type=customer" class="btn btn-<?= $currentType === 'customer' ? 'primary' : 'default' ?>"><?= $t['customer'] ?></a>
            <a href="index.php?page=line_users&type=agent" class="btn btn-<?= $currentType === 'agent' ? 'primary' : 'default' ?>"><?= $t['agent'] ?></a>
        </div>
    </div>
</div>

<div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <?php if (empty($lineUsers)): ?>
            <p class="text-muted text-center"><?= $t['no_users'] ?></p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th></th>
                        <th><?= $t['display_name'] ?></th>
                        <th><?= $t['user_type'] ?></th>
                        <th><?= $t['status'] ?></th>
                        <th><?= $t['last_active'] ?></th>
                        <th><?= $t['joined'] ?></th>
                        <th><?= $t['actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lineUsers as $lu): ?>
                    <tr>
                        <td>
                            <?php if (!empty($lu['picture_url'])): ?>
                            <img src="<?= htmlspecialchars($lu['picture_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:32px; height:32px; border-radius:50%;">
                            <?php else: ?>
                            <i class="fa fa-user-circle fa-2x text-muted"></i>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($lu['display_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <form method="POST" action="index.php?page=line_user_type_update" style="display:inline-flex; gap:4px; align-items:center; margin:0;">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <input type="hidden" name="line_user_id" value="<?= (int)$lu['id'] ?>">
                                <select name="user_type" class="form-control input-sm" style="max-width:110px;" title="<?= htmlspecialchars($t['change_type_hint'], ENT_QUOTES, 'UTF-8') ?>">
                                    <option value="customer" <?= ($lu['user_type'] ?? '') === 'customer' ? 'selected' : '' ?>><?= $t['customer'] ?></option>
                                    <option value="agent" <?= ($lu['user_type'] ?? '') === 'agent' ? 'selected' : '' ?>><?= $t['agent'] ?></option>
                                </select>
                                <button type="submit" class="btn btn-xs btn-primary" title="<?= $t['save'] ?>"><i class="fa fa-save"></i></button>
                            </form>
                        </td>
                        <td>
                            <?php if ($lu['is_blocked']): ?>
                            <span class="label label-danger"><?= $t['blocked'] ?></span>
                            <?php else: ?>
                            <span class="label label-success"><?= $t['active'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= $lu['last_interaction_at'] ? date('d M Y H:i', strtotime($lu['last_interaction_at'])) : '-' ?></td>
                        <td><?= date('d M Y', strtotime($lu['created_at'])) ?></td>
                        <td>
                            <a href="index.php?page=line_messages&user_id=<?= $lu['id'] ?>" class="btn btn-xs btn-info"><i class="fa fa-comments"></i> <?= $t['view_messages'] ?></a>
                            <a href="index.php?page=line_send_message&user_id=<?= $lu['id'] ?>" class="btn btn-xs btn-success"><i class="fa fa-paper-plane"></i> <?= $t['send_message'] ?></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
</div>
</div><!-- /master-data-container -->
