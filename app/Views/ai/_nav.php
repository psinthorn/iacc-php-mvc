<?php
/**
 * Shared AI Admin Navigation Bar
 * Include on all AI admin pages for consistent cross-navigation.
 * Variables available: $currentPage (string) — the active page identifier
 */
$currentPage = $currentPage ?? '';
$aiPages = [
    ['page' => 'ai_settings',       'icon' => 'fa-cogs',        'label' => 'Settings'],
    ['page' => 'ai_chat_history',   'icon' => 'fa-comments',    'label' => 'Chat History'],
    ['page' => 'ai_action_log',     'icon' => 'fa-list-alt',    'label' => 'Action Log'],
    ['page' => 'ai_schema_browser', 'icon' => 'fa-database',    'label' => 'Schema Browser'],
    ['page' => 'ai_schema_refresh', 'icon' => 'fa-refresh',     'label' => 'Schema Refresh'],
    ['page' => 'ai_documentation',  'icon' => 'fa-book',        'label' => 'Documentation'],
];
?>
<div class="ai-nav-bar" style="margin-bottom: 20px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 8px; display: flex; flex-wrap: wrap; gap: 4px;">
    <?php foreach ($aiPages as $p): ?>
    <a href="index.php?page=<?= $p['page'] ?>" 
       class="btn btn-sm <?= $currentPage === $p['page'] ? 'btn-primary' : 'btn-default' ?>"
       style="border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
        <i class="fa <?= $p['icon'] ?>"></i> <?= $p['label'] ?>
    </a>
    <?php endforeach; ?>
</div>
