<?php
$pageTitle = 'Tour Operator — Documents';

/**
 * Operator-side document library
 * Variables: $documents, $contracts, $message
 */
$isThai = ($_SESSION['lang'] ?? '0') === '1';

$messages = [
    'uploaded'      => ['✅', $isThai ? 'อัปโหลดสำเร็จ' : 'Document uploaded successfully'],
    'deleted'       => ['✅', $isThai ? 'ลบสำเร็จ' : 'Document deleted'],
    'missing_field' => ['⚠️', $isThai ? 'กรุณากรอกข้อมูลให้ครบ' : 'Please fill in all required fields'],
    'upload_error'  => ['⚠️', $isThai ? 'เกิดข้อผิดพลาดในการอัปโหลด' : 'Upload error'],
    'too_large'     => ['⚠️', $isThai ? 'ไฟล์ใหญ่เกิน 10MB' : 'File exceeds 10MB limit'],
    'bad_type'      => ['⚠️', $isThai ? 'ประเภทไฟล์ไม่ได้รับอนุญาต' : 'File type not allowed'],
    'save_failed'   => ['⚠️', $isThai ? 'บันทึกไฟล์ไม่สำเร็จ' : 'Failed to save file'],
    'not_found'     => ['⚠️', $isThai ? 'ไม่พบเอกสาร' : 'Document not found'],
    'error'         => ['⚠️', $isThai ? 'เกิดข้อผิดพลาด' : 'An error occurred'],
];

$categoryLabels = [
    'contract'   => [$isThai ? 'สัญญา'      : 'Contract',   '#0d9488'],
    'brochure'   => [$isThai ? 'โบรชัวร์'    : 'Brochure',   '#2563eb'],
    'terms'      => [$isThai ? 'ข้อกำหนด'   : 'Terms',      '#d97706'],
    'rate_sheet' => [$isThai ? 'ใบเสนอราคา' : 'Rate Sheet', '#7c3aed'],
    'other'      => [$isThai ? 'อื่นๆ'       : 'Other',      '#94a3b8'],
];

$visibilityLabels = [
    'all_agents'    => $isThai ? 'ตัวแทนทั้งหมด'   : 'All Agents',
    'contract'      => $isThai ? 'เฉพาะสัญญา'    : 'Contract Only',
    'operator_only' => $isThai ? 'ภายในเท่านั้น' : 'Internal Only',
];

function fmtSize($bytes) {
    if ($bytes < 1024) return $bytes . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
    return round($bytes / 1048576, 1) . ' MB';
}
?>

<link rel="stylesheet" href="css/master-data.css">

