<?php
/**
 * Payment Method Helper Functions
 * Use these functions to get payment methods from database
 */

/**
 * Get all active payment methods
 * @param mysqli $conn Database connection
 * @param bool $active_only Get only active methods (default: true)
 * @return array Array of payment methods
 */
function getPaymentMethods($conn, $active_only = true) {
    $where = $active_only ? "WHERE is_active = 1" : "";
    $result = mysqli_query($conn, "SELECT * FROM payment_method $where ORDER BY sort_order ASC, id ASC");
    
    $methods = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $methods[] = $row;
        }
    }
    return $methods;
}

/**
 * Get payment method labels as associative array
 * @param mysqli $conn Database connection
 * @param string $lang Language ('en' or 'th')
 * @return array Associative array [code => name]
 */
function getPaymentMethodLabels($conn, $lang = 'en') {
    $result = mysqli_query($conn, "SELECT code, name, name_th FROM payment_method WHERE is_active = 1 ORDER BY sort_order ASC");
    
    $labels = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $labels[$row['code']] = ($lang === 'th' && !empty($row['name_th'])) ? $row['name_th'] : $row['name'];
        }
    }
    return $labels;
}

/**
 * Get payment method labels with icons for display
 * @param mysqli $conn Database connection
 * @param string $lang Language ('en' or 'th')
 * @return array Associative array [code => html_with_icon]
 */
function getPaymentMethodLabelsWithIcons($conn, $lang = 'en') {
    $result = mysqli_query($conn, "SELECT code, name, name_th, icon FROM payment_method WHERE is_active = 1 ORDER BY sort_order ASC");
    
    $labels = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $name = ($lang === 'th' && !empty($row['name_th'])) ? $row['name_th'] : $row['name'];
            $icon = $row['icon'] ?: 'fa-money';
            $labels[$row['code']] = '<i class="fa ' . htmlspecialchars($icon) . '"></i> ' . htmlspecialchars($name);
        }
    }
    return $labels;
}

/**
 * Get payment method by code
 * @param mysqli $conn Database connection
 * @param string $code Payment method code
 * @return array|null Payment method data or null if not found
 */
function getPaymentMethodByCode($conn, $code) {
    $code = mysqli_real_escape_string($conn, $code);
    $result = mysqli_query($conn, "SELECT * FROM payment_method WHERE code = '$code' LIMIT 1");
    
    if ($result && mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }
    return null;
}

/**
 * Render payment method dropdown options
 * @param mysqli $conn Database connection
 * @param string $selected Currently selected value
 * @param object $xml Language XML object (optional)
 * @return string HTML options
 */
function renderPaymentMethodOptions($conn, $selected = '', $xml = null) {
    $methods = getPaymentMethods($conn);
    $lang = (isset($_SESSION['lang']) && $_SESSION['lang'] == 1) ? 'th' : 'en';
    
    $html = '';
    foreach ($methods as $method) {
        $name = ($lang === 'th' && !empty($method['name_th'])) ? $method['name_th'] : $method['name'];
        $isSelected = ($selected === $method['code']) ? 'selected' : '';
        $html .= '<option value="' . htmlspecialchars($method['code']) . '" ' . $isSelected . '>' . htmlspecialchars($name) . '</option>' . "\n";
    }
    return $html;
}

/**
 * Get display name for a payment method code
 * @param mysqli $conn Database connection
 * @param string $code Payment method code
 * @param string $lang Language ('en' or 'th')
 * @return string Display name or the code if not found
 */
function getPaymentMethodDisplayName($conn, $code, $lang = 'en') {
    $method = getPaymentMethodByCode($conn, $code);
    if ($method) {
        return ($lang === 'th' && !empty($method['name_th'])) ? $method['name_th'] : $method['name'];
    }
    // Return formatted code as fallback
    return ucwords(str_replace('_', ' ', $code));
}
