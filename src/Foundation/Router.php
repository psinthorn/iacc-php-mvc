<?php

namespace App\Foundation;

use Exception;

/**
 * Router - URL Routing and Dispatch System
 * 
 * Handles route registration and matching with support for:
 * - RESTful HTTP methods (GET, POST, PUT, DELETE, PATCH)
 * - Route parameters and constraints
 * - Middleware attachment
 * - Named routes
 * - Resource routes
 */
class Router
{
    /**
     * All registered routes
     * @var array
     */
    protected $routes = [];

    /**
     * Named routes for easy reference
     * @var array
     */
    protected $namedRoutes = [];

    /**
     * HTTP methods
     * @var array
     */
    protected $methods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'HEAD', 'OPTIONS'];

    /**
     * Register a GET route
     * 
     * @param string $path Route path
     * @param mixed $handler Handler (Closure, string, or array)
     * @return RouteRegistrar
     */
    public function get($path, $handler)
    {
        return $this->addRoute('GET', $path, $handler);
    }

    /**
     * Register a POST route
     * 
     * @param string $path Route path
     * @param mixed $handler Handler
     * @return RouteRegistrar
     */
    public function post($path, $handler)
    {
        return $this->addRoute('POST', $path, $handler);
    }

    /**
     * Register a PUT route
     * 
     * @param string $path Route path
     * @param mixed $handler Handler
     * @return RouteRegistrar
     */
    public function put($path, $handler)
    {
        return $this->addRoute('PUT', $path, $handler);
    }

    /**
     * Register a DELETE route
     * 
     * @param string $path Route path
     * @param mixed $handler Handler
     * @return RouteRegistrar
     */
    public function delete($path, $handler)
    {
        return $this->addRoute('DELETE', $path, $handler);
    }

    /**
     * Register a PATCH route
     * 
     * @param string $path Route path
     * @param mixed $handler Handler
     * @return RouteRegistrar
     */
    public function patch($path, $handler)
    {
        return $this->addRoute('PATCH', $path, $handler);
    }

    /**
     * Register routes for multiple methods
     * 
     * @param array $methods HTTP methods
     * @param string $path Route path
     * @param mixed $handler Handler
     * @return RouteRegistrar
     */
    public function match(array $methods, $path, $handler)
    {
        $registrar = null;
        foreach ($methods as $method) {
            $registrar = $this->addRoute($method, $path, $handler);
        }
        return $registrar;
    }

    /**
     * Register RESTful resource routes
     * 
     * @param string $name Resource name
     * @param string $controller Controller class name
     * @return void
     */
    public function resource($name, $controller)
    {
        // index: GET /resource
        $this->get("/{$name}", "{$controller}@index")->name("{$name}.index");

        // create: GET /resource/create
        $this->get("/{$name}/create", "{$controller}@create")->name("{$name}.create");

        // store: POST /resource
        $this->post("/{$name}", "{$controller}@store")->name("{$name}.store");

        // show: GET /resource/{id}
        $this->get("/{$name}/{id}", "{$controller}@show")->name("{$name}.show");

        // edit: GET /resource/{id}/edit
        $this->get("/{$name}/{id}/edit", "{$controller}@edit")->name("{$name}.edit");

        // update: PUT /resource/{id}
        $this->put("/{$name}/{id}", "{$controller}@update")->name("{$name}.update");

        // destroy: DELETE /resource/{id}
        $this->delete("/{$name}/{id}", "{$controller}@destroy")->name("{$name}.destroy");
    }

    /**
     * Add a route to the collection
     * 
     * @param string $method HTTP method
     * @param string $path Route path
     * @param mixed $handler Handler
     * @return RouteRegistrar
     */
    protected function addRoute($method, $path, $handler)
    {
        $route = new Route($method, $path, $handler);
        $this->routes[] = $route;
        return new RouteRegistrar($route, $this);
    }

    /**
     * Match incoming request to a route
     * 
     * @param string $method HTTP method
     * @param string $path Request path
     * @return Route|null
     */
    public function match($method, $path)
    {
        foreach ($this->routes as $route) {
            if ($route->matches($method, $path)) {
                return $route;
            }
        }
        return null;
    }

    /**
     * Get a named route
     * 
     * @param string $name Route name
     * @return Route|null
     */
    public function getNamedRoute($name)
    {
        return $this->namedRoutes[$name] ?? null;
    }

    /**
     * Register a named route
     * 
     * @param Route $route
     * @param string $name Route name
     * @return void
     */
    public function registerNamedRoute(Route $route, $name)
    {
        $this->namedRoutes[$name] = $route;
    }

    /**
     * Get all routes
     * 
     * @return array
     */
    public function getRoutes()
    {
        return $this->routes;
    }
}

