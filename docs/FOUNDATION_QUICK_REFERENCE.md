# Phase 4 Step 2: Foundation Classes Quick Reference

This guide provides quick access to the core foundation classes and their usage patterns.

## Service Container

```php
use App\Foundation\ServiceContainer;

// Create container
$container = new ServiceContainer();

// Register a service with factory
$container->register('database', function($c) {
    return new Database($c->get('config'));
});

// Register singleton (single instance)
$container->singleton('cache', function($c) {
    return new RedisCache();
});

// Bind interface to implementation
$container->bind('DatabaseInterface', 'App\Database\MySQLDatabase');

// Resolve service
$db = $container->get('database');

// Check if service exists
if ($container->has('cache')) {
    $cache = $container->get('cache');
}
```

## Router

```php
use App\Foundation\Router;

$router = new Router();

// Register routes
$router->get('/users', 'UserController@index')->name('users.index');
$router->post('/users', 'UserController@store')->name('users.store');
$router->get('/users/{id}', 'UserController@show')
    ->where('id', '\d+')
    ->name('users.show');

// Update/delete
$router->put('/users/{id}', 'UserController@update')->name('users.update');
$router->delete('/users/{id}', 'UserController@destroy')->name('users.destroy');

// Multiple methods
$router->match(['GET', 'POST'], '/dashboard', 'DashboardController@index');

// RESTful resource (auto-generates CRUD routes)
$router->resource('posts', 'PostController');

// Add middleware to route
$router->post('/admin/users', 'AdminController@createUser')
    ->middleware('AuthenticationMiddleware');

// Get matched route
$route = $router->match('GET', '/users');
if ($route) {
    $params = $route->parameters();
}
```

## Request

```php
use App\Foundation\Request;

// Get query parameters
$page = $request->query('page', 1);
$search = $request->query('search');

// Get POST/form data
$name = $request->input('name');
$email = $request->input('email');

// Get multiple inputs
$data = $request->input(['name', 'email', 'phone']);

// Get all input (query + post)
$all = $request->all();

// Check if input exists
if ($request->has('email')) {
    // ...
}

// Check if empty
if ($request->isEmpty('password')) {
    // ...
}

// Get headers
$authHeader = $request->header('Authorization');
$contentType = $request->header('Content-Type');

// Get route parameters (set by router)
$userId = $request->route('id');

// Upload files
$file = $request->file('upload');
if ($file) {
    $file->move('/storage/uploads/document.pdf');
}

// Request metadata
$method = $request->method(); // GET, POST, etc
$path = $request->path();     // /users/123
$ip = $request->ip();
$agent = $request->userAgent();

// Type checks
if ($request->isPost()) { }
if ($request->isGet()) { }
if ($request->isAjax()) { }
```

## Response

```php
use App\Foundation\Response;

// JSON response
return (new Response())
    ->json(['success' => true, 'id' => 123])
    ->status(201);

// View response
return (new Response())
    ->view('users/show', ['user' => $user])
    ->status(200);

// Plain text
return (new Response())
    ->text('Hello World')
    ->status(200);

// Redirect
return (new Response())
    ->redirect('/users')
    ->status(302);

// Download file
return (new Response())
    ->download('/path/to/file.pdf', 'document.pdf');

// Set headers and cookies
$response = new Response();
$response->header('X-Custom-Header', 'value');
$response->cookie('session', 'abc123', time() + 3600);
$response->status(200);
$response->json(['data' => $data]);

// Send response
$response->send();
```

## Configuration

```php
use App\Foundation\Config;

// Create config
$config = new Config();

// Load from directory
$config->loadFromPath('./config');

// Get values (dot notation for nested keys)
$dbHost = $config->get('database.mysql.host');
$dbPort = $config->get('database.mysql.port');
$appName = $config->get('app.name');

// Get with default
$timeout = $config->get('cache.timeout', 3600);

// Set values
$config->set('app.debug', true);

// Check existence
if ($config->has('app.name')) {
    // ...
}

// Get array
$dbConfig = $config->getArray('database.mysql');

// Get from environment
$secret = $config->getEnv('APP_SECRET');

// Magic property access
$name = $config->app; // equivalent to $config->get('app')
```

## Logger

