<?php
/**
 * LINE OA Webhook Event Log
 */
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$labels = [
    'en' => [
        'page_title' => 'Webhook Event Log',
        'event_type' => 'Event Type',
        'payload' => 'Payload',
        'processed' => 'Processed',
        'date' => 'Date',
        'yes' => 'Yes',
        'no' => 'No',
        'no_events' => 'No webhook events recorded.',
    ],
    'th' => [
        'page_title' => 'บันทึกเหตุการณ์ Webhook',
        'event_type' => 'ประเภทเหตุการณ์',
        'payload' => 'ข้อมูล',
        'processed' => 'ประมวลผลแล้ว',
        'date' => 'วันที่',
        'yes' => 'ใช่',
        'no' => 'ไม่ใช่',
        'no_events' => 'ยังไม่มีเหตุการณ์ webhook',
    ]
];
$t = $labels[$lang];
?>

<div class="row">
    <div class="col-lg-12">
        <h3 class="page-header"><i class="fa fa-list-alt"></i> <?= $t['page_title'] ?></h3>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-body">
        <?php if (empty($events)): ?>
            <p class="text-muted text-center"><?= $t['no_events'] ?></p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover" style="font-size: 12px;">
                <thead>
                    <tr>
                        <th>#</th>
                        <th><?= $t['event_type'] ?></th>
                        <th><?= $t['payload'] ?></th>
                        <th><?= $t['processed'] ?></th>
                        <th><?= $t['date'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $evt): ?>
                    <tr>
                        <td><?= $evt['id'] ?></td>
                        <td><span class="label label-info"><?= htmlspecialchars($evt['event_type'], ENT_QUOTES, 'UTF-8') ?></span></td>
                        <td style="max-width: 500px; word-break: break-all;">
                            <pre style="max-height:100px; overflow:auto; font-size:10px; margin:0; background:#f8f8f8; padding:5px;"><?= htmlspecialchars(json_encode(json_decode($evt['event_json']), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8') ?></pre>
                        </td>
                        <td><span class="label label-<?= $evt['processed'] ? 'success' : 'default' ?>"><?= $evt['processed'] ? $t['yes'] : $t['no'] ?></span></td>
                        <td><?= date('d M Y H:i:s', strtotime($evt['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
