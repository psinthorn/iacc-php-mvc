<?php

namespace App\Services;

use App\Foundation\Database;
use App\Foundation\Logger;
use App\Validation\Validator;
use App\Events\EventBus;
use App\Exceptions\TransactionException;

/**
 * Service - Base class for all application services
 * 
 * Provides common functionality:
 * - Transaction management
 * - Input validation
 * - Event dispatching
 * - Logging
 * - Error handling
 */
abstract class Service
{
    /**
     * Database instance
     * @var Database
     */
    protected $database;

    /**
     * Logger instance
     * @var Logger
     */
    protected $logger;

    /**
     * Validator instance
     * @var Validator
     */
    protected $validator;

    /**
     * Event bus for domain events
     * @var EventBus
     */
    protected $eventBus;

    /**
     * Constructor
     *
     * @param Database $database
     * @param Logger $logger
     * @param Validator $validator
     * @param EventBus $eventBus Optional event bus
     */
    public function __construct(
        Database $database,
        Logger $logger,
        Validator $validator,
        EventBus $eventBus = null
    ) {
        $this->database = $database;
        $this->logger = $logger;
        $this->validator = $validator;
        $this->eventBus = $eventBus;
    }

    /**
     * Execute code within a database transaction
     *
     * If the callback succeeds, the transaction is committed.
     * If an exception is thrown, the transaction is rolled back.
     *
     * @param callable $callback Function to execute within transaction
     * @return mixed Result of callback
     *
     * @throws TransactionException If transaction fails
     *
     * @example
     * $result = $this->transaction(function () {
     *     // Multiple database operations
     *     $user = $this->userRepo->create($data);
     *     $this->profileRepo->create(['user_id' => $user->id]);
     *     return $user;
     * });
     */
    protected function transaction(callable $callback)
    {
        try {
            $this->database->beginTransaction();

            $result = $callback();

            $this->database->commit();

            return $result;
        } catch (\Exception $e) {
            try {
                $this->database->rollback();
            } catch (\Exception $rollbackError) {
                $this->logger->error('Transaction rollback failed', [
                    'original_error' => $e->getMessage(),
                    'rollback_error' => $rollbackError->getMessage(),
                ]);
            }

            throw new TransactionException(
                'Transaction failed: ' . $e->getMessage(),
                $e
            );
        }
    }

    /**
     * Validate input data against rules
     *
     * @param array $data Input data to validate
     * @param array $rules Validation rules
     * @return array Empty array if valid, array of errors if invalid
     *
     * @example
     * $errors = $this->validate($data, [
     *     'email' => 'required|email',
     *     'age' => 'required|numeric|min:18',
     * ]);
     */
    protected function validate(array $data, array $rules)
    {
        return $this->validator->validate($data, $rules);
    }

    /**
     * Dispatch a domain event
     *
     * Events are useful for:
     * - Triggering side effects (emails, notifications)
     * - Decoupling services
     * - Audit logging
     * - Real-time updates
     *
     * @param object $event Event object
     * @return void
     *
     * @example
     * $this->dispatch(new OrderCreated($order));
     * $this->dispatch(new PaymentReceived($payment));
     */
    protected function dispatch(object $event)
    {
        if ($this->eventBus) {
            $this->eventBus->dispatch($event);
        }
    }

    /**
     * Log an action with context
     *
     * @param string $action Action name/message
     * @param array $context Additional context data
     * @param string $level Log level (debug, info, warning, error)
     * @return void
     *
     * @example
     * $this->log('user_created', [
     *     'user_id' => $user->id,
     *     'email' => $user->email,
     * ]);
     */
    protected function log($action, array $context = [], $level = 'info')
    {
        $method = $level === 'info' ? 'info' : $level;
        
        if (!method_exists($this->logger, $method)) {
            $method = 'info';
        }

        $this->logger->{$method}($action, $context);
    }

    /**
     * Check if event bus is available
     *
     * @return bool
     */
    protected function hasEventBus()
    {
        return $this->eventBus !== null;
    }

    /**
     * Get database instance
     *
     * @return Database
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Get logger instance
     *
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Get validator instance
     *
     * @return Validator
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Get event bus instance
     *
     * @return EventBus|null
     */
    public function getEventBus()
    {
        return $this->eventBus;
    }
}
