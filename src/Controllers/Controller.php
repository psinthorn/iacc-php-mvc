<?php

namespace App\Controllers;

use App\Foundation\Request;
use App\Foundation\Response;
use App\Foundation\ServiceContainer;
use App\Validation\Validator;
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\AuthorizationException;
use App\Exceptions\ApplicationException;

/**
 * Base Controller Class
 * All controllers extend this to inherit common functionality
 */
abstract class Controller
{
    protected $request;
    protected $response;
    protected $container;
    protected $services = [];

    public function __construct(
        Request $request,
        Response $response,
        ServiceContainer $container = null
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->container = $container;
    }

    /**
     * Get service from container
     */
    protected function service($name)
    {
        if (!$this->container) {
            throw new \RuntimeException("Service container not available");
        }
        return $this->container->get($name);
    }

    /**
     * Return JSON response
     */
    protected function json($data, $status = 200)
    {
        $this->response->setStatusCode($status);
        $this->response->setContentType('application/json');
        $this->response->send(json_encode(['status' => 'success', 'data' => $data]));
        return $this->response;
    }

    /**
     * Return JSON error response
     */
    protected function jsonError($message, $status = 400, $errors = [])
    {
        $this->response->setStatusCode($status);
        $this->response->setContentType('application/json');

        $response = [
            'status' => 'error',
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        $this->response->send(json_encode($response));
        return $this->response;
    }

    /**
     * Return paginated JSON response
     */
    protected function jsonPaginated($items, $page, $perPage, $total)
    {
        $this->response->setStatusCode(200);
        $this->response->setContentType('application/json');

        $response = [
            'status' => 'success',
            'data' => $items,
            'pagination' => [
                'page' => (int)$page,
                'per_page' => (int)$perPage,
                'total' => (int)$total,
                'last_page' => ceil($total / $perPage),
            ],
        ];

        $this->response->send(json_encode($response));
        return $this->response;
    }

    /**
     * Validate request data
     */
    protected function validate(array $data, array $rules)
    {
        $validator = new Validator();
        $errors = $validator->validate($data, $rules);

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }

        return true;
    }

    /**
     * Get all request data
     */
    protected function all()
    {
        return $this->request->all();
    }

    /**
     * Get specific request parameter
     */
    protected function get($key, $default = null)
    {
        return $this->request->get($key, $default);
    }

    /**
     * Get request body
     */
    protected function body()
    {
        return $this->request->getBody();
    }

    /**
     * Handle exceptions and return appropriate response
     */
    protected function handleException(\Exception $e)
    {
        // Log exception
        if (method_exists($this, 'logger')) {
            $this->logger()->error('Controller exception: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }

        // Return appropriate response based on exception type
        if ($e instanceof ValidationException) {
            return $this->jsonError(
                'Validation failed',
                422,
                $e->getErrors()
            );
        }

        if ($e instanceof NotFoundException) {
            return $this->jsonError($e->getMessage(), 404);
        }

        if ($e instanceof AuthorizationException) {
            return $this->jsonError($e->getMessage(), 403);
        }

        if ($e instanceof ApplicationException) {
            return $this->jsonError($e->getMessage(), 400);
        }

        // Generic error for unexpected exceptions
        return $this->jsonError(
            'An unexpected error occurred',
            500
        );
    }

    /**
     * Check if user is authenticated
     */
    protected function isAuthenticated()
    {
        return isset($this->request->user);
    }

    /**
     * Check if user has permission
     */
    protected function authorize($permission)
    {
        if (!$this->isAuthenticated()) {
            throw new AuthorizationException("Not authenticated");
        }

        // TODO: Implement permission checking
        return true;
    }

    /**
     * Get authenticated user
     */
    protected function user()
    {
        if (!$this->isAuthenticated()) {
            throw new AuthorizationException("Not authenticated");
        }

        return $this->request->user;
    }
}
