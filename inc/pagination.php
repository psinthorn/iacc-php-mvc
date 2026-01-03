<?php
/**
 * Pagination Helper Functions
 * Mobile-first responsive pagination component
 * 
 * @version 1.0
 */

/**
 * Calculate pagination parameters
 * 
 * @param int $total_records Total number of records
 * @param int $per_page Records per page (default 20)
 * @param int $current_page Current page number
 * @return array Pagination data
 */
function paginate($total_records, $per_page = 20, $current_page = 1) {
    $total_pages = ceil($total_records / $per_page);
    $current_page = max(1, min($current_page, $total_pages));
    $offset = ($current_page - 1) * $per_page;
    
    return [
        'total_records' => $total_records,
        'per_page' => $per_page,
        'current_page' => $current_page,
        'total_pages' => $total_pages,
        'offset' => $offset,
        'has_prev' => $current_page > 1,
        'has_next' => $current_page < $total_pages,
        'start_record' => $offset + 1,
        'end_record' => min($offset + $per_page, $total_records)
    ];
}

/**
 * Render pagination HTML (mobile-first responsive)
 * 
 * @param array $pagination Pagination data from paginate()
 * @param string $base_url Base URL for pagination links
 * @param array $params Additional query parameters to preserve
 * @return string HTML for pagination
 */
function render_pagination($pagination, $base_url, $params = []) {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    // Build query string preserving existing params
    $query_params = $params;
    unset($query_params['pg']); // Remove page param, we'll add it fresh
    
    $build_url = function($page) use ($base_url, $query_params) {
        $query_params['pg'] = $page;
        return $base_url . '&' . http_build_query($query_params);
    };
    
    $current = $pagination['current_page'];
    $total = $pagination['total_pages'];
    
    // Calculate visible page range (show 5 pages on desktop, 3 on mobile)
    $range = 2;
    $start = max(1, $current - $range);
    $end = min($total, $current + $range);
    
    // Adjust if at edges
    if ($current <= $range) {
        $end = min($total, 5);
    }
    if ($current >= $total - $range) {
        $start = max(1, $total - 4);
    }
    
    $html = '<nav aria-label="Page navigation" class="pagination-wrapper">';
    $html .= '<ul class="pagination pagination-responsive">';
    
    // Previous button
    if ($pagination['has_prev']) {
        $html .= '<li><a href="' . $build_url($current - 1) . '" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li>';
    } else {
        $html .= '<li class="disabled"><span aria-hidden="true">&laquo;</span></li>';
    }
    
    // First page + ellipsis
    if ($start > 1) {
        $html .= '<li><a href="' . $build_url(1) . '">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="disabled hidden-xs"><span>...</span></li>';
        }
    }
    
    // Page numbers
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current) {
            $html .= '<li class="active"><span>' . $i . '</span></li>';
        } else {
            // Hide middle pages on mobile
            $hide_class = ($i != $start && $i != $end && $i != $current) ? 'hidden-xs' : '';
            $html .= '<li class="' . $hide_class . '"><a href="' . $build_url($i) . '">' . $i . '</a></li>';
        }
    }
    
    // Last page + ellipsis
    if ($end < $total) {
        if ($end < $total - 1) {
            $html .= '<li class="disabled hidden-xs"><span>...</span></li>';
        }
        $html .= '<li><a href="' . $build_url($total) . '">' . $total . '</a></li>';
    }
    
    // Next button
    if ($pagination['has_next']) {
        $html .= '<li><a href="' . $build_url($current + 1) . '" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>';
    } else {
        $html .= '<li class="disabled"><span aria-hidden="true">&raquo;</span></li>';
    }
    
    $html .= '</ul>';
    
    // Record count info
    $html .= '<div class="pagination-info text-muted">';
    $html .= 'Showing ' . $pagination['start_record'] . '-' . $pagination['end_record'];
    $html .= ' of ' . $pagination['total_records'] . ' records';
    $html .= '</div>';
    
    $html .= '</nav>';
    
    return $html;
}

/**
 * Get default date range based on preset
 * 
 * @param string $preset Preset name: 'mtd', 'ytd', 'last30', 'last90', 'all'
 * @return array ['from' => date, 'to' => date]
 */
function get_date_range($preset = 'mtd') {
    $today = date('Y-m-d');
    
    switch ($preset) {
        case 'today':
            return ['from' => $today, 'to' => $today];
            
        case 'last7':
            return ['from' => date('Y-m-d', strtotime('-7 days')), 'to' => $today];
            
        case 'last30':
            return ['from' => date('Y-m-d', strtotime('-30 days')), 'to' => $today];
            
        case 'mtd': // Month to date
            return ['from' => date('Y-m-01'), 'to' => $today];
            
        case 'last_month':
            return [
                'from' => date('Y-m-01', strtotime('first day of last month')),
                'to' => date('Y-m-t', strtotime('last day of last month'))
            ];
            
        case 'ytd': // Year to date
            return ['from' => date('Y-01-01'), 'to' => $today];
            
        case 'last90':
            return ['from' => date('Y-m-d', strtotime('-90 days')), 'to' => $today];
            
        case 'last_year':
            return [
                'from' => date('Y-01-01', strtotime('-1 year')),
                'to' => date('Y-12-31', strtotime('-1 year'))
            ];
            
        case 'all':
        default:
            return ['from' => '', 'to' => ''];
    }
}

/**
 * Render date preset buttons (mobile-friendly)
 * 
 * @param string $current_preset Currently selected preset
 * @param string $page Page name for form
 * @return string HTML for preset buttons
 */
function render_date_presets($current_preset = '', $page = '') {
    $presets = [
        'mtd' => 'MTD',
        'ytd' => 'YTD', 
        'last30' => '30 Days',
        'last_month' => 'Last Month',
        'all' => 'All'
    ];
    
    $html = '<div class="btn-group btn-group-sm date-presets" role="group">';
    
    foreach ($presets as $key => $label) {
        $active = ($current_preset === $key) ? 'active' : '';
        $html .= '<button type="submit" name="date_preset" value="' . $key . '" ';
        $html .= 'class="btn btn-default ' . $active . '">' . $label . '</button>';
    }
    
    $html .= '</div>';
    
    return $html;
}

/**
 * Per page selector dropdown
 * 
 * @param int $current Current per page value
 * @return string HTML for per page selector
 */
function render_per_page_selector($current = 20) {
    $options = [10, 20, 50, 100];
    
    $html = '<div class="form-group per-page-selector">';
    $html .= '<label class="hidden-xs">Show:</label> ';
    $html .= '<select name="per_page" class="form-control input-sm" onchange="this.form.submit()">';
    
    foreach ($options as $opt) {
        $selected = ($current == $opt) ? 'selected' : '';
        $html .= '<option value="' . $opt . '" ' . $selected . '>' . $opt . '</option>';
    }
    
    $html .= '</select>';
    $html .= '<span class="hidden-xs"> per page</span>';
    $html .= '</div>';
    
    return $html;
}
