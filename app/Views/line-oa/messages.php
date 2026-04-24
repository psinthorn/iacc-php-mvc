<?php
$pageTitle = 'LINE OA — Messages';

/**
 * LINE OA Messages Log
 */
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$labels = [
    'en' => [
        'page_title' => 'LINE Messages',
        'user' => 'User',
        'direction' => 'Direction',
        'type' => 'Type',
        'content' => 'Content',
        'status' => 'Status',
        'date' => 'Date',
        'inbound' => 'Inbound',
        'outbound' => 'Outbound',
        'no_messages' => 'No messages found.',
        'all_users' => 'All Users',
    ],
    'th' => [
        'page_title' => 'ข้อความ LINE',
        'user' => 'ผู้ใช้',
        'direction' => 'ทิศทาง',
        'type' => 'ประเภท',
        'content' => 'เนื้อหา',
        'status' => 'สถานะ',
        'date' => 'วันที่',
        'inbound' => 'ขาเข้า',
        'outbound' => 'ขาออก',
        'no_messages' => 'ไม่พบข้อความ',
        'all_users' => 'ผู้ใช้ทั้งหมด',
    ]
];
$t = $labels[$lang];
?>

<?php $currentNavPage = 'line_messages'; include __DIR__ . '/_nav.php'; ?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_error']); endif; ?>

<div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <?php if (empty($messages)): ?>
            <p class="text-muted text-center"><?= $t['no_messages'] ?></p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th><?= $t['user'] ?></th>
                        <th><?= $t['direction'] ?></th>
                        <th><?= $t['type'] ?></th>
                        <th><?= $t['content'] ?></th>
                        <th><?= $t['status'] ?></th>
                        <th><?= $t['date'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($messages as $msg): ?>
                    <tr>
                        <td>
                            <a href="index.php?page=line_messages&user_id=<?= $msg['line_user_id'] ?>">
                                <?= htmlspecialchars($msg['display_name'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </td>
                        <td>
                            <span class="label label-<?= $msg['direction'] === 'inbound' ? 'info' : 'success' ?>">
                                <?= $t[$msg['direction']] ?? $msg['direction'] ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($msg['message_type'] ?? 'text', ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="max-width: 300px; word-break: break-word;">
                            <?= htmlspecialchars(mb_substr($msg['content'] ?? '', 0, 100), ENT_QUOTES, 'UTF-8') ?>
                            <?= mb_strlen($msg['content'] ?? '') > 100 ? '...' : '' ?>
                        </td>
                        <td><?= htmlspecialchars($msg['status'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= date('d M Y H:i', strtotime($msg['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
</div>
</div><!-- /master-data-container -->
