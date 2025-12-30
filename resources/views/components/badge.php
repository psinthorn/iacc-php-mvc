<?php
/**
 * Badge/Status Component
 * 
 * Usage:
 * $badge_status = 'pending' (pending|approved|rejected|active|inactive|in_progress|completed)
 * $badge_text = 'Custom text' (optional, defaults to status text)
 */
$badge_status = $badge_status ?? 'gray';
$badge_text = $badge_text ?? ucfirst(str_replace('_', ' ', $badge_status));

$status_colors = [
    'pending' => 'bg-yellow-100 text-yellow-800',
    'approved' => 'bg-green-100 text-green-800',
    'rejected' => 'bg-red-100 text-red-800',
    'active' => 'bg-green-100 text-green-800',
    'inactive' => 'bg-gray-100 text-gray-800',
    'in_progress' => 'bg-blue-100 text-blue-800',
    'completed' => 'bg-green-100 text-green-800',
    'draft' => 'bg-gray-100 text-gray-800',
    'published' => 'bg-green-100 text-green-800',
    'archived' => 'bg-gray-100 text-gray-800',
];

$color_class = $status_colors[$badge_status] ?? 'bg-gray-100 text-gray-800';
?>

<span class="badge <?php echo $color_class; ?>">
    <?php echo htmlspecialchars($badge_text); ?>
</span>
