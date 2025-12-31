<?php

namespace App\Foundation;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use Exception;

/**
 * Service Container - Dependency Injection Container
 * 
 * Manages service registration, resolution, and instantiation with support for:
 * - Factory functions
 * - Class resolution with constructor injection
 * - Singleton caching
 * - Binding interfaces to implementations
 */
class ServiceContainer
{
    /**
     * Registered services and factories
     * @var array
     */
    protected $services = [];

    /**
     * Singleton instances cache
     * @var array
     */
    protected $singletons = [];

    /**
     * Bindings (interface to implementation)
     * @var array
     */
    protected $bindings = [];

    /**
     * Register a service factory
     * 
     * @param string $name Service name
     * @param Closure|string $factory Factory function or class name
     * @return void
     */
    public function register($name, $factory)
    {
        $this->services[$name] = $factory;
    }

    /**
     * Register a singleton service (single instance, reused)
     * 
     * @param string $name Service name
     * @param Closure|string $factory Factory function or class name
     * @return void
     */
    public function singleton($name, $factory)
    {
        $this->register($name, $factory);
        $this->singletons[$name] = true;
    }

    /**
     * Bind an interface to an implementation
     * 
     * @param string $abstract Interface name
     * @param string $concrete Implementation class
     * @return void
     */
    public function bind($abstract, $concrete)
    {
        $this->bindings[$abstract] = $concrete;
    }

    /**
     * Get a service instance
     * 
     * @param string $name Service name
     * @return mixed
     * @throws Exception
     */
    public function get($name)
    {
        // Check if already resolved as singleton
        if (isset($this->singletons[$name])) {
            if (isset($this->singletons[$name . '_instance'])) {
                return $this->singletons[$name . '_instance'];
            }
        }

        // Resolve the service
        $instance = $this->resolve($name);

        // Cache singleton if registered
        if (isset($this->singletons[$name])) {
            $this->singletons[$name . '_instance'] = $instance;
        }

        return $instance;
    }

    /**
     * Resolve a service
     * 
     * @param string $name Service name
     * @return mixed
     * @throws Exception
     */
    protected function resolve($name)
    {
        // Check if service is registered
        if (!isset($this->services[$name])) {
            throw new Exception("Service not registered: {$name}");
        }

        $factory = $this->services[$name];

        // If factory is a Closure, call it
        if ($factory instanceof Closure) {
            return $factory($this);
        }

        // If factory is a string, try to instantiate the class
        if (is_string($factory)) {
            return $this->instantiate($factory);
        }

        throw new Exception("Invalid factory for service: {$name}");
    }

    /**
     * Instantiate a class with constructor injection
     * 
     * @param string $className Class name
     * @return object
     * @throws Exception
     */
    protected function instantiate($className)
    {
        // Check if binding exists
        if (isset($this->bindings[$className])) {
            $className = $this->bindings[$className];
        }

        try {
            $reflection = new ReflectionClass($className);
            $constructor = $reflection->getConstructor();

            // If no constructor, just instantiate
            if ($constructor === null) {
                return new $className();
            }

            // Get constructor parameters and resolve them
            $params = $constructor->getParameters();
            $args = [];

            foreach ($params as $param) {
                $paramClass = $param->getClass();

                if ($paramClass !== null) {
                    // Try to resolve the parameter as a service
                    $args[] = $this->instantiate($paramClass->getName());
                } else if ($param->isDefaultValueAvailable()) {
                    // Use default value
                    $args[] = $param->getDefaultValue();
                } else {
                    throw new Exception(
                        "Cannot resolve parameter: {$param->getName()} in {$className}"
                    );
                }
            }

            return new $className(...$args);
        } catch (Exception $e) {
            throw new Exception("Error instantiating {$className}: " . $e->getMessage());
        }
    }

    /**
     * Check if a service is registered
     * 
     * @param string $name Service name
     * @return bool
     */
    public function has($name)
    {
        return isset($this->services[$name]);
    }

    /**
     * Magic method to get services like properties
     * 
     * @param string $name Service name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Get all registered services
     * 
     * @return array
     */
    public function services()
    {
        return array_keys($this->services);
    }
}
