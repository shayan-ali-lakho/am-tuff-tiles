<?php

declare(strict_types=1);

use App\Controllers\Admin\CategoryController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\ProductController;
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

// Admin. The router already requires an admin login for every /admin path.
$router->get('/admin', [DashboardController::class, 'index']);

$router->get('/admin/products', [ProductController::class, 'index']);
$router->get('/admin/products/new', [ProductController::class, 'create']);
$router->post('/admin/products', [ProductController::class, 'store']);
$router->get('/admin/products/{id}/edit', [ProductController::class, 'edit']);
$router->post('/admin/products/{id}', [ProductController::class, 'update']);
$router->post('/admin/products/{id}/toggle', [ProductController::class, 'toggle']);
$router->post('/admin/products/{id}/delete', [ProductController::class, 'destroy']);
$router->post('/admin/products/{id}/images/{imageId}/primary', [ProductController::class, 'makePrimary']);
$router->post('/admin/products/{id}/images/{imageId}/delete', [ProductController::class, 'deleteImage']);

$router->get('/admin/categories', [CategoryController::class, 'index']);
$router->post('/admin/categories', [CategoryController::class, 'store']);
$router->post('/admin/categories/{id}', [CategoryController::class, 'update']);
$router->post('/admin/categories/{id}/delete', [CategoryController::class, 'destroy']);
