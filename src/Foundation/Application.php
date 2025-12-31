<?php

namespace App\Foundation;

use App\Exceptions\NotFoundException;
use App\Middleware\MiddlewarePipeline;

/**
 * Application - Main application class
 * 
 * Orchestrates the application lifecycle:
 * - Service registration
 * - Route matching
 * - Request handling
 * - Response sending
 * - Error handling
 */
class Application
{
    /**
     * Service container
     * @var ServiceContainer
     */
    protected $container;

    /**
     * Router instance
     * @var Router
     */
    protected $router;

    /**
     * Configuration
     * @var Config
     */
    protected $config;

    /**
     * Logger
     * @var Logger
     */
    protected $logger;

    /**
     * Exception handler
     * @var callable
     */
    protected $exceptionHandler;

    /**
     * Constructor
     * 
     * @param ServiceContainer $container
     * @param Router $router
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(ServiceContainer $container, Router $router, Config $config, Logger $logger)
    {
        $this->container = $container;
        $this->router = $router;
        $this->config = $config;
        $this->logger = $logger;

        // Set up error/exception handling
        $this->setupErrorHandling();
    }

    /**
     * Set up error and exception handling
     * 
     * @return void
     */
    protected function setupErrorHandling()
    {
        // Convert errors to exceptions
        set_error_handler(function ($errno, $errstr, $errfile, $errline) {
            if (error_reporting() & $errno) {
                throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
            }
        });

        // Handle exceptions
        set_exception_handler(function ($exception) {
            $this->handleException($exception);
        });
    }

    /**
     * Handle an exception
     * 
     * @param Exception $exception
     * @return void
     */
    public function handleException($exception)
    {
        if ($this->exceptionHandler) {
            call_user_func($this->exceptionHandler, $exception);
        } else {
            // Default exception handling
            $response = new Response();

            // Check if exception has a code (HTTP status)
            $code = $exception->getCode() ?: 500;
            if ($code < 100 || $code > 599) {
                $code = 500;
            }

            if ($this->config->get('app.debug')) {
                // Debug mode - show full exception
                $body = [
                    'error' => get_class($exception),
                    'message' => $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine(),
                    'trace' => $exception->getTraceAsString(),
                ];
            } else {
                // Production - show generic error
                $body = [
                    'error' => 'Error',
                    'message' => 'An error occurred',
                ];
            }

            $response->status($code)->json($body)->send();
        }
    }

    /**
     * Set custom exception handler
     * 
     * @param callable $handler
     * @return void
     */
    public function setExceptionHandler(callable $handler)
    {
        $this->exceptionHandler = $handler;
    }

    /**
     * Handle an HTTP request
     * 
     * @param Request $request
     * @return Response
     */
    public function handleRequest(Request $request)
    {
        try {
            // Match route
            $route = $this->router->match($request->method(), $request->path());

            if (!$route) {
                throw new NotFoundException("Route not found: {$request->method()} {$request->path()}");
            }

            // Set route parameters on request
            $request->setRouteParameters($route->parameters());

            // Log the request
            $this->logger->info("Matched route: {$request->method()} {$request->path()}");

            // Build middleware pipeline
            $pipeline = new MiddlewarePipeline(function ($request) use ($route) {
                return $this->dispatch($request, $route);
            });

            // Add route-specific middleware
            foreach ($route->middleware as $middleware) {
                $pipeline->add($middleware);
            }

            // Execute pipeline
            $response = $pipeline->execute($request);

            // Terminate middleware
            $pipeline->terminate($request, $response);

            return $response;
        } catch (\Exception $e) {
            $this->handleException($e);
            exit;
        }
    }

    /**
     * Dispatch request to handler
     * 
     * @param Request $request
     * @param Route $route
     * @return Response
     */
    protected function dispatch(Request $request, Route $route)
    {
        $handler = $route->handler;

        // If handler is a string like "UserController@show", resolve it
        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler);

            // Resolve controller from container
            if (!class_exists($controller)) {
                $controller = "App\\Controllers\\{$controller}";
            }

            $controllerInstance = $this->container->get($controller);

            if (!method_exists($controllerInstance, $method)) {
                throw new \Exception("Method {$method} not found in {$controller}");
            }

            return $controllerInstance->$method($request);
        }

        // If handler is a closure, call it
        if ($handler instanceof \Closure) {
            return $handler($request);
        }

        throw new \Exception("Invalid handler type");
    }

    /**
     * Run the application
     * 
     * @return void
     */
    public function run()
    {
        // Create request from globals
        $request = new Request();

        // Handle request
        $response = $this->handleRequest($request);

        // Send response
        $response->send();
    }

    /**
     * Get service container
     * 
     * @return ServiceContainer
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get router
     * 
     * @return Router
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * Get config
     * 
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get logger
     * 
     * @return Logger
     */
    public function getLogger()
    {
        return $this->logger;
    }
}
