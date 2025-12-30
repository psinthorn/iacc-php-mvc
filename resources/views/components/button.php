<?php
/**
 * Button Component
 * 
 * Usage: include 'button.php', then pass variables:
 * $button_text = 'Click me'
 * $button_type = 'primary' (primary|secondary|danger|success|warning)
 * $button_size = 'md' (sm|md|lg)
 * $button_disabled = false
 * $button_icon = 'fa-plus' (optional Font Awesome icon)
 * $button_onclick = 'someFunction()' (optional)
 */
$button_text = $button_text ?? 'Button';
$button_type = $button_type ?? 'primary';
$button_size = $button_size ?? 'md';
$button_disabled = $button_disabled ?? false;
$button_icon = $button_icon ?? null;
$button_onclick = $button_onclick ?? '';
$button_class = $button_class ?? '';
$button_name = $button_name ?? null;
$button_value = $button_value ?? null;

$size_classes = [
    'sm' => 'btn-sm',
    'md' => 'btn',
    'lg' => 'btn-lg'
];

$type_class = 'btn-' . $button_type;
$size_class = $size_classes[$button_size] ?? 'btn';
$disabled_class = $button_disabled ? 'disabled opacity-50 cursor-not-allowed' : '';
?>

<button type="button" 
        class="<?php echo $type_class; ?> <?php echo $size_class; ?> <?php echo $disabled_class; ?> <?php echo $button_class; ?>"
        <?php echo $button_disabled ? 'disabled' : ''; ?>
        <?php echo $button_onclick ? 'onclick="' . $button_onclick . '"' : ''; ?>>
    <?php if ($button_icon): ?>
        <i class="fas <?php echo $button_icon; ?>"></i>
    <?php endif; ?>
    <?php echo htmlspecialchars($button_text); ?>
</button>
