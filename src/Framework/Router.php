<?php
namespace Framework;

class Router
{
    private $routes = [];
    public function add(string $path, array $params): void
    {
        $this->routes[] = [
            "path" => $path,
            "params" => $params
        ];
    }

    public function match(string $path)
    {
        // Match root path
        if ($path === "/") {
            return ["controller" => "home", "action" => "index"];
        }
        
        // Match /controller/action pattern
        $pattern = "#^/(?<controller>[a-z]+)/(?<action>[a-z]+)$#";
        if(preg_match($pattern, $path, $matches)){
            $matches = array_filter($matches, "is_string", ARRAY_FILTER_USE_KEY);
            return $matches;
        }

        // foreach ($this->routes as $route) {
        //     if ($route["path"] === $path) {
        //         return $route["params"];
        //     } 
        // }
        return false;
    }
}
