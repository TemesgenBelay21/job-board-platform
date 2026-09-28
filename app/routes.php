<?php
/**
 * Application routes.
 *
 * Executed by public/index.php with $router already instantiated.
 * Patterns support named parameters: 'jobs/{id:\d+}'.
 *
 * Role checks are not declared here on purpose: each protected controller
 * calls requireRole() so a URL cannot be reached by another role's guess.
 */

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\HomeController;

$router->get('/', [HomeController::class, 'index']);

// Authentication ------------------------------------------------------------
$router->get('auth/register', [AuthController::class, 'showRegister']);
$router->post('auth/register', [AuthController::class, 'register']);
$router->get('auth/login', [AuthController::class, 'showLogin']);
$router->post('auth/login', [AuthController::class, 'login']);
$router->post('auth/logout', [AuthController::class, 'logout']);

// Password recovery ----------------------------------------------------------
$router->get('auth/forgot-password', [AuthController::class, 'showForgotPassword']);
$router->post('auth/forgot-password', [AuthController::class, 'sendResetLink']);
$router->get('auth/reset-password', [AuthController::class, 'showResetPassword']);
$router->post('auth/reset-password', [AuthController::class, 'resetPassword']);
