<?php
/**
 * Card Component
 * 
 * Usage:
 * $card_title = 'Card Title'
 * $card_content = 'Card content goes here'
 * $card_footer = 'Optional footer'
 * $card_class = 'Additional classes'
 */
$card_title = $card_title ?? null;
$card_content = $card_content ?? '';
$card_footer = $card_footer ?? null;
$card_class = $card_class ?? '';
?>

<div class="card <?php echo $card_class; ?>">
    <?php if ($card_title): ?>
        <div class="card-header">
            <h3 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($card_title); ?></h3>
        </div>
    <?php endif; ?>
    
    <div class="<?php echo $card_title ? '' : 'mb-4'; ?>">
        <?php echo $card_content; ?>
    </div>
    
    <?php if ($card_footer): ?>
        <div class="card-footer">
            <?php echo $card_footer; ?>
        </div>
    <?php endif; ?>
</div>
