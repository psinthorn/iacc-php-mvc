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
 * Include this once in your page's <style> section
 */
function get_skeleton_styles() {
    return <<<'CSS'
/* Skeleton Loading Core Styles */
.skeleton-container { display: none; }
.skeleton-loading .skeleton-container { display: block; }
.skeleton-loading .content-container { display: none; }

@keyframes skeleton-wave {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

.skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: skeleton-wave 1.5s ease-in-out infinite;
    border-radius: 4px;
}

.skeleton-dark {
    background: linear-gradient(90deg, #2a2a2a 25%, #3a3a3a 50%, #2a2a2a 75%);
    background-size: 200% 100%;
    animation: skeleton-wave 1.5s ease-in-out infinite;
    border-radius: 4px;
}

/* Text Skeletons */
.skeleton-text { height: 14px; margin-bottom: 8px; }
.skeleton-text.xs { width: 40%; }
.skeleton-text.sm { width: 60%; }
.skeleton-text.md { width: 80%; }
.skeleton-text.lg { width: 100%; }
.skeleton-text.title { height: 28px; width: 250px; margin-bottom: 12px; }
.skeleton-text.subtitle { height: 20px; width: 180px; margin-bottom: 10px; }

/* Common Components */
.skeleton-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
}

.skeleton-avatar.lg {
    width: 60px;
    height: 60px;
}

.skeleton-btn {
    height: 38px;
    border-radius: 8px;
    min-width: 80px;
}

.skeleton-badge {
    height: 24px;
    width: 70px;
    border-radius: 20px;
    display: inline-block;
}

.skeleton-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
}

.skeleton-icon.sm {
    width: 32px;
    height: 32px;
    border-radius: 8px;
}

.skeleton-icon.lg {
    width: 56px;
    height: 56px;
    border-radius: 14px;
}

/* Card Skeleton */
.skeleton-card {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
    padding: 20px;
    margin-bottom: 16px;
}

.skeleton-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 16px;
}

.skeleton-card-body {
    padding: 0;
}

/* Table Skeleton */
.skeleton-table {
    width: 100%;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
    overflow: hidden;
}

.skeleton-table-header {
    background: #f9fafb;
    padding: 16px 20px;
    border-bottom: 1px solid #e5e7eb;
    display: flex;
    gap: 20px;
}

.skeleton-table-row {
    padding: 16px 20px;
    border-bottom: 1px solid #f3f4f6;
    display: flex;
    gap: 20px;
    align-items: center;
}

.skeleton-table-row:last-child {
    border-bottom: none;
}

.skeleton-table-cell {
    flex: 1;
}

/* Stats Card Skeleton */
.skeleton-stat-card {
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border: 1px solid #e5e7eb;
}

.skeleton-stat-value {
    height: 32px;
    width: 60px;
    margin-bottom: 8px;
}

.skeleton-stat-label {
    height: 12px;
    width: 100px;
}

/* Grid Layouts */
.skeleton-grid-2 { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
.skeleton-grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
.skeleton-grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }

@media (max-width: 768px) {
    .skeleton-grid-2, .skeleton-grid-3, .skeleton-grid-4 {
        grid-template-columns: 1fr;
    }
}

/* Form Skeleton */
.skeleton-form-group {
    margin-bottom: 20px;
}

.skeleton-label {
    height: 14px;
    width: 120px;
    margin-bottom: 8px;
}

.skeleton-input {
    height: 42px;
    width: 100%;
    border-radius: 8px;
}

.skeleton-textarea {
    height: 120px;
    width: 100%;
    border-radius: 8px;
}

/* Chart/Graph Skeleton */
.skeleton-chart {
    height: 200px;
    width: 100%;
    border-radius: 12px;
    margin-bottom: 16px;
}

.skeleton-chart.lg {
    height: 300px;
}

/* List Skeleton */
.skeleton-list-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 0;
    border-bottom: 1px solid #f3f4f6;
}

.skeleton-list-item:last-child {
    border-bottom: none;
}
CSS;
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
