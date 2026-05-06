<?php
$pageTitle = 'LINE OA — Edit Template';
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$isThai = ($lang === 'th');
$isNew = empty($template);
$labels = [
    'en' => [
        'page_title' => $isNew ? 'New Template' : 'Edit Template',
        'name'         => 'Name',
        'template_type' => 'Type',
        'message_type' => 'Message Format',
        'alt_text'     => 'Notification Text',
        'alt_help'     => 'Shown in the LINE notification preview before the user opens the chat.',
        'content_th'   => 'Thai Content (JSON or text)',
        'content_en'   => 'English Content (JSON or text)',
        'variables'    => 'Variables (one per line, e.g. tour_name)',
        'active'       => 'Active',
        'save'         => 'Save Template',
        'cancel'       => 'Cancel',
        'help'         => 'Use {variable_name} placeholders. They will be replaced at send time.',
        'flex_help'    => 'For Flex messages, paste the LINE Flex bubble JSON. Use the official designer at developers.line.biz/flex-simulator.',
        'yes' => 'Yes', 'no' => 'No',
    ],
    'th' => [
        'page_title' => $isNew ? 'เทมเพลตใหม่' : 'แก้ไขเทมเพลต',
        'name'         => 'ชื่อ',
        'template_type' => 'ประเภท',
        'message_type' => 'รูปแบบข้อความ',
        'alt_text'     => 'ข้อความแจ้งเตือน',
        'alt_help'     => 'แสดงในการแจ้งเตือน LINE ก่อนผู้ใช้เปิดแชท',
        'content_th'   => 'เนื้อหาภาษาไทย (JSON หรือข้อความ)',
        'content_en'   => 'เนื้อหาภาษาอังกฤษ (JSON หรือข้อความ)',
        'variables'    => 'ตัวแปร (บรรทัดละ 1 ชื่อ เช่น tour_name)',
        'active'       => 'ใช้งาน',
        'save'         => 'บันทึก',
        'cancel'       => 'ยกเลิก',
        'help'         => 'ใช้ {ชื่อตัวแปร} เป็นตัวแทน จะถูกแทนที่ตอนส่ง',
        'flex_help'    => 'สำหรับข้อความ Flex ให้วาง JSON Bubble ของ LINE ใช้เครื่องมือออกแบบที่ developers.line.biz/flex-simulator',
        'yes' => 'ใช่', 'no' => 'ไม่ใช่',
    ],
];
$t = $labels[$lang];
$typeOptions = [
    'tour_package'     => $isThai ? 'แพ็คเกจทัวร์'  : 'Tour Package',
    'quotation'        => $isThai ? 'ใบเสนอราคา'   : 'Quotation',
    'booking_confirm'  => $isThai ? 'ยืนยันการจอง' : 'Booking Confirm',
    'payment_reminder' => $isThai ? 'แจ้งชำระเงิน' : 'Payment Reminder',
    'voucher'          => $isThai ? 'วอเชอร์'      : 'Voucher',
    'custom'           => $isThai ? 'อื่นๆ'         : 'Custom',
];

// Decode variables_json into newline-separated text for the textarea
$variablesText = '';
if (!empty($template['variables_json'])) {
    $vars = json_decode($template['variables_json'], true);
    if (is_array($vars)) $variablesText = implode("\n", $vars);
}
?>
<?php $currentNavPage = 'line_templates'; include __DIR__ . '/_nav.php'; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_error']); endif; ?>

