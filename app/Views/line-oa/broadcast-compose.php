<?php
$pageTitle = 'LINE OA — Compose Broadcast';
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$isThai = ($lang === 'th');
$isNew = empty($broadcast);
$labels = [
    'en' => [
        'page_title' => $isNew ? 'New Broadcast' : 'Edit Broadcast',
        'name' => 'Campaign Name',
        'audience' => 'Audience',
        'audience_all' => 'All friends',
        'audience_tag' => 'By tag',
        'audience_has_booked' => 'Has booked',
        'audience_last_active' => 'Active in last N days',
        'tag_select' => 'Select tag',
        'days' => 'Days',
        'recipient_count' => 'Recipients',
        'message' => 'Message',
        'message_text' => 'Plain text',
        'message_template' => 'From template',
        'message_custom_flex' => 'Custom Flex JSON',
        'pick_template' => 'Pick a template',
        'text_th' => 'Thai text', 'text_en' => 'English text',
        'flex_th' => 'Thai Flex JSON', 'flex_en' => 'English Flex JSON',
        'alt_text' => 'Notification text',
        'schedule' => 'Schedule',
        'send_now' => 'Send Now',
        'save_draft' => 'Save Draft',
        'schedule_btn' => 'Schedule',
        'cancel' => 'Cancel',
        'schedule_at' => 'Send at',
        'confirm_send' => 'Send this broadcast immediately to {N} recipients?',
    ],
    'th' => [
        'page_title' => $isNew ? 'แคมเปญใหม่' : 'แก้ไขแคมเปญ',
        'name' => 'ชื่อแคมเปญ',
        'audience' => 'กลุ่มเป้าหมาย',
        'audience_all' => 'เพื่อนทั้งหมด',
        'audience_tag' => 'ตามแท็ก',
        'audience_has_booked' => 'เคยจอง',
        'audience_last_active' => 'ใช้งานใน N วันล่าสุด',
        'tag_select' => 'เลือกแท็ก',
        'days' => 'จำนวนวัน',
        'recipient_count' => 'จำนวนผู้รับ',
        'message' => 'ข้อความ',
        'message_text' => 'ข้อความธรรมดา',
        'message_template' => 'จากเทมเพลต',
        'message_custom_flex' => 'Flex JSON กำหนดเอง',
        'pick_template' => 'เลือกเทมเพลต',
        'text_th' => 'ข้อความภาษาไทย', 'text_en' => 'ข้อความภาษาอังกฤษ',
        'flex_th' => 'Flex JSON ภาษาไทย', 'flex_en' => 'Flex JSON ภาษาอังกฤษ',
        'alt_text' => 'ข้อความแจ้งเตือน',
        'schedule' => 'ตั้งเวลา',
        'send_now' => 'ส่งทันที',
        'save_draft' => 'บันทึกแบบร่าง',
        'schedule_btn' => 'ตั้งเวลาส่ง',
        'cancel' => 'ยกเลิก',
        'schedule_at' => 'ส่งเวลา',
        'confirm_send' => 'ส่งแคมเปญนี้ไปยัง {N} รายทันที?',
    ],
];
$t = $labels[$lang];

$audienceFilter = !empty($broadcast['audience_filter_json'])
    ? (json_decode($broadcast['audience_filter_json'], true) ?: [])
    : [];
$audienceType = $broadcast['audience_type'] ?? 'all';
$messageKind  = $broadcast['message_kind'] ?? 'text';
?>
<?php $currentNavPage = 'line_broadcasts'; include __DIR__ . '/_nav.php'; ?>

<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_error']); endif; ?>

