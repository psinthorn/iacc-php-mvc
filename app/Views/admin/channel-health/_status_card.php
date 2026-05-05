<?php
/**
 * Status card partial — one channel.
 *
 * Variables in scope (set by index.php loop):
 *   $channel    — channel_type key (e.g. 'line_oa')
 *   $card       — ['state','response_ms','ago_seconds','error','last_checked_at']
 *   $cardName   — bilingual display name (e.g. 'LINE OA' / 'ไลน์ OA')
 *   $cardState  — state key ('healthy','degraded','down','not_configured','stale')
 *   $cardLabel  — bilingual state label
 *   $cardClass  — same as $cardState (used for CSS class)
 *   $cardIcon   — emoji for the state
 *   $isThai     — bool
 */
$canTest = in_array($cardState, ['healthy','degraded','down','stale'], true);
?>
<div class="ch-card">
    <div class="ch-c-title"><?= $cardIcon ?> <?= htmlspecialchars($cardName) ?></div>
    <span class="ch-c-state <?= htmlspecialchars($cardClass) ?>"><?= htmlspecialchars($cardLabel) ?></span>

    <?php if ($card['response_ms'] !== null): ?>
        <div class="ch-c-meta"><?= intval($card['response_ms']) ?> ms · <?= htmlspecialchars(ch_relative_time($card['ago_seconds'], $isThai)) ?></div>
    <?php elseif ($card['ago_seconds'] !== null): ?>
        <div class="ch-c-meta"><?= htmlspecialchars(ch_relative_time($card['ago_seconds'], $isThai)) ?></div>
    <?php else: ?>
        <div class="ch-c-meta">—</div>
    <?php endif; ?>

    <?php if (!empty($card['error'])): ?>
    <div class="ch-c-error" title="<?= htmlspecialchars($card['error']) ?>">
        <?= htmlspecialchars(mb_substr((string)$card['error'], 0, 80)) ?>
    </div>
    <?php endif; ?>

    <div class="ch-c-actions">
        <?php if ($canTest): ?>
        <button type="button" data-action="test-now" data-channel="<?= htmlspecialchars($channel) ?>">
            <?= $isThai ? 'ทดสอบ' : 'Test now' ?>
        </button>
        <?php else: ?>
        <button type="button" disabled title="<?= $isThai ? 'ตั้งค่าช่องทางก่อนจึงจะทดสอบได้' : 'Configure channel before testing' ?>">
            <?= $isThai ? 'ทดสอบ' : 'Test now' ?>
        </button>
        <?php endif; ?>
    </div>
</div>
