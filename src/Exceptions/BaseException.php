<?php

namespace App\Exceptions;

use Exception;

/**
 * BaseException - Base exception for all application exceptions
 */
class BaseException extends Exception
{
    /**
     * Exception severity level
     * @var int
     */
    protected $level = E_ERROR;

    /**
     * Context data for debugging
     * @var array
     */
    protected $context = [];

    /**
     * Constructor
     * 
     * @param string $message Exception message
     * @param int $code Exception code
     * @param Exception $previous Previous exception
     * @param array $context Debug context
     */
    public function __construct($message = '', $code = 0, Exception $previous = null, $context = [])
    {
        parent::__construct($message, $code, $previous);
        $this->context = $context;
    }

    /**
     * Get context data
     * 
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Set context data
     * 
     * @param array $context
     * @return self
     */
    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }

    /**
     * Get severity level
     * 
     * @return int
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * Get exception as array (for logging/API response)
     * 
     * @return array
     */
    public function toArray()
    {
        return [
            'error' => class_basename($this),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'file' => $this->getFile(),
            'line' => $this->getLine(),
            'context' => $this->context,
        ];
    }
}

/**
 * NotFoundException - Resource not found exception (404)
 */
class NotFoundException extends BaseException
{
    protected $code = 404;

    public function __construct($message = 'Not found', Exception $previous = null)
    {
        parent::__construct($message, 404, $previous);
    }
}

/**
 * ValidationException - Input validation failed
 */
class ValidationException extends BaseException
{
    protected $code = 422;

    /**
     * Validation errors
     * @var array
     */
    protected $errors = [];

    /**
     * Constructor
     * 
     * @param array $errors Validation errors
     * @param string $message
     */
    public function __construct($errors = [], $message = 'Validation failed')
    {
        parent::__construct($message, 422);
        $this->errors = $errors;
    }

    /**
     * Get validation errors
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get error messages for a field
     * 
     * @param string $field
     * @return array
     */
    public function getFieldErrors($field)
    {
        return $this->errors[$field] ?? [];
    }

    /**
     * Has errors for field
     * 
     * @param string $field
     * @return bool
     */
    public function hasFieldErrors($field)
    {
        return isset($this->errors[$field]);
    }

    public function toArray()
    {
        $data = parent::toArray();
        $data['errors'] = $this->errors;
        return $data;
    }
}

/**
 * AuthenticationException - Authentication failed (401)
 */
class AuthenticationException extends BaseException
{
    protected $code = 401;

    public function __construct($message = 'Authentication required', Exception $previous = null)
    {
        parent::__construct($message, 401, $previous);
    }
}

/**
 * AuthorizationException - Authorization denied (403)
 */
class AuthorizationException extends BaseException
{
    protected $code = 403;

    public function __construct($message = 'Access denied', Exception $previous = null)
    {
        parent::__construct($message, 403, $previous);
    }
}

/**
 * ConflictException - Resource conflict (409)
 */
class ConflictException extends BaseException
{
    protected $code = 409;

    public function __construct($message = 'Conflict', Exception $previous = null)
    {
        parent::__construct($message, 409, $previous);
    }
}

/**
 * ServerException - Server error (500)
 */
class ServerException extends BaseException
{
    protected $code = 500;

    public function __construct($message = 'Server error', Exception $previous = null)
    {
        parent::__construct($message, 500, $previous);
    }
}