/**
 * Route - Individual route definition
 */
class Route
{
    /**
     * HTTP method
     * @var string
     */
    public $method;

    /**
     * Path pattern
     * @var string
     */
    public $path;

    /**
     * Handler
     * @var mixed
     */
    public $handler;

    /**
     * Middleware stack
     * @var array
     */
    public $middleware = [];

    /**
     * Route parameters
     * @var array
     */
    public $parameters = [];

    /**
     * Route constraints
     * @var array
     */
    public $constraints = [];

    /**
     * Constructor
     * 
     * @param string $method HTTP method
     * @param string $path Route path
     * @param mixed $handler Handler
     */
    public function __construct($method, $path, $handler)
    {
        $this->method = $method;
        $this->path = $path;
        $this->handler = $handler;
    }

    /**
     * Check if route matches request
     * 
     * @param string $method HTTP method
     * @param string $path Request path
     * @return bool
     */
    public function matches($method, $path)
    {
        if ($this->method !== $method) {
            return false;
        }

        $pattern = $this->convertPathToRegex($this->path);
        
        if (preg_match($pattern, $path, $matches)) {
            // Extract parameters
            foreach ($matches as $key => $value) {
                if (!is_numeric($key) && $key !== 0) {
                    $this->parameters[$key] = $value;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Convert path pattern to regex
     * 
     * @param string $path Path pattern like /users/{id}
     * @return string
     */
    protected function convertPathToRegex($path)
    {
        $pattern = preg_replace_callback('/\{(\w+)\}/', function ($matches) {
            $name = $matches[1];
            $constraint = $this->constraints[$name] ?? '\d+'; // default: digits only
            return "(?P<{$name}>{$constraint})";
        }, $path);

        return '#^' . $pattern . '$#';
    }

    /**
     * Get parameter from matched route
     * 
     * @param string $name Parameter name
     * @param mixed $default Default value
     * @return mixed
     */
    public function parameter($name, $default = null)
    {
        return $this->parameters[$name] ?? $default;
    }

    /**
     * Get all parameters
     * 
     * @return array
     */
    public function parameters()
    {
        return $this->parameters;
    }
}

/**
 * RouteRegistrar - Fluent interface for route registration
 */
class RouteRegistrar
{
    /**
     * Route being registered
     * @var Route
     */
    protected $route;

    /**
     * Router instance
     * @var Router
     */
    protected $router;

    /**
     * Constructor
     * 
     * @param Route $route
     * @param Router $router
     */
    public function __construct(Route $route, Router $router)
    {
        $this->route = $route;
        $this->router = $router;
    }

    /**
     * Add middleware to route
     * 
     * @param array $middleware Middleware names
     * @return self
     */
    public function middleware($middleware)
    {
        if (!is_array($middleware)) {
            $middleware = [$middleware];
        }
        $this->route->middleware = array_merge($this->route->middleware, $middleware);
        return $this;
    }

    /**
     * Add parameter constraint
     * 
     * @param string $name Parameter name
     * @param string $pattern Regex pattern
     * @return self
     */
    public function where($name, $pattern)
    {
        $this->route->constraints[$name] = $pattern;
        return $this;
    }

    /**
     * Name the route
     * 
     * @param string $name Route name
     * @return self
     */
    public function name($name)
    {
        $this->router->registerNamedRoute($this->route, $name);
        return $this;
    }
}
