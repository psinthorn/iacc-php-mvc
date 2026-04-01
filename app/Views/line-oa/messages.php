<?php
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

<div class="row">
    <div class="col-lg-12">
        <h3 class="page-header"><i class="fa fa-comments"></i> <?= $t['page_title'] ?></h3>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-body">
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
                            <a href="?page=line_messages&user_id=<?= $msg['line_user_id'] ?>">
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
</div>
