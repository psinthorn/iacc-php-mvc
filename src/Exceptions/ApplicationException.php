<?php

namespace App\Exceptions;

use Exception;

/**
 * ApplicationException - Base exception for application errors
 */
class ApplicationException extends Exception
{
    protected $context = [];

    public function __construct($message = '', $code = 0, Exception $previous = null, array $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function withContext(array $context)
    {
        $this->context = array_merge($this->context, $context);
        return $this;
    }
}

/**
 * ValidationException - Input validation failed
 * HTTP 422 Unprocessable Entity
 */
class ValidationException extends ApplicationException
{
    protected $errors = [];

    public function __construct(array $errors = [], Exception $previous = null)
    {
        $this->errors = $errors;
        parent::__construct('Validation failed', 422, $previous, ['errors' => $errors]);
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getFirstError()
    {
        foreach ($this->errors as $field => $messages) {
            return $messages[0] ?? null;
        }
        return null;
    }

    public function hasError($field)
    {
        return isset($this->errors[$field]);
    }

    public function getFieldErrors($field)
    {
        return $this->errors[$field] ?? [];
    }
}

/**
 * NotFoundException - Resource not found
 * HTTP 404 Not Found
 */
class NotFoundException extends ApplicationException
{
    public function __construct($message = 'Resource not found', Exception $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
}

/**
 * AuthorizationException - User not authorized
 * HTTP 403 Forbidden
 */
class AuthorizationException extends ApplicationException
{
    public function __construct($message = 'Unauthorized action', Exception $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
}

/**
 * BusinessException - Business rule violation
 * HTTP 400 Bad Request
 */
class BusinessException extends ApplicationException
{
    public function __construct($message = 'Business rule violation', Exception $previous = null)
    {
        parent::__construct($message, 400, $previous);
    }
}

/**
 * ConflictException - Resource conflict
 * HTTP 409 Conflict
 */
class ConflictException extends ApplicationException
{
    public function __construct($message = 'Resource conflict', Exception $previous = null)
    {
        parent::__construct($message, 409, $previous);
    }
}

/**
 * DatabaseException - Database operation failed
 * HTTP 500 Internal Server Error
 */
class DatabaseException extends ApplicationException
{
    public function __construct($message = 'Database operation failed', Exception $previous = null)
    {
        parent::__construct($message, 500, $previous);
    }
}

/**
 * TransactionException - Transaction operation failed
 * HTTP 500 Internal Server Error
 */
class TransactionException extends ApplicationException
{
    public function __construct($message = 'Transaction failed', Exception $previous = null)
    {
        parent::__construct($message, 500, $previous);
    }
}
