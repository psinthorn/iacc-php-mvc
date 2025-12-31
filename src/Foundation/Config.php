<?php

namespace App\Foundation;

/**
 * Config - Configuration Management
 * 
 * Loads and manages application configuration with support for:
 * - Nested configuration values (array.key.subkey)
 * - Environment variables
 * - Default values
 * - Configuration caching
 */
class Config
{
    /**
     * Configuration values
     * @var array
     */
    protected $config = [];

    /**
     * Constructor
     * 
     * @param array $values Initial configuration values
     */
    public function __construct($values = [])
    {
        $this->config = $values;
    }

    /**
     * Load configuration from directory
     * 
     * @param string $path Config directory path
     * @return void
     */
    public function loadFromPath($path)
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (glob($path . '/*.php') as $file) {
            $key = basename($file, '.php');
            $this->config[$key] = require $file;
        }
    }

    /**
     * Get a configuration value
     * 
     * @param string $key Key path (nested keys use dots: app.name)
     * @param mixed $default Default value
     * @return mixed
     */
    public function get($key, $default = null)
    {
        // Support nested keys: app.name.full
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (is_array($value) && isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Set a configuration value
     * 
     * @param string $key Key path
     * @param mixed $value Configuration value
     * @return void
     */
    public function set($key, $value)
    {
        $keys = explode('.', $key);
        $current = &$this->config;

        foreach ($keys as $k) {
            if (!isset($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }

    /**
     * Check if configuration key exists
     * 
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!is_array($value) || !isset($value[$k])) {
                return false;
            }
            $value = $value[$k];
        }

        return true;
    }

    /**
     * Get all configuration
     * 
     * @return array
     */
    public function all()
    {
        return $this->config;
    }

    /**
     * Get a top-level config array
     * 
     * @param string $key
     * @return array
     */
    public function getArray($key, $default = [])
    {
        $value = $this->get($key, $default);
        return is_array($value) ? $value : $default;
    }

    /**
     * Get configuration from environment variable
     * 
     * @param string $env Environment variable name
     * @param mixed $default
     * @return mixed
     */
    public function getEnv($env, $default = null)
    {
        return getenv($env) ?: $default;
    }

    /**
     * Magic method to get config like properties
     * 
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }
}
