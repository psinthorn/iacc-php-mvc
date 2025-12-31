<?php

namespace App\Foundation;

/**
 * Response - HTTP Response Builder
 * 
 * Encapsulates HTTP response with support for:
 * - JSON responses
 * - View rendering
 * - Redirects
 * - Headers and status codes
 * - Cookie setting
 */
class Response
{
    /**
     * Response status code
     * @var int
     */
    protected $statusCode = 200;

    /**
     * Response headers
     * @var array
     */
    protected $headers = [];

    /**
     * Response body
     * @var string|mixed
     */
    protected $body = '';

    /**
     * Content type
     * @var string
     */
    protected $contentType = 'text/html';

    /**
     * HTTP status texts
     * @var array
     */
    protected static $statusTexts = [
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        409 => 'Conflict',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
    ];

    /**
     * Constructor
     * 
     * @param mixed $body Response body
     * @param int $status HTTP status code
     * @param array $headers Response headers
     */
    public function __construct($body = '', $status = 200, $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $status;
        $this->headers = $headers;
    }

    /**
     * Set response status code
     * 
     * @param int $code
     * @return self
     */
    public function status($code)
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Get status code
     * 
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Set a header
     * 
     * @param string $key Header name
     * @param string $value Header value
     * @return self
     */
    public function header($key, $value)
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * Get all headers
     * 
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * JSON response
     * 
     * @param array $data Data to encode as JSON
     * @param int $status HTTP status code
     * @return self
     */
    public function json($data, $status = 200)
    {
        $this->body = json_encode($data);
        $this->contentType = 'application/json';
        $this->statusCode = $status;
        return $this;
    }

    /**
     * View response (HTML)
     * 
     * @param string $view View file path
     * @param array $data Data to pass to view
     * @param int $status HTTP status code
     * @return self
     */
    public function view($view, $data = [], $status = 200)
    {
        $this->body = $this->renderView($view, $data);
        $this->contentType = 'text/html';
        $this->statusCode = $status;
        return $this;
    }

    /**
     * Render a view file
     * 
     * @param string $view
     * @param array $data
     * @return string
     */
    protected function renderView($view, $data = [])
    {
        $viewPath = realpath(__DIR__ . '/../../resources/views/' . $view . '.php');
        
        if (!$viewPath || !file_exists($viewPath)) {
            return "<h1>View not found: {$view}</h1>";
        }

        // Extract data as variables
        extract($data);

        // Capture output
        ob_start();
        require $viewPath;
        return ob_get_clean();
    }

    /**
     * Plain text response
     * 
     * @param string $text
     * @param int $status
     * @return self
     */
    public function text($text, $status = 200)
    {
        $this->body = $text;
        $this->contentType = 'text/plain';
        $this->statusCode = $status;
        return $this;
    }

    /**
     * Redirect response
     * 
     * @param string $url Redirect URL
     * @param int $status HTTP status code (301 or 302)
     * @return self
     */
    public function redirect($url, $status = 302)
    {
        $this->header('Location', $url);
        $this->statusCode = $status;
        return $this;
    }

    /**
     * Download file response
     * 
     * @param string $filePath File path
     * @param string $filename Optional: custom filename
     * @return self
     */
    public function download($filePath, $filename = null)
    {
        if (!file_exists($filePath)) {
            return $this->status(404)->text('File not found');
        }

        $filename = $filename ?: basename($filePath);
        
        $this->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
        $this->header('Content-Type', 'application/octet-stream');
        $this->header('Content-Length', filesize($filePath));
        
        $this->body = file_get_contents($filePath);
        return $this;
    }

    /**
     * Set a cookie
     * 
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param int $expire Expiration time (unix timestamp)
     * @param string $path Cookie path
     * @param string $domain Cookie domain
     * @param bool $secure HTTPS only
     * @param bool $httpOnly HTTP only
     * @return self
     */
    public function cookie($name, $value, $expire = 0, $path = '/', $domain = '', $secure = false, $httpOnly = true)
    {
        setcookie($name, $value, $expire, $path, $domain, $secure, $httpOnly);
        return $this;
    }

    /**
     * Send response headers and body
     * 
     * @return void
     */
    public function send()
    {
        // Set status code
        http_response_code($this->statusCode);

        // Set content-type header
        $this->header('Content-Type', $this->contentType . '; charset=UTF-8');

        // Send headers
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }

        // Send body
        if ($this->body) {
            echo $this->body;
        }
    }

    /**
     * Get response body
     * 
     * @return string|mixed
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Get content type
     * 
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }

    /**
     * Get status text for code
     * 
     * @return string
     */
    public function getStatusText()
    {
        return self::$statusTexts[$this->statusCode] ?? 'Unknown';
    }
}
