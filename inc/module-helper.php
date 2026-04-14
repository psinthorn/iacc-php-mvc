<?php
/**
 * Module Helper — Feature gating via company_modules table
 * 
 * Usage: isModuleEnabled($_SESSION['com_id'], 'tour_operator')
 * Caches results in $_SESSION['modules'] to avoid repeated queries.
 */

function isModuleEnabled(int $companyId, string $moduleKey): bool
{
    if ($companyId <= 0) return false;

    // Check session cache first
    if (isset($_SESSION['modules'][$companyId][$moduleKey])) {
        return $_SESSION['modules'][$companyId][$moduleKey];
    }

    // Query database
    global $db;
    $conn = $db->conn ?? null;
    if (!$conn) return false;

    $stmt = $conn->prepare(
        "SELECT is_enabled FROM company_modules 
         WHERE company_id = ? AND module_key = ? 
         AND (valid_to IS NULL OR valid_to >= CURDATE())
         LIMIT 1"
    );
    if (!$stmt) return false;

    $stmt->bind_param('is', $companyId, $moduleKey);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    $enabled = $row ? (bool) $row['is_enabled'] : false;

    // Cache in session
    if (!isset($_SESSION['modules'])) {
        $_SESSION['modules'] = [];
    }
    if (!isset($_SESSION['modules'][$companyId])) {
        $_SESSION['modules'][$companyId] = [];
    }
    $_SESSION['modules'][$companyId][$moduleKey] = $enabled;

    return $enabled;
}

/**
 * Clear module cache (call after enabling/disabling a module)
 */
function clearModuleCache(int $companyId = 0): void
{
    if ($companyId > 0) {
        unset($_SESSION['modules'][$companyId]);
    } else {
        unset($_SESSION['modules']);
    }
}