<style>
.doc-grid { display: grid; grid-template-columns: 360px 1fr; gap: 20px; }
.doc-form-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; }
.doc-form-card h3 { margin: 0 0 16px; font-size: 15px; color: #1e293b; padding-bottom: 12px; border-bottom: 1px solid #f1f5f9; }
.form-group { margin-bottom: 14px; }
.form-group label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 6px; }
.form-group input, .form-group select, .form-group textarea {
    width: 100%; padding: 9px 12px; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 13px; box-sizing: border-box;
}
.form-group textarea { min-height: 60px; resize: vertical; }
.form-group input[type=file] { padding: 8px; background: #f8fafc; }
.btn-upload { width: 100%; padding: 12px; background: #0d9488; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; }
.btn-upload:hover { background: #0f766e; }

.doc-table { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; overflow: hidden; }
.doc-table table { width: 100%; border-collapse: collapse; }
.doc-table th { background: #f8fafc; font-size: 12px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; padding: 12px 16px; text-align: left; border-bottom: 1px solid #e2e8f0; }
.doc-table td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; vertical-align: middle; }
.doc-table tr:last-child td { border-bottom: none; }
.doc-table tr:hover td { background: #f8fafc; }
.cat-badge { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 11px; font-weight: 500; color: white; }
.vis-badge { display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 11px; background: #f1f5f9; color: #64748b; }
.btn-delete { background: #fef2f2; color: #dc2626; border: none; padding: 5px 10px; border-radius: 6px; font-size: 11px; cursor: pointer; }
.btn-delete:hover { background: #fee2e2; }
.empty-state { text-align: center; padding: 48px; color: #94a3b8; }
.empty-state i { font-size: 48px; margin-bottom: 12px; display: block; }
@media (max-width: 900px) { .doc-grid { grid-template-columns: 1fr; } }
</style>

<div class="master-data-container">
    <div class="master-data-header" data-theme="teal">
        <div class="header-content">
            <div class="header-text">
                <h2><i class="fa fa-file-pdf-o"></i> <?= $isThai ? 'เอกสาร' : 'Documents' ?></h2>
                <p><?= $isThai ? 'อัปโหลดและจัดการเอกสารที่แชร์กับตัวแทน' : 'Upload and manage documents shared with agents' ?></p>
            </div>
            <div class="header-actions">
                <a href="index.php?page=tour_agent_list" class="btn-header btn-header-outline">
                    <i class="fa fa-arrow-left"></i> <?= $isThai ? 'กลับ' : 'Back' ?>
                </a>
            </div>
        </div>
    </div>

    <?php if (!empty($message) && isset($messages[$message])): ?>
    <div style="background:<?= strpos($messages[$message][0], '✅') !== false ? '#ecfdf5' : '#fef2f2' ?>; border-left:4px solid <?= strpos($messages[$message][0], '✅') !== false ? '#059669' : '#ef4444' ?>; padding:12px 20px; border-radius:0 8px 8px 0; margin-bottom:16px; font-size:14px;">
        <?= $messages[$message][0] ?> <?= $messages[$message][1] ?>
    </div>
    <?php endif; ?>

    <div class="doc-grid">
        <!-- Upload Form -->
        <div class="doc-form-card">
            <h3><i class="fa fa-cloud-upload"></i> <?= $isThai ? 'อัปโหลดเอกสารใหม่' : 'Upload New Document' ?></h3>
            <form method="post" action="index.php?page=tour_doc_upload" enctype="multipart/form-data">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label><?= $isThai ? 'ชื่อเอกสาร *' : 'Title *' ?></label>
                    <input type="text" name="title" required maxlength="255" placeholder="<?= $isThai ? 'เช่น สัญญาทัวร์ Q1 2026' : 'e.g. Tour Contract Q1 2026' ?>">
                </div>

                <div class="form-group">
                    <label><?= $isThai ? 'รายละเอียด' : 'Description' ?></label>
                    <textarea name="description" placeholder="<?= $isThai ? 'รายละเอียดเอกสาร...' : 'Document description...' ?>"></textarea>
                </div>

                <div class="form-group">
                    <label><?= $isThai ? 'ไฟล์ (สูงสุด 10MB) *' : 'File (max 10MB) *' ?></label>
                    <input type="file" name="document_file" required accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.txt">
                </div>

                <div class="form-group">
                    <label><?= $isThai ? 'หมวดหมู่' : 'Category' ?></label>
                    <select name="category">
                        <?php foreach ($categoryLabels as $key => $info): ?>
                        <option value="<?= $key ?>"><?= $info[0] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label><?= $isThai ? 'ผู้ที่เห็นได้' : 'Visibility' ?></label>
                    <select name="visibility" onchange="toggleContractField(this)" id="visibilitySelect">
                        <option value="all_agents"><?= $visibilityLabels['all_agents'] ?></option>
                        <option value="contract"><?= $visibilityLabels['contract'] ?></option>
                        <option value="operator_only"><?= $visibilityLabels['operator_only'] ?></option>
                    </select>
                </div>

                <div class="form-group" id="contractField" style="display:none;">
                    <label><?= $isThai ? 'สัญญา' : 'Contract' ?></label>
                    <select name="contract_id">
                        <option value=""><?= $isThai ? '— เลือกสัญญา —' : '— Select Contract —' ?></option>
                        <?php foreach ($contracts as $c): ?>
                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['contract_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-upload">
                    <i class="fa fa-cloud-upload"></i> <?= $isThai ? 'อัปโหลด' : 'Upload' ?>
                </button>
            </form>
        </div>

        <!-- Document Table -->
        <div>
            <?php if (empty($documents)): ?>
            <div class="empty-state">
                <i class="fa fa-folder-open-o"></i>
                <p><?= $isThai ? 'ยังไม่มีเอกสารที่อัปโหลด' : 'No documents uploaded yet.' ?></p>
            </div>
            <?php else: ?>
            <div class="doc-table">
                <table>
                    <thead>
                        <tr>
                            <th><?= $isThai ? 'ชื่อ' : 'Title' ?></th>
                            <th><?= $isThai ? 'หมวดหมู่' : 'Category' ?></th>
                            <th><?= $isThai ? 'ผู้เห็น' : 'Visibility' ?></th>
                            <th><?= $isThai ? 'ขนาด' : 'Size' ?></th>
                            <th><?= $isThai ? 'ดาวน์โหลด' : 'Downloads' ?></th>
                            <th><?= $isThai ? 'อัปโหลดเมื่อ' : 'Uploaded' ?></th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($documents as $d):
                            $cat = $categoryLabels[$d['category']] ?? $categoryLabels['other'];
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight:600;color:#1e293b;"><?= htmlspecialchars($d['title']) ?></div>
                                <?php if (!empty($d['description'])): ?>
                                <div style="font-size:11px;color:#94a3b8;margin-top:2px;"><?= htmlspecialchars(mb_strimwidth($d['description'], 0, 80, '…')) ?></div>
                                <?php endif; ?>
                                <div style="font-size:11px;color:#94a3b8;font-family:monospace;margin-top:2px;"><?= htmlspecialchars($d['file_name']) ?></div>
                            </td>
                            <td><span class="cat-badge" style="background:<?= $cat[1] ?>;"><?= $cat[0] ?></span></td>
                            <td>
                                <span class="vis-badge"><?= $visibilityLabels[$d['visibility']] ?? $d['visibility'] ?></span>
                                <?php if ($d['visibility'] === 'contract' && !empty($d['contract_name'])): ?>
                                <div style="font-size:11px;color:#0d9488;margin-top:3px;"><?= htmlspecialchars($d['contract_name']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><?= fmtSize($d['file_size']) ?></td>
                            <td><?= $d['download_count'] ?></td>
                            <td><?= date('d M Y', strtotime($d['created_at'])) ?></td>
                            <td>
                                <form method="post" action="index.php?page=tour_doc_delete" style="display:inline;"
                                      onsubmit="return confirm('<?= $isThai ? 'ลบเอกสารนี้?' : 'Delete this document?' ?>')">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                    <button type="submit" class="btn-delete" title="<?= $isThai ? 'ลบ' : 'Delete' ?>">
                                        <i class="fa fa-trash-o"></i>
                                    </button>
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
</div>

<script>
function toggleContractField(sel) {
    document.getElementById('contractField').style.display = (sel.value === 'contract') ? 'block' : 'none';
}
</script>
