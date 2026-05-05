<?php
/**
 * Alert row partial — one open or acknowledged alert.
 *
 * Variables in scope (set by index.php foreach):
 *   $a              — alert row from channel_alerts
 *   $channelLabels  — assoc array channel_type → bilingual name
 *   $isThai         — bool
 */
$alertChannel = $channelLabels[$a['channel_type']] ?? $a['channel_type'];
$openedSeconds = time() - strtotime($a['opened_at']);
$openedRel = ch_relative_time($openedSeconds, $isThai);
$isAck = ($a['status'] === 'acknowledged');
?>
<div class="ch-alert-row" style="background:white;border:1px solid #fecaca;border-radius:6px;padding:10px 12px;margin-bottom:8px;<?= $isAck ? 'opacity:0.7;' : '' ?>">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;">
        <div style="flex:1;min-width:200px;">
            <div style="font-weight:600;color:#991b1b;">
                🔴 <?= htmlspecialchars($alertChannel) ?>
                <?php if ($isAck): ?>
                <span style="font-size:11px;font-weight:600;color:#92400e;background:#fef3c7;padding:1px 6px;border-radius:8px;margin-left:6px;">
                    <?= $isThai ? 'รับทราบแล้ว' : 'Acknowledged' ?>
                </span>
                <?php endif; ?>
            </div>
            <div style="font-size:12px;color:#6b7280;margin-top:2px;">
                <?= $isThai ? 'เปิดเมื่อ' : 'Opened' ?> <?= htmlspecialchars($openedRel) ?>
                · <?= htmlspecialchars($a['opened_at']) ?>
            </div>
            <?php if (!empty($a['last_error'])): ?>
            <div style="font-size:11px;color:#991b1b;margin-top:4px;font-style:italic;background:#fef2f2;padding:3px 6px;border-radius:4px;">
                <?= htmlspecialchars(mb_substr((string)$a['last_error'], 0, 200)) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php if (!$isAck): ?>
        <div>
            <button type="button" data-action="acknowledge" data-alert-id="<?= intval($a['id']) ?>"
                    style="font-size:12px;padding:4px 10px;border-radius:4px;border:1px solid #fca5a5;background:white;color:#991b1b;cursor:pointer;">
                <?= $isThai ? 'รับทราบ' : 'Acknowledge' ?>
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>
