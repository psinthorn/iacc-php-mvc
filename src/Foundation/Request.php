<?php

namespace App\Foundation;

/**
 * Request - HTTP Request Abstraction
 * 
 * Encapsulates HTTP request data with convenient accessors for:
 * - Query parameters
 * - POST/form data
 * - Headers
 * - Route parameters
 * - Files
 * - Cookies
 */
class Request
{
    /**
     * Request method
     * @var string
     */
    protected $method;

    /**
     * Request path
     * @var string
     */
    protected $path;

    /**
     * Query parameters
     * @var array
     */
    protected $query;

    /**
     * POST/form data
     * @var array
     */
    protected $data;

    /**
     * HTTP headers
     * @var array
     */
    protected $headers;

    /**
     * Route parameters
     * @var array
     */
    protected $routeParameters = [];

    /**
     * Uploaded files
     * @var array
     */
    protected $files;

    /**
     * Cookies
     * @var array
     */
    protected $cookies;

    /**
     * Authenticated user
     * @var array|null
     */
    protected $user;

    /**
     * Constructor
     * 
     * @param array $server $_SERVER
     * @param array $get $_GET
     * @param array $post $_POST
     * @param array $files $_FILES
     * @param array $cookie $_COOKIE
     */
    public function __construct($server = [], $get = [], $post = [], $files = [], $cookie = [])
    {
        $server = $server ?: $_SERVER;
        $this->method = $server['REQUEST_METHOD'] ?? 'GET';
        $this->path = $this->parsePath($server['REQUEST_URI'] ?? '/');
        $this->query = $get ?: $_GET;
        $this->data = $post ?: $_POST;
        $this->files = $files ?: $_FILES;
        $this->cookies = $cookie ?: $_COOKIE;
        $this->headers = $this->parseHeaders($server);
    }

    /**
     * Parse request path from URI
     * 
     * @param string $uri
     * @return string
     */
    protected function parsePath($uri)
    {
        // Remove query string
        $path = explode('?', $uri)[0];
        // Remove trailing slash
        return rtrim($path, '/') ?: '/';
    }

    /**
     * Parse headers from $_SERVER
     * 
     * @param array $server
     * @return array
     */
    protected function parseHeaders($server)
    {
        $headers = [];
        foreach ($server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $key = str_replace('HTTP_', '', $key);
                $key = str_replace('_', '-', strtolower($key));
                $headers[$key] = $value;
            }
        }
        return $headers;
    }

    /**
     * Get request method
     * 
     * @return string
     */
    public function method()
    {
        return $this->method;
    }

    /**
     * Get request path
     * 
     * @return string
     */
    public function path()
    {
        return $this->path;
    }

    /**
     * Check if request is a specific method
     * 
     * @param string $method
     * @return bool
     */
    public function isMethod($method)
    {
        return strtoupper($this->method) === strtoupper($method);
    }

    /**
     * Check if request is GET
     * 
     * @return bool
     */
    public function isGet()
    {
        return $this->isMethod('GET');
    }

    /**
     * Check if request is POST
     * 
     * @return bool
     */
    public function isPost()
    {
        return $this->isMethod('POST');
    }

    /**
     * Check if AJAX request (via header check)
     * 
     * @return bool
     */
    public function isAjax()
    {
        return strtolower($this->header('x-requested-with')) === 'xmlhttprequest';
    }

    /**
     * Get a query parameter
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function query($key = null, $default = null)
    {
        if ($key === null) {
            return $this->query;
        }
        return $this->query[$key] ?? $default;
    }

    /**
     * Get input data (POST/form)
     * 
     * @param string|array $key Field name or array of field names
     * @param mixed $default
     * @return mixed
     */
    public function input($key = null, $default = null)
    {
        if ($key === null) {
            return $this->data;
        }

        // Handle multiple inputs: input(['name', 'email'])
        if (is_array($key)) {
            $result = [];
            foreach ($key as $k) {
                $result[$k] = $this->data[$k] ?? $default;
            }
            return $result;
        }

        return $this->data[$key] ?? $default;
    }

    /**
     * Get all input (query + data)
     * 
     * @return array
     */
    public function all()
    {
        return array_merge($this->query, $this->data);
    }

    /**
     * Check if input field exists
     * 
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return isset($this->data[$key]) || isset($this->query[$key]);
    }

    /**
     * Check if input is empty
     * 
     * @param string $key
     * @return bool
     */
    public function isEmpty($key)
    {
        return empty($this->input($key));
    }

    /**
     * Get a header value
     * 
     * @param string $key Header name
     * @param mixed $default
     * @return string
     */
    public function header($key, $default = null)
    {
        $key = strtolower(str_replace('_', '-', $key));
        return $this->headers[$key] ?? $default;
    }

    /**
     * Get all headers
     * 
     * @return array
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * Get route parameter
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function route($key = null, $default = null)
    {
        if ($key === null) {
            return $this->routeParameters;
        }
        return $this->routeParameters[$key] ?? $default;
    }

    /**
     * Set route parameters
     * 
     * @param array $parameters
     * @return void
     */
    public function setRouteParameters($parameters)
    {
        $this->routeParameters = $parameters;
    }

    /**
     * Get uploaded file
     * 
     * @param string $key File field name
     * @return UploadedFile|null
     */
    public function file($key)
    {
        if (isset($this->files[$key])) {
            return new UploadedFile($this->files[$key]);
        }
        return null;
    }

    /**
     * Get a cookie value
     * 
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function cookie($key, $default = null)
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Get request IP address
     * 
     * @return string
     */
    public function ip()
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get User-Agent
     * 
     * @return string
     */
    public function userAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Set authenticated user
     * 
     * @param array $user
     * @return void
     */
    public function setUser(array $user)
    {
        $this->user = $user;
    }

    /**
     * Get authenticated user
     * 
     * @return array|null
     */
    public function user()
    {
        return $this->user;
    }

    /**
     * Check if request is authenticated
     * 
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->user !== null;
    }
}

/**
 * UploadedFile - Uploaded file wrapper
 */
class UploadedFile
{
    /**
     * Original filename
     * @var string
     */
    protected $originalName;

    /**
     * MIME type
     * @var string
     */
    protected $mimeType;

    /**
     * File size
     * @var int
     */
    protected $size;

    /**
     * Temporary file path
     * @var string
     */
    protected $tempPath;

    /**
     * Constructor
     * 
     * @param array $fileData $_FILES array for this field
     */
    public function __construct($fileData)
    {
        $this->originalName = $fileData['name'] ?? '';
        $this->mimeType = $fileData['type'] ?? '';
        $this->size = $fileData['size'] ?? 0;
        $this->tempPath = $fileData['tmp_name'] ?? '';
    }

    /**
     * Get original filename
     * 
     * @return string
     */
    public function getOriginalName()
    {
        return $this->originalName;
    }

    /**
     * Get MIME type
     * 
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * Get file size in bytes
     * 
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Get temporary file path
     * 
     * @return string
     */
    public function getTempPath()
    {
        return $this->tempPath;
    }

    /**
     * Move file to destination
     * 
     * @param string $destination
     * @return bool
     */
    public function move($destination)
    {
        return move_uploaded_file($this->tempPath, $destination);
    }
}
