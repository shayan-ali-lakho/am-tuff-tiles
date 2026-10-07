<?php

declare(strict_types=1);

use App\Controllers\Admin\DashboardController;
use App\Controllers\AuthController;
use App\Controllers\HomeController;
use App\Controllers\PageController;

/** @var App\Core\Router $router */

// Public pages
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [PageController::class, 'about']);

// Accounts
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// Admin (the router already requires an admin login for every /admin path)
$router->get('/admin', [DashboardController::class, 'index']);
