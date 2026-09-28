<?php
/**
 * Application routes.
 *
 * Executed by public/index.php with $router already instantiated.
 * Patterns support named parameters: 'jobs/{id:\d+}'.
 */

use App\Core\Router;

$router->get('/', [App\Controllers\HomeController::class, 'index']);
