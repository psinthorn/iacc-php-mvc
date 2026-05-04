<?php
namespace App\Workers\Handlers;

/**
 * EchoHandler — no-op handler for end-to-end pipeline testing.
 *
 * Task type: 'echo'
 * Payload:   {"msg": "anything"}  (or any JSON object)
 * Result:    {"echoed": <payload>, "received_at": "<ISO8601>", "company_id": N, "attempt": N}
 *
 * Use this to:
 *   - Smoke-test the worker pipeline without depending on email/PDF/external APIs
 *   - Verify multi-tenant context propagation
 *   - Verify retry behaviour by sending {"fail": true} (handler will throw)
 *
 * NOT for production workloads.
 */
class EchoHandler implements TaskHandler
{
    public function handle(array $payload, array $context): array
    {
        // Allow tests to force a failure via {"fail": true}
        if (!empty($payload['fail'])) {
            throw new \RuntimeException(
                'EchoHandler intentional failure: ' . ($payload['reason'] ?? 'no reason given')
            );
        }

        return [
            'echoed'      => $payload,
            'received_at' => date('c'),
            'company_id'  => $context['company_id'] ?? null,
            'task_id'     => $context['task_id']    ?? null,
            'attempt'     => $context['attempt']    ?? null,
        ];
    }
}
