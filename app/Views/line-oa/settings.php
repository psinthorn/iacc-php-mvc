<?php
/**
 * LINE OA Settings
 * Configure LINE channel credentials and behavior
 */
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$labels = [
    'en' => [
        'page_title' => 'LINE OA Settings',
        'channel_config' => 'Channel Configuration',
        'channel_id' => 'Channel ID',
        'channel_secret' => 'Channel Secret',
        'channel_access_token' => 'Channel Access Token',
        'webhook_url' => 'Webhook URL',
        'webhook_help' => 'Set this URL in LINE Developers Console → Messaging API → Webhook URL',
        'is_active' => 'Active',
        'auto_reply' => 'Auto-Reply Enabled',
        'greeting_message' => 'Greeting Message',
        'greeting_help' => 'Sent when a user adds your LINE OA as friend. Use {NAME} for user display name.',
        'save' => 'Save Settings',
        'yes' => 'Yes',
        'no' => 'No',
        'how_to_setup' => 'How to Setup',
        'step1' => '1. Go to LINE Developers Console (developers.line.biz)',
        'step2' => '2. Create a Provider and Channel (Messaging API)',
        'step3' => '3. Copy Channel ID, Channel Secret, and issue a Channel Access Token',
        'step4' => '4. Paste them here and click Save',
        'step5' => '5. Copy the Webhook URL below and paste it in LINE Developers Console',
        'step6' => '6. Enable "Use webhook" in LINE Developers Console',
        'your_webhook_url' => 'Your Webhook URL',
    ],
    'th' => [
        'page_title' => 'ตั้งค่า LINE OA',
        'channel_config' => 'การตั้งค่าช่องทาง',
        'channel_id' => 'Channel ID',
        'channel_secret' => 'Channel Secret',
        'channel_access_token' => 'Channel Access Token',
        'webhook_url' => 'Webhook URL',
        'webhook_help' => 'ตั้งค่า URL นี้ใน LINE Developers Console → Messaging API → Webhook URL',
        'is_active' => 'เปิดใช้งาน',
        'auto_reply' => 'เปิดตอบกลับอัตโนมัติ',
        'greeting_message' => 'ข้อความต้อนรับ',
        'greeting_help' => 'ส่งเมื่อผู้ใช้เพิ่ม LINE OA เป็นเพื่อน ใช้ {NAME} แทนชื่อผู้ใช้',
        'save' => 'บันทึกการตั้งค่า',
        'yes' => 'ใช่',
        'no' => 'ไม่ใช่',
        'how_to_setup' => 'วิธีการตั้งค่า',
        'step1' => '1. ไปที่ LINE Developers Console (developers.line.biz)',
        'step2' => '2. สร้าง Provider และ Channel (Messaging API)',
        'step3' => '3. คัดลอก Channel ID, Channel Secret และออก Channel Access Token',
        'step4' => '4. วางที่นี่แล้วกดบันทึก',
        'step5' => '5. คัดลอก Webhook URL ด้านล่างและวางใน LINE Developers Console',
        'step6' => '6. เปิดใช้งาน "Use webhook" ใน LINE Developers Console',
        'your_webhook_url' => 'Webhook URL ของคุณ',
    ]
];
$t = $labels[$lang];

$companyId = $_SESSION['com_id'] ?? 0;
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$generatedWebhookUrl = $protocol . '://' . $host . '/line-webhook.php?company_id=' . $companyId;
?>
<?php $currentNavPage = 'line_settings'; include __DIR__ . '/_nav.php'; ?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_error']); endif; ?>

<div style="display:flex; gap:20px; flex-wrap:wrap;">
    <div style="flex:2; min-width:400px;">
        <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <h4 style="margin-top:0;"><i class="fa fa-cogs"></i> <?= $t['channel_config'] ?></h4>
                <form method="POST" action="index.php?page=line_store">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="action" value="save_config">

                    <div class="form-group">
                        <label><?= $t['channel_id'] ?></label>
                        <input type="text" name="channel_id" class="form-control" value="<?= htmlspecialchars($config['channel_id'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. 1234567890">
                    </div>

                    <div class="form-group">
                        <label><?= $t['channel_secret'] ?></label>
                        <input type="password" name="channel_secret" class="form-control" value="<?= htmlspecialchars($config['channel_secret'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. abc123def456...">
                    </div>

                    <div class="form-group">
                        <label><?= $t['channel_access_token'] ?></label>
                        <textarea name="channel_access_token" class="form-control" rows="3" placeholder="Long-lived channel access token"><?= htmlspecialchars($config['channel_access_token'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label><?= $t['webhook_url'] ?></label>
                        <input type="text" name="webhook_url" class="form-control" value="<?= htmlspecialchars($config['webhook_url'] ?? $generatedWebhookUrl, ENT_QUOTES, 'UTF-8') ?>">
                        <p class="help-block"><?= $t['webhook_help'] ?></p>
                    </div>

                    <div class="form-group">
                        <label><?= $t['is_active'] ?></label>
                        <select name="is_active" class="form-control">
                            <option value="1" <?= ($config['is_active'] ?? 0) ? 'selected' : '' ?>><?= $t['yes'] ?></option>
                            <option value="0" <?= !($config['is_active'] ?? 0) ? 'selected' : '' ?>><?= $t['no'] ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?= $t['auto_reply'] ?></label>
                        <select name="auto_reply_enabled" class="form-control">
                            <option value="1" <?= ($config['auto_reply_enabled'] ?? 1) ? 'selected' : '' ?>><?= $t['yes'] ?></option>
                            <option value="0" <?= !($config['auto_reply_enabled'] ?? 1) ? 'selected' : '' ?>><?= $t['no'] ?></option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?= $t['greeting_message'] ?></label>
                        <textarea name="greeting_message" class="form-control" rows="4" placeholder="Welcome {NAME}!"><?= htmlspecialchars($config['greeting_message'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                        <p class="help-block"><?= $t['greeting_help'] ?></p>
                    </div>

                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?= $t['save'] ?></button>
                </form>
        </div>
    </div>

    <div style="flex:1; min-width:300px;">
        <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06); border-left:4px solid #3498db;">
            <h4 style="margin-top:0;"><i class="fa fa-question-circle"></i> <?= $t['how_to_setup'] ?></h4>
                <ol style="padding-left: 15px; line-height: 2;">
                    <li><?= $t['step1'] ?></li>
                    <li><?= $t['step2'] ?></li>
                    <li><?= $t['step3'] ?></li>
                    <li><?= $t['step4'] ?></li>
                    <li><?= $t['step5'] ?></li>
                    <li><?= $t['step6'] ?></li>
                </ol>

                <hr>
                <label><?= $t['your_webhook_url'] ?>:</label>
                <div class="input-group">
                    <input type="text" class="form-control" id="webhook-url-display" readonly value="<?= htmlspecialchars($generatedWebhookUrl, ENT_QUOTES, 'UTF-8') ?>">
                    <span class="input-group-btn">
                        <button class="btn btn-default" onclick="navigator.clipboard.writeText(document.getElementById('webhook-url-display').value)"><i class="fa fa-copy"></i></button>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

</div>
