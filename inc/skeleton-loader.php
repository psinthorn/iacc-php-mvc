<?php
/**
 * Skeleton Loading Component
 * 
 * Provides reusable skeleton loading animations for pages
 * Usage: Include this file and call the skeleton functions
 * 
 * Example:
 *   include_once __DIR__ . '/inc/skeleton-loader.php';
 *   echo get_skeleton_styles();
 *   echo skeleton_card(); // or other skeleton components
 */

/**
 * Get the CSS styles for skeleton loading
 * Now returns empty since CSS is loaded globally via css/skeleton-loader.css
 * Kept for backward compatibility
 * 
 * @deprecated Use <link href="css/skeleton-loader.css"> instead (already included in css.php)
 */
function get_skeleton_styles() {
    // CSS is now loaded globally via css.php -> css/skeleton-loader.css
    // Return empty string for backward compatibility
    return '';
}

/**
 * Generate JavaScript for removing skeleton loading
 * Include this at the end of your page's script section
 * Works for both standalone pages and included files
 */
function get_skeleton_js($container_id = 'pageContainer', $delay = 300) {
    return <<<JS
// Remove skeleton loading after content is ready
// Uses IIFE for included files (DOMContentLoaded may have already fired)
(function() {
    function removeSkeleton() {
        setTimeout(function() {
            var container = document.getElementById('{$container_id}');
            if (container) {
                container.classList.remove('skeleton-loading');
            }
        }, {$delay});
    }
    
    // If DOM is already loaded, run immediately; otherwise wait for it
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', removeSkeleton);
    } else {
        removeSkeleton();
    }
})();
JS;
}

/**
 * Helper function to wrap content with skeleton loading container
 */
function skeleton_wrapper_start($id = 'pageContainer', $class = '') {
    return '<div class="' . htmlspecialchars($class) . ' skeleton-loading" id="' . htmlspecialchars($id) . '">';
}

function skeleton_wrapper_end() {
    return '</div>';
}

function skeleton_content_start() {
    return '<div class="content-container">';
}

function skeleton_content_end() {
    return '</div><!-- End content-container -->';
}

function skeleton_placeholder_start() {
    return '<div class="skeleton-container">';
}

function skeleton_placeholder_end() {
    return '</div><!-- End skeleton-container -->';
}

/**
 * Generate a skeleton stat card
 */
function skeleton_stat_card() {
    return <<<HTML
<div class="skeleton-stat-card">
    <div class="skeleton skeleton-icon" style="margin-bottom: 12px;"></div>
    <div class="skeleton skeleton-stat-value"></div>
    <div class="skeleton skeleton-stat-label"></div>
</div>
HTML;
}

/**
 * Generate multiple skeleton stat cards
 */
function skeleton_stat_cards($count = 4) {
    $html = '<div class="skeleton-grid-4">';
    for ($i = 0; $i < $count; $i++) {
        $html .= skeleton_stat_card();
    }
    $html .= '</div>';
    return $html;
}

/**
 * Generate a skeleton table
 */
function skeleton_table($rows = 5, $cols = 4) {
    $html = '<div class="skeleton-table">';
    
    // Header
    $html .= '<div class="skeleton-table-header">';
    for ($c = 0; $c < $cols; $c++) {
        $width = rand(60, 100);
        $html .= '<div class="skeleton-table-cell"><div class="skeleton" style="height: 14px; width: ' . $width . '%;"></div></div>';
    }
    $html .= '</div>';
    
    // Rows
    for ($r = 0; $r < $rows; $r++) {
        $html .= '<div class="skeleton-table-row">';
        for ($c = 0; $c < $cols; $c++) {
            $width = rand(50, 90);
            $html .= '<div class="skeleton-table-cell"><div class="skeleton" style="height: 14px; width: ' . $width . '%;"></div></div>';
        }
        $html .= '</div>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Generate a skeleton card
 */
function skeleton_card($with_footer = false) {
    $html = '<div class="skeleton-card">';
    $html .= '<div class="skeleton-card-header">';
    $html .= '<div class="skeleton skeleton-avatar"></div>';
    $html .= '<div style="flex: 1;">';
    $html .= '<div class="skeleton skeleton-text md"></div>';
    $html .= '<div class="skeleton skeleton-text sm"></div>';
    $html .= '</div>';
    $html .= '</div>';
    
    $html .= '<div class="skeleton-card-body">';
    $html .= '<div class="skeleton skeleton-text lg"></div>';
    $html .= '<div class="skeleton skeleton-text lg"></div>';
    $html .= '<div class="skeleton skeleton-text md"></div>';
    $html .= '</div>';
    
    if ($with_footer) {
        $html .= '<div style="display: flex; gap: 10px; margin-top: 16px;">';
        $html .= '<div class="skeleton skeleton-btn" style="flex: 1;"></div>';
        $html .= '<div class="skeleton skeleton-btn" style="flex: 1;"></div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Generate a skeleton form
 */
function skeleton_form($fields = 4) {
    $html = '<div class="skeleton-card">';
    
    for ($i = 0; $i < $fields; $i++) {
        $html .= '<div class="skeleton-form-group">';
        $html .= '<div class="skeleton skeleton-label"></div>';
        $html .= '<div class="skeleton skeleton-input"></div>';
        $html .= '</div>';
    }
    
    $html .= '<div style="display: flex; gap: 10px; margin-top: 20px;">';
    $html .= '<div class="skeleton skeleton-btn" style="width: 120px;"></div>';
    $html .= '<div class="skeleton skeleton-btn" style="width: 80px;"></div>';
    $html .= '</div>';
    
    $html .= '</div>';
    return $html;
}

/**
 * Generate a skeleton list
 */
function skeleton_list($items = 5) {
    $html = '<div class="skeleton-card">';
    
    for ($i = 0; $i < $items; $i++) {
        $html .= '<div class="skeleton-list-item">';
        $html .= '<div class="skeleton skeleton-avatar"></div>';
        $html .= '<div style="flex: 1;">';
        $html .= '<div class="skeleton skeleton-text md"></div>';
        $html .= '<div class="skeleton skeleton-text sm"></div>';
        $html .= '</div>';
        $html .= '<div class="skeleton skeleton-badge"></div>';
        $html .= '</div>';
    }
    
    $html .= '</div>';
    return $html;
}

/**
 * Generate page header skeleton
 */
function skeleton_page_header() {
    return <<<HTML
<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
    <div>
        <div class="skeleton skeleton-text title"></div>
        <div class="skeleton skeleton-text sm" style="width: 200px;"></div>
    </div>
    <div style="display: flex; gap: 10px;">
        <div class="skeleton skeleton-btn" style="width: 100px;"></div>
        <div class="skeleton skeleton-btn" style="width: 100px;"></div>
    </div>
</div>
HTML;
}
