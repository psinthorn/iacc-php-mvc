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
                        <td><span class="label label-<?= $lu['user_type'] === 'agent' ? 'primary' : 'info' ?>"><?= $t[$lu['user_type']] ?? $lu['user_type'] ?></span></td>
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
