<?php

namespace App\Http\Responses;

/**
 * API Response Helper
 * Standardized JSON responses for all endpoints
 */
class ApiResponse
{
    /**
     * Success response
     */
    public static function success($data = null, $message = null, $status = 200)
    {
        return [
            'status' => 'success',
            'message' => $message,
            'data' => $data,
            'code' => $status,
        ];
    }

    /**
     * Error response
     */
    public static function error($message, $status = 400, $errors = [], $code = null)
    {
        return [
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
            'code' => $code ?? $status,
        ];
    }

    /**
     * Paginated response
     */
    public static function paginated($items, $page, $perPage, $total)
    {
        return [
            'status' => 'success',
            'data' => $items,
            'pagination' => [
                'page' => (int)$page,
                'per_page' => (int)$perPage,
                'total' => (int)$total,
                'last_page' => ceil($total / $perPage),
                'from' => ($page - 1) * $perPage + 1,
                'to' => min($page * $perPage, $total),
            ],
        ];
    }

    /**
     * Validation error response
     */
    public static function validationError($errors)
    {
        return self::error('Validation failed', 422, $errors, 'VALIDATION_ERROR');
    }

    /**
     * Not found response
     */
    public static function notFound($message = 'Resource not found')
    {
        return self::error($message, 404, [], 'NOT_FOUND');
    }

    /**
     * Unauthorized response
     */
    public static function unauthorized($message = 'Unauthorized')
    {
        return self::error($message, 401, [], 'UNAUTHORIZED');
    }

    /**
     * Forbidden response
     */
    public static function forbidden($message = 'Forbidden')
    {
        return self::error($message, 403, [], 'FORBIDDEN');
    }

    /**
     * Conflict response
     */
    public static function conflict($message = 'Conflict')
    {
        return self::error($message, 409, [], 'CONFLICT');
    }

    /**
     * Server error response
     */
    public static function serverError($message = 'Server error')
    {
        return self::error($message, 500, [], 'SERVER_ERROR');
    }
}
