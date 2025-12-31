<?php

namespace App\Events;

use App\Foundation\Logger;

/**
 * EventBus - Publish-subscribe event dispatcher
 * 
 * Allows services to dispatch domain events and register listeners.
 * Enables loose coupling and side effect management.
 * 
 * @example
 * // Register event listeners
 * $bus->listen(OrderCreated::class, function(OrderCreated $event) {
 *     $mailer->sendOrderConfirmation($event->order);
 * });
 * 
 * // Dispatch event
 * $bus->dispatch(new OrderCreated($order));
 */
class EventBus
{
    /**
     * Event listeners registry
     * Format: ['EventClass' => [callable, callable, ...]]
     * @var array
     */
    protected $listeners = [];

    /**
     * Logger for event dispatch logging
     * @var Logger
     */
    protected $logger;

    /**
     * Dispatched events history for debugging
     * @var array
     */
    protected $history = [];

    /**
     * Whether to enable event logging
     * @var bool
     */
    protected $enableLogging = true;

    /**
     * Constructor
     *
     * @param Logger $logger Optional logger instance
     */
    public function __construct(Logger $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Register event listener
     *
     * @param string $eventClass Event class name
     * @param callable $callback Listener callback
     * @return self
     *
     * @example
     * $bus->listen(UserCreated::class, function($event) {
     *     sendWelcomeEmail($event->user);
     * });
     */
    public function listen(string $eventClass, callable $callback)
    {
        if (!isset($this->listeners[$eventClass])) {
            $this->listeners[$eventClass] = [];
        }

        $this->listeners[$eventClass][] = $callback;

        return $this;
    }

    /**
     * Register multiple listeners for an event
     *
     * @param string $eventClass Event class name
     * @param array $callbacks Array of callbacks
     * @return self
     */
    public function listenMany(string $eventClass, array $callbacks)
    {
        foreach ($callbacks as $callback) {
            $this->listen($eventClass, $callback);
        }

        return $this;
    }

    /**
     * Dispatch an event to all listeners
     *
     * @param Event $event Event instance
     * @return void
     */
    public function dispatch(Event $event)
    {
        $eventClass = get_class($event);

        // Log dispatch
        if ($this->enableLogging && $this->logger) {
            $this->logger->debug('Event dispatched', [
                'event' => $eventClass,
                'listeners' => count($this->listeners[$eventClass] ?? []),
            ]);
        }

        // Record in history
        $this->history[] = [
            'event' => $eventClass,
            'timestamp' => microtime(true),
        ];

        // Call all listeners
        if (isset($this->listeners[$eventClass])) {
            foreach ($this->listeners[$eventClass] as $listener) {
                try {
                    call_user_func($listener, $event);
                } catch (\Exception $e) {
                    if ($this->logger) {
                        $this->logger->error('Event listener failed', [
                            'event' => $eventClass,
                            'error' => $e->getMessage(),
                            'listener' => get_debug_type($listener),
                        ]);
                    }
                    // Continue with other listeners
                }
            }
        }
    }

    /**
     * Forget all listeners for an event
     *
     * @param string $eventClass Event class name
     * @return self
     */
    public function forget(string $eventClass)
    {
        unset($this->listeners[$eventClass]);
        return $this;
    }

    /**
     * Forget all listeners
     *
     * @return self
     */
    public function forgetAll()
    {
        $this->listeners = [];
        return $this;
    }

    /**
     * Check if event has listeners
     *
     * @param string $eventClass Event class name
     * @return bool
     */
    public function hasListeners(string $eventClass)
    {
        return isset($this->listeners[$eventClass]) && !empty($this->listeners[$eventClass]);
    }

    /**
     * Get listener count for event
     *
     * @param string $eventClass Event class name
     * @return int
     */
    public function getListenerCount(string $eventClass)
    {
        return count($this->listeners[$eventClass] ?? []);
    }

    /**
     * Enable event logging
     *
     * @return self
     */
    public function enableLogging()
    {
        $this->enableLogging = true;
        return $this;
    }

    /**
     * Disable event logging
     *
     * @return self
     */
    public function disableLogging()
    {
        $this->enableLogging = false;
        return $this;
    }

    /**
     * Get event dispatch history
     *
     * @param int $limit Maximum number of events
     * @return array
     */
    public function getHistory($limit = 100)
    {
        return array_slice($this->history, -$limit);
    }

    /**
     * Clear history
     *
     * @return self
     */
    public function clearHistory()
    {
        $this->history = [];
        return $this;
    }

    /**
     * Get total events dispatched
     *
     * @return int
     */
    public function getDispatchCount()
    {
        return count($this->history);
    }
}
