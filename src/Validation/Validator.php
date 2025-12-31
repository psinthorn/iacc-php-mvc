<?php

namespace App\Validation;

use App\Foundation\Logger;

/**
 * Validator - Input validation engine
 * 
 * Provides a simple but powerful validation system for user input.
 * Rules can be stacked and include custom validation.
 * 
 * @example
 * $validator = new Validator();
 * $errors = $validator->validate($_POST, [
 *     'email' => 'required|email|unique:users,email',
 *     'age' => 'required|numeric|min:18|max:120',
 *     'password' => 'required|min:8|confirmed',
 * ]);
 * 
 * if (!empty($errors)) {
 *     // Handle validation errors
 * }
 */
class Validator
{
    /**
     * Validation error messages
     * @var array
     */
    protected $errors = [];

    /**
     * Custom validation rules
     * @var array
     */
    protected $customRules = [];

    /**
     * Database instance for unique rule
     * @var mixed
     */
    protected $database;

    /**
     * Logger instance
     * @var Logger
     */
    protected $logger;

    /**
     * Default error messages
     * @var array
     */
    protected $messages = [
        'required' => 'The :field field is required',
        'email' => 'The :field field must be a valid email',
        'numeric' => 'The :field field must be numeric',
        'string' => 'The :field field must be a string',
        'min' => 'The :field field must be at least :min characters',
        'max' => 'The :field field must not exceed :max characters',
        'min_value' => 'The :field field must be at least :min_value',
        'max_value' => 'The :field field must not exceed :max_value',
        'confirmed' => 'The :field confirmation does not match',
        'regex' => 'The :field field format is invalid',
        'unique' => 'The :field value already exists',
        'exists' => 'The selected :field is invalid',
        'in' => 'The :field value must be one of: :values',
        'array' => 'The :field field must be an array',
        'boolean' => 'The :field field must be true or false',
        'date' => 'The :field field must be a valid date',
        'custom' => 'The :field field is invalid',
    ];

    /**
     * Constructor
     *
     * @param array $customMessages Custom error messages
     * @param mixed $database Optional database instance
     * @param Logger $logger Optional logger
     */
    public function __construct(array $customMessages = [], $database = null, Logger $logger = null)
    {
        $this->messages = array_merge($this->messages, $customMessages);
        $this->database = $database;
        $this->logger = $logger;
    }

    /**
     * Validate data against rules
     *
     * @param array $data Data to validate
     * @param array $rules Validation rules
     * @return array Empty if valid, array of errors if invalid
     *
     * @example
     * $errors = $validator->validate($data, [
     *     'name' => 'required|string|min:3',
     *     'email' => 'required|email|unique:users',
     * ]);
     */
    public function validate(array $data, array $rules)
    {
        $this->errors = [];

        foreach ($rules as $field => $ruleString) {
            $rules_list = explode('|', $ruleString);

            foreach ($rules_list as $rule) {
                $this->validateField($field, $data[$field] ?? null, $rule, $data);

                // Stop on first error for required fields
                if (!empty($this->errors[$field]) && strpos($ruleString, 'required') !== false) {
                    break;
                }
            }
        }

        return $this->errors;
    }

    /**
     * Validate single field against rule
     *
     * @param string $field Field name
     * @param mixed $value Field value
     * @param string $rule Rule string (may include parameters)
     * @param array $allData All input data (for cross-field validation)
     * @return bool
     */
    protected function validateField($field, $value, $rule, array $allData)
    {
        // Parse rule and parameters
        if (strpos($rule, ':') !== false) {
            [$ruleName, $params] = explode(':', $rule, 2);
            $params = array_filter(array_map('trim', explode(',', $params)));
        } else {
            $ruleName = $rule;
            $params = [];
        }

        // Skip if field not present and not required
        if ($value === null && $ruleName !== 'required') {
            return true;
        }

        // Check custom rules first
        if (isset($this->customRules[$ruleName])) {
            $isValid = call_user_func($this->customRules[$ruleName], $value, $params, $field, $allData);
            if (!$isValid) {
                $this->addError($field, 'custom');
            }
            return $isValid;
        }

        // Check built-in rules
        $method = 'rule' . ucfirst($ruleName);
        if (method_exists($this, $method)) {
            $isValid = $this->{$method}($field, $value, $params, $allData);
            if (!$isValid) {
                $this->addError($field, $ruleName, array_combine(
                    array_map(fn($i) => match($ruleName) {
                        'min' => 'min',
                        'max' => 'max',
                        'min_value' => 'min_value',
                        'max_value' => 'max_value',
                        'in' => 'values',
                        default => 'param' . $i,
                    }, range(0, count($params) - 1)),
                    $params
                ));
            }
            return $isValid;
        }

        // Unknown rule
        if ($this->logger) {
            $this->logger->warning('Unknown validation rule', ['rule' => $ruleName]);
        }

        return true;
    }

