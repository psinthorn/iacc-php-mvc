<?php
namespace App\Workers\Handlers;

/**
 * SyncInventoryChangeHandler — v6.2 #82.
 *
 * Task type: 'sync_inventory_change'
 * Fires webhook events to subscribed partners when a tour_allotments row
 * changes. Reuses existing App\Models\Webhook::fireEvent() infrastructure
 * (HMAC signing, retry, api_webhook_deliveries log).
 *
 * Payload (set by TourAllotment::notifyAllotmentChange):
 *   {
 *     "allotment_id": 123,
 *     "event":        "allotment.created" | "allotment.updated" | "allotment.closed" | "allotment.snapshot",
 *     "occurred_at":  "2026-05-05T15:30:00+07:00"
 *   }
 *
 * The handler may UPGRADE 'allotment.updated' → 'allotment.depleted' if
 * booked_seats >= total_seats at fire time.
 *
 * Result:
 *   {
 *     "allotment_id": 123, "event_fired": "allotment.updated",
 *     "subscribers_notified": 3, "deliveries": [...]
 *   }
 */
class SyncInventoryChangeHandler implements TaskHandler
{
    /** Quick retry — webhook delivery has its own retry inside Webhook model. */
    public static int $maxAttempts = 2;

    public function handle(array $payload, array $context): array
    {
        global $db;

        $allotmentId = intval($payload['allotment_id'] ?? 0);
        $eventHint   = $payload['event'] ?? 'allotment.updated';
        $companyId   = intval($context['company_id']);

        if ($allotmentId <= 0 || $companyId <= 0) {
            throw new \RuntimeException('SyncInventoryChange: missing allotment_id or company_id');
        }

        // Load current allotment state (post-change). Column names verified
        // against actual schema: tour_fleets.fleet_name (not .name) and
        // model.model_name (not .name_en/.name_th).
        $sql = sprintf(
            "SELECT a.id, a.company_id, a.fleet_id, a.model_id, a.travel_date,
                    a.total_seats, a.booked_seats, a.is_closed, a.closed_reason,
                    a.deleted_at, a.created_at, a.updated_at,
                    f.fleet_name AS fleet_name,
                    m.model_name AS model_name
               FROM tour_allotments a
          LEFT JOIN tour_fleets f ON f.id = a.fleet_id
          LEFT JOIN model       m ON m.id = a.model_id
              WHERE a.id = %d AND a.company_id = %d
              LIMIT 1",
            $allotmentId, $companyId
        );
        $res = mysqli_query($db->conn, $sql);
        $row = $res ? mysqli_fetch_assoc($res) : null;

        if (!$row) {
            // Allotment vanished (hard-deleted between enqueue and run).
            // Still fire a 'allotment.closed' so partners can purge their cache.
            return [
                'allotment_id' => $allotmentId,
                'event_fired'  => 'allotment.closed',
                'subscribers_notified' => 0,
                'note' => 'allotment_not_found_in_db',
            ];
        }

        // Decide final event type based on current state. Hint may be upgraded.
        $finalEvent = $this->resolveEvent($row, $eventHint);

        // Build the partner-facing payload
        $eventPayload = $this->buildPayload($row, $finalEvent);

        // Fire via existing Webhook model (handles HMAC, retry, logging)
        $webhookModel = new \App\Models\Webhook();
        $deliveries = $webhookModel->fireEvent($companyId, $finalEvent, $eventPayload);

        return [
            'allotment_id'         => $allotmentId,
            'event_fired'          => $finalEvent,
            'subscribers_notified' => count($deliveries),
            'deliveries'           => array_map(fn($d) => [
                'webhook_id' => $d['webhook_id']   ?? null,
                'success'    => $d['success']      ?? false,
                'response_code' => $d['response_code'] ?? null,
            ], $deliveries),
        ];
    }

    /**
     * Resolve the final event name from the hint + current row state.
     * Upgrade rules:
     *   updated → depleted   when booked_seats >= total_seats and not closed
     *   updated → closed     when is_closed=1 OR deleted_at != NULL
     */
    private function resolveEvent(array $row, string $hint): string
    {
        if ($hint === 'allotment.created' || $hint === 'allotment.snapshot') {
            return $hint; // never upgrade these
        }
        if (!empty($row['deleted_at']) || intval($row['is_closed']) === 1) {
            return 'allotment.closed';
        }
        $total  = intval($row['total_seats']);
        $booked = intval($row['booked_seats']);
        if ($total > 0 && $booked >= $total) {
            return 'allotment.depleted';
        }
        return 'allotment.updated';
    }

    /** Build the partner-facing JSON payload (camelCase top-level for compat with existing webhook payloads). */
    private function buildPayload(array $row, string $event): array
    {
        $total  = intval($row['total_seats']);
        $booked = intval($row['booked_seats']);
        return [
            'event'       => $event,
            'occurred_at' => date('c'),
            'company_id'  => intval($row['company_id']),
            'allotment'   => [
                'id'              => intval($row['id']),
                'fleet_id'        => intval($row['fleet_id']),
                'fleet_name'      => $row['fleet_name'] ?? null,
                'model_id'        => $row['model_id'] !== null ? intval($row['model_id']) : null,
                'model_name'      => $row['model_name'] ?? null,
                'travel_date'     => $row['travel_date'],
                'total_seats'     => $total,
                'booked_seats'    => $booked,
                'available_seats' => max(0, $total - $booked),
                'is_closed'       => (bool)intval($row['is_closed']),
                'closed_reason'   => $row['closed_reason'] ?? null,
                'updated_at'      => $row['updated_at'],
            ],
        ];
    }
}
