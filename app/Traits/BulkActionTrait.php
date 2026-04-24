<?php
namespace App\Traits;

/**
 * BulkActionTrait — Reusable multi-row bulk action handler
 *
 * Usage in any controller:
 *   use App\Traits\BulkActionTrait;
 *
 * The consuming controller MUST implement:
 *   protected function allowedBulkActions(): array
 *   protected function executeBulkAction(string $action, array $ids): array
 *
 * executeBulkAction() must return:
 *   ['processed' => int, 'failed' => int, 'errors' => string[]]
 */
trait BulkActionTrait
{
    /**
     * Entry point — call this from your POST route method.
     *
     * Expects POST body:
     *   action     string  — one of allowedBulkActions()
     *   ids        int[]   — array of record IDs
     *   csrf_token ...     — verified automatically
     */
    public function handleBulkAction(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $this->verifyCsrf();

        $action = trim($_POST['action'] ?? '');
        if ($action === '' || !in_array($action, $this->allowedBulkActions(), true)) {
            $this->json(['success' => false, 'message' => 'Invalid or disallowed action'], 422);
        }

        $raw = $_POST['ids'] ?? [];
        if (!is_array($raw)) {
            $this->json(['success' => false, 'message' => 'ids must be an array'], 422);
        }

        $ids = array_values(array_filter(array_map('intval', $raw)));

        if (empty($ids)) {
            $this->json(['success' => false, 'message' => 'No records selected'], 422);
        }

        if (count($ids) > 500) {
            $this->json(['success' => false, 'message' => 'Too many records selected (max 500)'], 422);
        }

        $result = $this->executeBulkAction($action, $ids);

        $processed = intval($result['processed'] ?? 0);
        $failed    = intval($result['failed']    ?? 0);
        $errors    = (array) ($result['errors']  ?? []);

        $this->json([
            'success'   => $failed === 0,
            'message'   => $processed . ' record(s) processed' . ($failed > 0 ? ", $failed failed" : ''),
            'processed' => $processed,
            'failed'    => $failed,
            'errors'    => $errors,
        ]);
    }

    /**
     * Verify IDs belong to current tenant.
     * Returns only IDs that pass ownership + soft-delete check.
     *
     * @param string $table  Table name (must have company_id, id, deleted_at columns)
     * @param int[]  $ids    Candidate IDs (already int-cast)
     * @param int    $comId  Current tenant company_id
     * @return int[]
     */
    protected function filterOwnedIds(string $table, array $ids, int $comId): array
    {
        if (empty($ids)) {
            return [];
        }

        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        $comId = intval($comId);

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT id FROM `$table`
             WHERE company_id = ? AND id IN ($placeholders) AND deleted_at IS NULL"
        );

        if (!$stmt) {
            return [];
        }

        $types  = str_repeat('i', count($ids) + 1);
        $params = array_merge([$comId], $ids);

        $refs = [];
        foreach ($params as $k => $v) {
            $refs[$k] = &$params[$k];
        }
        mysqli_stmt_bind_param($stmt, $types, ...$refs);
        mysqli_stmt_execute($stmt);

        $res   = mysqli_stmt_get_result($stmt);
        $owned = [];
        while ($row = mysqli_fetch_assoc($res)) {
            $owned[] = intval($row['id']);
        }
        mysqli_stmt_close($stmt);

        return $owned;
    }
}