    /**
     * Rule: field is required
     */
    protected function ruleRequired($field, $value, $params, $allData)
    {
        return $value !== null && $value !== '' && $value !== [];
    }

    /**
     * Rule: value is valid email
     */
    protected function ruleEmail($field, $value, $params, $allData)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Rule: value is numeric
     */
    protected function ruleNumeric($field, $value, $params, $allData)
    {
        return is_numeric($value);
    }

    /**
     * Rule: value is string
     */
    protected function ruleString($field, $value, $params, $allData)
    {
        return is_string($value);
    }

    /**
     * Rule: string minimum length
     */
    protected function ruleMin($field, $value, $params, $allData)
    {
        $min = (int)($params[0] ?? 0);
        return strlen((string)$value) >= $min;
    }

    /**
     * Rule: string maximum length
     */
    protected function ruleMax($field, $value, $params, $allData)
    {
        $max = (int)($params[0] ?? PHP_INT_MAX);
        return strlen((string)$value) <= $max;
    }

    /**
     * Rule: numeric minimum value
     */
    protected function ruleMinValue($field, $value, $params, $allData)
    {
        $min = (int)($params[0] ?? 0);
        return (int)$value >= $min;
    }

    /**
     * Rule: numeric maximum value
     */
    protected function ruleMaxValue($field, $value, $params, $allData)
    {
        $max = (int)($params[0] ?? PHP_INT_MAX);
        return (int)$value <= $max;
    }

    /**
     * Rule: password confirmation
     */
    protected function ruleConfirmed($field, $value, $params, $allData)
    {
        $confirmField = $field . '_confirmation';
        return isset($allData[$confirmField]) && $allData[$confirmField] === $value;
    }

    /**
     * Rule: regex pattern
     */
    protected function ruleRegex($field, $value, $params, $allData)
    {
        $pattern = $params[0] ?? '';
        return preg_match('/' . $pattern . '/', $value) > 0;
    }

    /**
     * Rule: value is unique in database
     */
    protected function ruleUnique($field, $value, $params, $allData)
    {
        if (!$this->database) {
            return true;
        }

        [$table, $column] = $params;
        $count = $this->database->selectOne(
            "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?",
            [$value]
        );

        return ($count['count'] ?? 0) === 0;
    }

    /**
     * Rule: value exists in database
     */
    protected function ruleExists($field, $value, $params, $allData)
    {
        if (!$this->database) {
            return true;
        }

        [$table, $column] = $params;
        $count = $this->database->selectOne(
            "SELECT COUNT(*) as count FROM {$table} WHERE {$column} = ?",
            [$value]
        );

        return ($count['count'] ?? 0) > 0;
    }

    /**
     * Rule: value in set
     */
    protected function ruleIn($field, $value, $params, $allData)
    {
        return in_array($value, $params, true);
    }

    /**
     * Rule: value is array
     */
    protected function ruleArray($field, $value, $params, $allData)
    {
        return is_array($value);
    }

    /**
     * Rule: value is boolean
     */
    protected function ruleBoolean($field, $value, $params, $allData)
    {
        return in_array($value, [true, false, 1, 0, '1', '0'], true);
    }

    /**
     * Rule: value is valid date
     */
    protected function ruleDate($field, $value, $params, $allData)
    {
        $format = $params[0] ?? 'Y-m-d';
        $d = \DateTime::createFromFormat($format, $value);
        return $d && $d->format($format) === $value;
    }

    /**
     * Register custom validation rule
     *
     * @param string $name Rule name
     * @param callable $callback Validation callback
     * @return self
     */
    public function addRule($name, callable $callback)
    {
        $this->customRules[$name] = $callback;
        return $this;
    }

    /**
     * Add custom error message
     *
     * @param string $field Field name
     * @param string $rule Rule name
     * @param array $params Rule parameters
     * @return void
     */
    protected function addError($field, $rule, array $params = [])
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }

        $message = $this->messages[$rule] ?? "The {$field} field is invalid";
        $message = str_replace(':field', $field, $message);

        foreach ($params as $key => $value) {
            $message = str_replace(':' . $key, $value, $message);
        }

        $this->errors[$field][] = $message;
    }

    /**
     * Get all validation errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Check if has errors
     *
     * @return bool
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Get first error message
     *
     * @return string|null
     */
    public function getFirstError()
    {
        foreach ($this->errors as $field => $messages) {
            return $messages[0] ?? null;
        }
        return null;
    }
}
