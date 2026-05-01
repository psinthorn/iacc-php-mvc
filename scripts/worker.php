<?php
/**
 * scripts/worker.php — entry point for the v6.1 background-worker tick (#76).
 *
 * NOT meant to be called directly via web. Invoked from cron.php:
 *
 *   curl -s "https://yourdomain.com/cron.php?task=run_worker&token=YOUR_SECRET"
 *
 * cPanel cron line (every minute):
 *   * * * * * curl -s "https://yourdomain.com/cron.php?task=run_worker&token=YOUR_SECRET" >> ~/logs/worker.log 2>&1
 *
 * Each invocation processes AT MOST ONE task. With a 1-min cron, max throughput
 * is ~1 task/min ≈ 1,440/day per server. Document this ceiling for ops.
 *
 * This file is intentionally thin — all logic lives in App\Workers\WorkerRunner
 * for testability. Don't add code here; extend WorkerRunner instead.
 */

if (!isset($db) || !$db instanceof DbConn) {
    http_response_code(500);
    echo "ERROR: scripts/worker.php must be required from cron.php (no \$db in scope)\n";
    exit(1);
}

require_once __DIR__ . '/../app/Workers/Handlers/TaskHandler.php';
require_once __DIR__ . '/../app/Workers/Handlers/EchoHandler.php';
require_once __DIR__ . '/../app/Workers/WorkerRunner.php';

$runner = new \App\Workers\WorkerRunner($db->conn);
$result = $runner->run();

// Structured JSON output to stdout — picked up by cron log (PM spec AC12)
echo json_encode($result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
