<?php
namespace App\Workers\Config;

/**
 * RetryPolicy — central configuration for the v6.1 worker (#77).
 *
 * Three pieces of policy are centralized here:
 *
 *   1. DEFAULT_BACKOFF — delay schedule between retries when a handler fails.
 *      attempts=1 → wait 60s, attempts=2 → 300s, attempts=3 → 1500s,
 *      attempts=4 → 7200s, then dead-letter (schedule exhausted).
 *
 *   2. DEFAULT_MAX_ATTEMPTS — how many times to retry a handler that throws.
 *      Hits dead_letter when (attempts >= max_attempts) AND last attempt failed.
 *
 *   3. ABSOLUTE_MAX_ATTEMPTS — anti-footgun cap. No matter what a handler
 *      declares, the worker won't retry more than this. Prevents
 *      infinite-retry DoS via a misconfigured handler (#77 EC1).
 *
 * Per-handler overrides:
 *   A handler MAY declare these public static properties:
 *     public static int $maxAttempts   = 3;
 *     public static array $backoffSchedule = [30, 120];
 *   The worker will read them via resolveMaxAttempts() / resolveBackoff()
 *   below, then clamp $maxAttempts to ABSOLUTE_MAX_ATTEMPTS.
 *
 * Why centralized?
 *   - Pre-#77, these were constants inside WorkerRunner. Moving them out lets
 *     handlers, tests, and admin UI all reference the same source of truth.
 *   - When ops needs to change "default retries from 5 to 7", it's one line.
 */
final class RetryPolicy
{
    /**
     * Default delay-until-next-attempt in seconds, indexed by (attempts - 1).
     * attempts=1 → BACKOFF[0], attempts=2 → BACKOFF[1], etc.
     * When attempts > count(BACKOFF), schedule is exhausted → dead_letter.
     */
    public const DEFAULT_BACKOFF = [60, 300, 1500, 7200]; // 1m, 5m, 25m, 2h

    /** Retry up to this many times before dead-lettering, unless handler overrides. */
    public const DEFAULT_MAX_ATTEMPTS = 5;

    /**
     * Hard ceiling — handler-declared max_attempts is clamped to this value.
     * Protects against a handler shipping ::$maxAttempts = 1000 (footgun #77 EC1).
     */
    public const ABSOLUTE_MAX_ATTEMPTS = 20;

    /**
     * Resolve the next delay (seconds) between retries.
     *
     * @param int        $attempts          1-indexed: attempt that just failed
     * @param array|null $customSchedule    handler-declared override (or null for default)
     * @return int|null  seconds until next attempt, or NULL if schedule exhausted
     *                   (caller should dead-letter on null)
     */
    public static function nextDelay(int $attempts, ?array $customSchedule = null): ?int
    {
        $schedule = $customSchedule ?: self::DEFAULT_BACKOFF;
        if (empty($schedule)) {
            return null;
        }
        // attempts is 1-indexed; index = attempts - 1
        return $schedule[$attempts - 1] ?? null;
    }

    /**
     * Read a handler's declared max_attempts (if any) and clamp it.
     *
     * Precedence:
     *   1. Handler-declared static $maxAttempts (if valid int >= 1) — wins
     *   2. Per-row task_queue.max_attempts ($rowDefault) — fallback
     *   3. DEFAULT_MAX_ATTEMPTS — final fallback
     * In all cases, result is clamped to [1, ABSOLUTE_MAX_ATTEMPTS] (anti-footgun).
     *
     * @param string   $handlerClass fully-qualified class name
     * @param int|null $rowDefault   value from task_queue.max_attempts (or null)
     * @return int     effective max_attempts, in [1, ABSOLUTE_MAX_ATTEMPTS]
     */
    public static function resolveMaxAttempts(string $handlerClass, ?int $rowDefault = null): int
    {
        if (class_exists($handlerClass) && property_exists($handlerClass, 'maxAttempts')) {
            $value = $handlerClass::$maxAttempts;
            if (is_int($value) && $value >= 1) {
                return min($value, self::ABSOLUTE_MAX_ATTEMPTS);
            }
        }
        if ($rowDefault !== null && $rowDefault >= 1) {
            return min($rowDefault, self::ABSOLUTE_MAX_ATTEMPTS);
        }
        return self::DEFAULT_MAX_ATTEMPTS;
    }

    /**
     * Read a handler's declared backoff schedule (if any).
     *
     * @param string $handlerClass fully-qualified class name
     * @return array<int> backoff schedule in seconds
     */
    public static function resolveBackoff(string $handlerClass): array
    {
        if (class_exists($handlerClass) && property_exists($handlerClass, 'backoffSchedule')) {
            $value = $handlerClass::$backoffSchedule;
            if (is_array($value) && !empty($value)) {
                // Validate every element is a positive int (defensive)
                $clean = array_values(array_filter($value, fn($v) => is_int($v) && $v > 0));
                if (!empty($clean)) {
                    return $clean;
                }
            }
        }
        return self::DEFAULT_BACKOFF;
    }
}
