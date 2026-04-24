<?php
$pageTitle = 'LINE OA — Auto Replies';

/**
 * LINE OA Auto-Reply Rules
 */
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$labels = [
    'en' => [
        'page_title' => 'Auto-Reply Rules',
        'add_rule' => 'Add Rule',
        'keyword' => 'Trigger Keyword',
        'match_type' => 'Match Type',
        'reply_type' => 'Reply Type',
        'reply_content' => 'Reply Content',
        'priority' => 'Priority',
        'active' => 'Active',
        'actions' => 'Actions',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'exact' => 'Exact Match',
        'contains' => 'Contains',
        'regex' => 'Regex',
        'text' => 'Text',
        'flex' => 'Flex Message',
        'template' => 'Template',
        'image' => 'Image',
        'yes' => 'Yes',
        'no' => 'No',
        'no_rules' => 'No auto-reply rules configured.',
        'confirm_delete' => 'Are you sure you want to delete this rule?',
        'higher_priority' => 'Higher number = checked first',
    ],
    'th' => [
        'page_title' => 'กฎตอบกลับอัตโนมัติ',
        'add_rule' => 'เพิ่มกฎ',
        'keyword' => 'คำกระตุ้น',
        'match_type' => 'ประเภทการจับคู่',
        'reply_type' => 'ประเภทการตอบ',
        'reply_content' => 'เนื้อหาตอบกลับ',
        'priority' => 'ลำดับความสำคัญ',
        'active' => 'เปิดใช้งาน',
        'actions' => 'จัดการ',
        'edit' => 'แก้ไข',
        'delete' => 'ลบ',
        'save' => 'บันทึก',
        'cancel' => 'ยกเลิก',
        'exact' => 'ตรงทั้งหมด',
        'contains' => 'มีคำ',
        'regex' => 'Regex',
        'text' => 'ข้อความ',
        'flex' => 'Flex Message',
        'template' => 'Template',
        'image' => 'รูปภาพ',
        'yes' => 'ใช่',
        'no' => 'ไม่ใช่',
        'no_rules' => 'ยังไม่มีกฎตอบกลับอัตโนมัติ',
        'confirm_delete' => 'คุณแน่ใจหรือไม่ที่จะลบกฎนี้?',
        'higher_priority' => 'ตัวเลขสูง = ตรวจสอบก่อน',
    ]
];
$t = $labels[$lang];
?>

<?php $currentNavPage = 'line_auto_replies'; include __DIR__ . '/_nav.php'; ?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_error']); endif; ?>

<div style="margin-bottom:15px;"><button class="btn btn-success" data-toggle="modal" data-target="#ruleModal" onclick="resetForm()"><i class="fa fa-plus"></i> <?= $t['add_rule'] ?></button></div>

<div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <?php if (empty($rules)): ?>
            <p class="text-muted text-center"><?= $t['no_rules'] ?></p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th><?= $t['keyword'] ?></th>
                        <th><?= $t['match_type'] ?></th>
                        <th><?= $t['reply_content'] ?></th>
                        <th><?= $t['priority'] ?></th>
                        <th><?= $t['active'] ?></th>
                        <th><?= $t['actions'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rules as $rule): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($rule['trigger_keyword'], ENT_QUOTES, 'UTF-8') ?></code></td>
                        <td><span class="label label-default"><?= $t[$rule['match_type']] ?? $rule['match_type'] ?></span></td>
                        <td style="max-width:300px; word-break:break-word;"><?= htmlspecialchars(mb_substr($rule['reply_content'], 0, 80), ENT_QUOTES, 'UTF-8') ?><?= mb_strlen($rule['reply_content']) > 80 ? '...' : '' ?></td>
                        <td><?= $rule['priority'] ?></td>
                        <td><span class="label label-<?= $rule['is_active'] ? 'success' : 'default' ?>"><?= $rule['is_active'] ? $t['yes'] : $t['no'] ?></span></td>
                        <td>
                            <button class="btn btn-xs btn-info" data-rule='<?= json_encode($rule, JSON_HEX_APOS | JSON_HEX_QUOT) ?>' onclick="editRule(JSON.parse(this.getAttribute('data-rule')))"><i class="fa fa-edit"></i></button>
                            <form method="POST" action="index.php?page=line_store" style="display:inline;" onsubmit="return confirm('<?= $t['confirm_delete'] ?>')">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <input type="hidden" name="action" value="delete_auto_reply">
                                <input type="hidden" name="reply_id" value="<?= $rule['id'] ?>">
                                <button type="submit" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
</div>

<!-- Add/Edit Modal -->
<div class="modal fade" id="ruleModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="index.php?page=line_store">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="action" value="save_auto_reply">
                <input type="hidden" name="reply_id" id="reply_id" value="">

                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title"><i class="fa fa-reply"></i> <?= $t['add_rule'] ?></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label><?= $t['keyword'] ?></label>
                        <input type="text" name="trigger_keyword" id="trigger_keyword" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label><?= $t['match_type'] ?></label>
                        <select name="match_type" id="match_type" class="form-control">
                            <option value="contains"><?= $t['contains'] ?></option>
                            <option value="exact"><?= $t['exact'] ?></option>
                            <option value="regex"><?= $t['regex'] ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?= $t['reply_type'] ?></label>
                        <select name="reply_type" id="reply_type" class="form-control">
                            <option value="text"><?= $t['text'] ?></option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><?= $t['reply_content'] ?></label>
                        <textarea name="reply_content" id="reply_content" class="form-control" rows="4" required></textarea>
                    </div>
                    <div class="form-group">
                        <label><?= $t['priority'] ?> <small class="text-muted">(<?= $t['higher_priority'] ?>)</small></label>
                        <input type="number" name="priority" id="priority" class="form-control" value="0" min="0">
                    </div>
                    <div class="form-group">
                        <label><?= $t['active'] ?></label>
                        <select name="is_active" id="is_active" class="form-control">
                            <option value="1"><?= $t['yes'] ?></option>
                            <option value="0"><?= $t['no'] ?></option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= $t['cancel'] ?></button>
                    <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> <?= $t['save'] ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetForm() {
    document.getElementById('reply_id').value = '';
    document.getElementById('trigger_keyword').value = '';
    document.getElementById('match_type').value = 'contains';
    document.getElementById('reply_type').value = 'text';
    document.getElementById('reply_content').value = '';
    document.getElementById('priority').value = '0';
    document.getElementById('is_active').value = '1';
}

function editRule(rule) {
    document.getElementById('reply_id').value = rule.id;
    document.getElementById('trigger_keyword').value = rule.trigger_keyword;
    document.getElementById('match_type').value = rule.match_type;
    document.getElementById('reply_type').value = rule.reply_type;
    document.getElementById('reply_content').value = rule.reply_content;
    document.getElementById('priority').value = rule.priority;
    document.getElementById('is_active').value = rule.is_active;
    $('#ruleModal').modal('show');
}
</script>
</div><!-- /master-data-container -->
