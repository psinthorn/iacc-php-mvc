<?php
$pageTitle = 'LINE OA — Templates';
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$isThai = ($lang === 'th');
$labels = [
    'en' => [
        'page_title' => 'Message Templates',
        'new'        => 'New Template',
        'name'       => 'Name',
        'type'       => 'Type',
        'message_kind' => 'Kind',
        'languages'  => 'Languages',
        'active'     => 'Active',
        'actions'    => 'Actions',
        'edit'       => 'Edit',
        'delete'     => 'Delete',
        'no_templates' => 'No templates yet. Create your first one to start sending rich messages.',
        'confirm_delete' => 'Delete this template?',
        'yes'        => 'Yes',
        'no'         => 'No',
    ],
    'th' => [
        'page_title' => 'เทมเพลตข้อความ',
        'new'        => 'สร้างเทมเพลต',
        'name'       => 'ชื่อ',
        'type'       => 'ประเภท',
        'message_kind' => 'รูปแบบ',
        'languages'  => 'ภาษา',
        'active'     => 'ใช้งาน',
        'actions'    => 'จัดการ',
        'edit'       => 'แก้ไข',
        'delete'     => 'ลบ',
        'no_templates' => 'ยังไม่มีเทมเพลต สร้างเทมเพลตแรกเพื่อส่งข้อความรูปแบบสวยงาม',
        'confirm_delete' => 'ลบเทมเพลตนี้?',
        'yes'        => 'ใช่',
        'no'         => 'ไม่ใช่',
    ],
];
$t = $labels[$lang];

$typeLabels = [
    'tour_package'     => $isThai ? 'แพ็คเกจทัวร์'  : 'Tour Package',
    'quotation'        => $isThai ? 'ใบเสนอราคา'   : 'Quotation',
    'booking_confirm'  => $isThai ? 'ยืนยันการจอง' : 'Booking Confirm',
    'payment_reminder' => $isThai ? 'แจ้งชำระเงิน' : 'Payment Reminder',
    'voucher'          => $isThai ? 'วอเชอร์'      : 'Voucher',
    'custom'           => $isThai ? 'อื่นๆ'         : 'Custom',
];
?>
<?php $currentNavPage = 'line_templates'; include __DIR__ . '/_nav.php'; ?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_error']); endif; ?>

<div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; flex-wrap:wrap; gap:10px;">
        <h4 style="margin:0;"><i class="fa fa-clone"></i> <?= $t['page_title'] ?></h4>
        <a href="index.php?page=line_template_edit" class="btn btn-success btn-sm">
            <i class="fa fa-plus"></i> <?= $t['new'] ?>
        </a>
    </div>

    <?php if (empty($templates)): ?>
        <p style="text-align:center; padding:40px 20px; color:#999;"><?= $t['no_templates'] ?></p>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover" style="margin:0;">
            <thead>
                <tr>
                    <th><?= $t['name'] ?></th>
                    <th><?= $t['type'] ?></th>
                    <th><?= $t['message_kind'] ?></th>
                    <th><?= $t['languages'] ?></th>
                    <th><?= $t['active'] ?></th>
                    <th style="text-align:right;"><?= $t['actions'] ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($templates as $tpl): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($tpl['name'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                    <td><span class="label label-info"><?= htmlspecialchars($typeLabels[$tpl['template_type']] ?? $tpl['template_type'], ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><code><?= htmlspecialchars($tpl['message_type'], ENT_QUOTES, 'UTF-8') ?></code></td>
                    <td>
                        <?php if (!empty($tpl['content_th'])): ?><span class="label label-default">TH</span><?php endif; ?>
                        <?php if (!empty($tpl['content_en'])): ?><span class="label label-default">EN</span><?php endif; ?>
                    </td>
                    <td>
                        <?php if ($tpl['is_active']): ?>
                            <span class="label label-success"><?= $t['yes'] ?></span>
                        <?php else: ?>
                            <span class="label label-default"><?= $t['no'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:right;">
                        <a href="index.php?page=line_template_edit&amp;id=<?= (int)$tpl['id'] ?>" class="btn btn-xs btn-outline-primary"><i class="fa fa-pencil"></i> <?= $t['edit'] ?></a>
                        <form method="POST" action="index.php?page=line_template_save" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_delete'] ?>');">
                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$tpl['id'] ?>">
                            <button type="submit" class="btn btn-xs btn-outline-danger"><i class="fa fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>
</div>