<form method="POST" action="index.php?page=line_broadcast_save" id="broadcast-form">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
    <input type="hidden" name="action" id="form-action" value="save_draft">
    <input type="hidden" name="id" value="<?= (int)($broadcast['id'] ?? 0) ?>">

    <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-bottom:16px;">
        <h4 style="margin-top:0;"><i class="fa fa-bullhorn"></i> <?= $t['page_title'] ?></h4>

        <div class="form-group">
            <label><?= $t['name'] ?> *</label>
            <input type="text" name="name" class="form-control" required maxlength="200"
                   value="<?= htmlspecialchars($broadcast['name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
    </div>

    <div style="display:flex; gap:16px; flex-wrap:wrap;">

    <!-- Step 1: Audience -->
    <div style="flex:1; min-width:280px;">
        <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <h5 style="margin-top:0;"><i class="fa fa-users"></i> 1. <?= $t['audience'] ?></h5>

            <div class="form-group">
                <label class="radio-inline"><input type="radio" name="audience_type" value="all" <?= $audienceType === 'all' ? 'checked' : '' ?>> <?= $t['audience_all'] ?></label><br>
                <label class="radio-inline"><input type="radio" name="audience_type" value="tag" <?= $audienceType === 'tag' ? 'checked' : '' ?>> <?= $t['audience_tag'] ?></label><br>
                <label class="radio-inline"><input type="radio" name="audience_type" value="has_booked" <?= $audienceType === 'has_booked' ? 'checked' : '' ?>> <?= $t['audience_has_booked'] ?></label><br>
                <label class="radio-inline"><input type="radio" name="audience_type" value="last_active" <?= $audienceType === 'last_active' ? 'checked' : '' ?>> <?= $t['audience_last_active'] ?></label>
            </div>

            <div class="form-group" id="filter-tag" style="display:<?= $audienceType === 'tag' ? 'block' : 'none' ?>;">
                <label><?= $t['tag_select'] ?></label>
                <select name="audience_filter[tag_id]" class="form-control">
                    <option value="">—</option>
                    <?php foreach (($tags ?? []) as $tag): ?>
                        <option value="<?= (int)$tag['id'] ?>" <?= ($audienceFilter['tag_id'] ?? 0) == $tag['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8') ?> (<?= (int)($tag['user_count'] ?? 0) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" id="filter-days" style="display:<?= $audienceType === 'last_active' ? 'block' : 'none' ?>;">
                <label><?= $t['days'] ?></label>
                <input type="number" name="audience_filter[days]" class="form-control" min="1" max="365"
                       value="<?= (int)($audienceFilter['days'] ?? 30) ?>">
            </div>

            <div style="background:#f0f8ff; padding:12px; border-radius:8px; margin-top:16px;">
                <strong><?= $t['recipient_count'] ?>:</strong>
                <span id="audience-count" style="font-size:1.4rem; color:#06C755; font-weight:bold;">…</span>
            </div>
        </div>
    </div>

    <!-- Step 2: Message -->
    <div style="flex:2; min-width:380px;">
        <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
            <h5 style="margin-top:0;"><i class="fa fa-envelope"></i> 2. <?= $t['message'] ?></h5>

            <div class="form-group">
                <label class="radio-inline"><input type="radio" name="message_kind" value="text" <?= $messageKind === 'text' ? 'checked' : '' ?>> <?= $t['message_text'] ?></label>
                <label class="radio-inline" style="margin-left:15px;"><input type="radio" name="message_kind" value="template" <?= $messageKind === 'template' ? 'checked' : '' ?>> <?= $t['message_template'] ?></label>
                <label class="radio-inline" style="margin-left:15px;"><input type="radio" name="message_kind" value="custom_flex" <?= $messageKind === 'custom_flex' ? 'checked' : '' ?>> <?= $t['message_custom_flex'] ?></label>
            </div>

            <!-- Text mode -->
            <div id="kind-text" style="display:<?= $messageKind === 'text' ? 'block' : 'none' ?>;">
                <div class="form-group">
                    <label>🇹🇭 <?= $t['text_th'] ?></label>
                    <textarea name="text_content_th" class="form-control" rows="4"><?= htmlspecialchars($broadcast['text_content_th'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="form-group">
                    <label>🇬🇧 <?= $t['text_en'] ?></label>
                    <textarea name="text_content_en" class="form-control" rows="4"><?= htmlspecialchars($broadcast['text_content_en'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <!-- Template mode -->
            <div id="kind-template" style="display:<?= $messageKind === 'template' ? 'block' : 'none' ?>;">
                <div class="form-group">
                    <label><?= $t['pick_template'] ?></label>
                    <select name="template_id" class="form-control">
                        <option value="">—</option>
                        <?php foreach (($templates ?? []) as $tpl): ?>
                            <option value="<?= (int)$tpl['id'] ?>" <?= ($broadcast['template_id'] ?? 0) == $tpl['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tpl['name'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Custom Flex mode -->
            <div id="kind-flex" style="display:<?= $messageKind === 'custom_flex' ? 'block' : 'none' ?>;">
                <div class="form-group">
                    <label>🇹🇭 <?= $t['flex_th'] ?></label>
                    <textarea name="flex_content_th" class="form-control" rows="6" style="font-family:monospace; font-size:0.85rem;"><?= htmlspecialchars($broadcast['flex_content_th'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                <div class="form-group">
                    <label>🇬🇧 <?= $t['flex_en'] ?></label>
                    <textarea name="flex_content_en" class="form-control" rows="6" style="font-family:monospace; font-size:0.85rem;"><?= htmlspecialchars($broadcast['flex_content_en'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
            </div>

            <div class="form-group">
                <label><?= $t['alt_text'] ?></label>
                <input type="text" name="alt_text" class="form-control" maxlength="400"
                       value="<?= htmlspecialchars($broadcast['alt_text'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
    </div>

    </div>

    <!-- Step 3: Schedule + Send -->
    <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06); margin-top:16px;">
        <h5 style="margin-top:0;"><i class="fa fa-clock-o"></i> 3. <?= $t['schedule'] ?></h5>
        <div class="form-group">
            <label><?= $t['schedule_at'] ?></label>
            <input type="datetime-local" name="scheduled_at" class="form-control" style="max-width:300px;"
                   value="<?= !empty($broadcast['scheduled_at']) ? date('Y-m-d\TH:i', strtotime($broadcast['scheduled_at'])) : '' ?>">
        </div>

        <div style="margin-top:20px;">
            <button type="button" class="btn btn-default" onclick="submitWith('save_draft')"><i class="fa fa-save"></i> <?= $t['save_draft'] ?></button>
            <button type="button" class="btn btn-info" onclick="submitWith('schedule')"><i class="fa fa-clock-o"></i> <?= $t['schedule_btn'] ?></button>
            <button type="button" class="btn btn-success" onclick="confirmSendNow()"><i class="fa fa-paper-plane"></i> <?= $t['send_now'] ?></button>
            <a href="index.php?page=line_broadcasts" class="btn btn-link"><?= $t['cancel'] ?></a>
        </div>
    </div>
</form>

<script>
(function() {
    function show(id, on) { document.getElementById(id).style.display = on ? 'block' : 'none'; }

    // Audience-type radios -> show/hide sub-filters + refresh count
    document.querySelectorAll('input[name="audience_type"]').forEach(function(r) {
        r.addEventListener('change', function() {
            show('filter-tag',  r.value === 'tag'  && r.checked);
            show('filter-days', r.value === 'last_active' && r.checked);
            refreshAudienceCount();
        });
    });
    document.querySelectorAll('select[name="audience_filter[tag_id]"], input[name="audience_filter[days]"]').forEach(function(el) {
        el.addEventListener('change', refreshAudienceCount);
    });

    // Message-kind radios -> swap sections
    document.querySelectorAll('input[name="message_kind"]').forEach(function(r) {
        r.addEventListener('change', function() {
            show('kind-text',     r.value === 'text'        && r.checked);
            show('kind-template', r.value === 'template'    && r.checked);
            show('kind-flex',     r.value === 'custom_flex' && r.checked);
        });
    });

    var lastCount = 0;
    function refreshAudienceCount() {
        var type   = document.querySelector('input[name="audience_type"]:checked').value;
        var filter = {};
        var tagSel = document.querySelector('select[name="audience_filter[tag_id]"]');
        if (tagSel && tagSel.value) filter.tag_id = parseInt(tagSel.value, 10);
        var daysIn = document.querySelector('input[name="audience_filter[days]"]');
        if (daysIn && daysIn.value) filter.days = parseInt(daysIn.value, 10);
        var url = 'index.php?page=line_broadcast_audience_count&audience_type=' + encodeURIComponent(type) +
                  '&filter=' + encodeURIComponent(JSON.stringify(filter));
        fetch(url, { credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(j) {
                lastCount = j.count || 0;
                document.getElementById('audience-count').textContent = lastCount.toLocaleString();
            })
            .catch(function() { document.getElementById('audience-count').textContent = '?'; });
    }

    window.submitWith = function(action) {
        document.getElementById('form-action').value = action;
        document.getElementById('broadcast-form').submit();
    };
    window.confirmSendNow = function() {
        var msg = <?= json_encode($t['confirm_send']) ?>.replace('{N}', lastCount.toLocaleString());
        if (confirm(msg)) submitWith('send_now');
    };

    refreshAudienceCount();
})();
</script>
</div>
