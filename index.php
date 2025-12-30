<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// phpinfo(); 

// // Router config file
// require "./src/router.php";

// Autoloder class with file name
spl_autoload_register(function(string $class_name){
    $path = "src/" . str_replace("\\", "/", $class_name) . ".php";
    if (!file_exists($path)) {
        echo "<!-- File not found: $path -->";
    }
    require $path;
});

// Add new routes
$router = new Framework\Router;
$router->add("/home/index", ["controller" => "home", "action" => "index"]);
$router->add("/products", ["controller" => "products", "action" => "index"]);
$router->add("/", ["controller" => "home", "action" => "index"]);

// print_r($router);

// To get URI parameter from path
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

// // Use explode to separate url by "/"
// $segments = explode("/", $path);

// Match router with path from url with match function and assing return values to $params variables
$params = $router->match($path);

if($params === false){
    exit("No route matches for: " . $path);
}
// // print for recheck value in segments variables
// var_dump($params);
// exit();
// use ucwords for capitalize 1st character
$controller = "App\Controllers\\" . ucwords($params["controller"]);
$action = $params["action"];

// // Require controller file follow the query string request
// require "./src/controllers/$controller.php";

// Create controller object from controller class
if (!class_exists($controller)) {
    exit("Controller class not found: " . $controller);
}

$controller_object = new $controller;

// Call a controller public function follow action from query string
if (!method_exists($controller_object, $action)) {
    exit("Method not found: " . $action);
}

$controller_object->$action();
