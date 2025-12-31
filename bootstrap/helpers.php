<?php
/**
 * Bootstrap Helper Functions
 * 
 * Global utility functions available throughout the application
 */

use App\Foundation\Application;
use App\Foundation\Config;
use App\Foundation\Logger;
use App\Foundation\Router;

/**
 * Get the application instance
 * 
 * @return Application
 */
function app()
{
    static $app;
    if ($app === null) {
        throw new RuntimeException('Application not bootstrapped');
    }
    return $app;
}

/**
 * Get a value from configuration
 * 
 * @param string $key Configuration key
 * @param mixed $default Default value
 * @return mixed
 */
function config($key, $default = null)
{
    return app()->getConfig()->get($key, $default);
}

/**
 * Log a message
 * 
 * @param string $message
 * @param string $level
 * @return void
 */
function log_message($message, $level = 'info')
{
    $logger = app()->getLogger();
    if (method_exists($logger, $level)) {
        $logger->$level($message);
    }
}

/**
 * Get the logger
 * 
 * @return Logger
 */
function logger()
{
    return app()->getLogger();
}

/**
 * Get the router
 * 
 * @return Router
 */
function router()
{
    return app()->getRouter();
}

/**
 * Abort with HTTP status and message
 * 
 * @param int $code HTTP status code
 * @param string $message Error message
 * @throws Exception
 */
function abort($code, $message = '')
{
    throw new Exception($message ?: "HTTP {$code}", $code);
}

/**
 * Redirect to a URL
 * 
 * @param string $url
 * @param int $status HTTP status code
 * @return Response
 */
function redirect($url, $status = 302)
{
    $response = new \App\Foundation\Response();
    return $response->redirect($url, $status);
}

/**
 * Create a JSON response
 * 
 * @param array $data
 * @param int $status HTTP status code
 * @return Response
 */
function json_response($data, $status = 200)
{
    $response = new \App\Foundation\Response();
    return $response->json($data, $status);
}

/**
 * Create a view response
 * 
 * @param string $view View file name
 * @param array $data View data
 * @param int $status HTTP status code
 * @return Response
 */
function view_response($view, $data = [], $status = 200)
{
    $response = new \App\Foundation\Response();
    return $response->view($view, $data, $status);
}

/**
 * Get base path of application
 * 
 * @param string $path Optional path to append
 * @return string
 */
function base_path($path = '')
{
    static $basePath;
    if ($basePath === null) {
        $basePath = dirname(__DIR__);
    }
    return $basePath . ($path ? DIRECTORY_SEPARATOR . $path : $path);
}

/**
 * Get storage path
 * 
 * @param string $path Optional path to append
 * @return string
 */
function storage_path($path = '')
{
    return base_path('storage' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
}

/**
 * Get config path
 * 
 * @param string $path Optional path to append
 * @return string
 */
function config_path($path = '')
{
    return base_path('config' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
}

/**
 * Get resources path
 * 
 * @param string $path Optional path to append
 * @return string
 */
function resource_path($path = '')
{
    return base_path('resources' . ($path ? DIRECTORY_SEPARATOR . $path : $path));
}

/**
 * Escape HTML
 * 
 * @param string $string
 * @return string
 */
function html($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Dump and die
 * 
 * @param mixed $value
 * @return void
 */
function dd($value)
{
    echo '<pre>';
    var_dump($value);
    echo '</pre>';
    die;
}