```php
use App\Foundation\Logger;

$logger = new Logger('./storage/logs/app.log', 'INFO');

// Log at different levels
$logger->debug('Debugging info', ['user_id' => 123]);
$logger->info('User logged in', ['user' => 'john@example.com']);
$logger->warning('Cache miss', ['key' => 'users']);
$logger->error('Database error', ['query' => 'SELECT...']);

// Get log contents
$logs = $logger->getContents();
$lastLogs = $logger->getLastEntries(10);

// Clear log
$logger->clear();

// Get log file path
$path = $logger->getLogFile();
```

## Exceptions

```php
use App\Exceptions\ValidationException;
use App\Exceptions\NotFoundException;
use App\Exceptions\AuthenticationException;
use App\Exceptions\AuthorizationException;

// Validation error (422)
throw new ValidationException([
    'email' => ['Email is required'],
    'password' => ['Password must be 8+ characters']
]);

// Not found (404)
throw new NotFoundException('User not found');

// Authentication required (401)
throw new AuthenticationException('Login required');

// Access denied (403)
throw new AuthorizationException('Admin access required');

// Use in controller
try {
    $user = $this->getUserOrFail($id);
} catch (NotFoundException $e) {
    return response()->status(404)->json(['error' => $e->getMessage()]);
}
```

## Middleware

```php
use App\Middleware\Middleware;
use App\Middleware\MiddlewarePipeline;

// Create custom middleware
class CustomMiddleware extends Middleware {
    public function handle(Request $request, callable $next) {
        // Do something before
        $response = $next($request);
        // Do something after
        return $response;
    }
    
    public function terminate(Request $request, Response $response) {
        // Cleanup/finalization
    }
}

// Use middleware pipeline
$pipeline = new MiddlewarePipeline(function($request) {
    return new Response('Hello');
});

$pipeline->add('App\Middleware\AuthenticationMiddleware');
$pipeline->add(new CorsMiddleware(['*']));
$pipeline->add('App\Middleware\LoggingMiddleware');

$response = $pipeline->execute($request);
```

## Application

```php
use App\Foundation\Application;

// Application is usually bootstrapped in bootstrap/app.php
$app = $container->get('app');

// Handle a request
$response = $app->handleRequest($request);

// Set custom exception handler
$app->setExceptionHandler(function($exception) {
    // Custom exception handling
});

// Run from globals
$app->run();

// Accessors
$container = $app->getContainer();
$router = $app->getRouter();
$config = $app->getConfig();
$logger = $app->getLogger();
```

## Helper Functions

```php
// Get application instance
$app = app();

// Get config value
$name = config('app.name');
$timeout = config('cache.timeout', 3600);

// Get logger
$logger = logger();
$logger->info('Something happened');

// Get router
$router = router();

// Log message
log_message('User action', 'info');

// Abort with status
abort(404, 'Not found');

// Create responses
return redirect('/home');
return json_response(['status' => 'ok']);
return view_response('users.show', ['user' => $user]);

// Path helpers
$base = base_path();
$storage = storage_path('logs');
$config = config_path();
$resources = resource_path('views');

// Escape HTML
echo html($userInput);

// Debug
dd($variable);
```

## Entry Points

### Web Entry Point (index.php)
```php
require __DIR__ . '/vendor/autoload.php';

use App\Foundation\ServiceContainer;

$container = new ServiceContainer();
$bootstrap = require __DIR__ . '/bootstrap/app.php';
$bootstrap($container);

$app = $container->get('app');
$app->run();
```

### API Entry Point (api.php)
```php
// Same as above, but handles exceptions with JSON responses
```

## File Structure

```
src/Foundation/
├── ServiceContainer.php    # DI container
├── Router.php              # URL routing
├── Request.php             # HTTP request abstraction
├── Response.php            # HTTP response builder
├── Config.php              # Configuration management
├── Logger.php              # Logging system
└── Application.php         # Main application class

src/Exceptions/
└── BaseException.php       # Exception hierarchy

src/Middleware/
└── Middleware.php          # Middleware and pipeline

config/
├── app.php                 # Application config
└── database.php            # Database config

bootstrap/
├── app.php                 # Service registration
└── helpers.php             # Helper functions
```

## Next Steps

Once the foundation is in place, Phase 4 Step 3 will focus on:
1. Creating Model classes for each database table
2. Building Repository pattern for data access
3. Implementing Service layer for business logic
4. Creating Controller classes
5. Building Request validation classes

See `PHASE_4_STEP_2_COMPLETION_REPORT.md` for detailed documentation.
