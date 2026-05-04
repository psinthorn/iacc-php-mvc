# Task Priority Convention (v6.1 Worker)

> Authoritative reference for `task_queue.priority`.
> Worker (`app/Workers/WorkerRunner.php`) processes tasks in `priority ASC, scheduled_for ASC` order — **lower number = picked up first**.

## Three Tiers

| Tier | Range | Tag | Use For | Examples |
|---|---|---|---|---|
| **High** | `1`–`3` | 🔴 user-blocking | Time-critical, user-facing actions where delay = bad UX | Password reset email, payment confirmation, login OTP, signup welcome |
| **Normal** | `4`–`6` (default `5`) | 🟡 operational | Standard background work; users notice if it lags but it isn't blocking | Voucher email send, invoice PDF generation, channel inventory sync, webhook delivery |
| **Low** | `7`–`10` | 🟢 batch | Maintenance, reports, anything OK to defer behind everything else | Weekly AR aging report, monthly P&L PDF, exchange-rate updater, data cleanup |

## Picking the Right Number

- **Default is `5`** — when in doubt, use 5.
- **Don't use `1`** unless the user is actively waiting (e.g. password reset). Reserve top priority for genuine emergencies; if everything is `1`, nothing is.
- **Don't use `10`** for anything that has an SLA. `10` may sit behind 100 normal-priority tasks for an hour on a busy day.
- **Match priority to expected throughput.** With 1-min cron + 1 task/tick, queue can drain ~1,440 tasks/day. A burst of 500 high-priority tasks delays low-priority work by ~8 hours.

## Priority is NOT the Same as Retry

- `priority` controls **order** of pickup.
- `RetryPolicy` controls **how many times** a failed handler retries and **how long** between attempts (see `app/Workers/Config/RetryPolicy.php`).

A `priority=1` password-reset email that throws still respects the same backoff schedule (1m → 5m → 25m → 2h → dead-letter) as a `priority=10` cleanup job. To change that, override the handler:

```php
class PasswordResetEmailHandler implements TaskHandler {
    public static int $maxAttempts = 3;          // fail-fast: 3 tries instead of 5
    public static array $backoffSchedule = [30, 120]; // 30s → 2m → dead-letter

    public function handle(array $payload, array $context): array { ... }
}
```

## Hard Cap (Anti-Footgun)

`RetryPolicy::ABSOLUTE_MAX_ATTEMPTS = 20` — no matter what a handler declares, the worker will not retry more than 20 times. This prevents a misconfigured handler from creating an infinite retry loop. Document and respect this; revisit only with PM + DBA approval.

## Anti-Patterns

| Don't | Why |
|---|---|
| Use `priority=1` as the default | Defeats the entire prioritization system |
| Hard-code priority inside a handler | Priority is **per-task**, not per-task-type. The enqueueing code decides. |
| Set `priority=10` for a task with an SLA | Low-priority work can sit indefinitely behind a queue burst |
| Ship a handler with `static $maxAttempts = 50` | Will be clamped to 20; document the cap if you actually need long retries |
| Use priority to express dependency ordering ("run task A before task B") | Priority is best-effort. For real ordering, enqueue B from inside A's handler on success. |

## When to Revisit

- If we routinely see `priority=1` queue depth > 50, the high tier is being abused → tighten the convention.
- If `priority=10` tasks regularly exceed 24h queue time, throughput is the bottleneck → consider parallel workers (out of v6.1 scope) or trim cron interval.
- If we add cross-tenant fairness requirements (one tenant's burst shouldn't starve another), priority alone won't solve it — needs per-tenant weighted scheduling, defer to v6.x re-arch.

## Related

- Schema: `database/migrations/2026_05_01_task_queue.sql` — `task_queue.priority TINYINT UNSIGNED DEFAULT 5`
- Worker: `app/Workers/WorkerRunner.php` — polling query `ORDER BY priority ASC, scheduled_for ASC, id ASC`
- Policy class: `app/Workers/Config/RetryPolicy.php` — DEFAULT_MAX_ATTEMPTS, DEFAULT_BACKOFF, ABSOLUTE_MAX_ATTEMPTS, resolveMaxAttempts(), resolveBackoff(), nextDelay()
- Sprint context: GitHub milestone v6.1 (#75–#78); this file is mandated by #77 AC + Out-of-Scope notes
