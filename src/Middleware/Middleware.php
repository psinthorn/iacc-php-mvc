<?php

namespace App\Middleware;

use App\Foundation\Request;
use App\Foundation\Response;

/**
 * Middleware - Base middleware class for request/response pipeline
 * 
 * Middleware allows you to:
 * - Inspect/modify requests before reaching handler
 * - Modify responses before sending
 * - Perform early termination
 * - Chain multiple middleware together
 */
abstract class Middleware
{
    /**
     * Process the request
     * 
     * @param Request $request
     * @param callable $next Next middleware in pipeline
     * @return Response
     */
    abstract public function handle(Request $request, callable $next);

    /**
     * Terminate/finalize the response
     * 
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function terminate(Request $request, Response $response)
    {
        // Override in child classes for cleanup tasks
    }
}

/**
 * MiddlewarePipeline - Middleware execution pipeline
 */
class MiddlewarePipeline
{
    /**
     * Middleware stack
     * @var array
     */
    protected $middleware = [];

    /**
     * Request handler
     * @var callable
     */
    protected $handler;

    /**
     * Constructor
     * 
     * @param callable $handler Final request handler
     */
    public function __construct(callable $handler)
    {
        $this->handler = $handler;
    }

    /**
     * Add middleware to pipeline
     * 
     * @param Middleware|string $middleware Middleware instance or class name
     * @return self
     */
    public function add($middleware)
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Execute the pipeline
     * 
     * @param Request $request
     * @return Response
     */
    public function execute(Request $request)
    {
        return $this->executeMiddleware($request, 0);
    }

    /**
     * Execute middleware in sequence
     * 
     * @param Request $request
     * @param int $index Current middleware index
     * @return Response
     */
    protected function executeMiddleware(Request $request, $index)
    {
        // If we've processed all middleware, execute the handler
        if ($index >= count($this->middleware)) {
            return call_user_func($this->handler, $request);
        }

        $middleware = $this->middleware[$index];

        // If middleware is a string, assume it's a class name
        if (is_string($middleware)) {
            $middleware = new $middleware();
        }

        // Create the next callable in the chain
        $next = function ($request) use ($index) {
            return $this->executeMiddleware($request, $index + 1);
        };

        // Execute the middleware
        return $middleware->handle($request, $next);
    }

    /**
     * Terminate all middleware
     * 
     * @param Request $request
     * @param Response $response
     * @return void
     */
    public function terminate(Request $request, Response $response)
    {
        foreach (array_reverse($this->middleware) as $middleware) {
            if (is_string($middleware)) {
                $middleware = new $middleware();
            }

            $middleware->terminate($request, $response);
        }
    }
}

/**
 * Example middleware implementations
 */

/**
 * CORS Middleware - Handle Cross-Origin Resource Sharing
 */
class CorsMiddleware extends Middleware
{
    /**
     * Allowed origins
     * @var array
     */
    protected $allowedOrigins = ['*'];

    public function __construct($origins = ['*'])
    {
        $this->allowedOrigins = $origins;
    }

    public function handle(Request $request, callable $next)
    {
        $response = $next($request);

        $origin = $request->header('Origin', '*');

        if ($this->isOriginAllowed($origin)) {
            $response->header('Access-Control-Allow-Origin', $origin);
            $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
            $response->header('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    protected function isOriginAllowed($origin)
    {
        return in_array($origin, $this->allowedOrigins) || in_array('*', $this->allowedOrigins);
    }
}

/**
 * Authentication Middleware - Require valid authentication
 */
class AuthenticationMiddleware extends Middleware
{
    public function handle(Request $request, callable $next)
    {
        // Check for authorization header
        $authHeader = $request->header('Authorization');

        if (!$authHeader) {
            $response = new Response();
            $response->status(401)
                ->json(['error' => 'Authentication required']);
            return $response;
        }

        // Verify token (simplified example)
        if (!$this->verifyToken($authHeader)) {
            $response = new Response();
            $response->status(401)
                ->json(['error' => 'Invalid token']);
            return $response;
        }

        return $next($request);
    }

    protected function verifyToken($token)
    {
        // Implement actual token verification logic
        // This is a placeholder
        return !empty($token) && strpos($token, 'Bearer ') === 0;
    }
}

/**
 * Logging Middleware - Log all requests
 */
class LoggingMiddleware extends Middleware
{
    /**
     * Logger instance
     * @var Logger
     */
    protected $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    public function handle(Request $request, callable $next)
    {
        $start = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $start) * 1000, 2);

        $this->logger->info(
            "{$request->method()} {$request->path()} - {$response->getStatusCode()} ({$duration}ms)"
        );

        return $response;
    }
}
