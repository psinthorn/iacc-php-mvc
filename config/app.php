<?php
/**
 * Application Configuration
 */
return [
    'name' => 'iAcc Application',
    'env' => getenv('APP_ENV') ?: 'local',
    'debug' => getenv('APP_DEBUG') === 'true' || getenv('APP_DEBUG') === '1',
    'url' => getenv('APP_URL') ?: 'http://localhost',
    'timezone' => 'UTC',
];
