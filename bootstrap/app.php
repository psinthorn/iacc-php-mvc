<?php
/**
 * Service Registration and Bootstrapping
 * 
 * Register all services in the container here
 */

use App\Foundation\ServiceContainer;
use App\Foundation\Router;
use App\Foundation\Config;
use App\Foundation\Logger;
use App\Foundation\Application;

return function (ServiceContainer $container) {
    // Register configuration
    $container->singleton('config', function ($container) {
        $config = new Config();
        $config->loadFromPath(__DIR__ . '/../config');
        return $config;
    });

    // Register logger
    $container->singleton('logger', function ($container) {
        $config = $container->get('config');
        $logFile = __DIR__ . '/../storage/logs/app.log';
        $minLevel = $config->get('app.debug') ? 'DEBUG' : 'INFO';
        return new Logger($logFile, $minLevel);
    });

    // Register router
    $container->singleton('router', function ($container) {
        return new Router();
    });

    // Register application
    $container->singleton('app', function ($container) {
        $app = new Application(
            $container,
            $container->get('router'),
            $container->get('config'),
            $container->get('logger')
        );

        // Set up custom exception handler if needed
        // $app->setExceptionHandler(function ($exception) { ... });

        return $app;
    });
};
