<?php
/**
 * API Entry Point
 * 
 * Handles all REST API requests
 * 
 * Usage: POST /api.php?action=users&method=list
 */

require __DIR__ . '/vendor/autoload.php';

use App\Foundation\ServiceContainer;
use App\Foundation\Response;

// Create container and bootstrap services
$container = new ServiceContainer();
$bootstrap = require __DIR__ . '/bootstrap/app.php';
$bootstrap($container);

try {
    // Get application and run
    $app = $container->get('app');
    $app->run();
} catch (Exception $e) {
    // API error response
    $response = new Response();
    $response->status(500)
        ->json(['error' => $e->getMessage()])
        ->send();
}
