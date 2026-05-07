<?php
$pageTitle = 'LINE OA — Agent Bindings';
$lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
$isThai = ($lang === 'th');
$labels = [
    'en' => [
        'page_title'   => 'Agent Bindings',
        'intro'        => 'Bind LINE OA agent accounts to iACC user accounts. Only bound agents can submit bookings via structured LINE messages (e.g. <code>book tour</code>).',
        'agent_users'  => 'Agent LINE Users',
        'display_name' => 'Display Name',
        'line_id'      => 'LINE userId',
        'user_type'    => 'Type',
        'bound_to'     => 'Bound iACC User',
        'select_user'  => 'Select user…',
        'bind'         => 'Bind',
        'unbind'       => 'Unbind',
        'change'       => 'Change',
        'no_users'     => 'No LINE users yet — they will appear here once a user adds your OA as a friend.',
        'no_agents'    => 'No LINE users currently flagged as agents. Open the Users page and change a user\'s type to "agent" first.',
        'how_title'    => 'How to register a LINE user as an agent',
        'step1'        => '1. The user adds your LINE OA as a friend.',
        'step2'        => '2. Open the <a href="index.php?page=line_users">Users</a> page and change their <strong>user_type</strong> to <strong>agent</strong>.',
        'step3'        => '3. Return here and bind their LINE account to an iACC user.',
        'unbound'      => '— not bound —',
    ],
    'th' => [
        'page_title'   => 'ผูกบัญชีตัวแทน',
        'intro'        => 'ผูกบัญชี LINE ของตัวแทนเข้ากับบัญชีผู้ใช้ iACC เฉพาะตัวแทนที่ผูกบัญชีแล้วเท่านั้น จึงจะส่งการจองผ่าน LINE ได้ (เช่นพิมพ์ <code>จองทัวร์</code>)',
        'agent_users'  => 'รายชื่อผู้ใช้ LINE ที่เป็นตัวแทน',
        'display_name' => 'ชื่อที่แสดง',
        'line_id'      => 'LINE userId',
        'user_type'    => 'ประเภท',
        'bound_to'     => 'ผูกกับผู้ใช้ iACC',
        'select_user'  => 'เลือกผู้ใช้…',
        'bind'         => 'ผูก',
        'unbind'       => 'ยกเลิก',
        'change'       => 'เปลี่ยน',
        'no_users'     => 'ยังไม่มีผู้ใช้ LINE — จะแสดงเมื่อผู้ใช้เพิ่ม OA เป็นเพื่อน',
        'no_agents'    => 'ยังไม่มีผู้ใช้ LINE ที่ระบุเป็น "agent" กรุณาเปิดหน้า Users และเปลี่ยน user_type เป็น "agent" ก่อน',
        'how_title'    => 'วิธีลงทะเบียนผู้ใช้ LINE เป็นตัวแทน',
        'step1'        => '1. ให้ผู้ใช้เพิ่ม LINE OA ของคุณเป็นเพื่อน',
        'step2'        => '2. ไปที่หน้า <a href="index.php?page=line_users">Users</a> แล้วเปลี่ยน <strong>user_type</strong> เป็น <strong>agent</strong>',
        'step3'        => '3. กลับมาที่หน้านี้เพื่อผูกบัญชีกับผู้ใช้ iACC',
        'unbound'      => '— ยังไม่ผูก —',
    ],
];
$t = $labels[$lang];

$agents = array_values(array_filter($bindings ?? [], fn($r) => ($r['user_type'] ?? '') === 'agent'));
?>
<?php $currentNavPage = 'line_agent_bindings'; include __DIR__ . '/_nav.php'; ?>

<?php if (!empty($_SESSION['flash_success'])): ?>
<div class="alert alert-success alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_success'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_success']); endif; ?>
<?php if (!empty($_SESSION['flash_error'])): ?>
<div class="alert alert-danger alert-dismissible"><button type="button" class="close" data-dismiss="alert">&times;</button><?= htmlspecialchars($_SESSION['flash_error'], ENT_QUOTES, 'UTF-8') ?></div>
<?php unset($_SESSION['flash_error']); endif; ?>

<div style="display:flex; gap:20px; flex-wrap:wrap;">

<div style="flex:2; min-width:380px;">
    <div style="background:white; border-radius:12px; padding:20px; box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <h4 style="margin-top:0;"><i class="fa fa-link"></i> <?= $t['agent_users'] ?></h4>
        <p style="color:#666;"><?= $t['intro'] ?></p>

        <?php if (empty($agents)): ?>
            <p style="text-align:center; padding:30px 20px; color:#999;"><?= $t['no_agents'] ?></p>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover" style="margin:0;">
                <thead>
                    <tr>
                        <th><?= $t['display_name'] ?></th>
                        <th><?= $t['line_id'] ?></th>
                        <th><?= $t['bound_to'] ?></th>
                        <th style="text-align:right;"><?= $t['bind'] ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($agents as $a): ?>
                    <?php $isBound = !empty($a['linked_user_id']); ?>
                    <tr>
                        <td>
                            <?php if (!empty($a['picture_url'])): ?>
                                <img src="<?= htmlspecialchars($a['picture_url'], ENT_QUOTES, 'UTF-8') ?>" alt="" style="width:24px; height:24px; border-radius:50%; vertical-align:middle; margin-right:6px;">
                            <?php endif; ?>
                            <strong><?= htmlspecialchars($a['display_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></strong>
                        </td>
                        <td><code style="font-size:0.75rem;"><?= htmlspecialchars(substr($a['line_user_id'] ?? '', 0, 12), ENT_QUOTES, 'UTF-8') ?>…</code></td>
                        <td>
                            <?php if ($isBound): ?>
                                <span class="label label-success">
                                    <i class="fa fa-check"></i>
                                    <?= htmlspecialchars($a['linked_name'] ?: $a['linked_email'] ?: ('#' . $a['linked_user_id']), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <?php if (!empty($a['linked_at'])): ?>
                                    <small class="text-muted" style="display:block;"><?= date('M d, Y', strtotime($a['linked_at'])) ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted"><?= $t['unbound'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:right;">
                            <form method="POST" action="index.php?page=line_agent_bind_save" style="display:inline-flex; gap:5px; align-items:center;">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <input type="hidden" name="line_user_id" value="<?= (int)$a['id'] ?>">
                                <select name="iacc_user_id" class="form-control input-sm" style="max-width:180px;">
                                    <option value=""><?= $t['select_user'] ?></option>
                                    <?php foreach (($iaccUsers ?? []) as $u): ?>
                                        <option value="<?= (int)$u['id'] ?>" <?= ($a['linked_user_id'] ?? 0) == $u['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(($u['name'] ?: $u['email']) . ' (lvl ' . (int)$u['level'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" name="action" value="bind" class="btn btn-sm btn-primary"><i class="fa fa-link"></i></button>
                                <?php if ($isBound): ?>
                                    <button type="submit" name="action" value="unbind" class="btn btn-sm btn-outline-danger" onclick="return confirm('Unbind this agent?');"><i class="fa fa-unlink"></i></button>
                                <?php endif; ?>
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

<div style="flex:1; min-width:280px;">
    <div style="background:#f9f9f9; border-radius:12px; padding:20px; border-left:4px solid #06C755;">
        <h5 style="margin-top:0;"><i class="fa fa-question-circle"></i> <?= $t['how_title'] ?></h5>
        <ol style="padding-left:0; list-style:none; line-height:2;">
            <li><?= $t['step1'] ?></li>
            <li><?= $t['step2'] ?></li>
            <li><?= $t['step3'] ?></li>
        </ol>
    </div>
</div>

</div>
</div>