<form method="POST" action="index.php?page=line_template_save" id="template-form">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="id" value="<?= (int)($template['id'] ?? 0) ?>">

    <div style="display:flex; gap:20px; flex-wrap:wrap;">

    <div style="flex:2; min-width:380px;">
        <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <h4 style="margin-top:0;"><i class="fa fa-clone"></i> <?= $t['page_title'] ?></h4>

            <div class="form-group">
                <label><?= $t['name'] ?> *</label>
                <input type="text" name="name" class="form-control" required maxlength="150"
                       value="<?= htmlspecialchars($template['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label><?= $t['template_type'] ?></label>
                <select name="template_type" class="form-control">
                    <?php foreach ($typeOptions as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($template['template_type'] ?? 'custom') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label><?= $t['message_type'] ?></label>
                <select name="message_type" class="form-control" id="message-type-select">
                    <option value="flex" <?= ($template['message_type'] ?? 'flex') === 'flex' ? 'selected' : '' ?>>Flex (rich card)</option>
                    <option value="text" <?= ($template['message_type'] ?? '') === 'text' ? 'selected' : '' ?>>Text</option>
                </select>
                <p class="help-block" id="flex-help-text" style="display:<?= ($template['message_type'] ?? 'flex') === 'flex' ? 'block' : 'none' ?>;">
                    <?= $t['flex_help'] ?>
                </p>
            </div>

            <div class="form-group">
                <label><?= $t['alt_text'] ?></label>
                <input type="text" name="alt_text" class="form-control" maxlength="400"
                       value="<?= htmlspecialchars($template['alt_text'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <p class="help-block"><?= $t['alt_help'] ?></p>
            </div>

            <div class="form-group">
                <label>🇹🇭 <?= $t['content_th'] ?></label>
                <textarea name="content_th" class="form-control" rows="10" style="font-family:monospace; font-size:0.85rem;"><?= htmlspecialchars($template['content_th'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="form-group">
                <label>🇬🇧 <?= $t['content_en'] ?></label>
                <textarea name="content_en" class="form-control" rows="10" style="font-family:monospace; font-size:0.85rem;"><?= htmlspecialchars($template['content_en'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="form-group">
                <label><?= $t['variables'] ?></label>
                <textarea id="variables-text" class="form-control" rows="4" placeholder="tour_name&#10;price&#10;date"><?= htmlspecialchars($variablesText, ENT_QUOTES, 'UTF-8') ?></textarea>
                <input type="hidden" name="variables_json" id="variables-json" value="<?= htmlspecialchars($template['variables_json'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <p class="help-block"><?= $t['help'] ?></p>
            </div>

            <div class="form-group">
                <label><?= $t['active'] ?></label>
                <select name="is_active" class="form-control">
                    <option value="1" <?= ($template['is_active'] ?? 1) ? 'selected' : '' ?>><?= $t['yes'] ?></option>
                    <option value="0" <?= !($template['is_active'] ?? 1) ? 'selected' : '' ?>><?= $t['no'] ?></option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?= $t['save'] ?></button>
            <a href="index.php?page=line_templates" class="btn btn-default"><?= $t['cancel'] ?></a>
        </div>
    </div>

    <div style="flex:1; min-width:280px;">
        <div style="background:#f9f9f9; border-radius:12px; padding:20px;">
            <h5><i class="fa fa-lightbulb-o"></i> <?= $isThai ? 'ตัวอย่าง JSON Flex' : 'Flex JSON example' ?></h5>
            <pre style="font-size:0.7rem; background:white; padding:10px; border-radius:6px; max-height:280px; overflow:auto;">{
  "type":"bubble",
  "hero":{
    "type":"image",
    "url":"{image_url}",
    "size":"full"
  },
  "body":{
    "type":"box",
    "layout":"vertical",
    "contents":[
      {"type":"text","text":"{tour_name}","weight":"bold","size":"xl"},
      {"type":"text","text":"฿{price}","color":"#06C755"}
    ]
  }
}</pre>
            <p style="font-size:0.85rem; margin-top:10px;">
                <?= $isThai ? 'ตัวอักษร' : 'Tokens' ?>:
                <code>{tour_name}</code>, <code>{price}</code>, <code>{image_url}</code>, <code>{book_url}</code>
            </p>
        </div>
    </div>

    </div>
</form>

<script>
// Sync the human-friendly variables textarea -> hidden JSON field on submit
document.getElementById('template-form').addEventListener('submit', function() {
    var raw = document.getElementById('variables-text').value || '';
    var lines = raw.split(/\r?\n/).map(function(s) { return s.trim(); }).filter(Boolean);
    document.getElementById('variables-json').value = JSON.stringify(lines);
});
// Toggle the flex-help hint based on message_type
document.getElementById('message-type-select').addEventListener('change', function() {
    document.getElementById('flex-help-text').style.display = this.value === 'flex' ? 'block' : 'none';
});
</script>
</div>
