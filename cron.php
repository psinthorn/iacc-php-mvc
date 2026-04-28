<?php
/**
 * cron.php — Scheduled task runner for cPanel cron jobs
 *
 * On cPanel, schedule via the cron control panel:
 *   curl -s "https://yourdomain.com/cron.php?task=daily_reports&token=YOUR_SECRET"
 *
 * Or hit it from a local cron with:
 *   wget -qO- "https://yourdomain.com/cron.php?task=weekly_reports&token=YOUR_SECRET"
 *
 * Tasks:
 *   daily_reports        — send daily contract digest to each operator's admin
 *   weekly_reports       — send weekly digest (run on Mondays)
 *   monthly_reports      — send monthly digest (run on the 1st)
 *   sync_all_contracts   — one-time/periodic full sync of all V2 operator contracts
 *                          to all assigned agents (rebuilds tour_operator_agent_products)
 *
 * Auth: ?token=... must match config['cron_token'] (set via env or config file)
 *
 * Output: text/plain log of what was processed.
 */

// Block direct browser access without token; the auth check happens below
header('Content-Type: text/plain; charset=utf-8');
header('X-Robots-Tag: noindex');

ini_set('display_errors', 0);
ini_set('log_errors', 1);
set_time_limit(300);  // 5 minutes for big batches

// ── Load minimal core ──
require_once __DIR__ . '/inc/sys.configs.php';
require_once __DIR__ . '/inc/class.dbconn.php';
require_once __DIR__ . '/inc/security.php';

$db = new DbConn($config);

// ── Auth: shared token ──
$expectedToken = $config['cron_token'] ?? getenv('CRON_TOKEN') ?: '';
$providedToken = $_GET['token'] ?? '';

if (empty($expectedToken)) {
    http_response_code(503);
    echo "ERROR: cron_token is not configured. Set CRON_TOKEN env var or config['cron_token'].\n";
    exit;
}
if (!hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    echo "Forbidden\n";
    exit;
}

$task = $_GET['task'] ?? '';

// ── Load deps for reporting & sync ──
require_once __DIR__ . '/inc/class.hard.php';
require_once __DIR__ . '/app/Models/BaseModel.php';
require_once __DIR__ . '/app/Models/ContractReport.php';
require_once __DIR__ . '/app/Models/AgentContract.php';
require_once __DIR__ . '/app/Models/ContractSync.php';
require_once __DIR__ . '/app/Services/EmailService.php';
require_once __DIR__ . '/app/Services/ContractSyncService.php';
require_once __DIR__ . '/app/Controllers/BaseController.php';
require_once __DIR__ . '/app/Controllers/ContractReportController.php';

echo "[" . date('Y-m-d H:i:s') . "] Starting cron task: $task\n\n";

switch ($task) {
    case 'daily_reports':
        runReports('daily');
        break;
    case 'weekly_reports':
        runReports('weekly');
        break;
    case 'monthly_reports':
        runReports('monthly');
        break;
    case 'sync_all_contracts':
        runSyncAllContracts();
        break;
    default:
        http_response_code(400);
        echo "Unknown task. Available: daily_reports, weekly_reports, monthly_reports, sync_all_contracts\n";
        exit;
}

echo "\n[" . date('Y-m-d H:i:s') . "] Done.\n";

// ─────────────────────────────────────────────────────────────

function runReports(string $period): void
{
    global $db;

    // Get all operators with tour_operator module enabled
    $sql = "SELECT DISTINCT cm.company_id, c.name_en
            FROM company_modules cm
            INNER JOIN company c ON cm.company_id = c.id
            WHERE cm.module = 'tour_operator' AND cm.is_enabled = 1";
    $res = mysqli_query($db->conn, $sql);
    if (!$res || mysqli_num_rows($res) === 0) {
        echo "No operators with tour_operator module enabled.\n";
        return;
    }

    $sent = 0;
    $failed = 0;
    $skipped = 0;

    // Reuse the controller method (need to bootstrap session for getCompanyId checks)
    // We bypass controller internals and call sendDigest directly
    $controller = new \App\Controllers\ContractReportController();

    while ($row = mysqli_fetch_assoc($res)) {
        $comId = intval($row['company_id']);
        $name = $row['name_en'];

        // Find admin email
        $emailSql = "SELECT email FROM user WHERE company_id = $comId AND user_level >= 1 AND email != '' ORDER BY user_level DESC, usr_id ASC LIMIT 1";
        $emailRes = mysqli_query($db->conn, $emailSql);
        $emailRow = $emailRes ? mysqli_fetch_assoc($emailRes) : null;
        $email = $emailRow['email'] ?? null;

        if (!$email) {
            echo "  - SKIP $name (#$comId): no admin email\n";
            $skipped++;
            continue;
        }

        $ok = $controller->sendDigest($comId, $email, $period);
        if ($ok) {
            echo "  - SENT $name (#$comId) → $email\n";
            $sent++;
        } else {
            echo "  - FAIL $name (#$comId) → $email\n";
            $failed++;
        }
    }

    echo "\nSummary: $sent sent, $failed failed, $skipped skipped.\n";
}

function runSyncAllContracts(): void
{
    global $db;

    // Find all V2 operator-level contracts and group by company
    $sql = "SELECT id, company_id, contract_name FROM agent_contracts
            WHERE is_operator_level = 1 AND deleted_at IS NULL
            ORDER BY company_id, id";
    $res = mysqli_query($db->conn, $sql);
    if (!$res || mysqli_num_rows($res) === 0) {
        echo "No V2 operator-level contracts found.\n";
        return;
    }

    $service = new \App\Services\ContractSyncService();
    $synced = 0;
    $skipped = 0;
    $totalAdded = 0;
    $totalRemoved = 0;

    while ($row = mysqli_fetch_assoc($res)) {
        $cid = intval($row['id']);
        $comId = intval($row['company_id']);
        $name = $row['contract_name'];

        $result = $service->syncContractToAgents($cid, $comId, 'cron');
        if ($result['success']) {
            $agents = $result['data']['agents'] ?? 0;
            $added = $result['data']['added'] ?? 0;
            $removed = $result['data']['removed'] ?? 0;
            if ($agents === 0) {
                echo "  - SKIP $name (#$cid): no agents assigned\n";
                $skipped++;
            } else {
                echo "  - SYNC $name (#$cid): $agents agents, +$added/-$removed products\n";
                $synced++;
                $totalAdded += $added;
                $totalRemoved += $removed;
            }
        } else {
            echo "  - FAIL $name (#$cid): " . ($result['error'] ?? 'unknown error') . "\n";
        }
    }

    echo "\nSummary: $synced contracts synced, $skipped skipped (no agents), +$totalAdded products added, -$totalRemoved removed.\n";
}
