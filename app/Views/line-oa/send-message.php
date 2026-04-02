<?php
/**
 * LINE OA Send Message
 */
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$labels = [
    'en' => [
        'page_title' => 'Send Message',
        'select_user' => 'Select User',
        'message_type' => 'Message Type',
        'type_text' => 'Text',
        'type_image' => 'Image',
        'message_content' => 'Message',
        'image_url' => 'Image URL',
        'preview_url' => 'Preview URL (optional)',
        'send' => 'Send Message',
        'placeholder_msg' => 'Enter your message here...',
        'placeholder_url' => 'https://example.com/image.jpg',
        'no_users' => 'No LINE users available. Users will appear after they add your LINE Official Account.',
    ],
    'th' => [
        'page_title' => 'ส่งข้อความ',
        'select_user' => 'เลือกผู้ใช้',
        'message_type' => 'ประเภทข้อความ',
        'type_text' => 'ข้อความ',
        'type_image' => 'รูปภาพ',
        'message_content' => 'ข้อความ',
        'image_url' => 'URL รูปภาพ',
        'preview_url' => 'URL ภาพตัวอย่าง (ไม่บังคับ)',
        'send' => 'ส่งข้อความ',
        'placeholder_msg' => 'พิมพ์ข้อความที่ต้องการส่ง...',
        'placeholder_url' => 'https://example.com/image.jpg',
        'no_users' => 'ยังไม่มีผู้ใช้ LINE ผู้ใช้จะปรากฏหลังจากเพิ่มบัญชี LINE Official ของคุณ',
    ]
];
$t = $labels[$lang];
?>

<?php $currentNavPage = 'line_send_message'; include __DIR__ . '/_nav.php'; ?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_error']); endif; ?>

<?php if (empty($lineUsers)): ?>
    <div class="alert alert-info"><?= $t['no_users'] ?></div>
<?php else: ?>
<div class="row">
    <div class="col-lg-6">
        <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
                <form method="POST" action="index.php?page=line_store">
                    <input type="hidden" name="action" value="send_message">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                    <div class="form-group">
                        <label><?= $t['select_user'] ?></label>
                        <select name="line_user_id" class="form-control" required>
                            <option value="">-- <?= $t['select_user'] ?> --</option>
                            <?php foreach ($lineUsers as $u): ?>
                            <option value="<?= $u['line_user_id'] ?>"><?= htmlspecialchars($u['display_name'] ?: $u['line_user_id'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label><?= $t['message_type'] ?></label>
                        <select name="message_type" id="message_type" class="form-control" onchange="toggleFields()">
                            <option value="text"><?= $t['type_text'] ?></option>
                            <option value="image"><?= $t['type_image'] ?></option>
                        </select>
                    </div>

                    <div class="form-group" id="field_text">
                        <label><?= $t['message_content'] ?></label>
                        <textarea name="message" class="form-control" rows="5" placeholder="<?= $t['placeholder_msg'] ?>"></textarea>
                    </div>

                    <div class="form-group" id="field_image" style="display:none;">
                        <label><?= $t['image_url'] ?></label>
                        <input type="url" name="image_url" class="form-control" placeholder="<?= $t['placeholder_url'] ?>">
                    </div>

                    <div class="form-group" id="field_preview" style="display:none;">
                        <label><?= $t['preview_url'] ?></label>
                        <input type="url" name="preview_url" class="form-control" placeholder="<?= $t['placeholder_url'] ?>">
                    </div>

                    <button type="submit" class="btn btn-success"><i class="fa fa-paper-plane"></i> <?= $t['send'] ?></button>
                </form>
            </div>
        </div>
</div>
<?php endif; ?>
</div><!-- /master-data-container -->

<script>
function toggleFields() {
    var type = document.getElementById('message_type').value;
    document.getElementById('field_text').style.display = type === 'text' ? '' : 'none';
    document.getElementById('field_image').style.display = type === 'image' ? '' : 'none';
    document.getElementById('field_preview').style.display = type === 'image' ? '' : 'none';
}
</script>
